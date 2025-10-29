$(document).ready(function () {
  // -----------------------------------------------------------------
  // 1. DATE PICKERS
  // -----------------------------------------------------------------
  $(".date-picker").datepicker({
    dateFormat: "yy-mm-dd",
    changeMonth: true,
    changeYear: true,
    yearRange: "1900:2099",
    showButtonPanel: true,
    showOn: "focus",
    showAnim: "fadeIn",
  });

  const today = new Date();
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
  $("#fromDate").datepicker("setDate", firstDay);
  $("#toDate").datepicker("setDate", today);

  // -----------------------------------------------------------------
  // 2. BUTTONS
  // -----------------------------------------------------------------
  $("#setToday").on("click", function (e) {
    e.preventDefault();
    const today = new Date();
    const todayStr = $.datepicker.formatDate('yy-mm-dd', today);
    
    // Set both dates to today
    $("#fromDate").datepicker("setDate", today);
    $("#toDate").datepicker("setDate", today);
    
    // Trigger change event to ensure the date is properly set
    $("#fromDate, #toDate").trigger("change");
    
    // Load the report for today
    loadReportData();
  });

  $("#searchBtn").on("click", loadReportData);
  $("#resetBtn").on("click", function () {
    $("#reportForm")[0].reset();
    $("#fromDate").datepicker("setDate", firstDay);
    $("#toDate").datepicker("setDate", today);
    $("#reportTableBody").empty();
    resetTotals();
    loadReportData();
  });

  // -----------------------------------------------------------------
  // 3. DATE VALIDATION
  // -----------------------------------------------------------------
  $(".date-picker").on("change", function () {
    const from = $("#fromDate").datepicker("getDate");
    const to = $("#toDate").datepicker("getDate");
    if (from && to && from > to) {
      alert("From date cannot be after To date");
      $(this).val("");
    }
  });

  // -----------------------------------------------------------------
  // 4. INITIAL LOAD
  // -----------------------------------------------------------------
  loadReportData();

  // -----------------------------------------------------------------
  // 5. MAIN REPORT (grouped by customer)
  // -----------------------------------------------------------------
  function loadReportData() {
    const paymentType = $("#paymentType").val();
    const fromDate = $("#fromDate").val();
    const toDate = $("#toDate").val();

    $.ajax({
      url: "ajax/php/customer-outstanding-summary.php",
      type: "POST",
      dataType: "json",
      data: {
        action: "get_outstanding_report",
        payment_type: paymentType,
        from_date: fromDate,
        to_date: toDate,
      },
      beforeSend: function () {
        $("#reportTableBody").html(
          '<tr><td colspan="7" class="text-center">Loading...</td></tr>'
        );
        resetTotals();
      },
      success: function (resp) {
        if (resp && resp.status === "success") {
          renderReportData(resp.data);
        } else {
          alert(resp.message || "Error loading data");
          $("#reportTableBody").html(
            '<tr><td colspan="7" class="text-center">No data found</td></tr>'
          );
        }
      },
      error: function (xhr, status, err) {
        console.error("AJAX Error:", {
          status,
          err,
          response: xhr.responseText,
        });
        alert("Error loading data.");
        $("#reportTableBody").html(
          '<tr><td colspan="7" class="text-center">Error</td></tr>'
        );
      },
    });
  }

  // -----------------------------------------------------------------
  // 6. RENDER MAIN TABLE (customer rows)
  // -----------------------------------------------------------------
  function renderReportData(data) {
    const $tbody = $("#reportTableBody");
    $tbody.empty();

    if (!data || data.length === 0) {
      $tbody.html(
        '<tr><td colspan="7" class="text-center">No records found</td></tr>'
      );
      resetTotals();
      return;
    }

    let totInv = 0,
      totAmt = 0,
      totPaid = 0,
      totOut = 0;

    data.forEach(function (c) {
      totInv += c.total_invoices;
      totAmt += parseFloat(c.invoice_amount || 0);
      totPaid += parseFloat(c.paid_amount || 0);
      totOut += parseFloat(c.outstanding || 0);

      const row = `
    <tr class="customer-row" data-customer-id="${c.customer_id}">
        <td>
            <i class="fas fa-plus expand-icon"></i>
            ${c.customer_code || ""} - ${c.customer_name || ""}${
        c.mobile_number ? " - " + c.mobile_number : ""
      }
        </td>
        <td class="text-center">${c.total_invoices || 0}</td>
        <td class="text-end">${parseFloat(c.invoice_amount || 0).toFixed(
          2
        )}</td>
        <td class="text-end">${parseFloat(c.paid_amount || 0).toFixed(2)}</td>
        <td class="text-end outstanding-column">${parseFloat(
          c.outstanding || 0
        ).toFixed(2)}</td>
        <td class="text-center">
            <button class="btn btn-sm btn-info view-invoices">
                <i class="fas fa-eye"></i> View
            </button>
        </td>
    </tr>
    <tr class="invoice-detail-row" style="display:none;">
        <td colspan="6" class="p-0">
            <div class="invoice-detail-container">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice No</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th class="text-end">Invoice Amt</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end outstanding-column">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody class="invoice-list"></tbody>
                </table>
            </div>
        </td>
    </tr>`;
      $tbody.append(row);
    });

    $("#totalInvoices").text(totInv);
    $("#totalInvoice").text(totAmt.toFixed(2));
    $("#totalPaid").text(totPaid.toFixed(2));
    $("#totalOutstanding")
      .text(totOut.toFixed(2))
      .css({ "background-color": "#eb4034", color: "#ffffff" });
  }

  function resetTotals() {
    $("#totalInvoices").text("0");
    $("#totalInvoice").text("0.00");
    $("#totalPaid").text("0.00");
    $("#totalOutstanding")
      .text("0.00")
      .css({ "background-color": "#eb4034", color: "#ffffff" });
  }

  // -----------------------------------------------------------------
  // 7. EXPAND / LOAD INVOICES
  // -----------------------------------------------------------------
  $(document).on("click", ".view-invoices, .expand-icon", function (e) {
    e.stopPropagation();
    const $btn = $(this);
    const $row = $btn.closest(".customer-row");
    const custId = $row.data("customer-id");
    const $detailRow = $row.next(".invoice-detail-row");
    const $tbody = $detailRow.find(".invoice-list");

    if ($detailRow.is(":visible")) {
      $detailRow.hide();
      $row.find(".expand-icon").removeClass("fa-minus").addClass("fa-plus");
      return;
    }

    // Load only once
    if ($tbody.data("loaded")) {
      $detailRow.show();
      $row.find(".expand-icon").removeClass("fa-plus").addClass("fa-minus");
      return;
    }

    const paymentType = $("#paymentType").val();
    const fromDate = $("#fromDate").val();
    const toDate = $("#toDate").val();

    $tbody.html('<tr><td colspan="6" class="text-center">Loading...</td></tr>');

    $.ajax({
      url: "ajax/php/customer-outstanding-summary.php",
      type: "POST",
      dataType: "json",
      data: {
        action: "get_customer_invoices",
        customer_id: custId,
        payment_type: paymentType,
        from_date: fromDate,
        to_date: toDate,
      },
      success: function (resp) {
        if (resp && resp.status === "success") {
          renderInvoiceList($tbody, resp.data);
          $tbody.data("loaded", true);
        } else {
          $tbody.html(
            '<tr><td colspan="6" class="text-center">No invoices found</td></tr>'
          );
        }
      },
      error: function () {
        $tbody.html(
          '<tr><td colspan="6" class="text-center">Error loading invoices</td></tr>'
        );
      },
      complete: function () {
        $detailRow.show();
        $row.find(".expand-icon").removeClass("fa-plus").addClass("fa-minus");
      },
    });
  });

  function renderInvoiceList($tbody, data) {
    $tbody.empty();
    if (!data || data.length === 0) {
      $tbody.html(
        '<tr><td colspan="6" class="text-center">No invoices found</td></tr>'
      );
      return;
    }

    data.forEach(function (i) {
      const days = parseInt(i.days_until_due || 0);
      let rowClass = "",
        dueClass = "due-date-cell",
        dueText = i.due_date || "N/A";

      if (days < 0) {
        rowClass = "overdue-row";
        dueClass += " overdue-text";
        dueText += ` (${Math.abs(days)} days overdue)`;
      } else if (days <= 2) {
        rowClass = "due-soon-row";
        dueClass += " due-soon-text";
        dueText +=
          days === 0
            ? " (Due Today)"
            : ` (Due in ${days} day${days > 1 ? "s" : ""})`;
      }

      const tr = `
                <tr class="${rowClass}">
                    <td>${i.invoice_no || ""}</td>
                    <td>${i.invoice_date || ""}</td>
                    <td class="${dueClass}">${dueText}</td>
                    <td class="text-end">${parseFloat(
                      i.invoice_amount || 0
                    ).toFixed(2)}</td>
                    <td class="text-end">${parseFloat(
                      i.paid_amount || 0
                    ).toFixed(2)}</td>
                    <td class="text-end outstanding-column">${parseFloat(
                      i.outstanding || 0
                    ).toFixed(2)}</td>
                </tr>`;
      $tbody.append(tr);
    });
  }

  // -----------------------------------------------------------------
  // 8. PDF EXPORT (unchanged – works with new data)
  // -----------------------------------------------------------------
  $("#exportToPdf").on("click", function () {
    const $btn = $(this);
    const orig = $btn.html();
    $btn
      .html(
        '<span class="spinner-border spinner-border-sm"></span> Exporting...'
      )
      .prop("disabled", true);

    const paymentType = $("#paymentType").val();
    const fromDate = $("#fromDate").val();
    const toDate = $("#toDate").val();

    $.ajax({
      url: "ajax/php/customer-outstanding-summary.php",
      type: "POST",
      dataType: "json",
      data: {
        action: "get_outstanding_report",
        payment_type: paymentType,
        from_date: fromDate,
        to_date: toDate,
      },
      success: function (resp) {
        if (resp && resp.status === "success" && resp.data.length) {
          exportToPdf(resp.data);
        } else {
          alert(resp.message || "No data to export");
        }
      },
      error: function () {
        alert("Export failed");
      },
      complete: function () {
        $btn.html(orig).prop("disabled", false);
      },
    });
  });
  // PDF export function
  function exportToPdf(data) {
    const reportTitle = "Customer Outstanding Report";
    let html = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>${reportTitle}</title>
            <style>
                @page { margin: 20px; }
                body { font-family: Arial, sans-serif; margin: 0; padding: 0; color: #333; line-height: 1.4; }
                .header { text-align: center; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 2px solid #eee; }
                .header h1 { margin: 0 0 10px 0; color: #2c3e50; font-size: 24px; }
                .header p { margin: 5px 0; color: #7f8c8d; color: #7f8c8d; font-size: 14px; }
                .report-title { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 13px; page-break-inside: auto; }
                th, td { border: 1px solid #e0e0e0; padding: 10px 12px; text-align: left; vertical-align: top; }
                thead th { background-color: #f8f9fa; color: #2c3e50; font-weight: 600; text-transform: uppercase; font-size: 12px; padding: 12px; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .summary { margin: 30px 0; padding: 20px; background-color: #f8f9fa; border-radius: 4px; border-left: 4px solid #3498db; }
                .footer { margin-top: 40px; padding-top: 20px; text-align: center; font-size: 11px; color: #7f8c8d; border-top: 1px solid #eee; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="report-title">${reportTitle}</div>
                <p>Generated on ${new Date().toLocaleString()}</p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Total Invoices</th>
                        <th class="text-right">Invoice Amount</th>
                        <th class="text-right">Paid Amount</th>
                        <th class="text-right">Outstanding</th>
                    </tr>
                </thead>
                <tbody>`;

    let totalInvoices = 0;
    let totalInvoiceAmount = 0;
    let totalPaid = 0;
    let totalOutstanding = 0;

    data.forEach((item) => {
      totalInvoices += item.total_invoices;
      totalInvoiceAmount += parseFloat(item.invoice_amount || 0);
      totalPaid += parseFloat(item.paid_amount || 0);
      totalOutstanding += parseFloat(item.outstanding || 0);

      html += `
                <tr>
                    <td>${item.customer_name || "-"}${
        item.mobile_number ? " - " + item.mobile_number : ""
      }</td>
                    <td class="text-center">${item.total_invoices || 0}</td>
                    <td class="text-right">${parseFloat(
                      item.invoice_amount || 0
                    ).toFixed(2)}</td>
                    <td class="text-right">${parseFloat(
                      item.paid_amount || 0
                    ).toFixed(2)}</td>
                    <td class="text-right">${parseFloat(
                      item.outstanding || 0
                    ).toFixed(2)}</td>
                </tr>`;
    });

    html += `
                </tbody>
                <tfoot>
                    <tr>
                        <th class="text-right">Total:</th>
                        <th class="text-center">${totalInvoices}</th>
                        <th class="text-right">${totalInvoiceAmount.toFixed(
                          2
                        )}</th>
                        <th class="text-right">${totalPaid.toFixed(2)}</th>
                        <th class="text-right">${totalOutstanding.toFixed(
                          2
                        )}</th>
                    </tr>
                </tfoot>
            </table>
            <div class="summary">
                <h3 style="margin-top: 0; color: #2c3e50;">Report Summary</h3>
                <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px; margin: 5px 0;">
                        <div style="font-size: 13px; color: #7f8c8d;">Total Customers</div>
                        <div style="font-size: 24px; font-weight: 600; color: #2c3e50;">${
                          data.length
                        }</div>
                    </div>
                    <div style="flex: 1; min-width: 200px; margin: 5px 0;">
                        <div style="font-size: 13px; color: #2c3e50;">Total Outstanding</div>
                        <div style="font-size: 24px; font-weight: 600; color: #2c3e50;">${totalOutstanding.toFixed(
                          2
                        )}</div>
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

    const printWindow = window.open("", "_blank");
    printWindow.document.write(html);
    printWindow.document.close();
    printWindow.onload = function () {
      try {
        printWindow.print();
        printWindow.onafterprint = function () {
          printWindow.close();
        };
      } catch (error) {
        console.error("Error during PDF print:", error);
        alert("PDF export failed. Please check console for details.");
        printWindow.close();
      }
    };
  }
});
