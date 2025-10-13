<!doctype html>
<?php
include 'class/include.php';
include './auth.php';

?>

<html lang="en">

<head>

    <meta charset="utf-8" />
    <title> Manage Profit Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
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
                            <a href="#" class="btn btn-primary" id="view_profit_report">
                                <i class="uil uil-save me-1"></i> View Report
                            </a>

                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active"> Manage Profit Report </li>
                            </ol>
                        </div>
                    </div>
                    <!--- Hidden Values -->


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
                                            <h5 class="font-size-16 mb-1">Manage Profit Report </h5>
                                            <p class="text-muted text-truncate mb-0">Fill all information below to
                                                Manage Profit Report </p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="mdi mdi-chevron-up accor-down-icon font-size-24"></i>
                                        </div>
                                    </div>

                                    <div class="p-4">
                                        <form id="form-data" autocomplete="off">
                                            <div class="row">

                                                <div class="col-md-2">
                                                    <label for="code" class="form-label">Item Code</label>
                                                    <div class="input-group mb-3">
                                                        <input id="code" name="code" type="text" placeholder="Item Code"
                                                            class="form-control" readonly>
                                                        <button class="btn btn-info" type="button"
                                                            data-bs-toggle="modal" data-bs-target="#main_item_master">
                                                            <i class="uil uil-search me-1"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <input type="hidden" id="item_id">
                                                <div class="col-md-4">
                                                    <label for="name" class="form-label">Item Name</label>
                                                    <div class="input-group mb-3">
                                                        <input id="name" name="name" type="text" class="form-control"
                                                            placeholder="Select your Item Name" readonly>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="from_date" class="form-label">From Date</label>
                                                    <div class="input-group" id="datepicker2">
                                                        <input type="texentry_datet" class="form-control date-picker"
                                                            id="from_date" name="from_date"> <span
                                                            class="input-group-text"><i
                                                                class="mdi mdi-calendar"></i></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="to_date" class="form-label">To Date</label>
                                                    <div class="input-group" id="datepicker2">
                                                        <input type="texentry_datet" class="form-control date-picker"
                                                            id="to_date" name="to_date"> <span
                                                            class="input-group-text"><i
                                                                class="mdi mdi-calendar"></i></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="company" class="form-label">Company</Select></label>
                                                    <div class="input-group mb-3">
                                                        <select id="company" name="company" class="form-select">
                                                            <option value="">-- Select Company--</option>
                                                            <?php
                                                            $COMPANY_PROFILE = new CompanyProfile();
                                                            foreach ($COMPANY_PROFILE->all() as $company_profile) {
                                                            ?>
                                                                <option value="<?php echo $company_profile['id'] ?>">
                                                                    <?php echo $company_profile['name'] ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="brand" class="form-label">Brand</label>
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
                                                <div class="col-md-3">
                                                    <label for="department_id" class="form-label">Department</label>
                                                    <div class="input-group mb-3">
                                                        <select id="department_id" name="department_id"
                                                            class="form-select">
                                                            <option value="0">-- All Departments -- </option>
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

                                            </div>
                                        </form>


                                        <hr class="my-4">

                                        <div id="profitReportDateRange" class="mb-3"></div>

                                        <!-- Table -->
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="profitReport">
                                                <thead class="table-light">
                                                    <tr>
                                                        <td>#Id</td>
                                                        <th>Invoice No</th>
                                                        <th>Invoice Date</th>
                                                        <th>Company</th>
                                                        <th>Customer</th>
                                                        <th>Department</th>
                                                        <th>Sales Type</th>
                                                        <th>Item</th>
                                                        <th>Cost</th>
                                                        <th>Selling</th>
                                                        <th>Profit</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td colspan="11" class="text-center text-muted">No items added</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <!-- /////////////////////////// -->
    <script src="ajax/js/report.js"></script>

    <!-- Main Item Master Modal -->
    <?php include 'main-item-master-model.php'; ?>

    <!-- include main js  -->
    <?php include 'main-js.php' ?>

    <!-- App js -->
    <script src="assets/js/app.js"></script>
    <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.js"></script>
    <script>
        $('#quotation_table').DataTable();
        $(function() {
            // Initialize the datepicker
            $(".date-picker").datepicker({
                dateFormat: 'yy-mm-dd' // or 'dd-mm-yy' as per your format
            });

            // Set today's date as default value
            var today = $.datepicker.formatDate('yy-mm-dd', new Date());
            $(".date-picker").val(today);
        });
    </script>

</body>

</html>