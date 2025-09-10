class MemberAccountManager {
  constructor() {
    this.currentMember = null;
    this.init();
  }

  init() {
    this.bindEvents();
    this.setupAutocomplete();
    this.setupFormValidation();
  }

  bindEvents() {
    // Personal form submission
    $("#personal-form").on("submit", (e) => this.handlePersonalFormSubmit(e));

    // Account form submission
    $("#account-form").on("submit", (e) => this.handleAccountFormSubmit(e));

    // Clear forms
    $("#clear-personal").on("click", () => this.clearPersonalForm());
    $("#clear-account").on("click", () => this.clearAccountForm());

    // Bank selection change
    $("#bank").on("change", (e) => this.handleBankChange(e));
  }

  setupAutocomplete() {
    // Initialize jQuery UI autocomplete for member search
    $("#member-search")
      .autocomplete({
        source: (request, response) => {
          this.searchMembers(request.term, response);
        },
        minLength: 2,
        select: (event, ui) => {
          this.selectMember(ui.item);
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
          $("#member-search").addClass("ui-autocomplete-loading");
        },
        response: () => {
          $("#member-search").removeClass("ui-autocomplete-loading");
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

      // Normal member item
      return $("<li>")
        .append(
          `<div class="p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0">
            <div class="font-medium text-gray-900 flex items-center">
              <i class="fas fa-user mr-2 text-blue-500"></i>
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
  }

  async searchMembers(query, response = null) {
    if (query.length < 2) return;

    try {
      const res = await fetch(
        `api/member-account.php?action=search_members&q=${encodeURIComponent(
          query
        )}`
      );
      const data = await res.json();

      if (response) {
        if (data.success && data.data.length === 0) {
          response([
            {
              label: "No members found",
              value: "",
              disabled: true,
              noResults: true,
            },
          ]);
        } else if (data.success) {
          response(data.data);
        } else {
          response([
            {
              label: "Error searching members",
              value: "",
              disabled: true,
              error: true,
            },
          ]);
        }
      }
    } catch (error) {
      console.error("Search error:", error);
      if (response) {
        response([
          {
            label: "Error searching members",
            value: "",
            disabled: true,
            error: true,
          },
        ]);
      }
    }
  }

  selectMember(member) {
    if (member.disabled) {
      return false;
    }

    this.currentMember = member;
    this.loadMemberDetails(member.coopid);

    Swal.fire({
      title: "Member Selected!",
      text: `${member.label} has been selected successfully.`,
      icon: "success",
      timer: 1500,
      showConfirmButton: false,
    });
  }

  async loadMemberDetails(coopId) {
    this.showLoading(true);

    try {
      const response = await fetch(
        `api/member-account.php?action=get_member_details&coop_id=${encodeURIComponent(
          coopId
        )}`
      );
      const result = await response.json();

      if (result.success) {
        this.populateForms(result.data);
        this.loadAccountHistory(coopId);
        $("#member-details-section").removeClass("hidden");
      } else {
        this.showNotification(result.message, "error");
      }
    } catch (error) {
      console.error("Load member details error:", error);
      this.showNotification("Error loading member details", "error");
    } finally {
      this.showLoading(false);
    }
  }

  populateForms(memberData) {
    // Populate personal information form
    $("#coop_id").val(memberData.CoopID || "");
    $("#first_name").val(memberData.FirstName || "");
    $("#middle_name").val(memberData.MiddleName || "");
    $("#last_name").val(memberData.LastName || "");
    $("#email").val(memberData.EmailAddress || "");
    $("#phone").val(memberData.PhoneNumber || "");
    $("#address").val(memberData.Address || "");
    $("#department").val(memberData.Department || "");
    $("#position").val(memberData.Position || "");

    // Populate account information form
    $("#bank").val(memberData.Bank || "");
    $("#account_no").val(memberData.AccountNo || "");
    $("#bank_code").val(memberData.bank_code || "");
  }

  async loadAccountHistory(coopId) {
    try {
      const response = await fetch(
        `api/member-account.php?action=get_account_history&coop_id=${encodeURIComponent(
          coopId
        )}`
      );
      const result = await response.json();

      if (result.success) {
        this.displayAccountHistory(result.data);
      }
    } catch (error) {
      console.error("Load account history error:", error);
    }
  }

  displayAccountHistory(history) {
    const historyContainer = $("#account-history");
    historyContainer.empty();

    if (history.length === 0) {
      historyContainer.html(`
        <div class="text-center text-gray-500 py-8">
          <i class="fas fa-history text-4xl mb-4"></i>
          <p>No account history available</p>
        </div>
      `);
      return;
    }

    history.forEach((record, index) => {
      const historyItem = $(`
        <div class="border border-gray-200 rounded-lg p-4 ${
          index === 0 ? "bg-blue-50 border-blue-200" : "bg-gray-50"
        }">
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <div class="flex items-center mb-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                  record.Status === "Current"
                    ? "bg-green-100 text-green-800"
                    : "bg-gray-100 text-gray-800"
                }">
                  <i class="fas ${
                    record.Status === "Current" ? "fa-check-circle" : "fa-clock"
                  } mr-1"></i>
                  ${record.Status}
                </span>
                <span class="ml-2 text-sm text-gray-500">
                  ${new Date(record.UpdatedAt).toLocaleDateString()}
                </span>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <span class="text-sm font-medium text-gray-700">Bank:</span>
                  <p class="text-sm text-gray-900">${
                    record.Bank || "Not specified"
                  }</p>
                </div>
                <div>
                  <span class="text-sm font-medium text-gray-700">Account Number:</span>
                  <p class="text-sm text-gray-900">${
                    record.AccountNo || "Not specified"
                  }</p>
                </div>
                <div>
                  <span class="text-sm font-medium text-gray-700">Bank Code:</span>
                  <p class="text-sm text-gray-900">${
                    record.bank_code || "Not specified"
                  }</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      `);
      historyContainer.append(historyItem);
    });
  }

  async handlePersonalFormSubmit(e) {
    e.preventDefault();

    if (!this.currentMember) {
      this.showNotification("Please select a member first", "warning");
      return;
    }

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.coop_id = this.currentMember.coopid;

    if (!this.validatePersonalForm(data)) {
      return;
    }

    this.showLoading(true);

    try {
      const response = await fetch("api/member-account.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "update_personal",
          ...data,
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
        // Reload member details to show updated information
        this.loadMemberDetails(this.currentMember.coopid);
      } else {
        this.showNotification(result.message, "error");
      }
    } catch (error) {
      console.error("Personal form submission error:", error);
      this.showNotification("Error updating personal details", "error");
    } finally {
      this.showLoading(false);
    }
  }

  async handleAccountFormSubmit(e) {
    e.preventDefault();

    if (!this.currentMember) {
      this.showNotification("Please select a member first", "warning");
      return;
    }

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.coop_id = this.currentMember.coopid;

    if (!this.validateAccountForm(data)) {
      return;
    }

    this.showLoading(true);

    try {
      const response = await fetch("api/member-account.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "update_account",
          ...data,
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
        // Reload member details and history
        this.loadMemberDetails(this.currentMember.coopid);
      } else {
        this.showNotification(result.message, "error");
      }
    } catch (error) {
      console.error("Account form submission error:", error);
      this.showNotification("Error updating account details", "error");
    } finally {
      this.showLoading(false);
    }
  }

  validatePersonalForm(data) {
    const errors = [];

    if (!data.first_name) errors.push("First name is required");
    if (!data.last_name) errors.push("Last name is required");
    if (data.email && !this.isValidEmail(data.email)) {
      errors.push("Please enter a valid email address");
    }

    if (errors.length > 0) {
      this.showNotification(errors.join("<br>"), "error");
      return false;
    }

    return true;
  }

  validateAccountForm(data) {
    const errors = [];

    if (!data.bank) errors.push("Bank name is required");
    if (!data.account_no) errors.push("Account number is required");
    if (data.account_no && !/^\d+$/.test(data.account_no)) {
      errors.push("Account number must contain only numbers");
    }

    if (errors.length > 0) {
      this.showNotification(errors.join("<br>"), "error");
      return false;
    }

    return true;
  }

  isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  handleBankChange(e) {
    // You can add logic here to auto-populate bank code based on selected bank
    const selectedBank = e.target.value;
    console.log("Selected bank:", selectedBank);
  }

  clearPersonalForm() {
    $("#personal-form")[0].reset();
    $("#coop_id").val(this.currentMember ? this.currentMember.coopid : "");
  }

  clearAccountForm() {
    $("#account-form")[0].reset();
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
    // Real-time validation for email
    $("#email").on(
      "blur",
      function () {
        const email = $(this).val();
        if (email && !this.isValidEmail(email)) {
          $(this).addClass("border-red-500");
          $(this).removeClass("border-gray-300");
        } else {
          $(this).removeClass("border-red-500");
          $(this).addClass("border-gray-300");
        }
      }.bind(this)
    );

    // Real-time validation for account number
    $("#account_no").on("input", function () {
      const value = $(this).val();
      if (value && !/^\d+$/.test(value)) {
        $(this).addClass("border-red-500");
        $(this).removeClass("border-gray-300");
      } else {
        $(this).removeClass("border-red-500");
        $(this).addClass("border-gray-300");
      }
    });
  }
}

// Initialize when DOM is ready
$(document).ready(() => {
  new MemberAccountManager();
});
