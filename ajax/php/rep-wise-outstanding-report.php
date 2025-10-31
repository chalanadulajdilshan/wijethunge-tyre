<?php
header('Content-Type: application/json');
require_once('../../class/Database.php');
require_once('../../class/MarketingExecutive.php');

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => []
];

try {
    // Check if the request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get the action
    $action = $_POST['action'] ?? '';

    if ($action === 'get_reps') {
        // Get all active marketing executives (REPs) and sales representatives
        $marketingExec = new MarketingExecutive(null);
        
        // Get all executives using the improved method
        $executives = $marketingExec->getAllExecutives();
        
        // Debug: Log the count
        error_log("Found " . count($executives) . " executives");
        
        // Fallback: if getAllExecutives returns empty, try the basic method
        if (empty($executives)) {
            error_log("getAllExecutives returned empty, trying getActiveExecutives");
            $executives = $marketingExec->getActiveExecutives();
            error_log("getActiveExecutives found " . count($executives) . " executives");
        }
        
        $data = [];
        
        foreach ($executives as $exec) {
            // Determine role type based on code prefix or other criteria
            $roleType = 'marketing_executive';
            $roleLabel = '';
            
            // Debug log each executive
            error_log("Processing executive: " . $exec['full_name'] . " (Code: " . $exec['code'] . ")");
            
            if (isset($exec['role_type'])) {
                // If role_type is already set from the query
                if (stripos($exec['role_type'], 'Sales') !== false) {
                    $roleType = 'sales_executive';
                    $roleLabel = ' (Sales)';
                } else if (stripos($exec['role_type'], 'Marketing') !== false) {
                    $roleType = 'marketing_executive';
                    $roleLabel = ' (Marketing)';
                }
            } else {
                // Fallback: determine by code prefix
                if (isset($exec['code']) && stripos($exec['code'], 'SE') === 0) {
                    $roleType = 'sales_executive';
                    $roleLabel = ' (Sales)';
                } else if (isset($exec['code']) && stripos($exec['code'], 'ME') === 0) {
                    $roleType = 'marketing_executive';
                    $roleLabel = ' (Marketing)';
                } else {
                    // Default to sales rep
                    $roleLabel = ' (Rep)';
                }
            }
            
            $data[] = [
                'id' => $exec['id'],
                'code' => $exec['code'] ?? '',
                'full_name' => $exec['full_name'] . $roleLabel,
                'mobile_number' => $exec['mobile_number'] ?? '',
                'is_active' => (int)($exec['is_active'] ?? 1),
                'role_type' => $roleType
            ];
        }

        // If no data found, throw error
        if (empty($data)) {
            throw new Exception('No active REPs found in the system');
        }

        $response = [
            'status' => 'success',
            'message' => 'REPs retrieved successfully',
            'data' => $data,
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data)
        ];

    } elseif ($action === 'get_rep_outstanding_report') {
        $repId = $_POST['rep_id'] ?? '';
        $fromDate = $_POST['from_date'] ?? '';
        $toDate = $_POST['to_date'] ?? '';
        $repRole = $_POST['rep_role'] ?? 'marketing_executive'; // Default to marketing executive

        // Validate required parameters
        if (empty($repId)) {
            throw new Exception('REP ID is required');
        }

        if (empty($fromDate) || empty($toDate)) {
            throw new Exception('Both from date and to date are required');
        }

        $db = new Database();

        // Build the query based on role type
        if ($repRole === 'sales_executive') {
            // Query for sales executives (if they have a separate table and field)
            $query = "SELECT 
                        si.invoice_no,
                        si.invoice_date,
                        si.customer_name,
                        si.grand_total as invoice_amount,
                        COALESCE(si.outstanding_settle_amount, 0) as paid_amount,
                        (si.grand_total - COALESCE(si.outstanding_settle_amount, 0)) as outstanding,
                        si.due_date,
                        DATEDIFF(si.due_date, CURDATE()) as days_until_due,
                        cm.mobile_number,
                        COALESCE(se.full_name, me.full_name) as rep_name,
                        COALESCE(se.code, me.code) as rep_code
                      FROM 
                        sales_invoice si
                      LEFT JOIN 
                        customer_master cm ON si.customer_id = cm.id
                      LEFT JOIN 
                        marketing_executive me ON si.marketing_executive_id = me.id
                      LEFT JOIN 
                        sales_executive se ON si.sales_executive_id = se.id
                      WHERE 
                        si.status = 'active' AND
                        si.grand_total > 0 AND
                        si.is_cancel = 0 AND
                        si.payment_type = 2 AND  -- Only show credit invoices
                        (si.sales_executive_id = " . (int)$repId . " OR si.marketing_executive_id = " . (int)$repId . ") AND
                        si.invoice_date BETWEEN '" . $db->escapeString($fromDate) . " 00:00:00' AND '" . $db->escapeString($toDate) . " 23:59:59' AND
                        (si.grand_total - COALESCE(si.outstanding_settle_amount, 0)) > 0  -- Only show invoices with outstanding amounts
                      ORDER BY 
                        si.invoice_date DESC, si.customer_name ASC";
        } else {
            // Default query for marketing executives
            $query = "SELECT 
                        si.invoice_no,
                        si.invoice_date,
                        si.customer_name,
                        si.grand_total as invoice_amount,
                        COALESCE(si.outstanding_settle_amount, 0) as paid_amount,
                        (si.grand_total - COALESCE(si.outstanding_settle_amount, 0)) as outstanding,
                        si.due_date,
                        DATEDIFF(si.due_date, CURDATE()) as days_until_due,
                        cm.mobile_number,
                        me.full_name as rep_name,
                        me.code as rep_code
                      FROM 
                        sales_invoice si
                      LEFT JOIN 
                        customer_master cm ON si.customer_id = cm.id
                      LEFT JOIN 
                        marketing_executive me ON si.marketing_executive_id = me.id
                      WHERE 
                        si.status = 'active' AND
                        si.grand_total > 0 AND
                        si.is_cancel = 0 AND
                        si.payment_type = 2 AND  -- Only show credit invoices
                        si.marketing_executive_id = " . (int)$repId . " AND
                        si.invoice_date BETWEEN '" . $db->escapeString($fromDate) . " 00:00:00' AND '" . $db->escapeString($toDate) . " 23:59:59' AND
                        (si.grand_total - COALESCE(si.outstanding_settle_amount, 0)) > 0  -- Only show invoices with outstanding amounts
                      ORDER BY 
                        si.invoice_date DESC, si.customer_name ASC";
        }

        $result = $db->readQuery($query);
        if (!$result) {
            throw new Exception('Error executing query: ' . mysqli_error($db->DB_CON));
        }

        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Only include records with outstanding amounts > 0
            $outstanding = (float)$row['outstanding'];
            if ($outstanding > 0) {
                $data[] = [
                    'invoice_no' => $row['invoice_no'],
                    'invoice_date' => $row['invoice_date'],
                    'customer_name' => $row['customer_name'],
                    'mobile_number' => $row['mobile_number'],
                    'invoice_amount' => (float)$row['invoice_amount'],
                    'paid_amount' => (float)$row['paid_amount'],
                    'outstanding' => $outstanding,
                    'due_date' => $row['due_date'],
                    'days_until_due' => (int)$row['days_until_due'],
                    'rep_name' => $row['rep_name'],
                    'rep_code' => $row['rep_code']
                ];
            }
        }

        $response = [
            'status' => 'success',
            'message' => 'REP outstanding data retrieved successfully',
            'data' => $data
        ];

    } elseif ($action === 'get_rep_summary') {
        // Get summary data for a specific REP
        $repId = $_POST['rep_id'] ?? '';
        $fromDate = $_POST['from_date'] ?? '';
        $toDate = $_POST['to_date'] ?? '';

        if (empty($repId) || empty($fromDate) || empty($toDate)) {
            throw new Exception('REP ID and date range are required');
        }

        $db = new Database();

        // Get REP details
        $repQuery = "SELECT 
                        id,
                        code,
                        full_name,
                        mobile_number
                     FROM 
                        marketing_executive 
                     WHERE 
                        id = " . (int)$repId;

        $repResult = $db->readQuery($repQuery);
        $repData = mysqli_fetch_assoc($repResult);

        if (!$repData) {
            throw new Exception('REP not found');
        }

        // Get summary statistics
        $summaryQuery = "SELECT 
                            COUNT(DISTINCT si.id) as total_invoices,
                            COUNT(DISTINCT si.customer_id) as total_customers,
                            SUM(si.grand_total) as total_invoice_amount,
                            SUM(COALESCE(si.outstanding_settle_amount, 0)) as total_paid_amount,
                            SUM(si.grand_total - COALESCE(si.outstanding_settle_amount, 0)) as total_outstanding
                         FROM 
                            sales_invoice si
                         WHERE 
                            si.status = 'active' AND
                            si.grand_total > 0 AND
                            si.is_cancel = 0 AND
                            si.payment_type = 2 AND
                            si.marketing_executive_id = " . (int)$repId . " AND
                            si.invoice_date BETWEEN '" . $db->escapeString($fromDate) . " 00:00:00' AND '" . $db->escapeString($toDate) . " 23:59:59' AND
                            (si.grand_total - COALESCE(si.outstanding_settle_amount, 0)) > 0";

        $summaryResult = $db->readQuery($summaryQuery);
        $summaryData = mysqli_fetch_assoc($summaryResult);

        $response = [
            'status' => 'success',
            'message' => 'REP summary retrieved successfully',
            'data' => [
                'rep_details' => $repData,
                'summary' => [
                    'total_invoices' => (int)($summaryData['total_invoices'] ?? 0),
                    'total_customers' => (int)($summaryData['total_customers'] ?? 0),
                    'total_invoice_amount' => (float)($summaryData['total_invoice_amount'] ?? 0),
                    'total_paid_amount' => (float)($summaryData['total_paid_amount'] ?? 0),
                    'total_outstanding' => (float)($summaryData['total_outstanding'] ?? 0)
                ]
            ]
        ];

    } else {
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'data' => []
    ];
}

// Output the JSON response
echo json_encode($response);
?>
