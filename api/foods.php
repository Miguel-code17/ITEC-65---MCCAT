<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../connection.php';

function syncFoodsFromJson($conn) {
    $foodsFile = __DIR__ . '/../data/foods.json';
    if (!file_exists($foodsFile)) {
        return;
    }

    $raw = json_decode(file_get_contents($foodsFile), true);
    if (!is_array($raw) || !isset($raw['foods']) || !is_array($raw['foods'])) {
        return;
    }

    $foods = $raw['foods'];
    if (count($foods) === 0) {
        return;
    }

    $stmt = $conn->prepare(
        'INSERT INTO foods (id, name, description, price, category, featured, popular)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           name = VALUES(name),
           description = VALUES(description),
           price = VALUES(price),
           category = VALUES(category),
           featured = VALUES(featured),
           popular = VALUES(popular)'
    );
    if (!$stmt) {
        return;
    }

    foreach ($foods as $food) {
        $id = isset($food['id']) ? (int)$food['id'] : 0;
        if ($id <= 0) {
            continue;
        }
        $name = trim($food['name'] ?? '');
        $description = trim($food['description'] ?? '');
        $price = isset($food['price']) ? (float)$food['price'] : 0.00;
        $category = trim($food['category'] ?? '');
        $featured = !empty($food['featured']) ? 1 : 0;
        $popular = !empty($food['popular']) ? 1 : 0;

        $stmt->bind_param('issdsii', $id, $name, $description, $price, $category, $featured, $popular);
        $stmt->execute();
    }

    $stmt->close();
}

syncFoodsFromJson($conn);

$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$featuredOnly = isset($_GET['featured']);

$sql = 'SELECT id, name, description, price, category, featured, popular FROM foods';
$conditions = [];
$params = [];
$types = '';

if ($category !== '') {
    $conditions[] = 'category = ?';
    $params[] = $category;
    $types .= 's';
}

if ($search !== '') {
    $conditions[] = '(name LIKE ? OR description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $types .= 'ss';
}

if ($featuredOnly) {
    $conditions[] = 'featured = 1';
}

if (count($conditions) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY featured DESC, popular DESC, name ASC';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to prepare menu query.', 'error' => $conn->error]);
    exit;
}

if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$foods = [];
while ($row = $result->fetch_assoc()) {
    $row['price'] = (float)$row['price'];
    $row['featured'] = (int)$row['featured'];
    $row['popular'] = (int)$row['popular'];
    $foods[] = $row;
}

$stmt->close();

echo json_encode(['foods' => $foods]);
