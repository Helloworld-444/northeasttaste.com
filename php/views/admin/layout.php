<?php
require_once dirname(dirname(dirname(__DIR__))) . '/php/config/config.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';
require_once __DIR__ . '/../../helpers.php';

startSession();
requireAdmin();

$user = new User($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Food Delivery</title>
    <link rel="stylesheet" href="/fooddelivery/css/admin.css">
</head>
<body>
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <div class="content">
        <?php echo $content; ?>
    </div>

    <!-- Scripts -->
    <script src="/fooddelivery/js/admin.js"></script>
</body>
</html> 