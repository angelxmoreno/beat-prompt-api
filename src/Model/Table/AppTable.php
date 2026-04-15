<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Base table for application tables.
 *
 * Shared ORM conventions and reusable table helpers should live here so
 * concrete tables inherit a single application-level model surface.
 */
class AppTable extends Table
{
    use LocatorAwareTrait;

    /**
     * Adds standardized enum validation to a field.
     *
     * @param \Cake\Validation\Validator $validator The validator to modify.
     * @param string $field The field name.
     * @param class-string $enumClass The backed enum class.
     * @return \Cake\Validation\Validator
     */
    protected function addEnumValidation(Validator $validator, string $field, string $enumClass): Validator
    {
        return $validator->inList($field, $enumClass::values());
    }
}
