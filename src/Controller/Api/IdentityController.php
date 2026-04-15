<?php
declare(strict_types=1);

namespace App\Controller\Api;

/**
 * Thin Crud-backed identity controller.
 */
class IdentityController extends AppController
{
    /**
     * @return void
     * @throws \Exception
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->Crud->mapAction('me', [
            'className' => 'IdentityMe',
            'api' => [
                'methods' => ['get'],
            ],
        ]);
    }
}
