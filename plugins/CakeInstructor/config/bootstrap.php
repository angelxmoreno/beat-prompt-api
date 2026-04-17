<?php
declare(strict_types=1);

use Cake\Core\Configure;

$defaults = require __DIR__ . '/cake_instructor.php';

$current = (array)Configure::read('CakeInstructor', []);
Configure::write('CakeInstructor', array_replace_recursive($defaults, $current));
