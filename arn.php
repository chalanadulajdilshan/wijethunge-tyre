<!doctype html>
<?php
include 'class/include.php';
include './auth.php';

$DOCUMENT_TRACKING = new DocumentTracking($doc_id);

// Get the last inserted package id
$lastId = $DOCUMENT_TRACKING->arn_id;
$arn_id = $COMPANY_PROFILE_DETAILS->company_code . '/ARN/00/' . ($lastId + 1);
?>

<html lang="en">

<head>

    <meta charset="utf-8" />
    <title>Arn Master | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="#" name="description" />
    <?php include 'main-css.php' ?>


    <style>
        .col-lg-1 {
            width: 6.9% !important;
        }
    </style>


</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">



    <!-- Begin page -->
    <div id="layout-wrapper">

        <?php include 'navigation.php' ?>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row mb-4">
                        <div class="col-md-8 d-flex align-items-center flex-wrap gap-2">
                            <a href="#" class="btn btn-success" id="new">
                                <i class="uil uil-plus me-1"></i> New
                            </a>
                            <a href="#" class="btn btn-primary" id="create_arn" style="display: none;">
                                <i class="uil uil-save me-1"></i> Save
                            </a>

                            <a href="#" class="btn btn-warning" id="payment" style="display: none;">
                                <i class="uil uil-save me-1"></i> Payment
                            </a>

                            <a href="#" class="btn btn-danger cancel-arn-btn" style="display: none;">
                                <i class="uil uil-trash-alt me-1"></i> Cancel ARN
                            </a>

                            <div class="d-flex align-items-center ms-3" id="payment_Label" style="display: none; font-size: 1.1rem;">
                                <span id="paymentStatusText" class="fw-bold d-inline-flex align-items-center">
                                    <i class="uil uil-check-circle me-1" style="font-size: 1.3rem; display: none;"></i>
                                    <span></span>
                                </span>
                            </div>
                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">ARN </li>
                            </ol>
                        </div>
                    </div>
                    <!--- Hidden Values -->
                    <input type="hidden" id="arn_id">
                    <input type="hidden" id="item_id">

                    <input type="hidden" id="availableQty">
                    <input type="hidden" id="purchase_order_id">
                    <input type="hidden" id="supplier_id">
                    <input type="hidden" id="status" value="1">


                    <!-- end page title -->

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="p-4">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="avatar-xs">
                                                <div class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                    01
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <h5 class="font-size-16 mb-1">ARN </h5>
                                            <p class="text-muted text-truncate mb-0">Fill all information below</p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="mdi mdi-chevron-up accor-down-icon font-size-24"></i>
                                        </div>
                                    </div>

                                </div>

                                <div class="p-4">
                                    <form id="form-data">
                                        <div class="row">

                                            <div class="col-md-2">
                                                <label for="arn_no" class="form-label">ARN No</label>
                                                <div class="input-group">
                                                    <input id="arn_no" name="arn_no" type="text" class="form-control"
                                                        value="<?php echo $arn_id ?>" readonly>

                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#arn_modal">
                                                        <i class="uil uil-search me-1"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <label for="PO_No" class="form-label">PO No</label>
                                                <div class="input-group">
                                                    <input id="po_no" type="text" class="form-control"
                                                        placeholder="Select Po No" readonly>

                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#po_number_modal">
                                                        <i class="uil uil-search me-1"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label" for="entry_date">PO Date</label>
                                                <input id="order_date" name="order_date" type="text"
                                                    class="form-control  " placeholder="Select Po Date" readonly>
                                            </div>

                                            <div class="col-md-4">
                                                <label for="supplier" class="form-label">Supplier</label>
                                                <div class="input-group mb-3">
                                                    <input id="supplier_code" name="supplier_code" type="text"
                                                        class="form-control ms-2 me-2" style="max-width: 150px;"
                                                        readonly placeholder=" Supplier Code">
                                                    <input id="supplier_name" name="supplier_name" type="text"
                                                        class="form-control" placeholder="Select Suplier Name" readonly>

                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#supplierModal">
                                                        <i class="uil uil-search me-1"></i>
                                                    </button>
                                                </div>

                                            </div>
                                            <div class="col-md-2">
                                                <label for="Entry_Date" class="form-label">Entry Date</label>
                                                <div class="input-group mb-3">
                                                    <input id="entry_date" name="entry_date" type="text"
                                                        class="form-control" value="<?php echo date('Y-m-d') ?> "
                                                        readonly>
                                                </div>
                                            </div>


                                            <div class="col-md-2">
                                                <label for="Department" class="form-label">Department</label>
                                                <div class="input-group mb-3">
                                                    <select id="department_id" name="department_id" class="form-select">
                                                        <?php
                                                        $DEPARTMENT_MASTER = new DepartmentMaster(NUll);
                                                        foreach ($DEPARTMENT_MASTER->getActiveDepartment() as $departments) {
                                                            if ($US->type != 1) {
                                                                if ($departments['id'] = $US->department_id) {
                                                        ?>
                                                                    <option value="<?php echo $departments['id'] ?>">
                                                                        <?php echo $departments['name'] ?>
                                                                    </option>
                                                                <?php }
                                                            } else {
                                                                ?>
                                                                <option value="<?php echo $departments['id'] ?>">
                                                                    <?php echo $departments['name'] ?>
                                                                </option>
                                                        <?php
                                                            }
                                                        } ?>


                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-2 hidden">
                                                <label for="CI_No" class="form-label">CI No</label>
                                                <div class="input-group mb-3">
                                                    <input id="ci_no" name="ci_no" type="text" placeholder="Enter CI No"
                                                        class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <label for="BL_No" class="form-label">Invoice No</label>
                                                <div class="input-group mb-3">
                                                    <input id="bl_no" name="bl_no" type="text" placeholder="Enter Invoice No"
                                                        class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <label for="Brand" class="form-label">Brand</label>
                                                <div class="input-group mb-3">
                                                    <select id="brand" name="brand" class="form-select">
                                                        <option value="">-- All Brands --</option>
                                                        <?php
                                                        $BRAND = new Brand(NULL);
                                                        foreach ($BRAND->activeBrands() as $brand) {
                                                            echo "<option value='{$brand['id']}'>{$brand['name']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>


                                            <div class="col-md-2">
                                                <label for="Category" class="form-label">Category</label>
                                                <div class="input-group mb-3">
                                                    <select id="category" name="category" class="form-select">
                                                        <option value="">-- All Category --</option>
                                                        <?php
                                                        $CATEGORY = new CategoryMaster(NULL);
                                                        foreach ($CATEGORY->getActiveCategory() as $category) {
                                                            echo "<option value='{$category['id']}'>{$category['name']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <label for="arn_status" class="form-label">ARN Status</label>
                                                <div class="input-group mb-3">
                                                    <select id="arn_status" name="arn_status" class="form-select">
                                                        <option value="">Select Status</option>
                                                        <option value="Pending">Pending</option>
                                                        <option value="Approved">Approved</option>
                                                        <option value="Received">Received</option>
                                                        <option value="Rejected">Rejected</option>


                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-2 hidden">
                                                <label for="LC?TT_No" class="form-label">LC/TT No</label>
                                                <div class="input-group mb-3">
                                                    <input id="lc_tt_no" name="lc_tt_no" type="text"
                                                        placeholder="Enter LC / TT No" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-2 hidden">
                                                <label for="PI_No" class="form-label">PI No</label>
                                                <div class="input-group mb-3">
                                                    <input id="pi_no" name="pi_no" type="text" placeholder="Enter PI No"
                                                        class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-2 hidden">
                                                <label for="Order_By" class="form-label">Order By</label>
                                                <div class="input-group mb-3">
                                                    <select id="order_by" name="order_by" class="form-select">
                                                        <?php
                                                        $DEFAULT_DATA = new DefaultData();
                                                        foreach ($DEFAULT_DATA->getOrderByOptions() as $key => $order_by) {
                                                        ?>
                                                            <option value="<?php echo $key ?>"><?php echo $order_by ?>
                                                            </option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-2 hidden">
                                                <label for="Purchase_Type" class="form-label">Purchase Type</label>
                                                <div class="input-group mb-3">
                                                    <select id="purchase_type" name="purchase_type" class="form-select">
                                                        <?php
                                                        $PURCHASE_TYPE = new PurchaseType(NULL);
                                                        foreach ($PURCHASE_TYPE->all() as $purchase_type) {
                                                        ?>
                                                            <option value="<?php echo $purchase_type['id'] ?>">
                                                                <?php echo $purchase_type['title'] ?>
                                                            </option>
                                                        <?php } ?>


                                                    </select>
                                                </div>
                                            </div>



                                            <div class="col-md-2">
                                                <label class="form-label" for="Invoice_date">Invoice Date</label>
                                                <input id="invoice_date" name="invoice_date" type="text"
                                                    class="form-control date-picker" placeholder="Select Invoice Date">
                                            </div>

                                            <div class="col-md-2">
                                                <label for="Country" class="form-label">Country</label>
                                                <div class="input-group mb-3">
                                                    <select id="country" name="country" class="form-select">
                                                        <?php
                                                        $COUNTRY = new Country(NULL);
                                                        foreach ($COUNTRY->activeCountry() as $country) {
                                                            echo "<option value='{$country['id']}'>{$country['name']}</option>";
                                                        }
                                                        ?>

                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-2  ">
                                                <label for="VAT" class="form-label">Payment Type <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group mb-3">
                                                    <select id="payment_type" name="payment_type" class="form-select">
                                                        <option value="">Select Payment Type</option>
                                                        <option value="Cash">Cash </option>
                                                        <option value="Credit">Credit</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <label class="form-label">Pending Credit Note Amount</label>
                                                <input type="number" id="credit_note_amount" name="credit_note_amount"
                                                    class="form-control" placeholder="Amount">
                                            </div>



                                            <div class="col-md-2">
                                                <label for="name" class="form-label">Delivery Date</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control date-picker"
                                                        id="delivery_date" name="delivery_date"> <span
                                                        class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <label for="company_arn_adjust" class="form-label">Company ARN Adjust</label>
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" id="company_arn_adjust" name="company_arn_adjust">
                                                    <label class="form-check-label" for="company_arn_adjust">
                                                        Enable Company ARN
                                                    </label>

                                                </div>
                                            </div>

                                            <div class="col-md-12 mt-3 ">
                                                <label for="remark" class="form-label">Remarks</label>
                                                <textarea id="remark" name="remark" class="form-control" rows="4"
                                                    placeholder="Enter any remarks or notes..."></textarea>
                                            </div>


                                            <hr class="my-4">

                                            <h5>Item Details</h5>

                                            <div class="row gx-2 gy-2" id="arn-item-table">
                                                <!-- ────────── First Line of Fields ────────── -->


                                                <div class="  col-sm-2 col-md-2 col-lg-2" style="width: 345px;">
                                                    <label for="Description" class="form-label">Item Code</label>
                                                    <div class="input-group input-group-sm">
                                                        <input id="itemCode" name="itemCode" type="text"
                                                            class="form-control" readonly>
                                                        <button id="item_search" class="btn btn-info" type="button"
                                                            data-bs-toggle="modal" data-bs-target="#main_item_master" disabled>
                                                            <i class="uil uil-search me-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="col-6 col-sm-4 col-md-2 col-lg-1">
                                                    <label class="form-label">Rec Qty</label>
                                                    <input type="number" class=" form-control form-control-sm"
                                                        id="rec_quantity">
                                                </div>

                                                <div class="col-6 col-sm-4 col-md-2 col-lg-1">
                                                    <label class="form-label">List Price</label>
                                                    <input type="text" id="list_price" name="list_price"
                                                        class="form-control form-control-sm">
                                                </div>


                                                <div class="col-6 col-sm-4 col-md-2 col-lg-1">
                                                    <label class="form-label"> Brand Dis %</label>
                                                    <input type="number" id="dis_1" class="form-control form-control-sm"
                                                        disabled>
                                                </div>


                                                <div class="col-6 col-sm-4 col-md-2 col-lg-1">
                                                    <label class="form-label">Item Dis %</label>
                                                    <input type="number" id="dis_2" class="form-control form-control-sm"
                                                        disabled>
                                                </div>

                                                <!-- ────────── Second Line of Fields ────────── -->
                                                <div class="col-6 col-sm-4 col-md-2 col-lg-1">
                                                    <label class="form-label">Dis 3 %</label>
                                                    <input type="number" id="dis_3" class="form-control form-control-sm">
                                                </div>
                                                <div class="col-6 col-sm-4 col-md-2 col-lg-1">
                                                    <label class="form-label">Dis 4 %</label>
                                                    <input type="number" id="dis_4" class="form-control form-control-sm">
                                                </div>
                                                <div class="col-6 col-sm-4 col-md-2 col-lg-1">
                                                    <label class="form-label">Dis 5 %</label>
                                                    <input type="number" id="dis_5" class="form-control form-control-sm">
                                                </div>

                                                <input type="hidden" id="dis_6">
                                                <input type="hidden" id="dis_7">
                                                <input type="hidden" id="dis_8">

                                                <div class="col-6 col-sm-4 col-md-2 col-lg-1">
                                                    <label class="form-label">Actual Cost</label>
                                                    <input type="text" id="actual_cost"
                                                        class="form-control form-control-sm  ">
                                                </div>

                                                <div class="col-6 col-sm-4 col-md-2 col-lg-1">
                                                    <label class="form-label">Selling Price</label>
                                                    <input type="text" id="invoice_price"
                                                        class="form-control form-control-sm">
                                                </div>




                                                <div class="col-6 col-sm-4 col-md-2 col-lg-1">
                                                    <label class="form-label">Unit Total</label>
                                                    <input type="text" id="unit_total"
                                                        class="form-control form-control-sm" readonly>
                                                </div>

                                                <div class="col-6 col-sm-4 col-md-2 col-lg-1 align-self-end">
                                                    <button type="button" class="btn btn-success btn-sm w-100"
                                                        id="addItemBtn">
                                                        Add
                                                    </button>
                                                </div>
                                            </div>

                                            <table id="itemTable" class="table table-bordered table-sm mt-5">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Item Code</th>
                                                        <th>Ord Qty</th>
                                                        <th>Rec Qty</th>
                                                        <th>List Price</th>
                                                        <th>Brand Dis%</th>
                                                        <th>Item Dis%</th>
                                                        <th>Dis 3%</th>
                                                        <th>Dis 4%</th>
                                                        <th>Dis 5%</th>
                                                        <th>Actual Cost</th>
                                                        <th>Unit Total</th>
                                                        <th>Selling Price</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>

                                                <tbody id="itemTableBody">
                                                    <tr id="noDataRow">
                                                        <td colspan="8" class="text-center">No data available</td>
                                                    </tr>
                                                </tbody>
                                            </table>


                                            <hr class="my-4">
                                            <div class="row justify-content-end">
                                                <div class="p-2 border rounded bg-light" style="max-width: 500px;">


                                                    <div class="row mb-2">
                                                        <div class="col-7">
                                                            <input type="text" class="form-control text_purchase3"
                                                                value="Total Discount" disabled>
                                                        </div>
                                                        <div class="col-5">
                                                            <input type="text" id="total_discount"
                                                                class="form-control text-end" value="0.00" disabled>
                                                        </div>
                                                    </div>

                                                    <div class="row mb-2 hidden">
                                                        <div class="col-7">
                                                            <input type="text" class="form-control text_purchase3"
                                                                value="Total VAT Value" disabled>
                                                        </div>
                                                        <div class="col-5">
                                                            <input type="text" id="total_vat"
                                                                class="form-control text-end" value="0.00" disabled>
                                                        </div>
                                                    </div>

                                                    <div class="row mb-2">
                                                        <div class="col-7">
                                                            <input type="text" class="form-control text_purchase3"
                                                                value="Total Received Qty" disabled>
                                                        </div>
                                                        <div class="col-5">
                                                            <input type="text" id="total_received_qty"
                                                                class="form-control text-end" value="0.00" disabled>
                                                        </div>
                                                    </div>

                                                    <div class="row border-top pt-2">
                                                        <div class="col-7">
                                                            <input type="text" class="form-control text_purchase3 "
                                                                value="Total Order Quantity" disabled>
                                                        </div>
                                                        <div class="col-5">
                                                            <input type="text" id="total_order_qty"
                                                                class="form-control  text-end" value="0.00" disabled>
                                                        </div>
                                                    </div>
                                                    <div class="row border-top pt-2">

                                                        <div class="col-7">
                                                            <input type="text"
                                                                class="form-control text_purchase3 fw-bold"
                                                                value="Total ARN Value" disabled>
                                                        </div>
                                                        <div class="col-5">
                                                            <input type="text" class="form-control text-end fw-bold"
                                                                id="total_arn" value="0.00" disabled>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'footer.php' ?>

    </div>


    <!-- purchase Order -->
    <div class="modal fade bs-example-modal-xl" id="po_number_modal" tabindex="-1" role="dialog"
        aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">

        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myExtraLargeModalLabel">Manage Purchase Orders</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table class="datatable table table-bordered dt-responsive nowrap"
                                style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>#id</th>
                                        <th>PO No</th>
                                        <th>Order Date</th>
                                        <th>Supplier Code and Name</th>
                                        <th>Department</th>
                                        <th>Grand Total</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php
                                    $PURCHASE_ORDER = new PurchaseOrder(null);
                                    foreach ($PURCHASE_ORDER->getAllByStatus(0) as $key => $purchase_order) {
                                        $CUSTOMER_MASTER = new CustomerMaster($purchase_order['supplier_id']);
                                        $DEPARTMENT_MASTER = new DepartmentMaster($purchase_order['department']);
                                        $key++;
                                    ?>
                                        <tr class="select-purchase-order" data-id="<?= $purchase_order['id']; ?>"
                                            data-po_number="<?= htmlspecialchars($purchase_order['po_number']); ?>"
                                            data-order_date="<?= htmlspecialchars($purchase_order['order_date']); ?>"
                                            data-supplier_id="<?= htmlspecialchars($purchase_order['supplier_id']); ?>"
                                            data-supplier_code="<?= htmlspecialchars($CUSTOMER_MASTER->code); ?>"
                                            data-supplier_name="<?= htmlspecialchars($CUSTOMER_MASTER->name); ?>"
                                            data-supplier_address="<?= htmlspecialchars($CUSTOMER_MASTER->address); ?>"

                                            data-address="<?= htmlspecialchars($purchase_order['address']); ?>"

                                            data-brand="<?= htmlspecialchars($purchase_order['brand']); ?>"
                                            data-bl_no="<?= htmlspecialchars($purchase_order['invoice_no']); ?>"
                                            data-country="<?= htmlspecialchars($purchase_order['country']); ?>"
                                            data-department="<?= htmlspecialchars($purchase_order['department']); ?>"
                                            data-grand_total="<?= htmlspecialchars($purchase_order['grand_total']); ?>"
                                            data-status="<?= htmlspecialchars($purchase_order['status']); ?>"
                                            data-remarks="<?= htmlspecialchars($purchase_order['remarks']); ?>">
                                            <td><?= $key; ?></td>
                                            <td><?= htmlspecialchars($purchase_order['po_number']); ?></td>
                                            <td><?= htmlspecialchars($purchase_order['order_date']); ?></td>
                                            <td><?= htmlspecialchars($CUSTOMER_MASTER->code . ' - ' . $CUSTOMER_MASTER->name); ?>
                                            </td>
                                            <td><?= htmlspecialchars($DEPARTMENT_MASTER->name); ?></td>

                                            <td><?= htmlspecialchars($purchase_order['grand_total']); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>

                            </table>
                        </div> <!-- end col -->
                    </div> <!-- end row -->
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>

    <!-- ARN -->
    <div class="modal fade bs-example-modal-xl" id="arn_modal" tabindex="-1" role="dialog"
        aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">

        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myExtraLargeModalLabel">Manage ARN Orders</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table id="arn_table" class="table table-bordered dt-responsive nowrap"
                                style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>#id</th>
                                        <th>ARN No</th>
                                        <th>Supplier </th>
                                        <th>Invoice No</th>
                                        <th>Invoice Date</th>
                                        <th>Department</th>
                                        <th>Grand Total</th>
                                        <th>Paid Amount</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php
                                    $ARN_MASTER = new ArnMaster(null);
                                    foreach ($ARN_MASTER->all() as $key => $arn_master) {
                                        $CUSTOMER_MASTER = new CustomerMaster($arn_master['supplier_id']);
                                        $DEPARTMENT_MASTER = new DepartmentMaster($arn_master['department']);
                                        $key++;

                                        $is_cancelled = isset($arn_master['is_cancelled']) && $arn_master['is_cancelled'] == 1;
                                        $rowClass = $is_cancelled ? 'table-danger' : '';
                                    ?>

                                        <tr class="select-arn-order <?= $rowClass ?>"
                                            data-id="<?= $arn_master['id'] ?? ''; ?>"
                                            data-is_cancelled="<?= $is_cancelled ? '1' : '0'; ?>"
                                            data-arn_no="<?= htmlspecialchars($arn_master['arn_no'] ?? ''); ?>"
                                            data-po_number="<?= htmlspecialchars($arn_master['po_no'] ?? ''); ?>"
                                            data-order_date="<?= htmlspecialchars($arn_master['po_date'] ?? ''); ?>"
                                            data-supplier_id="<?= htmlspecialchars($arn_master['supplier_id'] ?? ''); ?>"
                                            data-supplier_code="<?= htmlspecialchars($CUSTOMER_MASTER->code ?? ''); ?>"
                                            data-supplier_name="<?= htmlspecialchars($CUSTOMER_MASTER->name ?? ''); ?>"
                                            data-supplier_address="<?= htmlspecialchars($CUSTOMER_MASTER->address ?? ''); ?>"
                                            data-brand_id="<?= htmlspecialchars($arn_master['brand_id'] ?? ''); ?>"
                                            data-category_id="<?= htmlspecialchars($arn_master['category_id'] ?? ''); ?>"
                                            data-pi_no="<?= htmlspecialchars($arn_master['pi_no'] ?? ''); ?>"
                                            data-lc_tt_no="<?= htmlspecialchars($arn_master['lc_tt_no'] ?? ''); ?>"
                                            data-brand="<?= htmlspecialchars($arn_master['brand'] ?? ''); ?>"
                                            data-bl_no="<?= htmlspecialchars($arn_master['bl_no'] ?? ''); ?>"
                                            data-ci_no="<?= htmlspecialchars($arn_master['ci_no'] ?? ''); ?>"
                                            data-country="<?= htmlspecialchars($arn_master['country'] ?? ''); ?>"
                                            data-department="<?= htmlspecialchars($arn_master['department'] ?? ''); ?>"
                                            data-grand_total="<?= htmlspecialchars($arn_master['total_arn_value'] ?? 0); ?>"
                                            data-status="<?= htmlspecialchars($arn_master['arn_status'] ?? ''); ?>"
                                            data-total_discount="<?= htmlspecialchars($arn_master['total_discount'] ?? 0); ?>"
                                            data-total_received_qty="<?= htmlspecialchars($arn_master['total_received_qty'] ?? 0); ?>"
                                            data-total_order_qty="<?= htmlspecialchars($arn_master['total_order_qty'] ?? 0); ?>"
                                            data-paid_amount="<?= htmlspecialchars($arn_master['paid_amount'] ?? 0); ?>"
                                            data-remarks="<?= htmlspecialchars($arn_master['remark'] ?? ''); ?>">

                                            <td><?= $key; ?></td>
                                            <td>
                                                <?= htmlspecialchars($arn_master['arn_no'] ?? ''); ?>
                                                <?php if ($is_cancelled): ?>
                                                    <span class="badge bg-danger ms-2">Cancelled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars(($CUSTOMER_MASTER->code ?? '') . ' - ' . ($CUSTOMER_MASTER->name ?? '')); ?></td>
                                            <td><?= htmlspecialchars($arn_master['ci_no'] ?? ''); ?></td>
                                            <td><?= htmlspecialchars($arn_master['invoice_date'] ?? ''); ?></td>
                                            <td><?= htmlspecialchars($DEPARTMENT_MASTER->name ?? ''); ?></td>
                                            <td class="text-end"><?= number_format($arn_master['total_arn_value'] ?? 0, 2); ?></td>
                                            <td class="text-end"><?= number_format($arn_master['paid_amount'] ?? 0, 2); ?></td>
                                        </tr>

                                    <?php } ?>
                                </tbody>


                            </table>
                        </div> <!-- end col -->
                    </div> <!-- end row -->
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>


    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>


    <?php include 'supplier-payment-model.php' ?>


    <!-- JAVASCRIPT -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- add js files -->
    <script src="ajax/js/arn-master.js"></script>
    <script src="ajax/js/common.js"></script>
    <!-- include main js  -->
    <?php include 'main-js.php' ?>


    <script>
        // Pass company name to JavaScript
        var companyName = "<?php echo isset($_SESSION['company_name']) ? $_SESSION['company_name'] : $COMPANY_PROFILE_DETAILS->name; ?>";
    </script>

</body>

</html>