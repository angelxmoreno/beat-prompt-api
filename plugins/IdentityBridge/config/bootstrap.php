<?php
declare(strict_types=1);

use Cake\Core\Configure;

$defaults = require __DIR__ . '/app_default.php';
$current = (array)Configure::read('IdentityBridge', []);

Configure::write('IdentityBridge', array_replace($defaults['IdentityBridge'], $current));
