<?php
require_once('Connections/coop.php');
include_once('classes/model.php');

// Set page title
$pageTitle = 'OOUTH COOP - Dashboard';

// Include header
include 'includes/header.php';
?>

<style>
.fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card-hover {
    transition: all 0.3s ease;
}

.card-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.marquee {
    overflow: hidden;
    white-space: nowrap;
}

.marquee span {
    display: inline-block;
    animation: marquee 20s linear infinite;
}

@keyframes marquee {
    0% {
        transform: translateX(100%);
    }

    100% {
        transform: translateX(-100%);
    }
}
</style>

<!-- Welcome Section -->
<div class="mb-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Welcome to OOUTH COOP</h2>
                <p class="text-gray-600">Cooperative Management System Dashboard</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Current Date</p>
                <p class="text-lg font-semibold text-gray-900"><?= date('F j, Y') ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Marquee Section -->
<div class="mb-8">
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg p-4">
        <div class="marquee">
            <span class="text-white text-lg font-medium">
                Welcome to OOUTH COOP Management System - Your trusted partner in cooperative management and financial
                services.
                We are committed to providing excellent service to all our members.
                For inquiries, please contact us at info@oouthcoop.com or call +234 (0) 123 456 7890.
            </span>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Members</p>
                <p class="text-2xl font-bold text-gray-900">1,250</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-chart-line text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Active Loans</p>
                <p class="text-2xl font-bold text-gray-900">₦2.5M</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <i class="fas fa-piggy-bank text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Savings</p>
                <p class="text-2xl font-bold text-gray-900">₦5.2M</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <i class="fas fa-handshake text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">New Members</p>
                <p class="text-2xl font-bold text-gray-900">25</p>
            </div>
        </div>
    </div>
</div>

