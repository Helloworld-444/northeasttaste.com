<?php
require_once dirname(dirname(__DIR__)) . '/php/config/config.php';
require_once __DIR__ . '/../helpers.php';

// Redirect to home.php
header('Location: ' . SITE_URL . '/php/views/home.php');
exit(); 