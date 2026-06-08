<?php
// Seed sample data
require_once __DIR__ . '/../connection.php';

echo "=== Seeding Sample Data ===\n";

// Sample users
$users = [
    ['fullname' => 'John Doe', 'email' => 'john@example.com', 'phone' => '5551234567'],
    ['fullname' => 'Jane Smith', 'email' => 'jane@example.com', 'phone' => '5559876543'],
    ['fullname' => 'Bob Wilson', 'email' => 'bob@example.com', 'phone' => '5555551234'],
];

foreach ($users as $u) {
    $hashed = password_hash('password123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $u['fullname'], $u['email'], $u['phone'], $hashed);
    if ($stmt->execute()) {
        echo "✓ User created: {$u['email']}\n";
    } else {
        echo "✗ User insert failed: " . $stmt->error . "\n";
    }
}

// Sample orders
$orders_data = [
    [
        'customer_name' => 'Alice Chen',
        'phone' => '5551111111',
        'address' => '123 Main St, Springfield',
        'notes' => 'No onions please',
        'items' => [
            ['food_id' => 1, 'food_name' => 'Margherita Pizza', 'unit_price' => 12.99, 'quantity' => 2],
            ['food_id' => 2, 'food_name' => 'Caesar Salad', 'unit_price' => 8.50, 'quantity' => 1],
        ],
        'created_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
    ],
    [
        'customer_name' => 'Bob Taylor',
        'phone' => '5552222222',
        'address' => '456 Oak Ave, Shelbyville',
        'notes' => 'Extra cheese',
        'items' => [
            ['food_id' => 3, 'food_name' => 'Pepperoni Pizza', 'unit_price' => 14.99, 'quantity' => 1],
            ['food_id' => 4, 'food_name' => 'Garlic Bread', 'unit_price' => 4.99, 'quantity' => 2],
        ],
        'created_at' => date('Y-m-d H:i:s', strtotime('-8 days')),
    ],
    [
        'customer_name' => 'Carol Davis',
        'phone' => '5553333333',
        'address' => '789 Elm St, Capital City',
        'notes' => 'Delivery after 6pm',
        'items' => [
            ['food_id' => 5, 'food_name' => 'Veggie Pizza', 'unit_price' => 13.50, 'quantity' => 1],
        ],
        'created_at' => date('Y-m-d H:i:s', strtotime('-5 days 10:00:00')),
    ],
    [
        'customer_name' => 'David Martinez',
        'phone' => '5554444444',
        'address' => '321 Pine Rd, Shelbyville',
        'notes' => null,
        'items' => [
            ['food_id' => 1, 'food_name' => 'Margherita Pizza', 'unit_price' => 12.99, 'quantity' => 3],
            ['food_id' => 4, 'food_name' => 'Garlic Bread', 'unit_price' => 4.99, 'quantity' => 1],
            ['food_id' => 6, 'food_name' => 'Tiramisu', 'unit_price' => 5.99, 'quantity' => 2],
        ],
        'created_at' => date('Y-m-d H:i:s', strtotime('-3 days 15:30:00')),
    ],
    [
        'customer_name' => 'Emma Wilson',
        'phone' => '5555555555',
        'address' => '654 Birch Ln, Capital City',
        'notes' => 'Ring doorbell twice',
        'items' => [
            ['food_id' => 2, 'food_name' => 'Caesar Salad', 'unit_price' => 8.50, 'quantity' => 2],
            ['food_id' => 7, 'food_name' => 'Coca Cola', 'unit_price' => 2.50, 'quantity' => 3],
        ],
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 days 12:00:00')),
    ],
    [
        'customer_name' => 'Frank Brown',
        'phone' => '5556666666',
        'address' => '987 Maple Dr, Shelbyville',
        'notes' => 'Please call when arrive',
        'items' => [
            ['food_id' => 3, 'food_name' => 'Pepperoni Pizza', 'unit_price' => 14.99, 'quantity' => 2],
            ['food_id' => 4, 'food_name' => 'Garlic Bread', 'unit_price' => 4.99, 'quantity' => 1],
        ],
        'created_at' => date('Y-m-d H:i:s', strtotime('now -2 hours')),
    ],
];

foreach ($orders_data as $ord) {
    $subtotal = 0;
    foreach ($ord['items'] as $item) {
        $subtotal += $item['unit_price'] * $item['quantity'];
    }
    $delivery_fee = 3.50;
    $grand_total = $subtotal + $delivery_fee;
    $status = 'completed';
    
    $stmt = $conn->prepare("INSERT INTO orders (customer_name, phone, address, notes, subtotal, delivery_fee, grand_total, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssdddss', $ord['customer_name'], $ord['phone'], $ord['address'], $ord['notes'], $subtotal, $delivery_fee, $grand_total, $status, $ord['created_at']);
    
    if ($stmt->execute()) {
        $order_id = $conn->insert_id;
        echo "✓ Order #{$order_id} created for {$ord['customer_name']}\n";
        
        // Insert items
        foreach ($ord['items'] as $item) {
            $line_total = $item['unit_price'] * $item['quantity'];
            $stmt2 = $conn->prepare("INSERT INTO order_items (order_id, food_id, food_name, unit_price, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param('iisidi', $order_id, $item['food_id'], $item['food_name'], $item['unit_price'], $item['quantity'], $line_total);
            if ($stmt2->execute()) {
                echo "  ✓ Added {$item['quantity']}x {$item['food_name']}\n";
            } else {
                echo "  ✗ Failed to add item: " . $stmt2->error . "\n";
            }
        }
    } else {
        echo "✗ Order insert failed: " . $stmt->error . "\n";
    }
}

echo "\n=== Sample Data Seeded Successfully ===\n";
echo "Run: php scripts/olap_etl.php\n";
echo "Then visit: /admin/oltp.php and /admin/olap.php\n";
?>
