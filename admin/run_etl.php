<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../scripts/olap_etl.php';

header('Content-Type: application/json');

$results = runETL($conn);
echo json_encode([ 'status' => 'ok', 'results' => $results ]);
