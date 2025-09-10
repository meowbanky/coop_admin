/**
 * Loan Processor JavaScript
 * Modern UI interactions for loan processing system
 */

class LoanProcessor {
  constructor() {
    this.currentEmployee = null;
    this.currentPeriod = null;
    this.init();
    this.initializeBatchNumber();
  }

  init() {
    this.setupEventListeners();
    this.setupAutocomplete();
    this.setupFormValidation();
  }

  setupEventListeners() {
    // Employee search
    $("#employee-search").on("input", (e) => {
      // This is handled by autocomplete, no need for separate handler
      this.toggleClearButton();
    });

    // Clear search button
    $("#clear-search-btn").on("click", (e) => {
      e.preventDefault();
      this.clearEmployeeSearch();
    });

    // Period selection
    $("#payroll-period").on("change", (e) => {
      this.handlePeriodChange(e.target.value);
    });

    // Update loan button
    $("#update-loan-btn").on("click", (e) => {
      e.preventDefault();
      this.handleUpdateLoan();
    });

    // View loan list button
    $("#view-loan-list-btn").on("click", (e) => {
      e.preventDefault();
      this.showLoanListModal();
    });

    // Modal controls
    $("#close-loan-modal, #close-modal-btn").on("click", () => {
      this.hideLoanListModal();
    });

    // Close modal on backdrop click
    $("#loan-list-modal").on("click", (e) => {
      if (e.target === e.currentTarget) {
        this.hideLoanListModal();
      }
    });

    // Loan action buttons (using event delegation for dynamically added buttons)
    $(document).on("click", ".edit-loan-btn", (e) => {
      e.preventDefault();
      this.handleEditLoan(e.currentTarget);
    });

    $(document).on("click", ".delete-loan-btn", (e) => {
      e.preventDefault();
      this.handleDeleteLoan(e.currentTarget);
    });
  }

  setupAutocomplete() {
    $("#employee-search")
      .autocomplete({
        source: (request, response) => {
          this.searchEmployees(request.term, response);
        },
        minLength: 2,
        select: (event, ui) => {
          this.selectEmployee(ui.item);
          return false;
        },
        focus: (event, ui) => {
          return false;
        },
      })
      .autocomplete("instance")._renderItem = (ul, item) => {
      return $("<li>")
        .append(
          `
                    <div class="flex items-center p-2 hover:bg-gray-100">
                        <i class="fas fa-user text-gray-400 mr-3"></i>
                        <div>
                            <div class="font-medium text-gray-900">${item.full_name}</div>
                            <div class="text-sm text-gray-500">${item.coop_id}</div>
                        </div>
                    </div>
                `
        )
        .appendTo(ul);
    };
  }

  setupFormValidation() {
    // Real-time validation
    $("#loan-amount").on("input", (e) => {
      this.validateLoanAmount(e.target.value);
    });
  }

  async searchEmployees(searchTerm, response) {
    if (searchTerm.length < 2) {
      response([]);
      return;
    }

    try {
      const result = await this.makeRequest("GET", {
        action: "search_employee",
        q: searchTerm,
      });

      if (result.success) {
        response(result.data);
      } else {
        response([]);
      }
    } catch (error) {
      console.error("Search error:", error);
      response([]);
    }
  }

  async selectEmployee(employee) {
    this.currentEmployee = employee;

    // Set the value without triggering input event
    $("#employee-search").off("input").val(employee.full_name);

    // Re-attach the input event handler
    $("#employee-search").on("input", (e) => {
      this.toggleClearButton();
    });

    // Show employee details
    await this.loadEmployeeDetails(employee.coop_id);

    // If period is selected, load loan calculation
    if (this.currentPeriod) {
      await this.loadLoanCalculation(employee.coop_id, this.currentPeriod);
    }

    // Show clear button
    this.toggleClearButton();
  }

