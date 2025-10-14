<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';


//doc id get by session 
$DOCUMENT_TRACKING = new DocumentTracking($doc_id);

// Get the last inserted quotation
$lastId = $DOCUMENT_TRACKING->item_id;
$item_id = 'TI/0' . ($lastId + 1);

?>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <title>Item Master | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
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
                                <a href="#" class="btn btn-warning" id="update" hidden>
                                    <i class="uil uil-edit me-1"></i> Update
                                </a>
                            <?php endif; ?>
                            <?php if ($PERMISSIONS['delete_page']): ?>
                                <a href="#" class="btn btn-danger delete-item">
                                    <i class="uil uil-trash-alt me-1"></i> Delete
                                </a>
                            <?php endif; ?>
                            <?php if ($PERMISSIONS['add_page']): ?>
                                <a href="#" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#excelUploadModal">
                                    <i class="uil uil-upload me-1"></i> Upload Excel
                                </a>
                                <a href="sample-items-template.csv" class="btn btn-secondary" download>
                                    <i class="uil uil-download-alt me-1"></i> Download CSV Template
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">Item Master</li>
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
                                            <h5 class="font-size-16 mb-1">Item Master</h5>
                                            <p class="text-muted text-truncate mb-0">Fill all information below to add
                                                Item</p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="mdi mdi-chevron-up accor-down-icon font-size-24"></i>
                                        </div>
                                    </div>

                                </div>

                                <div class="p-4">
                                    <form id="form-data" autocomplete="off">
                                        <div class="row">
                                            <!-- Item Code -->
                                            <div class="col-md-3">
                                                <label class="form-label" for="code">Item Code</label>
                                                <div class="input-group mb-3">
                                                    <input id="code" name="code" type="text" class="form-control"
                                                        value="<?php echo $item_id ?>" readonly>
                                                    <?php if ($PERMISSIONS['search_page']): ?>
                                                        <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                                            data-bs-target="#main_item_master">
                                                            <i class="uil uil-search me-1"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Brand -->
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label" for="brand">Manufacturer Brand <span
                                                            class="text-danger">*</span></label>
                                                    <select id="brand" name="brand" class="form-select">

                                                        <?php
                                                        $BRAND = new Brand(NULL);
                                                        foreach ($BRAND->activeBrands() as $brand) {
                                                            echo "<option value='{$brand['id']}'>{$brand['name']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Size -->
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label" for="size">Item Size <span
                                                            class="text-danger">*</span></label>
                                                    <input id="size" onkeyup="toUpperCaseInput(this)" name="size" type="text" class="form-control"
                                                        placeholder="Enter item size">
                                                </div>
                                            </div>

                                            <!-- Pattern -->
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label" for="pattern">Item Pattern </label>
                                                    <input id="pattern" onkeyup="toUpperCaseInput(this)" name="pattern" type="text" class="form-control"
                                                        placeholder="Enter item pattern">
                                                </div>
                                            </div>

                                            <!-- Item Name -->
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label" for="name">Item Name <span
                                                            class="text-danger">*</span></label>
                                                    <input id="name" name="name" onkeyup="toUpperCaseInput(this)" type="text" class="form-control"
                                                        placeholder="Enter item name">
                                                </div>
                                            </div>

                                            <!-- Group -->
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <label class="form-label" for="group">Item Group <span
                                                            class="text-danger">*</span></label>
                                                    <select id="group" name="group" class="form-select">

                                                        <?php
                                                        $GROUP_MASTER = new GroupMaster(NULL);
                                                        foreach ($GROUP_MASTER->getActiveGroups() as $group) {
                                                            echo "<option value='{$group['id']}'>{$group['name']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Category -->
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label" for="category">Item Category <span
                                                            class="text-danger">*</span></label>
                                                    <select id="category" name="category" class="form-select">

                                                        <?php
                                                        $CATEGORY_MASTER = new CategoryMaster(NULL);
                                                        foreach ($CATEGORY_MASTER->getActiveCategory() as $category) {
                                                            echo "<option value='{$category['id']}'>{$category['name']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <!-- List Price -->
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <label class="form-label" for="list_price"> Customer Price <span
                                                            class="text-danger">*</span></label>
                                                    <input id="list_price" name="list_price" type="text"
                                                        class="form-control list-price" placeholder="Enter item Customer Price">
                                                </div>
                                            </div>
                                            <!-- DIS Column -->
                                            <div class="col-md-2" style="display: none;">
                                                <div class="mb-3">
                                                    <label class="form-label" for="discount">DIS % <span
                                                            class="text-danger">*</span></label>
                                                    <input id="discount" name="discount" type="text"
                                                        class="form-control discount" placeholder="%" value="0">
                                                </div>
                                            </div>
                                            <!-- Invoice Price -->
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <label class="form-label">Dealer Price <span
                                                            class="text-danger">*</span></label>
                                                    <input id="invoice_price" name="invoice_price" type="text"
                                                        class="form-control invoice-price" placeholder="Enter dealer price">
                                                </div>
                                            </div>

                                        </div>

                                        <hr class="my-4">

                                        <div class="row">


                                            <!-- Reorder Level -->
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label" for="re_order_level">Reorder Level</label>
                                                    <input id="re_order_level" name="re_order_level" type="text"
                                                        class="form-control" placeholder="Enter reorder level">
                                                </div>
                                            </div>

                                            <!-- Reorder Qty -->
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label" for="re_order_qty">Reorder Quantity <span
                                                            class="text-danger">*</span></label>
                                                    <input id="re_order_qty" name="re_order_qty" type="text"
                                                        class="form-control" placeholder="Enter reorder quantity">
                                                </div>
                                            </div>

                                            <!-- Stock Type -->
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label" for="stock_type">Stock Type <span
                                                            class="text-danger">*</span></label>
                                                    <select id="stock_type" name="stock_type" class="form-select">

                                                        <?php
                                                        $STOCK_TYPE = new StockType(NULL);
                                                        foreach ($STOCK_TYPE->getActiveStockType() as $stock_type) {
                                                            echo "<option value='{$stock_type['id']}'>{$stock_type['name']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>


                                            <div class="col-md-1 d-flex justify-content-center align-items-center">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="is_active"
                                                        name="is_active" checked>
                                                    <label class="form-check-label" for="is_active">
                                                        Active
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Notes -->
                                        <div class="mb-3">
                                            <label class="form-label" for="note">Item Notes</label>
                                            <textarea class="form-control" id="note" onkeyup="toUpperCaseInput(this)" name="note" rows="4"
                                                placeholder="Enter any additional notes about the item..."></textarea>
                                        </div>
                                        <input type="hidden" name="item_id" id="item_id" />
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

    <!-- Excel Upload Modal -->
    <div class="modal fade" id="excelUploadModal" tabindex="-1" aria-labelledby="excelUploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="excelUploadModalLabel">Upload Items from Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="excelUploadForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="excelFile" class="form-label">Select CSV File</label>
                            <input type="file" class="form-control" id="excelFile" name="excelFile" accept=".csv,.xlsx,.xls" required>
                            <div class="form-text">
                                <strong>Supported formats:</strong> CSV (.csv), Excel (.xlsx, .xls)<br>
                                <strong>Note:</strong> Excel files will need to be converted to CSV format. For best results, use CSV format.<br>
                                <strong>Brand/Group/Category/Stock Type:</strong> You can use either ID numbers (1, 2, 3...) or names (MICHELIN, TYRE, etc.)<br>
                                <strong>Auto Brand Extraction:</strong> If brand column is empty, system extracts from first word of item name (e.g., "MICHELIN 195/65R15" → "MICHELIN")<br>
                                <strong>To find correct IDs:</strong> Visit <a href="debug-ids.php" target="_blank">debug-ids.php</a> to see all available IDs (brands, groups, categories, stock types) in your database.<br>
                                <a href="sample-items-template-detailed.csv" download class="text-primary">Download detailed template with instructions</a>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="skipDuplicates" name="skipDuplicates" checked>
                                <label class="form-check-label" for="skipDuplicates">
                                    Skip duplicate items (based on item name)
                                </label>
                            </div>
                        </div>
                        <div id="uploadProgress" class="progress mb-3" style="display: none;">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div id="uploadResult" class="alert" style="display: none;"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="uploadExcelBtn">Upload Items</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>
    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <!-- /////////////////////////// -->
    <script src="ajax/js/item-master.js"></script>
    <script src="ajax/js/common.js"></script>

    <script>
        $(document).ready(function() {
            // Function to update item name
            function updateItemName() {
                const brand = $('#brand option:selected').text().trim();
                const size = $('#size').val().trim();
                const pattern = $('#pattern').val().trim();

                // Combine the values with spaces, but only if they're not empty
                const itemName = [brand, size, pattern].filter(Boolean).join(' ');

                // Update the item name field
                $('#name').val(itemName);
            }

            // Add event listeners to the relevant fields
            $('#brand, #size, #pattern').on('change keyup', updateItemName);

            // Initialize on page load
            updateItemName();

            // Excel upload functionality
            $('#uploadExcelBtn').click(function() {
                const formData = new FormData();
                const fileInput = $('#excelFile')[0];
                
                if (!fileInput.files[0]) {
                    alert('Please select an Excel file');
                    return;
                }
                
                formData.append('excelFile', fileInput.files[0]);
                formData.append('skipDuplicates', $('#skipDuplicates').is(':checked') ? 1 : 0);
                formData.append('upload_excel', 1);
                
                $('#uploadProgress').show();
                $('#uploadResult').hide();
                $('#uploadExcelBtn').prop('disabled', true);
                
                $.ajax({
                    url: 'ajax/php/item-master.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhr: function() {
                        const xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function(evt) {
                            if (evt.lengthComputable) {
                                const percentComplete = evt.loaded / evt.total * 100;
                                $('.progress-bar').css('width', percentComplete + '%');
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(response) {
                        $('#uploadProgress').hide();
                        $('#uploadExcelBtn').prop('disabled', false);
                        
                        if (response.status === 'success') {
                            $('#uploadResult').removeClass('alert-danger').addClass('alert-success').html(
                                '<strong>Success!</strong> ' + response.message
                            ).show();
                            setTimeout(() => {
                                $('#excelUploadModal').modal('hide');
                                location.reload();
                            }, 2000);
                        } else {
                            $('#uploadResult').removeClass('alert-success').addClass('alert-danger').html(
                                '<strong>Error!</strong> ' + response.message
                            ).show();
                        }
                    },
                    error: function() {
                        $('#uploadProgress').hide();
                        $('#uploadExcelBtn').prop('disabled', false);
                        $('#uploadResult').removeClass('alert-success').addClass('alert-danger').html(
                            '<strong>Error!</strong> Failed to upload file'
                        ).show();
                    }
                });
            });
        });
    </script>

    <!-- include main js  -->
    <?php include 'main-js.php' ?>

</body>

</html>