<?php
declare(strict_types=1);

namespace CakeInstructor\Support;

use Cognesy\Http\Data\HttpResponse;

final class ProviderResponseInspector
{
    /**
     * Decode a non-streamed provider response body as JSON when possible.
     *
     * @return array<string,mixed>|null
     */
    public function decodeResponseBody(HttpResponse $response): ?array
    {
        if ($response->isStreamed()) {
            return null;
        }

        $decoded = json_decode($response->body(), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Read request identifiers from provider response headers or payloads.
     */
    public function requestIdFromResponse(HttpResponse $response): string
    {
        $headers = $response->headers();
        foreach ($headers as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $normalized = strtolower($key);
            if (!in_array($normalized, ['request-id', 'x-request-id', 'anthropic-request-id'], true)) {
                continue;
            }

            if (is_string($value)) {
                return trim($value);
            }

            if (is_array($value) && is_string($value[0] ?? null)) {
                return trim((string)$value[0]);
            }
        }

        $payload = $this->decodeResponseBody($response);

        return $payload !== null ? $this->requestIdFromPayload($payload) : '';
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function payloadMessage(array $payload): string
    {
        $error = $payload['error'] ?? null;
        if (is_array($error) && is_string($error['message'] ?? null)) {
            return trim((string)$error['message']);
        }

        if (is_string($error)) {
            return trim($error);
        }

        $message = $payload['message'] ?? null;

        return is_string($message) ? trim($message) : '';
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function requestIdFromPayload(array $payload): string
    {
        $requestId = $payload['request_id'] ?? $payload['requestId'] ?? null;

        return is_string($requestId) ? trim($requestId) : '';
    }
}