<!-- Accounting Financial Widgets (if accounting tables exist) -->
<?php
// Check if accounting tables exist
$accountsExist = mysqli_query($coop, "SHOW TABLES LIKE 'coop_accounts'");
if ($accountsExist && mysqli_num_rows($accountsExist) > 0) {
    // Get current period
    $currentPeriodQuery = "SELECT id, PayrollPeriod FROM tbpayrollperiods ORDER BY id DESC LIMIT 1";
    $currentPeriodResult = mysqli_query($coop, $currentPeriodQuery);
    $currentPeriod = mysqli_fetch_assoc($currentPeriodResult);
    $currentPeriodId = $currentPeriod['id'] ?? 0;
    
    if ($currentPeriodId > 0) {
        require_once('libs/services/AccountBalanceCalculator.php');
        $calculator = new AccountBalanceCalculator($coop, $database);
        
        try {
            // Get account balances
            $cashData = $calculator->getAccountBalance(4, $currentPeriodId);  // Bank Account
            $cashBalance = $cashData['balance'] ?? 0;
            
            $loansData = $calculator->getAccountBalance(6, $currentPeriodId);  // Member Loans
            $loansBalance = $loansData['balance'] ?? 0;
            
            $savingsData = $calculator->getAccountBalance(37, $currentPeriodId);  // Ordinary Savings
            $savingsBalance = $savingsData['balance'] ?? 0;
            
            $sharesData = $calculator->getAccountBalance(33, $currentPeriodId);  // Ordinary Shares
            $sharesBalance = $sharesData['balance'] ?? 0;
            
            // Check trial balance
            $trialBalance = $calculator->getTrialBalance($currentPeriodId);
            $totalDebit = $trialBalance['total_debit'] ?? 0;
            $totalCredit = $trialBalance['total_credit'] ?? 0;
            $trialBalanced = abs($totalDebit - $totalCredit) < 0.01;
            $totalAssets = $cashBalance + $loansBalance;
            $memberEquity = $savingsBalance + $sharesBalance;
            ?>

<div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-lg p-6 mb-8 text-white">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-bold flex items-center">
                <i class="fas fa-balance-scale mr-2"></i>
                Accounting System - Real-time Financial Data
            </h3>
            <p class="text-sm opacity-90 mt-1">Period: <?= htmlspecialchars($currentPeriod['PayrollPeriod']) ?></p>
        </div>
        <div>
            <div class="inline-flex items-center px-4 py-2 rounded-lg <?= $trialBalanced ? 'bg-green-500' : 'bg-yellow-500' ?> shadow-md">
                <i class="fas fa-<?= $trialBalanced ? 'check-circle' : 'exclamation-triangle' ?> mr-2"></i>
                <span class="font-bold"><?= $trialBalanced ? 'Trial Balance: Balanced' : 'Trial Balance: Review Needed' ?></span>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Cash & Bank Widget -->
        <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 hover:bg-white/30 transition-all">
            <div class="flex items-center mb-2">
                <i class="fas fa-university text-2xl mr-2"></i>
                <h4 class="font-semibold text-sm">Cash & Bank</h4>
            </div>
            <p class="text-2xl font-bold">₦<?= number_format($cashBalance, 2) ?></p>
            <p class="text-xs opacity-75 mt-1">Main Account Balance</p>
        </div>
        
        <!-- Member Loans Widget -->
        <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 hover:bg-white/30 transition-all">
            <div class="flex items-center mb-2">
                <i class="fas fa-hand-holding-usd text-2xl mr-2"></i>
                <h4 class="font-semibold text-sm">Member Loans</h4>
            </div>
            <p class="text-2xl font-bold">₦<?= number_format($loansBalance, 2) ?></p>
            <p class="text-xs opacity-75 mt-1">Outstanding Loans</p>
        </div>
        
        <!-- Member Savings Widget -->
        <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 hover:bg-white/30 transition-all">
            <div class="flex items-center mb-2">
                <i class="fas fa-piggy-bank text-2xl mr-2"></i>
                <h4 class="font-semibold text-sm">Member Savings</h4>
            </div>
            <p class="text-2xl font-bold">₦<?= number_format($savingsBalance, 2) ?></p>
            <p class="text-xs opacity-75 mt-1">Ordinary Savings</p>
        </div>
        
        <!-- Member Shares Widget -->
        <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 hover:bg-white/30 transition-all">
            <div class="flex items-center mb-2">
                <i class="fas fa-certificate text-2xl mr-2"></i>
                <h4 class="font-semibold text-sm">Member Shares</h4>
            </div>
            <p class="text-2xl font-bold">₦<?= number_format($sharesBalance, 2) ?></p>
            <p class="text-xs opacity-75 mt-1">Share Capital</p>
        </div>
    </div>
    
    <!-- Additional Summary Row -->
    <div class="mt-4 grid grid-cols-3 gap-4 pt-4 border-t border-white/20">
        <div class="text-center">
            <p class="text-xs opacity-75">Total Assets</p>
            <p class="text-lg font-bold">₦<?= number_format($totalAssets, 2) ?></p>
        </div>
        <div class="text-center">
            <p class="text-xs opacity-75">Member Equity</p>
            <p class="text-lg font-bold">₦<?= number_format($memberEquity, 2) ?></p>
        </div>
        <div class="text-center">
            <a href="coop_trial_balance.php" class="text-sm font-semibold hover:underline opacity-90 hover:opacity-100">
                View Full Trial Balance →
            </a>
        </div>
    </div>
</div>

<?php
        } catch (Exception $e) {
            error_log("Failed to fetch accounting widgets data: " . $e->getMessage());
        }
    }
}
?>

