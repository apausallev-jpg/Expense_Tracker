<?php
require_once __DIR__ . '/../config.php';
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (empty($_SESSION['user_id'])) jsonResponse(['error' => 'Not authenticated'], 401);
$userId = (int) $_SESSION['user_id'];
$db = getDB();

$total = $db->prepare('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id=:uid');
$total->execute([':uid' => $userId]);
$total = $total->fetchColumn();

$byCategory = $db->prepare(
    'SELECT c.name, c.color, COALESCE(SUM(e.amount),0) AS total, COUNT(e.id) AS count
       FROM categories c LEFT JOIN expenses e ON e.category_id=c.id AND e.user_id=:uid
      GROUP BY c.id, c.name, c.color ORDER BY total DESC'
);
$byCategory->execute([':uid' => $userId]);

$byMonth = $db->prepare(
    "SELECT TO_CHAR(expense_date,'Mon YYYY') AS month,
            TO_CHAR(expense_date,'YYYY-MM')  AS month_key,
            SUM(amount) AS total, COUNT(*) AS count
       FROM expenses WHERE user_id=:uid AND expense_date >= CURRENT_DATE - INTERVAL '6 months'
      GROUP BY month, month_key ORDER BY month_key ASC"
);
$byMonth->execute([':uid' => $userId]);

$thisMonth = $db->prepare(
    "SELECT COALESCE(SUM(amount),0) FROM expenses
      WHERE user_id=:uid AND TO_CHAR(expense_date,'YYYY-MM')=TO_CHAR(CURRENT_DATE,'YYYY-MM')"
);
$thisMonth->execute([':uid' => $userId]);

$count = $db->prepare('SELECT COUNT(*) FROM expenses WHERE user_id=:uid');
$count->execute([':uid' => $userId]);

jsonResponse([
    'total'       => (float) $total,
    'this_month'  => (float) $thisMonth->fetchColumn(),
    'count'       => (int)   $count->fetchColumn(),
    'by_category' => $byCategory->fetchAll(),
    'by_month'    => $byMonth->fetchAll(),
]);
