<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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

    /* Sidebar Styles */
    .sidebar-link {
        position: relative;
        transition: all 0.3s ease;
    }

    .sidebar-link:hover {
        background-color: #eff6ff;
        color: #1d4ed8;
    }

    .sidebar-link.active {
        background-color: #dbeafe;
        color: #1d4ed8;
        border-right: 3px solid #1d4ed8;
    }

    .sidebar-link.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background-color: #1d4ed8;
    }

    /* Responsive sidebar */
    @media (max-width: 768px) {
        .sidebar {
            position: fixed;
            left: -100%;
            top: 0;
            z-index: 1000;
            transition: left 0.3s ease;
        }

        .sidebar.open {
            left: 0;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .sidebar-overlay.show {
            display: block;
        }
    }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo and Title -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-3">
                        <div class="bg-white/20 p-2 rounded-lg">
                            <i class="fas fa-university text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-white text-xl font-bold">OOUTH COOP</h1>
                            <p class="text-white/80 text-sm">Cooperative Management System</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation and User Info -->
                <div class="flex items-center space-x-4">
                    <!-- Mobile Menu Button (if not on home page) -->
                    <?php if (basename($currentPage) !== 'home.php'): ?>
                    <button id="mobile-menu-btn"
                        class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg transition-colors md:hidden">
                        <i class="fas fa-bars mr-2"></i>Menu
                    </button>

                    <!-- Back to Dashboard Button (desktop only) -->
                    <a href="home.php"
                        class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg transition-colors hidden md:inline-flex">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                    <?php endif; ?>

                    <!-- User Info -->
                    <div class="text-white text-sm">
                        <i class="fas fa-user-circle mr-2"></i>
                        Welcome, <?= htmlspecialchars($userName) ?>
                        <span class="text-white/80">(<?= htmlspecialchars($userRole) ?>)</span>
                    </div>

                    <!-- Logout Button -->
                    <a href="logout.php"
                        class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Layout Container -->
    <div class="min-h-screen flex flex-col">
        <!-- Sidebar and Main Content Container -->
        <div class="flex flex-1">
            <?php if (basename($currentPage) !== 'home.php'): ?>
            <!-- Mobile Overlay -->
            <div id="sidebar-overlay" class="sidebar-overlay"></div>

            <!-- Sidebar -->
            <aside id="sidebar" class="sidebar w-64 bg-white shadow-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-bars mr-2"></i>Navigation
                        </h2>
                        <button id="close-sidebar" class="md:hidden text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <nav class="space-y-2">
                        <!-- Dashboard -->
                        <a href="home.php"
                            class="sidebar-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?= basename($currentPage) === 'home.php' ? 'active' : '' ?>">
                            <i class="fas fa-home mr-3"></i>
                            <span>Dashboard</span>
                        </a>

                        <!-- Process Loan -->
                        <?php if (($userRole == 'Admin') || ($userRole == 'user')): ?>
                        <a href="loan-processor.php"
                            class="sidebar-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?= basename($currentPage) === 'loan-processor.php' ? 'active' : '' ?>">
                            <i class="fas fa-shopping-cart mr-3"></i>
                            <span>Process Loan</span>
                        </a>
                        <?php endif; ?>

                        <!-- Reports -->
                        <a href="masterReportModern.php"
                            class="sidebar-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?= basename($currentPage) === 'masterReportModern.php' ? 'active' : '' ?>">
                            <i class="fas fa-chart-bar mr-3"></i>
                            <span>Reports</span>
                        </a>

                        <?php if ($userRole == 'Admin'): ?>
                        <!-- Admin Section -->
                        <div class="mt-6">
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Admin Tools
                            </h3>

                            <a href="enquiry.php"
                                class="sidebar-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?= basename($currentPage) === 'enquiry.php' ? 'active' : '' ?>">
                                <i class="fas fa-search mr-3"></i>
                                <span>Enquiry</span>
                            </a>

                            <a href="procesCommodity.php"
                                class="sidebar-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?= basename($currentPage) === 'procesCommodity.php' ? 'active' : '' ?>">
                                <i class="fas fa-exchange-alt mr-3"></i>
                                <span>Commodity</span>
                            </a>

                            <a href="payperiods.php"
                                class="sidebar-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?= basename($currentPage) === 'payperiods.php' ? 'active' : '' ?>">
                                <i class="fas fa-table mr-3"></i>
                                <span>Periods</span>
                            </a>

                            <a href="users.php"
                                class="sidebar-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?= basename($currentPage) === 'users.php' ? 'active' : '' ?>">
                                <i class="fas fa-users mr-3"></i>
                                <span>Users</span>
                            </a>

                            <a href="employee.php"
                                class="sidebar-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?= basename($currentPage) === 'employee.php' ? 'active' : '' ?>">
                                <i class="fas fa-user mr-3"></i>
                                <span>Records</span>
                            </a>

                            <a href="update_deduction.php"
                                class="sidebar-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?= basename($currentPage) === 'update_deduction.php' ? 'active' : '' ?>">
                                <i class="fas fa-edit mr-3"></i>
                                <span>Update Deductions</span>
                            </a>

                            <a href="print_member.php"
                                class="sidebar-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?= basename($currentPage) === 'print_member.php' ? 'active' : '' ?>">
                                <i class="fas fa-print mr-3"></i>
                                <span>Print List</span>
                            </a>

                            <a href="upload.php"
                                class="sidebar-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?= basename($currentPage) === 'upload.php' ? 'active' : '' ?>">
                                <i class="fas fa-upload mr-3"></i>
                                <span>File Upload</span>
                            </a>

                            <a href="payprocess.php"
                                class="sidebar-link flex items-center px-4 py-3 text-gray-700 rounded-lg <?= basename($currentPage) === 'payprocess.php' ? 'active' : '' ?>">
                                <i class="fas fa-cogs mr-3"></i>
                                <span>Process Deduction</span>
                            </a>
                        </div>
                        <?php endif; ?>
                    </nav>
                </div>
            </aside>
            <?php endif; ?>

            <!-- Main Content Container -->
            <main
                class="<?= basename($currentPage) !== 'home.php' ? 'flex-1' : 'max-w-7xl mx-auto' ?> px-4 sm:px-6 lg:px-8 py-8">

                <script>
                // Sidebar functionality
                $(document).ready(function() {
                    // Mobile menu toggle
                    $('#mobile-menu-btn').on('click', function() {
                        $('#sidebar').addClass('open');
                        $('#sidebar-overlay').addClass('show');
                        $('body').addClass('overflow-hidden');
                    });

                    // Close sidebar
                    $('#close-sidebar, #sidebar-overlay').on('click', function() {
                        $('#sidebar').removeClass('open');
                        $('#sidebar-overlay').removeClass('show');
                        $('body').removeClass('overflow-hidden');
                    });

                    // Close sidebar on escape key
                    $(document).on('keydown', function(e) {
                        if (e.key === 'Escape') {
                            $('#sidebar').removeClass('open');
                            $('#sidebar-overlay').removeClass('show');
                            $('body').removeClass('overflow-hidden');
                        }
                    });

                    // Auto-close sidebar on link click (mobile)
                    $('.sidebar-link').on('click', function() {
                        if ($(window).width() < 768) {
                            $('#sidebar').removeClass('open');
                            $('#sidebar-overlay').removeClass('show');
                            $('body').removeClass('overflow-hidden');
                        }
                    });
                });
                </script>
        </div>
    </div>