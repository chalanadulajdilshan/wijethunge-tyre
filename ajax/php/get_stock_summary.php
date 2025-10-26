<?php
require_once '../../class/include.php'; 

header('Content-Type: application/json');

$response = [
    'status' => 'error',
    'message' => 'An error occurred',
    'data' => null
];

try {
    // Get POST parameters
    $department_id = isset($_POST['department_id']) ? $_POST['department_id'] : 'all';
    $brand_id = isset($_POST['brand_id']) ? $_POST['brand_id'] : 'all';
    $brand_name = isset($_POST['brand_name']) ? $_POST['brand_name'] : null;
    
    // If brand_id is 'all' but brand_name is provided, try to find the ID
    if (($brand_id === 'all' || $brand_id === '') && $brand_name && $brand_name !== 'Show All Brands') {
        $brand = new Brand();
        $brands = $brand->all();
        foreach ($brands as $b) {
            if ($b['name'] === $brand_name) {
                $brand_id = $b['id'];
                break;
            }
        }
    }

    // Get stock summary data
    $stock_summary = StockMaster::getStockSummary($department_id, $brand_id);

    // Add brand name
    if ($brand_id !== 'all' && $brand_id) {
        $brand = new Brand($brand_id);
        $stock_summary['brand_name'] = $brand->name;
    }

    // Prepare success response
    $response = [
        'status' => 'success',
        'message' => 'Stock summary retrieved successfully',
        'data' => [
            'summary' => $stock_summary
        ]
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