  async loadEmployeeDetails(coopId) {
    try {
      this.showLoading("#loading-overlay");

      const result = await this.makeRequest("GET", {
        action: "get_employee_details",
        coop_id: coopId,
      });

      if (result.success) {
        // Also get loan balance
        const loanBalanceResult = await this.makeRequest("GET", {
          action: "get_current_loan_balance",
          coop_id: coopId,
        });

        const loanBalance = loanBalanceResult.success
          ? loanBalanceResult.data
          : null;
        this.displayEmployeeDetails(result.data, loanBalance);
        $("#employee-details-card").removeClass("hidden").addClass("fade-in");

        // Only show loan calculation card if period is also selected
        if (this.currentPeriod) {
          $("#loan-calculation-card").removeClass("hidden").addClass("fade-in");
          await this.loadCurrentPeriodLoans(this.currentPeriod);

          // Focus on loan amount field after a short delay to ensure card is visible
          setTimeout(() => {
            $("#loan-amount").focus();
          }, 300);
        } else {
          // Hide loan calculation card if no period is selected
          $("#loan-calculation-card").addClass("hidden").removeClass("fade-in");
        }
      } else {
        this.showError("Failed to load employee details: " + result.message);
      }
    } catch (error) {
      console.error("Error loading employee details:", error);
      this.showError("Error loading employee details");
    } finally {
      this.hideLoading("#loading-overlay");
    }
  }

  displayEmployeeDetails(employee, loanBalance = null) {
    const loanBalanceHtml = loanBalance
      ? `
            <div class="bg-red-50 p-4 rounded-lg">
                <h4 class="font-semibold text-red-800 mb-2">Current Loan Balance</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div><span class="font-medium">Total Loans:</span> ₦${this.formatNumber(
                      loanBalance.total_loans
                    )}</div>
                    <div><span class="font-medium">Total Repayments:</span> ₦${this.formatNumber(
                      loanBalance.total_repayments
                    )}</div>
                    <div><span class="font-medium">Current Balance:</span> <span class="font-bold text-red-600">₦${this.formatNumber(
                      loanBalance.current_balance
                    )}</span></div>
                </div>
            </div>
        `
      : "";

    const html = `
            <div class="bg-blue-50 p-4 rounded-lg">
                <h4 class="font-semibold text-blue-800 mb-2">Basic Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div><span class="font-medium">Name:</span> ${
                      employee.FullName
                    }</div>
                    <div><span class="font-medium">ID:</span> ${
                      employee.CoopID
                    }</div>
                    <div><span class="font-medium">Department:</span> ${
                      employee.Department || "N/A"
                    }</div>
                    <div><span class="font-medium">Position:</span> ${
                      employee.Position || "N/A"
                    }</div>
                </div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <h4 class="font-semibold text-green-800 mb-2">Bank Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div><span class="font-medium">Bank:</span> ${
                      employee.Bank || "N/A"
                    }</div>
                    <div><span class="font-medium">Account No:</span> ${
                      employee.AccountNo || "N/A"
                    }</div>
                    <div><span class="font-medium">Bank Code:</span> ${
                      employee.BankCode || "N/A"
                    }</div>
                </div>
            </div>
            ${loanBalanceHtml}
        `;

    $("#employee-info").html(html);
  }

  async handlePeriodChange(periodId) {
    this.currentPeriod = periodId;

    // Always load all loans for the period regardless of selected employee
    if (periodId) {
      await this.loadCurrentPeriodLoans(periodId);
      // Show the card even if no loans exist
      $("#current-period-loans-card").removeClass("hidden").addClass("fade-in");
    }

    // Only show loan input card when BOTH employee and period are selected
    if (this.currentEmployee && periodId) {
      await this.loadLoanCalculation(this.currentEmployee.coop_id, periodId);

      // Focus on loan amount field after a short delay to ensure card is visible
      setTimeout(() => {
        $("#loan-amount").focus();
      }, 300);
    } else {
      // Hide loan input card if either employee or period is not selected
      $("#loan-calculation-card").addClass("hidden").removeClass("fade-in");
    }
  }

