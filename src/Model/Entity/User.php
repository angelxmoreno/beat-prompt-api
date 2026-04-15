<?php
declare(strict_types=1);

namespace App\Model\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property string $appwrite_id
 * @property string $name
 * @property string $email
 * @property string|null $avatar_url
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class User extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'appwrite_id' => true,
        'name' => true,
        'email' => true,
        'avatar_url' => true,
    ];
}
