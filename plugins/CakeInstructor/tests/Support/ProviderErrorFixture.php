<?php
declare(strict_types=1);

namespace CakeInstructor\Test\Support;

use Cognesy\Http\Data\HttpRequest;
use Cognesy\Http\Data\HttpResponse;
use Cognesy\Http\Exceptions\HttpClientErrorException;
use RuntimeException;

final class ProviderErrorFixture
{
    public function anthropicInsufficientCredits(): RuntimeException
    {
        $request = new HttpRequest(
            url: 'https://api.anthropic.com/v1/messages',
            method: 'POST',
            headers: [],
            body: [],
            options: [],
        );
        $response = new HttpResponse(
            statusCode: 402,
            body: json_encode([
                'type' => 'error',
                'error' => [
                    'type' => 'invalid_request_error',
                    'message' => 'Your credit balance is too low to access the Anthropic API.',
                ],
                'request_id' => 'req_123',
            ], JSON_THROW_ON_ERROR),
            headers: ['request-id' => 'req_123'],
            isStreamed: false,
        );

        return new RuntimeException('Provider error', 0, new HttpClientErrorException(402, $request, $response));
    }
}