  async loadLoanCalculation(coopId, periodId) {
    try {
      this.showLoading("#loading-overlay");

      const result = await this.makeRequest("GET", {
        action: "get_loan_calculation",
        coop_id: coopId,
        period_id: periodId,
      });

      if (result.success) {
        this.displayLoanCalculation(result.data);
        $("#loan-calculation-card").removeClass("hidden").addClass("fade-in");
        $("#loan-list-card").removeClass("hidden").addClass("fade-in");

        console.log("Loan calculation card shown after successful load");
        console.log(
          "Card visibility:",
          $("#loan-calculation-card").is(":visible")
        );
        console.log("Card classes:", $("#loan-calculation-card").attr("class"));

        // Focus on loan amount field after successful load
        setTimeout(() => {
          $("#loan-amount").focus();
        }, 100);
      } else {
        this.showError("Failed to load loan calculation: " + result.message);
      }
    } catch (error) {
      console.error("Error loading loan calculation:", error);
      this.showError("Error loading loan calculation");
    } finally {
      this.hideLoading("#loading-overlay");
    }
  }

  displayLoanCalculation(data) {
    const html = `
             <div class="bg-blue-50 p-4 rounded-lg">
                 <div class="flex items-center mb-2">
                     <i class="fas fa-piggy-bank text-blue-600 mr-2"></i>
                     <span class="font-semibold text-blue-800">Shares & Savings</span>
                 </div>
                 <div class="text-2xl font-bold text-blue-900">₦${this.formatNumber(
                   data.total_shares_savings
                 )}</div>
                 <div class="text-sm text-blue-600 mt-1">
                     Shares: ₦${this.formatNumber(data.total_shares)} | 
                     Savings: ₦${this.formatNumber(data.total_savings)}
                 </div>
             </div>
             <div class="bg-green-50 p-4 rounded-lg">
                 <div class="flex items-center mb-2">
                     <i class="fas fa-calculator text-green-600 mr-2"></i>
                     <span class="font-semibold text-green-800">Max Loan Obtainable</span>
                 </div>
                 <div class="text-2xl font-bold text-green-900">₦${this.formatNumber(
                   data.max_loan_obtainable
                 )}</div>
             </div>
             <div class="bg-red-50 p-4 rounded-lg">
                 <div class="flex items-center mb-2">
                     <i class="fas fa-credit-card text-red-600 mr-2"></i>
                     <span class="font-semibold text-red-800">Current Loan Balance</span>
                 </div>
                 <div class="text-2xl font-bold text-red-900">₦${this.formatNumber(
                   data.current_loan_balance
                 )}</div>
                 <div class="text-sm text-red-600 mt-1">
                     Total Loans: ₦${this.formatNumber(data.total_loans)} - 
                     Repayments: ₦${this.formatNumber(data.total_repayments)}
                 </div>
             </div>
             <div class="bg-purple-50 p-4 rounded-lg">
                 <div class="flex items-center mb-2">
                     <i class="fas fa-check-circle text-purple-600 mr-2"></i>
                     <span class="font-semibold text-purple-800">Available Loan</span>
                 </div>
                 <div class="text-2xl font-bold text-purple-900">₦${this.formatNumber(
                   data.available_loan
                 )}</div>
             </div>
         `;

    $("#loan-calculation").html(html);

    // Set max loan amount (disabled for override capability)
    // $("#loan-amount").attr("max", data.available_loan);
  }

