<?php
// Include session helper
require_once(__DIR__ . '/session_helper.php');

// Start session if not already started
initSession();

// Check authentication
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    header("location: index.php");
    exit();
}

// Get user info
$userName = $_SESSION['SESS_FIRST_NAME'] ?? 'User';
$userRole = $_SESSION['role'] ?? 'User';
$currentPage = $_SERVER["PHP_SELF"];
$pageTitle = $pageTitle ?? 'OOUTH COOP';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
    <link rel="manifest" href="../site.webmanifest">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jQuery UI for autocomplete -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

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

    .gradient-bg {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .card-hover {
        transition: all 0.3s ease;
    }

    .card-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .loading-spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3498db;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* Autocomplete dropdown styling */
    .ui-autocomplete {
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        background: white;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .ui-menu-item {
        padding: 8px 12px;
        border-bottom: 1px solid #f3f4f6;
        cursor: pointer;
    }

    .ui-menu-item:hover {
        background-color: #f3f4f6;
    }

    .ui-menu-item:last-child {
        border-bottom: none;
    }

    /* Sidebar styles */
    .sidebar {
        transition: transform 0.3s ease-in-out;
    }

    .sidebar-overlay {
        transition: opacity 0.3s ease-in-out;
    }

    @media (max-width: 1024px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.open {
            transform: translateX(0);
        }
    }

    .menu-item {
        transition: all 0.2s ease;
    }

    .menu-item:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateX(5px);
    }

    .menu-item.active {
        background: rgba(255, 255, 255, 0.15);
        border-left: 4px solid white;
    }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Sidebar -->
    <aside id="sidebar"
        class="sidebar fixed top-0 left-0 h-screen w-64 bg-gradient-to-b from-purple-800 to-indigo-900 text-white shadow-2xl z-50 overflow-y-auto">
        <div class="p-4">
            <!-- Logo -->
            <div class="flex items-center space-x-3 mb-8 pb-4 border-b border-white/20">
                <div class="bg-white/20 p-2 rounded-lg">
                    <i class="fas fa-university text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-white text-lg font-bold">OOUTH COOP</h1>
                    <p class="text-white/80 text-xs">Management System</p>
                </div>
            </div>

            <!-- Menu Items -->
            <nav class="space-y-1">
                <!-- Dashboard -->
                <a href="home.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'home.php' ? 'active' : '' ?>">
                    <i class="fas fa-home w-5"></i>
                    <span>Dashboard</span>
                </a>

                <?php if (($userRole == 'Admin') || ($userRole == 'user')): ?>
                <!-- Process Loan -->
                <a href="loan-processor.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'loan-processor.php' ? 'active' : '' ?>">
                    <i class="fas fa-shopping-cart w-5"></i>
                    <span>Process Loan</span>
                </a>
                <?php endif; ?>

                <?php if ($userRole == 'Admin'): ?>
                <!-- Loan Request Admin -->
                <a href="loan-request-admin.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'loan-request-admin.php' ? 'active' : '' ?>">
                    <i class="fas fa-money-bill-wave w-5"></i>
                    <span>Loan Request Admin</span>
                </a>

                <!-- Event Management -->
                <a href="event-management.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'event-management.php' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt w-5"></i>
                    <span>Event Management</span>
                </a>

                <!-- Reports -->
                <a href="masterReportModern.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'masterReportModern.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span>Reports</span>
                </a>

                <!-- Enquiry -->
                <a href="enquiry.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'enquiry.php' ? 'active' : '' ?>">
                    <i class="fas fa-search w-5"></i>
                    <span>Enquiry</span>
                </a>

                <!-- Commodity -->
                <a href="procesCommodity.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'procesCommodity.php' ? 'active' : '' ?>">
                    <i class="fas fa-exchange-alt w-5"></i>
                    <span>Commodity</span>
                </a>

                <!-- Periods -->
                <a href="payperiods.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'payperiods.php' ? 'active' : '' ?>">
                    <i class="fas fa-table w-5"></i>
                    <span>Periods</span>
                </a>

                <!-- Process Deduction -->
                <a href="payprocess.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'payprocess.php' ? 'active' : '' ?>">
                    <i class="fas fa-cogs w-5"></i>
                    <span>Process Deduction</span>
                </a>

                <!-- Users -->
                <a href="users.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'users.php' ? 'active' : '' ?>">
                    <i class="fas fa-users w-5"></i>
                    <span>Users</span>
                </a>

                <!-- Records -->
                <a href="employee.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'employee.php' ? 'active' : '' ?>">
                    <i class="fas fa-user w-5"></i>
                    <span>Records</span>
                </a>

                <!-- Update Deductions -->
                <a href="update_deduction.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'update_deduction.php' ? 'active' : '' ?>">
                    <i class="fas fa-edit w-5"></i>
                    <span>Update Deductions</span>
                </a>

                <!-- Print List -->
                <a href="print_member.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'print_member.php' ? 'active' : '' ?>">
                    <i class="fas fa-print w-5"></i>
                    <span>Print List</span>
                </a>

                <!-- File Upload -->
                <a href="api_upload.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'api_upload.php' ? 'active' : '' ?>">
                    <i class="fas fa-cloud-download-alt w-5"></i>
                    <span>File Upload</span>
                </a>

                <!-- Settings -->
                <a href="#" id="sidebar_settings" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg">
                    <i class="fas fa-cog w-5"></i>
                    <span>Settings</span>
                </a>

                <!-- Accounting Section Divider -->
                <?php 
                if (isset($coop)) {
                    $accountsExist = mysqli_query($coop, "SHOW TABLES LIKE 'coop_accounts'");
                } else {
                    $accountsExist = false;
                }
                if ($accountsExist && mysqli_num_rows($accountsExist) > 0): 
                ?>
                <div class="pt-4 pb-2 px-4">
                    <div
                        class="flex items-center space-x-2 text-white/60 text-xs font-semibold uppercase tracking-wider">
                        <i class="fas fa-calculator"></i>
                        <span>Accounting</span>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (($userRole == 'Admin' || $userRole == 'Accountant') && $accountsExist && mysqli_num_rows($accountsExist) > 0): ?>
                <!-- Chart of Accounts -->
                <a href="coop_chart_of_accounts.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'coop_chart_of_accounts.php' ? 'active' : '' ?>">
                    <i class="fas fa-list-alt w-5"></i>
                    <span>Chart of Accounts</span>
                </a>

                <!-- Journal Entries -->
                <a href="coop_journal_entries.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'coop_journal_entries.php' || basename($currentPage) === 'coop_journal_entry_form.php' ? 'active' : '' ?>">
                    <i class="fas fa-book w-5"></i>
                    <span>Journal Entries</span>
                </a>

                <!-- Trial Balance -->
                <a href="coop_trial_balance.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'coop_trial_balance.php' ? 'active' : '' ?>">
                    <i class="fas fa-balance-scale w-5"></i>
                    <span>Trial Balance</span>
                </a>

                <!-- Financial Statements -->
                <a href="coop_financial_statements.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'coop_financial_statements.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-invoice-dollar w-5"></i>
                    <span>Financial Statements</span>
                </a>

                <!-- General Ledger -->
                <a href="coop_general_ledger.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'coop_general_ledger.php' ? 'active' : '' ?>">
                    <i class="fas fa-book-open w-5"></i>
                    <span>General Ledger</span>
                </a>

                <!-- Member Statement -->
                <a href="coop_member_statement.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'coop_member_statement.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-circle w-5"></i>
                    <span>Member Statement</span>
                </a>

                <?php if ($userRole == 'Admin'): ?>
                <!-- Period Closing -->
                <a href="coop_period_closing.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'coop_period_closing.php' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-check w-5"></i>
                    <span>Period Closing</span>
                </a>
                <?php endif; ?>

                <!-- Bank Reconciliation -->
                <a href="coop_bank_reconciliation.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'coop_bank_reconciliation.php' ? 'active' : '' ?>">
                    <i class="fas fa-building w-5"></i>
                    <span>Bank Reconciliation</span>
                </a>

                <!-- Comparative Reports -->
                <a href="coop_comparative_reports.php"
                    class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($currentPage) === 'coop_comparative_reports.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line w-5"></i>
                    <span>Comparative Reports</span>
                </a>
                <?php endif; ?>
            </nav>
        </div>
    </aside>

    <!-- Sidebar Overlay (for mobile) -->
    <div id="sidebar-overlay" class="sidebar-overlay fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

    <!-- Header -->
    <header class="gradient-bg shadow-lg fixed top-0 left-0 right-0 z-30 lg:left-64">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Mobile Menu Toggle -->
                <button id="menu-toggle"
                    class="lg:hidden text-white hover:bg-white/20 p-2 rounded-lg transition-colors">
                    <i class="fas fa-bars text-xl"></i>
                </button>

                <!-- Page Title -->
                <div class="flex-1 text-center lg:text-left lg:ml-4">
                    <h2 class="text-white text-lg font-semibold"><?= htmlspecialchars($pageTitle) ?></h2>
                </div>

                <!-- User Info and Actions -->
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <!-- User Info -->
                    <div class="hidden sm:block text-white text-sm">
                        <i class="fas fa-user-circle mr-1"></i>
                        <?= htmlspecialchars($userName) ?>
                        <span class="text-white/80 text-xs">(<?= htmlspecialchars($userRole) ?>)</span>
                    </div>

                    <!-- Logout Button -->
                    <a href="logout.php"
                        class="bg-white/20 hover:bg-white/30 text-white px-3 py-2 rounded-lg transition-colors text-sm">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="hidden sm:inline ml-1">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="pt-20 lg:ml-64 px-4 sm:px-6 lg:px-8 py-8">

        <script>
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebar-overlay');

            function openSidebar() {
                sidebar.classList.add('open');
                sidebarOverlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebar.classList.remove('open');
                sidebarOverlay.classList.add('hidden');
                document.body.style.overflow = '';
            }

            menuToggle?.addEventListener('click', function() {
                if (sidebar.classList.contains('open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });

            sidebarOverlay?.addEventListener('click', closeSidebar);

            // Close sidebar on window resize to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) {
                    closeSidebar();
                }
            });

            // Settings modal trigger from sidebar
            document.getElementById('sidebar_settings')?.addEventListener('click', function(e) {
                e.preventDefault();
                closeSidebar();
                document.getElementById('link_deletetransaction')?.click();
            });
        });
        </script>