<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');


if (isset($_POST['action']) && $_POST['action'] == 'check_invoice_id') {


    $invoice_no = trim($_POST['invoice_no']);
    $SALES_INVOICE = new SalesInvoice(NULL);
    $res = $SALES_INVOICE->checkInvoiceIdExist($invoice_no);

    // Send JSON response
    echo json_encode(['exists' => $res]);
    exit();
}

// Handle sales rep orders fetch
if (isset($_POST['action']) && $_POST['action'] == 'fetch_sales_orders') {
    $department_id = isset($_POST['department_id']) ? $_POST['department_id'] : null;
    $invoice_type = isset($_POST['invoice_type']) ? $_POST['invoice_type'] : 'customer';
    $current_item_codes = isset($_POST['current_item_codes']) ? json_decode($_POST['current_item_codes'], true) : [];

    $SALES_ORDER = new SalesOrder(NULL);
    $sales_orders = $SALES_ORDER->all();

    // Debug: Check what we got from the database
    error_log("Total sales orders found: " . count($sales_orders));
    if (!empty($sales_orders)) {
        error_log("First sales order structure: " . json_encode($sales_orders[0]));
    }

    $result = [];

    foreach ($sales_orders as $order) {
        // Only filter by department if department_id is provided
        if ($department_id && $order['department_id'] != $department_id) {
            continue;
        }
        $SALES_ORDER_ITEM = new SalesOrderItem(NULL);
        $order_items = $SALES_ORDER_ITEM->getByOrderId($order['id']);

        // If current_item_codes are provided, only include orders that have matching items
        if (!empty($current_item_codes)) {
            $hasMatchingItems = false;
            foreach ($order_items as $item) {
                $ITEM_MASTER = new ItemMaster($item['item_id']);
                if (in_array($ITEM_MASTER->code, $current_item_codes)) {
                    $hasMatchingItems = true;
                    break;
                }
            }
            // Skip this order if it doesn't have matching items
            if (!$hasMatchingItems) {
                continue;
            }
        }

        $items = [];
        foreach ($order_items as $item) {
            $ITEM_MASTER = new ItemMaster($item['item_id']);
            $STOCK_MASTER = new StockMaster(NULL);
            $stock_qty = $STOCK_MASTER->getTotalAvailableQuantityByDepartment($item['item_id'], $department_id ?: $order['department_id']);

            $price = $invoice_type === 'customer' ? $ITEM_MASTER->customer_price : $ITEM_MASTER->dealer_price;

            $items[] = [
                'item_id' => $item['item_id'],
                'item_code' => $ITEM_MASTER->code,
                'item_name' => $ITEM_MASTER->name,
                'order_qty' => $item['qty'],
                'stock_qty' => $stock_qty,
                'customer_price' => $ITEM_MASTER->customer_price,
                'dealer_price' => $ITEM_MASTER->dealer_price
            ];
        }

        // Get marketing executive info - handle both old and new column names
        $marketing_executive_id = null;
        $marketing_executive_name = '';
        
        // Check if we have sales_executive_id (new) or rep_id (old)
        $sales_exec_id = null;
        if (isset($order['sales_executive_id']) && $order['sales_executive_id']) {
            $sales_exec_id = $order['sales_executive_id'];
            error_log("Using sales_executive_id: " . $sales_exec_id);
        } elseif (isset($order['rep_id']) && $order['rep_id']) {
            // If still using old column, get marketing executive from user
            $USER = new User($order['rep_id']);
            if ($USER->sales_executive_id) {
                $sales_exec_id = $USER->sales_executive_id;
                error_log("Using rep_id -> sales_executive_id: " . $sales_exec_id);
            }
        }
        
        if ($sales_exec_id) {
            $marketing_executive_id = $sales_exec_id;
            $MARKETING_EXECUTIVE = new MarketingExecutive($sales_exec_id);
            $marketing_executive_name = $MARKETING_EXECUTIVE->full_name ?? '';
            error_log("Marketing Executive found: ID={$marketing_executive_id}, Name={$marketing_executive_name}");
        } else {
            error_log("No marketing executive found for order ID: " . $order['id']);
        }

        // Get customer information
        $customer_name = '';
        $customer_mobile = '';
        $customer_address = '';
        $customer_code = '';
        if ($order['customer_id']) {
            $CUSTOMER = new CustomerMaster($order['customer_id']);
            $customer_name = $CUSTOMER->name ?? '';
            $customer_mobile = $CUSTOMER->mobile_number ?? '';
            $customer_address = $CUSTOMER->address ?? '';
            $customer_code = $CUSTOMER->code ?? '';
            
            error_log("Customer found: ID={$order['customer_id']}, Code={$customer_code}, Name={$customer_name}, Mobile={$customer_mobile}");
        } else {
            error_log("No customer ID for order: " . $order['id']);
        }

        // Get status text
        $status_text = '';
        switch ($order['status']) {
            case 0:
                $status_text = 'Pending';
                break;
            case 1:
                $status_text = 'Invoiced';
                break;
            case 2:
                $status_text = 'Cancelled';
                break;
            default:
                $status_text = 'Unknown';
        }

        $result[] = [
            'order_id' => $order['sales_order_id'],
            'order_db_id' => $order['id'], // Add database ID for status updates
            'customer_id' => $order['customer_id'],
            'customer_code' => $customer_code,
            'customer_name' => $customer_name,
            'customer_mobile' => $customer_mobile,
            'customer_address' => $customer_address,
            'marketing_executive_id' => $marketing_executive_id,
            'marketing_executive_name' => $marketing_executive_name,
            'order_date' => $order['order_date'],
            'status' => $order['status'],
            'status_text' => $status_text,
            'items' => $items
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $result]);
    
    // Debug: Log the final result structure for the first order (if any)
    if (!empty($result)) {
        error_log("Sample order data structure: " . json_encode($result[0]));
    }
    
    exit();
}

