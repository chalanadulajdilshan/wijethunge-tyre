<?php
header('Content-Type: application/json');
require_once('../../class/Database.php');

$response = ['status' => 'error', 'message' => 'Invalid request', 'data' => []];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $action = $_POST['action'] ?? '';
    $db = new Database();

    // -----------------------------------------------------------------
    // 1. MAIN REPORT: Grouped by Customer
    // -----------------------------------------------------------------
    if ($action === 'get_outstanding_report') {
        $paymentType = $_POST['payment_type'] ?? 'all';
        $fromDate    = $_POST['from_date'] ?? '';
        $toDate      = $_POST['to_date'] ?? '';

        // Validate date range
        if ((!empty($fromDate) && empty($toDate)) || (empty($fromDate) && !empty($toDate))) {
            throw new Exception('Both From and To dates are required when filtering.');
        }

        $sql = "SELECT
                    si.customer_id,
                    cm.code AS customer_code,
                    cm.name AS customer_name,
                    cm.mobile_number,
                    COUNT(si.id) AS total_invoices,
                    COALESCE(SUM(si.grand_total), 0) AS invoice_amount,
                    COALESCE(SUM(si.outstanding_settle_amount), 0) AS paid_amount,
                    COALESCE(SUM(si.grand_total - si.outstanding_settle_amount), 0) AS outstanding
                FROM sales_invoice si
                LEFT JOIN customer_master cm ON si.customer_id = cm.id
                WHERE si.status = 'active'
                  AND si.is_cancel = 0
                  AND si.grand_total > 0";

        // Payment Type Filter
        if ($paymentType === 'cash') {
            $sql .= " AND si.payment_type = 1";
        } elseif ($paymentType === 'credit') {
            $sql .= " AND si.payment_type = 2";
        }

        // Date Range Filter
        if (!empty($fromDate) && !empty($toDate)) {
            $from = $db->escapeString($fromDate) . ' 00:00:00';
            $to   = $db->escapeString($toDate) . ' 23:59:59';
            $sql .= " AND si.invoice_date BETWEEN '$from' AND '$to'";
        }

        // Group & Filter — exclude customers whose outstanding < 1
        $sql .= " GROUP BY si.customer_id
                  HAVING outstanding >= 1
                  ORDER BY customer_name ASC";

        $res = $db->readQuery($sql);
        if (!$res) throw new Exception(mysqli_error($db->DB_CON));

        $data = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = [
                'customer_id'     => (int)$row['customer_id'],
                'customer_code'   => $row['customer_code'] ?? '',
                'customer_name'   => $row['customer_name'] ?? '',
                'mobile_number'   => $row['mobile_number'] ?? '',
                'total_invoices'  => (int)$row['total_invoices'],
                'invoice_amount'  => (float)$row['invoice_amount'],
                'paid_amount'     => (float)$row['paid_amount'],
                'outstanding'     => (float)$row['outstanding']
            ];
        }

        $response = ['status' => 'success', 'data' => $data];
    }

    // -----------------------------------------------------------------
    // 2. DETAILED INVOICES FOR ONE CUSTOMER
    // -----------------------------------------------------------------
    elseif ($action === 'get_customer_invoices') {
        $custId      = (int)($_POST['customer_id'] ?? 0);
        $paymentType = $_POST['payment_type'] ?? 'all';
        $fromDate    = $_POST['from_date'] ?? '';
        $toDate      = $_POST['to_date'] ?? '';

        if ($custId <= 0) throw new Exception('Invalid customer ID');

        $sql = "SELECT
                    si.invoice_no,
                    DATE_FORMAT(si.invoice_date, '%Y-%m-%d') AS invoice_date,
                    DATE_FORMAT(si.due_date, '%Y-%m-%d') AS due_date,
                    si.grand_total AS invoice_amount,
                    COALESCE(si.outstanding_settle_amount, 0) AS paid_amount,
                    (si.grand_total - COALESCE(si.outstanding_settle_amount, 0)) AS outstanding,
                    DATEDIFF(si.due_date, CURDATE()) AS days_until_due
                FROM sales_invoice si
                WHERE si.customer_id = ?
                  AND si.status = 'active'
                  AND si.is_cancel = 0
                  AND si.grand_total > 0";

        $params = [$custId];
        $types = 'i';

        // Payment Type
        if ($paymentType === 'cash') {
            $sql .= " AND si.payment_type = 1";
        } elseif ($paymentType === 'credit') {
            $sql .= " AND si.payment_type = 2";
        }

        // Date Range
        if (!empty($fromDate) && !empty($toDate)) {
            $sql .= " AND si.invoice_date BETWEEN ? AND ?";
            $params[] = $db->escapeString($fromDate) . ' 00:00:00';
            $params[] = $db->escapeString($toDate) . ' 23:59:59';
            $types .= 'ss';
        }

        // Hide invoices with outstanding < 1
        $sql .= " AND (si.grand_total - COALESCE(si.outstanding_settle_amount, 0)) >= 1";

        $sql .= " ORDER BY si.invoice_date DESC";

        $stmt = $db->DB_CON->prepare($sql);
        if (!$stmt) throw new Exception('Prepare failed: ' . $db->DB_CON->error);

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        $data = [];
        while ($row = $res->fetch_assoc()) {
            $data[] = [
                'invoice_no'      => $row['invoice_no'],
                'invoice_date'    => $row['invoice_date'],
                'due_date'        => $row['due_date'],
                'invoice_amount'  => (float)$row['invoice_amount'],
                'paid_amount'     => (float)$row['paid_amount'],
                'outstanding'     => (float)$row['outstanding'],
                'days_until_due'  => (int)$row['days_until_due']
            ];
        }

        $response = ['status' => 'success', 'data' => $data];
    }

    else {
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response);
?>
