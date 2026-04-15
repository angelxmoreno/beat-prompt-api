<?php
declare(strict_types=1);

namespace IdentityBridge\Provider;

use Appwrite\Client;
use Appwrite\Models\User;
use Appwrite\Services\Account;
use IdentityBridge\ValueObject\RemoteIdentity;

class AppwriteProvider implements ProviderInterface
{
    public const PROVIDER_NAME = 'appwrite';

    protected string $endpoint;

    protected string $projectId;

    protected bool $isDev;

    /**
     * @param array{endpoint?: string, projectId?: string, isDev?: bool} $config Provider config.
     */
    public function __construct(array $config = [])
    {
        $this->endpoint = (string)($config['endpoint'] ?? '');
        $this->projectId = (string)($config['projectId'] ?? '');
        $this->isDev = (bool)($config['isDev'] ?? false);
    }

    /**
     * @throws \Appwrite\AppwriteException
     */
    protected function buildClient(string $jwt): Client
    {
        $client = new Client();

        $client
            ->setEndpoint($this->endpoint)
            ->setProject($this->projectId);

        if ($this->isDev) {
            $client->setSelfSigned();
        }

        $client->setJWT($jwt);

        return $client;
    }

    /**
     * Fetches the current Appwrite account user for the given bearer token.
     *
     * @param string $jwt The provider-issued bearer token.
     * @return \Appwrite\Models\User
     * @throws \Appwrite\AppwriteException
     */
    protected function fetchUser(string $jwt): User
    {
        $client = $this->buildClient($jwt);
        $account = new Account($client);

        return $account->get();
    }

    /**
     * Verifies a bearer token and returns normalized remote identity data.
     *
     * @param string $jwt The provider-issued bearer token.
     * @return \IdentityBridge\ValueObject\RemoteIdentity
     * @throws \Appwrite\AppwriteException
     */
    public function verify(string $jwt): RemoteIdentity
    {
        $user = $this->fetchUser($jwt);

        return new RemoteIdentity(
            provider: self::PROVIDER_NAME,
            providerUserId: (string)$user->id,
            email: $user->email,
            emailVerified: $user->emailVerification,
            displayName: $user->name,
            claims: [
                'labels' => $user->labels,
            ],
        );
    }
}