  async loadCurrentPeriodLoans(periodId) {
    try {
      console.log("Loading current period loans for period:", periodId);
      this.showLoading("#loading-overlay");

      const result = await this.makeRequest("GET", {
        action: "get_current_period_loans",
        period_id: periodId,
      });

      console.log("Current period loans result:", result);

      if (result.success) {
        this.displayCurrentPeriodLoans(result.data);

        // Force show the card with multiple approaches
        const $card = $("#current-period-loans-card");
        $card.removeClass("hidden");
        $card.addClass("fade-in");
        $card.show();
        $card.css({
          display: "block !important",
          visibility: "visible !important",
          opacity: "1 !important",
          position: "relative !important",
          zIndex: "10 !important",
          margin: "0 !important",
          padding: "0 !important",
        });

        // Add a data attribute to track visibility
        $card.attr("data-visible", "true");

        // Set up a mutation observer to detect if the card is hidden
        if (
          window.MutationObserver &&
          $card.length > 0 &&
          $card[0].ownerDocument
        ) {
          try {
            const observer = new MutationObserver((mutations) => {
              mutations.forEach((mutation) => {
                try {
                  if (
                    mutation.type === "attributes" &&
                    mutation.attributeName === "class"
                  ) {
                    const target = mutation.target;
                    if (
                      target.classList &&
                      target.classList.contains("hidden")
                    ) {
                      console.log("Card was hidden, forcing visibility...");
                      target.classList.remove("hidden");
                      target.classList.add("fade-in");
                      target.style.display = "block";
                      target.style.visibility = "visible";
                      target.style.opacity = "1";
                    }
                  }
                } catch (e) {
                  console.log("Error in mutation observer callback:", e);
                }
              });
            });

            observer.observe($card[0], {
              attributes: true,
              attributeFilter: ["class", "style"],
            });
          } catch (e) {
            console.log("Error setting up mutation observer:", e);
          }
        }

        console.log("Current period loans card shown");
        console.log("Card exists:", $("#current-period-loans-card").length > 0);
        console.log(
          "Card visibility:",
          $("#current-period-loans-card").is(":visible")
        );
        console.log(
          "Card classes:",
          $("#current-period-loans-card").attr("class")
        );
        console.log(
          "Card display:",
          $("#current-period-loans-card").css("display")
        );
        console.log("Card height:", $("#current-period-loans-card").height());
        console.log("Card width:", $("#current-period-loans-card").width());

        // Debug info
        if (result.data.count > 0) {
          console.log("Found " + result.data.count + " loans for this period");
        }

        // Check again after a short delay
        setTimeout(() => {
          try {
            const $card = $("#current-period-loans-card");

            // Check if element still exists and is attached to DOM
            if (!$card.length || !$card[0] || !$card[0].ownerDocument) {
              console.log("Card element not found or detached from DOM");
              return;
            }

            // Additional safety check - verify element is still in the DOM
            if (!document.contains($card[0])) {
              console.log("Card element is not in the DOM");
              return;
            }

            const offset = $card.offset();
            const windowHeight = $(window).height();
            const windowScrollTop = $(window).scrollTop();

            console.log("After delay - Card visibility:", $card.is(":visible"));
            console.log("After delay - Card display:", $card.css("display"));
            console.log(
              "After delay - Card parent visibility:",
              $card.parent().is(":visible")
            );
            console.log("After delay - Card offset:", offset);
            console.log("After delay - Window height:", windowHeight);
            console.log("After delay - Window scroll top:", windowScrollTop);
            console.log(
              "After delay - Card in viewport:",
              offset &&
                offset.top >= windowScrollTop &&
                offset.top <= windowScrollTop + windowHeight
            );

            // Simplified parent check to avoid jQuery DOM issues
            try {
              const parent = $card.parent();
              if (parent.length > 0 && parent[0] && parent[0].ownerDocument) {
                console.log("Parent element:", {
                  tag: parent.prop("tagName"),
                  classes: parent.attr("class"),
                  display: parent.css("display"),
                  visibility: parent.css("visibility"),
                  opacity: parent.css("opacity"),
                });
              } else {
                console.log("Parent element not available or detached");
              }
            } catch (e) {
              console.log("Error checking parent element:", e);
            }

            // Force scroll to card if it's not visible
            if (offset && offset.top > windowScrollTop + windowHeight) {
              console.log("Scrolling to card...");
              $("html, body").animate(
                {
                  scrollTop: offset.top - 100,
                },
                500
              );
            }

            // Add temporary highlight to make card more visible (only for debugging)
            if (
              window.location.hostname === "localhost" ||
              window.location.hostname.includes("test")
            ) {
              $card.css({
                border: "3px solid #ff0000 !important",
                backgroundColor: "#fff3cd !important",
              });

              // Remove highlight after 3 seconds
              setTimeout(() => {
                try {
                  const $cardHighlight = $("#current-period-loans-card");
                  if (
                    $cardHighlight.length &&
                    $cardHighlight[0].ownerDocument
                  ) {
                    $cardHighlight.css({
                      border: "",
                      backgroundColor: "",
                    });
                  }
                } catch (e) {
                  console.log("Error removing highlight:", e);
                }
              }, 3000);
            }
          } catch (e) {
            console.log("Error in setTimeout callback:", e);
          }
        }, 100);
      } else {
        this.showError(
          "Failed to load current period loans: " + result.message
        );
      }
    } catch (error) {
      console.error("Error loading current period loans:", error);
      this.showError("Error loading current period loans");
    } finally {
      this.hideLoading("#loading-overlay");
    }
  }