// Update sales order status
if (isset($_POST['action']) && $_POST['action'] == 'update_sales_order_status') {
    $orderId = $_POST['order_id'];
    $status = $_POST['status'];

    $SALES_ORDER = new SalesOrder($orderId);
    $SALES_ORDER->status = $status;
    $result = $SALES_ORDER->update();

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update sales order status']);
    }
    exit();
}

// Create a new invoice
if (isset($_POST['create'])) {

    $invoiceId = $_POST['invoice_no'];
    $items = json_decode($_POST['items'], true); // array of items 

    $paymentType = $_POST['payment_type'];



    $totalSubTotal = 0;
    $totalDiscount = 0;
    $final_cost = 0;

    $customer_prices = isset($_POST['customer_prices']) ? $_POST['customer_prices'] : [];
    $dealer_prices = isset($_POST['dealer_prices']) ? $_POST['dealer_prices'] : [];
    $sales_order_ids = isset($_POST['sales_order_ids']) ? $_POST['sales_order_ids'] : [];
    
    // Debug logging
    error_log("Sales Order IDs received: " . print_r($sales_order_ids, true));

    // Calculate subtotal and discount
    foreach ($items as $key => $item) {
        $price = floatval($item['price'] ?? 0);
        $qty = floatval($item['qty'] ?? 0);

        $customer_price = isset($customer_prices[$key]) ? floatval($customer_prices[$key]) : $price;
        $dealer_price = isset($dealer_prices[$key]) ? floatval($dealer_prices[$key]) : $price;

        $discount_percentage = floatval($item['discount'] ?? 0);

        //GET ARN ID BY ARN NO
        $ARN_MASTER = new ArnMaster(NULL);
        $arn_no = $item['arn_no'] ?? '';
        $arn_id = $ARN_MASTER->getArnIdByArnNo($arn_no);

        $item_id = $item['item_id'] ?? '';
        $code = $item['code'] ?? '';

        if (!empty($item_id)) {
            $ITEM_MASTER = new ItemMaster($item_id);
        }

        if (substr($code, 0, 2) !== 'SI') {
            if (!empty($arn_id)) {
                $ARN_ITEM = new ArnItem(NULL);
                $cost = $ARN_ITEM->getArnCostByArnId($arn_id);
                $final_cost_item = $cost * $qty;
                $final_cost += $final_cost_item;
            }
        } else {
            if (!empty($item_id)) {
                $SERVICE_ITEM = new ServiceItem($item_id);
                $cost = $SERVICE_ITEM->cost;
                $service_qty = floatval($item['service_qty'] ?? 0);
                $final_cost_item = $cost * $service_qty;
                $final_cost += $final_cost_item;

                $available_qty = $SERVICE_ITEM->qty - $service_qty;
                $SERVICE_ITEM->qty = $available_qty;
                $SERVICE_ITEM->update();
            }
        }

        // Use correct quantity based on item type
        $qty_for_calculation = (substr($code, 0, 2) === 'SI') ? floatval($item['service_qty'] ?? 0) : $qty;
        $itemTotal = $price * $qty_for_calculation;
        $discount_amount = $itemTotal * $discount_percentage / 100;
        $totalSubTotal += $itemTotal;
        $totalDiscount += $discount_amount;
    }
    $netTotal = $totalSubTotal - $totalDiscount;

    $USER = new User($_SESSION['id']);
    $COMPANY_PROFILE = new CompanyProfile($USER->company_id);




    // VAT calculation - only if company has VAT enabled
    $tax = 0;
    if ($COMPANY_PROFILE->is_vat == 1) {
        $tax = round(($netTotal * $COMPANY_PROFILE->vat_percentage) / 100, 2);
    }

    // Grand total = net total + VAT
    $grandTotal = $netTotal + $tax;

    // Create invoice
    $SALES_INVOICE = new SalesInvoice(NULL);
    $CUSTOMER_MASTER = new CustomerMaster(NULL);

    $SALES_INVOICE->invoice_no = $invoiceId;
    $SALES_INVOICE->invoice_type = isset($_POST['invoice_type']) ? $_POST['invoice_type'] : 'INV';
    $SALES_INVOICE->invoice_date = $_POST['invoice_date'];
    $SALES_INVOICE->company_id = $_POST['company_id'];
    $SALES_INVOICE->customer_id = $_POST['customer_id'];
    $SALES_INVOICE->customer_name = ucwords(strtolower(trim($_POST['customer_name'])));
    $SALES_INVOICE->customer_mobile = $_POST['customer_mobile'];
    $SALES_INVOICE->customer_address = ucwords(strtolower(trim($_POST['customer_address'])));
    $SALES_INVOICE->recommended_person = isset($_POST['recommended_person']) ? ucwords(strtolower(trim($_POST['recommended_person']))) : null;
    $SALES_INVOICE->department_id = $_POST['department_id'];
    $SALES_INVOICE->sale_type = $_POST['sales_type'];
    $SALES_INVOICE->final_cost = $final_cost;
    $SALES_INVOICE->marketing_executive_id = $_POST['marketing_executive'];

    $SALES_INVOICE->payment_type = $paymentType;
    $SALES_INVOICE->sub_total = $totalSubTotal;
    $SALES_INVOICE->discount = $totalDiscount;
    $SALES_INVOICE->tax = $tax;
    $SALES_INVOICE->grand_total = $grandTotal;
    $SALES_INVOICE->outstanding_settle_amount = $_POST['paidAmount'];
    $SALES_INVOICE->remark = !empty($_POST['remark']) ? $_POST['remark'] : null;

    if ($paymentType == 2 && !empty($_POST['credit_period'])) {
        $SALES_INVOICE->credit_period = $_POST['credit_period'];
        $CREDIT_PERIOD_OBJ = new CreditPeriod($_POST['credit_period']);
        if (isset($CREDIT_PERIOD_OBJ->days)) {
            $days = $CREDIT_PERIOD_OBJ->days;
            $due_date = date('Y-m-d', strtotime($_POST['invoice_date'] . ' + ' . $days . ' days'));
            $SALES_INVOICE->due_date = $due_date;
        } else {
            // Handle error: invalid credit period id
            echo json_encode(["status" => 'error', "message" => "Invalid credit period selected."]);
            exit();
        }
    }

    $invoiceResult = $SALES_INVOICE->create();

    if ($paymentType == 2) {
        $CUSTOMER_MASTER->updateCustomerOutstanding($_POST['customer_id'], $grandTotal, true);
    }

    $DOCUMENT_TRACKING = new DocumentTracking(null);

    if ($paymentType == 1) {
        $DOCUMENT_TRACKING->incrementDocumentId('cash');
    } else if ($paymentType == 2) {
        $DOCUMENT_TRACKING->incrementDocumentId('credit');
    } else {

        $DOCUMENT_TRACKING->incrementDocumentId('invoice');
    }


    if ($invoiceResult) {
        $invoiceTableId = $invoiceResult;

        foreach ($items as $item) {

            $item_discount_percentage = isset($item['discount']) ? $item['discount'] : 0;

            $item_id = $item['item_id'] ?? '';
            $code = $item['code'] ?? '';
            $arn_no = $item['arn_no'] ?? '';

            if (!empty($item_id)) {
                $ITEM_MASTER = new ItemMaster($item_id);

                //GET ARN ID BY ARN NO FIRST
                $ARN_MASTER = new ArnMaster(NULL);
                $arn_id = $ARN_MASTER->getArnIdByArnNo($arn_no);
                
                // Get the correct department_id for this ARN before saving item
                $db = new Database();
                $deptQuery = "SELECT department_id FROM stock_item_tmp WHERE arn_id = '{$arn_id}' AND item_id = '{$item_id}' LIMIT 1";
                $deptResult = $db->readQuery($deptQuery);
                $correctDepartmentId = $_POST['department_id']; // fallback to form department
                
                if ($deptRow = mysqli_fetch_assoc($deptResult)) {
                    $correctDepartmentId = $deptRow['department_id'];
                }

                $invoice_type = $SALES_INVOICE->invoice_type;

                $SALES_ITEM = new SalesInvoiceItem(NULL);

                $SALES_ITEM->invoice_id = $invoiceTableId;

                // Check if this is service id = 1 (skip saving main service, only save service items)
                if (substr($code, 0, 2) === 'SV' && $item_id == '1') {
                    // Service id = 1: Skip this record, only service items will be saved
                    continue;
                } elseif (substr($code, 0, 2) === 'SI') {
                    // Service item - use service_qty for all calculations
                    $SALES_ITEM->service_item_code = $item_id;
                    $SALES_ITEM->quantity = $item['service_qty'] ?? 0; // Use actual service_qty
                    $qty_for_total = $item['service_qty'] ?? 0; // Use service_qty for price calculations
                    $qty_for_stock = $item['service_qty'] ?? 0; // Use service_qty for stock management
                } elseif (substr($code, 0, 2) === 'SV') {
                    // Regular service (not id=1) - use actual quantity entered
                    $SALES_ITEM->item_code = $item_id;
                    $SALES_ITEM->quantity = $item['qty'] ?? 0;
                    $qty_for_total = $item['qty'] ?? 0;
                    $qty_for_stock = 0; // Services don't affect stock
                } else {
                    // Regular item
                    $SALES_ITEM->item_code = $item_id;
                    $SALES_ITEM->quantity = $item['qty'] ?? 0;
                    $qty_for_total = $item['qty'] ?? 0;
                    $qty_for_stock = $item['qty'] ?? 0; // Use regular qty for stock management
                }

                $price = floatval($item['price'] ?? 0);
                $selling_price = floatval($item['selling_price'] ?? $price);
                $item_discount_amount = ($price * $qty_for_total) * $item_discount_percentage / 100;

                $SALES_ITEM->item_name = ($item['name'] ?? '') . '|ARN:' . $arn_id . '|DEPT:' . $correctDepartmentId;
                $SALES_ITEM->price = $invoice_type === 'customer' ? ($item['customer_price'] ?? $price) : ($item['dealer_price'] ?? $price);
                $SALES_ITEM->customer_price = $item['customer_price'] ?? $price; // Save the customer price
                $SALES_ITEM->dealer_price = $item['dealer_price'] ?? $price; // Save the dealer price
                $SALES_ITEM->cost = $item['cost'] ?? 0; // Set the cost field
                $SALES_ITEM->discount = $item_discount_amount;
                $SALES_ITEM->total = ($selling_price * $qty_for_total);
                $SALES_ITEM->vehicle_no = isset($item['vehicle_no']) ? $item['vehicle_no'] : '';
                $SALES_ITEM->current_km = isset($item['current_km']) ? $item['current_km'] : '';
                $SALES_ITEM->next_service_date = (isset($item['next_service_days']) && !empty($item['next_service_days']) && intval($item['next_service_days']) > 0) ? date('Y-m-d', strtotime($SALES_INVOICE->invoice_date . ' + ' . $item['next_service_days'] . ' days')) : null;
                $SALES_ITEM->created_at = date("Y-m-d H:i:s");
                $SALES_ITEM->sales_order_id = isset($sales_order_ids[$key]) ? $sales_order_ids[$key] : null;
                
                // Debug logging
                error_log("Setting sales_order_id for item $key: " . ($SALES_ITEM->sales_order_id ?? 'NULL'));
                
                $SALES_ITEM->create();

                //stock master update quantity
                $STOCK_MASTER = new StockMaster(NULL);
                $currentQty = $STOCK_MASTER->getAvailableQuantity($_POST['department_id'], $item_id);
                $newQty = $currentQty - $qty_for_stock; // Use the correct quantity for stock management
                $STOCK_MASTER->quantity = $newQty;
                $STOCK_MASTER->updateQtyByItemAndDepartment($_POST['department_id'], $item_id, $newQty);

                // Update stock transaction with ARN reference if available
                $STOCK_TRANSACTION = new StockTransaction(NULL);
                $STOCK_TRANSACTION->item_id = $item_id;

                // Update stock_item_tmp for ARN-based inventory
                $STOCK_ITEM_TMP = new StockItemTmp(NULL);
                // Use negative qty to reduce stock
                $qtyToDeduct = -abs($qty_for_stock); // Use correct quantity for stock deduction
                
                if (!empty($arn_id)) {
                    $STOCK_ITEM_TMP->updateQtyByArnId(
                        $arn_id,
                        $item_id,
                        $correctDepartmentId, // Use the correct department for this ARN
                        $qtyToDeduct
                    );
                }

                //stock transaction table update
                $STOCK_TRANSACTION->type = 4; // get this id from stock adjustment type table PK
                $STOCK_TRANSACTION->date = date("Y-m-d");
                $STOCK_TRANSACTION->qty_in = 0;
                $STOCK_TRANSACTION->qty_out = $qty_for_stock; // Use correct quantity for transaction record
                $STOCK_TRANSACTION->remark = "INVOICE #$invoiceId " . (!empty($arn_id) ? "(ARN: {$arn_id}) " : "") . "Issued " . date("Y-m-d H:i:s");
                $STOCK_TRANSACTION->created_at = date("Y-m-d H:i:s");
                $STOCK_TRANSACTION->create();

                if ($paymentType == 1) {
                    $payments = json_decode($_POST['payments'], true); // decode JSON → array

                    if (is_array($payments)) {
                        foreach ($payments as $payment) {
                            $INVOICE_PAYMENT = new InvoicePayment(NULL);
                            $INVOICE_PAYMENT->invoice_id  = $invoiceTableId;
                            $INVOICE_PAYMENT->method_id   = $payment['method_id'];
                            $INVOICE_PAYMENT->amount      = $payment['amount'];
                            $INVOICE_PAYMENT->reference_no = $payment['reference_no'] ?? null;
                            $INVOICE_PAYMENT->bank_name    = $payment['bank_name'] ?? null;
                            $INVOICE_PAYMENT->cheque_date  = $payment['cheque_date'] ?? null;

                            $res = $INVOICE_PAYMENT->create();
                        }
                    }
                }
                //audit log 
                $AUDIT_LOG = new AuditLog(NULL);
                $AUDIT_LOG->ref_id = $invoiceTableId;
                $AUDIT_LOG->ref_code = $_POST['invoice_no'];
                $AUDIT_LOG->action = 'CREATE';
                $AUDIT_LOG->description = 'CREATE INVOICE NO #' . $invoiceTableId;
                $AUDIT_LOG->user_id = $_SESSION['id'];
                $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
                $AUDIT_LOG->create();
            }
        }

        // Update sales order status to 1 (invoiced) for any sales orders associated with this invoice
        $salesOrderIds = [];
        foreach ($items as $item) {
            if (!empty($item['sales_order_id'])) {
                $salesOrderIds[] = $item['sales_order_id'];
            }
        }
        
        // Debug logging
        error_log("Found sales order IDs for invoicing: " . print_r($salesOrderIds, true));
        
        // Remove duplicates and update sales order statuses to 1 (invoiced)
        $salesOrderIds = array_unique($salesOrderIds);
        foreach ($salesOrderIds as $salesOrderId) {
            error_log("Updating sales order $salesOrderId to status 1 (invoiced)");
            $SALES_ORDER = new SalesOrder($salesOrderId);
            $result = $SALES_ORDER->markAsInvoiced();
            error_log("Sales order $salesOrderId update result: " . ($result ? 'SUCCESS' : 'FAILED'));
        }

        echo json_encode([
            "status" => 'success',
            "invoice_id" => $invoiceTableId,
            "sub_total" => $totalSubTotal,
            "discount" => $totalDiscount,
            "vat" => $tax,
            "grand_total" => $grandTotal
        ]);
        exit();
    } else {
        echo json_encode(["status" => 'error']);
        exit();
    }
}


