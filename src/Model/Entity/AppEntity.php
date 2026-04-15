<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Base entity for application entities.
 *
 * Keep cross-cutting entity behavior here so domain entities can inherit it
 * instead of reimplementing shared helpers later.
 */
class AppEntity extends Entity
{
}
