jQuery(document).ready(function () {
  //WINDOWS LOADER
  loadCustomer();
  getInvoiceData();

  $("#view_price_report").on("click", function (e) {
    e.preventDefault();
    loadItems();
  });

  //LOARD ITEM MASTER
  // $("#item_brand_id, #item_category_id, #item_group_id,#item_department_id").on(
  //   "change",
  //   function () {
  //     loadItems();
  //   }
  // );

  //LOARD ITEM MASTER
  $("#item_item_code").on("keyup", function () {
    loadItems();
  });

  //LOARD ITEM MASTER
  $("#item_master").on("shown.bs.modal", function () {
    loadItems();
  });

  $("#all_item_master").on("shown.bs.modal", function () {
    loadAllItems();
  });

  //PAYMENT TYPE CHANGE
  $('input[name="payment_type"]').on("change", function () {
    getInvoiceData();
    togglePaymentButtons();
  });

  // INVOICE TYPE CHANGE
  $("#invoice_type").on("change", function () {
    // If there's an item selected, update the price based on the new type
    const itemCode = $("#itemCode").val();
    if (itemCode) {
      // Find the current active ARN row and extract prices
      const activeArn = $(".arn-row.active-arn").first();
      if (activeArn.length) {
        const customerPrice = parseFloat(activeArn.data("customer-price"));
        const dealerPrice = parseFloat(activeArn.data("dealer-price"));

        const invoiceType = $(this).val();
        const selectedPrice = invoiceType === "customer" ? customerPrice : dealerPrice;

        $("#itemPrice").val(parseFloat(selectedPrice).toFixed(2));
        calculatePayment("price");
      }
    }

    // Update sales rep orders table if it's visible
    if ($("#salesRepOrdersTable").is(":visible") && currentSalesOrdersData.length > 0) {
      populateSalesRepOrdersTable(currentSalesOrdersData);
    }
  });

  // SALES REP ORDERS CHECKBOX CHANGE
  $("#sales_rep_orders").on("change", function () {
    const isChecked = $(this).is(":checked");
    const departmentId = $("#department_id").val();

    if (isChecked) {
      if (!departmentId) {
        swal("Warning", "Please select a department first.", "warning");
        $(this).prop("checked", false);
        return;
      }
      // Hide item tables when sales rep orders is enabled
      $("#addItemTable").hide();
      $("#serviceItemTable").hide();
      $("#salesRepOrdersTable").show();
      $("#load_sales_order").show(); // Show the load sales order button
      fetchSalesOrders(departmentId);
    } else {
      // Show default item tables when sales rep orders is disabled
      $("#addItemTable").show();
      $("#serviceItemTable").hide(); // Keep service table hidden by default
      $("#salesRepOrdersTable").hide();
      $("#load_sales_order").hide(); // Hide the load sales order button
      currentSalesOrdersData = []; // Clear stored data
      clearSalesRepOrdersTable();
    }
  });

  // Initial button state
  togglePaymentButtons();

  // Function to toggle payment/save buttons based on payment type
  function togglePaymentButtons() {
    const paymentType = $('input[name="payment_type"]:checked').val();
    if (paymentType === "1") {
      // Cash
      $("#payment").show();
      $("#save").hide();
      $("#paymentSection").hide();
    } else {
      // Credit
      $("#payment").hide();
      $("#save").show();
      $("#paymentSection").show();
    }
  }

  // RESET INPUT FIELDS
  $("#new").click(function (e) {
    e.preventDefault();
    location.reload();
  });

  // BIND ENTER KEY TO ADD ITEM
  $(
    "#itemCode, #itemName, #itemPrice, #itemQty, #itemDiscount ,#itemSalePrice"
  ).on("keydown", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      addItem();
    }
  });

  // AMOUNT PAID FOCUS
  $("#paymentModal").on("shown.bs.modal", function () {
    $("#amountPaid").focus();
    const firstAmountInput = document.querySelector("#amountPaid");
    if (firstAmountInput) {
      firstAmountInput.value = document.querySelector("#modalFinalTotal").value;
      $("#totalPaid").val(document.querySelector("#modalFinalTotal").value);
    }
  });

  // BIND BUTTON CLICK
  $("#addItemBtn").click(addItem);
  $("#serviceItemBtn").click(addServiceItem);

  // ----------------------ITEM MASTER SECTION START ----------------------//

  let fullItemList = []; // Global variable
  let itemsPerPage = 20;

  function loadItems(page = 1) {
    // Hide any previous table (if needed)
    $("#serviceItemTable").hide();
  
    // Show a loading row in the table body
    $("#itemMaster tbody").html(`
      <tr>
        <td colspan="8" class="text-center text-secondary py-3">
          <div class="spinner-border spinner-border-sm me-2" role="status"></div>
          Loading items, please wait...
        </td>
      </tr>
    `);
  
    // Clear old pagination
    $("#itemPagination").empty();
  
    // Collect filters
    let brand_id = $("#item_brand_id").val();
    let category_id = $("#item_category_id").val();
    let group_id = $("#item_group_id").val();
    let department_id = $("#item_department_id").val();
    let item_code = $("#item_item_code").val().trim();
  
    // Perform AJAX
    $.ajax({
      url: "ajax/php/report.php",
      type: "POST",
      dataType: "json",
      data: {
        action: "loard_price_Control",
        brand_id,
        category_id,
        group_id,
        department_id,
        item_code,
      },
      success: function (data) {
        fullItemList = data || [];
  
        if (fullItemList.length === 0) {
          $("#itemMaster tbody").html(`
            <tr>
              <td colspan="8" class="text-center text-muted py-3">No items found</td>
            </tr>
          `);
          $("#itemPagination").empty();
        } else {
          renderPaginatedItems(page);
        }
      },
      error: function () {
        $("#itemMaster tbody").html(`
          <tr>
            <td colspan="8" class="text-center text-danger py-3">
              <i class="bi bi-exclamation-triangle me-2"></i> Error loading data
            </td>
          </tr>
        `);
        $("#itemPagination").empty();
      },
    });
  }
  

 

  //append to model to data in this funtion
  function renderPaginatedItems(page = 1) {
    let start = (page - 1) * itemsPerPage;
    let end = start + itemsPerPage;
    let slicedItems = fullItemList.slice(start, end);
    let tbody = "";

    let usedQtyMap = {};
    $("#invoiceItemsBody tr").each(function () {
      let rowCode = $(this).find('input[name="item_codes[]"]').val();
      let rowArn = $(this).find('input[name="arn_ids[]"]').val();
      let rowQty = parseFloat($(this).find(".item-qty").text()) || 0;
      let key = `${rowCode}_${rowArn}`;

      if (!usedQtyMap[key]) usedQtyMap[key] = 0;
      usedQtyMap[key] += rowQty;
    });

    if (slicedItems.length > 0) {
      $.each(slicedItems, function (index, item) {
        let rowIndex = start + index + 1;

        // Main item row
        tbody += `<tr class="table-primary">
                    <td>${rowIndex}</td>
                    <td colspan="2">${item.code} - ${item.name}</td>  
                    <td>
    <button style="
        background-color: red; 
        color: white; 
        border: none; 
        border-radius: 8px; 
        padding: 4px 10px; 
        font-weight: bold;
        font-size: 14px;
        cursor: pointer;
    ">
        ${item.total_available_qty}
    </button>
</td>

                    <td>${item.group}</td> 
                     <td colspan="2">${item.category}</td>
                     <td hidden >${item.id}</td>
                </tr>`;

        $("#available_qty").val(item.total_available_qty);

        // Render ARN rows
        let firstActiveAssigned = false;
        $.each(item.stock_tmp, function (i, row) {
          const totalQty = parseFloat(row.qty);
          const arnId = row.arn_no;

          const itemKey = `${item.code}_${arnId}`;

          const usedQty = parseFloat(usedQtyMap[itemKey]) || 0;

          const remainingQty = totalQty - usedQty;

          if (remainingQty <= 0) {
            // Skip rendering if no available quantity
            return true; // continue loop
          }

          let rowClass = "";
          if (remainingQty <= 0) {
            rowClass = "used-arn";
          } else if (!firstActiveAssigned) {
            $(".arn-row").removeClass("selected-arn");
            rowClass = "active-arn selected-arn";
            firstActiveAssigned = true;
            $("#available_qty").val(remainingQty);
          } else {
            rowClass = "disabled-arn";
          }

          const invoiceType = $("#invoice_type").val();
          const selectedPrice = invoiceType === "customer" ? row.customer_price : row.dealer_price;

          tbody += `
                    <tr class="table-info arn-row ${rowClass}" 
                        data-arn-index="${i}" 
                        data-qty="${totalQty}" 
                        data-used="${usedQty}" 
                        data-arn-id="${arnId}"
                        data-customer-price="${row.customer_price}"
                        data-dealer-price="${row.dealer_price}">
                        
                        <td colspan="1" style="width: 15%;"><strong>ARN:</strong> ${arnId}
                        
                        <div style="font-size: 12px; color: red">Cost: ${Number(
                          row.final_cost
                        ).toLocaleString("en-US", {
                          minimumFractionDigits: 2,
                        })}</div>
                        </td>
                      
                        
                        <td>
                            <div><strong>Department:</strong></div>
                            <div>${row.department}</div>
                        </td>
                        
                        <td style="width: 15%;">
                            <div><strong>Available Qty:</strong></div>
                            <div class="arn-qty">${remainingQty}</div> 
                        </td>
                    
                        <td style="width: 15%;">
                            <div><strong>Price:</strong></div>
                            <div class='text-danger'><b>${Number(selectedPrice).toLocaleString("en-US", {
                              minimumFractionDigits: 2,
                            })}</b></div>
                        </td>
                    
                        <td colspan="3">${row.created_at}</td>
                    </tr>`;
        });
      });
    } else {
      tbody = `<tr><td colspan="8" class="text-center text-muted">No items found</td></tr>`;
    }

    $("#itemMaster tbody").html(tbody);
    renderPaginationControls(page);
  }



  function addServiceItem() {
    $(
      "#itemCode, #itemName, #itemPrice,#item_cost_arn, #itemQty, #itemDiscount, #item_id, #itemSalePrice"
    ).val("");
    // Show the searchable dropdown
    $("#serviceItemTable").slideDown().focus(); // nicer animation than .show()

    // Make itemName editable
    $("#itemName").prop("readonly", false).val("");
  }

  // Handle service item selection from dropdown
  $(document).on("change", "#service_items", function () {
    const selectedId = $(this).val();
    if (selectedId!=0) {

      // Update the item code and name fields
      $("#itemCode").val("SI/" + selectedId.padStart(4, "0"));
      $("#item_id").val(selectedId);

      $.ajax({
        url: "ajax/php/service-item.php",
        method: "POST",
        data: {
          action: "get_service_item_cost",
          selectedId: selectedId,
        },
        dataType: "json",
        success: function (data) {
          console.log("AJAX Response:", data);
          if (data.status === "success") {
            console.log("Found service cost:", data.service_cost);
            
            // Store unit prices for calculations
            unitServiceCost = parseFloat(data.service_cost) || 0;
            unitServiceSellingPrice = parseFloat(data.service_selling_price) || 0;
            
            $("#item_cost_arn").val(data.service_cost).trigger("change"); // Added trigger
            $("#available_qty").val(data.service_qty).trigger("change"); // Added trigger
            $("#serviceSellingPrice").val(data.service_selling_price).trigger("change"); // Added selling price
            
            // Combine list price + service selling price for final selling price
            combineServicePrices();
          } else {
            console.error("Service not found. ID searched:", selectedId);
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
          console.log("Response:", xhr.responseText);
        },
      });

      // Focus on quantity field for better UX
      $("#itemQty").focus();
    }
  });

  $(document).on("change", "#service", function () {
    // Get selected service id and name
    const selectedId = $(this).val();
    const selectedText = $(this).find("option:selected").text().trim();

    // Update service name field
    $("#itemName").val(selectedText);
    $("#item_id").val(selectedId);
    $("#itemCode").val("SV/" + selectedId.toString().padStart(4, "0"));
    $("#available_qty").val(9999); // Unlimited for pure services

    // Show service extra details (Vehicle No & Current KM)
    if (selectedId && selectedId != "0") {
      $("#serviceExtraDetails").slideDown();
      $("#serviceKmDetails").slideDown();
      $("#serviceNextServiceDetails").slideDown();
    } else {
      $("#serviceExtraDetails").slideUp();
      $("#serviceKmDetails").slideUp();
      $("#serviceNextServiceDetails").slideUp();
      $("#vehicleNo").val("");
      $("#currentKm").val("");
      $("#nextServiceDays").val("");
    }

    // Fetch service price by selected service id
    $.ajax({
      url: "ajax/php/service.php",
      method: "POST",
      data: { action: "get_service_price", service_id: selectedId },
      dataType: "json",
      success: function (data) {
        if (data.status === "success") {
          $("#itemPrice").val(data.service_price);
          $("#itemSalePrice").val(data.service_price);
          
          // Trigger combination if service selling price is already set
          combineServicePrices();
        } else {
          console.warn("No price found for this service");
        }
      },
      error: function () {
        console.error("Failed to load service price.");
      },
    });

    // Focus on quantity field for better UX
    $("#serviceQty").focus();
  });

  // Function to combine list price + service selling price (only for service items)
  function combineServicePrices() {
    // Only combine prices when service item table is visible (service invoicing mode)
    if ($("#serviceItemTable").is(":visible")) {
      const listPrice = parseFloat($("#itemPrice").val()) || 0;
      const serviceSellingPrice = parseFloat($("#serviceSellingPrice").val()) || 0;
      const discount = parseFloat($("#itemDiscount").val()) || 0;
      
      // Calculate combined price before discount
      const combinedPriceBeforeDiscount = listPrice + serviceSellingPrice;
      
      // Apply discount to the combined total (discount is in percentage)
      const discountAmount = (combinedPriceBeforeDiscount * discount) / 100;
      const finalCombinedPrice = combinedPriceBeforeDiscount - discountAmount;
      
      // Update the main selling price field with final combined value after discount
      $("#itemSalePrice").val(finalCombinedPrice.toFixed(2));
      
      // Trigger calculation to update totals
      calculatePayment();
    }
  }

  // Add event listener for serviceSellingPrice changes
  $(document).on("input", "#serviceSellingPrice", function() {
    // When user manually changes selling price, update the unit price
    if ($("#serviceItemTable").is(":visible")) {
      const serviceQty = parseFloat($("#serviceQty").val()) || 1;
      const currentSellingPrice = parseFloat($(this).val()) || 0;
      
      // Update unit selling price based on manual input
      unitServiceSellingPrice = currentSellingPrice / serviceQty;
    }
    combineServicePrices();
  });

  // Add event listener for itemPrice changes when in service mode
  $(document).on("input", "#itemPrice", function() {
    if ($("#serviceItemTable").is(":visible")) {
      combineServicePrices();
    }
  });

  // Add event listener for discount changes when in service mode
  $(document).on("input", "#itemDiscount", function() {
    if ($("#serviceItemTable").is(":visible")) {
      combineServicePrices();
    }
  });

  // Add event listener for serviceQty changes to update cost and selling price
  $(document).on("input", "#serviceQty", function() {
    updateServiceCalculations();
  });

  // Add event listener for manual cost changes
  $(document).on("input", "#item_cost_arn", function() {
    if ($("#serviceItemTable").is(":visible")) {
      const serviceQty = parseFloat($("#serviceQty").val()) || 1;
      const currentCost = parseFloat($(this).val()) || 0;
      
      // Update unit cost based on manual input
      unitServiceCost = currentCost / serviceQty;
    }
  });

  // Variables to store unit prices
  let unitServiceCost = 0;
  let unitServiceSellingPrice = 0;

  // Function to update service calculations based on qty changes
  function updateServiceCalculations() {
    if ($("#serviceItemTable").is(":visible")) {
      const serviceQty = parseFloat($("#serviceQty").val()) || 1;
      
      // Calculate total cost and selling price based on quantity
      const totalCost = unitServiceCost * serviceQty;
      const totalSellingPrice = unitServiceSellingPrice * serviceQty;
      
      // Update the fields without triggering circular updates
      $("#item_cost_arn").val(totalCost.toFixed(2));
      $("#serviceSellingPrice").val(totalSellingPrice.toFixed(2));
      
      // Trigger the price combination with discount calculation
      combineServicePrices();
    }
  }

  //GET DATA ARN VISE
  $(document).on("click", ".arn-row", function () {
    if ($(this).hasClass("disabled-arn") || $(this).hasClass("used-arn")) {
      return;
    }

    // Deselect others
    $(".arn-row").removeClass("active-arn selected-arn");
    $(this).addClass("active-arn selected-arn");

    const totalQty = parseFloat($(this).data("qty")) || 0;
    const usedQty = parseFloat($(this).data("used")) || 0;
    const remainingQty = totalQty - usedQty;

    if (remainingQty <= 0) {
      swal("Warning", "No quantity left in this ARN.", "warning");
      return;
    }

    $("#available_qty").val(remainingQty);
  });

  function renderPaginationControls(currentPage) {
    let totalPages = Math.ceil(fullItemList.length / itemsPerPage);
    let pagination = "";

    if (totalPages <= 1) {
      $("#itemPagination").html("");
      return;
    }

    pagination += `<li class="page-item ${currentPage === 1 ? "disabled" : ""}">
                     <a class="page-link" href="#" data-page="${
                       currentPage - 1
                     }">Prev</a>
                   </li>`;

    for (let i = 1; i <= totalPages; i++) {
      pagination += `<li class="page-item ${i === currentPage ? "active" : ""}">
                         <a class="page-link" href="#" data-page="${i}">${i}</a>
                       </li>`;
    }

    pagination += `<li class="page-item ${
      currentPage === totalPages ? "disabled" : ""
    }">
                     <a class="page-link" href="#" data-page="${
                       currentPage + 1
                     }">Next</a>
                   </li>`;

    $("#itemPagination").html(pagination);
  }

  $(document).on("click", "#itemPagination .page-link", function (e) {
    e.preventDefault();
    const page = parseInt($(this).data("page")) || 1;
    renderPaginatedItems(page);
  });

  let itemAvailableMap = {};

  //click the and append values
  $(document).on("click", "#itemMaster tbody tr.table-light", function () {
    let mainRow = $(this).prevAll("tr.table-primary").first();
    let infoRow = $(this).prev("tr.table-info");

    let itemText = mainRow.find("td").eq(1).text().trim();
    let parts = itemText.split(" - ");
    let itemCode = parts[0] || "";
    let itemName = parts[1] || "";

    // Extract available qty from .table-info row
    let qtyRow = $(this)
      .find('td[colspan="2"]')
      .parent()
      .find("td")
      .eq(3)
      .html();
    let qtyMatch = qtyRow.match(/Available Qty:\s*(\d+\.?\d*)/i);
    let availableQty = qtyMatch ? parseFloat(qtyMatch[1]) : 0;

    // Store available qty in map and hidden field
    itemAvailableMap[itemCode] = availableQty;
    $("#available_qty").val(availableQty);

    $("#itemCode").val(itemCode);
    $("#itemName").val(itemName);

    $("#itemQty").val("");
    $("#itemDiscount").val("");

    calculatePayment();

    setTimeout(() => $("#itemQty").focus(), 200);

    let itemMasterModal = bootstrap.Modal.getInstance(
      document.getElementById("item_master")
    );
    if (itemMasterModal) {
      itemMasterModal.hide();
    }
  });
  

  $(document).on("click", "#all_itemMaster tbody tr", function () {
    let mainRow = $(this).closest("tr.table-primary"); // ✅ pick the clicked row

    let itemCode = mainRow.find("td").eq(1).text().trim().split(" - ")[0] || "";
    let itemName = mainRow.find("td").eq(1).text().trim().split(" - ")[1] || "";
    let availableQty = mainRow.find("td").eq(2).text().trim();
    let customerPrice = parseFloat(mainRow.data("customer-price"));
    let dealerPrice = parseFloat(mainRow.data("dealer-price"));
    let item_id = mainRow.find("td").eq(4).text().trim(); // id is at index 4 and hidden

    $("#available_qty").val(availableQty);

    $("#itemCode").val(itemCode);
    $("#itemName").val(itemName);
    $("#item_id").val(item_id);

    const invoiceType = $("#invoice_type").val();
    const selectedPrice = invoiceType === "customer" ? customerPrice : dealerPrice;

    $("#itemPrice").val(selectedPrice);
    $("#itemSalePrice").val(dealerPrice);

    calculatePayment();

    setTimeout(() => $("#itemQty").focus(), 200);

    let itemMasterModal = bootstrap.Modal.getInstance(
      document.getElementById("all_item_master")
    );
    if (itemMasterModal) {
      itemMasterModal.hide();
    }
  });

  $(document).on("click", "#itemMaster tbody tr.table-info", function () {


    
    // Get the main item row
    let mainRow = $(this).prevAll("tr.table-primary").first();
    let lastColValue = mainRow.find("td").last().text();

    $("#item_id").val(lastColValue);

    let itemText = mainRow.find("td").eq(1).text().trim();
    let parts = itemText.split(" - ");
    let itemCode = parts[0] || "";
    let itemName = parts[1] || "";
    const tdHtml = $(this).find("td");

    // Extract Available Qty (in td:eq(3))
    let availableQtyText = tdHtml.eq(2).text();
    let qtyMatch = availableQtyText.match(/Available Qty:\s*([\d.,]+)/i);
    let availableQty = qtyMatch ? parseFloat(qtyMatch[1].replace(/,/g, "")) : 0;

    let costText = tdHtml.eq(0).find("div").text(); // <-- get only inside div
    let costMatch = costText.match(/Cost:\s*([\d.,]+)/i);
    let cost_arn = costMatch ? parseFloat(costMatch[1].replace(/,/g, "")) : 0;

    // Extract ARN (in td:eq(0))
    let arnText = tdHtml.eq(0).text();
    let arnMatch = arnText.match(/ARN:\s*(.+)/i);
    let arn = arnMatch ? arnMatch[1].trim() : "";

    //Extract Price (now in td:eq(3))
    let priceText = tdHtml.eq(3).text();
    let priceMatch = priceText.match(/Price:\s*([\d.,]+)/i);
    let price = priceMatch ? parseFloat(priceMatch[1].replace(/,/g, "")) : 0;

    let customerPrice = parseFloat($(this).data("customer-price"));
    let dealerPrice = parseFloat($(this).data("dealer-price"));

    // Get selected invoice type
    const invoiceType = $("#invoice_type").val();

    // Set itemPrice based on invoice type
    let selectedPrice = invoiceType === "customer" ? customerPrice : dealerPrice;

    // Apply to inputs
    $("#itemCode").val(itemCode);
    $("#itemName").val(itemName);
    $("#itemPrice").val(parseFloat(selectedPrice).toFixed(2));
    $("#itemSalePrice").val(parseFloat(dealerPrice).toFixed(2)); // Dealer price as selling price
    $("#item_cost_arn").val(parseFloat(cost_arn).toFixed(2));

    let customer = parseFloat(customerPrice);
    let dealer = parseFloat(dealerPrice);

    if (!isNaN(customer) && !isNaN(dealer) && customer > 0) {
      // calculate percentage difference (customer - dealer) / customer * 100
      let percentage = ((customer - dealer) / customer) * 100;

      // show percentage (2 decimals)
      $("#itemDiscount").val(percentage.toFixed(2));
    } else {
      $("#itemDiscount").val("0.00");
    }

    $("#available_qty").val(availableQty);
    $("#arn_no").val(arn); // optiona

    // Clear qty, discount, payment
    $("#itemQty").val(1);
    $("#payment_type").prop("disabled", true);

    calculatePayment();
    setTimeout(() => $("#itemQty").focus(), 200);

    let itemMasterModal = bootstrap.Modal.getInstance(
      document.getElementById("item_master")
    );
    if (itemMasterModal) {
      itemMasterModal.hide();
    }
  });

  // ----------------------ITEM MASTER SECTION END ----------------------//

  //CHANGE THE DEPARTMENT VALUES EMPTY
  $("#department_id").on("change", function () {
    $("#item_id").val("");
    $("#itemCode").val("");
    $("#itemName").val("");
    $("#itemQty").val("");
    $("#item_cost_arn").val("");
    $("#itemPrice").val("");
    $("#available_qty").val(0);
  });

  //ITEM MODEL HIDDEN SECTION
  $("#item_master").on("hidden.bs.modal", function () {
    if (focusAfterModal) {
      $("#itemQty").focus();
      focusAfterModal = false;
    }
  });

  //get first row cash sales customer
  function loadCustomer() {
    $.ajax({
      url: "ajax/php/customer-master.php",
      method: "POST",
      data: { action: "get_first_customer" }, // you can customize this key/value
      dataType: "json",
      success: function (data) {
        if (!data.error) {
          $("#customer_id").val(data.customer_id);
          $("#customer_code").val(data.customer_code);
          $("#customer_name").val(data.customer_name);
          $("#customer_address").val(data.customer_address);
          $("#customer_mobile").val(data.mobile_number);
          $("#recommended_person").val(data.recommended_person || ""); // Add recommended_person field // adjust key if needed
        } else {
          console.warn("No customer found");
        }
      },
      error: function () {
        console.error("AJAX request failed.");
      },
    });
  }

  //GET INVOICE ID BY PAYMENT TYPE VISE
  function getInvoiceData() {
    const paymentType = $('input[name="payment_type"]:checked').val(); // 'cash' or 'credit'

    $.ajax({
      url: "ajax/php/common.php",
      method: "POST",
      data: {
        action: "get_invoice_id_by_type",
        payment_type: paymentType,
      },
      dataType: "json",
      success: function (response) {
        if (response.invoice_id) {
          $("#invoice_no").val(response.invoice_id);
        } else {
          console.warn("Invoice ID generation failed");
        }
      },
      error: function () {
        console.error("Failed to fetch invoice ID");
      },
    });
  }

  // OPEN PAYMENT MODEL AND PRE-FILL TOTAL
  $("#payment").on("click", function () {
    const totalRaw = $("#finalTotal").val();
    const invoiceId = $("#invoice_id").val();

    const total = parseFloat(totalRaw.replace(/,/g, ""));

    if (isNaN(total) || total <= 0) {
      swal({
        title: "Error!",
        text: "Please enter a valid Final Total amount",
        type: "error",
        timer: 3000,
        showConfirmButton: false,
      });
      return;
    }

    const invoiceNo = $("#invoice_no").val().trim();
    if (!invoiceNo) {
      $("#invoice_no").focus();
      return swal({
        title: "Error!",
        text: "Please enter an invoice number",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    }

    $.ajax({
      url: "ajax/php/sales-invoice.php",
      method: "POST",
      data: {
        action: "check_invoice_id",
        invoice_no: invoiceNo,
      },
      dataType: "json",
      success: function (checkRes) {
        if (checkRes.exists) {
          $("#invoice_no").focus();
          swal({
            title: "Duplicate!",
            text:
              "Invoice No <strong>" + invoiceNo + "</strong> already exists.",
            type: "error",
            html: true,
            timer: 2500,
            showConfirmButton: false,
          });
          return;
        }

        $("#modal_invoice_id").val(invoiceId);
        $("#modalFinalTotal").val(total.toFixed(2));
        $("#amountPaid").val("");
        $("#paymentType").val("1"); // Set default payment type to Cash (ID: 1)

        $("#balanceAmount").val("0.00").removeClass("text-danger");
        $("#paymentModal").modal("show");
      },
      error: function () {
        swal({
          title: "Error!",
          text: "Unable to verify Invoice No. right now.",
          type: "error",
          timer: 3000,
          showConfirmButton: false,
        });
      },
    });
  });

  // CALCULATE AND DISPLAY BALANCE OR SHOW INSUFFICIENT MESSAGE
  $("#amountPaid").on("input", function () {
    const paid = parseFloat($(this).val()) || 0;
    const total = parseFloat($("#modalFinalTotal").val()) || 0;

    if (paid < total) {
      $("#balanceAmount").val("Insufficient").addClass("text-danger");
    } else {
      const balance = paid - total;
      $("#balanceAmount").val(balance.toFixed(2)).removeClass("text-danger");
    }
  });

  // HANDLE PAYMENT FORM SUBMISSION
  $("#savePayment").click(function (event) {
    event.preventDefault();

    if (!$("#customer_id").val()) {
      swal({
        title: "Error!",
        text: "Please enter customer code",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    const invoiceNo = $("#invoice_no").val().trim();
    const dag_id = $("#dag_id").val();

    if (dag_id != 0) {
      processDAGInvoiceCreation();
    } else {
      $.ajax({
        url: "ajax/php/sales-invoice.php",
        method: "POST",
        data: {
          action: "check_invoice_id",
          invoice_no: invoiceNo,
        },
        dataType: "json",
        success: function (checkRes) {
          if (checkRes.exists) {
            swal({
              title: "Duplicate!",
              text:
                "Invoice No <strong>" + invoiceNo + "</strong> already exists.",
              type: "error",
              html: true,
              timer: 2500,
              showConfirmButton: false,
            });
            return;
          }

          processInvoiceCreation();
        },
        error: function () {
          swal({
            title: "Error!",
            text: "Unable to verify Invoice No. right now.",
            type: "error",
            timer: 3000,
            showConfirmButton: false,
          });
        },
      });
    }
  });

  //UPDATE BUTTON
  $("#update").on("click", function (event) {
    event.preventDefault();

    if (!$("#customer_id").val()) {
      swal({
        title: "Error!",
        text: "Please select a customer before updating invoice.",
        type: "error",
        timer: 3000,
        showConfirmButton: false,
      });
      return;
    }

    const invoiceNo = $("#invoice_no").val().trim();
    if (!invoiceNo) {
      $("#invoice_no").focus();
      return swal({
        title: "Error!",
        text: "Please enter an invoice number",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    }

    processInvoiceUpdate();
  });

  $("#save").click(function (event) {
    event.preventDefault();

    if (!$("#customer_id").val()) {
      swal({
        title: "Error!",
        text: "Please enter customer code",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    const invoiceNo = $("#invoice_no").val().trim();
    if (!invoiceNo) {
      $("#invoice_no").focus();
      return swal({
        title: "Error!",
        text: "Please enter an invoice number",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
    }

    $.ajax({
      url: "ajax/php/sales-invoice.php",
      method: "POST",
      data: {
        action: "check_invoice_id",
        invoice_no: invoiceNo,
      },
      dataType: "json",
      success: function (checkRes) {
        if (checkRes.exists) {
          $("#invoice_no").focus();
          swal({
            title: "Duplicate!",
            text:
              "Invoice No <strong>" + invoiceNo + "</strong> already exists.",
            type: "error",
            html: true,
            timer: 2500,
            showConfirmButton: false,
          });
          return;
        }

        processInvoiceCreation();
      },
      error: function () {
        swal({
          title: "Error!",
          text: "Unable to verify Invoice No. right now.",
          type: "error",
          timer: 3000,
          showConfirmButton: false,
        });
      },
    });
  });

  //ITEM INVOICE PROCESS
  function processInvoiceCreation() {
    const items = [];
    const dagItems = [];
    const customerPrices = [];
    const dealerPrices = [];
    const salesOrderIds = [];

    //  item invoice to send this php file
    $("#invoiceItemsBody tr").each(function (index) {
      const selectedPrice = parseFloat($(this).find("td:eq(2)").text()) || 0;
      const qty = parseFloat($(this).find("td:eq(3)").text()) || 0;
      const discount = parseFloat($(this).find("td:eq(4)").text()) || 0;

      const item_id = $(this).find('input[name="item_id[]"]').val();
      const code = $(this).find('input[name="item_codes[]"]').val();
      const customer_price = parseFloat($(this).find('input[name="customer_prices[]"]').val()) || 0;
      const dealer_price = parseFloat($(this).find('input[name="dealer_prices[]"]').val()) || 0;
      const arn_no = $(this).find('input[name="arn_ids[]"]').val();
      const cost = parseFloat($(this).find('input[name="arn_costs[]"]').val()) || 0;
      const service_qty = parseFloat($(this).find('input[name="service_qty[]"]').val()) || 0;
      const vehicle_no = $(this).find('input[name="vehicle_no[]"]').val();
      const current_km = $(this).find('input[name="current_km[]"]').val();
      const next_service_days = $(this).find('input[name="next_service_days[]"]').val();
      const sales_order_id = $(this).find('input[name="sales_order_ids[]"]').val();

      // Collect arrays for PHP processing
      customerPrices.push(customer_price);
      dealerPrices.push(dealer_price);
      salesOrderIds.push(sales_order_id || '');

      items.push({
        item_id,
        code,
        name: $(this).find("td").eq(1).text().trim(),
        price: selectedPrice,
        selling_price: dealer_price, // Use dealer price for selling_price
        customer_price,
        dealer_price,
        qty,
        discount,
        arn_no,
        cost,
        service_qty,
        vehicle_no,
        current_km,
        next_service_days,
        sales_order_id,
      });
    });

    // Validate items
    if (items.length === 0 && dagItems.length === 0) {
      return swal({
        title: "Error!",
        text: "Please add at least one item.",
        type: "error",
        timer: 3000,
        showConfirmButton: false,
      });
    }

    // Validate customer name
    const customerName = $("#customer_name").val().trim();
    if (!customerName) {
      $("#customer_name").focus();
      return swal({
        title: "Error!",
        text: "Please select a customer before creating an invoice.",
        type: "error",
        timer: 3000,
        showConfirmButton: false,
      });
    }

    // Validate cash sales with credit
    if ($("#customer_code").val() === "CM/01" && $("#payment_type").val() === "2") {
      $("#customer_code").focus();
      return swal({
        title: "Error!",
        text: "Cash sales customer is not allowed to create a credit invoice.",
        type: "error",
        timer: 3000,
        showConfirmButton: false,
      });
    }

    // Validate credit period 
    if ($("input[name='payment_type']:checked").val() === "2") {
      const creditPeriod = $("#credit_period").val()?.trim();
      if (!creditPeriod) {
        return swal({
          title: "Error!",
          text: "Please select credit period.",
          type: "error",
          timer: 3000,
          showConfirmButton: false,
        });
      }
    }


        

    let payments = [];
    let finalTotal = parseFloat($("#modalFinalTotal").val()) || 0;
    let totalAmount = 0;

    // Collect all payment rows
    $("#paymentRows .payment-row").each(function () {
      let methodId = $(this).find(".paymentType").val();
      let amount = parseFloat($(this).find(".paymentAmount").val()) || 0;
      let paymentMethod = $(this)
        .find(".paymentType option:selected")
        .text()
        .toLowerCase();

      // Only include cheque details for cheque payments
      let chequeNumber = null;
      let chequeBank = null;
      let chequeDate = "1000-01-01"; // Default valid MySQL date

      if (paymentMethod.includes("cheque")) {
        chequeNumber =
          $(this).find('input[name="chequeNumber[]"]').val() || null;
        chequeBank = $(this).find('input[name="chequeBank[]"]').val() || null;
        let dateInput = $(this).find('input[name="chequeDate[]"]').val();
        chequeDate = dateInput ? dateInput : "1000-01-01"; // Use default date if not provided
      }

      if (!methodId && $("#customer_id").val() == "CM/01") {
        swal({
          title: "Error!",
          text: "Please select a payment method in all rows.",
          type: "error",
          timer: 2000,
          showConfirmButton: false,
        });
        return false; // break out of each
      }

      if (amount <= 0 && $("#customer_id").val() == "CM/01") {
        swal({
          title: "Error!",
          text: "Please enter a valid amount in all rows.",
          type: "error",
          timer: 2000,
          showConfirmButton: false,
        });
        return false; // break out of each
      }

      totalAmount += amount;

      payments.push({
        method_id: methodId,
        amount: amount,
        reference_no: chequeNumber,
        bank_name: chequeBank,
        cheque_date: chequeDate || null,
      });
    });

    if (paymentType == 2) {
        const creditPeriod = $("#credit_period").val();
        if (!creditPeriod) {
            swal({
                title: "Error!",
                text: "Please select a credit period for credit sales.",
                type: "error",
                timer: 3000,
                showConfirmButton: false,
            });
            return;
        }
    }

    if (
      totalAmount !== finalTotal &&
      $('input[name="payment_type"]:checked').val() == "1"
    ) {
      swal({
        title: "Error!",
        text: "Total amount does not match the final total.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return false;
    }

    const formData = new FormData($("#form-data")[0]);
    formData.append("create", true);
    formData.append(
      "payment_type",
      $('input[name="payment_type"]:checked').val()
    );
    formData.append("customer_id", $("#customer_id").val());
    formData.append("customer_name", $("#customer_name").val());
    formData.append("customer_mobile", $("#customer_mobile").val());
    formData.append("customer_address", $("#customer_address").val());
    formData.append("recommended_person", $("#recommended_person").val());
    formData.append("invoice_no", $("#invoice_no").val());
    formData.append("invoice_date", $("#invoice_date").val());
    formData.append("items", JSON.stringify(items));
    
    // Add sales order IDs as separate arrays for PHP processing
    salesOrderIds.forEach((id, index) => {
      formData.append(`sales_order_ids[${index}]`, id);
    });
    
    formData.append(
      "sales_type",
      $('input[name="payment_type"]:checked').val()
    ); // Using payment_type as sales_type
    formData.append("company_id", $("#company_id").val() || 1); // Default to 1 if not found
    formData.append("department_id", $("#department_id").val() || 1); // Default to 1 if not found
    formData.append("payments", JSON.stringify(payments));

    
    formData.append("paidAmount", $("#paidAmount").val() || 1); // Default to 1 if not found

    formData.append("credit_period", $("#credit_period").val() || null);
    formData.append("remark", $("#remark").val() || null);

    $(".someBlock").preloader();

    $.ajax({
      url: "ajax/php/sales-invoice.php",
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function (res) {
        const invoiceId = res.invoice_id;
        // Save DAG items
        $.ajax({
          url: "ajax/php/sales-invoice-dag.php",
          type: "POST",
          data: {
            invoice_id: invoiceId,
            items: JSON.stringify(dagItems),
          },
          success: function () {
            console.log("DAG invoice saved");
          },
          error: function () {
            console.error("DAG invoice save failed");
          },
        });

        swal({
          title: "Success!",
          text: "Invoice saved successfully!",
          type: "success",
          timer: 3000,
          showConfirmButton: false,
        });

        $("#paymentModal").modal("hide");
        window.open("invoice.php?invoice_no=" + invoiceId, "_blank");
        setTimeout(() => location.reload(), 3000);
      },
      error: function (xhr) {
        console.error(xhr.responseText);
        swal({
          title: "Error",
          text: "Something went wrong!",
          type: "error",
          timer: 3000,
          showConfirmButton: false,
        });
      },
    });
  }

  //PROCESS INVOICE UPDATE
  function processInvoiceUpdate() {
    const items = [];
    const dagItems = [];

    //  item invoice to send this php file
    $("#invoiceItemsBody tr").each(function (index) {
      const selectedPrice = parseFloat($(this).find("td:eq(2)").text()) || 0;
      const qty = parseFloat($(this).find("td:eq(3)").text()) || 0;
      const discount = parseFloat($(this).find("td:eq(4)").text()) || 0;

      const item_id = $(this).find('input[name="item_id[]"]').val();
      const code = $(this).find('input[name="item_codes[]"]').val();
      const customer_price = parseFloat($(this).find('input[name="customer_prices[]"]').val()) || 0;
      const dealer_price = parseFloat($(this).find('input[name="dealer_prices[]"]').val()) || 0;
      const arn_no = $(this).find('input[name="arn_ids[]"]').val();
      const cost = parseFloat($(this).find('input[name="arn_costs[]"]').val()) || 0;
      const service_qty = parseFloat($(this).find('input[name="service_qty[]"]').val()) || 0;
      const vehicle_no = $(this).find('input[name="vehicle_no[]"]').val();
      const current_km = $(this).find('input[name="current_km[]"]').val();
      const next_service_days = $(this).find('input[name="next_service_days[]"]').val();
      const sales_order_id = $(this).find('input[name="sales_order_ids[]"]').val();

      items.push({
        item_id,
        code,
        name: $(this).find("td").eq(1).text().trim(),
        price: selectedPrice,
        selling_price: dealer_price, // Use dealer price for selling_price
        customer_price,
        dealer_price,
        qty,
        discount,
        arn_no,
        cost,
        service_qty,
        vehicle_no,
        current_km,
        next_service_days,
        sales_order_id,
      });
    });

   // Validate items
if (items.length === 0 && dagItems.length === 0) {
  return swal({
    title: "Error!",
    text: "Please add at least one item.",
    type: "error",
    timer: 3000,
    showConfirmButton: false,
  });
}

// Validate customer name
const customerName = $("#customer_name").val().trim();
if (!customerName) {
  $("#customer_name").focus();
  return swal({
    title: "Error!",
    text: "Please select a customer before updating invoice.",
    type: "error",
    timer: 3000,
    showConfirmButton: false,
  });
}

// Validate cash sales with credit
if ($("#customer_code").val() === "CM/01" && $("#payment_type").val() === "2") {
  $("#customer_code").focus();
  return swal({
    title: "Error!",
    text: "Cash sales customer is not allowed to create a credit invoice.",
    type: "error",
    timer: 3000,
    showConfirmButton: false,
  });
}

// Validate credit period 
if ($("input[name='payment_type']:checked").val() === "2") {
  const creditPeriod = $("#credit_period").val()?.trim();
  if (!creditPeriod) {
    return swal({
      title: "Error!",
      text: "Please select credit period.",
      type: "error",
      timer: 3000,
      showConfirmButton: false,
    });
  }
}


        

    let payments = [];
    let finalTotal = parseFloat($("#modalFinalTotal").val()) || 0;
    let totalAmount = 0;

    // Collect all payment rows
    $("#paymentRows .payment-row").each(function () {
      let methodId = $(this).find(".paymentType").val();
      let amount = parseFloat($(this).find(".paymentAmount").val()) || 0;
      let paymentMethod = $(this)
        .find(".paymentType option:selected")
        .text()
        .toLowerCase();

      // Only include cheque details for cheque payments
      let chequeNumber = null;
      let chequeBank = null;
      let chequeDate = "1000-01-01"; // Default valid MySQL date

      if (paymentMethod.includes("cheque")) {
        chequeNumber =
          $(this).find('input[name="chequeNumber[]"]').val() || null;
        chequeBank = $(this).find('input[name="chequeBank[]"]').val() || null;
        let dateInput = $(this).find('input[name="chequeDate[]"]').val();
        chequeDate = dateInput ? dateInput : "1000-01-01"; // Use default date if not provided
      }

      if (!methodId && $("#customer_id").val() == "CM/01") {
        swal({
          title: "Error!",
          text: "Please select a payment method in all rows.",
          type: "error",
          timer: 2000,
          showConfirmButton: false,
        });
        return false; // break out of each
      }

      if (amount <= 0 && $("#customer_id").val() == "CM/01") {
        swal({
          title: "Error!",
          text: "Please enter a valid amount in all rows.",
          type: "error",
          timer: 2000,
          showConfirmButton: false,
        });
        return false; // break out of each
      }

      totalAmount += amount;

      payments.push({
        method_id: methodId,
        amount: amount,
        reference_no: chequeNumber,
        bank_name: chequeBank,
        cheque_date: chequeDate || null,
      });
    });

    if (paymentType == 2) {
        const creditPeriod = $("#credit_period").val();
        if (!creditPeriod) {
            swal({
                title: "Error!",
                text: "Please select a credit period for credit sales.",
                type: "error",
                timer: 3000,
                showConfirmButton: false,
            });
            return;
        }
    }

    if (
      totalAmount !== finalTotal &&
      $('input[name="payment_type"]:checked').val() == "1"
    ) {
      swal({
        title: "Error!",
        text: "Total amount does not match the final total.",
        type: "error",
        timer: 2000,
        showConfirmButton: false,
      });
      return false;
    }

    const formData = new FormData($("#form-data")[0]);
    formData.append("update", true);
    formData.append(
      "payment_type",
      $('input[name="payment_type"]:checked').val()
    );
    formData.append("customer_id", $("#customer_id").val());
    formData.append("customer_name", $("#customer_name").val());
    formData.append("customer_mobile", $("#customer_mobile").val());
    formData.append("customer_address", $("#customer_address").val());
    formData.append("recommended_person", $("#recommended_person").val());
    formData.append("invoice_no", $("#invoice_no").val());
    formData.append("invoice_date", $("#invoice_date").val());
    formData.append("items", JSON.stringify(items));
    formData.append(
      "sales_type",
      $('input[name="payment_type"]:checked').val()
    ); // Using payment_type as sales_type
    formData.append("company_id", $("#company_id").val() || 1); // Default to 1 if not found
    formData.append("department_id", $("#department_id").val() || 1); // Default to 1 if not found
    formData.append("payments", JSON.stringify(payments));

    
    formData.append("paidAmount", $("#paidAmount").val() || 1); // Default to 1 if not found

    formData.append("credit_period", $("#credit_period").val() || null);
    formData.append("remark", $("#remark").val() || null);

    $(".someBlock").preloader();

    $.ajax({
      url: "ajax/php/sales-invoice.php",
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function (res) {
        const invoiceId = res.invoice_id;
        // Save DAG items
        $.ajax({
          url: "ajax/php/sales-invoice-dag.php",
          type: "POST",
          data: {
            invoice_id: invoiceId,
            items: JSON.stringify(dagItems),
          },
          success: function () {
            console.log("DAG invoice saved");
          },
          error: function () {
            console.error("DAG invoice save failed");
          },
        });

        swal({
          title: "Success!",
          text: "Invoice updated successfully!",
          type: "success",
          timer: 3000,
          showConfirmButton: false,
        });

        $("#paymentModal").modal("hide");
        window.open("invoice.php?invoice_no=" + invoiceId, "_blank");
        setTimeout(() => location.reload(), 3000);
      },
      error: function (xhr) {
        console.error(xhr.responseText);
        swal({
          title: "Error",
          text: "Something went wrong!",
          type: "error",
          timer: 3000,
          showConfirmButton: false,
        });
      },
    });
  }

  //PROCESS DAG INVOICE CREATION
  function processDAGInvoiceCreation() {
    const finalTotal = parseFloat($("#finalTotal").val()) || 0;
    const paymentType = $('input[name="payment_type"]:checked').val();
    
    // For cash payments, check if paid amount is sufficient
    if (paymentType === "1") {
      const paid = parseFloat($("#amountPaid").val()) || 0;
      if (paid < finalTotal) {
        swal({
          title: "Error!",
          text: "Paid amount cannot be less than Final Total",
          type: "error",
          timer: 3000,
          showConfirmButton: false,
        });
        return;
      }
    }

    const dagItems = [];

    $("#dagItemsBodyInvoice tr.dag-item-row").each(function () {
      const vehicleNo = $(this).find("td:eq(0)").text().trim();
      const beltDesign = $(this).find("td:eq(1)").text().trim();
      const size = $(this).find("td:eq(2)").text().trim();
      const serialNo = $(this).find("td:eq(3)").text().trim();
      const price = parseFloat($(this).find(".dag-price").val()) || 0;
      const cost = parseFloat($(this).find(".dag-cost").val()) || 0;
      const dagItemId = $(this).find(".dag-price").data("dag-item-id");

      // Validate that cost doesn't exceed price
      if (cost > price) {
        swal({
          title: "Validation Error!",
          text: `Cost (${cost.toFixed(2)}) cannot exceed price (${price.toFixed(2)}) for item: ${vehicleNo} - ${serialNo}`,
          type: "error",
          timer: 4000,
          showConfirmButton: true,
        });
        return false; // Stop processing
      }

      if (vehicleNo && price > 0) {
        dagItems.push({
          dag_item_id: dagItemId,
          vehicle_no: vehicleNo,
          belt_design: beltDesign,
          size: size,
          serial_no: serialNo,
          price: price,
          cost: cost,
          qty: 1, // Always 1 for DAG items
          total: price,
          is_dag: true
        });
      }
    });

    if (dagItems.length === 0) {
      swal({
        title: "Error!",
        text: "Please add at least one DAG item with a price.",
        type: "error",
        timer: 3000,
        showConfirmButton: false,
      });
      return;
    }

    const invoiceId = $("#invoice_no").val();
    if (!invoiceId) {
      swal("Error!", "Invoice ID is missing.", "error");
      return;
    }

    // Validate required fields
    const customerId = $("#customer_id").val();
    const customerName = $("#customer_name").val();
    const dagId = $("#dag_id").val();

    if (!customerId || !customerName) {
      swal({
        title: "Error!",
        text: "Please select a customer before creating invoice.",
        type: "error",
        timer: 3000,
        showConfirmButton: false,
      });
      return;
    }

    if (!dagId) {
      swal({
        title: "Error!",
        text: "Please select a DAG before creating invoice.",
        type: "error",
        timer: 3000,
        showConfirmButton: false,
      });
      return;
    }

    $(".someBlock").preloader();

    // Prepare FormData with all values
    const formData = new FormData($("#form-data")[0]);
    formData.append("create", true);
    formData.append("paid", paymentType === "1" ? $("#amountPaid").val() : "0");
    formData.append("payment_type", paymentType);
    formData.append("customer_id", customerId);
    formData.append("customer_name", customerName);
    formData.append("customer_mobile", $("#customer_mobile").val() || "");
    formData.append("customer_address", $("#customer_address").val() || "");
    formData.append("department_id", $("#department_id").val() || "1");
    formData.append("invoice_no", invoiceId);
    formData.append("recommended_person", $("#recommended_person").val() || "");
    formData.append("items", JSON.stringify(dagItems));
    formData.append("dag_id", dagId);

    $.ajax({
      url: "ajax/php/sales-invoice-dag.php",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: function (res) {
        $(".someBlock").preloader("remove");
        swal({
          title: "Success!",
          text: "DAG Invoice saved successfully!",
          type: "success",
          timer: 2000,
          showConfirmButton: false,
        });

        if ($("#paymentModal").hasClass('show')) {
          $("#paymentModal").modal("hide");
        }
        
        // Open regular invoice page for DAG invoices too
        window.open("invoice.php?invoice_no=" + invoiceId, "_blank");
        setTimeout(() => location.reload(), 2000);
      },
      error: function () {
        $(".someBlock").preloader("remove");
        swal("Error!", "Failed to save DAG invoice.", "error");
      },
    });
  }

  //ADD ITEM TO INVOICE TABLE
  function addItem() {
    const item_id = $("#item_id").val().trim();
    const code = $("#itemCode").val().trim();
    const name = $("#itemName").val().trim();
    const price = parseFloat($("#itemPrice").val()) || 0;
    const qty = parseFloat($("#itemQty").val()) || 0;
    const discount = parseFloat($("#itemDiscount").val()) || 0;
    const sale_price = parseFloat($("#itemSalePrice").val()) || 0;

    let availableQty = parseFloat($("#available_qty").val()) || 0;
    let serviceQty = parseFloat($("#serviceQty").val()) || 0;
    
    // Get vehicle no and current km for services
    const vehicleNo = $("#vehicleNo").val().trim() || "";
    const currentKm = $("#currentKm").val().trim() || "";
    const nextServiceDays = $("#nextServiceDays").val().trim() || "";

    if (!code || !name || price <= 0 || qty <= 0) {
      swal({
        title: "Error!",
        text: "Please enter valid item details including quantity and price.",
        type: "error",
        timer: 3000,
        showConfirmButton: false,
      });
      return;
    } else if (!code.startsWith("SI") && !code.startsWith("SV") && qty > availableQty) {
      swal({
        title: "Error!",
        text: "Transfer quantity cannot exceed available quantity!",
        type: "error",
        timer: 2500,
        showConfirmButton: false,
      });
      return;
    } else if (code.startsWith("SI") && serviceQty > availableQty) {
      swal({
        title: "Error!",
        text: "Transfer quantity cannot exceed available quantity!",
        type: "error",
        timer: 2500,
        showConfirmButton: false,
      });
      return;
    }

    // Find the active ARN row
    const activeArn = $(".arn-row.active-arn").first();

    let arnId, arnQty, usedQty, remainingQty;
    if (activeArn.length) {
      arnId = activeArn.data("arn-id");
      arnQty = parseFloat(activeArn.data("qty"));
      usedQty = parseFloat(activeArn.data("used")) || 0;
      remainingQty = arnQty - usedQty;
    } else {
      arnId = code;
      arnQty = 0;
      usedQty = 0;
      remainingQty = 0;
    }

    if (!code.startsWith("SI") && !code.startsWith("SV") && qty > remainingQty) {
      swal(
        "Error!",
        `Only ${remainingQty} qty available for the current ARN.`,
        "error"
      );
      return;
    }

    // If item already exists in invoice, remove and restore ARN qty
    let alreadyExists = false;
    $("#invoiceItemsBody tr").each(function () {
      const existingCode = $(this).find('input[name="item_codes[]"]').val();
      const existingArn = $(this).find('input[name="arn_ids[]"]').val();
      if (existingCode === code && existingArn === arnId) {
        const existingQty = parseFloat($(this).find(".item-qty").text()) || 0;

        // Restore used quantity
        const currentUsed = parseFloat(activeArn.data("used")) || 0;
        const newUsed = currentUsed - existingQty;

        activeArn.data("used", newUsed);
        activeArn.find(".arn-qty").text((arnQty - newUsed).toFixed(2));

        alreadyExists = true;
        return false;
      }
    });

    if (alreadyExists) {
      swal(
        "Warning!",
        "This item from the current ARN is already added.",
        "warning"
      );
      return;
    }

    // Calculate total based on whether it's a service invoice or regular invoice
    let total;
    if ($("#serviceItemTable").is(":visible")) {
      // For service invoices, use the sale_price (which includes combined service + service item price with discount)
      total = sale_price * qty;
    } else {
      // For regular invoices, use the original calculation
      total = price * qty - price * qty * (discount / 100);
    }
    $("#noItemRow").remove();
    $("#noQuotationItemRow").remove();
    $("#noInvoiceItemRow").remove();

    // Calculate display values based on invoice type
    let displayPrice, displayName, customerPrice, dealerPrice, selectedPrice;
    if ($("#serviceItemTable").is(":visible")) {
      // For service invoices, show combined service + service item details
      const serviceSellingPrice = parseFloat($("#serviceSellingPrice").val()) || 0;
      const combinedPriceBeforeDiscount = price + serviceSellingPrice;
      displayPrice = combinedPriceBeforeDiscount;
      displayName = name + " (Service + Item)";
      customerPrice = combinedPriceBeforeDiscount; // For services, same price
      dealerPrice = combinedPriceBeforeDiscount;
      selectedPrice = combinedPriceBeforeDiscount;
    } else {
      // For regular invoices, get prices from the modal data
      // We need to get the prices from the clicked row
      const invoiceType = $("#invoice_type").val();
      customerPrice = price; // itemPrice is set based on type
      dealerPrice = sale_price;
      selectedPrice = price; // Show selected price
      displayPrice = price; // Show selected price
      displayName = name;
    }

    // Get the cost value from the form
    const cost = parseFloat($("#item_cost_arn").val()) || 0;
    
    const row = `
            <tr>
                <td>${code}
                    <input type="hidden" name="item_id[]" value="${item_id}">
                    <input type="hidden" name="item_codes[]" value="${code}">
                    <input type="hidden" name="customer_prices[]" value="${customerPrice}">
                    <input type="hidden" name="dealer_prices[]" value="${dealerPrice}">
                    <input type="hidden" name="arn_ids[]" value="${arnId}">
                    <input type="hidden" name="arn_costs[]" value="${cost}">
                    <input type="hidden" name="service_qty[]" value="${serviceQty}">
                    <input type="hidden" name="vehicle_no[]" value="${vehicleNo}">
                    <input type="hidden" name="current_km[]" value="${currentKm}">
                    <input type="hidden" name="next_service_days[]" value="${nextServiceDays}">
                </td>
                <td>${displayName}</td>
                <td class="item-price">${selectedPrice.toFixed(2)}</td>
                <td class="item-qty">${qty}</td>
                <td class="item-discount">${discount}</td>
                <td>${total.toLocaleString(undefined, {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                })}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger btn-remove-item" data-code="${code}" data-qty="${qty}" data-arn-id="${arnId}">Remove</button>
                </td>
            </tr>
        `;

    $("#invoiceItemsBody").append(row);

    // Clear input fields
    updateFinalTotal();
    $(
      "#itemCode, #itemName, #itemPrice,#item_cost_arn, #itemQty, #itemDiscount, #item_id, #itemSalePrice"
    ).val("");
    $("#vehicleNo, #currentKm, #nextServiceDays").val("");
    $("#serviceItemTable").hide();
    $("#serviceExtraDetails").hide();
    $("#serviceKmDetails").hide();
    $("#serviceNextServiceDetails").hide();

    const newUsedQty = usedQty + qty;
    if (activeArn.length) {
      activeArn.data("used", newUsedQty);

      remainingQty = arnQty - newUsedQty;
      activeArn.find(".arn-qty").text(remainingQty.toFixed(2));

      // Disable ARN if fully used
      if (remainingQty <= 0) {
        activeArn.removeClass("active-arn").addClass("used-arn");
        activeArn.find(".arn-qty").text("0");

        // Activate the next available ARN
        const nextArn = activeArn.nextAll(".arn-row.disabled-arn").first();
        if (nextArn.length) {
          nextArn.removeClass("disabled-arn").addClass("active-arn");
        }
      }
    }

    $(".arn-row").each(function () {
      const qty = parseFloat($(this).data("qty")) || 0;
      const used = parseFloat($(this).data("used")) || 0;
      const remaining = qty - used;

      if (remaining <= 0) {
        $(this).removeClass("active-arn selected-arn").addClass("disabled-arn");
        $(this).find(".arn-qty").text("0");
      }
    });
  }

  //UPDATE FINAL TOTAL
  function updateFinalTotal() {
    let subTotal = 0;
    let discountTotal = 0;
    let taxTotal = 0;

    $("#invoiceItemsBody tr").each(function () {
      const qty =
        parseFloat($(this).find(".item-qty").text().replace(/,/g, "")) || 0;
      const price =
        parseFloat($(this).find(".item-price").text().replace(/,/g, "")) || 0;
      const discount =
        parseFloat($(this).find(".item-discount").text().replace(/,/g, "")) ||
        0;

      const itemTotal = price * qty;
      const itemDiscount = itemTotal * (discount / 100);
      const itemTax = 0;

      subTotal += itemTotal;
      discountTotal += itemDiscount;
      taxTotal += itemTax;
    });

    const grandTotal = subTotal - discountTotal + taxTotal;
    $("#subTotal").val(
      subTotal.toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    );
    $("#disTotal").val(
      discountTotal.toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    );
    $("#tax").val(
      taxTotal.toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    );
    $("#finalTotal").val(
      grandTotal.toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    );

    $("#balanceAmount").val($("#finalTotal").val());
  }

  // EVENT DELEGATION FOR REMOVE BUTTONS
  $(document).on("click", ".btn-remove-item", function () {
    const btn = this;
    const code = $(btn).data("code");
    const qty = parseFloat($(btn).data("qty"));
    const arnId = $(btn).data("arn-id");

    removeRow(btn, code, qty, arnId);
  });

  // REMOVE ITEM ROWinvoiceTable
  function removeRow(btn, code, qty, arnId) {
    $(btn).closest("tr").remove();

    const arnRow = $(`.arn-row[data-arn-id="${arnId}"]`);
    let usedQty = parseFloat(arnRow.data("used")) || 0;
    let newUsedQty = usedQty - qty;

    arnRow.data("used", newUsedQty);
    arnRow.find(".arn-qty").text(parseFloat(arnRow.data("qty")) - newUsedQty);

    // Reactivate if previously marked as used
    if (arnRow.hasClass("used-arn")) {
      arnRow.removeClass("used-arn").addClass("active-arn");

      // Re-disable next ARN if unused
      const nextArn = arnRow.nextAll(".arn-row.active-arn").first();
      if (nextArn.length && parseFloat(nextArn.data("used")) === 0) {
        nextArn.removeClass("active-arn").addClass("disabled-arn");
      }
    }

    updateFinalTotal();
  }

  function calculatePayment(changedField) {
    const price = parseFloat($("#itemPrice").val()) || 0;
    const qty = parseFloat($("#itemQty").val()) || 0;
    const discount = parseFloat($("#itemDiscount").val()) || 0;
    const salePrice = parseFloat($("#itemSalePrice").val()) || 0;

    let finalSalePrice = salePrice;
    let finalDiscount = discount;

    if (changedField === "price" || changedField === "discount") {
      // Recalculate Sale Price
      finalSalePrice = price - price * (discount / 100);
      $("#itemSalePrice").val(finalSalePrice.toFixed(2));
    } else if (changedField === "salePrice") {
      // Recalculate Discount
      if (price > 0) {
        finalDiscount = ((price - salePrice) / price) * 100;
        $("#itemDiscount").val(finalDiscount.toFixed(2));
      }
    }

    // Always recalc payment
    const total = (parseFloat($("#itemSalePrice").val()) || 0) * qty;
    $("#itemPayment").val(total.toFixed(2));
  }

  // 🔗 Event bindings
  $("#itemPrice").on("input", function () {
    calculatePayment("price");
  });
  $("#itemQty").on("input", function () {
    calculatePayment("qty");
  });
  $("#itemDiscount").on("input", function () {
    calculatePayment("discount");
  });
  $("#itemSalePrice").on("input", function () {
    calculatePayment("salePrice");
  });

  $("#paidAmount").on("input", function () {
    const paidAmount = parseFloat($(this).val()) || 0;
    const finalTotal = parseFloat($("#finalTotal").val().replace(/,/g, '')) || 0;
    const balanceAmount = finalTotal - paidAmount;
    $("#balanceAmount").val(balanceAmount.toFixed(2));
});


  // Get all ARN IDs from the table
  function getAllArnIds() {
    let arnIds = [];

    $("#invoiceItemsBody .btn-remove-item").each(function () {
      let arnId = $(this).data("arn-id");
      arnIds.push(arnId);
    });

    return arnIds;
  }

  // CANCEL INVOICE FUNCTION
  $(document).on("click", ".cancel-invoice", function () {
    const invoiceId = $("#invoice_id").val();
    let arnIds = getAllArnIds();

    swal(
      {
        title: "Are you sure?",
        text: "You will not be able to recover this approvel course request.!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: "Yes, Cancel it!",
        closeOnConfirm: false,
      },
      function () {
        $.ajax({
          url: "ajax/php/sales-invoice.php",
          type: "POST",
          data: {
            action: "cancel",
            id: invoiceId,
            arnIds: arnIds,
          },
          dataType: "JSON",
          success: function (jsonStr) {
            if (jsonStr.status === "already_cancelled") {
              swal({
                title: "Already Cancelled!",
                text: "This invoice has already been cancelled.",
                type: "warning",
                timer: 2000,
                showConfirmButton: true,
              });
              return;
            } else if (jsonStr.status === "success") {
              swal({
                title: "Cancelled!",
                text: "The invoice has been cancelled successfully.",
                type: "success",
                timer: 2000,
                showConfirmButton: false,
              });

              // Update UI to show cancelled state
              $(".cancel-invoice").hide();
              $("#cancelled-badge").show();

              // Optional: Disable form elements
              $("#form-data :input").prop("disabled", true);

              // Remove any existing success messages after delay and refresh page
              setTimeout(function () {
                $(".swal2-container").fadeOut();
                location.reload(); // Refresh the page after successful cancellation
              }, 2000);
            } else if (jsonStr.status === "error") {
              swal({
                title: "Error!",
                text: "Failed to cancel the invoice. Please try again.",
                type: "error",
                timer: 3000,
                showConfirmButton: false,
              });
            }
          },
        });
      }
    );
  });

  // ADD CLICK EVENT LISTENER TO CUSTOMER NAME FIELD
  $("#customer_name").on("click", function () {
    // Clear customer-related fields

    $("#customer_name").val("");
    $("#customer_address").val("");
    $("#customer_mobile").val("");
    $("#recommended_person").val("");

    // Set focus back to customer name for better UX
    $(this).val("").focus();
  });

  $("#quotationBtn").on("click", function () {
    $("#quotationModel").modal("show");
  });

  function fetchQuotationData(quotationId) {
    $.ajax({
      url: "ajax/php/quotation.php",
      type: "POST",
      data: {
        action: "get_quotation",
        id: quotationId,
      },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          const quotation = response.data.quotation;
          const customer = response.data.customer;
          const items = response.data.items;
          // console.log('Quotation:', quotation);
          console.log("Customer:", customer.customer_code);

          $("#quotationModal").modal("hide");

          $("#quotation_ref_no").val(quotation.quotation_no || "");

          // Set customer information
          $("#customer_code").val(customer.customer_code || "");
          $("#customer_name").val(customer.customer_name || "");
          $("#customer_address").val(customer.address || "");
          $("#customer_mobile").val(customer.mobile_number || "");

          $("#invoiceItemsBody").empty();

          // Add items to the table
          if (items.length > 0) {
            items.forEach(function (item) {
              const discount = parseFloat(item.discount) || 0;
              const price = parseFloat(item.price) || 0;
              const qty = parseFloat(item.qty) || 0;
              const total = parseFloat(item.sub_total) || 0;

              const row = `
                            <tr>
                                <td>${
                                  item.item_code
                                }                                
                                <input type="hidden" class="item-id" value="${
                                  item.item_id
                                }"></td>
                                <td>${item.item_name}</td>
                                <td><input type="number" class="item-price form-control form-control-sm price"   value="${price}"  ></td>
                                <td><input type="number" class="item-qty form-control form-control-sm qty" value="${qty}"></td>
                                <td><input type="number" class="item-discount form-control form-control-sm discount" value="${discount}"></td>
                                <td><input type="text" class="item-total form-control form-control-sm totalPrice"  value="${total.toFixed(
                                  2
                                )}" readonly>
                                <td><button type="button" class="btn btn-sm btn-danger btn-remove-item" onclick="removeRow(this)">Remove</button></td>
                            </tr>
                            `;

              $("#invoiceItemsBody").append(row);
            });
          } else {
            // Add "No items" row if no items found
            $("#invoiceItemsBody").append(`
                            <tr id="noItemRow">
                                <td colspan="8" class="text-center text-muted">No items added</td>
                            </tr>
                        `);
          }
        } else {
          alert("No quotation data found");
        }
      },
      error: function (xhr, status, error) {
        console.error("Error fetching quotation data:", error);
        alert("Failed to load quotation data. Please try again.");
      },
    });
    updateFinalTotal();
  }

  // Row click → populate form
  $("#quotationTableBody tr").on("click", function () {
    const id = $(this).data("id");
    if (id) {
      fetchQuotationData(id);
    }
  });

  //PRINT INVOICE
  $(document).on("click", "#print", function () {
    const invoiceId = $("#invoice_id").val();

    if (invoiceId === "") {
      swal({
        title: "Warning!",
        text: "Please enter a valid Invoice ID before printing.",
        type: "warning",
        timer: 2000,
        showConfirmButton: false,
      });
    } else {
      window.location.href = "invoice.php?invoice_no=" + invoiceId;
    }
  });

  // DAG Selection Handler
  $(document).on("click", ".select-dag", function () {
    const data = $(this).data();
    
    // Set DAG information
    $("#dag_id").val(data.id);
    $("#ref_no").val(data.ref_no);
    
    // Set customer information
    $("#customer_code").val(data.customer_code);
    $("#customer_name").val(data.customer_name);
    $("#customer_id").val(data.customer_id);
    $("#department_id").val(data.department_id);
    
    // Close modal
    $("#dagModel").modal("hide");
    
    // Hide item table and show DAG table
    $("#invoiceTable").hide();
    $("#addItemTable").hide();
    $("#dagTableHide").show();
    
    // Clear DAG items table
    $("#dagItemsBodyInvoice").empty();
    
    // Fetch DAG items
    fetchDagItems(data.id);
  });

  // Function to fetch DAG items
  function fetchDagItems(dagId) {
    $.ajax({
      url: "ajax/php/create-dag.php",
      type: "POST",
      data: { 
        dag_id: dagId,
        for_invoice: true // Only get non-invoiced items
      },
      dataType: "json",
      success: function (response) {
        if (response.status === "success" && response.data.length > 0) {
          const items = response.data;
          
          items.forEach(function (item) {
            const row = `
              <tr class="dag-item-row">
                <td>${item.vehicle_no}</td>
                <td>${item.belt_title || ''}</td>
                <td>${item.size_name || ''}</td>
                <td>${item.serial_number || ''}</td>
                <td>
                  <input type="number" class="form-control form-control-sm dag-cost" 
                         value="${item.total_amount || '0.00'}" step="0.01" min="0" 
                         data-dag-item-id="${item.id}">
                </td>
                <td>
                  <input type="number" class="form-control form-control-sm dag-price" 
                         value="${item.casing_cost || '0.00'}" step="0.01" min="0" 
                         data-dag-item-id="${item.id}">
                </td>
                <td>
                  <button type="button" class="btn btn-sm btn-danger remove-dag-item" 
                          data-dag-item-id="${item.id}">
                    <i class="uil uil-trash-alt"></i>
                  </button>
                </td>
              </tr>
            `;
            $("#dagItemsBodyInvoice").append(row);
          });
          
          // Remove "no items" row if it exists
          $("#noDagItemRow").remove();
          
        } else {
          $("#dagItemsBodyInvoice").html(`
            <tr id="noDagItemRow">
              <td colspan="7" class="text-center text-muted">No items found for this DAG</td>
            </tr>
          `);
        }
      },
      error: function () {
        swal("Error!", "Failed to load DAG items.", "error");
      }
    });
  }

  // Handle price input changes for DAG items
  $(document).on('input', '.dag-price', function () {
    const row = $(this).closest('tr');
    const price = parseFloat($(this).val()) || 0;
    const costInput = row.find('.dag-cost');
    const cost = parseFloat(costInput.val()) || 0;

    // If cost is higher than price, reset cost to price value
    if (cost > price) {
      costInput.val(price.toFixed(2));
      swal({
        title: "Warning!",
        text: "Cost cannot exceed the selling price. Cost has been adjusted to match the price.",
        type: "warning",
        timer: 3000,
        showConfirmButton: false,
      });
    }

    calculateDagTotals();
  });

  // Handle cost input changes for DAG items
  $(document).on('input', '.dag-cost', function () {
    const row = $(this).closest('tr');
    const cost = parseFloat($(this).val()) || 0;
    const price = parseFloat(row.find('.dag-price').val()) || 0;

    // If cost exceeds price, prevent the change and show warning
    if (cost > price) {
      $(this).val(price.toFixed(2));
      swal({
        title: "Invalid Cost!",
        text: "Cost cannot be higher than the selling price.",
        type: "error",
        timer: 3000,
        showConfirmButton: false,
      });
    }

    calculateDagTotals();
  });

  // Remove DAG item
  $(document).on('click', '.remove-dag-item', function () {
    $(this).closest('tr').remove();
    calculateDagTotals();
    
    // Show "no items" row if no items left
    if ($("#dagItemsBodyInvoice tr").length === 0) {
      $("#dagItemsBodyInvoice").html(`
        <tr id="noDagItemRow">
          <td colspan="7" class="text-center text-muted">No items added</td>
        </tr>
      `);
    }
  });

  // Calculate DAG totals
  function calculateDagTotals() {
    let subTotal = 0;
    
    $("#dagItemsBodyInvoice .dag-price").each(function () {
      const price = parseFloat($(this).val()) || 0;
      subTotal += price;
    });
    
    // Update totals
    $("#subTotal").val(subTotal.toFixed(2));
    $("#finalTotal").val(subTotal.toFixed(2));
  }

  // SALES ORDERS MODAL
  $("#salesOrdersModal").on("shown.bs.modal", function () {
    // Clear search input when modal opens
    $('#salesOrdersSearch').val('');
    loadAllSalesOrders();
  });

  // Function to load all sales orders for the modal
  function loadAllSalesOrders() {
    $("#noSalesOrdersRow").html('<td colspan="7" class="text-center text-secondary py-3"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Loading sales orders, please wait...</td>');
    
    // Collect item codes from current invoice items
    let currentItemCodes = [];
    $("#invoiceItemsBody tr").each(function () {
      const itemCode = $(this).find('input[name="item_codes[]"]').val();
      if (itemCode && !currentItemCodes.includes(itemCode)) {
        currentItemCodes.push(itemCode);
      }
    });
    
    $.ajax({
      url: "ajax/php/sales-invoice.php",
      method: "POST",
      data: {
        action: "fetch_sales_orders",
        invoice_type: $("#invoice_type").val(),
        current_item_codes: JSON.stringify(currentItemCodes) // Send current item codes for filtering
      },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          populateSalesOrdersModal(response.data);
        } else {
          $("#salesOrdersTableBody").html('<tr><td colspan="7" class="text-center text-danger py-3">Failed to load sales orders</td></tr>');
        }
      },
      error: function () {
        $("#salesOrdersTableBody").html('<tr><td colspan="7" class="text-center text-danger py-3">Error loading sales orders</td></tr>');
      }
    });
  }


  
  // Store original orders data for filtering
  let originalOrdersData = [];

  // Populate the sales orders modal table
  function populateSalesOrdersModal(orders) {
    originalOrdersData = orders; // Store original data for filtering
    
    if (orders.length === 0) {
      $("#salesOrdersTableBody").html('<tr><td colspan="7" class="text-center text-muted py-3">No sales orders found</td></tr>');
      return;
    }

    renderOrdersTable(orders);
    
    // Initialize DataTable for pagination only
    if ($.fn.DataTable.isDataTable('#salesOrdersTable')) {
      $('#salesOrdersTable').DataTable().destroy();
    }
    
    var salesOrdersTable = $('#salesOrdersTable').DataTable({
      pageLength: 10,
      searching: false, // Disable default search
      lengthChange: false,
      info: true,
      paging: true,
      ordering: false, // Disable ordering to prevent conflicts
      columnDefs: [
        { orderable: false, targets: '_all' } // Disable ordering on all columns
      ]
    });

    // Setup real-time search
    setupRealTimeSearch();
  }

  // Render orders table rows
  function renderOrdersTable(orders) {
    let tbody = "";
    orders.forEach(function(order, index) {
      const statusClass = order.status === 0 ? 'badge bg-warning' : 
                         order.status === 1 ? 'badge bg-success' : 
                         order.status === 2 ? 'badge bg-danger' : 'badge bg-secondary';

      tbody += `<tr class="sales-order-select" 
                      data-order-id="${order.order_db_id}"
                      data-order-data='${JSON.stringify(order)}'
                      data-search-text="${order.order_id.toLowerCase()} ${order.customer_name.toLowerCase()} ${(order.marketing_executive_name || '').toLowerCase()}">
                  <td>${index + 1}</td>
                  <td>${order.order_id}</td>
                  <td>${order.order_date}</td>
                  <td>${order.customer_name}</td>
                  <td>${order.marketing_executive_name || 'N/A'}</td>
                  <td><span class="${statusClass}">${order.status_text}</span></td>
                  <td><button type="button" class="btn btn-primary btn-sm select-order-btn">Select</button></td>
                </tr>`;
    });
    
    $("#salesOrdersTableBody").html(tbody);
  }

  // Setup real-time search functionality
  function setupRealTimeSearch() {
    $('#salesOrdersSearch').off('keyup input').on('keyup input', function() {
      const searchTerm = $(this).val().toLowerCase().trim();
      
      if (searchTerm === '') {
        // Show all rows if search is empty
        $("#salesOrdersTable tbody tr").show();
      } else {
        // Filter rows based on search term
        $("#salesOrdersTable tbody tr").each(function() {
          const searchText = $(this).data('search-text') || '';
          if (searchText.includes(searchTerm)) {
            $(this).show();
          } else {
            $(this).hide();
          }
        });
      }
      
      // Update DataTable display after filtering
      if ($.fn.DataTable.isDataTable('#salesOrdersTable')) {
        $('#salesOrdersTable').DataTable().draw(false);
      }
    });
  }

  // Handle sales order selection
  $(document).on('click', '.select-order-btn', function () {
    const row = $(this).closest('tr');
    const orderDataJson = row.data('order-data');
    
    try {
      const orderData = typeof orderDataJson === 'string' ? JSON.parse(orderDataJson) : orderDataJson;
      
      // Confirm selection with SweetAlert
      swal({
        title: "Confirm Selection",
        text: `Are you sure you want to load sales order "${orderData.order_id}"?\n\nThis will populate customer details and load all items from this order into the Sales Rep Orders table for manual selection.`,
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, Load Order",
        cancelButtonText: "Cancel"
      }, function(isConfirm) {
        if (isConfirm) {
          loadSalesOrderToInvoice(orderData);
          $("#salesOrdersModal").modal("hide");
        }
      });
    } catch (e) {
      console.error('Error parsing order data:', e);
      swal("Error", "Failed to load sales order data", "error");
    }
  });

  // Function to load sales order data to invoice
  function loadSalesOrderToInvoice(orderData) {
    console.log('Loading sales order to invoice:', orderData);
    
    // Ensure Sales Rep Orders functionality is enabled
    if (!$("#sales_rep_orders").is(":checked")) {
      $("#sales_rep_orders").prop("checked", true).trigger("change");
    }
    
    // Populate customer fields
    $("#customer_id").val(orderData.customer_id || '');
    $("#customer_code").val(orderData.customer_code || '');
    $("#customer_name").val(orderData.customer_name || '');
    $("#customer_mobile").val(orderData.customer_mobile || '');
    $("#customer_address").val(orderData.customer_address || '');
    
    // Populate marketing executive
    if (orderData.marketing_executive_id) {
      $("#marketing_executive").val(orderData.marketing_executive_id).trigger('change');
    }
    
    // Load items into Sales Rep Orders table instead of directly to invoice
    if (orderData.items && orderData.items.length > 0) {
      // Populate the sales rep orders table with this single order
      populateSalesRepOrdersTable([orderData]);
      
      // Show success message
      swal({
        title: "Success!",
        text: `Sales order "${orderData.order_id}" has been loaded successfully. Customer details have been populated and all items are now available in the Sales Rep Orders table for manual selection.`,
        type: "success",
        timer: 4000,
        showConfirmButton: false
      });
    } else {
      // No items, just show customer data loaded
      $("#customer_code").trigger('change');
      $("#customer_name").trigger('change');
      
      swal({
        title: "Success!",
        text: `Sales order "${orderData.order_id}" customer details have been loaded. No items found in this order.`,
        type: "success",
        timer: 2000,
        showConfirmButton: false
      });
    }
  }


  // Fetch sales orders data
  function fetchSalesOrders(departmentId) {
    const invoiceType = $("#invoice_type").val();
    
    // Collect item codes from current invoice items for filtering
    let currentItemCodes = [];
    $("#invoiceItemsBody tr").each(function () {
      const itemCode = $(this).find('input[name="item_codes[]"]').val();
      if (itemCode && !currentItemCodes.includes(itemCode)) {
        currentItemCodes.push(itemCode);
      }
    });
    
    $.ajax({
      url: "ajax/php/sales-invoice.php",
      method: "POST",
      data: {
        action: "fetch_sales_orders",
        department_id: departmentId,
        invoice_type: invoiceType,
        current_item_codes: JSON.stringify(currentItemCodes) // Send current item codes for filtering
      },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          // Store data globally for auto-fill functionality
          currentSalesOrdersData = response.data;
          console.log('Sales orders data stored globally:', currentSalesOrdersData);
          
          populateSalesRepOrdersTable(response.data);
        } else {
          swal("Error", response.message || "Failed to fetch sales orders", "error");
          $("#sales_rep_orders").prop("checked", false);
          $("#salesRepOrdersTable").hide();
        }
      },
      error: function () {
        swal("Error", "Failed to fetch sales orders", "error");
        $("#sales_rep_orders").prop("checked", false);
        $("#salesRepOrdersTable").hide();
      }
    });
  }

  // Clear sales rep orders table
  function clearSalesRepOrdersTable() {
    $("#noSalesRepOrdersRow").show();
    $("#salesRepOrdersBody tr.sales-order-row").remove();
  }

  // Populate sales rep orders table
  function populateSalesRepOrdersTable(orders) {
    clearSalesRepOrdersTable();

    if (orders.length === 0) {
      $("#noSalesRepOrdersRow").show();
      return;
    }

    // Get current invoice type to determine which price to show
    const invoiceType = $("#invoice_type").val();
    const priceType = invoiceType === "customer" ? "customer_price" : "dealer_price";
    const priceLabel = invoiceType === "customer" ? "Customer Price" : "Dealer Price";

    // Update table header dynamically
    $("#salesRepOrdersTable thead th:nth-child(6)").text(priceLabel);

    let tbody = "";
    orders.forEach(function(order) {
      order.items.forEach(function(item) {
        // Use the appropriate price based on invoice type, fallback to dealer_price if customer_price is 0 or null
        const selectedPrice = invoiceType === "customer" 
          ? (parseFloat(item.customer_price) || parseFloat(item.dealer_price)) 
          : parseFloat(item.dealer_price);
        const sellingPrice = selectedPrice; // Initial selling price is the selected price

        const orderDataObject = {
          customer_id: order.customer_id,
          customer_code: order.customer_code,
          customer_name: order.customer_name,
          customer_mobile: order.customer_mobile,
          customer_address: order.customer_address,
          marketing_executive_id: order.marketing_executive_id,
          marketing_executive_name: order.marketing_executive_name
        };
        
        const orderDataJson = JSON.stringify(orderDataObject);

        tbody += `<tr class="sales-order-row" 
                      data-item-id="${item.item_id}" 
                      data-order-db-id="${order.order_db_id}"
                      data-order-data='${orderDataJson}'
                      data-customer-price="${item.customer_price}"
                      data-dealer-price="${item.dealer_price}">
                    <td>${order.order_id}</td>
                    <td>${item.item_code}</td>
                    <td>${item.item_name}</td>
                    <td class="order-qty">${item.order_qty}</td>
                    <td class="stock-qty">${item.stock_qty}</td>
                    <td class="selected-price">${selectedPrice.toFixed(2)}</td>
                    <td><input type="number" class="form-control discount-input" value="0" min="0" max="100" style="width: 70px;"></td>
                    <td class="selling-price">${sellingPrice.toFixed(2)}</td>
                    <td><button type="button" class="btn btn-success btn-sm add-to-invoice">Add to Invoice</button></td>
                  </tr>`;
      });
    });
    
    $("#noSalesRepOrdersRow").hide();
    $("#salesRepOrdersBody").append(tbody);
  }

  // Handle discount changes in sales rep orders table
  $(document).on("input", ".discount-input", function() {
    const row = $(this).closest("tr");
    const selectedPrice = parseFloat(row.find(".selected-price").text()) || 0;
    const discountPercent = parseFloat($(this).val()) || 0;

    const discountAmount = (selectedPrice * discountPercent) / 100;
    const sellingPrice = selectedPrice - discountAmount;

    row.find(".selling-price").text(sellingPrice.toFixed(2));
  });

  // Add to invoice functionality
  $(document).on('click', '.add-to-invoice', function () {
    const row = $(this).closest('tr');
    const orderId = row.find('td').eq(0).text();
    const orderDbId = row.data('order-db-id');
    const itemId = row.data('item-id');
    const itemCode = row.find('td').eq(1).text();
    const itemName = row.find('td').eq(2).text();
    const orderQty = parseInt(row.find('.order-qty').text()) || 0;
    const stockQty = parseInt(row.find('.stock-qty').text()) || 0;
    const selectedPrice = row.find('.selected-price').text();
    const discount = row.find('.discount-input').val();
    const sellingPrice = row.find('.selling-price').text();

    // Check stock availability
    if (stockQty <= 0) {
      swal({
        title: "No Stock Available",
        text: `Item "${itemName}" (${itemCode}) has no stock available. Cannot add to invoice.`,
        type: "warning",
        confirmButtonText: "OK"
      });
      return;
    }

    // Determine quantity to add (minimum of order qty and stock qty)
    const qtyToAdd = Math.min(orderQty, stockQty);
    
    // Show confirmation if we're adding less than ordered quantity
    if (qtyToAdd < orderQty) {
      swal({
        title: "Limited Stock Available",
        text: `Item "${itemName}" (${itemCode}) has only ${stockQty} units in stock, but ${orderQty} units were ordered. Only ${qtyToAdd} units will be added to the invoice.`,
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Add Available Stock",
        cancelButtonText: "Cancel"
      }, function(isConfirm) {
        if (isConfirm) {
          processAddToInvoice(row, orderId, orderDbId, itemId, itemCode, itemName, qtyToAdd, selectedPrice, discount, sellingPrice);
        }
      });
    } else {
      // Stock is sufficient, add normally
      processAddToInvoice(row, orderId, orderDbId, itemId, itemCode, itemName, qtyToAdd, selectedPrice, discount, sellingPrice);
    }
  });

  // Helper function to process adding item to invoice
  function processAddToInvoice(row, orderId, orderDbId, itemId, itemCode, itemName, qty, selectedPrice, discount, sellingPrice) {

    // Get order data from the row's data attributes
    const orderDataJson = row.data('order-data');
    let orderData = null;
    
    try {
      if (typeof orderDataJson === 'string') {
        orderData = JSON.parse(orderDataJson);
      } else {
        orderData = orderDataJson;
      }
    } catch (e) {
      console.error('Error parsing order data:', e);
    }
    
    // Populate customer fields if not already filled
    if (orderData && !$("#customer_code").val()) {
      $("#customer_id").val(orderData.customer_id || '');
      $("#customer_code").val(orderData.customer_code || '');
      $("#customer_name").val(orderData.customer_name || '');
      $("#customer_mobile").val(orderData.customer_mobile || '');
      $("#customer_address").val(orderData.customer_address || '');
      
      // Trigger change events
      $("#customer_code").trigger('change');
      $("#customer_name").trigger('change');
    }
    
    // Populate marketing executive field if not already filled
    if (orderData && orderData.marketing_executive_id && !$("#marketing_executive").val()) {
      $("#marketing_executive").val(orderData.marketing_executive_id);
      $("#marketing_executive").trigger('change');
    }

    // Add to main invoice table using the validated quantity
    addSalesOrderItemToInvoice(orderId, itemId, itemCode, itemName, qty, selectedPrice, sellingPrice, discount, orderDbId);

    // Update sales order status to 1 (invoiced)
    updateSalesOrderStatus(orderDbId);

    // Remove from sales rep orders table
    row.remove();

    // Show "no items" row if no items left
    if ($("#salesRepOrdersBody tr:not(#noSalesRepOrdersRow)").length === 0) {
      $("#noSalesRepOrdersRow").show();
    }
  }

  // Function to update sales order status to 1 (invoiced)
  function updateSalesOrderStatus(orderDbId) {
    $.ajax({
      url: "ajax/php/sales-invoice.php",
      method: "POST",
      data: {
        action: "update_sales_order_status",
        order_id: orderDbId,
        status: 1
      },
      dataType: "json",
      success: function(response) {
        if (response.status !== "success") {
          console.error("Failed to update sales order status:", response.message);
        }
      },
      error: function() {
        console.error("Error updating sales order status");
      }
    });
  }

  // Function to add sales order item to main invoice table
  function addSalesOrderItemToInvoice(orderId, itemId, itemCode, itemName, qty, selectedPrice, sellingPrice, discount, orderDbId) {
    // Hide no items row
    $("#noInvoiceItemRow").hide();

    // Calculate total based on selling price * quantity
    const total = parseFloat(sellingPrice) * parseFloat(qty);

    // Get customer and dealer prices from the row data attributes
    const row = $(`.sales-order-row[data-item-id="${itemId}"][data-order-db-id="${orderDbId}"]`);
    const customerPrice = parseFloat(row.data("customer-price")) || parseFloat(row.data("dealer-price"));
    const dealerPrice = parseFloat(row.data("dealer-price"));

    // For sales rep orders, we need to find an available ARN or use the item code
    // Since sales rep orders might not have specific ARNs, we'll use the item code
    const arnNo = itemCode; // Use item code as ARN identifier for sales orders
    const cost = 0; // No specific cost for sales orders

    // Create new row matching the exact structure of existing invoice items
    const newRow = `<tr>
                      <td>${itemCode}
                          <input type="hidden" name="item_id[]" value="${itemId}">
                          <input type="hidden" name="item_codes[]" value="${itemCode}">
                          <input type="hidden" name="customer_prices[]" value="${customerPrice}">
                          <input type="hidden" name="dealer_prices[]" value="${dealerPrice}">
                          <input type="hidden" name="arn_ids[]" value="${arnNo}">
                          <input type="hidden" name="arn_costs[]" value="${cost}">
                          <input type="hidden" name="service_qty[]" value="0">
                          <input type="hidden" name="vehicle_no[]" value="">
                          <input type="hidden" name="current_km[]" value="">
                          <input type="hidden" name="next_service_days[]" value="">
                          <input type="hidden" name="sales_order_ids[]" value="${orderDbId}">
                      </td>
                      <td>${itemName}</td>
                      <td class="item-price">${parseFloat(sellingPrice).toFixed(2)}</td>
                      <td class="item-qty">${qty}</td>
                      <td class="item-discount">${discount}</td>
                      <td>${total.toFixed(2)}</td>
                      <td>
                          <button type="button" class="btn btn-sm btn-danger btn-remove-item" data-code="${itemCode}" data-qty="${qty}" data-arn-id="${arnNo}">Remove</button>
                      </td>
                    </tr>`;

    $("#invoiceItemsBody").append(newRow);
    updateFinalTotal();
  }

});
