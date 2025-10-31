<?php
include 'class/include.php';
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>REP Wise Outstanding Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css">
    <style>
        /* Target only the Outstanding column in the report table */
        #reportTable thead th.outstanding-column,
        #reportTable tbody td.outstanding-column {
            background-color: #ffebee !important;
        }

        /* Style for total outstanding cell */
        #totalOutstanding {
            background-color: #eb4034 !important;
            color: #ffffff !important;
        }

        /* Style for rows close to due date (within 2 days) */
        .due-soon-row {
            background-color: #fff3cd !important;
        }

        /* Style for overdue rows */
        .overdue-row {
            background-color: #f8d7da !important;
        }

        /* Due date column styling */
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

        /* REP info card styling */
        .rep-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .rep-info-card h5 {
            margin-bottom: 10px;
            font-weight: 600;
        }

        .rep-info-card .rep-details {
            font-size: 14px;
            opacity: 0.9;
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">

    <!-- Begin page -->
    <div id="layout-wrapper">
        <?php include 'navigation.php'; ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">REP Wise Outstanding Report</h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form id="reportForm">
                                        <div class="row">
                                            <!-- REP Filter -->
                                            <div class="col-md-4">
                                                <label for="repCode" class="form-label">Sales Representative (REP)</label>
                                                <div class="input-group mb-3">
                                                    <input id="rep_code" name="rep_code" type="text"
                                                        placeholder="Select REP" class="form-control" readonly>
                                                    <input type="hidden" id="rep_id" name="rep_id">
                                                    <input type="hidden" id="rep_role" name="rep_role">
                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#repModal">
                                                        <i class="uil uil-search me-1"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Date Filter Section -->
                                            <div class="col-md-8">
                                                <div class="card-body p-3">
                                                    <div class="row g-3 align-items-end">
                                                        <div class="col-md-5">
                                                            <label for="fromDate" class="form-label fw-semibold text-muted mb-2">From Date</label>
                                                            <div class="input-group">
                                                                <input type="text" class="form-control date-picker" id="fromDate" name="fromDate" placeholder="Select start date">
                                                                <span class="input-group-text bg-light"><i class="mdi mdi-calendar text-primary"></i></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <label for="toDate" class="form-label fw-semibold text-muted mb-2">To Date</label>
                                                            <div class="input-group">
                                                                <input type="text" class="form-control date-picker" id="toDate" name="toDate" placeholder="Select end date">
                                                                <span class="input-group-text bg-light"><i class="mdi mdi-calendar text-primary"></i></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2 d-grid">
                                                            <button type="button" class="btn btn-outline-primary btn-sm mb-2" id="setToday">
                                                                <i class="mdi mdi-calendar-today me-1"></i> Today
                                                            </button>
                                                            <button id="exportToPdf" class="btn btn-warning btn-sm">
                                                                <i class="fas fa-file-pdf me-1"></i> Export PDF
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2">
                                                        <small class="text-muted"><i class="mdi mdi-information-outline me-1"></i> Date range is required when REP is selected</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <button type="button" class="btn btn-primary me-1" id="searchBtn">
                                                    <i class="mdi mdi-magnify me-1"></i> Search
                                                </button>
                                                <button type="button" class="btn btn-secondary" id="resetBtn">
                                                    <i class="mdi mdi-refresh me-1"></i> Reset
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- REP Info Card (Hidden by default) -->
                    <div class="row" id="repInfoSection" style="display: none;">
                        <div class="col-12">
                            <div class="rep-info-card">
                                <h5 id="repName">REP Name</h5>
                                <div class="rep-details">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>Code:</strong> <span id="repCodeDisplay">-</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Mobile:</strong> <span id="repMobile">-</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Date Range:</strong> <span id="dateRangeDisplay">-</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Total Customers:</strong> <span id="totalCustomers">0</span>
                                        </div>
                                    </div>
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
                                                <th>Invoice No</th>
                                                <th>Customer</th>
                                                <th>Date</th>
                                                <th>Due Date</th>
                                                <th class="text-end">Invoice Amount</th>
                                                <th class="text-end">Paid Amount</th>
                                                <th class="text-end outstanding-column">Outstanding</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reportTableBody">
                                            <!-- Data will be loaded via AJAX -->
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="4" class="text-end">Total:</th>
                                                <td id="totalInvoice" class="text-danger text-end">0.00</td>
                                                <td id="totalPaid" class="text-danger text-end">0.00</td>
                                                <td id="totalOutstanding" class="text-danger text-end outstanding-column">0.00</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            <?php include 'footer.php'; ?>
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <!-- REP Selection Modal -->
    <div class="modal fade" id="repModal" tabindex="-1" aria-labelledby="repModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="repModalLabel">Select Sales Representative</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table id="repTable" class="table table-bordered dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include 'main-js.php'; ?>

    <!-- Required datatable js -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/moment/min/moment.min.js"></script>
    <script src="assets/libs/daterangepicker/daterangepicker.min.js"></script>
    <!-- jQuery UI Datepicker -->
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <!-- Custom JS for REP Outstanding Report -->
    <script src="ajax/js/rep-wise-outstanding-report.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize the datepicker with proper configuration
            $(".date-picker").datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                yearRange: '1900:2099',
                showButtonPanel: true,
                showOn: 'focus',
                showAnim: 'fadeIn',
                buttonImageOnly: false
            });

            // Set to today's date and first day of month when clicking the Today button
            $('#setToday').click(function(e) {
                e.preventDefault();
                const today = new Date();
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);

                $('#toDate').datepicker('setDate', today);
                $('#fromDate').datepicker('setDate', firstDay);
            });

            // Handle REP selection from modal
            $(document).on('click', '.select-rep', function(e) {
                e.preventDefault();
                const repId = $(this).data('id');
                const repCode = $(this).data('code');
                const repName = $(this).data('name');
                const repMobile = $(this).data('mobile');

                $('#rep_id').val(repId);
                $('#rep_code').val(repCode);
                
                // Update REP info display
                $('#repName').text(repName);
                $('#repCodeDisplay').text(repCode);
                $('#repMobile').text(repMobile || '-');
                
                $('#repModal').modal('hide');
            });

            // Reset form
            $('#resetBtn').click(function() {
                $('#rep_id').val('');
                $('#rep_code').val('');
                $('#fromDate').val('');
                $('#toDate').val('');
                $('#repInfoSection').hide();
                // Clear the table if needed
                $('#reportTableBody').empty();
                $('.text-danger').text('0.00');
            });

            // Validate date range
            $('.date-picker').change(function() {
                const fromDate = $('#fromDate').datepicker('getDate');
                const toDate = $('#toDate').datepicker('getDate');

                if (fromDate && toDate && fromDate > toDate) {
                    alert('From date cannot be after To date');
                    $(this).val('');
                }
            });

            // Initialize with current month as default
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            $('#fromDate').datepicker('setDate', firstDay);
            $('#toDate').datepicker('setDate', today);
        });
    </script>

</body>

</html>
