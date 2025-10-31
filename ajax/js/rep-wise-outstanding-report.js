/**
 * REP Wise Outstanding Report JS
 * Handles REP selection and report data loading
 */

$(document).ready(function () {
  // Initialize DataTable for REP selection
  if ($.fn.DataTable.isDataTable("#repTable")) {
    $("#repTable").DataTable().destroy();
  }

  const repTable = $("#repTable").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "ajax/php/rep-wise-outstanding-report.php",
      type: "POST",
      data: function (d) {
        d.action = "get_reps";
      },
      dataSrc: function (json) {
        console.log("REP Server response:", json);
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
    columns: [
      { data: "code" },
      { data: "full_name" },
      { data: "mobile_number" },
      { 
        data: "role_type",
        render: function (data) {
          if (data === 'marketing_executive') {
            return '<span class="badge bg-info">Marketing</span>';
          } else if (data === 'sales_executive') {
            return '<span class="badge bg-success">Sales</span>';
          }
          return '<span class="badge bg-secondary">Unknown</span>';
        },
      },
      { 
        data: "is_active",
        render: function (data) {
          return data === 1 || data === "1" ? 
            '<span class="badge bg-success">Active</span>' : 
            '<span class="badge bg-danger">Inactive</span>';
        },
      },
      {
        data: null,
        orderable: false,
        render: function (data, type, row) {
          return `<button class="btn btn-sm btn-primary select-rep" 
                    data-id="${row.id}" 
                    data-code="${row.code}" 
                    data-name="${row.full_name}"
                    data-mobile="${row.mobile_number || ''}"
                    data-role="${row.role_type || 'marketing_executive'}">
                    <i class="uil uil-check me-1"></i> Select
                  </button>`;
        },
      },
    ],
    order: [[0, "desc"]],
    pageLength: 10,
    responsive: true,
    error: function (xhr, error, thrown) {
      console.error("DataTables error:", error);
      console.error("Server response:", xhr.responseText);
      alert("An error occurred while loading the REP data. Please check the console for details.");
    },
  });

  // Handle REP selection from the modal
  $("#repTable tbody").on("click", ".select-rep", function () {
    const repId = $(this).data('id');
    const repCode = $(this).data('code');
    const repName = $(this).data('name');
    const repMobile = $(this).data('mobile');
    const repRole = $(this).data('role');

    $("#rep_id").val(repId);
    $("#rep_code").val(repCode);
    
    // Store role type for later use
    $("#rep_role").val(repRole);
    
    // Update REP info display
    $("#repName").text(repName);
    $("#repCodeDisplay").text(repCode);
    $("#repMobile").text(repMobile || '-');
    
    $("#repModal").modal("hide");
    
    // Automatically load report after REP selection if dates are available
    const fromDate = $("#fromDate").val();
    const toDate = $("#toDate").val();
    if (fromDate && toDate) {
      loadReportData();
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
    const repId = $("#rep_id").val();
    const fromDate = $("#fromDate").val();
    const toDate = $("#toDate").val();

    // Validation: REP and date range are required
    if (!repId) {
      alert("Please select a Sales Representative (REP)");
      return;
    }

    if (!fromDate || !toDate) {
      alert("Please select both From Date and To Date");
      return;
    }

    loadReportData();
  });

  // Reset button click handler
  $("#resetBtn").on("click", function () {
    $("#reportForm")[0].reset();
    $("#rep_id").val("");
    $("#rep_code").val("");
    $("#rep_role").val("");
    $("#repInfoSection").hide();
    $("#reportTableBody").empty();
    $("[id^=total]").text("0.00");
    
    // Reset dates to current month
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    $("#fromDate").val(formatDate(firstDay));
    $("#toDate").val(formatDate(today));
  });

  // Load report data via AJAX
  function loadReportData() {
    const repId = $("#rep_id").val();
    const fromDate = $("#fromDate").val();
    const toDate = $("#toDate").val();
    const repRole = $("#rep_role").val() || 'marketing_executive';

    console.log("REP ID:", repId);
    console.log("REP Role:", repRole);
    console.log("From Date:", fromDate);
    console.log("To Date:", toDate);

    // Validation
    if (!repId) {
      alert("Please select a Sales Representative (REP)");
      return;
    }

    if (!fromDate || !toDate) {
      alert("Please select both From Date and To Date");
      return;
    }

    const requestData = {
      action: "get_rep_outstanding_report",
      rep_id: repId,
      rep_role: repRole,
      from_date: fromDate,
      to_date: toDate,
    };

    console.log("Sending request with data:", requestData);

    $.ajax({
      url: "ajax/php/rep-wise-outstanding-report.php",
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
          
          // Show REP info section and update date range display
          $("#repInfoSection").show();
          $("#dateRangeDisplay").text(fromDate + " to " + toDate);
          $("#totalCustomers").text(getUniqueCustomerCount(response.data));
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

  // Get unique customer count from data
  function getUniqueCustomerCount(data) {
    if (!data || data.length === 0) return 0;
    
    const uniqueCustomers = new Set();
    data.forEach(function(item) {
      if (item.customer_name) {
        uniqueCustomers.add(item.customer_name);
      }
    });
    
    return uniqueCustomers.size;
  }

  // Render report data in table
  function renderReportData(data) {
    const tbody = $("#reportTableBody");
    tbody.empty();

    if (!data || data.length === 0) {
      tbody.html(
        '<tr><td colspan="7" class="text-center">No records found for the selected REP and date range</td></tr>'
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
                    <td class="text-end text-danger outstanding-column">${parseFloat(
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

  // Export to PDF functionality
  $("#exportToPdf").on("click", function () {
    const repId = $("#rep_id").val();
    const fromDate = $("#fromDate").val();
    const toDate = $("#toDate").val();
    const repRole = $("#rep_role").val() || 'marketing_executive';

    if (!repId || !fromDate || !toDate) {
      alert("Please select REP and date range before exporting");
      return;
    }

    // Show loading state
    const originalText = $(this).html();
    $(this).html(
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Exporting...'
    );
    $(this).prop("disabled", true);

    // Make AJAX request for REP outstanding report data
    $.ajax({
      url: "ajax/php/rep-wise-outstanding-report.php",
      type: "POST",
      dataType: "json",
      data: {
        action: "get_rep_outstanding_report",
        rep_id: repId,
        rep_role: repRole,
        from_date: fromDate,
        to_date: toDate,
      },
      success: function (response) {
        if (response && response.status === "success") {
          const data = response.data;

          if (data && data.length > 0) {
            exportRepOutstandingToPdf(data);
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
        $("#exportToPdf").html(originalText);
        $("#exportToPdf").prop("disabled", false);
      },
    });
  });

  // Function to export REP outstanding report data to PDF
  function exportRepOutstandingToPdf(data) {
    const repName = $("#repName").text();
    const repCode = $("#repCodeDisplay").text();
    const dateRange = $("#dateRangeDisplay").text();
    const totalCustomers = $("#totalCustomers").text();

    let html = `
  <!DOCTYPE html>
  <html>
  <head>
      <meta charset="utf-8">
      <title>REP Wise Outstanding Report - ${repName}</title>
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
          .rep-info {
              background-color: #f8f9fa;
              padding: 15px;
              border-radius: 5px;
              margin-bottom: 20px;
              border-left: 4px solid #667eea;
          }
          .rep-info h3 {
              margin: 0 0 10px 0;
              color: #2c3e50;
          }
          .rep-info-grid {
              display: grid;
              grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
              gap: 10px;
          }
          .rep-info-item {
              font-size: 14px;
          }
          .rep-info-item strong {
              color: #495057;
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
          <h1>REP Wise Outstanding Report</h1>
          <p>Generated on ${new Date().toLocaleString()}</p>
      </div>

      <div class="rep-info">
          <h3>Sales Representative Details</h3>
          <div class="rep-info-grid">
              <div class="rep-info-item"><strong>Name:</strong> ${repName}</div>
              <div class="rep-info-item"><strong>Code:</strong> ${repCode}</div>
              <div class="rep-info-item"><strong>Date Range:</strong> ${dateRange}</div>
              <div class="rep-info-item"><strong>Total Customers:</strong> ${totalCustomers}</div>
          </div>
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

    const totalOutstanding = data.reduce((sum, item) => sum + parseFloat(item.outstanding || 0), 0);
    const totalInvoiceAmount = data.reduce((sum, item) => sum + parseFloat(item.invoice_amount || 0), 0);
    const totalPaidAmount = data.reduce((sum, item) => sum + parseFloat(item.paid_amount || 0), 0);

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
                  <div style="font-size: 13px; color: #7f8c8d;">Total Invoice Amount</div>
                  <div style="font-size: 18px; font-weight: 600; color: #2c3e50;">${totalInvoiceAmount.toFixed(2)}</div>
              </div>
              <div style="flex: 1; min-width: 200px; margin: 5px 0;">
                  <div style="font-size: 13px; color: #7f8c8d;">Total Paid Amount</div>
                  <div style="font-size: 18px; font-weight: 600; color: #28a745;">${totalPaidAmount.toFixed(2)}</div>
              </div>
              <div style="flex: 1; min-width: 200px; margin: 5px 0;">
                  <div style="font-size: 13px; color: #7f8c8d;">Total Outstanding</div>
                  <div style="font-size: 24px; font-weight: 600; color: #dc3545;">${totalOutstanding.toFixed(2)}</div>
              </div>
          </div>
      </div>

      <div class="footer">
          <p>This report was generated by the REP Wise Outstanding Management System</p>
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