  displayCurrentPeriodLoans(data) {
    console.log("Displaying current period loans data:", data);
    const { loans, total_loan_amount, total_monthly_repayment, count } = data;

    if (count === 0) {
      console.log("No loans found for this period");
      const html = `
        <div class="text-center py-8 text-gray-500">
          <i class="fas fa-file-invoice text-4xl mb-4"></i>
          <p>No loans found for this period</p>
          <p class="text-sm mt-2">Select an employee and add a loan to get started</p>
        </div>
      `;
      $("#current-period-loans-content").html(html);
      return;
    }

    const loansHtml = loans
      .map(
        (loan) => `
      <div class="bg-gray-50 p-4 rounded-lg mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <span class="text-sm font-medium text-gray-600">Coop ID:</span>
            <p class="font-semibold text-gray-900">${loan.coop_id}</p>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-600">Name:</span>
            <p class="font-semibold text-gray-900">${loan.name}</p>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-600">Loan Amount:</span>
            <p class="font-semibold text-green-600">₦${this.formatNumber(
              loan.loan_amount
            )}</p>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-600">Monthly Repayment:</span>
            <p class="font-semibold text-blue-600">₦${this.formatNumber(
              loan.monthly_repayment
            )}</p>
          </div>
        </div>
        <div class="mt-2 text-sm text-gray-500">
          <span>Approval Date: ${loan.approval_date}</span>
          <span class="ml-4">Batch: ${loan.batch || "N/A"}</span>
        </div>
        <div class="mt-3 flex justify-end space-x-2">
          <button 
            class="edit-loan-btn bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition-colors"
            data-loan-id="${loan.loan_approval_id}"
            data-coop-id="${loan.coop_id}"
            data-loan-amount="${loan.loan_amount}"
            data-monthly-repayment="${loan.monthly_repayment}"
            data-batch="${loan.batch || ""}"
            title="Edit Loan"
          >
            <i class="fas fa-edit mr-1"></i>Edit
          </button>
          <button 
            class="delete-loan-btn bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm transition-colors"
            data-loan-id="${loan.loan_approval_id}"
            data-coop-id="${loan.coop_id}"
            data-loan-amount="${loan.loan_amount}"
            data-name="${loan.name}"
            title="Delete Loan"
          >
            <i class="fas fa-trash mr-1"></i>Delete
          </button>
        </div>
      </div>
    `
      )
      .join("");

    const totalsHtml = `
      <div class="bg-blue-50 p-6 rounded-lg mt-6 border-2 border-blue-200">
        <h4 class="text-lg font-semibold text-blue-800 mb-4 flex items-center">
          <i class="fas fa-calculator mr-2"></i>
          Period Summary
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="text-center">
            <div class="text-2xl font-bold text-blue-900">${count}</div>
            <div class="text-sm text-blue-600">Total Loans</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-green-600">₦${this.formatNumber(
              total_loan_amount
            )}</div>
            <div class="text-sm text-green-600">Total Loan Amount</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-purple-600">₦${this.formatNumber(
              total_monthly_repayment
            )}</div>
            <div class="text-sm text-purple-600">Total Monthly Repayment</div>
          </div>
        </div>
      </div>
    `;

    const html = `
      <div class="space-y-4">
        ${loansHtml}
        ${totalsHtml}
      </div>
    `;

    console.log("Generated HTML length:", html.length);
    console.log("Loans HTML length:", loansHtml.length);
    console.log("Totals HTML length:", totalsHtml.length);

    $("#current-period-loans-content").html(html);
    console.log("HTML content set to card");

    // Force the card to be visible and in viewport
    try {
      const $card = $("#current-period-loans-card");
      if ($card.length > 0 && $card[0].ownerDocument) {
        $card[0].scrollIntoView({ behavior: "smooth", block: "start" });
        console.log("Card scrolled into view");
      } else {
        console.error("Card element not found or detached from DOM!");
      }
    } catch (e) {
      console.log("Error scrolling to card:", e);
    }
  }

