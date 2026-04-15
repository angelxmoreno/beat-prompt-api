<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 */
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;

$this->disableAutoLayout();

if (!Configure::read('debug')) {
    throw new NotFoundException(
        'Replace templates/Pages/home.php with your application home page or enable debug mode.',
    );
}
?>
<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CakePHP Project Template</title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css(['normalize.min', 'milligram.min', 'fonts', 'cake']) ?>
</head>
<body>
    <main class="main">
        <div class="container" style="max-width: 780px; padding-top: 4rem;">
            <div class="message success">
                CakePHP Project Template
            </div>
            <h1>Template Ready</h1>
            <p>
                This application has been cleaned up to remove the original Lopusboard
                workspace domain and leave a smaller base for future CakePHP projects.
            </p>
            <p>
                The remaining app keeps the CakePHP skeleton, the local <code>users</code>
                model, and the <code>/api/identity/me</code> endpoint used by the
                IdentityBridge auth integration.
            </p>
            <h2>Suggested next steps</h2>
            <ul>
                <li>Bake the models, controllers, and templates for your new domain.</li>
                <li>Replace the home page and API routes with project-specific ones.</li>
                <li>Remove or adapt IdentityBridge if this project will not use Appwrite.</li>
            </ul>
            <p>
                CakePHP version: <?= h(Configure::version()) ?>
            </p>
        </div>
    </main>
</body>
</html>