<!-- Main Navigation Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
    <!-- Process Loan -->
    <?php if (($userRole == 'Admin') || ($userRole == 'user')): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-shopping-cart text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Process Loan</h3>
        </div>
        <p class="text-gray-600 mb-4">Manage loan applications and processing.</p>
        <a href="loan-processor.php"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Process Loan
        </a>
    </div>
    <?php endif; ?>

    <!-- Settings (Admin Only) -->
    <?php if ($userRole == 'Admin'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <i class="fas fa-cog text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Settings</h3>
        </div>
        <p class="text-gray-600 mb-4">System configuration and settings.</p>
        <a href="#" id="link_deletetransaction"
            class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            View Settings
        </a>
    </div>
    <?php endif; ?>

    <!-- Enquiry (Admin Only) -->
    <?php if ($userRole == 'Admin'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-search text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Enquiry</h3>
        </div>
        <p class="text-gray-600 mb-4">Search and query system data.</p>
        <a href="enquiry.php"
            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Search Data
        </a>
    </div>
    <?php endif; ?>

    <!-- Reports -->
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                <i class="fas fa-chart-bar text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Reports</h3>
        </div>
        <p class="text-gray-600 mb-4">Generate and view system reports.</p>
        <a href="masterReportModern.php"
            class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            View Reports
        </a>
    </div>

    <!-- Commodity (Admin Only) -->
    <?php if ($userRole == 'Admin'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                <i class="fas fa-exchange-alt text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Commodity</h3>
        </div>
        <p class="text-gray-600 mb-4">Manage commodity transactions.</p>
        <a href="procesCommodity.php"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Process Commodities
        </a>
    </div>
    <?php endif; ?>

    <!-- Periods (Admin Only) -->
    <?php if ($userRole == 'Admin'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-pink-100 text-pink-600">
                <i class="fas fa-table text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Periods</h3>
        </div>
        <p class="text-gray-600 mb-4">Manage payroll periods.</p>
        <a href="payperiods.php"
            class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Manage Periods
        </a>
    </div>
    <?php endif; ?>

    <!-- Users (Admin Only) -->
    <?php if ($userRole == 'Admin'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-teal-100 text-teal-600">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Users</h3>
        </div>
        <p class="text-gray-600 mb-4">Manage system users and permissions.</p>
        <a href="users.php"
            class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Manage Users
        </a>
    </div>
    <?php endif; ?>

    <!-- Records (Admin Only) -->
    <?php if ($userRole == 'Admin'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-cyan-100 text-cyan-600">
                <i class="fas fa-user text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Records</h3>
        </div>
        <p class="text-gray-600 mb-4">Manage employee records.</p>
        <a href="employee.php"
            class="inline-flex items-center px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Manage Records
        </a>
    </div>
    <?php endif; ?>

    <!-- Update Deductions (Admin Only) -->
    <?php if ($userRole == 'Admin'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-red-100 text-red-600">
                <i class="fas fa-edit text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Update Deductions</h3>
        </div>
        <p class="text-gray-600 mb-4">Modify deduction settings.</p>
        <a href="update_deduction.php"
            class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Update Deductions
        </a>
    </div>
    <?php endif; ?>

    <!-- Print List (Admin Only) -->
    <?php if ($userRole == 'Admin'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-gray-100 text-gray-600">
                <i class="fas fa-print text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Print List</h3>
        </div>
        <p class="text-gray-600 mb-4">Generate printable member lists.</p>
        <a href="print_member.php"
            class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Print List
        </a>
    </div>
    <?php endif; ?>

    <!-- API Upload (Admin Only) -->
    <?php if ($userRole == 'Admin'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <i class="fas fa-cloud-download-alt text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">File Upload</h3>
        </div>
        <p class="text-gray-600 mb-4">Upload and import data files.</p>
        <a href="api_upload.php"
            class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Upload Files
        </a>
    </div>
    <?php endif; ?>

    <!-- Excel Upload (Admin Only) - Legacy -->
    <?php if ($userRole == 'Admin'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-emerald-100 text-emerald-600">
                <i class="fas fa-file-excel text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Excel Upload</h3>
        </div>
        <p class="text-gray-600 mb-4">Legacy Excel file upload (Old Method).</p>
        <a href="upload.php"
            class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Excel Upload
        </a>
    </div>
    <?php endif; ?>

    <!-- ==================== ACCOUNTING MODULE SECTION ==================== -->
    
    <!-- Chart of Accounts (Admin & Accountant) -->
    <?php if ($userRole == 'Admin' || $userRole == 'Accountant'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                <i class="fas fa-list-alt text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Chart of Accounts</h3>
        </div>
        <p class="text-gray-600 mb-4">View and manage account structure.</p>
        <a href="coop_chart_of_accounts.php"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            View Accounts
        </a>
    </div>
    <?php endif; ?>

    <!-- Journal Entries (Admin & Accountant) -->
    <?php if ($userRole == 'Admin' || $userRole == 'Accountant'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-violet-100 text-violet-600">
                <i class="fas fa-book text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Journal Entries</h3>
        </div>
        <p class="text-gray-600 mb-4">Create and manage journal entries.</p>
        <div class="flex gap-2">
            <a href="coop_journal_entry_form.php"
                class="inline-flex items-center px-3 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition-colors text-sm">
                <i class="fas fa-plus mr-2"></i>
                New Entry
            </a>
            <a href="coop_journal_entries.php"
                class="inline-flex items-center px-3 py-2 bg-violet-500 text-white rounded-lg hover:bg-violet-600 transition-colors text-sm">
                <i class="fas fa-list mr-2"></i>
                View All
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Trial Balance (Admin & Accountant) -->
    <?php if ($userRole == 'Admin' || $userRole == 'Accountant'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-pink-100 text-pink-600">
                <i class="fas fa-balance-scale text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Trial Balance</h3>
        </div>
        <p class="text-gray-600 mb-4">View accounting trial balance.</p>
        <a href="coop_trial_balance.php"
            class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            View Trial Balance
        </a>
    </div>
    <?php endif; ?>

    <!-- Financial Statements (Admin & Accountant) -->
    <?php if ($userRole == 'Admin' || $userRole == 'Accountant'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-rose-100 text-rose-600">
                <i class="fas fa-file-invoice-dollar text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Financial Statements</h3>
        </div>
        <p class="text-gray-600 mb-4">Income, Balance Sheet, Cashflow reports.</p>
        <a href="coop_financial_statements.php"
            class="inline-flex items-center px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            View Statements
        </a>
    </div>
    <?php endif; ?>

    <!-- General Ledger (Admin & Accountant) -->
    <?php if ($userRole == 'Admin' || $userRole == 'Accountant'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-fuchsia-100 text-fuchsia-600">
                <i class="fas fa-book-open text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">General Ledger</h3>
        </div>
        <p class="text-gray-600 mb-4">View account-wise ledger entries.</p>
        <a href="coop_general_ledger.php"
            class="inline-flex items-center px-4 py-2 bg-fuchsia-600 text-white rounded-lg hover:bg-fuchsia-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            View Ledger
        </a>
    </div>
    <?php endif; ?>

    <!-- Member Statement (Admin & Accountant) -->
    <?php if ($userRole == 'Admin' || $userRole == 'Accountant'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-sky-100 text-sky-600">
                <i class="fas fa-user-circle text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Member Statement</h3>
        </div>
        <p class="text-gray-600 mb-4">View member account statements.</p>
        <a href="coop_member_statement.php"
            class="inline-flex items-center px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Member Statements
        </a>
    </div>
    <?php endif; ?>

    <!-- Period Closing (Admin Only) -->
    <?php if ($userRole == 'Admin'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-amber-100 text-amber-600">
                <i class="fas fa-calendar-check text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Period Closing</h3>
        </div>
        <p class="text-gray-600 mb-4">Close accounting periods.</p>
        <a href="coop_period_closing.php"
            class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Close Period
        </a>
    </div>
    <?php endif; ?>

    <!-- Bank Reconciliation (Admin & Accountant) -->
    <?php if ($userRole == 'Admin' || $userRole == 'Accountant'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-lime-100 text-lime-600">
                <i class="fas fa-building text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Bank Reconciliation</h3>
        </div>
        <p class="text-gray-600 mb-4">Reconcile bank statements.</p>
        <a href="coop_bank_reconciliation.php"
            class="inline-flex items-center px-4 py-2 bg-lime-600 text-white rounded-lg hover:bg-lime-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Reconcile Bank
        </a>
    </div>
    <?php endif; ?>

    <!-- Comparative Reports (Admin & Accountant) -->
    <?php if ($userRole == 'Admin' || $userRole == 'Accountant'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                <i class="fas fa-chart-line text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Comparative Reports</h3>
        </div>
        <p class="text-gray-600 mb-4">Compare financial periods.</p>
        <a href="coop_comparative_reports.php"
            class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            View Reports
        </a>
    </div>
    <?php endif; ?>

    <!-- Process Deduction (Admin Only) -->
    <?php if ($userRole == 'Admin'): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-emerald-100 text-emerald-600">
                <i class="fas fa-cogs text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Process Deduction</h3>
        </div>
        <p class="text-gray-600 mb-4">Process payroll deductions.</p>
        <a href="payprocess.php"
            class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Process Deductions
        </a>
    </div>
    <?php endif; ?>
</div>


<!-- Settings Modal -->
<div id="deletetransaction" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-6 rounded-t-xl">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold">Cooperative Settings</h3>
                <button id="closeSettingsModal" class="text-white hover:text-gray-200 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <div class="p-6">
            <div class="space-y-6">
                <!-- Interest Rate -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-percentage text-blue-600 mr-3"></i>
                        <span class="font-semibold text-gray-700">Interest Rate</span>
                    </div>
                    <div class="text-right">
                        <span class="text-2xl font-bold text-blue-600">
                            <?php echo retrieveSettings('tbl_globa_settings', 'value', 5, 'setting_id'); ?>%
                        </span>
                    </div>
                </div>

                <!-- Development Levy -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-building text-green-600 mr-3"></i>
                        <span class="font-semibold text-gray-700">Development Levy</span>
                    </div>
                    <div class="text-right">
                        <span class="text-2xl font-bold text-green-600">
                            ₦<?php echo retrieveSettings('tbl_globa_settings', 'value', 8, 'setting_id'); ?>
                        </span>
                    </div>
                </div>

                <!-- Stationery -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-file-alt text-purple-600 mr-3"></i>
                        <span class="font-semibold text-gray-700">Stationery</span>
                    </div>
                    <div class="text-right">
                        <span class="text-2xl font-bold text-purple-600">
                            ₦<?php echo retrieveSettings('tbl_globa_settings', 'value', 9, 'setting_id'); ?>
                        </span>
                    </div>
                </div>

                <!-- Savings Percentage -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-piggy-bank text-yellow-600 mr-3"></i>
                        <span class="font-semibold text-gray-700">Savings %</span>
                    </div>
                    <div class="text-right">
                        <span class="text-2xl font-bold text-yellow-600">
                            <?php echo retrieveSettings('tbl_globa_settings', 'value', 4, 'setting_id'); ?>%
                        </span>
                    </div>
                </div>

                <!-- Shares Percentage -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-chart-pie text-indigo-600 mr-3"></i>
                        <span class="font-semibold text-gray-700">Shares %</span>
                    </div>
                    <div class="text-right">
                        <span class="text-2xl font-bold text-indigo-600">
                            <?php echo retrieveSettings('tbl_globa_settings', 'value', 3, 'setting_id'); ?>%
                        </span>
                    </div>
                </div>

                <!-- Registration Fees -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-id-card text-red-600 mr-3"></i>
                        <span class="font-semibold text-gray-700">Registration Fees</span>
                    </div>
                    <div class="text-right">
                        <span class="text-2xl font-bold text-red-600">
                            ₦<?php echo retrieveSettings('tbl_globa_settings', 'value', 10, 'setting_id'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 rounded-b-xl">
            <div class="flex justify-end space-x-3">
                <button id="closeSettingsModalBtn"
                    class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                    <i class="fas fa-times mr-2"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="bg-white rounded-lg shadow-lg p-6">
    <h3 class="text-xl font-bold text-gray-900 mb-4">Recent Activity</h3>
    <div class="space-y-4">
        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
            <div class="p-2 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-check text-sm"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">System Updated</p>
                <p class="text-xs text-gray-500">Last updated: <?= date('M j, Y \a\t g:i A') ?></p>
            </div>
        </div>
        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
            <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-user-plus text-sm"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">New Member Registered</p>
                <p class="text-xs text-gray-500">5 new members this week</p>
            </div>
        </div>
        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
            <div class="p-2 rounded-full bg-yellow-100 text-yellow-600">
                <i class="fas fa-chart-line text-sm"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">Monthly Report Generated</p>
                <p class="text-xs text-gray-500">Report for <?= date('F Y') ?> is ready</p>
            </div>
        </div>
    </div>
</div>

<script>
class DashboardManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Settings modal
        $('#link_deletetransaction').on('click', (e) => {
            e.preventDefault();
            this.showSettingsModal();
        });

        // Close modal buttons
        $('#closeSettingsModal, #closeSettingsModalBtn').on('click', () => {
            this.hideSettingsModal();
        });

        // Close modal on backdrop click
        $('#deletetransaction').on('click', (e) => {
            if (e.target === e.currentTarget) {
                this.hideSettingsModal();
            }
        });

        // Close modal on escape key
        $(document).on('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideSettingsModal();
            }
        });
    }

    showSettingsModal() {
        $('#deletetransaction').removeClass('hidden').addClass('fade-in');
        $('body').addClass('overflow-hidden');
    }

    hideSettingsModal() {
        $('#deletetransaction').addClass('hidden').removeClass('fade-in');
        $('body').removeClass('overflow-hidden');
    }
}

// Initialize when document is ready
$(document).ready(() => {
    new DashboardManager();
});
</script>

<?php include 'includes/footer.php'; ?>