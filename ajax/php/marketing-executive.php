<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

// Create a new Marketing Executive
if (isset($_POST['create'])) {

    $EXECUTIVE = new MarketingExecutive(NULL); // Create new object

    $EXECUTIVE->code = $_POST['code'];
    $EXECUTIVE->full_name = $_POST['full_name'];
    $EXECUTIVE->nic = $_POST['nic'];
    $EXECUTIVE->mobile_number = $_POST['mobile_number'];
    $EXECUTIVE->whatsapp_number = $_POST['whatsapp_number'];
    $EXECUTIVE->target_month = $_POST['target_month'];
    $EXECUTIVE->target = $_POST['target'];
    $EXECUTIVE->commission = $_POST['commission'];
    $EXECUTIVE->joined_date = $_POST['joined_date'];
    $EXECUTIVE->is_active = isset($_POST['is_active']) ? 1 : 0;
    $EXECUTIVE->remark = $_POST['remark'];

    $res = $EXECUTIVE->create();



    if ($res) {

        $name = $_POST['full_name'];
        $code = $_POST['user_id'];
        $type = 3;
        $company_id = 1;
        $active = 1;
        $email = '-';
        $phone = $_POST['mobile_number'];
        $username = $_POST['user_name'];
        $password = $_POST['password'];
        $department_id = 1;
        $sales_executive_id = $res;

        $USER = new User(NULL);

        $res = $USER->create($name, $code, $type, $company_id, $active, $email, $phone, $username, $password, $password, $department_id, $sales_executive_id);

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit();
}

// Update Marketing Executive
if (isset($_POST['update'])) {

    $EXECUTIVE = new MarketingExecutive($_POST['id']); // Load existing object by ID

    $EXECUTIVE->code = $_POST['code'];
    $EXECUTIVE->full_name = $_POST['full_name'];
    $EXECUTIVE->nic = $_POST['nic'];
    $EXECUTIVE->mobile_number = $_POST['mobile_number'];
    $EXECUTIVE->whatsapp_number = $_POST['whatsapp_number'];
    $EXECUTIVE->target_month = $_POST['target_month'];
    $EXECUTIVE->target = $_POST['target'];
    $EXECUTIVE->commission = $_POST['commission'];
    $EXECUTIVE->joined_date = $_POST['joined_date'];
    $EXECUTIVE->is_active = isset($_POST['is_active']) ? 1 : 0;
    $EXECUTIVE->remark = $_POST['remark'];

    $res = $EXECUTIVE->update();

    if ($res) {

        $USER = new User(null);
        $user = $USER->getUserBySalesExecutiveId($_POST['id']);
        if ($user) {
            $USER = new User($user['id']);
            $USER->update($_POST['user_name'], $_POST['password']);
        }
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit();
}

// Delete Marketing Executive
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $EXECUTIVE = new MarketingExecutive($_POST['id']);
    $res = $EXECUTIVE->delete();

    if ($res) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
