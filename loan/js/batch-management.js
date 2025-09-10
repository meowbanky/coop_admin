/**
 * Batch Management System - Modern JavaScript
 * ES6+ with modular architecture
 */

class BatchManager {
  constructor() {
    this.currentPage = 1;
    this.itemsPerPage = 10;
    this.totalItems = 0;
    this.totalPages = 0;
    this.searchTerm = "";
    this.allBatches = [];

    this.init();
  }

  init() {
    this.bindEvents();
    this.initializePagination();
    this.setupFormValidation();
  }

  bindEvents() {
    // Generate batch number
    document.getElementById("generate-batch")?.addEventListener("click", () => {
      this.generateBatchNumber();
    });

    // Loan modal events - handled by global function openLoanModal()

    document
      .getElementById("close-loan-modal")
      ?.addEventListener("click", () => {
        this.closeLoanModal();
      });

    document.getElementById("cancel-loan")?.addEventListener("click", () => {
      this.closeLoanModal();
    });

    document.getElementById("save-loan")?.addEventListener("click", () => {
      // Check if this is post account mode or regular loan insertion
      if (
        this.currentBatchNumber &&
        document
          .querySelector("#loan-modal h3")
          .textContent.includes("Post Account")
      ) {
        this.savePostAccount();
      } else {
        this.saveLoan();
      }
    });

    // Form submission
    document.getElementById("batch-form")?.addEventListener("submit", (e) => {
      this.handleFormSubmit(e);
    });

    // Search functionality
    document
      .getElementById("search-batches")
      ?.addEventListener("input", (e) => {
        this.searchTerm = e.target.value.toLowerCase();
        this.filterAndPaginate();
      });

    // Items per page change
    document
      .getElementById("items-per-page")
      ?.addEventListener("change", (e) => {
        this.itemsPerPage = parseInt(e.target.value);
        this.currentPage = 1;
        this.initializePagination();
      });

    // Auto-hide success messages
    this.autoHideMessages();
  }

  generateBatchNumber() {
    const now = new Date();

    // Get day with ordinal suffix
    const day = now.getDate();
    const daySuffix = this.getOrdinalSuffix(day);

    // Get month name
    const monthNames = [
      "January",
      "February",
      "March",
      "April",
      "May",
      "June",
      "July",
      "August",
      "September",
      "October",
      "November",
      "December",
    ];
    const month = monthNames[now.getMonth()];

    // Get year in short format
    const year = now.getFullYear().toString().slice(-2);

    // Get time in 12-hour format with AM/PM (no colon)
    let hours = now.getHours();
    const minutes = now.getMinutes().toString().padStart(2, "0");
    const ampm = hours >= 12 ? "PM" : "AM";
    hours = hours % 12;
    hours = hours ? hours : 12; // 0 should be 12
    const time = `${hours}${minutes}${ampm}`;

    // Combine all parts (no special characters, only letters and numbers)
    const batchNumber = `${day}${daySuffix}${month}${year}${time}`;

    document.getElementById("batch-number").value = batchNumber;
    Swal.fire({
      title: "Generated!",
      text: "Batch number generated successfully!",
      icon: "success",
      timer: 1500,
      showConfirmButton: false,
    });
  }

  getOrdinalSuffix(day) {
    if (day >= 11 && day <= 13) {
      return "th";
    }

    switch (day % 10) {
      case 1:
        return "st";
      case 2:
        return "nd";
      case 3:
        return "rd";
      default:
        return "th";
    }
  }

  async handleFormSubmit(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const batchNumber = formData.get("batch");

    if (!this.validateBatchNumber(batchNumber)) {
      return;
    }

    this.showLoading(true);

    try {
      const response = await fetch("api/batch.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (result.success) {
        await Swal.fire({
          title: "Success!",
          text: result.message,
          icon: "success",
          timer: 2000,
          showConfirmButton: false,
        });
        e.target.reset();
        // Reload page to show new batch
        setTimeout(() => window.location.reload(), 2000);
      } else {
        this.showNotification(result.message, "error");
      }
    } catch (error) {
      this.showNotification("An error occurred. Please try again.", "error");
    } finally {
      this.showLoading(false);
    }
  }

