<?php
declare(strict_types=1);

namespace App\Test\TestCase\Prompt;

use App\Prompt\Canonicalizer\CanonicalKeySerializer;
use App\Prompt\Canonicalizer\CanonicalRequest;
use App\Prompt\StyleExtractor\StyleExtractor;
use CakeInstructor\Testing\FakeStructuredExtractor;
use PHPUnit\Framework\TestCase;

final class StyleExtractorTest extends TestCase
{
    public function testExtractMapsStructuredOutputWithoutPhaseLevelCache(): void
    {
        $fake = new FakeStructuredExtractor([
            [
                'genre' => 'Lyrical Hip-Hop',
                'mood' => ['Dark', 'Intense'],
                'energy' => 'High',
                'tempoBpm' => 94,
                'instruments' => ['Piano', 'Drums', 'Bass'],
                'rhythm_traits' => ['Boom Bap', 'Hard-Hitting'],
                'production_traits' => ['Punchy', 'Cinematic'],
            ],
            [
                'genre' => 'Lyrical Hip-Hop',
                'mood' => ['Dark', 'Intense'],
                'energy' => 'High',
                'tempoBpm' => 94,
                'instruments' => ['Piano', 'Drums', 'Bass'],
                'rhythm_traits' => ['Boom Bap', 'Hard-Hitting'],
                'production_traits' => ['Punchy', 'Cinematic'],
            ],
        ]);

        $extractor = new StyleExtractor(extractor: $fake);
        $serializer = new CanonicalKeySerializer();
        $canonical = new CanonicalRequest(
            kind: 'artist_style_prompt',
            artists: ['joyner lucas'],
            target: 'beat',
            modifiers: [],
            source: 'llm',
        );
        $canonicalKey = $serializer->serialize($canonical);

        $first = $extractor->extract($canonical, $canonicalKey);
        $second = $extractor->extract($canonical, $canonicalKey);

        self::assertSame('lyrical hip-hop', $first->genre);
        self::assertSame(['dark', 'intense'], $first->mood);
        self::assertSame('high', $first->energy);
        self::assertSame(94, $first->tempoBpm);
        self::assertSame(['piano', 'drums', 'bass'], $first->instruments);
        self::assertSame(['boom bap', 'hard-hitting'], $first->rhythmTraits);
        self::assertSame(['punchy', 'cinematic'], $first->productionTraits);
        self::assertEquals($first, $second);
        self::assertCount(2, $fake->calls());
    }
}
