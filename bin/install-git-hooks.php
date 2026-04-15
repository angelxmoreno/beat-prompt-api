<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$gitDirectory = $root . DIRECTORY_SEPARATOR . '.git';
$hooksDirectory = $gitDirectory . DIRECTORY_SEPARATOR . 'hooks';
$captainHookBinary = $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'captainhook';

if (!is_dir($gitDirectory)) {
    fwrite(STDOUT, "Skipping CaptainHook installation: .git directory not found.\n");

    return;
}

if (!is_dir($hooksDirectory) || !is_writable($hooksDirectory)) {
    fwrite(STDOUT, "Skipping CaptainHook installation: .git/hooks is missing or not writable.\n");

    return;
}

if (!is_file($captainHookBinary)) {
    fwrite(STDOUT, "Skipping CaptainHook installation: vendor/bin/captainhook not found.\n");

    return;
}

$command = sprintf(
    '%s %s install --force --skip-existing',
    escapeshellarg(PHP_BINARY),
    escapeshellarg($captainHookBinary),
);

passthru($command, $exitCode);

if ($exitCode !== 0) {
    exit($exitCode);
}
