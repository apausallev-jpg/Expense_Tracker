<?php
// ============================================
// Expenses API  (api/expenses.php)
// ============================================

require_once __DIR__ . '/../config.php';
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Auth guard
if (empty($_SESSION['user_id'])) jsonResponse(['error' => 'Not authenticated'], 401);

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;
$db     = getDB();

switch ($method) {

    case 'GET':
        if ($id) {
            $stmt = $db->prepare(
                'SELECT e.*, c.name AS category_name, c.color AS category_color
                   FROM expenses e JOIN categories c ON c.id = e.category_id
                  WHERE e.id = :id AND e.user_id = :uid'
            );
            $stmt->execute([':id' => $id, ':uid' => $userId]);
            $row = $stmt->fetch();
            $row ? jsonResponse($row) : jsonResponse(['error' => 'Not found'], 404);
        }

        $where  = ['e.user_id = :uid'];
        $params = [':uid' => $userId];

        if (!empty($_GET['category_id'])) { $where[] = 'e.category_id = :cat'; $params[':cat'] = (int)$_GET['category_id']; }
        if (!empty($_GET['month']))        { $where[] = "TO_CHAR(e.expense_date,'YYYY-MM') = :month"; $params[':month'] = $_GET['month']; }
        if (!empty($_GET['search']))       { $where[] = '(e.title ILIKE :q OR e.description ILIKE :q)'; $params[':q'] = '%'.$_GET['search'].'%'; }

        $sql = 'SELECT e.*, c.name AS category_name, c.color AS category_color
                  FROM expenses e JOIN categories c ON c.id = e.category_id
                 WHERE ' . implode(' AND ', $where) .
               ' ORDER BY e.expense_date DESC, e.created_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        jsonResponse($stmt->fetchAll());

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $err  = validateExpense($data);
        if ($err) jsonResponse(['error' => $err], 422);

        $stmt = $db->prepare(
            'INSERT INTO expenses (user_id, title, amount, category_id, expense_date, description)
             VALUES (:uid, :title, :amount, :category_id, :expense_date, :description) RETURNING *'
        );
        $stmt->execute([
            ':uid'          => $userId,
            ':title'        => trim($data['title']),
            ':amount'       => (float)$data['amount'],
            ':category_id'  => (int)$data['category_id'],
            ':expense_date' => $data['expense_date'],
            ':description'  => trim($data['description'] ?? ''),
        ]);
        jsonResponse($stmt->fetch(), 201);

    case 'PUT':
        if (!$id) jsonResponse(['error' => 'ID required'], 400);
        $data = json_decode(file_get_contents('php://input'), true);
        $err  = validateExpense($data);
        if ($err) jsonResponse(['error' => $err], 422);

        $stmt = $db->prepare(
            'UPDATE expenses SET title=:title, amount=:amount, category_id=:category_id,
             expense_date=:expense_date, description=:description
             WHERE id=:id AND user_id=:uid RETURNING *'
        );
        $stmt->execute([
            ':title'        => trim($data['title']),
            ':amount'       => (float)$data['amount'],
            ':category_id'  => (int)$data['category_id'],
            ':expense_date' => $data['expense_date'],
            ':description'  => trim($data['description'] ?? ''),
            ':id'           => $id,
            ':uid'          => $userId,
        ]);
        $row = $stmt->fetch();
        $row ? jsonResponse($row) : jsonResponse(['error' => 'Not found'], 404);

    case 'DELETE':
        if (!$id) jsonResponse(['error' => 'ID required'], 400);
        $stmt = $db->prepare('DELETE FROM expenses WHERE id=:id AND user_id=:uid RETURNING id');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $stmt->fetch() ? jsonResponse(['message' => 'Deleted']) : jsonResponse(['error' => 'Not found'], 404);

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

function validateExpense(?array $d): ?string {
    if (!$d)                                    return 'Invalid JSON body';
    if (empty($d['title']))                     return 'Title is required';
    if (!isset($d['amount']) || !is_numeric($d['amount']) || $d['amount'] <= 0) return 'Amount must be a positive number';
    if (empty($d['category_id']))               return 'Category is required';
    if (empty($d['expense_date']))              return 'Date is required';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['expense_date'])) return 'Date must be YYYY-MM-DD';
    return null;
}
