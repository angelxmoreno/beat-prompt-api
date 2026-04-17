<?php
declare(strict_types=1);

namespace App\Prompt;

/**
 * Deterministically serializes canonical requests to stable cache keys.
 */
final class CanonicalKeySerializer
{
    /**
     * Serialize canonical request fields into a deterministic cache key.
     */
    public function serialize(CanonicalRequest $request): string
    {
        $artists = $request->artists;
        sort($artists, SORT_STRING);

        $modifiers = $request->modifiers;
        sort($modifiers, SORT_STRING);

        return sprintf(
            'kind:%s|artists:%s|target:%s|modifiers:%s',
            $request->kind,
            implode(',', $artists),
            $request->target,
            implode(',', $modifiers),
        );
    }
}
