<?php

declare(strict_types=1);

use Portfolio\Support\Config;
use Portfolio\Support\ConnectionException;
use Portfolio\Support\EnvironmentLoader;
use Portfolio\Support\FileRateLimiter;
use Portfolio\Support\KadiClient;
use Portfolio\Support\RateLimiter;
use Portfolio\Support\SupportController;
use Portfolio\Support\SupportService;
use Portfolio\Support\Transport;
use Portfolio\Support\TransportResponse;

require __DIR__ . '/../src/bootstrap.php';

final class FakeTransport implements Transport
{
    /** @var list<TransportResponse|Throwable> */
    public array $queue = [];

    /** @var list<array{method: string, url: string, headers: array<string, string>, body: ?array}> */
    public array $requests = [];

    public function request(string $method, string $url, array $headers, ?array $body = null): TransportResponse
    {
        $this->requests[] = compact('method', 'url', 'headers', 'body');
        $response = array_shift($this->queue);
        if ($response instanceof Throwable) {
            throw $response;
        }
        if (!$response instanceof TransportResponse) {
            throw new RuntimeException('No fake response was queued.');
        }
        return $response;
    }
}

final class FakeRateLimiter implements RateLimiter
{
    public function __construct(private readonly bool $allowed = true)
    {
    }

    public function allow(string $key, int $maximumAttempts, int $windowSeconds): bool
    {
        return $this->allowed;
    }
}

final class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;

    public function test(string $name, callable $callback): void
    {
        try {
            $callback();
            $this->passed++;
            echo "PASS {$name}\n";
        } catch (Throwable $exception) {
            $this->failed++;
            echo "FAIL {$name}: {$exception->getMessage()}\n";
        }
    }

    public function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message !== '' ? $message : 'Values are not identical.');
        }
    }

    public function assertTrue(bool $condition, string $message = ''): void
    {
        if (!$condition) {
            throw new RuntimeException($message !== '' ? $message : 'Condition is not true.');
        }
    }

    public function finish(): never
    {
        echo "\n{$this->passed} passed, {$this->failed} failed.\n";
        exit($this->failed === 0 ? 0 : 1);
    }
}

function config(): Config
{
    return new Config(
        baseUrl: 'https://kadi.test',
        secretKey: 'unit-test-key',
        minimumAmount: 50,
        maximumAmount: 10000,
        frontendUrl: 'https://portfolio.test',
        rateLimitDirectory: sys_get_temp_dir(),
    );
}

function controller(FakeTransport $transport, ?RateLimiter $limiter = null): SupportController
{
    $configuration = config();
    return new SupportController(new SupportService(
        $configuration,
        new KadiClient($configuration, $transport),
        $limiter ?? new FakeRateLimiter(),
    ));
}

function validInput(string $phone = '0716933897', int $amount = 250): array
{
    return ['phone' => $phone, 'amount' => $amount, 'request_id' => '550e8400-e29b-41d4-a716-446655440000'];
}

function successResponse(): TransportResponse
{
    return new TransportResponse(202, [
        'transaction_id' => 'txn_public123',
        'status' => 'pending',
        'checkout_request_id' => 'not-for-browser',
        'merchant_request_id' => 'not-for-browser',
    ]);
}

$tests = new TestRunner();

$tests->test('project environment file loads without overriding process variables', function () use ($tests): void {
    $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'coffee-env-test-' . bin2hex(random_bytes(6));
    file_put_contents($path, "COFFEE_TEST_FILE_VALUE=from-file\nCOFFEE_TEST_PRIORITY=from-file\n");
    putenv('COFFEE_TEST_FILE_VALUE');
    putenv('COFFEE_TEST_PRIORITY=from-process');
    EnvironmentLoader::loadFirstExisting([$path]);
    $tests->assertSame('from-file', getenv('COFFEE_TEST_FILE_VALUE'));
    $tests->assertSame('from-process', getenv('COFFEE_TEST_PRIORITY'));
    putenv('COFFEE_TEST_FILE_VALUE');
    putenv('COFFEE_TEST_PRIORITY');
    unlink($path);
});

$tests->test('valid STK Push initiation', function () use ($tests): void {
    $transport = new FakeTransport();
    $transport->queue[] = successResponse();
    [$status, $body] = controller($transport)->initiate(validInput(), '192.0.2.1');
    $tests->assertSame(202, $status);
    $tests->assertSame('txn_public123', $body['transaction_id']);
    $tests->assertSame('254716933897', $transport->requests[0]['body']['phone']);
});

foreach (['0716933897', '254716933897', '+254716933897', '0716 933 897'] as $phone) {
    $tests->test("Kenyan phone normalisation: {$phone}", function () use ($tests, $phone): void {
        $transport = new FakeTransport();
        $transport->queue[] = successResponse();
        controller($transport)->initiate(validInput($phone), '192.0.2.2');
        $tests->assertSame('254716933897', $transport->requests[0]['body']['phone']);
    });
}

$tests->test('011 Kenyan phone normalisation', function () use ($tests): void {
    $transport = new FakeTransport();
    $transport->queue[] = successResponse();
    controller($transport)->initiate(validInput('0113920136'), '192.0.2.3');
    $tests->assertSame('254113920136', $transport->requests[0]['body']['phone']);
});

