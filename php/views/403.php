<?php
require_once dirname(dirname(__DIR__)) . '/php/config/config.php';
require_once __DIR__ . '/../helpers.php';
require_once 'header.php';
?>

<div class="container error-page">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <h1 class="display-1">403</h1>
            <h2>Access Forbidden</h2>
            <p>You don't have permission to access this resource.</p>
            <a href="<?= url('index.php') ?>" class="btn btn-primary">Go to Homepage</a>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?> 