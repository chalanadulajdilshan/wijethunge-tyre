<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';
$INVOICE_REMARK = new InvoiceRemark();

// Get the last inserted package id
$lastId = $INVOICE_REMARK->getLastID();
$remark_id = 'IR/0' . ($lastId + 1);

?>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <title>Invoice Remark | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>

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

                            <?php if ($PERMISSIONS['add_page']): ?>
                                <a href="#" class="btn btn-primary" id="create">
                                    <i class="uil uil-save me-1"></i> Save
                                </a>
                            <?php endif; ?>

                            <?php if ($PERMISSIONS['edit_page']): ?>
                                <a href="#" class="btn btn-warning" id="update" style="display:none;">
                                    <i class="uil uil-edit me-1"></i> Update
                                </a>
                            <?php endif; ?>

                            <?php if ($PERMISSIONS['delete_page']): ?>
                                <a href="#" class="btn btn-danger delete-invoice-remark">
                                    <i class="uil uil-trash-alt me-1"></i> Delete
                                </a>
                            <?php endif; ?>

                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">INVOICE REMARK</li>
                            </ol>
                        </div>
                    </div>

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
                                            <h5 class="font-size-16 mb-1">Invoice Remark</h5>
                                            <p class="text-muted text-truncate mb-0">Fill all information below</p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="mdi mdi-chevron-up accor-down-icon font-size-24"></i>
                                        </div>
                                    </div>

                                </div>

                                <div class="p-4">

                                    <form id="form-data" autocomplete="off">
                                        <form id="form-data" autocomplete="off">
                                            <div class="row">
                                                <!-- Ref No Field -->
                                                <div class="col-md-2">
                                                    <label class="form-label" for="code">Ref No</label>
                                                    <div class="input-group align-items-start">
                                                        <input id="code" name="code" type="text" value="<?php echo $remark_id; ?>" placeholder="Ref No" class="form-control" readonly>
                                                        <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#remarkModel">
                                                            <i class="uil uil-search me-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="payment_type" class="form-label">Payment Type</label>
                                                    <div class="input-group mb-3">
                                                        <select id="payment_type" name="payment_type" class="form-select">
                                                            <?php
                                                            $PAYMENT_TYPE = new PaymentType(NULL);
                                                            foreach ($PAYMENT_TYPE->getActivePaymentType() as $payment_type) {
                                                            ?>
                                                                <option value="<?php echo $payment_type['id'] ?>">
                                                                    <?php echo $payment_type['name'] ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <!-- Remark Field  -->
                                                <div class="col-md-5">
                                                    <label for="remark" class="form-label">Remark</label>
                                                    <input type="text" id="remark" name="remark" placeholder="Enter remark" class="form-control">
                                                </div>

                                                <!-- Active Checkbox -->
                                                <div class="col-md-2 d-flex align-items-start pt-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active">
                                                        <label class="form-check-label" for="is_active">Active</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <input type="hidden" id="id" name="id" value="0">
                                        </form>
                                </div>
                                <input type="hidden" id="id" name="id" value="0">

                                </form>

                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- container-fluid -->
        </div>
        <?php include 'footer.php' ?>

    </div>
    </div>


    <!-- model open here -->
    <div class="modal fade bs-example-modal-xl" id="remarkModel" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myExtraLargeModalLabel">Manage Invoice Remark</h5>
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
                                        <th>#</th>
                                        <th>Ref No</th>
                                        <th>Payment Type</th>
                                        <th>remark</th>
                                        <th>Is Active</th>

                                    </tr>
                                </thead>


                                <tbody>
                                    <?php
                                    $REMARK = new InvoiceRemark(null);
                                    foreach ($REMARK->all() as $key => $remark) {
                                        $PAYMENT_TYPE = new PaymentType($remark['payment_type']);
                                        $key++;
                                    ?>
                                        <tr class="select-remark" data-id="<?php echo $remark['id']; ?>"
                                            data-code="<?php echo htmlspecialchars($remark['code']); ?>"
                                            data-payment_type="<?php echo htmlspecialchars($PAYMENT_TYPE->name); ?>"
                                            data-remark="<?php echo htmlspecialchars($remark['remark']); ?>"
                                            data-queue="<?php echo htmlspecialchars($remark['queue']); ?>"
                                            data-is_active="<?php echo htmlspecialchars($remark['is_active']); ?>">

                                            <td><?php echo $key; ?></td>
                                            <td><?php echo htmlspecialchars($remark['code']); ?></td>
                                            <td><?php echo htmlspecialchars($PAYMENT_TYPE->name); ?></td>
                                            <td><?php echo htmlspecialchars($remark['remark']); ?></td>
                                            <td>
                                                <?php if ($remark['is_active'] == 1): ?>
                                                    <span class="badge bg-soft-success font-size-12">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-soft-danger font-size-12">Inactive</span>
                                                <?php endif; ?>
                                            </td>
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
    <!-- model close here -->

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <!-- /////////////////////////// -->
    <script src="ajax/js/invoice-remark.js"></script>


    <!-- include main js  -->
    <?php include 'main-js.php' ?>


</body>

</html>