// Update invoice details
if (isset($_POST['update'])) {
    $invoiceId = $_POST['invoice_id']; // Retrieve invoice ID

    // Create SalesInvoice object and load the data by ID
    $SALES_INVOICE = new SalesInvoice($invoiceId);

    // Update invoice details
    $SALES_INVOICE->invoice_date = $_POST['invoice_date']; // You can update the date or other details here
    $SALES_INVOICE->company_id = $_POST['company_id'];
    $SALES_INVOICE->customer_id = $_POST['customer_id'];
    $SALES_INVOICE->customer_name = ucwords(strtolower(trim($_POST['customer_name'])));
    $SALES_INVOICE->customer_mobile = $_POST['customer_mobile'];
    $SALES_INVOICE->customer_address = ucwords(strtolower(trim($_POST['customer_address'])));
    $SALES_INVOICE->recommended_person = isset($_POST['recommended_person']) ? ucwords(strtolower(trim($_POST['recommended_person']))) : null;
    $SALES_INVOICE->marketing_executive_id = $_POST['marketing_executive'];

    // Attempt to update the invoice
    $result = $SALES_INVOICE->update();

    if ($result) {
        $result = [
            "status" => 'success'
        ];
        echo json_encode($result);
        exit();
    } else {
        $result = [
            "status" => 'error'
        ];
        echo json_encode($result);
        exit();
    }
}

