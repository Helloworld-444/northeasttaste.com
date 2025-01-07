<?php
require_once dirname(dirname(__DIR__)) . '/php/config/config.php';
require_once __DIR__ . '/../helpers.php';
require_once 'header.php';
?>

<div class="container error-page">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <h1 class="display-1">500</h1>
            <h2>Internal Server Error</h2>
            <p>Something went wrong on our end. Please try again later or contact support if the problem persists.</p>
            <a href="<?= url('index.php') ?>" class="btn btn-primary">Go to Homepage</a>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?> 