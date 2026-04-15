#!/usr/bin/env php
<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/bootstrap.php';

use Appwrite\Client;
use Appwrite\Services\Account;
use Appwrite\Services\Users;
use Cake\Http\Client as HttpClient;
use function Cake\Core\env;

const DEFAULT_URL = 'http://localhost:8765/api/identity/me';

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

/**
 * @param string $message
 * @return never
 */
function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function value(string $name, string $default = ''): string
{
    return (string)env($name, $default);
}

function makeBaseAppwriteClient(): Client
{
    $endpoint = value('APPWRITE_ENDPOINT');
    $projectId = value('APPWRITE_PROJECT_ID');

    if ($endpoint === '') {
        fail('Missing APPWRITE_ENDPOINT in .env');
    }
    if ($projectId === '') {
        fail('Missing APPWRITE_PROJECT_ID in .env');
    }

    $client = new Client();
    $client
        ->setEndpoint($endpoint)
        ->setProject($projectId);

    if (filter_var(value('DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN)) {
        $client->setSelfSigned();
    }

    return $client;
}

function makeAdminAppwriteClient(): Client
{
    $key = value('APPWRITE_KEY');
    if ($key === '') {
        fail('Missing APPWRITE_KEY in .env');
    }

    return makeBaseAppwriteClient()->setKey($key);
}

function hitIdentityEndpoint(string $jwt): void
{
    $url = value('IDENTITY_URL', DEFAULT_URL);
    $http = new HttpClient();
    $response = $http->get($url, [], [
        'headers' => [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ],
    ]);

    fwrite(STDOUT, 'Status: ' . $response->getStatusCode() . PHP_EOL);
    fwrite(STDOUT, (string)$response->getBody() . PHP_EOL);
}

$email = value('DEMO_EMAIL');
$password = value('DEMO_PASSWORD');

if ($email === '') {
    fail('Missing DEMO_EMAIL in .env');
}
if ($password === '') {
    fail('Missing DEMO_PASSWORD in .env');
}

try {
    $account = new Account(makeBaseAppwriteClient());
    $session = $account->createEmailPasswordSession($email, $password);
    $jwt = (new Users(makeAdminAppwriteClient()))
        ->createJWT($session->userId, $session->id)
        ->jwt;

    hitIdentityEndpoint($jwt);
} catch (Throwable $exception) {
    fail($exception->getMessage());
}
