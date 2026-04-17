<?php
declare(strict_types=1);

namespace App\Test\TestCase\Prompt;

use App\Prompt\Canonicalizer\Canonicalizer;
use CakeInstructor\Exception\InstructorIntegrationException;
use CakeInstructor\Testing\FakeStructuredExtractor;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CanonicalizerLlmTest extends TestCase
{
    public function testUsesLlmForFlexibleArtistPhrase(): void
    {
        $fake = new FakeStructuredExtractor([[
            'kind' => 'artist_style_prompt',
            'artists' => ['joyner lucas'],
            'target' => 'beat',
            'modifiers' => ['vibes'],
        ]]);

        $canon = new Canonicalizer(extractor: $fake);

        $req = $canon->canonicalize('a beat with vibes like Joyner Lucas');

        self::assertSame('artist_style_prompt', $req->kind);
        self::assertSame(['joyner lucas'], $req->artists);
        self::assertSame('beat', $req->target);
        self::assertSame(['vibes'], $req->modifiers);
        self::assertSame('llm', $req->source);
    }

    public function testThrowsWhenLlmFails(): void
    {
        $fake = new FakeStructuredExtractor([new InstructorIntegrationException('boom')]);

        $canon = new Canonicalizer(extractor: $fake);

        $this->expectException(RuntimeException::class);

        $canon->canonicalize('Joyner Lucas type beat');
    }
}
