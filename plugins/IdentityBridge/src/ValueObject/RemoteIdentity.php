<?php
declare(strict_types=1);

namespace IdentityBridge\ValueObject;

use JsonSerializable;

/**
 * Normalized remote identity returned by an auth provider adapter.
 *
 * @property-read array<string, mixed> $claims
 */
readonly class RemoteIdentity implements JsonSerializable
{
    /**
     * @param array<string, mixed> $claims Provider-specific claims payload.
     */
    public function __construct(
        public string $provider,
        public string $providerUserId,
        public ?string $email = null,
        public bool $emailVerified = false,
        public ?string $displayName = null,
        public ?string $avatarUrl = null,
        public array $claims = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'provider' => $this->provider,
            'providerUserId' => $this->providerUserId,
            'email' => $this->email,
            'emailVerified' => $this->emailVerified,
            'displayName' => $this->displayName,
            'avatarUrl' => $this->avatarUrl,
        ];
    }
}
