<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController as BaseAppController;
use Cake\View\JsonView;
use Crud\Controller\ControllerTrait;

/**
 * API application controller.
 *
 * Crud is loaded here so HTTP API controllers can use mapped Crud actions
 * without affecting CLI tooling or non-API controllers.
 */
class AppController extends BaseAppController
{
    use ControllerTrait;

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->setRequest($this->getRequest()->withParam('_ext', 'json'));
        $this->viewBuilder()->setClassName(JsonView::class);

        $this->loadComponent('Crud.Crud', [
            'actions' => [
                'Crud.Index',
                'Crud.View',
                'Crud.Add',
                'Crud.Edit',
                'Crud.Delete',
            ],
            'listeners' => [
                'Crud.Api',
                'Crud.ApiPagination',
                'Crud.ApiQueryLog',
            ],
        ]);
    }
}