  validateBatchNumber(batchNumber) {
    const errors = [];

    if (!batchNumber || batchNumber.trim() === "") {
      errors.push("Batch number is required");
    }

    if (!/^[A-Za-z0-9]+$/.test(batchNumber)) {
      errors.push("Batch number can only contain letters and numbers");
    }

    if (batchNumber.length < 3) {
      errors.push("Batch number must be at least 3 characters long");
    }

    if (batchNumber.length > 50) {
      errors.push("Batch number cannot exceed 50 characters");
    }

    if (errors.length > 0) {
      this.showNotification(errors.join("<br>"), "error");
      return false;
    }

    return true;
  }

  initializePagination() {
    this.collectBatchData();
    this.totalItems = this.getFilteredBatches().length;
    this.totalPages = Math.ceil(this.totalItems / this.itemsPerPage);

    if (this.totalItems > 0) {
      this.updatePagination();
      this.showPage(1);
    } else {
      this.hidePagination();
    }
  }

  collectBatchData() {
    const tableRows = document.querySelectorAll("#batches-table tbody tr");
    this.allBatches = Array.from(tableRows).map((row) => ({
      element: row,
      batchId: row.cells[0]?.textContent?.trim() || "",
      batchNumber: row.cells[1]?.textContent?.trim() || "",
      transactions: row.cells[2]?.textContent?.trim() || "",
      status: row.cells[3]?.textContent?.trim() || "",
      visible: true,
    }));
  }

  getFilteredBatches() {
    if (!this.searchTerm) {
      return this.allBatches;
    }

    return this.allBatches.filter(
      (batch) =>
        batch.batchId.toLowerCase().includes(this.searchTerm) ||
        batch.batchNumber.toLowerCase().includes(this.searchTerm) ||
        batch.transactions.toLowerCase().includes(this.searchTerm)
    );
  }

  filterAndPaginate() {
    this.currentPage = 1;
    this.initializePagination();
  }

  showPage(page) {
    const filteredBatches = this.getFilteredBatches();
    const startIndex = (page - 1) * this.itemsPerPage;
    const endIndex = startIndex + this.itemsPerPage;

    // Hide all rows first
    this.allBatches.forEach((batch) => {
      batch.element.style.display = "none";
    });

    // Show only the rows for current page
    filteredBatches.slice(startIndex, endIndex).forEach((batch) => {
      batch.element.style.display = "";
    });

    this.currentPage = page;
    this.updatePagination();
  }

