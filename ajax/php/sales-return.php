<?php

include '../../class/include.php';

header('Content-Type: application/json; charset=UTF8');

// Check invoice exists and get invoice data
if (isset($_POST['action']) && $_POST['action'] == 'check_invoice_exists') {
    $invoice_no = trim($_POST['invoice_no']);
    $SALES_INVOICE = new SalesInvoice(NULL);
    $invoice = $SALES_INVOICE->getInvoiceByNo($invoice_no);

    if ($invoice) {
        $CUSTOMER = new CustomerMaster($invoice['customer_id']);
        $DEPARTMENT = new DepartmentMaster($invoice['department_id']);

        echo json_encode([
            'exists' => true,
            'invoice' => $invoice,
            'customer' => [
                'id' => $CUSTOMER->id,
                'name' => $CUSTOMER->name,
                'address' => $CUSTOMER->address,
                'mobile_number' => $CUSTOMER->mobile_number
            ],
            'department' => [
                'id' => $DEPARTMENT->id,
                'name' => $DEPARTMENT->name
            ]
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    exit();
}

// Fetch invoice items
if (isset($_POST['action']) && $_POST['action'] == 'fetch_invoice_items') {
    $invoice_id = $_POST['invoice_id'];

    $SALES_INVOICE_ITEM = new SalesInvoiceItem(NULL);
    $items = $SALES_INVOICE_ITEM->getByInvoiceId($invoice_id);

    $result = [];
    foreach ($items as $item) {
        $ITEM_MASTER = new ItemMaster($item['item_id']);
        $result[] = [
            'id' => $item['id'],
            'item_id' => $item['item_id'],
            'item_code' => $item['item_code'],
            'item_name' => $item['item_name'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['price'],
            'customer_price' => $item['customer_price'],
            'dealer_price' => $item['dealer_price'],
            'discount' => $item['discount'],
            'total' => $item['total']
        ];
    }

    echo json_encode(['items' => $result]);
    exit();
}

// Save sales return
if (isset($_POST['action']) && $_POST['action'] == 'save_sales_return') {
    $return_data = json_decode($_POST['return_data'], true);
    $return_items = json_decode($_POST['return_items'], true);

    // Create sales return
    $SALES_RETURN = new SalesReturn();
    $SALES_RETURN->return_no = $return_data['return_no'];
    $SALES_RETURN->return_date = $return_data['return_date'];
    $SALES_RETURN->invoice_no = $return_data['invoice_no'];
    $SALES_RETURN->customer_id = $return_data['customer_id'];
    $SALES_RETURN->total_amount = $return_data['total_amount'];
    $SALES_RETURN->return_reason = $return_data['return_reason'];
    $SALES_RETURN->remarks = $return_data['remarks'];
    $SALES_RETURN->created_by = $_SESSION['user']['id'] ?? 1;

    $return_id = $SALES_RETURN->create();

    if ($return_id) {
        // Save return items
        foreach ($return_items as $item) {
            $RETURN_ITEM = new SalesReturnItem();
            $RETURN_ITEM->return_id = $return_id;
            $RETURN_ITEM->item_id = $item['item_id'];
            $RETURN_ITEM->quantity = $item['quantity'];
            $RETURN_ITEM->unit_price = $item['unit_price'];
            $RETURN_ITEM->discount = $item['discount'];
            $RETURN_ITEM->tax = $item['tax'];
            $RETURN_ITEM->net_amount = $item['net_amount'];
            $RETURN_ITEM->remarks = $item['remarks'] ?? '';
            $RETURN_ITEM->create();
        }

        echo json_encode(['status' => 'success', 'return_id' => $return_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create sales return']);
    }
    exit();
}

// Get sales returns
if (isset($_POST['action']) && $_POST['action'] == 'get_sales_returns') {
    $SALES_RETURN = new SalesReturn();
    $returns = $SALES_RETURN->all();

    $result = [];
    foreach ($returns as $return) {
        $CUSTOMER = new CustomerMaster($return['customer_id']);
        $result[] = [
            'id' => $return['id'],
            'return_no' => $return['return_no'],
            'return_date' => $return['return_date'],
            'invoice_no' => $return['invoice_no'],
            'customer_name' => $CUSTOMER->name,
            'total_amount' => $return['total_amount'],
            'return_reason' => $return['return_reason']
        ];
    }

    echo json_encode(['returns' => $result]);
    exit();
}

// Get sales return details
if (isset($_POST['action']) && $_POST['action'] == 'get_sales_return_details') {
    $return_id = $_POST['return_id'];

    $SALES_RETURN = new SalesReturn($return_id);
    $SALES_RETURN_ITEM = new SalesReturnItem();
    $items = $SALES_RETURN_ITEM->getByReturnId($return_id);

    $CUSTOMER = new CustomerMaster($SALES_RETURN->customer_id);

    $return_data = [
        'id' => $SALES_RETURN->id,
        'return_no' => $SALES_RETURN->return_no,
        'return_date' => $SALES_RETURN->return_date,
        'invoice_no' => $SALES_RETURN->invoice_no,
        'customer' => [
            'id' => $CUSTOMER->id,
            'name' => $CUSTOMER->name,
            'address' => $CUSTOMER->address,
            'mobile_number' => $CUSTOMER->mobile_number
        ],
        'total_amount' => $SALES_RETURN->total_amount,
        'return_reason' => $SALES_RETURN->return_reason,
        'remarks' => $SALES_RETURN->remarks
    ];

    $item_data = [];
    foreach ($items as $item) {
        $ITEM_MASTER = new ItemMaster($item['item_id']);
        $item_data[] = [
            'id' => $item['id'],
            'item_id' => $item['item_id'],
            'item_code' => $ITEM_MASTER->code,
            'item_name' => $ITEM_MASTER->name,
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'discount' => $item['discount'],
            'tax' => $item['tax'],
            'net_amount' => $item['net_amount'],
            'remarks' => $item['remarks']
        ];
    }

    echo json_encode([
        'return' => $return_data,
        'items' => $item_data
    ]);
    exit();
}

// Delete sales return
if (isset($_POST['action']) && $_POST['action'] == 'delete_sales_return') {
    $return_id = $_POST['return_id'];

    $SALES_RETURN_ITEM = new SalesReturnItem();
    $SALES_RETURN_ITEM->deleteByReturnId($return_id);

    $SALES_RETURN = new SalesReturn($return_id);
    $result = $SALES_RETURN->delete();

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete sales return']);
    }
    exit();
}

?>
