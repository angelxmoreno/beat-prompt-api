<?php
declare(strict_types=1);

$errorReporting = (string)(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

$command = [
    PHP_BINARY,
    '-d',
    "error_reporting={$errorReporting}",
    __DIR__ . '/../vendor/bin/phpmd',
    'src,tests,plugins',
    'ansi',
    'phpmd.xml',
];

$process = proc_open($command, [
    0 => ['file', 'php://stdin', 'r'],
    1 => ['file', 'php://stdout', 'w'],
    2 => ['file', 'php://stderr', 'w'],
], $pipes);

if (!is_resource($process)) {
    exit(1);
}

$exitCode = proc_close($process);

exit($exitCode);