  updatePagination() {
    const paginationContainer = document.getElementById("pagination-controls");
    const pageInfo = document.getElementById("page-info");

    if (!paginationContainer || !pageInfo) return;

    // Update page info
    const startItem = (this.currentPage - 1) * this.itemsPerPage + 1;
    const endItem = Math.min(
      this.currentPage * this.itemsPerPage,
      this.totalItems
    );
    pageInfo.textContent = `Showing ${startItem} to ${endItem} of ${this.totalItems} results`;

    // Generate pagination buttons
    let paginationHtml = "";

    // Previous button
    if (this.currentPage > 1) {
      paginationHtml += this.createPaginationButton(
        this.currentPage - 1,
        '<i class="fas fa-chevron-left"></i>',
        "rounded-l-md"
      );
    } else {
      paginationHtml += this.createPaginationButton(
        null,
        '<i class="fas fa-chevron-left"></i>',
        "rounded-l-md",
        true
      );
    }

    // Page numbers
    const startPage = Math.max(1, this.currentPage - 2);
    const endPage = Math.min(this.totalPages, this.currentPage + 2);

    if (startPage > 1) {
      paginationHtml += this.createPaginationButton(1, "1");
      if (startPage > 2) {
        paginationHtml +=
          '<span class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border-t border-b border-gray-300">...</span>';
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      const isActive = i === this.currentPage;
      paginationHtml += this.createPaginationButton(
        i,
        i.toString(),
        "",
        false,
        isActive
      );
    }

    if (endPage < this.totalPages) {
      if (endPage < this.totalPages - 1) {
        paginationHtml +=
          '<span class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border-t border-b border-gray-300">...</span>';
      }
      paginationHtml += this.createPaginationButton(
        this.totalPages,
        this.totalPages.toString()
      );
    }

    // Next button
    if (this.currentPage < this.totalPages) {
      paginationHtml += this.createPaginationButton(
        this.currentPage + 1,
        '<i class="fas fa-chevron-right"></i>',
        "rounded-r-md"
      );
    } else {
      paginationHtml += this.createPaginationButton(
        null,
        '<i class="fas fa-chevron-right"></i>',
        "rounded-r-md",
        true
      );
    }

    paginationContainer.innerHTML = paginationHtml;
  }

  createPaginationButton(
    page,
    text,
    extraClasses = "",
    disabled = false,
    active = false
  ) {
    const baseClasses = "px-3 py-2 text-sm font-medium border border-gray-300";
    const hoverClasses = disabled ? "" : "hover:bg-gray-50 hover:text-gray-700";
    const activeClasses = active
      ? "text-white bg-primary border-primary"
      : "text-gray-500 bg-white";
    const disabledClasses = disabled
      ? "text-gray-300 bg-gray-100 cursor-not-allowed"
      : "";
    const roundedClasses = extraClasses;

    const classes = `${baseClasses} ${
      disabled ? disabledClasses : activeClasses
    } ${hoverClasses} ${roundedClasses}`;

    if (disabled) {
      return `<button disabled class="${classes}">${text}</button>`;
    } else {
      return `<button onclick="batchManager.showPage(${page})" class="${classes}">${text}</button>`;
    }
  }

  hidePagination() {
    const paginationContainer = document.getElementById("pagination-controls")
      ?.parentElement?.parentElement;
    if (paginationContainer) {
      paginationContainer.style.display = "none";
    }
  }

  showLoading(show) {
    const overlay = document.getElementById("loading-overlay");
    if (overlay) {
      overlay.classList.toggle("hidden", !show);
    }
  }

  showNotification(message, type = "info") {
    const notification = document.createElement("div");
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full max-w-md`;

    const bgColor =
      type === "success"
        ? "bg-green-500"
        : type === "error"
        ? "bg-red-500"
        : type === "warning"
        ? "bg-yellow-500"
        : "bg-blue-500";

    const icon =
      type === "success"
        ? "check-circle"
        : type === "error"
        ? "exclamation-circle"
        : type === "warning"
        ? "exclamation-triangle"
        : "info-circle";

    notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-${icon} text-white mr-3"></i>
                <div class="text-white">${message}</div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

    notification.classList.add(bgColor);
    document.body.appendChild(notification);

    // Animate in
    setTimeout(() => {
      notification.classList.remove("translate-x-full");
    }, 100);

    // Auto remove after 5 seconds
    setTimeout(() => {
      notification.classList.add("translate-x-full");
      setTimeout(() => notification.remove(), 300);
    }, 5000);
  }

  setupFormValidation() {
    const batchInput = document.getElementById("batch-number");
    if (batchInput) {
      batchInput.addEventListener("input", (e) => {
        // Only allow alphanumeric characters
        e.target.value = e.target.value.replace(/[^A-Za-z0-9]/g, "");
      });
    }
  }

  autoHideMessages() {
    const messages = document.querySelectorAll(".bg-green-100, .bg-red-100");
    messages.forEach((message) => {
      setTimeout(() => {
        message.style.opacity = "0";
        setTimeout(() => message.remove(), 300);
      }, 5000);
    });
  }

  // Loan Modal Methods
  async openLoanModal(batchNumber = null) {
    try {
      // Store batch number for later use
      this.currentBatchNumber = batchNumber;

      // Set today's date as default
      const today = new Date().toISOString().split("T")[0];
      document.getElementById("date-of-loan-app").value = today;

      // Load payroll periods
      await this.loadPayrollPeriods();

      // Setup member search autocomplete
      this.setupLoanMemberSearch();

      // Show modal
      document.getElementById("loan-modal").classList.remove("hidden");
    } catch (error) {
      console.error("Error opening loan modal:", error);
      this.showNotification("Error opening loan modal", "error");
    }
  }

  closeLoanModal() {
    document.getElementById("loan-modal").classList.add("hidden");

    // Reset modal title and button text
    document.querySelector("#loan-modal h3").innerHTML =
      '<i class="fas fa-plus-circle text-green-600 mr-2"></i>Add New Loan';
    document.querySelector("#save-loan").innerHTML =
      '<i class="fas fa-save mr-2"></i>Save Loan';

    // Reset form fields
    document.getElementById("payroll-period").value = "";

    // Clear beneficiaries table
    const tbody = document.getElementById("beneficiaries-table-body");
    if (tbody) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" class="px-4 py-8 text-center text-gray-500">
            <i class="fas fa-spinner fa-spin mr-2"></i>
            Loading beneficiaries...
          </td>
        </tr>
      `;
    }

    // Reset batch info
    const batchInfoDiv = document.getElementById("batch-info");
    if (batchInfoDiv) {
      batchInfoDiv.innerHTML = `
        <div class="flex items-center">
          <i class="fas fa-info-circle text-blue-600 mr-2"></i>
          <div>
            <h4 class="font-semibold text-blue-800">Batch Information</h4>
            <p class="text-sm text-blue-600">Loading batch details...</p>
          </div>
        </div>
      `;
    }

    // Clear current data
    this.currentBatchNumber = null;
    this.currentBatchBeneficiaries = null;
  }