$validationCases = [
    'invalid phone rejection' => [validInput('12345'), 'phone'],
    'invalid amount rejection' => [['phone' => '0716933897', 'amount' => 10.5, 'request_id' => '550e8400-e29b-41d4-a716-446655440000'], 'amount'],
    'amount below minimum' => [validInput('0716933897', 49), 'amount'],
    'amount above maximum' => [validInput('0716933897', 10001), 'amount'],
    'invalid request UUID' => [['phone' => '0716933897', 'amount' => 250, 'request_id' => 'not-a-uuid'], 'request_id'],
];

foreach ($validationCases as $name => [$input, $field]) {
    $tests->test($name, function () use ($tests, $input, $field): void {
        $transport = new FakeTransport();
        [$status, $body] = controller($transport)->initiate($input, '192.0.2.4');
        $tests->assertSame(422, $status);
        $tests->assertTrue(isset($body['errors'][$field]));
        $tests->assertSame([], $transport->requests);
    });
}

$tests->test('idempotency header generation', function () use ($tests): void {
    $transport = new FakeTransport();
    $transport->queue[] = successResponse();
    controller($transport)->initiate(validInput(), '192.0.2.5');
    $tests->assertSame('coffee-550e8400-e29b-41d4-a716-446655440000', $transport->requests[0]['headers']['Idempotency-Key']);
});

$tests->test('same request UUID produces identical Kadi request identity', function () use ($tests): void {
    $transport = new FakeTransport();
    $transport->queue[] = successResponse();
    $transport->queue[] = successResponse();
    $controller = controller($transport);
    $controller->initiate(validInput(), '192.0.2.51');
    $controller->initiate(validInput(), '192.0.2.51');
    $tests->assertSame($transport->requests[0]['headers']['Idempotency-Key'], $transport->requests[1]['headers']['Idempotency-Key']);
    $tests->assertSame($transport->requests[0]['body']['reference'], $transport->requests[1]['body']['reference']);
});

$tests->test('sanitised successful Kadi response', function () use ($tests): void {
    $transport = new FakeTransport();
    $transport->queue[] = successResponse();
    [, $body] = controller($transport)->initiate(validInput(), '192.0.2.6');
    $tests->assertSame(['transaction_id', 'status', 'message'], array_keys($body));
});

$tests->test('Kadi HTTP 422 response', function () use ($tests): void {
    $transport = new FakeTransport();
    $transport->queue[] = new TransportResponse(422, ['errors' => ['phone' => ['Phone was rejected.'], 'unsafe-field' => ['hidden']]]);
    [$status, $body] = controller($transport)->initiate(validInput(), '192.0.2.7');
    $tests->assertSame(422, $status);
    $tests->assertSame(['phone' => ['The payment platform could not accept this phone number.']], $body['errors']);
    $tests->assertTrue(!str_contains(json_encode($body), 'Phone was rejected.'));
});

$tests->test('Kadi connection failure', function () use ($tests): void {
    $transport = new FakeTransport();
    $transport->queue[] = new ConnectionException('Sensitive internal connection detail.');
    [$status, $body] = controller($transport)->initiate(validInput(), '192.0.2.8');
    $tests->assertSame(502, $status);
    $tests->assertTrue(!str_contains(json_encode($body), 'Sensitive'));
});

$tests->test('transaction-status lookup', function () use ($tests): void {
    $transport = new FakeTransport();
    $transport->queue[] = new TransportResponse(200, [
        'transaction_id' => 'txn_public123',
        'status' => 'success',
        'amount' => 250,
        'reference' => 'COFFEE-A1B2C3',
        'merchant_request_id' => 'hidden',
    ]);
    [$status, $body] = controller($transport)->status('txn_public123', '192.0.2.9');
    $tests->assertSame(200, $status);
    $tests->assertSame('Thank you for supporting my work.', $body['message']);
    $tests->assertSame(['transaction_id', 'status', 'message'], array_keys($body));
});

$tests->test('unsafe transaction identifier is rejected before Kadi lookup', function () use ($tests): void {
    $transport = new FakeTransport();
    [$status] = controller($transport)->status('../private', '192.0.2.91');
    $tests->assertSame(422, $status);
    $tests->assertSame([], $transport->requests);
});

$tests->test('rate limiting', function () use ($tests): void {
    $transport = new FakeTransport();
    [$status] = controller($transport, new FakeRateLimiter(false))->initiate(validInput(), '192.0.2.10');
    $tests->assertSame(429, $status);
});

$tests->test('file rate limiter enforces its attempt window', function () use ($tests): void {
    $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'coffee-rate-test-' . bin2hex(random_bytes(6));
    $limiter = new FileRateLimiter($directory);
    $tests->assertTrue($limiter->allow('test-key', 1, 60));
    $tests->assertTrue(!$limiter->allow('test-key', 1, 60));
    $file = $directory . DIRECTORY_SEPARATOR . hash('sha256', 'test-key') . '.json';
    if (is_file($file)) {
        unlink($file);
    }
    rmdir($directory);
});

$tests->test('secret key absent from responses', function () use ($tests): void {
    $transport = new FakeTransport();
    $transport->queue[] = successResponse();
    [, $body] = controller($transport)->initiate(validInput(), '192.0.2.11');
    $tests->assertTrue(!str_contains(json_encode($body), 'unit-test-key'));
});

$tests->finish();
