<?php
// Set page title
$pageTitle = 'OOUTH COOP - Loan Request Admin';

// Include header
include 'includes/header.php';

require_once('Connections/coop.php');

// Check authentication
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}
?>
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8 fade-in">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-money-bill-wave text-blue-600 mr-3"></i>Loan Request Administration
                </h2>
                <p class="text-gray-600">Manage loan period limits and approve loan requests</p>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-xl shadow-lg mb-8 fade-in">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button onclick="showTab('limits')" id="tab-limits"
                    class="tab-button active py-4 px-6 text-sm font-medium text-blue-600 border-b-2 border-blue-600">
                    <i class="fas fa-chart-line mr-2"></i>Loan Limits
                </button>
                <button onclick="showTab('approvals')" id="tab-approvals"
                    class="tab-button py-4 px-6 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300">
                    <i class="fas fa-check-circle mr-2"></i>Pending Approvals
                </button>
                <button onclick="showTab('outstanding')" id="tab-outstanding"
                    class="tab-button py-4 px-6 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300">
                    <i class="fas fa-clock mr-2"></i>Outstanding Loans
                </button>
                <button onclick="showTab('manual')" id="tab-manual"
                    class="tab-button py-4 px-6 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300">
                    <i class="fas fa-plus-circle mr-2"></i>Manual Loan
                </button>
            </nav>
        </div>
    </div>

    <!-- Loan Limits Tab -->
    <div id="content-limits" class="tab-content">
        <!-- Action Bar -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 fade-in">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <i class="fas fa-chart-line text-blue-600 mr-3"></i>
                    <h3 class="text-lg font-semibold text-gray-900">Monthly Loan Limits</h3>
                </div>
                <button onclick="openAddLimitModal()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-plus mr-2"></i>Set New Limit
                </button>
            </div>
        </div>

        <!-- Limits Table -->
        <div class="bg-white rounded-xl shadow-lg fade-in">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Period Limits</h3>
            </div>
            <div class="p-6">
                <div id="limits-table-container">
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-blue-600 text-2xl mb-2"></i>
                        <p class="text-gray-500">Loading limits...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Approvals Tab -->
    <div id="content-approvals" class="tab-content hidden">
        <!-- Summary Cards -->
        <div id="summary-cards" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Cards will be populated by JavaScript -->
        </div>

        <!-- Period Summary -->
        <div class="bg-white rounded-xl shadow-lg mb-8 fade-in">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-chart-bar text-blue-600 mr-2"></i>Period Summary
                </h3>
            </div>
            <div class="p-6">
                <div id="period-summary-container">
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-blue-600 text-2xl mb-2"></i>
                        <p class="text-gray-500">Loading summary...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Requests Table -->
        <div class="bg-white rounded-xl shadow-lg fade-in">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Pending Loan Requests</h3>
            </div>
            <div class="p-6">
                <div id="approvals-table-container">
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-blue-600 text-2xl mb-2"></i>
                        <p class="text-gray-500">Loading requests...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Loan Tab -->
    <div id="content-manual" class="tab-content hidden">
        <div class="bg-white rounded-xl shadow-lg fade-in">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-plus-circle text-green-600 mr-2"></i>Create Loan Manually
                </h3>
            </div>
            <div class="p-6">
                <form id="manual-loan-form" class="space-y-6">
                    <!-- Member Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Member CoopID <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" id="manual-member-coop-id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter or search CoopID/Name" required
                                onkeyup="handleMemberSearchInput(event)">
                            <button type="button" onclick="searchMemberForManualLoan()"
                                class="absolute right-2 top-2 px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                        <!-- Search Results Dropdown -->
                        <div id="manual-member-results"
                            class="mt-2 hidden border border-gray-300 rounded-lg bg-white shadow-lg max-h-60 overflow-y-auto z-10">
                            <!-- Search results will be displayed here -->
                        </div>
                        <!-- Selected Member Info -->
                        <div id="manual-member-info" class="mt-2 hidden p-3 bg-gray-50 rounded-lg">
                            <!-- Selected member info will be displayed here -->
                        </div>
                    </div>

                    <!-- Period Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Payroll Period <span class="text-red-500">*</span>
                        </label>
                        <select id="manual-period-id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            required>
                            <option value="">Loading periods...</option>
                        </select>
                    </div>

                    <!-- Loan Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Requested Amount (₦) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="manual-requested-amount" step="0.01" min="0"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter loan amount" required>
                    </div>

                    <!-- Status Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Initial Status <span class="text-red-500">*</span>
                        </label>
                        <select id="manual-status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            required>
                            <option value="draft">Draft</option>
                            <option value="submitted">Submitted</option>
                            <option value="approved">Approved</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            <strong>Draft:</strong> Created but not submitted<br>
                            <strong>Submitted:</strong> Ready for admin review<br>
                            <strong>Approved:</strong> Auto-approved (bypasses guarantors)
                        </p>
                    </div>

                    <!-- Approved Amount (only shown if status is approved) -->
                    <div id="manual-approved-amount-container" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Approved Amount (₦)
                            <span class="text-xs text-gray-500 font-normal">(Leave empty for 100% approval)</span>
                        </label>
                        <input type="number" id="manual-approved-amount" step="0.01" min="0"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter approved amount">
                        <p class="text-xs text-gray-500 mt-1" id="manual-outstanding-info"></p>
                    </div>

                    <!-- Skip Guarantors (only shown if status is approved) -->
                    <div id="manual-skip-guarantors-container" class="hidden">
                        <label class="flex items-center">
                            <input type="checkbox" id="manual-skip-guarantors" class="mr-2">
                            <span class="text-sm text-gray-700">Skip guarantor requirement</span>
                        </label>
                    </div>

                    <!-- Guarantors Section -->
                    <div id="manual-guarantors-section">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Guarantors (2 required) <span class="text-red-500">*</span>
                        </label>

                        <!-- Guarantor 1 -->
                        <div class="mb-4">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Guarantor 1</label>
                            <div class="relative">
                                <input type="text" id="manual-guarantor-1-coop-id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter or search CoopID/Name"
                                    onkeyup="handleGuarantorSearchInput(event, 1)">
                                <button type="button" onclick="searchGuarantorForManualLoan(1)"
                                    class="absolute right-2 top-2 px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div id="manual-guarantor-1-results"
                                class="mt-1 hidden border border-gray-300 rounded-lg bg-white shadow-lg max-h-48 overflow-y-auto z-10">
                                <!-- Search results will be displayed here -->
                            </div>
                            <div id="manual-guarantor-1-info" class="mt-1 hidden p-2 bg-gray-50 rounded text-xs">
                                <!-- Selected guarantor info will be displayed here -->
                            </div>
                        </div>

                        <!-- Guarantor 2 -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Guarantor 2</label>
                            <div class="relative">
                                <input type="text" id="manual-guarantor-2-coop-id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter or search CoopID/Name"
                                    onkeyup="handleGuarantorSearchInput(event, 2)">
                                <button type="button" onclick="searchGuarantorForManualLoan(2)"
                                    class="absolute right-2 top-2 px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div id="manual-guarantor-2-results"
                                class="mt-1 hidden border border-gray-300 rounded-lg bg-white shadow-lg max-h-48 overflow-y-auto z-10">
                                <!-- Search results will be displayed here -->
                            </div>
                            <div id="manual-guarantor-2-info" class="mt-1 hidden p-2 bg-gray-50 rounded text-xs">
                                <!-- Selected guarantor info will be displayed here -->
                            </div>
                        </div>
                    </div>

                    <!-- Admin Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Admin Notes (Optional)</label>
                        <textarea id="manual-admin-notes" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Add any notes about this loan..."></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="resetManualLoanForm()"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                            Reset
                        </button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <i class="fas fa-save mr-2"></i>Create Loan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Outstanding Loans Tab -->
    <div id="content-outstanding" class="tab-content hidden">
        <div class="bg-white rounded-xl shadow-lg fade-in">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-clock text-yellow-600 mr-2"></i>Outstanding Loans
                    </h3>
                    <button onclick="loadOutstandingLoans()"
                        class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                        <i class="fas fa-sync-alt mr-1"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div id="outstanding-table-container">
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-blue-600 text-2xl mb-2"></i>
                        <p class="text-gray-500">Loading outstanding loans...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Add/Edit Limit Modal -->