  async loadPayrollPeriods() {
    try {
      console.log("Loading payroll periods...");
      const response = await fetch("api/loan.php?action=get_payroll_periods");
      const result = await response.json();

      console.log("Payroll periods API response:", result);

      if (result.success) {
        const select = document.getElementById("payroll-period");
        select.innerHTML = '<option value="">Select Payroll Period</option>';

        console.log("Adding payroll periods:", result.data);
        result.data.forEach((period) => {
          const option = document.createElement("option");
          option.value = period.id;
          option.textContent = `${period.PayrollPeriod} (${period.PhysicalYear} - ${period.PhysicalMonth})`;
          select.appendChild(option);
        });

        console.log("Payroll periods loaded successfully");
      } else {
        console.error("Failed to load payroll periods:", result.message);
      }
    } catch (error) {
      console.error("Error loading payroll periods:", error);
    }
  }

  setupLoanMemberSearch() {
    const searchInput = document.getElementById("loan-member-search");

    // Simple autocomplete setup (you can enhance this with jQuery UI if needed)
    searchInput.addEventListener("input", async (e) => {
      const query = e.target.value;
      if (query.length >= 2) {
        // You can implement member search here
        console.log("Searching for member:", query);
      }
    });
  }

  async saveLoan() {
    try {
      const formData = {
        action: "insert_loan",
        coop_id: document.getElementById("loan-member-id").value,
        date_of_loan_app: document.getElementById("date-of-loan-app").value,
        loan_amount: document.getElementById("loan-amount").value,
        monthly_repayment: document.getElementById("monthly-repayment").value,
        loan_period: document.getElementById("loan-period").value,
        payroll_period_id: document.getElementById("payroll-period").value,
        batch_number: this.currentBatchNumber,
        loan_status: 1,
        stationery_status: 0,
      };

      // Validate form
      if (!this.validateLoanForm(formData)) {
        return;
      }

      this.showLoading(true);

      const response = await fetch("api/loan.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(formData),
      });

      const result = await response.json();

      if (result.success) {
        await Swal.fire({
          title: "Success!",
          text: result.message,
          icon: "success",
          timer: 2000,
          showConfirmButton: false,
        });

        this.closeLoanModal();
      } else {
        this.showNotification(result.message, "error");
      }
    } catch (error) {
      console.error("Loan save error:", error);
      this.showNotification("Error saving loan", "error");
    } finally {
      this.showLoading(false);
    }
  }

  validateLoanForm(data) {
    const errors = [];

    if (!data.coop_id) errors.push("Member selection is required");
    if (!data.date_of_loan_app)
      errors.push("Date of loan application is required");
    if (!data.loan_amount || data.loan_amount <= 0)
      errors.push("Valid loan amount is required");
    if (!data.monthly_repayment || data.monthly_repayment <= 0)
      errors.push("Valid monthly repayment is required");
    if (!data.loan_period || data.loan_period <= 0)
      errors.push("Valid loan period is required");
    if (!data.payroll_period_id)
      errors.push("Payroll period selection is required");

    if (errors.length > 0) {
      this.showNotification(errors.join("<br>"), "error");
      return false;
    }

    return true;
  }

  // Post Account Modal Methods
  async openPostAccountModal(batchNumber) {
    console.log("openPostAccountModal called with batch:", batchNumber);
    try {
      // Store batch number for later use
      this.currentBatchNumber = batchNumber;

      // Update modal title and button text
      document.querySelector("#loan-modal h3").innerHTML =
        '<i class="fas fa-upload text-yellow-600 mr-2"></i>Post Account - Batch: ' +
        batchNumber;
      document.querySelector("#save-loan").innerHTML =
        '<i class="fas fa-upload mr-2"></i>Post Account';

      // Load payroll periods
      await this.loadPayrollPeriods();

      // Load batch beneficiaries to show in table
      await this.loadBatchBeneficiaries(batchNumber);

      // Show modal
      document.getElementById("loan-modal").classList.remove("hidden");
    } catch (error) {
      console.error("Error opening post account modal:", error);
      this.showNotification("Error opening post account modal", "error");
    }
  }

  async loadBatchBeneficiaries(batchNumber) {
    try {
      const response = await fetch(
        `api/loan.php?action=get_batch_beneficiaries&batch_number=${encodeURIComponent(
          batchNumber
        )}`
      );
      const result = await response.json();

      if (result.success) {
        this.currentBatchBeneficiaries = result.data;
        console.log(
          `Found ${result.data.length} beneficiaries in batch ${batchNumber}`
        );

        // Populate the beneficiaries table
        this.populateBeneficiariesTable(result.data);

        // Update batch info
        this.updateBatchInfo(batchNumber, result.data.length);
      } else {
        this.showNotification(result.message, "error");
        this.populateBeneficiariesTable([]);
      }
    } catch (error) {
      console.error("Error loading batch beneficiaries:", error);
      this.populateBeneficiariesTable([]);
    }
  }

  populateBeneficiariesTable(beneficiaries) {
    const tbody = document.getElementById("beneficiaries-table-body");

    if (beneficiaries.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7" class="px-4 py-8 text-center text-gray-500">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            No beneficiaries found in this batch
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = beneficiaries
      .map(
        (beneficiary, index) => `
      <tr class="hover:bg-gray-50">
        <td class="px-4 py-3 text-center">
          <input type="checkbox" 
                 class="beneficiary-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" 
                 data-beneficiary-code="${beneficiary.BeneficiaryCode || ""}"
                 data-beneficiary-index="${index}"
                 checked>
        </td>
        <td class="px-4 py-3 text-sm text-gray-900 font-medium">
          ${beneficiary.BeneficiaryCode || "N/A"}
        </td>
        <td class="px-4 py-3 text-sm text-gray-900">
          ${beneficiary.BeneficiaryName || "N/A"}
        </td>
        <td class="px-4 py-3 text-sm text-gray-900">
          ${beneficiary.Bank || "N/A"}
        </td>
        <td class="px-4 py-3 text-sm text-gray-900">
          ${beneficiary.AccountNumber || "N/A"}
        </td>
        <td class="px-4 py-3 text-sm text-gray-900 font-semibold">
          ₦${parseFloat(beneficiary.Amount || 0).toLocaleString("en-NG", {
            minimumFractionDigits: 2,
          })}
        </td>
        <td class="px-4 py-3 text-sm">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
            <i class="fas fa-check-circle mr-1"></i>
            Ready
          </span>
        </td>
      </tr>
    `
      )
      .join("");

    // Setup checkbox event handlers
    this.setupCheckboxHandlers();
  }

  updateBatchInfo(batchNumber, beneficiaryCount) {
    const batchInfoDiv = document.getElementById("batch-info");
    const totalAmount = this.currentBatchBeneficiaries.reduce(
      (sum, b) => sum + parseFloat(b.Amount || 0),
      0
    );

    batchInfoDiv.innerHTML = `
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <i class="fas fa-info-circle text-blue-600 mr-2"></i>
          <div>
            <h4 class="font-semibold text-blue-800">Batch: ${batchNumber}</h4>
            <p class="text-sm text-blue-600">
              <span id="selected-count">${beneficiaryCount}</span> of ${beneficiaryCount} selected • 
              Total: ₦${totalAmount.toLocaleString("en-NG", {
                minimumFractionDigits: 2,
              })}
            </p>
          </div>
        </div>
        <div class="text-right">
          <div class="text-sm text-blue-600">Ready to Post</div>
        </div>
      </div>
    `;
  }

  setupCheckboxHandlers() {
    // Select all checkboxes
    const selectAllCheckboxes = document.querySelectorAll(
      "#select-all-beneficiaries, #select-all-beneficiaries-header"
    );
    const beneficiaryCheckboxes = document.querySelectorAll(
      ".beneficiary-checkbox"
    );

    // Handle select all checkboxes
    selectAllCheckboxes.forEach((selectAll) => {
      selectAll.addEventListener("change", (e) => {
        const isChecked = e.target.checked;

        // Update all select all checkboxes
        selectAllCheckboxes.forEach((cb) => (cb.checked = isChecked));

        // Update all beneficiary checkboxes
        beneficiaryCheckboxes.forEach((cb) => (cb.checked = isChecked));

        // Update selected count
        this.updateSelectedCount();
      });
    });

    // Handle individual beneficiary checkboxes
    beneficiaryCheckboxes.forEach((checkbox) => {
      checkbox.addEventListener("change", () => {
        this.updateSelectedCount();
        this.updateSelectAllState();
      });
    });
  }

  updateSelectedCount() {
    const selectedCheckboxes = document.querySelectorAll(
      ".beneficiary-checkbox:checked"
    );
    const totalCheckboxes = document.querySelectorAll(".beneficiary-checkbox");
    const selectedCountElement = document.getElementById("selected-count");

    if (selectedCountElement) {
      selectedCountElement.textContent = selectedCheckboxes.length;
    }

    // Update total amount for selected items
    this.updateSelectedAmount();
  }

  updateSelectAllState() {
    const beneficiaryCheckboxes = document.querySelectorAll(
      ".beneficiary-checkbox"
    );
    const selectedCheckboxes = document.querySelectorAll(
      ".beneficiary-checkbox:checked"
    );
    const selectAllCheckboxes = document.querySelectorAll(
      "#select-all-beneficiaries, #select-all-beneficiaries-header"
    );

    const allChecked =
      selectedCheckboxes.length === beneficiaryCheckboxes.length;
    const someChecked = selectedCheckboxes.length > 0;

    selectAllCheckboxes.forEach((checkbox) => {
      checkbox.checked = allChecked;
      checkbox.indeterminate = someChecked && !allChecked;
    });
  }

  updateSelectedAmount() {
    const selectedCheckboxes = document.querySelectorAll(
      ".beneficiary-checkbox:checked"
    );
    let selectedAmount = 0;

    selectedCheckboxes.forEach((checkbox) => {
      const index = parseInt(checkbox.dataset.beneficiaryIndex);
      if (
        this.currentBatchBeneficiaries &&
        this.currentBatchBeneficiaries[index]
      ) {
        selectedAmount += parseFloat(
          this.currentBatchBeneficiaries[index].Amount || 0
        );
      }
    });

    // Update the batch info with selected amount
    const batchInfoDiv = document.getElementById("batch-info");
    if (batchInfoDiv) {
      const selectedCount = selectedCheckboxes.length;
      const totalCount = this.currentBatchBeneficiaries
        ? this.currentBatchBeneficiaries.length
        : 0;
      const batchNumber = this.currentBatchNumber;

      batchInfoDiv.innerHTML = `
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
            <div>
              <h4 class="font-semibold text-blue-800">Batch: ${batchNumber}</h4>
              <p class="text-sm text-blue-600">
                <span id="selected-count">${selectedCount}</span> of ${totalCount} selected • 
                Selected Total: ₦${selectedAmount.toLocaleString("en-NG", {
                  minimumFractionDigits: 2,
                })}
              </p>
            </div>
          </div>
          <div class="text-right">
            <div class="text-sm text-blue-600">Ready to Post</div>
          </div>
        </div>
      `;
    }
  }

  async savePostAccount() {
    try {
      // Get selected beneficiaries
      const selectedCheckboxes = document.querySelectorAll(
        ".beneficiary-checkbox:checked"
      );

      if (selectedCheckboxes.length === 0) {
        this.showNotification(
          "Please select at least one beneficiary to process",
          "warning"
        );
        return;
      }

      // Get selected beneficiary codes
      const selectedBeneficiaries = Array.from(selectedCheckboxes).map(
        (checkbox) => {
          const index = parseInt(checkbox.dataset.beneficiaryIndex);
          return this.currentBatchBeneficiaries[index];
        }
      );

      const formData = {
        action: "post_account",
        batch_number: this.currentBatchNumber,
        loan_period: 12, // Default loan period of 12 months
        payroll_period_id: document.getElementById("payroll-period").value,
        selected_beneficiaries: selectedBeneficiaries,
      };

      // Debug: Log form data
      console.log("Form data being validated:", formData);
      console.log("Using default loan period: 12 months");
      console.log(
        "Payroll period value:",
        document.getElementById("payroll-period").value
      );

      // Validate form
      if (!this.validatePostAccountForm(formData)) {
        return;
      }

      this.showLoading(true);

      const response = await fetch("api/loan.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(formData),
      });

      const result = await response.json();

      if (result.success) {
        await Swal.fire({
          title: "Success!",
          text: result.message,
          icon: "success",
          timer: 3000,
          showConfirmButton: false,
        });

        this.closeLoanModal();
      } else {
        this.showNotification(result.message, "error");
      }
    } catch (error) {
      console.error("Post account error:", error);
      this.showNotification("Error posting account", "error");
    } finally {
      this.showLoading(false);
    }
  }

  validatePostAccountForm(data) {
    const errors = [];

    console.log("Validating form data:", data);

    if (!data.batch_number) errors.push("Batch number is required");

    // Loan period is now defaulted to 12 months, no validation needed
    console.log("Using default loan period:", data.loan_period);

    console.log("Payroll period validation:", {
      raw: data.payroll_period_id,
      isEmpty: !data.payroll_period_id,
      length: data.payroll_period_id ? data.payroll_period_id.length : 0,
    });

    if (!data.payroll_period_id) {
      errors.push("Payroll period selection is required");
    }

    console.log("Validation errors:", errors);

    if (errors.length > 0) {
      this.showNotification(errors.join("<br>"), "error");
      return false;
    }

    return true;
  }
}

// Global function to open loan modal (called from onclick)
window.openLoanModal = function (batchNumber) {
  if (window.batchManager) {
    window.batchManager.openLoanModal(batchNumber);
  }
};

// Post Account function - opens modal to select loan period and payroll period
window.postAccount = function (batchNumber) {
  console.log("postAccount called with batch:", batchNumber);
  console.log("window.batchManager exists:", !!window.batchManager);

  if (window.batchManager) {
    window.batchManager.openPostAccountModal(batchNumber);
  } else {
    console.error("BatchManager not initialized!");
    // Fallback: try to initialize and then call
    setTimeout(() => {
      if (window.batchManager) {
        window.batchManager.openPostAccountModal(batchNumber);
      } else {
        alert("System not ready. Please refresh the page and try again.");
      }
    }, 100);
  }
};

window.confirmSubmit = function () {
  return confirm(
    "Are you sure you want to send SMS to the selected batch numbers?"
  );
};

// Initialize the application
document.addEventListener("DOMContentLoaded", () => {
  window.batchManager = new BatchManager();
  console.log("BatchManager initialized:", !!window.batchManager);
});

// Test function to verify button clicks work
window.testPostAccount = function () {
  console.log("Test function called - button clicks are working!");
  alert("Button click test successful!");
};