  async handleUpdateLoan() {
    if (!this.currentEmployee || !this.currentPeriod) {
      this.showError("Please select an employee and payroll period first");
      return;
    }

    const loanAmount = parseFloat($("#loan-amount").val());
    const batchNumber = $("#batch-number").val();

    if (!loanAmount || loanAmount <= 0) {
      this.showError("Please enter a valid loan amount");
      return;
    }

    if (!batchNumber) {
      this.showError("Please enter a batch number");
      return;
    }

    try {
      this.showLoading();

      const result = await this.makeRequest("POST", {
        action: "update_loan",
        coop_id: this.currentEmployee.coop_id,
        loan_amount: loanAmount,
        period_id: this.currentPeriod,
        batch: batchNumber,
      });

      if (result.success) {
        this.showSuccess("Loan updated successfully!");
        $("#loan-amount").val("");

        // Reload loan calculation
        await this.loadLoanCalculation(
          this.currentEmployee.coop_id,
          this.currentPeriod
        );

        // Reload loan list
        await this.loadLoanList(
          this.currentEmployee.coop_id,
          this.currentPeriod
        );

        // Reload current period loans to show the new loan
        await this.loadCurrentPeriodLoans(this.currentPeriod);
      } else {
        this.showError("Failed to update loan: " + result.message);
      }
    } catch (error) {
      console.error("Error updating loan:", error);
      this.showError("Error updating loan");
    } finally {
      this.hideLoading();
    }
  }

  async loadLoanList(coopId, periodId = null) {
    try {
      const result = await this.makeRequest("GET", {
        action: "get_loan_list",
        coop_id: coopId,
        period_id: periodId,
      });

      if (result.success) {
        this.displayLoanList(result.data, "#loan-list-content");
      }
    } catch (error) {
      console.error("Error loading loan list:", error);
    }
  }

  displayLoanList(loans, container) {
    if (loans.length === 0) {
      $(container).html(`
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-4"></i>
                    <p>No loans found for this employee</p>
                </div>
            `);
      return;
    }

    const html = `
             <div class="overflow-x-auto">
                 <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                     <thead class="bg-gray-50">
                         <tr>
                             <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                             <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                             <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monthly Repayment</th>
                             <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                             <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                             <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                         </tr>
                     </thead>
                     <tbody class="bg-white divide-y divide-gray-200">
                         ${loans
                           .map(
                             (loan) => `
                             <tr class="hover:bg-gray-50">
                                 <td class="px-4 py-3 text-sm text-gray-900">${
                                   loan.date_of_loan_app
                                 }</td>
                                 <td class="px-4 py-3 text-sm text-gray-900">₦${
                                   loan.loan_amount
                                 }</td>
                                 <td class="px-4 py-3 text-sm text-gray-900">₦${
                                   loan.monthly_repayment
                                 }</td>
                                 <td class="px-4 py-3 text-sm text-gray-900">${
                                   loan.payroll_period
                                 }</td>
                                 <td class="px-4 py-3 text-sm text-gray-900">${
                                   loan.batch || "N/A"
                                 }</td>
                                 <td class="px-4 py-3 text-sm">
                                     <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                       loan.loan_status == 1
                                         ? "bg-green-100 text-green-800"
                                         : "bg-gray-100 text-gray-800"
                                     }">
                                         ${loan.status_text}
                                     </span>
                                 </td>
                             </tr>
                         `
                           )
                           .join("")}
                     </tbody>
                 </table>
             </div>
         `;

