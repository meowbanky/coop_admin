<?php
// Set page title
$pageTitle = 'OOUTH COOP - Member Contributions';

// Include header
include 'includes/header.php';

?>

<?php
ini_set('max_execution_time', '300');
session_start();

require_once('Connections/coop.php');
include_once('classes/model.php');

// Check whether the session variable SESS_MEMBER_ID is present or not
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}

$userName = $_SESSION['SESS_FIRST_NAME'] ?? 'User';
$userRole = $_SESSION['SESS_ROLE'] ?? 'Administrator';
?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8 fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-users text-blue-600 mr-3"></i>Member Contributions
                    </h2>
                    <p class="text-gray-600">View and export member contribution reports by period</p>
                </div>
                <div class="flex space-x-3 no-print">
                    <button onclick="window.print()"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                    <button onclick="exportToExcel()" id="exportBtn"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-file-excel mr-2"></i>Export to Excel
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 fade-in no-print">
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Members</p>
                        <p class="text-2xl font-bold text-gray-900" id="totalMembers">
                            <?php
                            $total_members = $conn->query("SELECT COUNT(*) FROM tblemployees")->fetchColumn();
                            echo number_format($total_members);
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-user-check text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Active Members</p>
                        <p class="text-2xl font-bold text-gray-900" id="activeMembers">
                            <?php
                            $active_members = $conn->query("SELECT COUNT(*) FROM tblemployees WHERE Status = 'Active'")->fetchColumn();
                            echo number_format($active_members);
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-calendar text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Selected Period</p>
                        <p class="text-2xl font-bold text-gray-900" id="selectedPeriod">All Periods</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Contributions</p>
                        <p class="text-2xl font-bold text-gray-900" id="totalContributions">₦0.00</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white rounded-xl shadow-lg mb-8 fade-in no-print">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center">
                    <i class="fas fa-filter text-blue-600 mr-3"></i>
                    <h3 class="text-lg font-semibold text-gray-900">Filter & Search</h3>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Period Selection -->
                    <div>
                        <label for="periodSelect" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar mr-1"></i>Select Period
                        </label>
                        <select id="periodSelect" name="periodSelect"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Periods</option>
                            <?php 
                            $query = $conn->prepare('SELECT * FROM tbpayrollperiods ORDER BY id DESC');
                            $res = $query->execute();
                            $out = $query->fetchAll(PDO::FETCH_ASSOC);
                            
                            while ($row = array_shift($out)) {
                                echo '<option value="' . htmlspecialchars($row['id']) . '">';
                                echo htmlspecialchars($row['PayrollPeriod']);
                                echo '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="statusFilter" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-filter mr-1"></i>Status Filter
                        </label>
                        <select id="statusFilter" name="statusFilter"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="In-Active">In-Active</option>
                        </select>
                    </div>

                    <!-- Rows Per Page -->
                    <div>
                        <label for="rowsPerPage" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-list mr-1"></i>Rows Per Page
                        </label>
                        <select id="rowsPerPage" name="rowsPerPage"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="50">50</option>
                            <option value="250" selected>250</option>
                            <option value="500">500</option>
                            <option value="1000">1000</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-end space-x-3">
                        <button onclick="filterMembers()" id="filterBtn"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                        <button onclick="clearFilters()" id="clearBtn"
                            class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition-colors">
                            <i class="fas fa-times mr-2"></i>Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div class="bg-white rounded-xl shadow-lg fade-in">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-table text-blue-600 mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900">Member Contributions</h3>
                    </div>
                    <div class="text-sm text-gray-500" id="resultsCount">
                        Loading...
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <div id="loadingSpinner" class="hidden flex justify-center items-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-3 text-gray-600">Loading members...</span>
                </div>
                <div id="membersTable">
                    <table class="min-w-full divide-y divide-gray-200 print-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    S/N</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Coop ID</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Full Name</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Department</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Monthly Contribution</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Savings Amount</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Contribution</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Signature</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date</th>
                            </tr>
                        </thead>
                        <tbody id="membersTableBody" class="bg-white divide-y divide-gray-200">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div id="paginationContainer" class="mt-6 no-print">
            <!-- Pagination will be loaded here -->
        </div>
    </main>


    <script>
    class MemberContributionManager {
        constructor() {
            this.currentPage = 1;
            this.recordsPerPage = parseInt($('#rowsPerPage').val()) || 250;
            this.currentPeriod = '';
            this.currentStatus = '';
            this.membersData = [];
            this.totalRecords = 0;
            this.totalPages = 0;
            this.apiTotalContributions = 0;
            this.init();
        }

        init() {
            this.bindEvents();
            this.showEmptyState();
        }

        bindEvents() {
            $('#periodSelect').on('change', () => {
                this.currentPeriod = $('#periodSelect').val();
                this.currentPage = 1;
                if (this.currentPeriod) {
                    this.loadMembers();
                } else {
                    this.showEmptyState();
                }
            });

            $('#statusFilter').on('change', () => {
                this.currentStatus = $('#statusFilter').val();
                this.currentPage = 1;
                if (this.currentPeriod) {
                    this.loadMembers();
                }
            });

            $('#rowsPerPage').on('change', () => {
                this.recordsPerPage = parseInt($('#rowsPerPage').val()) || 250;
                this.currentPage = 1;
                if (this.currentPeriod) {
                    this.loadMembers();
                }
            });

            $('#filterBtn').on('click', () => this.filterMembers());
            $('#clearBtn').on('click', () => this.clearFilters());
        }

        showEmptyState() {
            this.hideLoading();
            this.membersData = [];
            this.totalRecords = 0;
            this.totalPages = 0;
            this.apiTotalContributions = 0;

            // Show empty state message
            const tbody = $('#membersTableBody');
            tbody.html(`
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-calendar-alt text-4xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Select a Period</h3>
                            <p class="text-sm text-gray-500">Please select a payroll period to view member contributions</p>
                        </div>
                    </td>
                </tr>
            `);

            // Update statistics
            $('#totalContributions').text('₦0.00');
            $('#selectedPeriod').text('All Periods');
            $('#resultsCount').text('Select a period to view contributions');

            // Hide pagination
            $('#paginationContainer').empty();
        }

        async loadMembers() {
            this.showLoading();

            try {
                const response = await fetch('getMemberContributions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        period: this.currentPeriod,
                        status: this.currentStatus,
                        page: this.currentPage,
                        records_per_page: this.recordsPerPage
                    })
                });

                const data = await response.json();
                console.log('API Response:', data);

                if (data.success) {
                    this.membersData = data.members;

                    // Extract pagination data from the response
                    if (data.pagination) {
                        this.totalRecords = data.pagination.total_records;
                        this.totalPages = data.pagination.total_pages;
                        this.currentPage = data.pagination.current_page;
                    } else {
                        // Fallback to direct properties
                        this.totalRecords = data.total_records || 0;
                        this.totalPages = data.total_pages || 0;
                        this.currentPage = data.current_page || 1;
                    }

                    // Store total contributions from API (all rows, not just current page)
                    this.apiTotalContributions = data.total_contributions || 0;

                    console.log('Members data:', this.membersData);
                    console.log('Total records:', this.totalRecords);
                    console.log('Total pages:', this.totalPages);
                    console.log('Current page:', this.currentPage);
                    console.log('Total contributions (all rows):', this.apiTotalContributions);

                    this.displayMembers();
                    this.updateStatistics();
                    this.updatePagination();
                } else {
                    console.error('API Error:', data);
                    this.showError(data.message || 'Failed to load members');
                }
            } catch (error) {
                console.error('Error loading members:', error);
                this.showError('An error occurred while loading members');
            }
        }

        displayMembers() {
            const tbody = $('#membersTableBody');
            tbody.empty();

            if (this.membersData.length === 0) {
                tbody.html(`
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                            <i class="fas fa-info-circle mr-2"></i>
                            No members found for the selected criteria
                        </td>
                    </tr>
                `);
            } else {
                this.membersData.forEach((member, index) => {
                    const rowNumber = ((this.currentPage - 1) * this.recordsPerPage) + index + 1;
                    const fullName = `${member.LastName}, ${member.FirstName} ${member.MiddleName || ''}`
                        .trim();

                    tbody.append(`
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${rowNumber}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">${member.CoopID}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${fullName}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${member.Department || 'N/A'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₦${this.formatNumber(member.monthly_contribution || 0)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₦${this.formatNumber(member.savings_amount || 0)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">₦${this.formatNumber(member.total_contribution || 0)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">_____________</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${new Date().toLocaleDateString()}</td>
                        </tr>
                    `);
                });
            }

            this.hideLoading();
        }

        updateStatistics() {
            // Use total_contributions from API response (all rows) instead of calculating from current page
            const totalContributions = this.apiTotalContributions || 0;
            $('#totalContributions').text(`₦${this.formatNumber(totalContributions)}`);

            const selectedPeriodText = this.currentPeriod ?
                $('#periodSelect option:selected').text() : 'All Periods';
            $('#selectedPeriod').text(selectedPeriodText);

            $('#resultsCount').text(`${this.totalRecords} members found`);
        }

        updatePagination() {
            const container = $('#paginationContainer');
            container.empty();

            if (this.totalPages <= 1) return;

            let pagination =
                '<nav class="flex items-center justify-between"><div class="flex-1 flex justify-between sm:hidden">';

            // Mobile pagination
            if (this.currentPage > 1) {
                pagination +=
                    `<button onclick="memberManager.goToPage(${this.currentPage - 1})" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</button>`;
            }
            if (this.currentPage < this.totalPages) {
                pagination +=
                    `<button onclick="memberManager.goToPage(${this.currentPage + 1})" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</button>`;
            }

            pagination += '</div><div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">';
            pagination +=
                `<div><p class="text-sm text-gray-700">Showing <span class="font-medium">${((this.currentPage - 1) * this.recordsPerPage) + 1}</span> to <span class="font-medium">${Math.min(this.currentPage * this.recordsPerPage, this.totalRecords)}</span> of <span class="font-medium">${this.totalRecords}</span> results</p></div>`;

            pagination += '<div><span class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">';

            // Previous button
            if (this.currentPage > 1) {
                pagination +=
                    `<button onclick="memberManager.goToPage(${this.currentPage - 1})" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"><i class="fas fa-chevron-left"></i></button>`;
            }

            // Page numbers
            const startPage = Math.max(1, this.currentPage - 2);
            const endPage = Math.min(this.totalPages, this.currentPage + 2);

            for (let i = startPage; i <= endPage; i++) {
                const isActive = i === this.currentPage;
                pagination +=
                    `<button onclick="memberManager.goToPage(${i})" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium ${isActive ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'}">${i}</button>`;
            }

            // Next button
            if (this.currentPage < this.totalPages) {
                pagination +=
                    `<button onclick="memberManager.goToPage(${this.currentPage + 1})" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"><i class="fas fa-chevron-right"></i></button>`;
            }

            pagination += '</span></div></div></nav>';
            container.html(pagination);
        }

        goToPage(page) {
            this.currentPage = page;
            this.loadMembers();
        }

        filterMembers() {
            if (!this.currentPeriod) {
                this.showError('Please select a period first before filtering');
                return;
            }
            this.currentStatus = $('#statusFilter').val();
            this.currentPage = 1;
            this.loadMembers();
        }

        clearFilters() {
            $('#periodSelect').val('');
            $('#statusFilter').val('');
            $('#rowsPerPage').val('250');
            this.currentPeriod = '';
            this.currentStatus = '';
            this.recordsPerPage = 250;
            this.currentPage = 1;
            this.showEmptyState();
        }

        async exportToExcel() {
            if (!this.currentPeriod) {
                this.showError('Please select a period first before exporting');
                return;
            }

            if (this.membersData.length === 0) {
                this.showError('No data to export. Please load members first.');
                return;
            }

            this.showLoading();

            try {
                const response = await fetch('exportMemberContributions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        period: this.currentPeriod,
                        status: this.currentStatus,
                        export: 'excel'
                    })
                });

                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `member_contributions_${new Date().toISOString().split('T')[0]}.xlsx`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);

                    this.showSuccess('Excel file downloaded successfully!');
                } else {
                    this.showError('Failed to export data');
                }
            } catch (error) {
                console.error('Export error:', error);
                this.showError('An error occurred while exporting data');
            } finally {
                this.hideLoading();
            }
        }

        formatNumber(num) {
            return new Intl.NumberFormat('en-NG').format(num);
        }

        showLoading() {
            $('#loadingSpinner').removeClass('hidden');
            $('#membersTable').addClass('hidden');
            $('#exportBtn').prop('disabled', true);
        }

        hideLoading() {
            $('#loadingSpinner').addClass('hidden');
            $('#membersTable').removeClass('hidden');
            $('#exportBtn').prop('disabled', false);
        }

        showSuccess(message) {
            Swal.fire({
                title: 'Success!',
                text: message,
                icon: 'success',
                confirmButtonColor: '#10b981'
            });
        }

        showError(message) {
            Swal.fire({
                title: 'Error!',
                text: message,
                icon: 'error',
                confirmButtonColor: '#ef4444'
            });
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        window.memberManager = new MemberContributionManager();
    });

    // Global functions for onclick handlers
    function searchMembers() {
        window.memberManager.searchMembers();
    }

    function clearFilters() {
        window.memberManager.clearFilters();
    }

    function exportToExcel() {
        window.memberManager.exportToExcel();
    }
    </script>

<?php include 'includes/footer.php'; ?>
