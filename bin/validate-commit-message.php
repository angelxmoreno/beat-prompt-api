<?php
/**
 * Conventional Commit message validator.
 *
 * Pattern: type(scope): description
 */

$commitMsgFile = $argv[1] ?? null;

if (!$commitMsgFile || !file_exists($commitMsgFile)) {
    echo "Usage: php validate-commit-message.php <file>\n";
    exit(1);
}

$content = file_get_contents($commitMsgFile);
$lines = explode("\n", $content);

// Get first non-comment line
$firstLine = '';
foreach ($lines as $line) {
    if (trim($line) === '' || strpos(trim($line), '#') === 0) {
        continue;
    }
    $firstLine = $line;
    break;
}

if (!$firstLine) {
    echo "Empty commit message.\n";
    exit(1);
}

// Regex for Conventional Commits
// type(scope)!: description
$pattern = '/^(feat|fix|refactor|test|docs|chore|style|perf|ci|build|revert)(\(.+\))?(!)?: .+/';

if (!preg_match($pattern, $firstLine)) {
    echo "\033[31mINVALID COMMIT MESSAGE\033[0m\n";
    echo "Commit messages must follow the Conventional Commits format:\n";
    echo "  <type>(<scope>): <description>\n\n";
    echo "Types: feat, fix, refactor, test, docs, chore, style, perf, ci, build, revert\n";
    echo "Examples:\n";
    echo "  feat: added project policies\n";
    echo "  fix(auth): corrected jwt flow\n\n";
    echo "Your message was: \"$firstLine\"\n";
    exit(1);
}

exit(0);
