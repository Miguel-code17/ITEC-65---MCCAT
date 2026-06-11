<?php
// Seed sample data
require_once __DIR__ . '/../connection.php';

echo "=== Seeding Sample Data ===\n";

// Ensure foods exist so order items can reference them
$foodsFile = __DIR__ . '/../data/foods.json';
$foods = [];
if (file_exists($foodsFile)) {
    $rawFoods = json_decode(file_get_contents($foodsFile), true);
    if (is_array($rawFoods) && isset($rawFoods['foods']) && is_array($rawFoods['foods'])) {
        foreach ($rawFoods['foods'] as $food) {
            if (!empty($food['id']) && !empty($food['name'])) {
                $foods[] = [
                    'id' => (int)$food['id'],
                    'name' => $food['name'],
                    'description' => $food['description'] ?? null,
                    'price' => (float)$food['price'],
                    'category' => $food['category'] ?? null,
                    'featured' => !empty($food['featured']) ? 1 : 0,
                    'popular' => !empty($food['popular']) ? 1 : 0,
                ];
            }
        }
    }
}

if (empty($foods)) {
    $foods = [
        ['id' => 1, 'name' => 'Margherita Pizza', 'description' => null, 'price' => 12.99, 'category' => 'Pizza', 'featured' => 1, 'popular' => 1],
        ['id' => 2, 'name' => 'Caesar Salad', 'description' => null, 'price' => 8.50, 'category' => 'Salad', 'featured' => 0, 'popular' => 1],
        ['id' => 3, 'name' => 'Pepperoni Pizza', 'description' => null, 'price' => 14.99, 'category' => 'Pizza', 'featured' => 1, 'popular' => 1],
        ['id' => 4, 'name' => 'Garlic Bread', 'description' => null, 'price' => 4.99, 'category' => 'Sides', 'featured' => 0, 'popular' => 1],
        ['id' => 5, 'name' => 'Veggie Pizza', 'description' => null, 'price' => 13.50, 'category' => 'Pizza', 'featured' => 0, 'popular' => 0],
        ['id' => 6, 'name' => 'Tiramisu', 'description' => null, 'price' => 5.99, 'category' => 'Dessert', 'featured' => 0, 'popular' => 0],
        ['id' => 7, 'name' => 'Coca Cola', 'description' => null, 'price' => 2.50, 'category' => 'Drinks', 'featured' => 0, 'popular' => 0],
    ];
}

foreach ($foods as $f) {
    $stmtf = $conn->prepare("SELECT id FROM foods WHERE id = ? LIMIT 1");
    $stmtf->bind_param('i', $f['id']);
    $stmtf->execute();
    $resf = $stmtf->get_result();
    if ($resf->num_rows === 0) {
        $ins = $conn->prepare("INSERT INTO foods (id, name, description, price, category, featured, popular) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param('issdsii', $f['id'], $f['name'], $f['description'], $f['price'], $f['category'], $f['featured'], $f['popular']);
        $ins->execute();
        $ins->close();
        echo "✓ Food added: {$f['name']} (ID: {$f['id']})\n";
    } else {
        $upd = $conn->prepare("UPDATE foods SET name = ?, description = ?, price = ?, category = ?, featured = ?, popular = ? WHERE id = ?");
        $upd->bind_param('ssdiiii', $f['name'], $f['description'], $f['price'], $f['category'], $f['featured'], $f['popular'], $f['id']);
        $upd->execute();
        $upd->close();
        echo "→ Food updated: {$f['name']} (ID: {$f['id']})\n";
    }
    $stmtf->close();
}

// Sample users
$users = [
    ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com', 'phone' => '09123456789'],
    ['first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@example.com', 'phone' => '09987654321'],
    ['first_name' => 'Bob', 'last_name' => 'Wilson', 'email' => 'bob@example.com', 'phone' => '09112233445'],
];

foreach ($users as $u) {
    $hashed = password_hash('password123', PASSWORD_DEFAULT);
    // Skip if user already exists
    $checkUser = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $checkUser->bind_param('s', $u['email']);
    $checkUser->execute();
    $resUser = $checkUser->get_result();
    if ($resUser && $resUser->num_rows > 0) {
        echo "→ User already exists, skipping: {$u['email']}\n";
        $checkUser->close();
        continue;
    }
    $checkUser->close();

    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssss', $u['first_name'], $u['last_name'], $u['email'], $u['phone'], $hashed);
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
            $stmt2->bind_param('iisdid', $order_id, $item['food_id'], $item['food_name'], $item['unit_price'], $item['quantity'], $line_total);
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
