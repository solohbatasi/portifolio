<?php

declare(strict_types=1);

namespace Portfolio\Support;

final class CurlTransport implements Transport
{
    public function request(string $method, string $url, array $headers, ?array $body = null): TransportResponse
    {
        $handle = curl_init($url);
        if ($handle === false) {
            throw new ConnectionException('Unable to initialise the payment connection.');
        }

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = "{$name}: {$value}";
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ];

        if ($body !== null) {
            $encodedBody = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $options[CURLOPT_POSTFIELDS] = $encodedBody;
        }

        curl_setopt_array($handle, $options);
        $rawResponse = curl_exec($handle);
        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $errorNumber = curl_errno($handle);
        curl_close($handle);

        if ($rawResponse === false || $errorNumber !== 0) {
            throw new ConnectionException('The payment provider could not be reached.');
        }

        $decodedBody = json_decode($rawResponse, true);
        if (!is_array($decodedBody)) {
            throw new UpstreamException('The payment provider returned an unexpected response.');
        }

        return new TransportResponse($statusCode, $decodedBody);
    }
}
