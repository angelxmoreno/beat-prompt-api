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

    public function testKeepsInstrumentalTargetWhenExplicitlyReturned(): void
    {
        $fake = new FakeStructuredExtractor([[
            'kind' => 'artist_style_prompt',
            'artists' => ['joyner lucas'],
            'target' => 'instrumental',
            'modifiers' => [],
        ]]);

        $canon = new Canonicalizer(extractor: $fake);
        $req = $canon->canonicalize('give me an instrumental in Joyner Lucas style');

        self::assertSame('instrumental', $req->target);
    }

    public function testNormalizesArtistPunctuationToCanonicalForm(): void
    {
        $fake = new FakeStructuredExtractor([[
            'kind' => 'artist_style_prompt',
            'artists' => ['Joyner Lucas', 'J. Cole'],
            'target' => 'beat',
            'modifiers' => [],
        ]]);

        $canon = new Canonicalizer(extractor: $fake);
        $req = $canon->canonicalize('Joyner Lucas x J. Cole type beat');

        self::assertSame(['joyner lucas', 'j cole'], $req->artists);
    }

    public function testKeepsLlmTargetWithoutAllowlistFiltering(): void
    {
        $fake = new FakeStructuredExtractor([[
            'kind' => 'general_prompt',
            'artists' => [],
            'target' => 'loop pack',
            'modifiers' => ['dark'],
        ]]);

        $canon = new Canonicalizer(extractor: $fake);
        $req = $canon->canonicalize('make a dark loop pack');

        self::assertSame('loop pack', $req->target);
    }
}
