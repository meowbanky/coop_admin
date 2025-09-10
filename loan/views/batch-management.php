<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Batch Management System</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom CSS -->
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',
                        secondary: '#1E40AF',
                        accent: '#F59E0B',
                        success: '#10B981',
                        danger: '#EF4444',
                        warning: '#F59E0B',
                        info: '#06B6D4'
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Navigation Header -->
    <nav class="gradient-bg shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-coins text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-white text-xl font-bold">Loan Batch Management</h1>
                        <p class="text-blue-100 text-sm">Modern Financial System</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="beneficiary.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition-all">
                        <i class="fas fa-users mr-2"></i>Beneficiaries
                    </a>
                    <a href="member-account-update.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition-all">
                        <i class="fas fa-user-edit mr-2"></i>Update Member Accounts
                    </a>
                    <div class="text-white text-sm">
                        <i class="fas fa-user-circle mr-2"></i>
                        Welcome, <?= htmlspecialchars($_SESSION['complete_name'] ?? 'Admin') ?>
                    </div>
                    <a href="logout.php" 
                       class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-all"
                       onclick="return confirm('Are you sure you want to logout?')">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Login Success Message -->
        <?php if (isset($login_success) && $login_success): ?>
            <div id="login-success-alert" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span>Welcome back, <?= htmlspecialchars($_SESSION['complete_name'] ?? 'Admin') ?>! You have successfully logged in.</span>
                </div>
                <button onclick="document.getElementById('login-success-alert').style.display='none'" class="text-green-500 hover:text-green-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Auto-hide login success message -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const loginSuccessAlert = document.getElementById('login-success-alert');
                if (loginSuccessAlert) {
                    // Auto-hide after 5 seconds
                    setTimeout(function() {
                        loginSuccessAlert.style.transition = 'opacity 0.5s ease';
                        loginSuccessAlert.style.opacity = '0';
                        setTimeout(function() {
                            loginSuccessAlert.style.display = 'none';
                        }, 500);
                    }, 5000);
                }
            });
        </script>
        
        <!-- Success/Error Messages -->
        <?php 
        $sessionMessage = $responseHandler->getSessionMessage();
        if ($sessionMessage): 
        ?>
        <div class="mb-6 p-4 rounded-lg <?= $sessionMessage['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700' ?> fade-in">
            <div class="flex items-center">
                <i class="fas fa-<?= $sessionMessage['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> mr-2"></i>
                <span><?= htmlspecialchars($sessionMessage['text']) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-layer-group text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Batches</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $totalBatches ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-exchange-alt text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Transactions</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?= array_sum(array_column($batches, 'transaction_count')) ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Active Batches</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?= count(array_filter($batches, function($batch) { return $batch['transaction_count'] > 0; })) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Batch Creation Form -->
        <div class="bg-white rounded-xl shadow-lg mb-8 fade-in">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-plus-circle text-primary mr-3"></i>
                    Create New Batch
                </h2>
            </div>
            <div class="p-6">
                <form id="batch-form" method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="create_batch">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="batch-number" class="block text-sm font-medium text-gray-700 mb-2">
                                Batch Number
                            </label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    id="batch-number" 
                                    name="batch" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                    placeholder="Enter batch number or generate one"
                                    required
                                />
                                <button 
                                    type="button" 
                                    id="generate-batch" 
                                    class="absolute right-2 top-2 px-4 py-2 bg-primary text-white rounded-md hover:bg-secondary transition-all text-sm"
                                >
                                    <i class="fas fa-sync-alt mr-1"></i>
                                    Generate
                                </button>
                            </div>
                        </div>
                        
                        <div class="flex items-end">
                            <button 
                                type="submit" 
                                class="w-full bg-success hover:bg-green-600 text-white font-medium py-3 px-6 rounded-lg transition-all flex items-center justify-center"
                            >
                                <i class="fas fa-save mr-2"></i>
                                Create Batch
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Batches Table -->
        <div class="bg-white rounded-xl shadow-lg fade-in">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-list text-primary mr-3"></i>
                        Batch Management
                    </h2>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <input 
                                type="text" 
                                id="search-batches" 
                                placeholder="Search batches..." 
                                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                            />
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <select id="items-per-page" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="10">10 per page</option>
                            <option value="25">25 per page</option>
                            <option value="50">50 per page</option>
                            <option value="100">100 per page</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table id="batches-table" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Batch ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Batch Number
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Transactions
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($batches)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <i class="fas fa-inbox text-4xl mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No batches found</h3>
                                    <p class="text-gray-500">Create your first batch to get started.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($batches as $batch): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #<?= htmlspecialchars($batch['batch_id']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($batch['batch_number']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?= $batch['transaction_count'] ?> transactions
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($batch['transaction_count'] > 0): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <i class="fas fa-clock mr-1"></i>
                                            Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="beneficiary.php?Session_batch=<?= urlencode($batch['batch_number']) ?>&Batchid_session=<?= $batch['batch_id'] ?>" 
                                           class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-primary hover:bg-secondary transition-all">
                                            <i class="fas fa-plus mr-1"></i>
                                            Add Payment
                                        </a>
                                        <a href="sendsms.php?batch=<?= urlencode($batch['batch_number']) ?>" 
                                           onclick="return confirm('Are you sure you want to send SMS to this batch?')"
                                           class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-info hover:bg-blue-600 transition-all">
                                            <i class="fas fa-sms mr-1"></i>
                                            Send SMS
                                        </a>
                                        <button onclick="postAccount('<?= htmlspecialchars($batch['batch_number']) ?>'); console.log('Button clicked for batch: <?= htmlspecialchars($batch['batch_number']) ?>');" 
                                                class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-warning hover:bg-yellow-600 transition-all">
                                            <i class="fas fa-upload mr-1"></i>
                                            Post Account
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalBatches > 0): ?>
            <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-t border-gray-200">
                <div class="flex items-center">
                    <p id="page-info" class="text-sm text-gray-700">
                        Showing results...
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <div id="pagination-controls" class="flex items-center space-x-1">
                        <!-- Pagination buttons will be inserted here by JavaScript -->
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    © 2024 Loan Management System. All rights reserved.
                </div>
                <div class="flex space-x-4">
                    <a href="#" class="text-sm text-gray-500 hover:text-gray-700">Help</a>
                    <a href="#" class="text-sm text-gray-500 hover:text-gray-700">Support</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <div class="loading-spinner w-6 h-6 border-2 border-primary border-t-transparent rounded-full"></div>
            <span class="text-gray-700">Processing...</span>
        </div>
    </div>

    <!-- JavaScript -->
    <!-- Loan Insertion Modal -->
    <div id="loan-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] flex flex-col">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-plus-circle text-green-600 mr-2"></i>
                            Add New Loan
                        </h3>
                        <button id="close-loan-modal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 flex-1 overflow-y-auto">
                    <!-- Batch Info Section -->
                    <div id="batch-info" class="mb-6 bg-blue-50 p-4 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                            <div>
                                <h4 class="font-semibold text-blue-800">Batch Information</h4>
                                <p class="text-sm text-blue-600">Loading batch details...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Payroll Period Selection -->
                    <div class="mb-6">
                        <label for="payroll-period" class="block text-sm font-medium text-gray-700 mb-2">
                            Select Payroll Period <span class="text-red-500">*</span>
                        </label>
                        <select id="payroll-period" name="payroll_period_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                required>
                            <option value="">Select Payroll Period</option>
                            <!-- Periods will be loaded dynamically -->
                        </select>
                    </div>

                    <!-- Beneficiaries Table -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Batch Beneficiaries</h3>
                            <div class="flex items-center">
                                <input type="checkbox" id="select-all-beneficiaries" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" checked>
                                <label for="select-all-beneficiaries" class="ml-2 text-sm text-gray-700">
                                    Select All
                                </label>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                                            <input type="checkbox" id="select-all-beneficiaries-header" 
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" checked>
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coop ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bank</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="beneficiaries-table-body" class="bg-white divide-y divide-gray-200">
                                    <!-- Beneficiaries will be loaded here -->
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                            <i class="fas fa-spinner fa-spin mr-2"></i>
                                            Loading beneficiaries...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button id="cancel-loan" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button id="save-loan" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Save Loan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/batch-management.js"></script>
</body>
</html>