<div id="limitModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900" id="modal-title">Set Loan Limit</h3>
            </div>
            <div class="px-6 py-4">
                <form id="limitForm">
                    <input type="hidden" id="limit-id">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payroll Period</label>
                        <select id="limit-period-id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            required>
                            <option value="">Select Period</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Limit Amount (₦)</label>
                        <input type="number" id="limit-amount" step="0.01" min="0"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                        <textarea id="limit-notes" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                </form>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                <button onclick="closeLimitModal()"
                    class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                <button onclick="saveLimit()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Import Outstanding Loan Modal -->
<div id="importModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">Import Outstanding Loan</h3>
            </div>
            <div class="px-6 py-4">
                <div id="import-details"></div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Period to Import To</label>
                    <select id="import-period-id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        required>
                        <option value="">Loading periods...</option>
                    </select>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                <button onclick="closeImportModal()"
                    class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                <button onclick="importOutstandingLoan()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Import</button>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div id="approvalModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">Approve Loan Request</h3>
            </div>
            <div class="px-6 py-4">
                <div id="approval-details"></div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Approved Amount (₦)
                        <span class="text-xs text-gray-500 font-normal">(Leave empty for 100% approval, or enter partial
                            amount)</span>
                    </label>
                    <input type="number" id="approved-amount" step="0.01" min="0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Enter approved amount">
                    <p class="text-xs text-gray-500 mt-1" id="outstanding-info"></p>
                </div>
                <div class="mt-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="skip-guarantor" class="mr-2">
                        <span class="text-sm text-gray-700">Skip guarantor requirement (bypass guarantor
                            approvals)</span>
                    </label>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Admin Notes (Optional)</label>
                    <textarea id="admin-notes" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                <button onclick="closeApprovalModal()"
                    class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                <button onclick="approveLoan()"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Approve</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentLoanRequestId = null;
let apiBaseUrl = 'auth_api/api/admin';
let isAdminPanel = true; // Flag to indicate we're in admin panel

// Tab switching
function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-button').forEach(el => {
        el.classList.remove('active', 'text-blue-600', 'border-blue-600');
        el.classList.add('text-gray-500', 'border-transparent');
    });

    document.getElementById(`content-${tab}`).classList.remove('hidden');
    const button = document.getElementById(`tab-${tab}`);
    button.classList.add('active', 'text-blue-600', 'border-blue-600');
    button.classList.remove('text-gray-500', 'border-transparent');

    if (tab === 'limits') {
        loadLimits();
    } else if (tab === 'approvals') {
        loadPendingApprovals();
    } else if (tab === 'outstanding') {
        loadOutstandingLoans();
    } else if (tab === 'manual') {
        loadPeriodsForManualLoan();
    }
}

