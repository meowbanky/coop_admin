<?php include 'includes/header.php'; ?>

<!-- Custom CSS for Loan Processor -->
<style>
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
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}

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

.ui-autocomplete {
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
}
</style>

<!-- Main Content -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Page Header -->
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900 flex items-center">
            <i class="fas fa-edit text-primary mr-3"></i>
            Update/View Loan
        </h2>
        <p class="text-gray-600 mt-2">Process and manage employee loans efficiently</p>
    </div>

    <!-- Search and Period Selection -->
    <div class="bg-white rounded-xl shadow-lg mb-8 fade-in">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-search text-primary mr-2"></i>
                Employee Search & Period Selection
            </h3>
        </div>
        <div class="p-6">
            <form id="search-form" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="employee-search" class="block text-sm font-medium text-gray-700 mb-2">
                            Search Employee
                        </label>
                        <div class="relative">
                            <input type="text" id="employee-search" name="employee"
                                class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                placeholder="Enter staff name or staff number" autocomplete="off" />
                            <div id="search-loader" class="absolute right-3 top-3 hidden">
                                <i class="fas fa-spinner fa-spin text-gray-400"></i>
                            </div>
                            <button type="button" id="clear-search-btn"
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 transition-colors hidden"
                                title="Clear search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="payroll-period" class="block text-sm font-medium text-gray-700 mb-2">
                            Select Payroll Period
                        </label>
                        <select id="payroll-period" name="period"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                            <option value="">Select Period</option>
                            <?php foreach ($payrollPeriods as $period): ?>
                            <option value="<?= $period['id'] ?>">
                                <?= htmlspecialchars($period['payroll_period']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="batch-number" class="block text-sm font-medium text-gray-700 mb-2">
                            Batch Number
                        </label>
                        <input type="text" id="batch-number" name="batch"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                            placeholder="Enter batch number"
                            value="<?= htmlspecialchars($_SESSION['Batch'] ?? '') ?>" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Employee Details Card -->
    <div id="employee-details-card" class="bg-white rounded-xl shadow-lg mb-8 fade-in hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-user text-primary mr-2"></i>
                Employee Information
            </h3>
        </div>
        <div class="p-6">
            <div id="employee-info" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Employee details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Loan Calculation Card -->
    <div id="loan-calculation-card" class="bg-white rounded-xl shadow-lg mb-8 fade-in hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-calculator text-primary mr-2"></i>
                Loan Calculation
            </h3>
        </div>
        <div class="p-6">
            <div id="loan-calculation" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Loan calculation will be loaded here -->
            </div>

            <!-- Loan Amount Input -->
            <div class="mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="loan-amount" class="block text-sm font-medium text-gray-700 mb-2">
                            Loan Amount (₦)
                        </label>
                        <input type="number" id="loan-amount" name="loan_amount"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                            placeholder="Enter loan amount (max: ₦402,060)" min="1000" max="10000000" step="100" />
                    </div>
                    <div class="flex items-end">
                        <button id="update-loan-btn"
                            class="w-full bg-success hover:bg-green-600 text-white font-medium py-3 px-6 rounded-lg transition-all flex items-center justify-center">
                            <i class="fas fa-save mr-2"></i>
                            Update Loan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Period Loans Card -->
    <div id="current-period-loans-card" class="bg-white rounded-xl shadow-lg mb-8 fade-in hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-file-invoice text-primary mr-2"></i>
                Current Period Loans
            </h3>
        </div>
        <div class="p-6">
            <div id="current-period-loans-content">
                <!-- Current period loans will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Loan List Card -->
    <div id="loan-list-card" class="bg-white rounded-xl shadow-lg fade-in hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-list text-primary mr-2"></i>
                    Loan History
                </h3>
                <button id="view-loan-list-btn"
                    class="bg-info hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-all flex items-center">
                    <i class="fas fa-eye mr-2"></i>
                    View All Loans
                </button>
            </div>
        </div>
        <div class="p-6">
            <div id="loan-list-content">
                <!-- Loan list will be loaded here -->
            </div>
        </div>
    </div>
</main>



<?php include 'includes/footer.php'; ?>

<!-- Loading Overlay -->
<div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
        <div class="loading-spinner w-6 h-6 border-2 border-primary border-t-transparent rounded-full"></div>
        <span class="text-gray-700">Processing...</span>
    </div>
</div>

<!-- Loan List Modal -->
<div id="loan-list-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] flex flex-col">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-list text-primary mr-2"></i>
                        Loan History
                    </h3>
                    <button id="close-loan-modal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-4 flex-1 overflow-y-auto">
                <div id="modal-loan-list">
                    <!-- Loan list will be loaded here -->
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                <button id="close-modal-btn"
                    class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="js/loan-processor.js"></script>
</body>