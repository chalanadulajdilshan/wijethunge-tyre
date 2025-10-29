<?php
include 'class/include.php';
include 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Customer Outstanding Summary | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css">
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <style>
        

        #totalOutstanding {
            background-color: #eb4034 !important;
            color: #ffffff !important;
        }


        .due-date-cell {
            font-weight: 500;
        }

        .due-soon-text {
            color: #856404;
            font-weight: bold;
        }

        .overdue-text {
            color: #721c24;
            font-weight: bold;
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">
    <div id="layout-wrapper">
        <?php include 'navigation.php'; ?>
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Customer Outstanding Report</h4>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form id="reportForm">
                                        <div class="row">
                                            <!--  FILTER ROW  -->
                                            <div class="row g-2 align-items-center">
                                                <div class="col-md-2">
                                                    <label for="paymentType" class="form-label fw-semibold text-muted mb-1" style="font-size:0.8rem;">Payment Type</label>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="fromDate" class="form-label fw-semibold text-muted mb-1" style="font-size:0.8rem;">From Date</label>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="toDate" class="form-label fw-semibold text-muted mb-1" style="font-size:0.8rem;">To Date</label>
                                                </div>
                                                <div class="col-md-4"></div>
                                            </div>

                                            <div class="row g-2 align-items-center">
                                                <div class="col-md-2">
                                                    <select class="form-select" id="paymentType" name="paymentType">
                                                        <option value="">All Types</option>
                                                        <option value="cash">Cash</option>
                                                        <option value="credit">Credit</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control date-picker" id="fromDate" placeholder="Start date">
                                                        <span class="input-group-text bg-light"><i class="mdi mdi-calendar text-primary"></i></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control date-picker" id="toDate" placeholder="End date">
                                                        <span class="input-group-text bg-light"><i class="mdi mdi-calendar text-primary"></i></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                                        <button type="button" class="btn btn-outline-primary btn-sm" id="setToday">
                                                            <i class="mdi mdi-calendar-today me-1"></i> Today
                                                        </button>
                                                        <button id="exportToPdf" class="btn btn-warning btn-sm">
                                                            <i class="fas fa-file-pdf me-1"></i> PDF
                                                        </button>
                                                        <button type="button" class="btn btn-primary btn-sm" id="searchBtn">
                                                            <i class="mdi mdi-magnify me-1"></i> Search
                                                        </button>
                                                        <button type="button" class="btn btn-secondary btn-sm" id="resetBtn">
                                                            <i class="mdi mdi-refresh me-1"></i> Reset
                                                        </button>
                                                    </div>
                                                    <div class="mt-1">
                                                        <small class="text-muted"><i class="mdi mdi-information-outline me-1"></i> Leave dates empty for all records</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <table id="reportTable" class="table table-bordered dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>Customer</th>
                                                <th>Total Invoices</th>
                                                <th class="text-end">Invoice Amount</th>
                                                <th class="text-end">Paid Amount</th>
                                                <th class="text-end outstanding-column">Outstanding</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reportTableBody"></tbody>
                                        <tfoot>
                                            <tr>
                                                <th class="text-end">Total:</th>
                                                <th id="totalInvoices" class="text-danger text-end">0</th>
                                                <th id="totalInvoice" class="text-danger text-end">0.00</th>
                                                <th id="totalPaid" class="text-danger text-end">0.00</th>
                                                <th id="totalOutstanding" class="text-danger text-end outstanding-column">0.00</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="invoiceModal" class="modal fade bs-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="invoiceModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="invoiceModalLabel">Customer Invoices</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <table id="reportTable" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Total Invoices</th>
                                        <th class="text-end">Invoice Amount</th>
                                        <th class="text-end">Paid Amount</th>
                                        <th class="text-end outstanding-column">Outstanding</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="reportTableBody"></tbody>
                                <tfoot>
                                    <tr>
                                        <th class="text-end">Total:</th>
                                        <th id="totalInvoices" class="text-danger text-end">0</th>
                                        <th id="totalInvoice" class="text-danger text-end">0.00</th>
                                        <th id="totalPaid" class="text-danger text-end">0.00</th>
                                        <th id="totalOutstanding" class="text-danger text-end outstanding-column">0.00</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>
    <?php include 'main-js.php'; ?>
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/moment/min/moment.min.js"></script>
    <script src="assets/libs/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="ajax/js/customer-outstanding-summary.js"></script>
</body>

</html>