// Load loan limits
async function loadLimits() {
    try {
        const response = await fetch(`${apiBaseUrl}/loan-limits.php`, {
            credentials: 'include' // Include session cookies
        });
        const result = await response.json();

        if (result.success) {
            displayLimits(result.data);
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (error) {
        console.error('Error loading limits:', error);
        Swal.fire('Error', 'Failed to load limits', 'error');
    }
}

function displayLimits(limits) {
    const container = document.getElementById('limits-table-container');

    if (!limits || limits.length === 0) {
        container.innerHTML = '<div class="text-center py-8 text-gray-500">No limits set yet</div>';
        return;
    }

    let html =
        '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Limit Amount</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Set By</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>';
    html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';

    limits.forEach(limit => {
        html += `<tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${limit.period_name || 'Period ' + limit.period_id}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₦${parseFloat(limit.limit_amount).toLocaleString('en-NG', {minimumFractionDigits: 2})}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${limit.set_by}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <button onclick="editLimit(${limit.id}, ${limit.period_id}, ${limit.limit_amount}, '${limit.notes || ''}')" 
                    class="text-blue-600 hover:text-blue-800 mr-3">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

// Load pending approvals
async function loadPendingApprovals() {
    try {
        const container = document.getElementById('approvals-table-container');
        container.innerHTML =
            '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-blue-600 text-2xl mb-2"></i><p class="text-gray-500">Loading requests...</p></div>';

        const response = await fetch(`${apiBaseUrl}/loan-approval.php`, {
            credentials: 'include' // Include session cookies
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('Approval API response:', result);

        if (result.success) {
            if (result.summary) {
                displaySummaryCards(result.summary);
                displayPeriodSummary(result.summary.periods || []);
            }
            displayPendingApprovals(result.data || []);
        } else {
            container.innerHTML = `<div class="text-center py-8 text-red-500">Error: ${result.message}</div>`;
            Swal.fire('Error', result.message, 'error');
        }
    } catch (error) {
        console.error('Error loading approvals:', error);
        const container = document.getElementById('approvals-table-container');
        container.innerHTML = `<div class="text-center py-8 text-red-500">Failed to load: ${error.message}</div>`;
        Swal.fire('Error', 'Failed to load pending approvals: ' + error.message, 'error');
    }
}

function displaySummaryCards(summary) {
    const container = document.getElementById('summary-cards');

    if (!summary || !summary.periods) {
        container.innerHTML = '<div class="col-span-4 text-center py-4 text-gray-500">No summary data available</div>';
        return;
    }

    const totalSubmitted = summary.total_submitted || 0;
    const totalPendingGuarantor = summary.total_pending_guarantor || 0;

    // Calculate total amounts
    let totalSubmittedAmount = 0;
    let totalPendingGuarantorAmount = 0;

    summary.periods.forEach(period => {
        totalSubmittedAmount += period.submitted_amount || 0;
        totalPendingGuarantorAmount += period.pending_guarantor_amount || 0;
    });

    container.innerHTML = `
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Total Submitted</p>
                    <p class="text-2xl font-bold text-gray-900">${totalSubmitted}</p>
                    <p class="text-sm text-gray-600 mt-1">₦${totalSubmittedAmount.toLocaleString('en-NG', {minimumFractionDigits: 2})}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-file-alt text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Pending Guarantor</p>
                    <p class="text-2xl font-bold text-gray-900">${totalPendingGuarantor}</p>
                    <p class="text-sm text-gray-600 mt-1">₦${totalPendingGuarantorAmount.toLocaleString('en-NG', {minimumFractionDigits: 2})}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <i class="fas fa-user-clock text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Total Approved</p>
                    <p class="text-2xl font-bold text-gray-900">${summary.periods.reduce((sum, p) => sum + (p.approved_count || 0), 0)}</p>
                    <p class="text-sm text-gray-600 mt-1">₦${summary.periods.reduce((sum, p) => sum + (p.approved_amount || 0), 0).toLocaleString('en-NG', {minimumFractionDigits: 2})}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Total Limit Set</p>
                    <p class="text-2xl font-bold text-gray-900">${summary.periods.filter(p => p.limit_amount).length}</p>
                    <p class="text-sm text-gray-600 mt-1">₦${summary.periods.reduce((sum, p) => sum + (p.limit_amount || 0), 0).toLocaleString('en-NG', {minimumFractionDigits: 2})}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <i class="fas fa-chart-line text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>
    `;
}

function displayPeriodSummary(periods) {
    const container = document.getElementById('period-summary-container');

    if (!periods || periods.length === 0) {
        container.innerHTML = '<div class="text-center py-8 text-gray-500">No period data available</div>';
        return;
    }

    let html =
        '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Limit</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pending Guarantor</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approved</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Used</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remaining</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usage %</th>';
    html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';

    periods.forEach(period => {
        const usagePercentage = period.usage_percentage !== null ? period.usage_percentage.toFixed(1) : 'N/A';
        const usageColor = period.usage_percentage !== null ?
            (period.usage_percentage >= 90 ? 'text-red-600' : period.usage_percentage >= 75 ?
                'text-yellow-600' : 'text-green-600') :
            'text-gray-600';

        html += `<tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${period.period_name}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${period.limit_amount ? '₦' + parseFloat(period.limit_amount).toLocaleString('en-NG', {minimumFractionDigits: 2}) : '<span class="text-gray-400">Not Set</span>'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${period.submitted_count} (₦${parseFloat(period.submitted_amount).toLocaleString('en-NG', {minimumFractionDigits: 2})})
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${period.pending_guarantor_count} (₦${parseFloat(period.pending_guarantor_amount).toLocaleString('en-NG', {minimumFractionDigits: 2})})
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${period.approved_count} (₦${parseFloat(period.approved_amount).toLocaleString('en-NG', {minimumFractionDigits: 2})})
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                ₦${parseFloat(period.total_submitted_and_approved).toLocaleString('en-NG', {minimumFractionDigits: 2})}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${period.remaining_limit !== null 
                    ? '₦' + parseFloat(period.remaining_limit).toLocaleString('en-NG', {minimumFractionDigits: 2})
                    : '<span class="text-gray-400">N/A</span>'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold ${usageColor}">
                ${usagePercentage}${usagePercentage !== 'N/A' ? '%' : ''}
            </td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function displayPendingApprovals(requests) {
    const container = document.getElementById('approvals-table-container');

    if (!requests || requests.length === 0) {
        container.innerHTML = '<div class="text-center py-8 text-gray-500">No pending requests</div>';
        return;
    }

    let html =
        '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requester</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guarantors</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guarantor Names</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>';
    html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';

    requests.forEach(req => {
        const statusBadge = req.status === 'submitted' ?
            '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Submitted</span>' :
            '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Fully Guaranteed</span>';

        const guarantorStatus = req.approved_guarantors === req.total_guarantors ?
            `<span class="text-green-600 font-semibold">${req.approved_guarantors}/${req.total_guarantors}</span>` :
            `<span class="text-yellow-600">${req.approved_guarantors}/${req.total_guarantors}</span>`;

        // Build guarantor names display
        let guarantorNamesHtml = '<div class="space-y-1">';
        if (req.guarantors && req.guarantors.length > 0) {
            req.guarantors.forEach((guarantor, index) => {
                const statusColor = guarantor.status === 'approved' ? 'text-green-600' :
                    guarantor.status === 'rejected' ? 'text-red-600' :
                    'text-yellow-600';
                const statusIcon = guarantor.status === 'approved' ? 'fa-check-circle' :
                    guarantor.status === 'rejected' ? 'fa-times-circle' :
                    'fa-clock';
                guarantorNamesHtml += `
                    <div class="flex items-center text-xs">
                        <i class="fas ${statusIcon} ${statusColor} mr-1"></i>
                        <span class="${statusColor}">${guarantor.name || 'Unknown'}</span>
                        <span class="text-gray-500 ml-1">(${guarantor.coop_id})</span>
                    </div>
                `;
            });
        } else {
            guarantorNamesHtml += '<span class="text-gray-400 text-xs">No guarantors</span>';
        }
        guarantorNamesHtml += '</div>';

        const submittedDate = req.submitted_at ?
            new Date(req.submitted_at).toLocaleDateString('en-NG', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            }) :
            'N/A';

        // Escape special characters in requester name for onclick
        const escapedName = (req.requester_name || 'Unknown').replace(/'/g, "\\'").replace(/"/g, '&quot;');

        html += `<tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${req.requester_name || 'Unknown'}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">₦${parseFloat(req.requested_amount).toLocaleString('en-NG', {minimumFractionDigits: 2})}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${req.period_name || 'Period ' + req.period_id}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">${statusBadge}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">${guarantorStatus}</td>
            <td class="px-6 py-4 text-sm text-gray-700 max-w-xs">${guarantorNamesHtml}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${submittedDate}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <button onclick="openApprovalModal(${req.id}, '${escapedName}', ${req.requested_amount}, ${req.approved_guarantors}, ${req.total_guarantors})" 
                    class="px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                    <i class="fas fa-check-circle mr-1"></i> Approve
                </button>
            </td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

// Modal functions
function openAddLimitModal() {
    document.getElementById('limit-id').value = '';
    document.getElementById('limit-period-id').value = '';
    document.getElementById('limit-amount').value = '';
    document.getElementById('limit-notes').value = '';
    document.getElementById('modal-title').textContent = 'Set Loan Limit';
    loadPeriodsForLimit();
    document.getElementById('limitModal').classList.remove('hidden');
}

function editLimit(id, periodId, amount, notes) {
    document.getElementById('limit-id').value = id;
    document.getElementById('limit-period-id').value = periodId;
    document.getElementById('limit-amount').value = amount;
    document.getElementById('limit-notes').value = notes || '';
    document.getElementById('modal-title').textContent = 'Edit Loan Limit';
    loadPeriodsForLimit();
    document.getElementById('limitModal').classList.remove('hidden');
}

function closeLimitModal() {
    document.getElementById('limitModal').classList.add('hidden');
}

async function loadPeriodsForLimit() {
    const select = document.getElementById('limit-period-id');
    select.innerHTML = '<option value="">Loading...</option>';

    try {
        const response = await fetch(`${apiBaseUrl}/periods.php`, {
            credentials: 'include' // Include session cookies
        });
        const result = await response.json();

        if (result.success && result.data && result.data.length > 0) {
            select.innerHTML = '<option value="">Select Period</option>';
            result.data.forEach(period => {
                const option = document.createElement('option');
                const periodId = period.id || period.period_id;
                const displayName = period.display_name || period.description || period.PayrollPeriod ||
                    period.name || `Period ${periodId}`;

                option.value = periodId;
                option.textContent = displayName;
                select.appendChild(option);
            });
        } else {
            select.innerHTML = '<option value="">No periods available</option>';
            console.warn('No periods found:', result.message || 'No data returned');
        }
    } catch (error) {
        console.error('Error loading periods:', error);
        select.innerHTML = '<option value="">Error loading periods</option>';
        Swal.fire('Error', 'Failed to load periods: ' + error.message, 'error');
    }
}

async function saveLimit() {
    const id = document.getElementById('limit-id').value;
    const periodId = document.getElementById('limit-period-id').value;
    const amount = document.getElementById('limit-amount').value;
    const notes = document.getElementById('limit-notes').value;

    if (!periodId || !amount) {
        Swal.fire('Error', 'Please fill all required fields', 'error');
        return;
    }

    try {
        const url = id ? `${apiBaseUrl}/loan-limits.php` : `${apiBaseUrl}/loan-limits.php`;
        const method = id ? 'PUT' : 'POST';
        const body = id ? {
            id: parseInt(id),
            limit_amount: parseFloat(amount),
            notes: notes
        } : {
            period_id: parseInt(periodId),
            limit_amount: parseFloat(amount),
            notes: notes
        };

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include', // Include session cookies
            body: JSON.stringify(body)
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire('Success', result.message, 'success');
            closeLimitModal();
            loadLimits();
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (error) {
        console.error('Error saving limit:', error);
        Swal.fire('Error', 'Failed to save limit', 'error');
    }
}

let currentRequestedAmount = 0;

function openApprovalModal(id, name, amount, approvedGuarantors, totalGuarantors) {
    currentLoanRequestId = id;
    currentRequestedAmount = parseFloat(amount);
    document.getElementById('skip-guarantor').checked = false;
    document.getElementById('admin-notes').value = '';
    document.getElementById('approved-amount').value = '';
    document.getElementById('outstanding-info').textContent = '';

    const details = document.getElementById('approval-details');
    details.innerHTML = `
        <div class="space-y-2">
            <p><strong>Requester:</strong> ${name}</p>
            <p><strong>Requested Amount:</strong> ₦${currentRequestedAmount.toLocaleString('en-NG', {minimumFractionDigits: 2})}</p>
            <p><strong>Guarantors:</strong> ${approvedGuarantors}/${totalGuarantors} approved</p>
        </div>
    `;

    // Add event listener to calculate outstanding amount
    const approvedAmountInput = document.getElementById('approved-amount');
    approvedAmountInput.oninput = function() {
        const approved = parseFloat(this.value) || 0;
        if (approved > currentRequestedAmount) {
            document.getElementById('outstanding-info').innerHTML =
                '<span class="text-red-600">Amount cannot exceed requested amount</span>';
        } else if (approved > 0 && approved < currentRequestedAmount) {
            const outstanding = currentRequestedAmount - approved;
            document.getElementById('outstanding-info').innerHTML =
                `<span class="text-yellow-600">Outstanding: ₦${outstanding.toLocaleString('en-NG', {minimumFractionDigits: 2})} will be available for next period</span>`;
        } else {
            document.getElementById('outstanding-info').textContent = '';
        }
    };

    document.getElementById('approvalModal').classList.remove('hidden');
}

function closeApprovalModal() {
    document.getElementById('approvalModal').classList.add('hidden');
    currentLoanRequestId = null;
    currentRequestedAmount = 0;
    document.getElementById('approved-amount').value = '';
    document.getElementById('outstanding-info').textContent = '';
}

async function approveLoan() {
    if (!currentLoanRequestId) return;

    const skipGuarantor = document.getElementById('skip-guarantor').checked;
    const adminNotes = document.getElementById('admin-notes').value;
    const approvedAmountInput = document.getElementById('approved-amount').value;
    const approvedAmount = approvedAmountInput ? parseFloat(approvedAmountInput) : null;

    // Validate approved amount
    if (approvedAmount !== null) {
        if (approvedAmount <= 0) {
            Swal.fire('Error', 'Approved amount must be greater than 0', 'error');
            return;
        }
        if (approvedAmount > currentRequestedAmount) {
            Swal.fire('Error', 'Approved amount cannot exceed requested amount', 'error');
            return;
        }
    }

    try {
        const requestBody = {
            loan_request_id: currentLoanRequestId,
            skip_guarantor: skipGuarantor,
            admin_notes: adminNotes
        };

        // Only include approved_amount if it's different from requested amount
        if (approvedAmount !== null && approvedAmount !== currentRequestedAmount) {
            requestBody.approved_amount = approvedAmount;
        }

        const response = await fetch(`${apiBaseUrl}/loan-approval.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include', // Include session cookies
            body: JSON.stringify(requestBody)
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire('Success', result.message, 'success');
            closeApprovalModal();
            loadPendingApprovals();
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (error) {
        console.error('Error approving loan:', error);
        Swal.fire('Error', 'Failed to approve loan', 'error');
    }
}

// Outstanding Loans Functions
let currentOutstandingLoanId = null;

async function loadOutstandingLoans() {
    try {
        const container = document.getElementById('outstanding-table-container');
        container.innerHTML =
            '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-blue-600 text-2xl mb-2"></i><p class="text-gray-500">Loading...</p></div>';

        const response = await fetch(`${apiBaseUrl}/outstanding-loans.php`, {
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            displayOutstandingLoans(result.data || []);
        } else {
            container.innerHTML = `<div class="text-center py-8 text-red-500">Error: ${result.message}</div>`;
        }
    } catch (error) {
        console.error('Error loading outstanding loans:', error);
        const container = document.getElementById('outstanding-table-container');
        container.innerHTML = `<div class="text-center py-8 text-red-500">Failed to load: ${error.message}</div>`;
    }
}

function displayOutstandingLoans(loans) {
    const container = document.getElementById('outstanding-table-container');

    if (!loans || loans.length === 0) {
        container.innerHTML = '<div class="text-center py-8 text-gray-500">No outstanding loans</div>';
        return;
    }

    let html =
        '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requester</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approved</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Outstanding</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Original Period</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approved Date</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>';
    html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';

    loans.forEach(loan => {
        const approvedDate = loan.approved_at ?
            new Date(loan.approved_at).toLocaleDateString('en-NG', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            }) :
            'N/A';

        // Escape special characters for onclick
        const escapedName = (loan.requester_name || 'Unknown').replace(/'/g, "\\'").replace(/"/g, '&quot;');
        const escapedPeriod = (loan.original_period_name || 'Period ' + loan.original_period_id).replace(/'/g,
            "\\'").replace(/"/g, '&quot;');

        html += `<tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${loan.requester_name || 'Unknown'}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₦${parseFloat(loan.requested_amount).toLocaleString('en-NG', {minimumFractionDigits: 2})}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₦${parseFloat(loan.approved_amount).toLocaleString('en-NG', {minimumFractionDigits: 2})}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-yellow-600">₦${parseFloat(loan.outstanding_amount).toLocaleString('en-NG', {minimumFractionDigits: 2})}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${loan.original_period_name || 'Period ' + loan.original_period_id}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${approvedDate}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <button onclick="openImportModal(${loan.id}, '${escapedName}', ${loan.outstanding_amount}, '${escapedPeriod}')" 
                    class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                    <i class="fas fa-download mr-1"></i> Import to Current Period
                </button>
            </td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function openImportModal(loanId, requesterName, outstandingAmount, originalPeriod) {
    currentOutstandingLoanId = loanId;

    const details = document.getElementById('import-details');
    details.innerHTML = `
        <div class="space-y-2">
            <p><strong>Requester:</strong> ${requesterName}</p>
            <p><strong>Outstanding Amount:</strong> ₦${parseFloat(outstandingAmount).toLocaleString('en-NG', {minimumFractionDigits: 2})}</p>
            <p><strong>Original Period:</strong> ${originalPeriod}</p>
            <p class="text-sm text-gray-600 mt-2">This will create a new loan request for the selected period with the outstanding amount.</p>
        </div>
    `;

    // Load periods for import
    loadPeriodsForImport();

    document.getElementById('importModal').classList.remove('hidden');
}

function closeImportModal() {
    document.getElementById('importModal').classList.add('hidden');
    currentOutstandingLoanId = null;
}

async function loadPeriodsForImport() {
    const select = document.getElementById('import-period-id');
    select.innerHTML = '<option value="">Loading...</option>';

    try {
        const response = await fetch(`${apiBaseUrl}/periods.php`, {
            credentials: 'include'
        });
        const result = await response.json();

        if (result.success && result.data && result.data.length > 0) {
            select.innerHTML = '<option value="">Select Period</option>';
            result.data.forEach(period => {
                const option = document.createElement('option');
                const periodId = period.id || period.period_id;
                const displayName = period.display_name || period.description || period.PayrollPeriod ||
                    period.name || `Period ${periodId}`;

                option.value = periodId;
                option.textContent = displayName;
                select.appendChild(option);
            });
        } else {
            select.innerHTML = '<option value="">No periods available</option>';
        }
    } catch (error) {
        console.error('Error loading periods:', error);
        select.innerHTML = '<option value="">Error loading periods</option>';
    }
}

async function importOutstandingLoan() {
    if (!currentOutstandingLoanId) return;

    const newPeriodId = document.getElementById('import-period-id').value;

    if (!newPeriodId) {
        Swal.fire('Error', 'Please select a period to import to', 'error');
        return;
    }

    try {
        const response = await fetch(`${apiBaseUrl}/outstanding-loans.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                outstanding_loan_id: currentOutstandingLoanId,
                new_period_id: parseInt(newPeriodId)
            })
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire('Success', result.message, 'success');
            closeImportModal();
            loadOutstandingLoans();
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (error) {
        console.error('Error importing outstanding loan:', error);
        Swal.fire('Error', 'Failed to import outstanding loan', 'error');
    }
}

function getAuthToken() {
    // Admin panel uses session-based auth, so we don't need a token
    // But API endpoints expect Authorization header, so return empty or session ID
    return '';
}

// Manual Loan Functions
async function loadPeriodsForManualLoan() {
    const select = document.getElementById('manual-period-id');
    select.innerHTML = '<option value="">Loading...</option>';

    try {
        const response = await fetch(`${apiBaseUrl}/periods.php`, {
            credentials: 'include'
        });
        const result = await response.json();

        if (result.success && result.data && result.data.length > 0) {
            select.innerHTML = '<option value="">Select Period</option>';
            result.data.forEach(period => {
                const option = document.createElement('option');
                const periodId = period.id || period.period_id;
                const displayName = period.display_name || period.description || period.PayrollPeriod ||
                    period.name || `Period ${periodId}`;

                option.value = periodId;
                option.textContent = displayName;
                select.appendChild(option);
            });
        } else {
            select.innerHTML = '<option value="">No periods available</option>';
        }
    } catch (error) {
        console.error('Error loading periods:', error);
        select.innerHTML = '<option value="">Error loading periods</option>';
    }
}

let memberSearchTimeout = null;

function handleMemberSearchInput(event) {
    const query = event.target.value.trim();

    // Clear previous timeout
    if (memberSearchTimeout) {
        clearTimeout(memberSearchTimeout);
    }

    // Hide results if query is too short
    if (query.length < 2) {
        document.getElementById('manual-member-results').classList.add('hidden');
        document.getElementById('manual-member-info').classList.add('hidden');
        return;
    }

    // Debounce search
    memberSearchTimeout = setTimeout(() => {
        searchMemberForManualLoan();
    }, 300);
}

async function searchMemberForManualLoan() {
    const coopId = document.getElementById('manual-member-coop-id').value.trim();
    const resultsDiv = document.getElementById('manual-member-results');
    const infoDiv = document.getElementById('manual-member-info');

    if (!coopId || coopId.length < 2) {
        resultsDiv.classList.add('hidden');
        return;
    }

    try {
        // Use the correct search endpoint (with underscore, not hyphen)
        const response = await fetch(`auth_api/api/auth/search_users.php?query=${encodeURIComponent(coopId)}`, {
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success && result.data && result.data.length > 0) {
            // Display all results in a dropdown
            let html = '';
            result.data.forEach((member, index) => {
                const fullName =
                    `${member.FirstName || ''} ${member.MiddleName || ''} ${member.LastName || ''}`.trim();
                html += `
                    <div onclick="selectMemberForManualLoan('${member.CoopID}', '${fullName.replace(/'/g, "\\'")}', '${member.EmailAddress || ''}', '${member.MobileNumber || ''}')" 
                        class="px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 ${index === 0 ? 'border-t-0' : ''}">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">${fullName}</p>
                                <p class="text-sm text-gray-600">CoopID: ${member.CoopID}</p>
                                ${member.EmailAddress && member.EmailAddress !== 'NONE' ? `<p class="text-xs text-gray-500">${member.EmailAddress}</p>` : ''}
                            </div>
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        </div>
                    </div>
                `;
            });

            resultsDiv.innerHTML = html;
            resultsDiv.classList.remove('hidden');

            // If only one result and it's an exact CoopID match, auto-select it
            if (result.data.length === 1 && result.data[0].CoopID.toUpperCase() === coopId.toUpperCase()) {
                const member = result.data[0];
                const fullName = `${member.FirstName || ''} ${member.MiddleName || ''} ${member.LastName || ''}`
                    .trim();
                selectMemberForManualLoan(member.CoopID, fullName, member.EmailAddress || '', member.MobileNumber ||
                    '');
            }
        } else {
            resultsDiv.innerHTML = `
                <div class="px-4 py-3 text-center text-gray-500">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    No members found
                </div>
            `;
            resultsDiv.classList.remove('hidden');
            infoDiv.classList.add('hidden');
        }
    } catch (error) {
        console.error('Error searching member:', error);
        resultsDiv.innerHTML = `
            <div class="px-4 py-3 text-center text-red-500">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Error searching: ${error.message}
            </div>
        `;
        resultsDiv.classList.remove('hidden');
    }
}

function selectMemberForManualLoan(coopId, fullName, email, mobile) {
    // Set the CoopID in the input
    document.getElementById('manual-member-coop-id').value = coopId;

    // Hide results dropdown
    document.getElementById('manual-member-results').classList.add('hidden');

    // Show selected member info
    const infoDiv = document.getElementById('manual-member-info');
    infoDiv.innerHTML = `
        <div class="flex items-center justify-between">
            <div>
                <p class="font-semibold text-gray-900">${fullName}</p>
                <p class="text-sm text-gray-600">CoopID: ${coopId}</p>
                ${email && email !== 'NONE' ? `<p class="text-sm text-gray-600">Email: ${email}</p>` : ''}
                ${mobile ? `<p class="text-sm text-gray-600">Mobile: ${mobile}</p>` : ''}
            </div>
            <button type="button" onclick="clearSelectedMember()" class="text-gray-400 hover:text-red-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    infoDiv.classList.remove('hidden');
}

function clearSelectedMember() {
    document.getElementById('manual-member-coop-id').value = '';
    document.getElementById('manual-member-info').classList.add('hidden');
    document.getElementById('manual-member-results').classList.add('hidden');
}

// Guarantor search functions
let guarantorSearchTimeouts = {};

function handleGuarantorSearchInput(event, guarantorNum) {
    const query = event.target.value.trim();

    // Clear previous timeout
    if (guarantorSearchTimeouts[guarantorNum]) {
        clearTimeout(guarantorSearchTimeouts[guarantorNum]);
    }

    // Hide results if query is too short
    if (query.length < 2) {
        document.getElementById(`manual-guarantor-${guarantorNum}-results`).classList.add('hidden');
        document.getElementById(`manual-guarantor-${guarantorNum}-info`).classList.add('hidden');
        return;
    }

    // Debounce search
    guarantorSearchTimeouts[guarantorNum] = setTimeout(() => {
        searchGuarantorForManualLoan(guarantorNum);
    }, 300);
}

async function searchGuarantorForManualLoan(guarantorNum) {
    const input = document.getElementById(`manual-guarantor-${guarantorNum}-coop-id`);
    const query = input.value.trim();
    const resultsDiv = document.getElementById(`manual-guarantor-${guarantorNum}-results`);
    const infoDiv = document.getElementById(`manual-guarantor-${guarantorNum}-info`);

    if (!query || query.length < 2) {
        resultsDiv.classList.add('hidden');
        return;
    }

    try {
        const response = await fetch(`auth_api/api/auth/search_users.php?query=${encodeURIComponent(query)}`, {
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success && result.data && result.data.length > 0) {
            // Filter out the requester and other guarantor
            const requesterCoopId = document.getElementById('manual-member-coop-id').value.trim();
            const otherGuarantorNum = guarantorNum === 1 ? 2 : 1;
            const otherGuarantorCoopId = document.getElementById(`manual-guarantor-${otherGuarantorNum}-coop-id`)
                .value.trim();

            const filteredResults = result.data.filter(member => {
                return member.CoopID !== requesterCoopId && member.CoopID !== otherGuarantorCoopId;
            });

            if (filteredResults.length > 0) {
                let html = '';
                filteredResults.forEach((member, index) => {
                    const fullName =
                        `${member.FirstName || ''} ${member.MiddleName || ''} ${member.LastName || ''}`
                        .trim();
                    html += `
                        <div onclick="selectGuarantorForManualLoan(${guarantorNum}, '${member.CoopID}', '${fullName.replace(/'/g, "\\'")}')" 
                            class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 ${index === 0 ? 'border-t-0' : ''}">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900 text-sm">${fullName}</p>
                                    <p class="text-xs text-gray-600">CoopID: ${member.CoopID}</p>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                            </div>
                        </div>
                    `;
                });

                resultsDiv.innerHTML = html;
                resultsDiv.classList.remove('hidden');

                // Auto-select if exact match
                if (filteredResults.length === 1 && filteredResults[0].CoopID.toUpperCase() === query
                    .toUpperCase()) {
                    const member = filteredResults[0];
                    const fullName = `${member.FirstName || ''} ${member.MiddleName || ''} ${member.LastName || ''}`
                        .trim();
                    selectGuarantorForManualLoan(guarantorNum, member.CoopID, fullName);
                }
            } else {
                resultsDiv.innerHTML = `
                    <div class="px-3 py-2 text-center text-gray-500 text-xs">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        No available guarantors found
                    </div>
                `;
                resultsDiv.classList.remove('hidden');
            }
        } else {
            resultsDiv.innerHTML = `
                <div class="px-3 py-2 text-center text-gray-500 text-xs">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    No members found
                </div>
            `;
            resultsDiv.classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error searching guarantor:', error);
        resultsDiv.innerHTML = `
            <div class="px-3 py-2 text-center text-red-500 text-xs">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Error: ${error.message}
            </div>
        `;
        resultsDiv.classList.remove('hidden');
    }
}

function selectGuarantorForManualLoan(guarantorNum, coopId, fullName) {
    // Set the CoopID in the input
    document.getElementById(`manual-guarantor-${guarantorNum}-coop-id`).value = coopId;

    // Hide results dropdown
    document.getElementById(`manual-guarantor-${guarantorNum}-results`).classList.add('hidden');

    // Show selected guarantor info
    const infoDiv = document.getElementById(`manual-guarantor-${guarantorNum}-info`);
    infoDiv.innerHTML = `
        <div class="flex items-center justify-between">
            <div>
                <span class="font-semibold text-gray-900">${fullName}</span>
                <span class="text-gray-600 ml-2">(${coopId})</span>
            </div>
            <button type="button" onclick="clearSelectedGuarantor(${guarantorNum})" class="text-gray-400 hover:text-red-600">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
    `;
    infoDiv.classList.remove('hidden');
}

function clearSelectedGuarantor(guarantorNum) {
    document.getElementById(`manual-guarantor-${guarantorNum}-coop-id`).value = '';
    document.getElementById(`manual-guarantor-${guarantorNum}-info`).classList.add('hidden');
    document.getElementById(`manual-guarantor-${guarantorNum}-results`).classList.add('hidden');
}

async function submitManualLoan() {
    const coopId = document.getElementById('manual-member-coop-id').value.trim();
    const periodId = document.getElementById('manual-period-id').value;
    const requestedAmount = parseFloat(document.getElementById('manual-requested-amount').value);
    const status = document.getElementById('manual-status').value;
    const approvedAmountInput = document.getElementById('manual-approved-amount').value;
    const approvedAmount = approvedAmountInput ? parseFloat(approvedAmountInput) : null;
    const skipGuarantors = document.getElementById('manual-skip-guarantors').checked;
    const adminNotes = document.getElementById('manual-admin-notes').value.trim();

    // Get guarantors
    const guarantor1 = document.getElementById('manual-guarantor-1-coop-id').value.trim();
    const guarantor2 = document.getElementById('manual-guarantor-2-coop-id').value.trim();
    const guarantors = [];
    if (guarantor1) guarantors.push(guarantor1);
    if (guarantor2) guarantors.push(guarantor2);

    // Validation
    if (!coopId || !periodId || !requestedAmount || requestedAmount <= 0) {
        Swal.fire('Error', 'Please fill in all required fields', 'error');
        return;
    }

    // Validate guarantors (required unless status is approved or skip_guarantors is checked)
    if (status !== 'approved' && !skipGuarantors && guarantors.length < 2) {
        Swal.fire('Error', 'Please select 2 guarantors or check "Skip guarantor requirement"', 'error');
        return;
    }

    // Validate guarantors are not the same as requester
    if (guarantors.includes(coopId)) {
        Swal.fire('Error', 'Guarantors cannot be the same as the requester', 'error');
        return;
    }

    // Validate guarantors are not duplicates
    if (guarantor1 && guarantor2 && guarantor1 === guarantor2) {
        Swal.fire('Error', 'Guarantors must be different', 'error');
        return;
    }

    if (status === 'approved' && approvedAmount !== null) {
        if (approvedAmount <= 0) {
            Swal.fire('Error', 'Approved amount must be greater than 0', 'error');
            return;
        }
        if (approvedAmount > requestedAmount) {
            Swal.fire('Error', 'Approved amount cannot exceed requested amount', 'error');
            return;
        }
    }

    try {
        const requestBody = {
            requester_coop_id: coopId,
            period_id: parseInt(periodId),
            requested_amount: requestedAmount,
            status: status,
            skip_guarantors: skipGuarantors,
            admin_notes: adminNotes || null,
            guarantors: guarantors
        };

        // Only include approved_amount if status is approved and it's different from requested
        if (status === 'approved' && approvedAmount !== null && approvedAmount !== requestedAmount) {
            requestBody.approved_amount = approvedAmount;
        }

        const response = await fetch(`${apiBaseUrl}/manual-loan.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(requestBody)
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire('Success', result.message, 'success');
            resetManualLoanForm();
            // Reload relevant tabs
            if (status === 'approved') {
                loadOutstandingLoans();
            } else if (status === 'submitted') {
                loadPendingApprovals();
            }
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (error) {
        console.error('Error creating manual loan:', error);
        Swal.fire('Error', 'Failed to create loan: ' + error.message, 'error');
    }
}

function resetManualLoanForm() {
    document.getElementById('manual-loan-form').reset();
    document.getElementById('manual-member-info').classList.add('hidden');
    document.getElementById('manual-member-results').classList.add('hidden');
    document.getElementById('manual-approved-amount-container').classList.add('hidden');
    document.getElementById('manual-skip-guarantors-container').classList.add('hidden');
    document.getElementById('manual-outstanding-info').textContent = '';
    clearSelectedGuarantor(1);
    clearSelectedGuarantor(2);
    loadPeriodsForManualLoan();
}

// Load initial data
document.addEventListener('DOMContentLoaded', function() {
    loadLimits();
    // Pre-load periods for faster modal opening
    loadPeriodsForLimit();

    // Handle status change to show/hide approved amount field
    const statusSelect = document.getElementById('manual-status');
    const approvedAmountContainer = document.getElementById('manual-approved-amount-container');
    const skipGuarantorsContainer = document.getElementById('manual-skip-guarantors-container');
    const approvedAmountInput = document.getElementById('manual-approved-amount');
    const requestedAmountInput = document.getElementById('manual-requested-amount');
    const outstandingInfo = document.getElementById('manual-outstanding-info');

    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            const guarantorsSection = document.getElementById('manual-guarantors-section');
            if (this.value === 'approved') {
                approvedAmountContainer.classList.remove('hidden');
                skipGuarantorsContainer.classList.remove('hidden');
                if (guarantorsSection) guarantorsSection.classList.add('hidden');
            } else {
                approvedAmountContainer.classList.add('hidden');
                skipGuarantorsContainer.classList.add('hidden');
                if (guarantorsSection) guarantorsSection.classList.remove('hidden');
                if (approvedAmountInput) approvedAmountInput.value = '';
                if (outstandingInfo) outstandingInfo.textContent = '';
            }
        });
    }

    // Calculate outstanding amount when approved amount changes
    if (approvedAmountInput && requestedAmountInput) {
        approvedAmountInput.addEventListener('input', function() {
            const requested = parseFloat(requestedAmountInput.value) || 0;
            const approved = parseFloat(this.value) || 0;

            if (approved > requested) {
                outstandingInfo.innerHTML =
                    '<span class="text-red-600">Amount cannot exceed requested amount</span>';
            } else if (approved > 0 && approved < requested) {
                const outstanding = requested - approved;
                outstandingInfo.innerHTML =
                    `<span class="text-yellow-600">Outstanding: ₦${outstanding.toLocaleString('en-NG', {minimumFractionDigits: 2})} will be available for next period</span>`;
            } else {
                outstandingInfo.textContent = '';
            }
        });
    }

    // Handle form submission
    const manualLoanForm = document.getElementById('manual-loan-form');
    if (manualLoanForm) {
        manualLoanForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await submitManualLoan();
        });
    }

    // Close search results when clicking outside
    document.addEventListener('click', function(event) {
        const memberInput = document.getElementById('manual-member-coop-id');
        const resultsDiv = document.getElementById('manual-member-results');

        if (memberInput && resultsDiv && !memberInput.contains(event.target) && !resultsDiv.contains(
                event.target)) {
            resultsDiv.classList.add('hidden');
        }

        // Close guarantor search results
        for (let i = 1; i <= 2; i++) {
            const guarantorInput = document.getElementById(`manual-guarantor-${i}-coop-id`);
            const guarantorResults = document.getElementById(`manual-guarantor-${i}-results`);

            if (guarantorInput && guarantorResults && !guarantorInput.contains(event.target) && !
                guarantorResults.contains(event.target)) {
                guarantorResults.classList.add('hidden');
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>