<?php
require_once dirname(dirname(dirname(__DIR__))) . '/php/config/config.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/dish_controller.php';

$controller = new AdminDishController();
$controller->handleRequest(); 