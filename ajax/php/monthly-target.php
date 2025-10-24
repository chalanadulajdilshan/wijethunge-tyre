<?php
include '../../class/include.php';

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    parse_str($_POST['data'], $data);
    $TARGET = new MonthlyTarget();
    $TARGET->month = $data['combined_month'];
    $TARGET->target = $data['target'];
    $TARGET->target_commission = $data['target_commission'];
    $TARGET->supper_target = $data['supper_target'];
    $TARGET->supper_target_commission = $data['supper_target_commission'];
    $TARGET->collection_target = $data['collection_target'];
    $TARGET->sales_executive_id = $data['sales_executive_id'];

    $id = $TARGET->create();
    echo json_encode(['status' => $id ? 'success' : 'error', 'message' => $id ? 'Target added successfully' : 'Error adding target']);
}

if ($action === 'update') {
    parse_str($_POST['data'], $data);
    $TARGET = new MonthlyTarget($data['target_id']);
    $TARGET->month = $data['combined_month'];
    $TARGET->target = $data['target'];
    $TARGET->target_commission = $data['target_commission'];
    $TARGET->supper_target = $data['supper_target'];
    $TARGET->supper_target_commission = $data['supper_target_commission'];
    $TARGET->collection_target = $data['collection_target'];
    $TARGET->sales_executive_id = $data['sales_executive_id'];

    $success = $TARGET->update();
    echo json_encode(['status' => $success ? 'success' : 'error', 'message' => $success ? 'Target updated successfully' : 'Error updating target']);
}

if ($action === 'delete') {
    $TARGET = new MonthlyTarget($_POST['id']);
    $success = $TARGET->delete();
    echo json_encode(['status' => $success ? 'success' : 'error', 'message' => $success ? 'Target deleted successfully' : 'Error deleting target']);
}
