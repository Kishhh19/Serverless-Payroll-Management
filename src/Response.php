<?php

declare(strict_types=1);

namespace PayrollApi;

class Response
{
    /**
     * Send a raw JSON response and terminate script execution.
     *
     * @param mixed $payload
     * @param int $statusCode
     * @param array<string, string> $headers
     * @return void
     */
    public static function json(mixed $payload, int $statusCode = 200, array $headers = []): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send a successful JSON response.
     *
     * @param mixed $data
     * @param int $statusCode
     * @param string|null $message
     * @param array<string, string> $headers
     * @return void
     */
    public static function success(mixed $data, int $statusCode = 200, ?string $message = null, array $headers = []): void
    {
        $payload = [
            'success' => true,
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        $payload['data'] = $data;

        self::json($payload, $statusCode, $headers);
    }

    /**
     * Send an error JSON response.
     *
     * @param string $message
     * @param int $statusCode
     * @param array<string, mixed>|null $details
     * @return void
     */
    public static function error(string $message, int $statusCode = 400, ?array $details = null): void
    {
        $payload = [
            'success' => false,
            'error' => $message,
            'status_code' => $statusCode,
        ];

        if ($details !== null && !empty($details)) {
            $payload['details'] = $details;
        }

        self::json($payload, $statusCode);
    }
}
