<?php
declare(strict_types=1);

namespace CakeInstructor\Support;

use CakeInstructor\Exception\InstructorIntegrationException;
use CakeInstructor\Exception\ProviderRequestException;
use CakeInstructor\Exception\ResponseSchemaException;
use Cognesy\Instructor\Deserialization\Exceptions\DeserializationException;
use Cognesy\Instructor\Exceptions\StructuredOutputRecoveryException;
use Cognesy\Instructor\Validation\Exceptions\ValidationException;
use Throwable;

final class InstructorExceptionMapper
{
    /**
     * @param \CakeInstructor\Support\ProviderFailureInspector|null $providerInspector Provider failure message helper.
     */
    public function __construct(
        private readonly ?ProviderFailureInspector $providerInspector = null,
    ) {
    }

    /**
     * Map provider and schema errors into stable plugin exceptions.
     */
    public function map(
        Throwable $exception,
        string $context = 'Instructor request failed',
    ): InstructorIntegrationException {
        if ($exception instanceof InstructorIntegrationException) {
            return $exception;
        }

        if ($this->causedBySchemaFailure($exception)) {
            return new ResponseSchemaException(
                message: $this->formatMessage($context, $exception),
                code: (int)$exception->getCode(),
                previous: $exception,
            );
        }

        if ($this->providerFailures()->causedByProviderFailure($exception)) {
            return new ProviderRequestException(
                message: $this->formatMessage($context, $exception),
                code: (int)$exception->getCode(),
                previous: $exception,
            );
        }

        return new InstructorIntegrationException(
            message: $this->formatMessage($context, $exception),
            code: (int)$exception->getCode(),
            previous: $exception,
        );
    }

    /**
     * Prefix mapped exception messages with operation context.
     */
    private function formatMessage(string $context, Throwable $exception): string
    {
        $message = trim($this->providerFailures()->bestDiagnosticMessage($exception));
        if ($message !== '') {
            $message = $context . ': ' . $message;
        }
        if ($message === '') {
            $message = $context;
        }

        $hint = $this->diagnosticHint($message, $context);
        if ($hint === '') {
            return $message;
        }

        return $message . ' | Hint: ' . $hint;
    }

    /**
     * Determine whether an exception represents schema/validation failure.
     */
    private function isSchemaFailure(Throwable $exception): bool
    {
        return $exception instanceof ValidationException
            || $exception instanceof DeserializationException
            || $exception instanceof StructuredOutputRecoveryException;
    }

    /**
     * Check whether any exception in the causal chain is a schema failure.
     */
    private function causedBySchemaFailure(Throwable $exception): bool
    {
        $current = $exception;
        while ($current !== null) {
            if ($this->isSchemaFailure($current)) {
                return true;
            }

            $current = $current->getPrevious();
        }

        return false;
    }

    /**
     * Provide targeted remediation hints for generic provider failures.
     */
    private function diagnosticHint(string $message, string $context): string
    {
        $lower = strtolower($message);
        if (
            str_contains($lower, 'credit balance is too low')
            || str_contains($lower, 'purchase credits')
            || str_contains($lower, 'insufficient quota')
        ) {
            return 'Provider credentials look valid, but the account/project needs billing or credits '
                . 'before requests will succeed.';
        }

        if (!str_contains($lower, 'provider error')) {
            return '';
        }

        $conn = $this->connectionFromContext($context);
        if (str_starts_with($conn, 'anthropic:')) {
            return 'Check ANTHROPIC_API_KEY, ANTHROPIC_API_URL, and ANTHROPIC_MODEL. '
                . 'Default URL should be https://api.anthropic.com/v1.';
        }

        if (str_starts_with($conn, 'gemini:')) {
            return 'Check GEMINI_API_KEY, GEMINI_API_URL, and GEMINI_MODEL. '
                . 'Default URL should be https://generativelanguage.googleapis.com/v1beta.';
        }

        if (str_starts_with($conn, 'openai:')) {
            return 'Check OPENAI_API_KEY, OPENAI_API_URL, and OPENAI_MODEL. '
                . 'Default URL should be https://api.openai.com/v1.';
        }

        if (str_starts_with($conn, 'ollama:')) {
            return 'Check OLLAMA_API_URL, configured model name, and model availability on your Ollama host.';
        }

        return 'Check connection apiUrl/apiKey/model settings and provider credentials for the selected connection.';
    }

    /**
     * Extract configured connection name from mapping context.
     */
    private function connectionFromContext(string $context): string
    {
        if (preg_match('/connection:\s*([^)]+)/i', $context, $matches) !== 1) {
            return '';
        }

        $conn = trim((string)($matches[1] ?? ''));

        return strtolower($conn);
    }

    /**
     * Lazily resolve the provider failure helper.
     */
    private function providerFailures(): ProviderFailureInspector
    {
        return $this->providerInspector ?? new ProviderFailureInspector();
    }
}
