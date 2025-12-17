class BeneficiaryManager {
  constructor() {
    this.init();
  }

  init() {
    this.bindEvents();
    this.setupAutocomplete();
    this.setupFormValidation();
    this.autoHideMessages();
  }

  bindEvents() {
    // Form submission
    $("#beneficiary-form").on("submit", (e) => this.handleFormSubmit(e));

    // Clear form
    $("#clear-form, #clear-form-btn").on("click", () => this.clearForm());

    // Amount formatting
    $("#txtAmount").on("input", (e) => this.formatAmount(e.target));

    // Employee search
    $("#CoopName").on("input", (e) => this.searchEmployees(e.target.value));

    // Select all checkbox
    $("#select-all").on("change", (e) =>
      this.toggleSelectAll(e.target.checked)
    );

    // Bank edit modal events
    $(document).on("click", ".edit-bank", (e) => this.openBankEditModal(e));
    $(document).on("click", ".edit-bank-name", (e) =>
      this.openBankEditModal(e)
    );
    $("#close-bank-modal, #cancel-bank-edit").on("click", () =>
      this.closeBankEditModal()
    );
    $("#save-bank-edit").on("click", () => this.saveBankEdit());

    // Standalone bank edit events
    this.setupStandaloneBankEdit();

    // Delete selected
    $("#delete-selected").on("click", () => this.deleteSelected());

    // Individual delete buttons
    $(document).on("click", ".delete-beneficiary", (e) =>
      this.deleteBeneficiary(e)
    );

    // Edit buttons
    $(document).on("click", ".edit-beneficiary", (e) =>
      this.editBeneficiary(e)
    );

    // Edit modal
    $("#cancel-edit").on("click", () => this.closeEditModal());
    $("#edit-form").on("submit", (e) => this.handleEditSubmit(e));

    // Close modal on outside click
    $("#edit-modal").on("click", (e) => {
      if (e.target.id === "edit-modal") {
        this.closeEditModal();
      }
    });
  }

  setupAutocomplete() {
    // Initialize jQuery UI autocomplete
    $("#CoopName")
      .autocomplete({
        source: (request, response) => {
          this.searchEmployeesLegacy(request.term, response);
        },
        minLength: 2,
        select: (event, ui) => {
          // Prevent selection of disabled items (no results, error)
          if (ui.item.disabled) {
            return false;
          }
          this.selectEmployee(ui.item);
          return false;
        },
        focus: (event, ui) => {
          return false;
        },
        delay: 300,
        autoFocus: true,
        open: () => {
          // Add custom styling when dropdown opens
          $(".ui-autocomplete").addClass("shadow-lg");
        },
        close: () => {
          // Clean up when dropdown closes
          $(".ui-autocomplete").removeClass("shadow-lg");
        },
        search: () => {
          // Show loading indicator
          $("#CoopName").addClass("ui-autocomplete-loading");
        },
        response: () => {
          // Hide loading indicator
          $("#CoopName").removeClass("ui-autocomplete-loading");
        },
      })
      .autocomplete("instance")._renderItem = (ul, item) => {
      // Handle special cases (no results, error)
      if (item.noResults || item.error) {
        return $("<li>")
          .append(
            `<div class="p-3 text-center border-b border-gray-100 last:border-b-0">
              <div class="font-medium ${
                item.error ? "text-red-600" : "text-gray-500"
              } flex items-center justify-center">
                <i class="fas ${
                  item.error ? "fa-exclamation-triangle" : "fa-search"
                } mr-2"></i>
                ${item.label}
              </div>
            </div>`
          )
          .appendTo(ul);
      }

      // Normal employee item
      return $("<li>")
        .append(
          `<div class="p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0">
            <div class="font-medium text-gray-900 flex items-center">
              <i class="fas fa-user mr-2 text-blue-500"></i>
              ${item.label}
            </div>
            <div class="text-sm text-gray-500 mt-1">
              <span class="inline-block mr-4">
                <i class="fas fa-university mr-1 text-green-500"></i>
                <span class="font-medium">Bank:</span> ${
                  item.bank || "Not Available"
                }
              </span>
              <span class="inline-block mr-4">
                <i class="fas fa-credit-card mr-1 text-purple-500"></i>
                <span class="font-medium">Account:</span> ${
                  item.AccountNo || "Not Available"
                }
              </span>
              <span class="inline-block">
                <i class="fas fa-code mr-1 text-orange-500"></i>
                <span class="font-medium">Code:</span> ${
                  item.BankCode || "Not Available"
                }
              </span>
            </div>
          </div>`
        )
        .appendTo(ul);
    };
  }

  async searchEmployeesLegacy(query, response = null) {
    if (query.length < 2) return;

    try {
      const res = await fetch(`search.php?term=${encodeURIComponent(query)}`);
      const data = await res.json();

      if (response) {
        if (data.length === 0) {
          // Show "No results found" message
          response([
            {
              label: "No employees found",
              value: "",
              disabled: true,
              noResults: true,
            },
          ]);
        } else {
          response(data);
        }
      }
    } catch (error) {
      console.error("Search error:", error);
      if (response) {
        response([
          {
            label: "Error searching employees",
            value: "",
            disabled: true,
            error: true,
          },
        ]);
      }
    }
  }

  async searchEmployees(query, response = null) {
    if (query.length < 2) return;

    try {
      const res = await fetch(
        `api/beneficiary.php?action=search_employees&q=${encodeURIComponent(
          query
        )}`
      );
      const data = await res.json();

      if (data.success && response) {
        response(data.data);
      }
    } catch (error) {
      console.error("Search error:", error);
      this.showNotification("Error searching employees", "error");
    }
  }

  selectEmployee(employee) {
    // Handle both legacy and new data structures
    const name = employee.coopname || employee.label || employee.name;
    const id = employee.value || employee.id || employee.CoopID;
    const bank = employee.bank || employee.Bank || "";
    const accountNo = employee.AccountNo || employee.AccountNumber || "";
    const bankCode = employee.BankCode || employee.CBNCode || "";

    $("#CoopName").val(name);
    $("#txtCoopid").val(id);
    $("#txtBankName").val(bank);
    $("#txtBankAccountNo").val(accountNo);
    $("#txtbankcode").val(bankCode);
    $("#txtAmount").focus();

    // Show success notification
    Swal.fire({
      title: "Employee Selected!",
      text: `${name} has been selected successfully.`,
      icon: "success",
      timer: 1500,
      showConfirmButton: false,
    });
  }

  async handleFormSubmit(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    // Validate form
    if (!this.validateForm(data)) {
      return;
    }

    this.showLoading(true);

    try {
      const response = await fetch("api/beneficiary.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "add_beneficiary",
          ...data,
        }),
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
        this.clearForm();
        // Reload page to show updated list
        setTimeout(() => {
          window.location.reload();
        }, 2000);
      } else {
        this.showNotification(result.message, "error");
      }
    } catch (error) {
      console.error("Form submission error:", error);
      this.showNotification("Error submitting form", "error");
    } finally {
      this.showLoading(false);
    }
  }

  validateForm(data) {
    const errors = [];

    if (!data.CoopName) errors.push("Beneficiary name is required");
    if (!data.txtCoopid) errors.push("Cooperative ID is required");
    if (!data.txtBankName) errors.push("Bank name is required");
    if (!data.txtBankAccountNo) errors.push("Account number is required");
    if (!data.txtbankcode) errors.push("Bank code is required");
    if (!data.txtAmount) errors.push("Amount is required");
    if (!data.txNarration) errors.push("Narration is required");

    // Amount validation
    if (data.txtAmount) {
      const amount = parseFloat(data.txtAmount.replace(/,/g, ""));
      if (isNaN(amount) || amount <= 0) {
        errors.push("Amount must be a valid positive number");
      }
    }

    if (errors.length > 0) {
      this.showNotification(errors.join("<br>"), "error");
      return false;
    }

    return true;
  }

  formatAmount(input) {
    let value = input.value.replace(/[^0-9.]/g, "");
    const parts = value.split(".");

    if (parts[0]) {
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    input.value = parts.join(".");
  }

  clearForm() {
    $("#beneficiary-form")[0].reset();
    $("#CoopName").focus();
  }

  toggleSelectAll(checked) {
    $('input[name="coop_id"]').prop("checked", checked);
  }

  async deleteSelected() {
    const selected = $('input[name="coop_id"]:checked');

    if (selected.length === 0) {
      this.showNotification(
        "Please select at least one beneficiary to delete",
        "warning"
      );
      return;
    }

    const result = await Swal.fire({
      title: "Are you sure?",
      text: `You are about to delete ${selected.length} selected beneficiary(ies). This action cannot be undone!`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#EF4444",
      cancelButtonColor: "#6B7280",
      confirmButtonText: "Yes, delete them!",
      cancelButtonText: "Cancel",
      reverseButtons: true,
    });

    if (!result.isConfirmed) {
      return;
    }

    this.showLoading(true);

    try {
      const deletePromises = Array.from(selected).map((checkbox) =>
        this.deleteBeneficiaryById(checkbox.value)
      );

      await Promise.all(deletePromises);

      await Swal.fire({
        title: "Deleted!",
        text: "Selected beneficiaries have been deleted successfully.",
        icon: "success",
        timer: 2000,
        showConfirmButton: false,
      });

      setTimeout(() => {
        window.location.reload();
      }, 2000);
    } catch (error) {
      console.error("Delete error:", error);
      this.showNotification("Error deleting beneficiaries", "error");
    } finally {
      this.showLoading(false);
    }
  }

  async deleteBeneficiary(e) {
    const beneficiaryCode = $(e.currentTarget).data("code");

    const result = await Swal.fire({
      title: "Are you sure?",
      text: "You are about to delete this beneficiary. This action cannot be undone!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#EF4444",
      cancelButtonColor: "#6B7280",
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "Cancel",
      reverseButtons: true,
    });

    if (!result.isConfirmed) {
      return;
    }

    this.showLoading(true);

    try {
      await this.deleteBeneficiaryById(beneficiaryCode);

      await Swal.fire({
        title: "Deleted!",
        text: "Beneficiary has been deleted successfully.",
        icon: "success",
        timer: 2000,
        showConfirmButton: false,
      });

      setTimeout(() => {
        window.location.reload();
      }, 2000);
    } catch (error) {
      console.error("Delete error:", error);
      this.showNotification("Error deleting beneficiary", "error");
    } finally {
      this.showLoading(false);
    }
  }

  async deleteBeneficiaryById(beneficiaryCode) {
    const response = await fetch("api/beneficiary.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "delete_beneficiary",
        beneficiary_code: beneficiaryCode,
        batch: $('input[name="Batch"]').val(),
      }),
    });

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.message);
    }

    return result;
  }

  editBeneficiary(e) {
    const beneficiary = $(e.currentTarget).data("beneficiary");

    $("#edit-amount").val(this.formatNumber(beneficiary.Amount));
    $("#edit-beneficiary-code").val(beneficiary.BeneficiaryCode);
    $("#edit-modal").removeClass("hidden");
  }

  closeEditModal() {
    $("#edit-modal").addClass("hidden");
    $("#edit-form")[0].reset();
  }

  async handleEditSubmit(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    if (!data.amount || parseFloat(data.amount.replace(/,/g, "")) <= 0) {
      this.showNotification("Please enter a valid amount", "error");
      return;
    }

    this.showLoading(true);

    try {
      const response = await fetch("api/beneficiary.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "update_beneficiary",
          beneficiary_code: data.beneficiary_code,
          amount: data.amount,
          batch: $('input[name="Batch"]').val(),
        }),
      });

      const result = await response.json();

      if (result.success) {
        await Swal.fire({
          title: "Updated!",
          text: result.message,
          icon: "success",
          timer: 2000,
          showConfirmButton: false,
        });
        this.closeEditModal();
        setTimeout(() => {
          window.location.reload();
        }, 2000);
      } else {
        this.showNotification(result.message, "error");
      }
    } catch (error) {
      console.error("Edit error:", error);
      this.showNotification("Error updating beneficiary", "error");
    } finally {
      this.showLoading(false);
    }
  }

  formatNumber(num) {
    return parseFloat(num).toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  showLoading(show) {
    if (show) {
      $("#loading-overlay").removeClass("hidden");
    } else {
      $("#loading-overlay").addClass("hidden");
    }
  }

  showNotification(message, type = "info") {
    const swalConfig = {
      title: this.getNotificationTitle(type),
      text: message,
      icon: type,
      confirmButtonText: "OK",
      confirmButtonColor: this.getNotificationColor(type),
      timer: type === "success" ? 3000 : null,
      timerProgressBar: type === "success",
      showConfirmButton: type !== "success",
      allowOutsideClick: true,
      allowEscapeKey: true,
    };

    // Handle HTML content in error messages
    if (type === "error" && message.includes("<br>")) {
      swalConfig.html = message;
      delete swalConfig.text;
    }

    Swal.fire(swalConfig);
  }

  getNotificationTitle(type) {
    const titles = {
      success: "Success!",
      error: "Error!",
      warning: "Warning!",
      info: "Information",
    };
    return titles[type] || "Notification";
  }

  getNotificationColor(type) {
    const colors = {
      success: "#10B981",
      error: "#EF4444",
      warning: "#F59E0B",
      info: "#3B82F6",
    };
    return colors[type] || "#3B82F6";
  }

  setupFormValidation() {
    // Real-time validation
    $("#txtAmount").on("blur", function () {
      const value = $(this).val().replace(/,/g, "");
      if (value && (isNaN(value) || parseFloat(value) <= 0)) {
        $(this).addClass("border-red-500");
        $(this).removeClass("border-gray-300");
      } else {
        $(this).removeClass("border-red-500");
        $(this).addClass("border-gray-300");
      }
    });
  }

  autoHideMessages() {
    // SweetAlert handles auto-hiding automatically
    // This method is kept for compatibility but does nothing
  }

  // Bank Edit Modal Methods
  async openBankEditModal(e) {
    e.preventDefault();

    try {
      const beneficiaryData = $(e.currentTarget).data("beneficiary");
      console.log("Raw beneficiary data:", beneficiaryData);

      let beneficiary;
      if (typeof beneficiaryData === "string") {
        beneficiary = JSON.parse(beneficiaryData);
      } else {
        beneficiary = beneficiaryData;
      }

      console.log("Parsed beneficiary:", beneficiary);

      // Populate modal with current data
      $("#modal-member-name").val(beneficiary.BeneficiaryName);
      $("#modal-member-id").val(beneficiary.BeneficiaryCode);

      console.log("About to load banks...");
      // Load banks first
      await this.loadBanksForModal();
      console.log("Banks loaded, now loading current bank details...");

      // Fetch and populate current bank details
      await this.loadCurrentBankDetails(beneficiary.BeneficiaryCode);
      console.log("Current bank details loaded");

      // Add bank change event handler
      $("#modal-bank")
        .off("change")
        .on("change", (e) => {
          this.fetchBankCode(e.target.value);
        });

      // Show modal
      $("#bank-edit-modal").removeClass("hidden");
      console.log("Modal shown");
    } catch (error) {
      console.error("Error opening bank edit modal:", error);
      this.showNotification("Error loading beneficiary data", "error");
    }
  }

  // Load current bank details for the member
  async loadCurrentBankDetails(coopId) {
    try {
      console.log("Loading bank details for CoopID:", coopId);
      const response = await fetch(
        `api/beneficiary.php?action=get_member_bank_details&coop_id=${encodeURIComponent(
          coopId
        )}`
      );
      const result = await response.json();
      console.log("Bank details API response:", result);

      if (result.success && result.data) {
        const bankDetails = result.data;
        console.log("Bank details data:", bankDetails);
        $("#modal-bank").val(bankDetails.Bank || "");
        $("#modal-account-no").val(bankDetails.AccountNo || "");
        $("#modal-bank-code").val(bankDetails.bank_code || "");
        console.log("Modal fields populated");
      } else {
        console.log("No bank details found or API error:", result.message);
        // Clear fields if no bank details found
        $("#modal-bank").val("");
        $("#modal-account-no").val("");
        $("#modal-bank-code").val("");
      }
    } catch (error) {
      console.error("Error loading current bank details:", error);
      // Clear fields on error
      $("#modal-bank").val("");
      $("#modal-account-no").val("");
      $("#modal-bank-code").val("");
    }
  }

  async loadBanksForModal() {
    try {
      const response = await fetch("api/beneficiary.php?action=get_banks");
      const result = await response.json();

      if (result.success) {
        const bankSelect = $("#modal-bank");
        bankSelect.empty().append('<option value="">Select Bank</option>');

        result.data.forEach((bank) => {
          bankSelect.append(
            `<option value="${bank.bank}">${bank.bank}</option>`
          );
        });
      }
    } catch (error) {
      console.error("Error loading banks:", error);
    }
  }

  closeBankEditModal() {
    $("#bank-edit-modal").addClass("hidden");
    $("#bank-edit-form")[0].reset();
  }

  async saveBankEdit() {
    const formData = {
      action: "update_bank_details",
      coop_id: $("#modal-member-id").val(),
      bank: $("#modal-bank").val(),
      account_no: $("#modal-account-no").val(),
      bank_code: $("#modal-bank-code").val(),
    };

    // Validate form
    if (!formData.bank || !formData.account_no) {
      this.showNotification(
        "Bank name and account number are required",
        "error"
      );
      return;
    }

    if (!/^\d+$/.test(formData.account_no)) {
      this.showNotification(
        "Account number must contain only numbers",
        "error"
      );
      return;
    }

    this.showLoading(true);

    try {
      const response = await fetch("api/beneficiary.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(formData),
      });

      const result = await response.json();

      if (result.success) {
        await Swal.fire({
          title: "Updated!",
          text: result.message,
          icon: "success",
          timer: 2000,
          showConfirmButton: false,
        });

        this.closeBankEditModal();
        // Reload the page to show updated data
        location.reload();
      } else {
        this.showNotification(result.message, "error");
      }
    } catch (error) {
      console.error("Bank edit error:", error);
      this.showNotification("Error updating bank details", "error");
    } finally {
      this.showLoading(false);
    }
  }

  // Standalone Bank Edit Methods
  setupStandaloneBankEdit() {
    let selectedMember = null;

    // Setup autocomplete for standalone member search
    $("#standalone-member-search")
      .autocomplete({
        source: (request, response) => {
          this.searchEmployees(request.term, response);
        },
        minLength: 2,
        select: (event, ui) => {
          if (ui.item.disabled) {
            return false;
          }
          selectedMember = ui.item;
          $("#open-standalone-bank-edit").prop("disabled", false);
          $("#standalone-member-search").val(ui.item.label);
          return false;
        },
        focus: (event, ui) => {
          return false;
        },
        delay: 300,
        autoFocus: true,
        open: () => {
          $(".ui-autocomplete").addClass("shadow-lg");
        },
        close: () => {
          $(".ui-autocomplete").removeClass("shadow-lg");
        },
        search: () => {
          $("#standalone-member-search").addClass("ui-autocomplete-loading");
        },
        response: () => {
          $("#standalone-member-search").removeClass("ui-autocomplete-loading");
        },
      })
      .autocomplete("instance")._renderItem = (ul, item) => {
      if (item.noResults || item.error) {
        return $("<li>")
          .append(
            `<div class="p-3 text-center border-b border-gray-100 last:border-b-0">
              <div class="font-medium ${
                item.error ? "text-red-600" : "text-gray-500"
              } flex items-center justify-center">
                <i class="fas ${
                  item.error ? "fa-exclamation-triangle" : "fa-search"
                } mr-2"></i>
                ${item.label}
              </div>
            </div>`
          )
          .appendTo(ul);
      }

      return $("<li>")
        .append(
          `<div class="p-3 hover:bg-green-50 cursor-pointer border-b border-gray-100 last:border-b-0">
            <div class="font-medium text-gray-900 flex items-center">
              <i class="fas fa-user mr-2 text-green-500"></i>
              ${item.label}
            </div>
            <div class="text-sm text-gray-500 mt-1">
              <span class="inline-block mr-4">
                <i class="fas fa-id-card mr-1 text-green-500"></i>
                <span class="font-medium">ID:</span> ${item.coopid}
              </span>
            </div>
          </div>`
        )
        .appendTo(ul);
    };

    // Handle standalone bank edit button click
    $("#open-standalone-bank-edit").on("click", () => {
      if (selectedMember) {
        // Create a beneficiary-like object for the modal
        const beneficiaryData = {
          BeneficiaryName: selectedMember.fullname || selectedMember.label,
          BeneficiaryCode: selectedMember.coopid || selectedMember.value,
          Bank: "",
          AccountNumber: "",
          CBNCode: "",
        };

        // Use the same logic as the bank icon
        this.openStandaloneBankEditModal(selectedMember);
      }
    });

    // Handle clear search button
    $("#clear-standalone-search").on("click", () => {
      $("#standalone-member-search").val("");
      selectedMember = null;
      $("#open-standalone-bank-edit").prop("disabled", true);
    });
  }

  async openStandaloneBankEditModal(member) {
    try {
      console.log("Opening standalone bank edit for member:", member);

      // Populate modal with member data
      $("#modal-member-name").val(member.fullname || member.label);
      $("#modal-member-id").val(member.coopid || member.value);

      console.log("About to load banks...");
      // Load banks first
      await this.loadBanksForModal();
      console.log("Banks loaded, now loading current bank details...");

      // Fetch and populate current bank details - same logic as bank icon
      await this.loadCurrentBankDetails(member.coopid || member.value);
      console.log("Current bank details loaded");

      // Add bank change event handler
      $("#modal-bank")
        .off("change")
        .on("change", (e) => {
          this.fetchBankCode(e.target.value);
        });

      // Show modal
      $("#bank-edit-modal").removeClass("hidden");
      console.log("Modal shown");
    } catch (error) {
      console.error("Error opening standalone bank edit:", error);
      this.showNotification("Error loading member data", "error");
    }
  }

  async openStandaloneBankEdit(member) {
    try {
      // Create a beneficiary-like object for the modal
      const beneficiaryData = {
        BeneficiaryName: member.fullname || member.label,
        BeneficiaryCode: member.coopid || member.value,
        Bank: "",
        AccountNumber: "",
        CBNCode: "",
      };

      // Populate modal with member data
      $("#modal-member-name").val(beneficiaryData.BeneficiaryName);
      $("#modal-member-id").val(beneficiaryData.BeneficiaryCode);
      $("#modal-bank").val("");
      $("#modal-account-no").val("");
      $("#modal-bank-code").val("");

      // Load banks
      await this.loadBanksForModal();

      // Add bank change event handler
      $("#modal-bank")
        .off("change")
        .on("change", (e) => {
          this.fetchBankCode(e.target.value);
        });

      // Show modal
      $("#bank-edit-modal").removeClass("hidden");
    } catch (error) {
      console.error("Error opening standalone bank edit:", error);
      this.showNotification("Error loading member data", "error");
    }
  }

  // Auto-fetch bank code when bank is selected
  async fetchBankCode(bankName) {
    if (!bankName) {
      $("#modal-bank-code").val("");
      return;
    }

    try {
      const response = await fetch(
        `api/beneficiary.php?action=get_bank_code&bank_name=${encodeURIComponent(
          bankName
        )}`
      );
      const result = await response.json();

      if (result.success) {
        $("#modal-bank-code").val(result.data.bank_code || "");
      } else {
        $("#modal-bank-code").val("");
        console.warn("Could not fetch bank code:", result.message);
      }
    } catch (error) {
      console.error("Error fetching bank code:", error);
      $("#modal-bank-code").val("");
    }
  }
}

