<?php
declare(strict_types=1);

namespace IdentityBridge\Enum;

/**
 * Supported route protection modes for IdentityBridge middleware.
 */
enum AuthenticationMode: string
{
    case PublicByDefault = 'public_by_default';
    case ProtectedByDefault = 'protected_by_default';

    /**
     * Returns all supported config values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
