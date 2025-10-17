/**
 * Customer Outstanding Report JS
 * Handles customer selection and report data loading
 */

$(document).ready(function () {
  // Initialize DataTable for customer selection
  if ($.fn.DataTable.isDataTable("#customerTable")) {
    $("#customerTable").DataTable().destroy();
  }

  const customerTable = $("#customerTable").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "ajax/php/customer-master.php",
      type: "POST",
      data: function (d) {
        d.filter = true;
        d.category = "1"; // Filter for category = 1 only
        d.status = "active"; // Keep the active status filter
      },
      // No need to modify customer table styling here
      dataSrc: function (json) {
        console.log("Server response:", json); // Log the server response
        if (json && json.data) {
          return json.data;
        }
        return [];
      },
      error: function (xhr, error, thrown) {
        console.error("DataTables error:", error);
        console.error("Server response:", xhr.responseText);
      },
    },
    // Update the columns configuration to handle is_vat properly
    columns: [
      { data: "id" },
      { data: "code" },
      { data: "name" },
      { data: "mobile_number" },
      { data: "email" },
      { data: "category" },
      { data: "credit_limit" }, // Credit Discount
      { data: "outstanding" }, // Outstanding amount
      {
        data: "is_vat",
        render: function (data) {
          return data === 1 || data === "1" ? "Yes" : "No";
        },
      },
      {
        data: "status_label",
        orderable: false,
      },
    ],
    order: [[0, "desc"]],
    pageLength: 10,
    responsive: true,
    createdRow: function (row, data, index) {
      // Ensure styling is not accidentally applied to email; target credit column (index 5)
      $("td:eq(5)", row).removeClass("text-danger");
    },
    // Enable server-side processing parameters
    serverParams: function (data) {
      // Map DataTables parameters to server-side parameters
      data.start = data.start || 0;
      data.length = data.length || 10;
      if (data.search && data.search.value) {
        data.search = data.search.value;
      }
    },
    // Handle server response
    error: function (xhr, error, thrown) {
      console.error("DataTables error:", error);
      console.error("Server response:", xhr.responseText);
      // Display a user-friendly error message
      alert(
        "An error occurred while loading the data. Please check the console for details."
      );
    },
  });

  // Handle customer selection from the modal
  $("#customerTable tbody").on("click", "tr", function () {
    const data = customerTable.row(this).data();
    if (data) {
      $("#customer_id").val(data.id);
      $("#customer_code").val(data.code);
      $("#customer_name").val(data.name);
      $("#customerModal").modal("hide");
      // Automatically load report after customer selection
      loadReportData();
    }
  });

  // Toggle between customer and date filters
  $('input[name="filterType"]').on("change", function () {
    if ($(this).val() === "customer") {
      $("#customerFilter").show();
      $("#dateFilter").hide();
    } else {
      $("#customerFilter").hide();
      $("#dateFilter").show();
    }
  });

  // Set default dates
  const today = new Date();
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
  $("#fromDate").val(formatDate(firstDay));
  $("#toDate").val(formatDate(today));

  // Format date to YYYY-MM-DD
  function formatDate(date) {
    const d = new Date(date);
    let month = "" + (d.getMonth() + 1);
    let day = "" + d.getDate();
    const year = d.getFullYear();

    if (month.length < 2) month = "0" + month;
    if (day.length < 2) day = "0" + day;

    return [year, month, day].join("-");
  }

  // Search button click handler
  $("#searchBtn").on("click", function () {
    loadReportData();
  });

  // Reset button click handler
  $("#resetBtn").on("click", function () {
    $("#reportForm")[0].reset();
    $("#customer_id").val("");
    $("#customer_code").val("");
    $("#reportTableBody").empty();
    $("[id^=total]").text("0.00");
  });

  // Load report data via AJAX
  function loadReportData() {
    const customerId = $("#customer_id").val();
    const fromDate = $("#fromDate").val();
    const toDate = $("#toDate").val();

    console.log("Customer ID:", customerId);
    console.log("From Date:", fromDate);
    console.log("To Date:", toDate);

    // If no filters are provided, show all records
    if (!customerId && (!fromDate || !toDate)) {
      console.log("No filters applied, showing all records");
    }

    const requestData = {
      action: "get_outstanding_report",
      customer_id: customerId || "",
      from_date: fromDate || "",
      to_date: toDate || "",
    };

    console.log("Sending request with data:", requestData);

    $.ajax({
      url: "ajax/php/customer-outstanding-report.php",
      type: "POST",
      dataType: "json",
      data: requestData,
      beforeSend: function () {
        console.log("Sending request...");
        $("#reportTableBody").html(
          '<tr><td colspan="7" class="text-center">Loading...</td></tr>'
        );
      },
      success: function (response) {
        console.log("Server response:", response);
        if (response && response.status === "success") {
          renderReportData(response.data);
        } else {
          const errorMsg =
            response && response.message
              ? response.message
              : "Error loading data";
          console.error("Error response:", errorMsg);
          alert(errorMsg);
          $("#reportTableBody").html(
            '<tr><td colspan="7" class="text-center">No data found</td></tr>'
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", {
          status: status,
          error: error,
          response: xhr.responseText,
        });
        alert("Error loading data. Please check console for details.");
        $("#reportTableBody").html(
          '<tr><td colspan="7" class="text-center">Error loading data</td></tr>'
        );
      },
      complete: function () {
        console.log("Request completed");
      },
    });
  }

  // Render report data in table
  function renderReportData(data) {
    const tbody = $("#reportTableBody");
    tbody.empty();

    if (!data || data.length === 0) {
      tbody.html(
        '<tr><td colspan="7" class="text-center">No records found</td></tr>'
      );
      $("[id^=total]").text("0.00");
      return;
    }

    let totalInvoice = 0;
    let totalPaid = 0;
    let totalOutstanding = 0;

    data.forEach(function (item) {
      // Calculate row highlighting based on days until due
      const daysUntilDue = parseInt(item.days_until_due || 0);
      let rowClass = "";
      let dueDateClass = "due-date-cell";
      let dueDateText = item.due_date || "N/A";

      if (daysUntilDue < 0) {
        // Overdue
        rowClass = "overdue-row";
        dueDateClass += " overdue-text";
        dueDateText += ` (${Math.abs(daysUntilDue)} days overdue)`;
      } else if (daysUntilDue <= 2) {
        // Due within 2 days (including today)
        rowClass = "due-soon-row";
        dueDateClass += " due-soon-text";
        if (daysUntilDue === 0) {
          dueDateText += " (Due Today)";
        } else {
          dueDateText += ` (Due in ${daysUntilDue} day${
            daysUntilDue > 1 ? "s" : ""
          })`;
        }
      }

      const row = `
                <tr class="${rowClass}">
                    <td>${item.invoice_no || ""}</td>
                    <td>${item.customer_name || ""}${
        item.mobile_number ? " - " + item.mobile_number : ""
      }</td>
                    <td>${item.invoice_date || ""}</td>
                    <td class="${dueDateClass}">${dueDateText}</td>
                    <td class="text-end">${parseFloat(
                      item.invoice_amount || 0
                    ).toFixed(2)}</td>
                    <td class="text-end">${parseFloat(
                      item.paid_amount || 0
                    ).toFixed(2)}</td>
                    <td class="text-end text-danger" style="background-color: #ffebee;">${parseFloat(
                      item.outstanding || 0
                    ).toFixed(2)}</td>
                </tr>`;

      tbody.append(row);

      totalInvoice += parseFloat(item.invoice_amount || 0);
      totalPaid += parseFloat(item.paid_amount || 0);
      totalOutstanding += parseFloat(item.outstanding || 0);
    });

    // Update totals
    $("#totalInvoice").text(totalInvoice.toFixed(2));
    $("#totalPaid").text(totalPaid.toFixed(2));
    $("#totalOutstanding")
      .text(totalOutstanding.toFixed(2))
      .attr(
        "style",
        "background-color: #eb4034 !important; color: #ffffff !important;"
      );
  }

  $("#exportToPdf").on("click", function () {
    const buttonText = $(this).text().trim();

    // Show loading state
    const originalText = $(this).html();
    $(this).html(
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Exporting...'
    );
    $(this).prop("disabled", true);

    // Get current filter values for outstanding report
    const customerId = $("#customer_id").val();
    const fromDate = $("#fromDate").val();
    const toDate = $("#toDate").val();

    // Make AJAX request for outstanding report data
    $.ajax({
      url: "ajax/php/customer-outstanding-report.php",
      type: "POST",
      dataType: "json",
      data: {
        action: "get_outstanding_report",
        customer_id: customerId || "",
        from_date: fromDate || "",
        to_date: toDate || "",
      },
      success: function (response) {
        if (response && response.status === "success") {
          const data = response.data;

          if (data && data.length > 0) {
            exportOutstandingToPdf(data);
          } else {
            alert("No data available for export");
          }
        } else {
          alert(
            "Failed to retrieve export data: " +
              (response.message || "Unknown error")
          );
        }
      },
      error: function (xhr, status, error) {
        alert("Export failed: " + error);
      },
      complete: function () {
        // Restore button state
        $("#exportToPdf").prop("disabled", false);
      },
    });
  });

  // Function to export outstanding report data to PDF
  function exportOutstandingToPdf(data) {
    const customerName = $("#customer_name").val();
    const deptName = customerName ? `Customer Outstanding Report - ${customerName}` : "Customer Outstanding Report";

    let html = `
  <!DOCTYPE html>
  <html>
  <head>
      <meta charset="utf-8">
      <title>Customer Outstanding Report - ${deptName}</title>
      <style>
          @page { margin: 20px; }
          body { 
              font-family: 'Arial', sans-serif; 
              margin: 0;
              padding: 0;
              color: #333;
              line-height: 1.4;
          }
          .header { 
              text-align: center; 
              margin-bottom: 30px;
              padding-bottom: 15px;
              border-bottom: 2px solid #eee;
          }
          .header h1 { 
              margin: 0 0 10px 0; 
              color: #2c3e50;
              font-size: 24px;
          }
          .header p { 
              margin: 5px 0; 
              color: #7f8c8d;
              font-size: 14px;
          }
          .report-title {
              font-size: 18px;
              font-weight: bold;
              margin-bottom: 5px;
          }
          table { 
              width: 100%; 
              border-collapse: collapse; 
              margin: 15px 0;
              font-size: 13px;
              page-break-inside: auto;
          }
          th, td { 
              border: 1px solid #e0e0e0; 
              padding: 10px 12px; 
              text-align: left; 
              vertical-align: top;
          }
          thead th {
              background-color: #f8f9fa;
              color: #2c3e50;
              font-weight: 600;
              text-transform: uppercase;
              font-size: 12px;
              padding: 12px;
          }
          .text-right { text-align: right; }
          .text-center { text-align: center; }
          .summary { 
              margin: 30px 0; 
              padding: 20px; 
              background-color: #f8f9fa; 
              border-radius: 4px;
              border-left: 4px solid #3498db;
          }
          .footer { 
              margin-top: 40px; 
              padding-top: 20px;
              text-align: center; 
              font-size: 11px; 
              color: #7f8c8d;
              border-top: 1px solid #eee;
          }
          .overdue-row {
              background-color: #f8d7da;
          }
          .due-soon-row {
              background-color: #fff3cd;
          }
          .overdue-text {
              color: #721c24;
              font-weight: bold;
          }
          .due-soon-text {
              color: #856404;
              font-weight: bold;
          }
      </style>
  </head>
  <body>
      <div class="header">
          <div class="report-title">Customer Outstanding Report - ${deptName}</div>
          <p>Generated on ${new Date().toLocaleString()}</p>
      </div>

      <table>
          <thead>
              <tr>
                  <th>Invoice No</th>
                  <th>Customer</th>
                  <th>Date</th>
                  <th>Due Date</th>
                  <th class="text-right">Invoice Amount</th>
                  <th class="text-right">Paid Amount</th>
                  <th class="text-right">Outstanding</th>
              </tr>
          </thead>
          <tbody>`;

    data.forEach((item) => {
      let rowClass = "";
      let dueDateText = item.due_date || "N/A";
      const daysUntilDue = parseInt(item.days_until_due || 0);

      if (daysUntilDue < 0) {
        rowClass = "overdue-row";
        dueDateText += ` (${Math.abs(daysUntilDue)} days overdue)`;
      } else if (daysUntilDue <= 2) {
        rowClass = "due-soon-row";
        if (daysUntilDue === 0) {
          dueDateText += " (Due Today)";
        } else {
          dueDateText += ` (Due in ${daysUntilDue} day${daysUntilDue > 1 ? 's' : ''})`;
        }
      }

      html += `
              <tr class="${rowClass}">
                  <td>${item.invoice_no || "-"}</td>
                  <td>${item.customer_name || "-"} ${item.mobile_number ? ' - ' + item.mobile_number : ''}</td>
                  <td>${item.invoice_date || "-"}</td>
                  <td>${dueDateText}</td>
                  <td class="text-right">${parseFloat(item.invoice_amount || 0).toFixed(2)}</td>
                  <td class="text-right">${parseFloat(item.paid_amount || 0).toFixed(2)}</td>
                  <td class="text-right">${parseFloat(item.outstanding || 0).toFixed(2)}</td>
              </tr>`;
    });

    html += `
          </tbody>
      </table>

      <div class="summary">
          <h3 style="margin-top: 0; color: #2c3e50;">Report Summary</h3>
          <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
              <div style="flex: 1; min-width: 200px; margin: 5px 0;">
                  <div style="font-size: 13px; color: #7f8c8d;">Total Invoices</div>
                  <div style="font-size: 24px; font-weight: 600; color: #2c3e50;">${data.length}</div>
              </div>
              <div style="flex: 1; min-width: 200px; margin: 5px 0;">
                  <div style="font-size: 13px; color: #7f8c8d;">Total Outstanding</div>
                  <div style="font-size: 24px; font-weight: 600; color: #2c3e50;">
                      ${data.reduce((sum, item) => sum + parseFloat(item.outstanding || 0), 0).toFixed(2)}
                  </div>
              </div>
              <div style="flex: 1; min-width: 200px; margin: 5px 0;">
                  <div style="font-size: 13px; color: #7f8c8d;">Report Generated</div>
                  <div style="font-size: 14px; font-weight: 500; color: #2c3e50;">${new Date().toLocaleString()}</div>
              </div>
          </div>
      </div>

      <div class="footer">
          <p>This report was generated by the Customer Outstanding Management System</p>
          <p style="margin: 5px 0 0 0; font-size: 10px; color: #bdc3c7;">Page <span class="pageNumber"></span> of <span class="totalPages"></span></p>
      </div>
  </body>
  </html>`;

    // Create a new window with the HTML content
    const printWindow = window.open("", "_blank");
    printWindow.document.write(html);
    printWindow.document.close();

    // Wait for content to load, then print (which will trigger PDF download in most browsers)
    printWindow.onload = function () {
      try {
        printWindow.print();
        printWindow.onafterprint = function () {
          printWindow.close();
        };
        console.log("PDF export completed successfully.");
      } catch (error) {
        console.error("Error during PDF print:", error);
        alert("PDF export failed. Please check the console for details.");
        printWindow.close();
      }
    };
  }
});