// Initialize when DOM is ready
$(document).ready(() => {
  new BeneficiaryManager();
});

// Legacy functions for backward compatibility
function disableButton(btn) {
  btn.disabled = true;
  btn.value = "Processing...";
  btn.form.submit();
}

function getName(coopid) {
  // This function is now handled by the autocomplete
  console.log("getName called with:", coopid);
}

function deleteBeneficiary(coopid) {
  // This function is now handled by the new BeneficiaryManager
  console.log("deleteBeneficiary called with:", coopid);
}

function commaFormat(inputString) {
  inputString = inputString.toString();
  var decimalPart = "";
  if (inputString.indexOf(".") != -1) {
    inputString = inputString.split(".");
    decimalPart = "." + inputString[1];
    inputString = inputString[0];
  }
  var outputString = "";
  var count = 0;
  for (
    var i = inputString.length - 1;
    i >= 0 && inputString.charAt(i) != "-";
    i--
  ) {
    if (count == 3) {
      outputString += ",";
      count = 0;
    }
    outputString += inputString.charAt(i);
    count++;
  }
  if (inputString.charAt(0) == "-") {
    outputString += "-";
  }
  return outputString.split("").reverse().join("") + decimalPart;
}

function formatNumber(myElement) {
  var myVal = "";
  var myDec = "";
  var parts = myElement.value.toString().split(".");
  parts[0] = parts[0].replace(/[^0-9]/g, "");
  if (!parts[1] && myElement.value.indexOf(".") > 1) {
    myDec = ".00";
  }
  if (parts[1]) {
    myDec = "." + parts[1];
  }
  while (parts[0].length > 3) {
    myVal = "'" + parts[0].substr(parts[0].length - 3, parts[0].length) + myVal;
    parts[0] = parts[0].substr(0, parts[0].length - 3);
  }
  myElement.value = parts[0] + myVal + myDec;
}

function clearBox() {
  document.forms[0].CoopName.value = "";
  document.forms[0].txtCoopid.value = "";
  document.forms[0].txtBankAccountNo.value = "";
  document.forms[0].txtAmount.value = "";
  document.forms[0].txtBankName.value = "";
  document.forms[0].txNarration.value = "";
  document.forms[0].txtbankcode.value = "";
  document.forms[0].txtAccountNo_hidden.value = "";
  fo();
}

function fo() {
  document.eduEntry.CoopName.focus();
}

function fo2() {
  document.eduEntry.txtAmount.focus();
}
