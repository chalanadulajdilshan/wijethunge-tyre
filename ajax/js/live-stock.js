jQuery(document).ready(function () {
    // Define department colors - add more colors if you have more departments
    const departmentColors = {
        1: '#d1e7ff', // Light Blue
        2: '#fff3cd', // Light Yellow
        3: '#f8d7da', // Light Red
        4: '#d1e7dd', // Light Green
        5: '#e2d9f3', // Light Purple
        6: '#fff8e1', // Light Orange
        7: '#e0f7fa', // Light Cyan
        8: '#f3e5f5'  // Light Pink
    };

    // Function to get department color
    function getDepartmentColor(departmentId) {
        // If we have a color defined for this department ID, return it
        if (departmentColors[departmentId]) {
            return departmentColors[departmentId];
        }
        // For departments without a defined color, generate a consistent color based on the ID
        const colors = Object.values(departmentColors);
        return colors[departmentId % colors.length] || '#f8f9fa'; // Default light gray
    }

    // Initialize DataTable with server-side processing
    var table = $('#stockTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "ajax/php/item-master.php",
            type: "POST",
            data: function (d) {
                d.filter = true;
                d.status = 1; // Only show active items
                d.stock_only = true; // Only show items with stock tracking enabled
                const depVal = $('#filter_department_id').val();
                d.department_id = depVal; // Get selected department ("all" becomes 0 on server)
                d.expand_departments = (depVal === 'all'); // Expand into per-department rows when All is selected
            },
            beforeSend: function () {
                try {
                    var $target = $(".someBlock");
                    if ($target.length && typeof $target.preloader === 'function') {
                        $target.preloader();
                    } else {
                        var $wrap = $('#stockTable').closest('.dataTables_wrapper');
                        if ($wrap.length && typeof $wrap.preloader === 'function') {
                            $wrap.preloader();
                        }
                    }
                } catch (e) { /* noop */ }
            },
            dataSrc: function (json) {
                return json.data;
            },
            error: function (xhr) {
                console.error("Server Error Response:", xhr.responseText);
            },
            complete: function () {
                try {
                    var $target = $(".someBlock");
                    if ($target.length && typeof $target.preloader === 'function') {
                        $target.preloader('remove');
                    }
                    var $wrap = $('#stockTable').closest('.dataTables_wrapper');
                    if ($wrap.length && typeof $wrap.preloader === 'function') {
                        $wrap.preloader('remove');
                    }
                } catch (e) { /* noop */ }
            }
        },
        columns: [
            { 
                data: null,
                title: "",
                className: 'details-control',
                orderable: false,
                defaultContent: '<span class="mdi mdi-plus-circle-outline" style="font-size:18px; cursor:pointer;"></span>',
                width: '30px'
            },
            { data: "code", title: "Item Code" },
            { data: "name", title: "Item Description" },
            { 
                data: null,
                title: "Department",
                render: function(data, type, row) {
                    const departmentId = $('#filter_department_id').val();
                    // If expanded (All selected), show the department for this row
                    if (departmentId === 'all' || departmentId === '' || departmentId === null) {
                        const depId = row.row_department_id || (row.department_stock && row.department_stock[0] ? row.department_stock[0].department_id : null);
                        if (depId !== null && depId !== undefined) {
                            const name = $('#filter_department_id option[value="' + depId + '"]').text();
                            return name || ('Dept ' + depId);
                        }
                        return 'All Departments';
                    }
                    // Otherwise show the selected department name
                    return $('#filter_department_id option:selected').text();
                }
            },
            { data: "category", title: "Category" },
            
            {
                data: "list_price",
                title: "Selling",
                render: function (data, type, row) {
                    return parseFloat(data || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
            },
            {
                data: "discount",
                title: "Dealer Price",
                render: function (data, type, row) {
                    if (row.list_price && data) {
                        const dealerPrice = parseFloat(row.list_price) * (1 - parseFloat(data) / 100);
                        return dealerPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                    return '0.00';
                }
            },
            {
                data: "department_stock",
                title: "Quantity",
                render: function (data, type, row) {
                    const departmentId = $('#filter_department_id').val();
                    // If expanded (All selected), use row's department qty
                    if (departmentId === 'all' || departmentId === '' || departmentId === null) {
                        const q = row.row_department_qty != null ? row.row_department_qty : (row.available_qty || 0);
                        return parseFloat(q || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                    // Otherwise, show quantity for the selected department
                    if (data && data.length > 0) {
                        const stock = data.find(s => s.department_id == departmentId);
                        return stock ? parseFloat(stock.quantity || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0.00';
                    }
                    return '0.00';
                }
            },
            {
                data: "status",
                title: "Stock Status",
                render: function (data, type, row) {
                    const departmentId = $('#filter_department_id').val();
                    let quantity = 0;

                    if (departmentId === 'all' || departmentId === '' || departmentId === null) {
                        // Use per-department qty for the expanded row
                        quantity = parseFloat(row.row_department_qty != null ? row.row_department_qty : (row.available_qty || 0));
                    } else if (row.department_stock && row.department_stock.length > 0) {
                        const stock = row.department_stock.find(s => s.department_id == departmentId);
                        quantity = stock ? parseFloat(stock.quantity) : 0;
                    }

                    const reorderLevel = parseFloat(row.re_order_level) || 0;
                    const isLowStock = quantity <= reorderLevel && quantity > 0;
                    const isOutOfStock = quantity <= 0;

                    let statusText = '';
                    let statusClass = '';

                    if (isOutOfStock) {
                        statusText = 'Out of Stock';
                        statusClass = 'danger';
                    } else if (isLowStock) {
                        statusText = 'Re-order';
                        statusClass = 'warning';
                    } else {
                        statusText = 'In Stock';
                        statusClass = 'success';
                    }

                    return `<span class="badge bg-soft-${statusClass} font-size-12">${statusText}</span>`;
                },
                orderable: false
            }
        ],
        order: [[2, 'asc']], // Default sort by item name (shifted due to details column)
        lengthMenu: [10, 25, 50, 100],
        pageLength: 25,
        responsive: true,
        language: {
            paginate: {
                previous: "<i class='mdi mdi-chevron-left'>",
                next: "<i class='mdi mdi-chevron-right'>"
            }
        },
        createdRow: function(row, data, dataIndex) {
            // Only apply colors when viewing 'All Departments'
            const departmentFilter = $('#filter_department_id').val();
            if (departmentFilter === 'all' || departmentFilter === '' || departmentFilter === null) {
                // Get the department ID for this row
                let departmentId = 0;
                if (data.row_department_id !== undefined) {
                    departmentId = data.row_department_id;
                } else if (data.department_stock && data.department_stock[0]) {
                    departmentId = data.department_stock[0].department_id;
                }
                
                // Apply background color based on department ID
                if (departmentId) {
                    $(row).css('background-color', getDepartmentColor(parseInt(departmentId)));
                }
            }
        },
        drawCallback: function() {
            // Any draw callbacks can go here
        },
        drawCallback: function () {
            $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
        }
    });

    // Make rows appear clickable
    $('#stockTable tbody').css('cursor', 'pointer');

    // Function to load summary totals
    function loadSummaryTotals(departmentId = 'all') {
        const data = { action: 'get_totals' };
        if (departmentId !== 'all' && departmentId !== '') {
            data.department_id = departmentId;
        }
        $.ajax({
            url: 'ajax/php/item-master.php',
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function(resp) {
                if (resp && resp.status === 'success') {
                    const data = resp.data;
                    $('#total-cost').text('Rs. ' + parseFloat(data.total_cost || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $('#total-invoice').text('Rs. ' + parseFloat(data.total_invoice || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $('#profit-percentage').text(parseFloat(data.profit_percentage || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '%');
                } else {
                    $('#total-cost').text('Error');
                    $('#total-invoice').text('Error');
                    $('#profit-percentage').text('Error');
                }
            },
            error: function() {
                $('#total-cost').text('Error');
                $('#total-invoice').text('Error');
                $('#profit-percentage').text('Error');
            }
        });
    }

    // Load summary totals on page load
    loadSummaryTotals();

    // Format function for row details (ARN-wise Last Price and Invoice Price)
    function renderArnWiseTable(lots) {
        if (!Array.isArray(lots) || lots.length === 0) {
            return '<div class="p-2 text-muted">No ARN lots available</div>';
        }
        let html = '<div class="table-responsive"><table class="table table-sm table-bordered mb-0">';
        html += '<thead class="table-light"><tr>'+
                '<th>ARN No</th>'+
                '<th class="text-end">Cost</th>'+
                '<th class="text-end">Qty</th>'+
                '<th class="text-end">Total</th>'+
                '<th class="text-end">List Price</th>'+
                '<th class="text-end">Invoice Price</th>'+
                '<th class="text-end">Total</th>'+
            '</tr></thead><tbody>';
        lots.forEach(function(l){
            html += '<tr>'+
                '<td>'+(l.arn_no || '-')+'</td>'+
                '<td class="text-end">'+Number(l.cost || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})+'</td>'+
                '<td class="text-end">'+Number(l.qty || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})+'</td>'+
                '<td class="text-end">'+Number((l.cost || 0) * (l.qty || 0)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})+'</td>'+
                '<td class="text-end">'+Number(l.list_price || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})+'</td>'+
                '<td class="text-end">'+Number(l.invoice_price || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})+'</td>'+
                '<td class="text-end">'+Number((l.invoice_price || 0) * (l.qty || 0)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})+'</td>'+
            '</tr>';
        });
        html += '</tbody></table></div>';
        return html;
    }

    // Toggle details on click of first column
    $('#stockTable tbody').on('click', 'td.details-control', function (e) {
        e.stopPropagation();
        var tr = $(this).closest('tr');
        var row = table.row(tr);
        var icon = $(this).find('span.mdi');

        if (row.child.isShown()) {
            // Close
            row.child.hide();
            tr.removeClass('shown');
            icon.removeClass('mdi-minus-circle-outline').addClass('mdi-plus-circle-outline');
        } else {
            // Open
            const data = row.data();
            // Show temporary loading content
            const loading = '<div class="p-2 text-muted">Loading ARN lots...</div>';
            row.child(loading).show();
            tr.addClass('shown');
            icon.removeClass('mdi-plus-circle-outline').addClass('mdi-minus-circle-outline');

            // Resolve department id context for this row
            let depId = null;
            const filterVal = $('#filter_department_id').val();
            if (filterVal && filterVal !== 'all') {
                depId = parseInt(filterVal);
            } else if (data.row_department_id) {
                depId = parseInt(data.row_department_id);
            } else if (Array.isArray(data.department_stock) && data.department_stock.length > 0) {
                depId = parseInt(data.department_stock[0].department_id);
            }

            // Fetch fresh lots by item_id (and department if available)
            $.ajax({
                url: 'ajax/php/item-master.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'get_stock_tmp_by_item', item_id: data.id, department_id: depId || 0 },
                success: function(resp) {
                    if (resp && resp.status === 'success') {
                        row.child(renderArnWiseTable(resp.data)).show();
                    } else {
                        row.child('<div class="p-2 text-muted">No ARN lots available</div>').show();
                    }
                },
                error: function() {
                    row.child('<div class="p-2 text-danger">Failed to load ARN lots</div>').show();
                }
            });
        }
    });

    // Department filter change handler
    $('#filter_department_id').on('change', function () {
        const depId = $(this).val();
        loadSummaryTotals(depId);
        table.ajax.reload();
    });

    // Row click: navigate to sales-invoice with prefilled item and department
    $('#stockTable tbody').on('click', 'tr', function () {
        const rowData = table.row(this).data();
        if (!rowData) return;

        const depFilter = $('#filter_department_id').val();
        let depId = null;
        if (depFilter === 'all' || depFilter === '' || depFilter === null) {
            depId = rowData.row_department_id || (rowData.department_stock && rowData.department_stock[0] ? rowData.department_stock[0].department_id : null);
        } else {
            depId = depFilter;
        }

        const itemCode = rowData.code;
        if (itemCode) {
            // Prefer item-master page_id injected from PHP, fallback to navigation anchor
            let pageId = (typeof window !== 'undefined' && window.ITEM_MASTER_PAGE_ID && Number(window.ITEM_MASTER_PAGE_ID) > 0)
                ? Number(window.ITEM_MASTER_PAGE_ID)
                : null;

            if (!pageId) {
                const itemMasterAnchor = $('a[href*="item-master.php"][href*="page_id="]').first().attr('href');
                if (itemMasterAnchor) {
                    try {
                        const linkUrl = new URL(itemMasterAnchor, window.location.origin);
                        const pid = linkUrl.searchParams.get('page_id');
                        if (pid) {
                            pageId = Number(pid);
                        }
                    } catch (e) {
                        const match = itemMasterAnchor.match(/[?&]page_id=([^&]+)/);
                        if (match && match[1]) {
                            pageId = Number(match[1]);
                        }
                    }
                }
            }

            // If we cannot detect a valid page_id, don't navigate. Show a message instead.
            if (!pageId || isNaN(pageId) || pageId <= 0) {
                if (typeof swal === 'function') {
                    swal({
                        title: "No Permission",
                        text: "You don't have permission to open Item Master.",
                        type: "warning",
                        timer: 2200,
                        showConfirmButton: false
                    });
                } else {
                    alert("You don't have permission to open Item Master.");
                }
                return;
            }

            const itemId = rowData.id;
            const url = `item-master.php?page_id=${pageId}&from=live_stock&prefill_item_id=${itemId || ''}&prefill_item_code=${encodeURIComponent(itemCode)}${depId ? `&department_id=${depId}` : ''}`;
            window.location.href = url;
        }
    });

    // Initialize department select2 if it exists
    if ($.fn.select2) {
        $('#filter_department_id').select2({
            placeholder: 'Select Department',
            allowClear: true
        });
    }
});
