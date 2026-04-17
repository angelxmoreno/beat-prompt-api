<?php
declare(strict_types=1);

namespace App\Test\TestCase\Prompt;

use App\Prompt\CanonicalKeySerializer;
use App\Prompt\CanonicalRequest;
use PHPUnit\Framework\TestCase;

final class CanonicalKeySerializerTest extends TestCase
{
    public function testSerializeProducesExpectedFormat(): void
    {
        $serializer = new CanonicalKeySerializer();

        $request = new CanonicalRequest(
            kind: 'artist_style_prompt',
            artists: ['joyner lucas'],
            target: 'beat',
            modifiers: [],
        );

        self::assertSame(
            'kind:artist_style_prompt|artists:joyner lucas|target:beat|modifiers:',
            $serializer->serialize($request),
        );
    }
}
