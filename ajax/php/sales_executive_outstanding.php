<?php
include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Create a new outstanding record
if (isset($_POST['create'])) {

    $OUTSTANDING = new SalesExecutiveOutstanding(NULL); // Create a new object

    // Set values
    $OUTSTANDING->sale_ex_id = $_POST['sale_ex_id'];
    $OUTSTANDING->invoice_id = $_POST['invoice_id'];
    $OUTSTANDING->customer_id = $_POST['customer_id'];
    $OUTSTANDING->amount = $_POST['amount'];

    // Attempt to create record
    $res = $OUTSTANDING->create();

    echo json_encode(["status" => $res ? 'success' : 'error']);
    exit();
}

// Update outstanding record
if (isset($_POST['update'])) {

    $OUTSTANDING = new SalesExecutiveOutstanding($_POST['id']); // Get record by ID

    // Update details
    $OUTSTANDING->sale_ex_id = $_POST['sale_ex_id'];
    $OUTSTANDING->invoice_id = $_POST['invoice_id'];
    $OUTSTANDING->customer_id = $_POST['customer_id'];
    $OUTSTANDING->amount = $_POST['amount'];

    $res = $OUTSTANDING->update();

    echo json_encode(["status" => $res ? 'success' : 'error']);
    exit();
}

// Delete outstanding record
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $OUTSTANDING = new SalesExecutiveOutstanding($_POST['id']);
    $res = $OUTSTANDING->delete();

    echo json_encode(["status" => $res ? 'success' : 'error']);
    exit();
}
?>
