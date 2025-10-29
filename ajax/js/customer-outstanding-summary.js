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
  $("#setToday").on("click", function () {
    const today = new Date();
    const todayStr = $.datepicker.formatDate("yy-mm-dd", today);
    $("#fromDate").datepicker("setDate", today);
    $("#toDate").datepicker("setDate", today);
    $("#fromDate, #toDate").trigger("change");
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
  // 5. LOAD MAIN REPORT
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
          '<tr><td colspan="5" class="text-center">Loading...</td></tr>'
        );
        resetTotals();
      },
      success: function (resp) {
        if (resp && resp.status === "success") {
          renderReportData(resp.data);
        } else {
          $("#reportTableBody").html(
            '<tr><td colspan="5" class="text-center">No data found</td></tr>'
          );
        }
      },
      error: function () {
        $("#reportTableBody").html(
          '<tr><td colspan="5" class="text-center">Error loading data</td></tr>'
        );
      },
    });
  }

  // -----------------------------------------------------------------
  // 6. RENDER MAIN TABLE
  // -----------------------------------------------------------------
function renderReportData(data) {
  const $tbody = $("#reportTableBody");
  const paymentType = $("#paymentType").val();
  const isCash = paymentType === "cash";
  $tbody.empty();

  if (!data || data.length === 0) {
    $tbody.html(
      '<tr><td colspan="5" class="text-center">No records found</td></tr>'
    );
    resetTotals();
    return;
  }

  // Update table header based on payment type
  const $thead = $("#reportTable thead");
  const $tfoot = $("#reportTable tfoot");
  if (isCash) {
    $thead.html(`
      <tr>
        <th>Customer</th>
        <th class="text-center">Total Invoices</th>
        <th class="text-end">Total Invoice Amount</th>
      </tr>
    `);
  } else {
    $thead.html(`
      <tr>
        <th>Customer</th>
        <th class="text-center">Total Invoices</th>
        <th class="text-end">Invoice Amount</th>
        <th class="text-end">Paid</th>
        <th class="text-end">Outstanding</th>
      </tr>
    `);
  }

  let totInv = 0,
    totAmt = 0,
    totPaid = 0,
    totOut = 0;

  const formatNumber = (num) =>
    parseFloat(num || 0).toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });

  data.forEach(function (c) {
    totInv += parseInt(c.total_invoices || 0);
    totAmt += parseFloat(c.invoice_amount || 0);
    totPaid += parseFloat(c.paid_amount || 0);
    totOut += parseFloat(c.outstanding || 0);

    // CASH VIEW → 3 columns only
    if (isCash) {
      $tbody.append(`
        <tr class="customer-row" data-customer-id="${c.customer_id}">
          <td>
            <i class="fas fa-plus expand-icon"></i>
            ${c.customer_code || ""} - ${c.customer_name || ""}
            ${c.mobile_number ? " - " + c.mobile_number : ""}
          </td>
          <td class="text-center">${c.total_invoices || 0}</td>
          <td class="text-end">${formatNumber(c.invoice_amount)}</td>
        </tr>
        <tr class="invoice-detail-row" style="display:none;">
          <td colspan="3" class="p-0">
            <div class="invoice-detail-container">
              <table class="table table-sm table-bordered mb-0">
                <thead class="table-light"><tr><td colspan="3" class="text-center">Loading invoices...</td></tr></thead>
                <tbody class="invoice-list"></tbody>
              </table>
            </div>
          </td>
        </tr>
      `);
    } else {
      // CREDIT / ALL → full 5-column view
      $tbody.append(`
        <tr class="customer-row" data-customer-id="${c.customer_id}">
          <td>
            <i class="fas fa-plus expand-icon"></i>
            ${c.customer_code || ""} - ${c.customer_name || ""}
            ${c.mobile_number ? " - " + c.mobile_number : ""}
          </td>
          <td class="text-center">${c.total_invoices || 0}</td>
          <td class="text-end">${formatNumber(c.invoice_amount)}</td>
          <td class="text-end">${formatNumber(c.paid_amount)}</td>
          <td class="text-end outstanding-column">${formatNumber(c.outstanding)}</td>
        </tr>
        <tr class="invoice-detail-row" style="display:none;">
          <td colspan="5" class="p-0">
            <div class="invoice-detail-container">
              <table class="table table-sm table-bordered mb-0">
                <thead class="table-light"><tr><td colspan="6" class="text-center">Loading invoices...</td></tr></thead>
                <tbody class="invoice-list"></tbody>
              </table>
            </div>
          </td>
        </tr>
      `);
    }
  });

  // Render footer totals dynamically
  const $tfootHtml = isCash
    ? `
      <tr>
        <th class="text-end">Total:</th>
        <th class="text-center">${totInv.toLocaleString()}</th>
        <th class="text-end">${formatNumber(totAmt)}</th>
      </tr>
    `
    : `
      <tr>
        <th class="text-end">Total:</th>
        <th class="text-center">${totInv.toLocaleString()}</th>
        <th class="text-end">${formatNumber(totAmt)}</th>
        <th class="text-end">${formatNumber(totPaid)}</th>
        <th class="text-end outstanding-column" style="background-color:#eb4034;color:#fff;">
          ${formatNumber(totOut)}
        </th>
      </tr>
    `;

  if ($tfoot.length === 0) {
    $("#reportTable").append(`<tfoot>${$tfootHtml}</tfoot>`);
  } else {
    $tfoot.html($tfootHtml);
  }
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
  // 7. EXPAND / COLLAPSE + LOAD INVOICES
  // -----------------------------------------------------------------
  $(document).on("click", ".customer-row .expand-icon", function () {
    const $row = $(this).closest(".customer-row");
    const $detailRow = $row.next(".invoice-detail-row");
    const $tbody = $detailRow.find(".invoice-list");
    const custId = $row.data("customer-id");

    if ($detailRow.is(":visible")) {
      $detailRow.hide();
      $(this).removeClass("fa-minus").addClass("fa-plus");
      return;
    }

    if ($tbody.data("loaded")) {
      $detailRow.show();
      $(this).removeClass("fa-plus").addClass("fa-minus");
      return;
    }

    const paymentType = $("#paymentType").val();
    const fromDate = $("#fromDate").val();
    const toDate = $("#toDate").val();

    $detailRow
      .find("thead")
      .html('<tr><td colspan="6" class="text-center">Loading...</td></tr>');

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
          renderInvoiceList($detailRow.find("table"), resp.data, paymentType);
          $tbody.data("loaded", true);
        } else {
          $detailRow
            .find("tbody")
            .html(
              '<tr><td colspan="6" class="text-center">No invoices found</td></tr>'
            );
        }
      },
      error: function () {
        $detailRow
          .find("tbody")
          .html(
            '<tr><td colspan="6" class="text-center">Error loading invoices</td></tr>'
          );
      },
      complete: function () {
        $detailRow.show();
        $row.find(".expand-icon").removeClass("fa-plus").addClass("fa-minus");
      },
    });
  });

  function renderInvoiceList($table, data, paymentType) {
    const $thead = $table.find("thead");
    const $tbody = $table.find("tbody");
    const isCash = paymentType === "cash";

    $tbody.empty();

    if (!data || data.length === 0) {
      $tbody.html(
        '<tr><td colspan="6" class="text-center">No invoices found</td></tr>'
      );
      return;
    }

    const formatNumber = (num) =>
      parseFloat(num || 0).toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });

    // Set header based on payment type
    if (isCash) {
      $thead.html(`
                        <tr>
                            <th>Invoice No</th>
                            <th>Date</th>
                            <th class="text-end">Invoice Amt</th>
                        </tr>
                    `);
      data.forEach((i) => {
        $tbody.append(`
                            <tr>
                                <td>${i.invoice_no || ""}</td>
                                <td>${i.invoice_date || ""}</td>
                                <td class="text-end">${formatNumber(
                                  i.invoice_amount
                                )}</td>
                            </tr>
                        `);
      });
    } else {
      $thead.html(`
                        <tr>
                            <th>Invoice No</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th class="text-end">Invoice Amt</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end outstanding-column">Outstanding</th>
                        </tr>
                    `);
      data.forEach((i) => {
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

        $tbody.append(`
                            <tr class="${rowClass}">
                                <td>${i.invoice_no || ""}</td>
                                <td>${i.invoice_date || ""}</td>
                                <td class="${dueClass}">${dueText}</td>
                                <td class="text-end">${formatNumber(
                                  i.invoice_amount
                                )}</td>
                                <td class="text-end">${formatNumber(
                                  i.paid_amount
                                )}</td>
                                <td class="text-end outstanding-column">${formatNumber(
                                  i.outstanding
                                )}</td>
                            </tr>
                        `);
      });
    }
  }

  // -----------------------------------------------------------------
  // 8. PDF EXPORT
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

  function exportToPdf(data) {
    const reportTitle = "Customer Outstanding Report";
    let html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>${reportTitle}</title>
                <style>
                    body { font-family: Arial; margin: 20px; color: #333; }
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 15px; }
                    h1 { margin: 0; color: #2c3e50; font-size: 24px; }
                    table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 13px; }
                    th, td { border: 1px solid #e0e0e0; padding: 10px; }
                    th { background: #f8f9fa; color: #2c3e50; font-weight: 600; font-size: 12px; }
                    .text-right { text-align: right; }
                    .text-center { text-align: center; }
                    .footer { margin-top: 40px; text-align: center; font-size: 11px; color: #7f8c8d; border-top: 1px solid #eee; padding-top: 10px; }
                </style>
                </head><body>
                <div class="header"><h1>${reportTitle}</h1><p>Generated on ${new Date().toLocaleString()}</p></div>
                <table><thead><tr>
                    <th>Customer</th>
                    <th class="text-center">Total Invoices</th>
                    <th class="text-right">Invoice Amount</th>
                    <th class="text-right">Paid Amount</th>
                    <th class="text-right">Outstanding</th>
                </tr></thead><tbody>`;

    let totalInvoices = 0,
      totalInvoiceAmount = 0,
      totalPaid = 0,
      totalOutstanding = 0;

    data.forEach((item) => {
      totalInvoices += parseInt(item.total_invoices || 0);
      totalInvoiceAmount += parseFloat(item.invoice_amount || 0);
      totalPaid += parseFloat(item.paid_amount || 0);
      totalOutstanding += parseFloat(item.outstanding || 0);

      html += `<tr>
                        <td>${item.customer_name || ""}${
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

    html += `</tbody><tfoot><tr>
                    <th class="text-right">Total:</th>
                    <th class="text-center">${totalInvoices}</th>
                    <th class="text-right">${totalInvoiceAmount.toFixed(2)}</th>
                    <th class="text-right">${totalPaid.toFixed(2)}</th>
                    <th class="text-right">${totalOutstanding.toFixed(2)}</th>
                </tr></tfoot></table>
                <div class="footer">Generated by Customer Outstanding System</div>
                </body></html>`;

    const win = window.open("", "_blank");
    win.document.write(html);
    win.document.close();
    win.onload = () => win.print();
  }
});
