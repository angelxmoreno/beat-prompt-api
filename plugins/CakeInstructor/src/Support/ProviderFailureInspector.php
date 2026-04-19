<?php
declare(strict_types=1);

namespace CakeInstructor\Support;

use Cognesy\Http\Data\HttpResponse;
use Cognesy\Http\Exceptions\ConnectionException;
use Cognesy\Http\Exceptions\HttpClientErrorException;
use Cognesy\Http\Exceptions\HttpRequestException;
use Cognesy\Http\Exceptions\NetworkException;
use Cognesy\Http\Exceptions\ServerErrorException;
use Cognesy\Http\Exceptions\TimeoutException;
use Cognesy\Polyglot\Inference\Exceptions\ProviderException;
use Throwable;

final class ProviderFailureInspector
{
    /**
     * @param \CakeInstructor\Support\ProviderResponseInspector|null $responseInspector Provider response helper.
     */
    public function __construct(
        private readonly ?ProviderResponseInspector $responseInspector = null,
    ) {
    }

    /**
     * Determine whether a throwable is a direct provider/network failure.
     */
    public function isProviderFailure(Throwable $exception): bool
    {
        return $exception instanceof TimeoutException
            || $exception instanceof ConnectionException
            || $exception instanceof NetworkException
            || $exception instanceof HttpClientErrorException
            || $exception instanceof ServerErrorException
            || $exception instanceof ProviderException;
    }

    /**
     * Walk the causal chain looking for the most actionable provider message.
     */
    public function bestDiagnosticMessage(Throwable $exception): string
    {
        $fallback = trim($exception->getMessage());

        foreach ($this->exceptionChain($exception) as $current) {
            if ($current instanceof ProviderException) {
                return $this->providerExceptionMessage($current);
            }

            if ($current instanceof HttpRequestException) {
                return $this->httpRequestMessage($current);
            }

            $message = trim($current->getMessage());
            if ($message !== '' && !$this->isGenericProviderMessage($message)) {
                $fallback = $message;
            }
        }

        return $fallback;
    }

    /**
     * Check whether any exception in the causal chain is a provider/network failure.
     */
    public function causedByProviderFailure(Throwable $exception): bool
    {
        foreach ($this->exceptionChain($exception) as $current) {
            if ($this->isProviderFailure($current)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the best message from a typed provider exception payload.
     */
    private function providerExceptionMessage(ProviderException $exception): string
    {
        $message = trim($exception->getMessage());
        $payload = is_array($exception->payload) ? $exception->payload : null;
        $message = $this->preferPayloadMessage($message, $payload);
        $requestId = $payload !== null ? $this->responses()->requestIdFromPayload($payload) : '';

        return $this->appendDiagnostics($message, $exception->statusCode, $requestId);
    }

    /**
     * Extract provider details from an HTTP exception and response payload.
     */
    private function httpRequestMessage(HttpRequestException $exception): string
    {
        $message = trim($exception->getMessage());
        $response = $exception->getResponse();
        $payload = $response instanceof HttpResponse ? $this->responses()->decodeResponseBody($response) : null;
        $message = $this->preferPayloadMessage($message, $payload);
        $requestId = $response instanceof HttpResponse ? $this->responses()->requestIdFromResponse($response) : '';

        return $this->appendDiagnostics($message, $exception->getStatusCode(), $requestId);
    }

    /**
     * @param array<string,mixed>|null $payload
     */
    private function preferPayloadMessage(string $fallback, ?array $payload): string
    {
        if ($payload === null) {
            return $fallback;
        }

        $message = $this->responses()->payloadMessage($payload);
        if ($message === '' || $this->isGenericProviderMessage($message)) {
            return $fallback;
        }

        return $message;
    }

    /**
     * Append structured HTTP diagnostics to a provider message.
     */
    private function appendDiagnostics(string $message, ?int $statusCode, string $requestId): string
    {
        $details = [];
        if ($statusCode !== null) {
            $details[] = sprintf('status=%d', $statusCode);
        }
        if ($requestId !== '') {
            $details[] = sprintf('request_id=%s', $requestId);
        }

        if ($details === []) {
            return $message;
        }

        return sprintf('%s [%s]', $message, implode(', ', $details));
    }

    /**
     * Detect generic provider messages that should be replaced with richer upstream details.
     */
    private function isGenericProviderMessage(string $message): bool
    {
        $lower = strtolower(trim($message));

        return $lower === ''
            || $lower === 'provider error'
            || preg_match('/^http\s+\d+\s+(client|server)\s+error$/', $lower) === 1;
    }

    /**
     * @return iterable<int,\Throwable>
     */
    private function exceptionChain(Throwable $exception): iterable
    {
        $current = $exception;
        while ($current !== null) {
            yield $current;
            $current = $current->getPrevious();
        }
    }

    /**
     * Lazily resolve the provider response helper.
     */
    private function responses(): ProviderResponseInspector
    {
        return $this->responseInspector ?? new ProviderResponseInspector();
    }
}
