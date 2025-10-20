<?php
include '../../class/include.php';
header('Content-Type: application/json');


if ($_POST['action'] === 'get_credit_invoices') {
    $customerId = (int) $_POST['customer_id'];
    $ARN = new ARNMaster(null);
    $data = $ARN->getCreditInvoicesByCustomerAndStatus(2, $customerId);
    header('Content-Type: application/json');
    echo json_encode(['success' => !empty($data), 'data' => $data]);
    exit();
}

// Create a new payment receipt
if (isset($_POST['create'])) {

    $RECEIPT = new PaymentReceiptSupplier(NULL);

    $RECEIPT->receipt_no   = $_POST['code'];
    $RECEIPT->customer_id  = $_POST['customer_id'];
    $RECEIPT->entry_date   = $_POST['entry_date'];
    $RECEIPT->amount_paid  = $_POST['paid_amount'];
    $RECEIPT->remark       = $_POST['remark'];

    $res = $RECEIPT->create();

    if ($res) {
        // Check if new JSON format is being used
        if (isset($_POST['methods']) && !empty($_POST['methods'])) {
            $methods = json_decode($_POST['methods'], true);

            if (is_array($methods)) {
                foreach ($methods as $method) {
                    $PAYMENT_RECEIPT_METHOD_SUPPLIER = new PaymentReceiptMethodSupplier(null);

                    $PAYMENT_RECEIPT_METHOD_SUPPLIER->receipt_id = $res;
                    $PAYMENT_RECEIPT_METHOD_SUPPLIER->invoice_id = $method['invoice_id'] ?? null;
                    $PAYMENT_RECEIPT_METHOD_SUPPLIER->payment_type_id = $method['payment_type_id'] ?? null;
                    $PAYMENT_RECEIPT_METHOD_SUPPLIER->amount = $method['amount'] ?? 0;
                    $PAYMENT_RECEIPT_METHOD_SUPPLIER->cheq_no = $method['cheq_no'] ?? null;
                    $PAYMENT_RECEIPT_METHOD_SUPPLIER->bank_id = $method['bank_id'] ?? null;
                    $PAYMENT_RECEIPT_METHOD_SUPPLIER->branch_id = $method['branch_id'] ?? null;
                    $PAYMENT_RECEIPT_METHOD_SUPPLIER->cheq_date = $method['cheq_date'] ?? null;

                    $PAYMENT_RECEIPT_METHOD_SUPPLIER->create();

                    // Update invoice outstanding if invoice_id is provided
                    if (!empty($PAYMENT_RECEIPT_METHOD_SUPPLIER->invoice_id)) {
                        $ARN = new ARNMaster(null);
                        $ARN->updateInvoiceOutstanding($PAYMENT_RECEIPT_METHOD_SUPPLIER->invoice_id, $PAYMENT_RECEIPT_METHOD_SUPPLIER->amount);

                        // Check if invoice is fully settled
                        $ARN_ = new ARNMaster($PAYMENT_RECEIPT_METHOD_SUPPLIER->invoice_id);
                        if ($ARN_->paid_amount >= $ARN_->total_arn_value) {
                            $PAYMENT_RECEIPT_METHOD_SUPPLIER->updateIsSettle($PAYMENT_RECEIPT_METHOD_SUPPLIER->id);
                        }
                    }
                }
            }
        } else {
            // Fallback to old format for backward compatibility
            // var_dump($_POST['cheque_no']);
            foreach ($_POST['invoice_id'] as $index => $invoice_id) {
                // Get the payment amounts for this invoice and ensure they are floats
                $chequePay = isset($_POST['cheque_pay'][$index]) ? floatval(str_replace(',', '', $_POST['cheque_pay'][$index])) : 0.0;
                $cashPay = isset($_POST['cash_pay'][$index]) ? floatval(str_replace(',', '', $_POST['cash_pay'][$index])) : 0.0;

                $PAYMENT_RECEIPT_METHOD_SUPPLIER = new PaymentReceiptMethodSupplier(null);
                $ARN = new ARNMaster(null);

                // Only process if at least one payment method has an amount > 0
                if ($chequePay > 0 || $cashPay > 0) {
                    if ($cashPay > 0) {
                        $PAYMENT_RECEIPT_METHOD_SUPPLIER->receipt_id = $res;
                        $PAYMENT_RECEIPT_METHOD_SUPPLIER->invoice_id = $invoice_id;
                        $PAYMENT_RECEIPT_METHOD_SUPPLIER->payment_type_id = 1; // 1 for 'cash'
                        $PAYMENT_RECEIPT_METHOD_SUPPLIER->amount = $cashPay;
                    }
                    if ($chequePay > 0) {
                        $PAYMENT_RECEIPT_METHOD_SUPPLIER->receipt_id = $res;
                        $PAYMENT_RECEIPT_METHOD_SUPPLIER->invoice_id = $invoice_id;
                        $PAYMENT_RECEIPT_METHOD_SUPPLIER->payment_type_id = 2; // 2 for 'cheque'
                        $PAYMENT_RECEIPT_METHOD_SUPPLIER->amount = $chequePay;
                        $PAYMENT_RECEIPT_METHOD_SUPPLIER->cheq_no = $_POST['cheque_no'][$index] ?? '';
                        $PAYMENT_RECEIPT_METHOD_SUPPLIER->branch_id = $_POST['bank_branch'][$index] ?? null;
                        $PAYMENT_RECEIPT_METHOD_SUPPLIER->cheq_date = $_POST['cheque_date'][$index] ?? null;
                    }
                    $res1 = $PAYMENT_RECEIPT_METHOD_SUPPLIER->create();
                }
                $ARN->updateInvoiceOutstanding($invoice_id, $chequePay + $cashPay);

                // Reload the invoice to get updated outstanding amount
                $ARN_ = new ARNMaster($invoice_id);


                if ($ARN_->paid_amount >= $ARN_->total_arn_value) {
                    $PAYMENT_RECEIPT_METHOD_SUPPLIER->updateIsSettle($res1);
                }
            }
        }

        $CUSTOMER_MASTER = new CustomerMaster(NULL);
        $CUSTOMER_MASTER->updateCustomerOutstanding($_POST['customer_id'], $_POST['paid_amount'], false);

        $DOCUMENT_TRACKING = new DocumentTracking(null);
        $DOCUMENT_TRACKING->incrementDocumentId('payment_receipt');
    }

    if ($res) {
        echo json_encode(["status" => 'success', "id" => $res]);
    } else {
        echo json_encode(["status" => 'error']);
    }
    exit();
}

// Update payment receipt
if (isset($_POST['update'])) {

    if (!isset($_POST['id'])) {
        echo json_encode(["status" => 'error', "message" => "Missing receipt ID"]);
        exit();
    }

    $RECEIPT = new PaymentReceipt($_POST['id']); // Load receipt by ID

    $RECEIPT->receipt_no   = $_POST['receipt_no'];
    $RECEIPT->customer_id  = $_POST['customer_id'];
    $RECEIPT->entry_date   = $_POST['entry_date'];
    $RECEIPT->amount_paid  = $_POST['amount_paid'];
    $RECEIPT->remark       = $_POST['remark'];

    $res = $RECEIPT->update();

    if ($res) {
        echo json_encode(["status" => 'success']);
    } else {
        echo json_encode(["status" => 'error']);
    }
    exit();
}

// Delete payment receipt
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $RECEIPT = new PaymentReceipt($_POST['id']);
    $res = $RECEIPT->delete();

    if ($res) {
        echo json_encode(["status" => 'success']);
    } else {
        echo json_encode(["status" => 'error']);
    }
    exit();
}