    $(container).html(html);
  }

  async showLoanListModal() {
    if (!this.currentEmployee) {
      this.showError("Please select an employee first");
      return;
    }

    try {
      this.showLoading("#loan-list-modal");
      $("#loan-list-modal").removeClass("hidden");

      const result = await this.makeRequest("GET", {
        action: "get_loan_list",
        coop_id: this.currentEmployee.coop_id,
      });

      if (result.success) {
        this.displayLoanList(result.data, "#modal-loan-list");
      } else {
        this.showError("Failed to load loan list: " + result.message);
      }
    } catch (error) {
      console.error("Error showing loan list modal:", error);
      this.showError("Error loading loan list");
    } finally {
      this.hideLoading("#loan-list-modal");
    }
  }

  hideLoanListModal() {
    $("#loan-list-modal").addClass("hidden");
  }

  validateLoanAmount(amount) {
    const amountNum = parseFloat(amount);
    const maxAmount = parseFloat($("#loan-amount").attr("max")) || 0;

    if (amountNum > maxAmount) {
      $("#loan-amount").addClass("border-red-500");
      this.showError(
        `Loan amount cannot exceed ₦${this.formatNumber(maxAmount)}`
      );
    } else {
      $("#loan-amount").removeClass("border-red-500");
    }
  }

  async makeRequest(method, data) {
    const url = "api/loan-processor.php";

    const options = {
      method: method,
      headers: {
        "Content-Type": "application/json",
      },
    };

    if (method === "POST") {
      options.body = JSON.stringify(data);
    } else if (method === "GET") {
      const params = new URLSearchParams(data);
      const fullUrl = `${url}?${params}`;
      const response = await fetch(fullUrl, options);
      return await response.json();
    }

    const response = await fetch(url, options);
    return await response.json();
  }

  showLoading(selector = "#loading-overlay") {
    $(selector).removeClass("hidden");
  }

  hideLoading(selector = "#loading-overlay") {
    $(selector).addClass("hidden");
  }

  showSuccess(message) {
    Swal.fire({
      icon: "success",
      title: "Success!",
      text: message,
      timer: 3000,
      showConfirmButton: false,
    });
  }

  showError(message) {
    Swal.fire({
      icon: "error",
      title: "Error!",
      text: message,
      confirmButtonText: "OK",
    });
  }

  toggleClearButton() {
    const searchValue = $("#employee-search").val().trim();
    if (searchValue.length > 0) {
      $("#clear-search-btn").removeClass("hidden");
    } else {
      $("#clear-search-btn").addClass("hidden");
    }
  }

  clearEmployeeSearch() {
    $("#employee-search").val("").trigger("input");
    $("#clear-search-btn").addClass("hidden");

    // Clear all employee-related data
    this.currentEmployee = null;
    // Don't reset currentPeriod - keep it for loan input

    // Hide employee-related cards only
    $("#employee-details-card").addClass("hidden").removeClass("fade-in");
    $("#loan-calculation-card").addClass("hidden").removeClass("fade-in");
    $("#loan-list-card").addClass("hidden").removeClass("fade-in");

    // Keep current-period-loans-card visible if period is selected
    if (this.currentPeriod) {
      $("#current-period-loans-card").removeClass("hidden").addClass("fade-in");
    }

    // Clear form fields except period
    $("#loan-amount").val("");
    // Don't clear payroll-period
  }

  initializeBatchNumber() {
    const batchNumber = this.generateBatchNumber();
    $("#batch-number").val(batchNumber);
  }

  generateBatchNumber() {
    const now = new Date();
    const day = now.getDate();
    const month = now.toLocaleString("default", { month: "long" });
    const year = now.getFullYear().toString().slice(-2);
    const hours = now.getHours();
    const minutes = now.getMinutes();
    const ampm = hours >= 12 ? "PM" : "AM";
    const displayHours = hours % 12 || 12;
    const displayMinutes = minutes.toString().padStart(2, "0");

    // Format: 9thJuly25_09:24AM
    const ordinal = this.getOrdinal(day);
    const timeStr = `${displayHours}:${displayMinutes}${ampm}`;

    return `${ordinal}${month}${year}_${timeStr}`;
  }

  getOrdinal(day) {
    if (day > 3 && day < 21) return day + "th";
    switch (day % 10) {
      case 1:
        return day + "st";
      case 2:
        return day + "nd";
      case 3:
        return day + "rd";
      default:
        return day + "th";
    }
  }

  formatNumber(number) {
    return new Intl.NumberFormat("en-NG").format(number);
  }

  async handleEditLoan(button) {
    const loanId = $(button).data("loan-id");
    const coopId = $(button).data("coop-id");
    const loanAmount = $(button).data("loan-amount");
    const monthlyRepayment = $(button).data("monthly-repayment");
    const batch = $(button).data("batch");

    // Set current employee and period for update functionality
    this.currentEmployee = { coop_id: coopId };
    this.currentPeriod = $("#payroll-period").val();

    // Ensure loan calculation card is visible
    $("#loan-calculation-card").removeClass("hidden").addClass("fade-in");

    try {
      // Load employee details to populate the loan calculation card
      await this.loadEmployeeDetails(coopId);

      // Pre-fill the loan amount field with current value
      $("#loan-amount").val(loanAmount);

      // Focus on the loan amount field for editing after a short delay
      setTimeout(() => {
        $("#loan-amount").focus();
        // Scroll to the loan amount field
        $("#loan-amount")[0].scrollIntoView({
          behavior: "smooth",
          block: "center",
        });
      }, 300);

      // Show success message
      this.showSuccess(
        `Loan for ${coopId} loaded for editing. Modify the amount and click Update Loan.`
      );
    } catch (error) {
      console.error("Error loading employee details for edit:", error);
      this.showError("Error loading employee details for editing");
    }
  }

  async handleDeleteLoan(button) {
    const loanId = $(button).data("loan-id");
    const coopId = $(button).data("coop-id");
    const loanAmount = $(button).data("loan-amount");
    const name = $(button).data("name");

    const result = await Swal.fire({
      title: "Delete Loan?",
      html: `
        <div class="text-left">
          <p><strong>Coop ID:</strong> ${coopId}</p>
          <p><strong>Name:</strong> ${name}</p>
          <p><strong>Loan Amount:</strong> ₦${this.formatNumber(loanAmount)}</p>
          <p class="text-red-600 font-semibold mt-2">This action cannot be undone!</p>
        </div>
      `,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#ef4444",
      cancelButtonColor: "#6b7280",
      confirmButtonText: "Yes, Delete Loan",
      cancelButtonText: "Cancel",
    });

    if (result.isConfirmed) {
      try {
        this.showLoading("#loading-overlay");

        const response = await this.makeRequest("POST", {
          action: "delete_loan",
          loan_id: loanId,
        });

        if (response.success) {
          this.showSuccess("Loan deleted successfully!");

          // Reload current period loans to refresh the list
          if (this.currentPeriod) {
            await this.loadCurrentPeriodLoans(this.currentPeriod);
          }
        } else {
          this.showError("Failed to delete loan: " + response.message);
        }
      } catch (error) {
        console.error("Error deleting loan:", error);
        this.showError("Error deleting loan");
      } finally {
        this.hideLoading("#loading-overlay");
      }
    }
  }
}

// Initialize when document is ready
$(document).ready(function () {
  new LoanProcessor();
});
