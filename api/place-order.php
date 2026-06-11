<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed.']);
    exit;
}

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload.']);
    exit;
}

$customer = isset($data['customer']) && is_array($data['customer']) ? $data['customer'] : [];
$cart     = isset($data['cart']) && is_array($data['cart']) ? $data['cart'] : [];

$name    = trim($customer['name'] ?? '');
$phone   = trim($customer['phone'] ?? '');
$address = trim($customer['address'] ?? '');
$notes   = trim($customer['notes'] ?? '');

$errors = [];

if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    $errors[] = 'Full name is required and must be between 2 and 100 characters.';
}

$cleanPhone = preg_replace('/[\s\-()]/', '', $phone);
if ($cleanPhone === '' || !preg_match('/^(\+63|0)9[0-9]{9}$/', $cleanPhone)) {
    $errors[] = 'Please enter a valid Philippine phone number (e.g. 09123456789 or +639123456789).';
}

if ($address === '' || mb_strlen($address) < 10) {
    $errors[] = 'Please enter a complete delivery address.';
}

if (count($cart) === 0) {
    $errors[] = 'Your cart is empty. Add at least one item before placing an order.';
}

$calcSubtotal = 0.0;
foreach ($cart as $index => $item) {
    if (!isset($item['food_id'], $item['food_name'], $item['unit_price'], $item['quantity'], $item['line_total'])) {
        $errors[] = 'Cart item at position ' . ($index + 1) . ' is missing required fields.';
        continue;
    }

    $foodId    = filter_var($item['food_id'], FILTER_VALIDATE_INT);
    $foodName  = trim($item['food_name']);
    $unitPrice = filter_var($item['unit_price'], FILTER_VALIDATE_FLOAT);
    $quantity  = filter_var($item['quantity'], FILTER_VALIDATE_INT);
    $lineTotal = filter_var($item['line_total'], FILTER_VALIDATE_FLOAT);

    if ($foodId === false || $foodId <= 0) {
        $errors[] = 'Invalid food item ID at position ' . ($index + 1) . '.';
    }
    if ($foodName === '') {
        $errors[] = 'Invalid food name at position ' . ($index + 1) . '.';
    }
    if ($unitPrice === false || $unitPrice < 0) {
        $errors[] = 'Invalid unit price for item at position ' . ($index + 1) . '.';
    }
    if ($quantity === false || $quantity < 1 || $quantity > 50) {
        $errors[] = 'Quantity must be between 1 and 50 for item at position ' . ($index + 1) . '.';
    }
    if ($lineTotal === false || $lineTotal < 0) {
        $errors[] = 'Invalid line total for item at position ' . ($index + 1) . '.';
    }

    $expectedLineTotal = round($unitPrice * $quantity, 2);
    if (round($lineTotal, 2) !== $expectedLineTotal) {
        $errors[] = 'Line total mismatch for item "' . htmlspecialchars($foodName, ENT_QUOTES, 'UTF-8') . '".';
    }

    $calcSubtotal += $expectedLineTotal;
}

if (count($errors) > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Validation failed.', 'errors' => $errors]);
    exit;
}

$deliveryFee = $calcSubtotal >= 500 ? 0.00 : 49.00;
$grandTotal  = round($calcSubtotal + $deliveryFee, 2);

$payloadSubtotal = isset($data['subtotal']) ? filter_var($data['subtotal'], FILTER_VALIDATE_FLOAT) : false;
$payloadDelivery = isset($data['delivery_fee']) ? filter_var($data['delivery_fee'], FILTER_VALIDATE_FLOAT) : false;
$payloadGrand    = isset($data['grand_total']) ? filter_var($data['grand_total'], FILTER_VALIDATE_FLOAT) : false;

if ($payloadSubtotal === false || round($payloadSubtotal, 2) !== round($calcSubtotal, 2)) {
    $errors[] = 'Subtotal does not match calculated cart total.';
}
if ($payloadDelivery === false || round($payloadDelivery, 2) !== round($deliveryFee, 2)) {
    $errors[] = 'Delivery fee does not match the order total.';
}
if ($payloadGrand === false || round($payloadGrand, 2) !== round($grandTotal, 2)) {
    $errors[] = 'Grand total does not match subtotal plus delivery.';
}

if (count($errors) > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order totals validation failed.', 'errors' => $errors]);
    exit;
}

require_once __DIR__ . '/../connection.php';

$conn->begin_transaction();

$orderSql = "INSERT INTO orders (customer_name, phone, address, notes, subtotal, delivery_fee, grand_total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?);";
$orderStmt = $conn->prepare($orderSql);
$status = 'pending';

if (!$orderStmt) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not prepare order statement.']);
    exit;
}

$orderStmt->bind_param('ssssddds', $name, $phone, $address, $notes, $calcSubtotal, $deliveryFee, $grandTotal, $status);

if (!$orderStmt->execute()) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not create order. Please try again later.']);
    exit;
}

$orderId = $conn->insert_id;
$itemSql = "INSERT INTO order_items (order_id, food_id, food_name, unit_price, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?);";
$itemStmt = $conn->prepare($itemSql);

foreach ($cart as $item) {
    $foodId    = (int)$item['food_id'];
    $foodName  = trim($item['food_name']);
    $unitPrice = round((float)$item['unit_price'], 2);
    $quantity  = (int)$item['quantity'];
    $lineTotal = round((float)$item['line_total'], 2);

    $itemStmt->bind_param('iisdid', $orderId, $foodId, $foodName, $unitPrice, $quantity, $lineTotal);
    if (!$itemStmt->execute()) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Could not save order items. Please try again later.']);
        exit;
    }
}

$conn->commit();

echo json_encode(['success' => true, 'order_id' => $orderId]);
