jQuery(document).ready(function() {
    // Initialize date picker
    $("#date").datepicker({
        format: "yyyy-mm-dd",
        autoclose: true,
        todayHighlight: true
    });

    // Load customers on page load
    loadCustomers();

    // Invoice search functionality
    $("#invoice_no").on("change", function() {
        const invoiceNo = $(this).val().trim();
        if (invoiceNo) {
            checkInvoiceExists(invoiceNo);
        }
    });

    // New button functionality
    $("#new").click(function(e) {
        e.preventDefault();
        resetForm();
    });

    // Save button functionality
    $("#create").click(function(e) {
        e.preventDefault();
        saveSalesReturn();
    });

    // Print button functionality
    $("#print").click(function(e) {
        e.preventDefault();
        // Implement print functionality
        alert("Print functionality to be implemented");
    });

    // Delete button functionality
    $(".delete-category").click(function(e) {
        e.preventDefault();
        const returnId = $("#return_id").val();
        if (returnId) {
            deleteSalesReturn(returnId);
        } else {
            alert("Please select a sales return to delete");
        }
    });

    // Invoice modal search
    $("#searchInvoiceBtn").click(function() {
        searchInvoices();
    });

    // Load invoice items when invoice is selected
    $("#invoice_no").on("change", function() {
        const invoiceNo = $(this).val().trim();
        if (invoiceNo) {
            loadInvoiceItems(invoiceNo);
        }
    });
});

// Load customers for dropdown
function loadCustomers() {
    $.ajax({
        url: "ajax/php/customer-master.php",
        type: "POST",
        dataType: "json",
        data: { action: "load_customers" },
        success: function(data) {
            // This would populate customer dropdown if needed
            console.log("Customers loaded:", data);
        },
        error: function(xhr, status, error) {
            console.error("Error loading customers:", error);
        }
    });
}

// Check if invoice exists and load details
function checkInvoiceExists(invoiceNo) {
    $.ajax({
        url: "ajax/php/sales-return.php",
        type: "POST",
        dataType: "json",
        data: {
            action: "check_invoice_exists",
            invoice_no: invoiceNo
        },
        success: function(response) {
            if (response.exists) {
                populateInvoiceData(response.invoice, response.customer, response.department);
            } else {
                alert("Invoice not found!");
                clearInvoiceData();
            }
        },
        error: function(xhr, status, error) {
            console.error("Error checking invoice:", error);
            alert("Error checking invoice. Please try again.");
        }
    });
}

// Populate invoice data in form
function populateInvoiceData(invoice, customer, department) {
    $("#invoice_id").val(invoice.id);
    $("#customer_id").val(customer.id);
    $("#customer_code").val(customer.id); // Assuming customer code is ID
    $("#customer_name").val(customer.name);
    $("#customer_address").val(customer.address);
    $("#department_id").val(department.id).trigger('change');

    // Set payment type and other invoice details
    $("#payment_type").val(invoice.payment_type == 1 ? "Cash" : "Credit");
    $("#invoice_date").val(invoice.invoice_date);

    // Load invoice items
    loadInvoiceItems(invoice.invoice_no);
}

// Clear invoice data
function clearInvoiceData() {
    $("#invoice_id, #customer_id, #customer_code, #customer_name, #customer_address, #payment_type, #invoice_date").val("");
    $("#invoiceItemsBody").html('<tr id="noItemRow"><td colspan="7" class="text-center text-muted">No items added</td></tr>');
    calculateTotals();
}

// Load invoice items
function loadInvoiceItems(invoiceNo) {
    const invoiceId = $("#invoice_id").val();

    $.ajax({
        url: "ajax/php/sales-return.php",
        type: "POST",
        dataType: "json",
        data: {
            action: "fetch_invoice_items",
            invoice_id: invoiceId
        },
        success: function(response) {
            populateInvoiceItems(response.items);
        },
        error: function(xhr, status, error) {
            console.error("Error loading invoice items:", error);
        }
    });
}

