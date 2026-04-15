<?php
declare(strict_types=1);

namespace App\Action;

use App\Controller\Api\AppController;
use ArrayAccess;
use Cake\Http\Exception\UnauthorizedException;
use Crud\Action\BaseAction;

/**
 * Crud action that exposes the current authenticated API identity.
 */
class IdentityMeAction extends BaseAction
{
    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'enabled' => true,
        'api' => [
            'methods' => ['get'],
        ],
    ];

    /**
     * Returns the current authenticated identity and local user summary.
     *
     * @return void
     */
    protected function _handle(): void
    {
        $controller = $this->_controller();
        assert($controller instanceof AppController);

        /** @var \IdentityBridge\ValueObject\AuthenticatedRequestIdentity|null $identity */
        $identity = $controller->IdentityBridge->getAuthenticatedRequestIdentity();
        if ($identity === null) {
            throw new UnauthorizedException('Authenticated identity is required.');
        }

        $controller->set('data', [
            'remoteIdentity' => $identity->remoteIdentity,
            'user' => $this->serializeUser($identity->user),
        ]);
        $controller->set('success', true);
        $controller->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * @param \ArrayAccess<string, mixed> $user The resolved local user object.
     * @return array<string, mixed>
     */
    protected function serializeUser(ArrayAccess $user): array
    {
        return [
            'id' => $user['id'] ?? null,
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'avatarUrl' => $user['avatar_url'] ?? null,
        ];
    }
}
