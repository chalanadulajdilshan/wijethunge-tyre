<?php

include '../../class/include.php';

header('Content-Type: application/json; charset=UTF8');

// Fetch single item by id (for reliable prefill)
if (isset($_POST['action']) && $_POST['action'] === 'get_by_id') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $response = ['status' => 'error', 'message' => 'Item not found'];
    try {
        if ($id <= 0) {
            throw new Exception('Invalid id');
        }
        $db = new Database();
        $sql = "SELECT * FROM item_master WHERE id = $id LIMIT 1";
        $res = $db->readQuery($sql);
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            $item = [
                'id' => (int)$row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'brand_id' => (int)$row['brand'],
                'category_id' => (int)$row['category'],
                'group' => (int)$row['group'],
                'size' => $row['size'],
                'pattern' => $row['pattern'],
                'list_price' => (float)$row['customer_price'],
                'invoice_price' => (float)$row['dealer_price'],
                're_order_level' => $row['re_order_level'],
                're_order_qty' => $row['re_order_qty'],
                'stock_type' => $row['stock_type'],
                'discount' => $row['discount'],
                'note' => $row['note'],
                'status' => (int)$row['is_active']
            ];
            echo json_encode(['status' => 'success', 'item' => $item]);
            exit;
        }
    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    }
    echo json_encode($response);
    exit;
}

// Fetch single item by exact code (for reliable prefill)
if (isset($_POST['action']) && $_POST['action'] === 'get_by_code') {
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';
    $response = ['status' => 'error', 'message' => 'Item not found'];
    try {
        if ($code === '') {
            throw new Exception('Invalid code');
        }
        $db = new Database();
        $escCode = mysqli_real_escape_string($db->DB_CON, $code);
        $sql = "SELECT * FROM item_master WHERE code = '" . $escCode . "' LIMIT 1";
        $res = $db->readQuery($sql);
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            $item = [
                'id' => (int)$row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'brand_id' => (int)$row['brand'],
                'category_id' => (int)$row['category'],
                'group' => (int)$row['group'],
                'size' => $row['size'],
                'pattern' => $row['pattern'],
                'list_price' => (float)$row['customer_price'],
                'invoice_price' => (float)$row['dealer_price'],
                're_order_level' => $row['re_order_level'],
                're_order_qty' => $row['re_order_qty'],
                'stock_type' => $row['stock_type'],
                'discount' => $row['discount'],
                'note' => $row['note'],
                'status' => (int)$row['is_active']
            ];
            echo json_encode(['status' => 'success', 'item' => $item]);
            exit;
        }
    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    }
    echo json_encode($response);
    exit;
}

// Create a new item
if (isset($_POST['create'])) {

    $ITEM = new ItemMaster(NULL); // Create a new ItemMaster object

    // Set item details
    $ITEM->code = $_POST['code'];
    $ITEM->name = $_POST['name'];
    $ITEM->brand = $_POST['brand'];
    $ITEM->size = $_POST['size'];
    $ITEM->pattern = $_POST['pattern'];
    $ITEM->group = $_POST['group'];
    $ITEM->category = $_POST['category'];
    $ITEM->customer_price = $_POST['list_price'];
    $ITEM->dealer_price = $_POST['invoice_price'];
    $ITEM->re_order_level = $_POST['re_order_level'];
    $ITEM->re_order_qty = $_POST['re_order_qty'];
    $ITEM->stock_type = $_POST['stock_type'];
    $ITEM->note = $_POST['note'];
    $ITEM->is_active = isset($_POST['is_active']) ? 1 : 0; //  

    // Attempt to create the item
    $res = $ITEM->create();

    //audit log
    $AUDIT_LOG = new AuditLog(NUll);
    $AUDIT_LOG->ref_id = $res;
    $AUDIT_LOG->ref_code = $_POST['code'];
    $AUDIT_LOG->action = 'CREATE';
    $AUDIT_LOG->description = 'CREATE ITEM NO #' . $_POST['code'];
    $AUDIT_LOG->user_id = $_SESSION['id'];
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();


    $DOCUMENT_TRACKING = new DocumentTracking(null);
    $DOCUMENT_TRACKING->incrementDocumentId('item');
    
    if ($res) {
        $result = [
            "status" => 'success'
        ];
        echo json_encode($result);
        exit();
    } else {
        // Check if it's a duplicate error
        $db = new Database();
        $escapedName = mysqli_real_escape_string($db->DB_CON, $_POST['name']);
        $checkQuery = "SELECT id FROM item_master WHERE UPPER(TRIM(name)) = UPPER(TRIM('$escapedName')) LIMIT 1";
        $checkResult = $db->readQuery($checkQuery);
        if (mysqli_num_rows($checkResult) > 0) {
            $result = [
                "status" => 'error',
                "message" => 'Duplicate item name found. Item name already exists.'
            ];
        } else {
            $result = [
                "status" => 'error'
            ];
        }
        echo json_encode($result);
        exit();
    }
}

