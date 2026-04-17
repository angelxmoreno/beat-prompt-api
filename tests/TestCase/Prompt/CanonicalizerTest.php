<?php
declare(strict_types=1);

namespace App\Test\TestCase\Prompt;

use App\Prompt\Canonicalizer;
use App\Prompt\CanonicalKeySerializer;
use App\Prompt\CanonicalRequest;
use PHPUnit\Framework\TestCase;

final class CanonicalizerTest extends TestCase
{
    public function testTypeBeatPhraseResolvesArtistIntent(): void
    {
        $canonicalizer = new Canonicalizer();

        $request = $canonicalizer->canonicalize('Joyner Lucas type beat');

        self::assertSame('artist_style_prompt', $request->kind);
        self::assertSame(['joyner lucas'], $request->artists);
        self::assertSame('beat', $request->target);
        self::assertSame([], $request->modifiers);
    }

    public function testBeatsLikeAndTypeBeatMapToSameCanonicalKey(): void
    {
        $canonicalizer = new Canonicalizer();

        $keyFromTypeBeat = $canonicalizer->canonicalKey('Joyner Lucas type beat');
        $keyFromBeatsLike = $canonicalizer->canonicalKey('beats like joyner lucas');

        self::assertSame($keyFromTypeBeat, $keyFromBeatsLike);
        self::assertSame('kind:artist_style_prompt|artists:joyner lucas|target:beat|modifiers:', $keyFromTypeBeat);
    }

    public function testGeneralPromptNormalizesAndCapturesModifiers(): void
    {
        $canonicalizer = new Canonicalizer();

        $request = $canonicalizer->canonicalize('   Dark TRAP   instrumental   ');

        self::assertSame('general_prompt', $request->kind);
        self::assertSame([], $request->artists);
        self::assertSame('beat', $request->target);
        self::assertSame(['dark', 'trap'], $request->modifiers);
    }

    public function testKeySerializerSortsCollectionsDeterministically(): void
    {
        $serializer = new CanonicalKeySerializer();

        $request = new CanonicalRequest(
            kind: 'artist_style_prompt',
            artists: ['z artist', 'a artist'],
            target: 'beat',
            modifiers: ['moody', 'aggressive'],
        );

        $key = $serializer->serialize($request);

        self::assertSame(
            'kind:artist_style_prompt|artists:a artist,z artist|target:beat|modifiers:aggressive,moody',
            $key,
        );
    }
}
