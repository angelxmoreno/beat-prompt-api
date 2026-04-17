<?php
declare(strict_types=1);

namespace CakeInstructor\Support;

use CakeInstructor\Exception\InstructorIntegrationException;
use CakeInstructor\Exception\ProviderRequestException;
use CakeInstructor\Exception\ResponseSchemaException;
use Cognesy\Http\Exceptions\ConnectionException;
use Cognesy\Http\Exceptions\HttpClientErrorException;
use Cognesy\Http\Exceptions\NetworkException;
use Cognesy\Http\Exceptions\ServerErrorException;
use Cognesy\Http\Exceptions\TimeoutException;
use Cognesy\Instructor\Deserialization\Exceptions\DeserializationException;
use Cognesy\Instructor\Exceptions\StructuredOutputRecoveryException;
use Cognesy\Instructor\Validation\Exceptions\ValidationException;
use Throwable;

final class InstructorExceptionMapper
{
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

        if ($this->isSchemaFailure($exception)) {
            return new ResponseSchemaException(
                message: $this->formatMessage($context, $exception),
                code: (int)$exception->getCode(),
                previous: $exception,
            );
        }

        if ($this->isProviderFailure($exception)) {
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
        $message = trim($exception->getMessage());
        if ($message === '') {
            return $context;
        }

        return $context . ': ' . $message;
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
     * Determine whether an exception represents provider/network failure.
     */
    private function isProviderFailure(Throwable $exception): bool
    {
        return $exception instanceof TimeoutException
            || $exception instanceof ConnectionException
            || $exception instanceof NetworkException
            || $exception instanceof HttpClientErrorException
            || $exception instanceof ServerErrorException;
    }
}
