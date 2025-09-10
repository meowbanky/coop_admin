<?php
ini_set('max_execution_time', '300');
require_once('Connections/coop.php');
include_once('classes/model.php');

// Set page title
$pageTitle = 'OOUTH COOP - Master Report';

// Include header
include 'includes/header.php';

$today = date('Y-m-d');
?>
<!-- Additional styles for this page -->
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

    .ui-autocomplete {
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
    }

    .ui-menu-item {
        padding: 8px 12px;
        border-bottom: 1px solid #e5e7eb;
    }

    .ui-menu-item:hover {
        background-color: #f3f4f6;
    }

    .ui-state-active {
        background-color: #3b82f6 !important;
        color: white !important;
    }

    .table-container {
        max-height: 70vh;
        overflow-y: auto;
    }

    .sticky-header {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f9fafb;
    }

    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3498db;
        border-radius: 50%;
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

    .dropdown-item.active {
        background-color: #dbeafe;
        border-left: 3px solid #3b82f6;
    }

    .dropdown-item:hover {
        background-color: #f3f4f6;
    }

    /* Loading Modal Animations */
    #loading-modal {
        transition: opacity 0.3s ease-in-out;
    }

    #loading-modal:not(.hidden) {
        animation: fadeIn 0.3s ease-in-out;
    }

    #loading-modal .bg-white {
        animation: slideInUp 0.3s ease-out;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Spinner Animation */
    .animate-spin {
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

    /* Bounce Animation */
    .animate-bounce {
        animation: bounce 1s infinite;
    }

    @keyframes bounce {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Master Report System</h1>
            <p class="text-gray-600 mt-2">Generate comprehensive reports and analytics</p>
        </div>
    </div>
</div>
        <!-- Page Header -->
        <div class="mb-8 fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-chart-line text-blue-600 mr-3"></i>Master Report
                    </h2>
                    <p class="text-gray-600">Comprehensive financial report for all members</p>
                </div>
            </div>
        </div>

        <!-- Breadcrumb -->
        <nav class="mb-6 fade-in">
            <ol class="flex items-center space-x-2 text-sm">
                <li><a href="home.php" class="text-blue-600 hover:text-blue-800"><i
                            class="fas fa-home mr-1"></i>Dashboard</a></li>
                <li><i class="fas fa-chevron-right text-gray-400"></i></li>
                <li class="text-gray-500">Master Report</li>
            </ol>
        </nav>

        <!-- Action Bar -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 fade-in">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center">
                    <i class="fas fa-search text-blue-600 mr-3"></i>
                    <h3 class="text-lg font-semibold text-gray-900">Search & Filter</h3>
                </div>
            </div>

            <form id="master-report-form" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Staff Search -->
                    <div class="relative">
                        <label for="staff-search" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-1"></i>Staff Member
                        </label>
                        <div class="relative">
                            <input type="text" id="staff-search" name="staff_search"
                                class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Enter staff name or ID...">
                            <button type="button" id="clear-staff-search"
                                class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div id="staff-loader" class="hidden mt-2">
                            <div class="loading-spinner"></div>
                            <span class="ml-2 text-sm text-gray-500">Searching...</span>
                        </div>
                    </div>

                    <!-- Period From -->
                    <div>
                        <label for="period-from" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar-alt mr-1"></i>Period From
                        </label>
                        <select id="period-from" name="period_from"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Start Period</option>
                            <?php  
                            $query = $conn->prepare('SELECT * FROM tbpayrollperiods ORDER BY id DESC');
                            $res = $query->execute();
                            $out = $query->fetchAll(PDO::FETCH_ASSOC);
                            while ($row = array_shift($out)) {
                                echo('<option value="' . $row['id'] . '">' . $row['PayrollPeriod'] . '</option>');
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Period To -->
                    <div>
                        <label for="period-to" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar-alt mr-1"></i>Period To
                        </label>
                        <select id="period-to" name="period_to"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select End Period</option>
                            <?php  
                            $query = $conn->prepare('SELECT * FROM tbpayrollperiods ORDER BY id DESC');
                            $res = $query->execute();
                            $out = $query->fetchAll(PDO::FETCH_ASSOC);
                            while ($row = array_shift($out)) {
                                echo('<option value="' . $row['id'] . '">' . $row['PayrollPeriod'] . '</option>');
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Records Per Page -->
                    <div>
                        <label for="records-per-page" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-list mr-1"></i>Records Per Page
                        </label>
                        <select id="records-per-page" name="records_per_page"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="100">100 Records</option>
                            <option value="250">250 Records</option>
                            <option value="500">500 Records</option>
                            <option value="1000">1000 Records</option>
                        </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <div class="flex items-center space-x-3">
                        <button type="button" id="generate-report"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-search mr-2"></i>Generate Report
                        </button>
                        <button type="button" id="clear-filters"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-undo mr-2"></i>Clear Filters
                        </button>
                    </div>

                    <div class="flex items-center space-x-3">
                        <button type="button" id="export-excel"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-file-excel mr-2"></i>Export Excel
                        </button>
                        <button type="button" id="print-report"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-print mr-2"></i>Print
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Selected Staff Info -->
        <div id="staff-info" class="card p-6 mb-6 hidden">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-user-circle mr-2"></i>Selected Staff Information
            </h3>
            <div id="staff-details" class="text-gray-600">
                <!-- Staff details will be loaded here -->
            </div>
        </div>

        <!-- Report Table -->
        <div id="report-container" class="hidden">
            <div class="bg-white rounded-xl shadow-lg fade-in">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-table mr-2"></i>Master Report Data
                    </h3>
                    <div class="flex items-center space-x-3">
                        <span id="total-records" class="text-sm text-gray-500"></span>
                        <button type="button" id="delete-selected"
                            class="btn-secondary px-4 py-2 rounded-lg disabled:opacity-50">
                            <i class="fas fa-trash mr-2"></i>Delete Selected
                        </button>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div id="progress-container" class="hidden mb-4">
                    <div class="bg-gray-200 rounded-full h-2">
                        <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                            style="width: 0%"></div>
                    </div>
                    <div id="progress-text" class="text-sm text-gray-600 mt-2"></div>
                </div>

                <!-- Table Container -->
                <div class="table-container">
                    <table id="master-report-table" class="min-w-full divide-y divide-gray-200">
                        <thead class="sticky-header bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">
                                    <input type="checkbox" id="select-all"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    S/N</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Period</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Coop No</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Share Amt</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Share Bal</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Sav Amt</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Sav Bal</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Interest</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Dev Levy</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Stationery</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Entry Fee</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Loan</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Loan Pay</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Loan Bal</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Commodity</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Comdty Pay</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Comdty Bal</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total</th>
                            </tr>
                        </thead>
                        <tbody id="report-tbody" class="bg-white divide-y divide-gray-200">
                            <!-- Report data will be loaded here -->
                        </tbody>
                        <tfoot id="report-tfoot" class="bg-gray-50">
                            <!-- Summary totals will be loaded here -->
                        </tfoot>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="pagination-container" class="mt-6 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-700">Showing</span>
                        <select id="page-size" class="px-3 py-1 border border-gray-300 rounded-md text-sm">
                            <option value="100">100</option>
                            <option value="250">250</option>
                            <option value="500">500</option>
                            <option value="1000">1000</option>
                        </select>
                        <span class="text-sm text-gray-700">records per page</span>
                    </div>
                    <div id="pagination" class="flex items-center space-x-1">
                        <!-- Pagination buttons will be generated here -->
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Loading Modal -->
    <div id="loading-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-2xl p-8 max-w-sm w-full mx-4">
            <div class="text-center">
                <!-- Animated Spinner -->
                <div class="relative mb-6">
                    <div class="w-16 h-16 border-4 border-blue-200 rounded-full animate-spin border-t-blue-600 mx-auto">
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                    </div>
                </div>

                <!-- Loading Text -->
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Loading Report</h3>
                <p class="text-gray-600 mb-4">Please wait while we fetch the data...</p>

                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                    <div id="modal-progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                        style="width: 0%"></div>
                </div>

                <!-- Loading Dots Animation -->
                <div class="flex justify-center space-x-1">
                    <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                    <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                </div>
            </div>
        </div>
    </div>


    <script>
    class MasterReportManager {
        constructor() {
            this.currentPage = 1;
            this.totalPages = 1;
            this.recordsPerPage = 100;
            this.selectedStaff = null;
            this.reportData = [];
            this.init();
        }

        init() {
            this.bindEvents();
            this.setupAutocomplete();
        }

        bindEvents() {
            // Form submission
            $('#generate-report').click(() => this.generateReport());
            $('#clear-filters').click(() => this.clearFilters());

            // Export and print
            $('#export-excel').click(() => this.exportToExcel());
            $('#print-report').click(() => this.printReport());

            // Table actions
            $('#select-all').change(() => this.toggleSelectAll());
            $('#delete-selected').click(() => this.deleteSelected());

            // Search clear button
            $('#clear-staff-search').click(() => this.clearStaffSearch());
            $('#staff-search').on('input', () => this.toggleClearButton());

            // Pagination
            $(document).on('click', '.page-link', (e) => {
                e.preventDefault();
                const page = $(e.target).data('page');
                if (page) this.loadPage(page);
            });

            $('#page-size').change(() => {
                this.recordsPerPage = parseInt($('#page-size').val());
                this.currentPage = 1; // Reset to page 1 when changing page size
                this.showLoadingModal('pageSize');
                this.generateReport();
            });

            // Prevent modal from closing when clicking outside during loading
            $('#loading-modal').click((e) => {
                if (e.target === e.currentTarget) {
                    // Don't close modal during loading
                    return false;
                }
            });
        }

        setupAutocomplete() {
            // Check if jQuery UI autocomplete is available
            if (typeof $.fn.autocomplete === 'undefined') {
                console.warn('jQuery UI autocomplete not available, using fallback');
                this.setupFallbackAutocomplete();
                return;
            }

            $('#staff-search').autocomplete({
                source: 'searchStaff.php',
                minLength: 1,
                delay: 300,
                select: (event, ui) => {
                    this.selectedStaff = ui.item.value;
                    this.loadStaffInfo(ui.item.value);
                }
            });
        }

        setupFallbackAutocomplete() {
            let searchTimeout;
            const $input = $('#staff-search');
            const $dropdown = $(`
                <div class="absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-lg hidden mt-1 max-h-60 overflow-y-auto" 
                     id="staff-dropdown" style="top: 100%; left: 0; right: 0;">
                </div>
            `);

            // Insert dropdown after input container
            $input.parent().append($dropdown);

            $input.on('input', (e) => {
                const query = e.target.value.trim();

                if (query.length < 1) {
                    $dropdown.hide();
                    return;
                }

                // Show loading state
                $dropdown.html(
                    '<div class="px-4 py-2 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Searching...</div>'
                ).show();

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.searchStaff(query, $dropdown);
                }, 300);
            });

            // Hide dropdown when clicking outside
            $(document).on('click', (e) => {
                if (!$(e.target).closest('#staff-search, #staff-dropdown').length) {
                    $dropdown.hide();
                }
            });

            // Handle keyboard navigation
            $input.on('keydown', (e) => {
                const $items = $dropdown.find('.dropdown-item');
                const $active = $dropdown.find('.dropdown-item.active');

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if ($active.length) {
                        $active.removeClass('active');
                        $active.next().addClass('active');
                    } else {
                        $items.first().addClass('active');
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if ($active.length) {
                        $active.removeClass('active');
                        $active.prev().addClass('active');
                    } else {
                        $items.last().addClass('active');
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if ($active.length) {
                        $active.click();
                    }
                } else if (e.key === 'Escape') {
                    $dropdown.hide();
                }
            });
        }

        searchStaff(query, $dropdown) {
            $.get('searchStaff.php', {
                    term: query
                })
                .done((data) => {
                    if (Array.isArray(data) && data.length > 0) {
                        $dropdown.empty();
                        data.forEach((item, index) => {
                            const $item = $(`
                            <div class="dropdown-item px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 flex items-center justify-between ${index === 0 ? 'active' : ''}" 
                                 data-value="${item.value}">
                                <div>
                                    <div class="font-medium text-gray-900">${item.label}</div>
                                    <div class="text-sm text-gray-500">${item.full_name}</div>
                                </div>
                                <div class="text-xs text-gray-400">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                        `);

                            $item.on('click', () => {
                                $('#staff-search').val(item.value);
                                this.selectedStaff = item.value;
                                this.loadStaffInfo(item.value);
                                $dropdown.hide();
                            });

                            $item.on('mouseenter', () => {
                                $dropdown.find('.dropdown-item').removeClass('active');
                                $item.addClass('active');
                            });

                            $dropdown.append($item);
                        });
                        $dropdown.show();
                    } else {
                        $dropdown.html(
                                '<div class="px-4 py-3 text-gray-500 text-center">No staff found</div>')
                            .show();
                    }
                })
                .fail(() => {
                    $dropdown.html(
                        '<div class="px-4 py-3 text-red-500 text-center">Search failed. Please try again.</div>'
                    ).show();
                });
        }

        loadStaffInfo(staffId) {
            $.post('getNamee.php', {
                    item: staffId
                })
                .done((data) => {
                    $('#staff-details').html(data);
                    $('#staff-info').removeClass('hidden').addClass('fade-in');
                })
                .fail(() => {
                    this.showError('Failed to load staff information');
                });
        }

        generateReport(page = null) {
            const periodFrom = $('#period-from').val();
            const periodTo = $('#period-to').val();
            const staffId = this.selectedStaff || '';

            if (!periodFrom || !periodTo) {
                this.showError('Please select both start and end periods');
                return;
            }

            this.showLoading();
            // Only reset to page 1 if no specific page is provided
            if (page !== null) {
                this.currentPage = page;
            } else if (this.currentPage === 1) {
                // Only reset if we're already on page 1 (new search)
                this.currentPage = 1;
            }

            // Ensure numeric values
            const data = {
                period_from: parseInt(periodFrom),
                period_to: parseInt(periodTo),
                staff_id: staffId,
                records_per_page: parseInt(this.recordsPerPage),
                page: parseInt(this.currentPage)
            };

            console.log('Sending data:', data);
            console.log('Current page:', this.currentPage, 'Page parameter:', page);

            $.ajax({
                url: 'getMasterReportModern.php',
                method: 'POST',
                data: data,
                xhrFields: {
                    onprogress: (e) => {
                        if (e.lengthComputable) {
                            const percentComplete = (e.loaded / e.total) * 100;
                            this.updateProgress(percentComplete);
                            this.updateModalProgress(percentComplete);
                        }
                    }
                },
                success: (response) => {
                    this.hideLoading();
                    this.hideLoadingModal();
                    if (response.success) {
                        this.displayReport(response.data, response.totals, response.grand_totals);
                        this.updatePagination(response.pagination);
                    } else {
                        this.showError(response.message);
                    }
                },
                error: (xhr, status, error) => {
                    this.hideLoading();
                    this.hideLoadingModal();
                    console.error('AJAX Error:', xhr.responseText);
                    this.showError('Failed to generate report: ' + xhr.responseText);
                }
            });
        }

        displayReport(data, totals = null, grandTotals = null) {
            // Store data for export
            this.reportData = data;

            const tbody = $('#report-tbody');
            tbody.empty();

            if (data.length === 0) {
                tbody.html(`
                        <tr>
                            <td colspan="19" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-4"></i>
                                <p>No data found for the selected criteria</p>
                            </td>
                        </tr>
                    `);
                return;
            }

            data.forEach((row, index) => {
                const rowHtml = `
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <input type="checkbox" class="row-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" 
                                       value="${row.coopid},${row.period}">
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">${index + 1}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">${row.period_display}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">${row.coopid}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">₦${this.formatNumber(row.shares_amount)}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">₦${this.formatNumber(row.shares_balance)}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">₦${this.formatNumber(row.savings_amount)}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">₦${this.formatNumber(row.savings_balance)}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">₦${this.formatNumber(row.interest_paid)}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">₦${this.formatNumber(row.dev_levy)}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">₦${this.formatNumber(row.stationery)}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">₦${this.formatNumber(row.entry_fee)}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">₦${this.formatNumber(row.loan)}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">₦${this.formatNumber(row.loan_repayment)}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">₦${this.formatNumber(row.loan_balance)}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">₦${this.formatNumber(row.commodity)}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">₦${this.formatNumber(row.commodity_repayment)}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">₦${this.formatNumber(row.commodity_balance)}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right">₦${this.formatNumber(row.total)}</td>
                        </tr>
                    `;
                tbody.append(rowHtml);
            });

            // Add grand totals row if grand totals are provided
            if (grandTotals) {
                const grandTotalsRow = `
                    <tr class="bg-gradient-to-r from-blue-50 to-indigo-50 border-t-4 border-blue-500 font-bold">
                        <td colspan="4" class="px-4 py-4 text-sm text-gray-900 text-center">
                            <i class="fas fa-calculator mr-2 text-blue-600"></i>GRAND TOTALS (ALL PAGES)
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-900 text-right">₦${this.formatNumber(grandTotals.shares_amount)}</td>
                        <td class="px-4 py-4 text-sm text-gray-900 text-right">-</td>
                        <td class="px-4 py-4 text-sm text-gray-900 text-right">₦${this.formatNumber(grandTotals.savings_amount)}</td>
                        <td class="px-4 py-4 text-sm text-gray-900 text-right">-</td>
                        <td class="px-4 py-4 text-sm text-gray-900 text-right">₦${this.formatNumber(grandTotals.interest_paid)}</td>
                        <td class="px-4 py-4 text-sm text-gray-900 text-right">₦${this.formatNumber(grandTotals.dev_levy)}</td>
                        <td class="px-4 py-4 text-sm text-gray-900 text-right">₦${this.formatNumber(grandTotals.stationery)}</td>
                        <td class="px-4 py-4 text-sm text-gray-900 text-right">₦${this.formatNumber(grandTotals.entry_fee)}</td>
                        <td class="px-4 py-4 text-sm text-gray-900 text-right">₦${this.formatNumber(grandTotals.loan)}</td>
                        <td class="px-4 py-4 text-sm text-gray-900 text-right">₦${this.formatNumber(grandTotals.loan_repayment)}</td>
                        <td class="px-4 py-4 text-sm text-gray-900 text-right">-</td>
                        <td class="px-4 py-4 text-sm text-gray-900 text-right">₦${this.formatNumber(grandTotals.commodity)}</td>
                        <td class="px-4 py-4 text-sm text-gray-900 text-right">₦${this.formatNumber(grandTotals.commodity_repayment)}</td>
                        <td class="px-4 py-4 text-sm text-gray-900 text-right">-</td>
                        <td class="px-4 py-4 text-sm font-bold text-blue-700 text-right text-lg">₦${this.formatNumber(grandTotals.total)}</td>
                    </tr>
                `;
                tbody.append(grandTotalsRow);
            }

            $('#report-container').removeClass('hidden').addClass('fade-in');
        }

        updatePagination(pagination) {
            const container = $('#pagination');
            container.empty();

            if (pagination.total_pages <= 1) return;

            // Previous button
            if (pagination.current_page > 1) {
                container.append(`
                        <button class="page-link px-3 py-2 text-sm bg-white border border-gray-300 rounded-l-md hover:bg-gray-50" 
                                data-page="${pagination.current_page - 1}">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    `);
            }

            // Page numbers
            const startPage = Math.max(1, pagination.current_page - 2);
            const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

            for (let i = startPage; i <= endPage; i++) {
                const isActive = i === pagination.current_page;
                container.append(`
                        <button class="page-link px-3 py-2 text-sm border-t border-b border-gray-300 hover:bg-gray-50 ${isActive ? 'bg-blue-50 text-blue-600' : 'bg-white'}" 
                                data-page="${i}">
                            ${i}
                        </button>
                    `);
            }

            // Next button
            if (pagination.current_page < pagination.total_pages) {
                container.append(`
                        <button class="page-link px-3 py-2 text-sm bg-white border border-gray-300 rounded-r-md hover:bg-gray-50" 
                                data-page="${pagination.current_page + 1}">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    `);
            }
        }

        loadPage(page) {
            console.log('Loading page:', page);
            this.showLoadingModal('pagination');
            this.generateReport(page);
        }

        toggleSelectAll() {
            const isChecked = $('#select-all').is(':checked');
            $('.row-checkbox').prop('checked', isChecked);
        }

        deleteSelected() {
            const selectedRows = $('.row-checkbox:checked');
            if (selectedRows.length === 0) {
                this.showError('Please select records to delete');
                return;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: `This will delete ${selectedRows.length} selected records permanently!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.performDelete(selectedRows);
                }
            });
        }

        performDelete(selectedRows) {
            const records = [];
            selectedRows.each(function() {
                records.push($(this).val());
            });

            $.ajax({
                url: 'api/masterReport.php',
                method: 'POST',
                data: {
                    action: 'delete_records',
                    records: records
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess(`${records.length} records deleted successfully`);
                        this.generateReport();
                    } else {
                        this.showError(response.message);
                    }
                },
                error: () => {
                    this.showError('Failed to delete records');
                }
            });
        }

        exportToExcel() {
            if (this.reportData.length === 0) {
                this.showError('No data to export. Please generate a report first.');
                return;
            }

            this.showLoading();

            // Get current form data
            const periodFrom = $('#period-from').val();
            const periodTo = $('#period-to').val();
            const staffId = this.selectedStaff || '';

            if (!periodFrom || !periodTo) {
                this.hideLoading();
                this.showError('Please select both start and end periods');
                return;
            }

            // Create export URL with parameters
            const params = new URLSearchParams({
                period_from: periodFrom,
                period_to: periodTo,
                staff_id: staffId,
                export: 'true'
            });

            // Create a temporary link and click it to trigger download
            const link = document.createElement('a');
            link.href = `getMasterReportModern.php?${params.toString()}`;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            this.hideLoading();
            this.showSuccess('Excel file is being generated...');
        }

        printReport() {
            window.print();
        }

        clearFilters() {
            $('#staff-search').val('');
            $('#period-from').val('');
            $('#period-to').val('');
            $('#records-per-page').val('100');
            this.selectedStaff = null;
            $('#staff-info').addClass('hidden');
            $('#report-container').addClass('hidden');
            this.toggleClearButton();
        }

        clearStaffSearch() {
            $('#staff-search').val('');
            this.selectedStaff = null;
            $('#staff-info').addClass('hidden');
            this.toggleClearButton();
        }

        toggleClearButton() {
            const hasValue = $('#staff-search').val().trim().length > 0;
            $('#clear-staff-search').toggleClass('hidden', !hasValue);
        }

        showLoading() {
            $('#generate-report').prop('disabled', true).html(
                '<i class="fas fa-spinner fa-spin mr-2"></i>Generating...');
            $('#progress-container').removeClass('hidden');
        }

        hideLoading() {
            $('#generate-report').prop('disabled', false).html(
                '<i class="fas fa-search mr-2"></i>Generate Report');
            $('#progress-container').addClass('hidden');
        }

        showLoadingModal(context = 'report') {
            $('#loading-modal').removeClass('hidden');
            $('#modal-progress-bar').css('width', '0%');

            // Update text based on context
            const contextText = context === 'pagination' ? 'Loading page...' :
                context === 'pageSize' ? 'Updating records per page...' :
                'Loading report...';

            $('#loading-modal p').text(contextText);

            // Set a timeout to show a message if loading takes too long
            this.loadingTimeout = setTimeout(() => {
                if (!$('#loading-modal').hasClass('hidden')) {
                    $('#loading-modal p').text('This is taking longer than expected. Please wait...');
                }
            }, 10000); // 10 seconds
        }

        hideLoadingModal() {
            $('#loading-modal').addClass('hidden');
            if (this.loadingTimeout) {
                clearTimeout(this.loadingTimeout);
                this.loadingTimeout = null;
            }
        }

        updateModalProgress(percent) {
            $('#modal-progress-bar').css('width', percent + '%');
        }

        updateProgress(percent) {
            $('#progress-bar').css('width', percent + '%');
            $('#progress-text').text(`Loading... ${Math.round(percent)}%`);
        }

        formatNumber(num) {
            return parseFloat(num || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: message,
                timer: 3000
            });
        }

        showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message
            });
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        // Check if jQuery UI is loaded
        if (typeof $.fn.autocomplete === 'undefined') {
            console.warn('jQuery UI not loaded, loading fallback...');
            // Load jQuery UI dynamically
            $.getScript('https://code.jquery.com/ui/1.13.2/jquery-ui.min.js')
                .done(() => {
                    console.log('jQuery UI loaded successfully');
                    new MasterReportManager();
                })
                .fail(() => {
                    console.warn('Failed to load jQuery UI, using fallback autocomplete');
                    new MasterReportManager();
                });
        } else {
            new MasterReportManager();
        }
    });
    </script>

<?php include 'includes/footer.php'; ?>