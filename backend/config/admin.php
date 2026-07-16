<?php

$emails = array_values(array_unique(array_filter(array_map(
    fn (string $email): string => strtolower(trim($email)),
    explode(',', (string) env('ADMIN_ALLOWED_EMAILS', '')),
))));

return ['allowed_emails' => $emails];