if (isset($_POST['filter'])) {

    $SALES_INVOICE = new SalesInvoice();
    $response = $SALES_INVOICE->fetchInvoicesForDataTable($_REQUEST);


    echo json_encode($response);
    exit;
}

if (isset($_POST['get_by_id'])) {

    $SALES_INVOICE = new SalesInvoice();
    $response = $SALES_INVOICE->getByID($_POST['id']);

    $CUSTOMER_MASTER = new CustomerMaster($response['customer_id']);
    $response['customer_code'] = $CUSTOMER_MASTER->code;
    $response['customer_name'] = $CUSTOMER_MASTER->name;
    $response['customer_address'] = $CUSTOMER_MASTER->address;
    $response['customer_mobile'] = $CUSTOMER_MASTER->mobile_number;
    $response['recommended_person'] = $response['recommended_person'] ?? null;
    $response['marketing_executive'] = $response['marketing_executive_id'] ?? null;

    echo json_encode($response);
    exit;
}



// Delete invoice
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $invoice = new SalesInvoice($_POST['id']);
    $result = $invoice->delete(); // Make sure this method exists in your class

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}



if (isset($_POST['action']) && $_POST['action'] == 'latest') {
    $SALES_INVOICE = new SalesInvoice();
    $invoices = $SALES_INVOICE->latest();

    echo json_encode(["data" => $invoices]);
    exit();
}