// Populate invoice items table
function populateInvoiceItems(items) {
    let html = "";

    if (items.length > 0) {
        items.forEach(function(item, index) {
            const itemSubtotal = (item.quantity * item.unit_price) - (item.discount || 0);

            html += `
                <tr data-item-id="${item.item_id}">
                    <td>${item.item_code}</td>
                    <td>${item.item_name}</td>
                    <td class="text-end">${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td class="text-end">${parseFloat(item.quantity).toFixed(2)}</td>
                    <td>
                        <input type="number" class="form-control item-return-qty" value="0" min="0" step="0.01" max="${item.quantity}" data-max="${item.quantity}" data-price="${item.unit_price}" data-discount="${item.discount || 0}">
                    </td>
                    <td class="text-end item-subtotal">0.00</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger remove-item">
                            <i class="bx bx-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
    } else {
        html = '<tr id="noItemRow"><td colspan="7" class="text-center text-muted">No items found</td></tr>';
    }

    $("#invoiceItemsBody").html(html);
    calculateTotals();
}

// Calculate totals
function calculateTotals() {
    let grandTotal = 0;

    $("#invoiceItemsBody tr").each(function() {
        const row = $(this);
        if (!row.attr('id') || row.attr('id') !== 'noItemRow') {
            const returnQtyInput = row.find(".item-return-qty");
            const returnQty = parseFloat(returnQtyInput.val()) || 0;
            const price = parseFloat(returnQtyInput.data("price")) || 0;
            const discount = parseFloat(returnQtyInput.data("discount")) || 0;

            // Calculate subtotal for this row
            const rowSubtotal = (price * returnQty) - (discount * (returnQty / parseFloat(returnQtyInput.attr("max"))));
            
            // Update the row subtotal display
            row.find(".item-subtotal").text(rowSubtotal.toFixed(2));
            
            grandTotal += rowSubtotal;
        }
    });

    // Update the grand total if you have a field for it
    $("#finalTotal").val(grandTotal.toFixed(2));
}

// Save sales return
function saveSalesReturn() {
    const returnData = {
        return_no: $("#grn_no").val(),
        return_date: $("#date").val(),
        invoice_no: $("#invoice_no").val(),
        customer_id: $("#customer_id").val(),
        total_amount: $("#finalTotal").val(),
        return_reason: $("#remark").val(),
        remarks: $("#remark").val()
    };

    const returnItems = [];
    $("#invoiceItemsBody tr").each(function() {
        const row = $(this);
        if (!row.attr('id') || row.attr('id') !== 'noItemRow') {
            const returnQtyInput = row.find(".item-return-qty");
            const returnQty = parseFloat(returnQtyInput.val()) || 0;
            if (returnQty > 0) {
                const price = parseFloat(returnQtyInput.data("price")) || 0;
                const discount = parseFloat(returnQtyInput.data("discount")) || 0;
                const maxQty = parseFloat(returnQtyInput.attr("max")) || 0;
                const proportionalDiscount = discount * (returnQty / maxQty);
                
                returnItems.push({
                    item_id: row.data("item-id"),
                    quantity: returnQty,
                    unit_price: price,
                    discount: proportionalDiscount,
                    tax: 0,
                    net_amount: (price * returnQty) - proportionalDiscount,
                    remarks: ""
                });
            }
        }
    });

    if (returnItems.length === 0) {
        alert("Please add at least one item to return");
        return;
    }

    $.ajax({
        url: "ajax/php/sales-return.php",
        type: "POST",
        dataType: "json",
        data: {
            action: "save_sales_return",
            return_data: JSON.stringify(returnData),
            return_items: JSON.stringify(returnItems)
        },
        success: function(response) {
            if (response.status === 'success') {
                alert("Sales return saved successfully!");
                resetForm();
            } else {
                alert("Error saving sales return: " + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error saving sales return:", error);
            alert("Error saving sales return. Please try again.");
        }
    });
}

// Reset form
function resetForm() {
    $("#form-data")[0].reset();
    $("#invoiceItemsBody").html('<tr id="noItemRow"><td colspan="7" class="text-center text-muted">No items added</td></tr>');
    calculateTotals();
}

// Search invoices for modal
function searchInvoices() {
    const invoiceNo = $("#searchInvoiceNo").val().trim();

    if (!invoiceNo) {
        alert("Please enter an invoice number");
        return;
    }

    $.ajax({
        url: "ajax/php/sales-return.php",
        type: "POST",
        dataType: "json",
        data: {
            action: "check_invoice_exists",
            invoice_no: invoiceNo
        },
        success: function(response) {
            if (response.exists) {
                const invoice = response.invoice;
                const customer = response.customer;

                let html = `
                    <div class="card">
                        <div class="card-body">
                            <h6>Invoice Details</h6>
                            <p><strong>Invoice No:</strong> ${invoice.invoice_no}</p>
                            <p><strong>Date:</strong> ${invoice.invoice_date}</p>
                            <p><strong>Customer:</strong> ${customer.name}</p>
                            <p><strong>Total:</strong> ${invoice.grand_total}</p>
                            <button type="button" class="btn btn-success" onclick="selectInvoice('${invoice.invoice_no}')">Select This Invoice</button>
                        </div>
                    </div>
                `;

                $("#invoiceSearchResults").html(html);
            } else {
                $("#invoiceSearchResults").html('<div class="alert alert-warning">Invoice not found</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error searching invoice:", error);
            $("#invoiceSearchResults").html('<div class="alert alert-danger">Error searching invoice</div>');
        }
    });
}

// Select invoice from search results
function selectInvoice(invoiceNo) {
    $("#invoice_no").val(invoiceNo);
    $("#invoiceModal").modal('hide');
    checkInvoiceExists(invoiceNo);
}

// Delete sales return
function deleteSalesReturn(returnId) {
    if (confirm("Are you sure you want to delete this sales return?")) {
        $.ajax({
            url: "ajax/php/sales-return.php",
            type: "POST",
            dataType: "json",
            data: {
                action: "delete_sales_return",
                return_id: returnId
            },
            success: function(response) {
                if (response.status === 'success') {
                    alert("Sales return deleted successfully!");
                    resetForm();
                } else {
                    alert("Error deleting sales return: " + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error deleting sales return:", error);
                alert("Error deleting sales return. Please try again.");
            }
        });
    }
}

// Bind calculation events
$(document).on("input", ".item-return-qty", function() {
    // Validate max quantity
    const max = parseFloat($(this).attr("max"));
    const val = parseFloat($(this).val());
    if (val > max) {
        $(this).val(max);
        alert("Return quantity cannot exceed invoice quantity");
    }
    calculateTotals();
});

// Remove item row
$(document).on("click", ".remove-item", function() {
    $(this).closest("tr").remove();
    
    // Check if no items left
    if ($("#invoiceItemsBody tr").length === 0) {
        $("#invoiceItemsBody").html('<tr id="noItemRow"><td colspan="7" class="text-center text-muted">No items added</td></tr>');
    }
    
    calculateTotals();
});