// Update item details
if (isset($_POST['update'])) {

    $ITEM = new ItemMaster($_POST['item_id']); // Retrieve item by ID

    // Update item details
    $ITEM->code = $_POST['code'];
    $ITEM->name = $_POST['name'];
    $ITEM->brand = $_POST['brand'];
    $ITEM->size = $_POST['size'];
    $ITEM->pattern = $_POST['pattern'];
    $ITEM->group = $_POST['group'];
    $ITEM->category = $_POST['category'];
    $ITEM->re_order_level = $_POST['re_order_level'];
    $ITEM->re_order_qty = $_POST['re_order_qty'];
    $ITEM->stock_type = $_POST['stock_type'];
    $ITEM->note = $_POST['note'];
    $ITEM->customer_price = $_POST['list_price'];
    $ITEM->dealer_price = $_POST['invoice_price'];
    $ITEM->discount = $_POST['discount'];
    $ITEM->is_active = isset($_POST['is_active']) ? 1 : 0;

    // Attempt to update the item
    $result = $ITEM->update();


    //audit log
    $AUDIT_LOG = new AuditLog(NUll);
    $AUDIT_LOG->ref_id = $_POST['item_id'];
    $AUDIT_LOG->ref_code = $_POST['code'];
    $AUDIT_LOG->action = 'UPDATE';
    $AUDIT_LOG->description = 'UPDATE ITEM NO #' . $_POST['code'];
    $AUDIT_LOG->user_id = $_SESSION['id'];
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();

    if ($result) {
        $result = [
            "status" => 'success'
        ];
        echo json_encode($result);
        exit();
    } else {
        // Check if it's a duplicate error
        $db = new Database();
        $escapedName = mysqli_real_escape_string($db->DB_CON, $_POST['name']);
        $checkQuery = "SELECT id FROM item_master WHERE UPPER(TRIM(name)) = UPPER(TRIM('$escapedName')) AND id != '{$_POST['item_id']}' LIMIT 1";
        $checkResult = $db->readQuery($checkQuery);
        if (mysqli_num_rows($checkResult) > 0) {
            $result = [
                "status" => 'error',
                "message" => 'Duplicate item name found. Item name already exists.'
            ];
        } else {
            $result = [
                "status" => 'error'
            ];
        }
        echo json_encode($result);
        exit();
    }
}

