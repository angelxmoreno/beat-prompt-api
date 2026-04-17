<?php
declare(strict_types=1);

namespace CakeInstructor\Contracts;

use CakeInstructor\Data\ExtractionRequest;

interface StructuredExtractorInterface
{
    /**
     * @template TModel of object
     * @param \CakeInstructor\Data\ExtractionRequest $request
     * @return TModel|mixed|array<string, mixed>
     */
    public function extract(ExtractionRequest $request): mixed;
}
