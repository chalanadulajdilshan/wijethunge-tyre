<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';
?>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <title>Monthly Targets | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />

    <?php include 'main-css.php' ?>

</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">

    <div id="layout-wrapper">

        <?php include 'navigation.php' ?>

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
                                <a href="#" class="btn btn-danger delete-target">
                                    <i class="uil uil-trash-alt me-1"></i> Delete
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                                <li class="breadcrumb-item active">Monthly Targets</li>
                            </ol>
                        </div>
                    </div>

                    <!-- ================= FORM SECTION ================= -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div id="addtarget-accordion" class="custom-accordion">
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
                                                <h5 class="font-size-16 mb-1">Monthly Target Setup</h5>
                                                <p class="text-muted text-truncate mb-0">Enter target details below</p>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <i class="mdi mdi-chevron-up accor-down-icon font-size-24"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-4">
                                        <form id="form-data" method="post" enctype="multipart/form-data" autocomplete="off">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label for="month" class="form-label">Month <span class="text-danger">*</span></label>
                                                    <div class="input-group mb-3">
                                                        <div class="row g-2 flex-grow-1">
                                                            <div class="col-6">
                                                                <select id="month" name="month" class="form-select" required>
                                                                    <option value="">-- Month --</option>
                                                                    <option value="01">January</option>
                                                                    <option value="02">February</option>
                                                                    <option value="03">March</option>
                                                                    <option value="04">April</option>
                                                                    <option value="05">May</option>
                                                                    <option value="06">June</option>
                                                                    <option value="07">July</option>
                                                                    <option value="08">August</option>
                                                                    <option value="09">September</option>
                                                                    <option value="10">October</option>
                                                                    <option value="11">November</option>
                                                                    <option value="12">December</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-6">
                                                                <select id="year" name="year" class="form-select" required>
                                                                    <option value="">-- Year --</option>
                                                                    <?php
                                                                    $currentYear = date('Y');
                                                                    for ($year = $currentYear + 1; $year >= $currentYear - 5; $year--) {
                                                                        echo "<option value='{$year}'>{$year}</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                                            data-bs-target="#target_master">
                                                            <i class="uil uil-search me-1"></i>
                                                        </button>
                                                    </div>
                                                    <input type="hidden" id="combined_month" name="combined_month">
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="target" class="form-label">Target Amount</label>
                                                    <input type="number" id="target" name="target" class="form-control" placeholder="Enter Target">
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="target_commission" class="form-label">Target Commission</label>
                                                    <input type="number" id="target_commission" name="target_commission" class="form-control" placeholder="Enter Commission">
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="supper_target" class="form-label">Super Target</label>
                                                    <input type="number" id="supper_target" name="supper_target" class="form-control" placeholder="Enter Super Target">
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="supper_target_commission" class="form-label">Super Target Commission</label>
                                                    <input type="number" id="supper_target_commission" name="supper_target_commission" class="form-control" placeholder="Enter Commission">
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="collection_target" class="form-label">Collection Target</label>
                                                    <input type="number" id="collection_target" name="collection_target" class="form-control" placeholder="Enter Collection Target">
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="sales_executive_id" class="form-label">Sales Executive <span class="text-danger">*</span></label>
                                                    <select id="sales_executive_id" name="sales_executive_id" class="form-select" required>
                                                        <option value="">-- Select Executive --</option>
                                                        <?php
                                                        $MarketingExecutive= new MarketingExecutive(NULL);
                                                        foreach ($MarketingExecutive->all() as $emp) {
                                                            echo "<option value='{$emp['id']}'>{$emp['full_name']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <input type="hidden" id="target_id" name="target_id">
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================= MODAL SECTION ================= -->
                    <div class="modal fade" id="target_master" tabindex="-1" role="dialog" aria-labelledby="targetModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="targetModalLabel">Manage Monthly Targets</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-12">
                                            <table class="datatable table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Month</th>
                                                        <th>Sales Executive</th>
                                                        <th>Target</th>
                                                        <th>Target Commission</th>
                                                        <th>Super Target</th>
                                                        <th>Super Commission</th>
                                                        <th>Collection Target</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $TARGET = new MonthlyTarget(NULL);
                                                    foreach ($TARGET->all() as $key => $target) {
                                                        $key++;
                                                        $EXEC = new MarketingExecutive($target['sales_executive_id']);
                                                    ?>
                                                        <tr class="select-target"
                                                            data-id="<?php echo $target['id']; ?>"
                                                            data-month="<?php echo $target['month']; ?>"
                                                            data-target="<?php echo $target['target']; ?>"
                                                            data-target_commission="<?php echo $target['target_commission']; ?>"
                                                            data-supper_target="<?php echo $target['supper_target']; ?>"
                                                            data-supper_target_commission="<?php echo $target['supper_target_commission']; ?>"
                                                            data-collection_target="<?php echo $target['collection_target']; ?>"
                                                            data-sales_executive_id="<?php echo $target['sales_executive_id']; ?>">
                                                            <td><?php echo $key; ?></td>
                                                            <td><?php echo htmlspecialchars($target['month']); ?></td>
                                                            <td><?php echo htmlspecialchars($EXEC->full_name); ?></td>
                                                            <td><?php echo htmlspecialchars($target['target']); ?></td>
                                                            <td><?php echo htmlspecialchars($target['target_commission']); ?></td>
                                                            <td><?php echo htmlspecialchars($target['supper_target']); ?></td>
                                                            <td><?php echo htmlspecialchars($target['supper_target_commission']); ?></td>
                                                            <td><?php echo htmlspecialchars($target['collection_target']); ?></td>
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

                </div><!-- container-fluid -->
            </div>

            <?php include 'footer.php' ?>
        </div>
    </div>

    <div class="rightbar-overlay"></div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="ajax/js/monthly-target.js"></script>
    <?php include 'main-js.php' ?>

</body>
</html>
