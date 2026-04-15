<?php
declare(strict_types=1);

namespace App\Auth;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use ArrayAccess;
use Cake\ORM\Locator\LocatorAwareTrait;
use IdentityBridge\Resolver\LocalUserResolverInterface;
use IdentityBridge\ValueObject\RemoteIdentity;

class AppUserResolver implements LocalUserResolverInterface
{
    use LocatorAwareTrait;

    /**
     * Finds, creates, or updates the local user for a verified remote identity.
     *
     * @param \IdentityBridge\ValueObject\RemoteIdentity $identity The normalized identity.
     * @return \ArrayAccess<string, mixed>
     */
    public function resolve(RemoteIdentity $identity): ArrayAccess
    {
        $user = $this->findUser($identity);
        if ($user === null) {
            $user = $this->createUser($identity);
        }

        return $this->updateUser($user, $identity);
    }

    /**
     * Finds the local user associated with the remote identity.
     *
     * @param \IdentityBridge\ValueObject\RemoteIdentity $identity The normalized identity.
     * @return \App\Model\Entity\User|null
     */
    protected function findUser(RemoteIdentity $identity): ?User
    {
        return $this->users()->find()
            ->where([
                'appwrite_id' => $identity->providerUserId,
            ])
            ->first();
    }

    /**
     * Creates a new local user entity from the remote identity.
     *
     * @param \IdentityBridge\ValueObject\RemoteIdentity $identity The normalized identity.
     * @return \App\Model\Entity\User
     */
    protected function createUser(RemoteIdentity $identity): User
    {
        /** @var \App\Model\Entity\User $user */
        $user = $this->users()->newEntity([
            'appwrite_id' => $identity->providerUserId,
            'name' => $identity->displayName ?? $identity->email ?? $identity->providerUserId,
            'email' => $identity->email ?? sprintf('%s@identitybridge.local', $identity->providerUserId),
            'avatar_url' => $identity->avatarUrl,
        ]);

        return $user;
    }

    /**
     * Persists allowed local user field updates from the remote identity.
     *
     * @param \App\Model\Entity\User $user The local user entity.
     * @param \IdentityBridge\ValueObject\RemoteIdentity $identity The normalized identity.
     * @return \App\Model\Entity\User
     */
    protected function updateUser(User $user, RemoteIdentity $identity): User
    {
        $user = $this->users()->patchEntity($user, [
            'name' => $identity->displayName ?? $user->name,
            'email' => $identity->email ?? $user->email,
            'avatar_url' => $identity->avatarUrl,
        ]);

        return $this->users()->saveOrFail($user);
    }

    /**
     * Returns the users table instance.
     *
     * @return \App\Model\Table\UsersTable
     */
    protected function users(): UsersTable
    {
        /** @var \App\Model\Table\UsersTable */
        return $this->getTableLocator()->get(UsersTable::class);
    }
}
