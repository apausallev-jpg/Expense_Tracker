<?php
// ============================================
// Categories API  (api/categories.php)
// ============================================

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$db   = getDB();
$stmt = $db->query('SELECT * FROM categories ORDER BY name');
jsonResponse($stmt->fetchAll());