if (isset($_POST['action']) && $_POST['action'] == 'search') {
    $SALES_INVOICE = new SalesInvoice();
    $invoices = $SALES_INVOICE->search($_POST['q']);

    echo json_encode(["data" => $invoices]);
    exit();
}


// Handle cancel invoice action
// Check invoice status
if (isset($_POST['action']) && $_POST['action'] == 'check_status') {
    $invoiceId = $_POST['id'];
    $SALES_INVOICE = new SalesInvoice($invoiceId);
    echo json_encode(['is_cancelled' => ($SALES_INVOICE->is_cancel == 1)]);
    exit();
}

// Cancel invoice
if (isset($_POST['action']) && $_POST['action'] == 'cancel') {
    $invoiceId = $_POST['id'];
    $arnIds = isset($_POST['arnIds']) ? $_POST['arnIds'] : [];

    $SALES_INVOICE = new SalesInvoice($invoiceId);

    if ($SALES_INVOICE->is_cancel == 1) {
        echo json_encode(['status' => 'already_cancelled']);
        exit();
    }
    
    // Check if invoice has been partially paid
    if ($SALES_INVOICE->isInvoicePartiallyPaid($invoiceId)) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot cancel invoice that has been partially paid through payment receipts']);
        exit();
    }
    
    $result = $SALES_INVOICE->cancel();

    if (is_array($result) && $result['success']) {
        $STOCK_TRANSACTION = new StockTransaction(NULL);
        $SALES_INVOICE_ITEM = new SalesInvoiceItem(NULL);
        $STOCK_ITEM_TMP = new StockItemTmp(NULL);

        $items = $SALES_INVOICE_ITEM->getItemsByInvoiceId($invoiceId);

        foreach ($items as $item) {
            
            // Extract ARN ID and Department from item_name
            $arnId = null;
            $arnDepartmentId = $SALES_INVOICE->department_id; // fallback to invoice department
            
            if (strpos($item['item_name'], '|ARN:') !== false) {
                preg_match('/\|ARN:(\d+)\|DEPT:(\d+)/', $item['item_name'], $matches);
                if (isset($matches[1]) && isset($matches[2])) {
                    $arnId = (int)$matches[1];
                    $arnDepartmentId = (int)$matches[2];
                }
            }
            
            if ($item['item_code'] != 0) {
                $STOCK_MASTER = new StockMaster(NULL);
                
                // Add quantity back to the ARN's original department, not invoice department
                $currentQty = $STOCK_MASTER->getAvailableQuantity($arnDepartmentId, $item['item_code']);
                $newQty = $currentQty + $item['quantity'];
                $STOCK_MASTER->quantity = $newQty;
                $STOCK_MASTER->updateQtyByItemAndDepartment($arnDepartmentId, $item['item_code'], $newQty);

                // Update stock transaction with ARN reference if available
                $STOCK_TRANSACTION->item_id = $item['item_code'];
                $STOCK_TRANSACTION->type = 14; // get this id from stock adjustment type table PK
                $STOCK_TRANSACTION->date = date("Y-m-d");
                $STOCK_TRANSACTION->qty_in = $item['quantity'];
                $STOCK_TRANSACTION->qty_out = 0;
                $STOCK_TRANSACTION->remark = "INVOICE CANCELLED #$invoiceId " . ($arnId ? "(ARN: {$arnId}) " : "") . "Cancelled " . date("Y-m-d H:i:s");
                $STOCK_TRANSACTION->created_at = date("Y-m-d H:i:s");
                $STOCK_TRANSACTION->create();

                // Restore ARN quantities - try specific ARN first, then fallback to general ARN stock restoration
                $arnRestored = false;
                if ($arnId) {
                    // Try to restore to the specific ARN that was used
                    $qtyToAdd = abs($item['quantity']);
                    $arnRestored = $STOCK_ITEM_TMP->updateQtyByArnId($arnId, $item['item_code'], $arnDepartmentId, $qtyToAdd);
                }
                
                // If specific ARN restoration failed or no ARN ID, restore to general ARN stock using FIFO
                if (!$arnRestored) {
                    $STOCK_ITEM_TMP->addBackQuantity($item['item_code'], $arnDepartmentId, abs($item['quantity']));
                }
             
            } else {
                $SERVICE_ITEM = new ServiceItem($item['service_item_code']);
                $currentQty = $SERVICE_ITEM->qty;
                $newQty = $currentQty + $item['quantity'];
                $SERVICE_ITEM->qty = $newQty;
                $SERVICE_ITEM->update();
            }
        }

        $CUSTOMER_MASTER = new CustomerMaster($SALES_INVOICE->customer_id);
        $CUSTOMER_MASTER->updateCustomerOutstanding($SALES_INVOICE->customer_id, $SALES_INVOICE->grand_total, false);

        // Update sales order status to 2 (invoice cancelled) for any sales orders associated with this invoice
        $salesOrderIds = [];
        foreach ($items as $item) {
            if (!empty($item['sales_order_id'])) {
                $salesOrderIds[] = $item['sales_order_id'];
            }
        }
        
        // Debug logging
        error_log("Found sales order IDs for cancellation: " . print_r($salesOrderIds, true));
        
        // Remove duplicates and update sales order statuses
        $salesOrderIds = array_unique($salesOrderIds);
        foreach ($salesOrderIds as $salesOrderId) {
            error_log("Updating sales order $salesOrderId to status 2 (cancelled)");
            $SALES_ORDER = new SalesOrder($salesOrderId);
            $result = $SALES_ORDER->markAsInvoiceCancelled();
            error_log("Sales order $salesOrderId update result: " . ($result ? 'SUCCESS' : 'FAILED'));
        }

        echo json_encode(['status' => 'success', 'message' => $result['message']]);
    } else {
        // Handle cancellation failure with specific error message
        $errorMessage = 'Failed to cancel invoice';
        if (is_array($result) && isset($result['message'])) {
            $errorMessage = $result['message'];
        }
        echo json_encode(['status' => 'error', 'message' => $errorMessage]);
    }
    exit();
}

if (isset($_POST['get_items'])) {
    $SALES_INVOICE = new SalesInvoice(NULL);
    $invoice = $SALES_INVOICE->getByID($_POST['invoice_id']);
    $invoice_type = $invoice && isset($invoice['invoice_type']) ? $invoice['invoice_type'] : 'customer'; // default to customer if not found

    $SALES_INVOICE_ITEM = new SalesInvoiceItem(NULL);
    $items = $SALES_INVOICE_ITEM->getItemsByInvoiceId($_POST['invoice_id']);

    // Set the price based on invoice_type
    foreach ($items as &$item) {
        $item['price'] = $invoice_type === 'customer' ? $item['customer_price'] : $item['dealer_price'];
    }

    echo json_encode($items);
    exit();
}