// Delete item
if (isset($_POST['delete']) && isset($_POST['id'])) {
    try {
        $ITEM_MASTER = new ItemMaster($_POST['id']);

        if (!$ITEM_MASTER->id) {
            throw new Exception('Item not found');
        }

        $result = $ITEM_MASTER->delete();

        if ($result) {
            // Add audit log
            $AUDIT_LOG = new AuditLog(null);
            $AUDIT_LOG->ref_id = $_POST['id'];
            $AUDIT_LOG->ref_code = $ITEM_MASTER->code;
            $AUDIT_LOG->action = 'DELETE';
            $AUDIT_LOG->description = 'DELETED ITEM #' . $ITEM_MASTER->code;
            $AUDIT_LOG->user_id = $_SESSION['id'];
            $AUDIT_LOG->created_at = date('Y-m-d H:i:s');
            $AUDIT_LOG->create();

            echo json_encode(['status' => 'success', 'message' => 'Item deleted successfully']);
        } else {
            throw new Exception('Failed to delete item');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

//filter for item for invoices
if (isset($_POST['filter_by_invoice'])) {
    $ITEM_MASTER = new ItemMaster();
    $response = $ITEM_MASTER->fetchForDataTable($_REQUEST);

    echo json_encode($response);
    exit;
}

if (isset($_POST['filter'])) {
    
    
    $ITEM_MASTER = new ItemMaster();
    $response = $ITEM_MASTER->fetchForDataTable($_REQUEST);

    echo json_encode($response);
    exit;
}

// Handle DataTable server-side processing
if (isset($_POST['action']) && $_POST['action'] === 'fetch_for_datatable') {
    $itemMaster = new ItemMaster();

    // If department_id is provided, ensure it's an integer
    if (isset($_POST['department_id']) && !empty($_POST['department_id'])) {
        $_POST['department_id'] = (int)$_POST['department_id'];
    } else {
        // If no department is selected, you might want to handle this case
        // For now, we'll unset it to show all items
        unset($_POST['department_id']);
    }

    $result = $itemMaster->fetchForDataTable($_POST);
    echo json_encode($result);
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'get_items_with_stock') {
    $itemMaster = new ItemMaster();
    $items = $itemMaster::getItemsWithStock();
    echo json_encode(['data' => $items]);
    exit();
}

// Handle stock adjustment item filtering
if (isset($_POST['action']) && $_POST['action'] === 'fetch_for_stock_adjustment') {
    $response = [
        'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => null
    ];

    try {
        if (!isset($_POST['department_id']) || empty($_POST['department_id'])) {
            throw new Exception('Department ID is required');
        }

        $department_id = (int)$_POST['department_id'];
        $search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
        $show_zero_qty = isset($_POST['show_zero_qty']) ? (bool)$_POST['show_zero_qty'] : false;

        // Get items with department stock
        $items = ItemMaster::getItemsByDepartmentAndStock(
            $department_id,
            $show_zero_qty ? -1 : 1, // 1 means filter out zero quantity items, -1 means show all
            $search
        );

        // Ensure $items is an array
        if (!is_array($items)) {
            throw new Exception('Invalid data format received from getItemsByDepartmentAndStock');
        }

        // Format the response for DataTables
        $formattedData = [];
        foreach ($items as $item) {
            $formattedData[] = [
                'DT_RowId' => 'row_' . $item['id'],
                'id' => $item['id'],
                'code' => $item['code'],
                'name' => $item['name'],
                'brand' => $item['brand_name'] ?? '',
                'category' => $item['category_name'] ?? '',
                'list_price' => number_format($item['customer_price'], 2),
                'invoice_price' => number_format($item['dealer_price'], 2),
                'available_qty' => (int)($item['available_qty'] ?? 0),
                'discount' => isset($item['discount']) ? $item['discount'] . '%' : '0%',
                'status_label' => ($item['is_active'] ?? 0) == 1 ?
                    '<span class="badge bg-success">Active</span>' :
                    '<span class="badge bg-danger">Inactive</span>',
                'department_stock' => [
                    [
                        'department_id' => $department_id,
                        'quantity' => (int)($item['available_qty'] ?? 0)
                    ]
                ]
            ];
        }

        $response['recordsTotal'] = count($formattedData);
        $response['recordsFiltered'] = count($formattedData);
        $response['data'] = $formattedData;
    } catch (Exception $e) {
        error_log('Error in fetch_for_stock_adjustment: ' . $e->getMessage());
        $response['error'] = $e->getMessage();
    }

    // Ensure we're sending valid JSON
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// Get total cost, total invoice, and profit % from all ARN lots
if (isset($_POST['action']) && $_POST['action'] === 'get_totals') {
    try {
        $db = new Database();
        $sql = "SELECT 
            SUM(cost * qty) as total_cost, 
            SUM(invoice_price * qty) as total_invoice 
            FROM stock_item_tmp";
        $departmentId = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
        if ($departmentId > 0) {
            $sql .= " WHERE department_id = $departmentId";
        }
        $res = $db->readQuery($sql);
        $totals = ['total_cost' => 0, 'total_invoice' => 0, 'profit_percentage' => 0];
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            $totals['total_cost'] = (float)$row['total_cost'];
            $totals['total_invoice'] = (float)$row['total_invoice'];
            if ($totals['total_cost'] > 0) {
                $totals['profit_percentage'] = (($totals['total_invoice'] - $totals['total_cost']) / $totals['total_cost']) * 100;
            }
        }
        echo json_encode(['status' => 'success', 'data' => $totals]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// Fetch ARN-wise stock lots for a given item id (for Live Stock row details)
if (isset($_POST['action']) && $_POST['action'] === 'get_stock_tmp_by_item') {
    try {
        $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $departmentFilterId = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
        if ($itemId <= 0) {
            throw new Exception('Invalid item_id');
        }

        $STOCK_TMP = new StockItemTmp(NULL);
        $lots = $STOCK_TMP->getByItemId($itemId);

        // If a department is specified, filter to that department only
        if ($departmentFilterId > 0) {
            $lots = array_values(array_filter($lots, function ($l) use ($departmentFilterId) {
                return isset($l['department_id']) && (int)$l['department_id'] === $departmentFilterId;
            }));
        }

        // Decorate with ARN number and department name
        $decorated = [];
        foreach ($lots as $lot) {
            $arnNo = null;
            if (!empty($lot['arn_id'])) {
                $ARN = new ArnMaster((int)$lot['arn_id']);
                if ($ARN && isset($ARN->arn_no)) {
                    $arnNo = $ARN->arn_no;
                }
            }
            $deptName = null;
            if (!empty($lot['department_id'])) {
                $DEPT = new DepartmentMaster((int)$lot['department_id']);
                if ($DEPT && isset($DEPT->name)) {
                    $deptName = $DEPT->name;
                }
            }
            $lot['arn_no'] = $arnNo;
            $lot['department'] = $deptName;
            $decorated[] = $lot;
        }

        echo json_encode(['status' => 'success', 'data' => $decorated]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// Debug endpoint to show available brand IDs
if (isset($_POST['action']) && $_POST['action'] === 'debug_brands') {
    try {
        $db = new Database();
        $brandQuery = "SELECT id, name FROM brands ORDER BY id ASC";
        $result = $db->readQuery($brandQuery);
        
        $brands = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $brands[] = $row;
        }
        
        echo json_encode(['status' => 'success', 'brands' => $brands]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// Debug endpoint to show available group IDs
if (isset($_POST['action']) && $_POST['action'] === 'debug_groups') {
    try {
        $db = new Database();
        $groupQuery = "SELECT id, name FROM group_master ORDER BY id ASC";
        $result = $db->readQuery($groupQuery);
        
        $groups = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $groups[] = $row;
        }
        
        echo json_encode(['status' => 'success', 'groups' => $groups]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// Debug endpoint to show available category IDs
if (isset($_POST['action']) && $_POST['action'] === 'debug_categories') {
    try {
        $db = new Database();
        $categoryQuery = "SELECT id, name FROM category_master ORDER BY id ASC";
        $result = $db->readQuery($categoryQuery);
        
        $categories = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
        
        echo json_encode(['status' => 'success', 'categories' => $categories]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// Debug endpoint to show available stock type IDs
if (isset($_POST['action']) && $_POST['action'] === 'debug_stock_types') {
    try {
        $db = new Database();
        $stockTypeQuery = "SELECT id, name FROM stock_type ORDER BY id ASC";
        $result = $db->readQuery($stockTypeQuery);
        
        $stock_types = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $stock_types[] = $row;
        }
        
        echo json_encode(['status' => 'success', 'stock_types' => $stock_types]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// Handle Excel file upload
if (isset($_POST['upload_excel']) && isset($_FILES['excelFile'])) {
    try {
        $uploadedFile = $_FILES['excelFile'];
        $skipDuplicates = isset($_POST['skipDuplicates']) ? (bool)$_POST['skipDuplicates'] : true;
        
        // Validate file
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error');
        }
        
        $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ['xlsx', 'xls', 'csv'])) {
            throw new Exception('Invalid file format. Please upload Excel (.xlsx, .xls) or CSV file');
        }
        
        // Move uploaded file to temporary location
        $tempFile = sys_get_temp_dir() . '/' . uniqid() . '.' . $fileExtension;
        if (!move_uploaded_file($uploadedFile['tmp_name'], $tempFile)) {
            throw new Exception('Failed to process uploaded file');
        }
        
        $items = [];
        
        // Process CSV file (simplified approach)
        if ($fileExtension === 'csv') {
            $items = parseCSVFile($tempFile);
        } else {
            // Convert Excel to CSV for processing (simple approach)
            throw new Exception('Excel files not supported yet. Please save your file as CSV format and upload again.');
        }
        
        if (empty($items)) {
            throw new Exception('No valid data found in the uploaded file');
        }
        
        // Process items with ItemMaster
        $itemMaster = new ItemMaster();
        $result = $itemMaster->bulkInsert($items, $skipDuplicates);
        
        // Clean up temporary file
        unlink($tempFile);
        
        $message = "Successfully processed {$result['inserted']} items";
        if ($result['skipped'] > 0) {
            $message .= ", skipped {$result['skipped']} duplicates";
        }
        if (!empty($result['errors'])) {
            $message .= ". Errors: " . implode('; ', array_slice($result['errors'], 0, 3));
            if (count($result['errors']) > 3) {
                $message .= " and " . (count($result['errors']) - 3) . " more...";
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'details' => $result
        ]);
        
    } catch (Exception $e) {
        // Clean up temporary file if it exists
        if (isset($tempFile) && file_exists($tempFile)) {
            unlink($tempFile);
        }
        
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit();
}

function parseCSVFile($filePath) {
    $items = [];
    $handle = fopen($filePath, 'r');
    
    if ($handle === false) {
        throw new Exception('Cannot read CSV file');
    }
    
    // Read header row
    $headers = fgetcsv($handle);
    if (!$headers) {
        fclose($handle);
        throw new Exception('Invalid CSV format - no headers found');
    }
    
    // Map headers to expected fields (case-insensitive)
    $headerMap = mapHeaders($headers);
    
    $rowIndex = 1;
    while (($row = fgetcsv($handle)) !== false) {
        $rowIndex++;
        if (count($row) < count($headers)) {
            continue; // Skip incomplete rows
        }
        
        $item = [];
        foreach ($headerMap as $csvIndex => $fieldName) {
            $item[$fieldName] = isset($row[$csvIndex]) ? trim($row[$csvIndex]) : '';
        }
        
        // Validate and convert data types
        $processedItem = processItemData($item, $rowIndex);
        if ($processedItem) {
            $items[] = $processedItem;
        }
    }
    
    fclose($handle);
    return $items;
}

function mapHeaders($headers) {
    $headerMap = [];
    $fieldMappings = [
        'code' => ['code', 'item_code', 'item code', 'product_code'],
        'name' => ['name', 'item_name', 'item name', 'product_name', 'product name'],
        'brand' => ['brand', 'brand_id', 'brand id', 'manufacturer'],
        'size' => ['size', 'item_size', 'item size'],
        'pattern' => ['pattern', 'item_pattern', 'item pattern'],
        'group' => ['group', 'group_id', 'group id', 'item_group'],
        'category' => ['category', 'category_id', 'category id', 'item_category'],
        'customer_price' => ['customer_price', 'customer price', 'list_price', 'list price', 'price'],
        'dealer_price' => ['dealer_price', 'dealer price', 'invoice_price', 'invoice price', 'wholesale_price'],
        're_order_level' => ['re_order_level', 'reorder_level', 'reorder level', 'minimum_stock'],
        're_order_qty' => ['re_order_qty', 'reorder_qty', 'reorder quantity', 'reorder_quantity'],
        'stock_type' => ['stock_type', 'stock type', 'stock_type_id'],
        'note' => ['note', 'notes', 'description', 'remarks'],
        'discount' => ['discount', 'discount_percent', 'discount %'],
        'is_active' => ['is_active', 'active', 'status']
    ];
    
    foreach ($headers as $index => $header) {
        $normalizedHeader = strtolower(trim($header));
        foreach ($fieldMappings as $field => $possibleNames) {
            if (in_array($normalizedHeader, $possibleNames)) {
                $headerMap[$index] = $field;
                break;
            }
        }
    }
    
    return $headerMap;
}

function processItemData($item, $rowIndex) {
    // Skip empty rows
    if (empty($item['name'])) {
        return null;
    }
    
    // Get database references for lookups
    $db = new Database();
    
    // Handle brand - accept ID directly, lookup by name, or extract from item name
    if (!empty($item['brand'])) {
        if (is_numeric($item['brand'])) {
            // Validate that the brand ID exists
            $brandId = (int)$item['brand'];
            $brandCheckQuery = "SELECT id FROM brands WHERE id = $brandId LIMIT 1";
            $brandCheckResult = $db->readQuery($brandCheckQuery);
            if (!mysqli_fetch_assoc($brandCheckResult)) {
                throw new Exception("Row $rowIndex: Brand ID '{$item['brand']}' does not exist");
            }
            $item['brand'] = $brandId;
        } else {
            // Look up by name
            $brandName = mysqli_real_escape_string($db->DB_CON, $item['brand']);
            $brandQuery = "SELECT id FROM brands WHERE UPPER(name) = UPPER('$brandName') LIMIT 1";
            $brandResult = $db->readQuery($brandQuery);
            if ($brandRow = mysqli_fetch_assoc($brandResult)) {
                $item['brand'] = $brandRow['id'];
            } else {
                throw new Exception("Row $rowIndex: Brand '{$item['brand']}' not found");
            }
        }
    } elseif (!empty($item['name'])) {
        // If brand is empty, try to extract it from the first word of item name
        $nameParts = explode(' ', trim($item['name']));
        $potentialBrand = $nameParts[0];
        
        if (!empty($potentialBrand)) {
            // Look up the extracted brand name
            $brandName = mysqli_real_escape_string($db->DB_CON, $potentialBrand);
            $brandQuery = "SELECT id FROM brands WHERE UPPER(name) = UPPER('$brandName') LIMIT 1";
            $brandResult = $db->readQuery($brandQuery);
            if ($brandRow = mysqli_fetch_assoc($brandResult)) {
                $item['brand'] = $brandRow['id'];
            } else {
                throw new Exception("Row $rowIndex: Could not find brand '{$potentialBrand}' extracted from item name '{$item['name']}'");
            }
        }
    }
    
    // Handle group - accept ID directly or lookup by name
    if (!empty($item['group'])) {
        if (is_numeric($item['group'])) {
            // Validate that the group ID exists
            $groupId = (int)$item['group'];
            $groupCheckQuery = "SELECT id FROM group_master WHERE id = $groupId LIMIT 1";
            $groupCheckResult = $db->readQuery($groupCheckQuery);
            if (!mysqli_fetch_assoc($groupCheckResult)) {
                throw new Exception("Row $rowIndex: Group ID '{$item['group']}' does not exist");
            }
            $item['group'] = $groupId;
        } else {
            // Look up by name
            $groupName = mysqli_real_escape_string($db->DB_CON, $item['group']);
            $groupQuery = "SELECT id FROM group_master WHERE UPPER(name) = UPPER('$groupName') LIMIT 1";
            $groupResult = $db->readQuery($groupQuery);
            if ($groupRow = mysqli_fetch_assoc($groupResult)) {
                $item['group'] = $groupRow['id'];
            } else {
                throw new Exception("Row $rowIndex: Group '{$item['group']}' not found");
            }
        }
    }
    
    // Handle category - accept ID directly or lookup by name
    if (!empty($item['category'])) {
        if (is_numeric($item['category'])) {
            // Validate that the category ID exists
            $categoryId = (int)$item['category'];
            $categoryCheckQuery = "SELECT id FROM category_master WHERE id = $categoryId LIMIT 1";
            $categoryCheckResult = $db->readQuery($categoryCheckQuery);
            if (!mysqli_fetch_assoc($categoryCheckResult)) {
                throw new Exception("Row $rowIndex: Category ID '{$item['category']}' does not exist");
            }
            $item['category'] = $categoryId;
        } else {
            // Look up by name
            $categoryName = mysqli_real_escape_string($db->DB_CON, $item['category']);
            $categoryQuery = "SELECT id FROM category_master WHERE UPPER(name) = UPPER('$categoryName') LIMIT 1";
            $categoryResult = $db->readQuery($categoryQuery);
            if ($categoryRow = mysqli_fetch_assoc($categoryResult)) {
                $item['category'] = $categoryRow['id'];
            } else {
                throw new Exception("Row $rowIndex: Category '{$item['category']}' not found");
            }
        }
    }
    
    // Handle stock type - accept ID directly or lookup by name
    if (!empty($item['stock_type'])) {
        if (is_numeric($item['stock_type'])) {
            // Validate that the stock type ID exists
            $stockTypeId = (int)$item['stock_type'];
            $stockTypeCheckQuery = "SELECT id FROM stock_type WHERE id = $stockTypeId LIMIT 1";
            $stockTypeCheckResult = $db->readQuery($stockTypeCheckQuery);
            if (!mysqli_fetch_assoc($stockTypeCheckResult)) {
                throw new Exception("Row $rowIndex: Stock Type ID '{$item['stock_type']}' does not exist");
            }
            $item['stock_type'] = $stockTypeId;
        } else {
            // Look up by name
            $stockTypeName = mysqli_real_escape_string($db->DB_CON, $item['stock_type']);
            $stockTypeQuery = "SELECT id FROM stock_type WHERE UPPER(name) = UPPER('$stockTypeName') LIMIT 1";
            $stockTypeResult = $db->readQuery($stockTypeQuery);
            if ($stockTypeRow = mysqli_fetch_assoc($stockTypeResult)) {
                $item['stock_type'] = $stockTypeRow['id'];
            } else {
                throw new Exception("Row $rowIndex: Stock Type '{$item['stock_type']}' not found");
            }
        }
    }
    
    // Convert active status
    if (isset($item['is_active'])) {
        $activeValue = strtolower(trim($item['is_active']));
        $item['is_active'] = in_array($activeValue, ['1', 'true', 'yes', 'active', 'y']) ? 1 : 0;
    }
    
    return $item;
}
