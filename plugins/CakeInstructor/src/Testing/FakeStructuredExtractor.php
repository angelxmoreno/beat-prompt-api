<?php
declare(strict_types=1);

namespace CakeInstructor\Testing;

use CakeInstructor\Contracts\StructuredExtractorInterface;
use CakeInstructor\Data\ExtractionRequest;
use CakeInstructor\Exception\InstructorIntegrationException;
use Throwable;

final class FakeStructuredExtractor implements StructuredExtractorInterface
{
    /**
     * @var array<int, mixed>
     */
    private array $responses;

    /**
     * @var array<int, \CakeInstructor\Data\ExtractionRequest>
     */
    private array $calls = [];

    /**
     * @param array<int, mixed> $responses
     */
    public function __construct(array $responses = [])
    {
        $this->responses = array_values($responses);
    }

    /**
     * Return the next queued response for the given extraction request.
     */
    public function extract(ExtractionRequest $request): mixed
    {
        $this->calls[] = $request;

        if ($this->responses === []) {
            throw new InstructorIntegrationException('FakeStructuredExtractor has no queued responses.');
        }

        $next = array_shift($this->responses);

        if ($next instanceof Throwable) {
            throw $next;
        }

        if (is_callable($next)) {
            return $next($request, count($this->calls));
        }

        return $next;
    }

    /**
     * Queue an additional fake response.
     */
    public function push(mixed $response): self
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * @return array<int, \CakeInstructor\Data\ExtractionRequest>
     */
    public function calls(): array
    {
        return $this->calls;
    }

    /**
     * Return the number of queued fake responses not yet consumed.
     */
    public function pendingCount(): int
    {
        return count($this->responses);
    }
}
