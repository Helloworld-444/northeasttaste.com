<?php
require_once dirname(dirname(__DIR__)) . '/php/config/config.php';
require_once __DIR__ . '/../helpers.php';
require_once 'header.php';
?>

<div class="container error-page">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <h1 class="display-1">404</h1>
            <h2>Page Not Found</h2>
            <p>The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
            <a href="<?= url('index.php') ?>" class="btn btn-primary">Go to Homepage</a>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?> 