<?php
declare(strict_types=1);

namespace CakeInstructor;

use Cake\Console\CommandCollection;
use Cake\Console\CommandFactoryInterface;
use Cake\Core\BasePlugin;
use Cake\Core\ContainerInterface;
use Cake\Core\ServiceConfig;
use CakeInstructor\Command\InstructorConnectionProbeCommand;
use CakeInstructor\Command\InstructorConnectionsDoctorCommand;
use CakeInstructor\Command\InstructorConnectionsValidateCommand;
use CakeInstructor\Service\InstructorConnectionProbeService;
use CakeInstructor\Support\ConnectionConfigValidator;

final class CakeInstructorPlugin extends BasePlugin
{
    /**
     * @inheritDoc
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        return parent::console($commands);
    }

    /**
     * @inheritDoc
     */
    public function services(ContainerInterface $container): void
    {
        $container->add(InstructorConnectionProbeService::class, function () {
            $resolved = (new ServiceConfig())->get('CakeInstructor', []);

            return new InstructorConnectionProbeService(is_array($resolved) ? $resolved : []);
        });
        $container->add(ConnectionConfigValidator::class, function () {
            $resolved = (new ServiceConfig())->get('CakeInstructor', []);

            return new ConnectionConfigValidator(is_array($resolved) ? $resolved : []);
        });
        $container->add(InstructorConnectionProbeCommand::class, function () use ($container) {
            return new InstructorConnectionProbeCommand(
                $container->get(InstructorConnectionProbeService::class),
                $container->get(CommandFactoryInterface::class),
            );
        });
        $container->add(InstructorConnectionsValidateCommand::class, function () use ($container) {
            return new InstructorConnectionsValidateCommand(
                $container->get(ConnectionConfigValidator::class),
                $container->get(CommandFactoryInterface::class),
            );
        });
        $container->add(InstructorConnectionsDoctorCommand::class, function () use ($container) {
            return new InstructorConnectionsDoctorCommand(
                $container->get(ConnectionConfigValidator::class),
                $container->get(InstructorConnectionProbeService::class),
                $container->get(CommandFactoryInterface::class),
            );
        });
    }
}
