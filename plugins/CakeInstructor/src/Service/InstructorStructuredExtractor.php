<?php
declare(strict_types=1);

namespace CakeInstructor\Service;

use CakeInstructor\Contracts\StructuredExtractorInterface;
use CakeInstructor\Contracts\StructuredOutputFactoryInterface;
use CakeInstructor\Data\ExtractionRequest;
use CakeInstructor\Support\InstructorExceptionMapper;
use Throwable;

final class InstructorStructuredExtractor implements StructuredExtractorInterface
{
    /**
     * Create an extractor bound to a configured connection.
     */
    public function __construct(
        private readonly StructuredOutputFactoryInterface $factory,
        private readonly InstructorExceptionMapper $exceptionMapper,
        private readonly ?string $connectionName = null,
    ) {
    }

    /**
     * Execute one structured extraction request.
     */
    public function extract(ExtractionRequest $request): mixed
    {
        try {
            $structuredOutput = $this->factory->make($this->connectionName);

            return $structuredOutput->with(
                messages: $request->messages,
                responseModel: $request->responseModel,
                system: $request->system,
                prompt: $request->prompt,
                examples: $request->examples,
                model: $request->model,
                options: $request->options,
            )->get();
        } catch (Throwable $exception) {
            $conn = $this->connectionName ?? 'default';
            $context = sprintf('Structured extraction failed (connection: %s)', $conn);

            throw $this->exceptionMapper->map($exception, $context);
        }
    }
}
