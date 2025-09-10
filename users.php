<?php 
ini_set('max_execution_time','300');
require_once('Connections/coop.php'); 
include_once('classes/model.php'); 

// Set page title
$pageTitle = 'OOUTH COOP - User Management';

// Include header
include 'includes/header.php';

//Start session
session_start();

//Check whether the session variable SESS_MEMBER_ID is present or not
if(!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')|| $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}

$currentPage = $_SERVER["PHP_SELF"];
$today = date('Y-m-d');
?>
    <div class="container mx-auto px-4 py-6">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 card-hover transition-all duration-300">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Users</p>
                        <p id="totalUsers" class="text-2xl font-bold text-gray-900">0</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 card-hover transition-all duration-300">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-full">
                        <i class="fas fa-user-check text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Active Users</p>
                        <p id="activeUsers" class="text-2xl font-bold text-gray-900">0</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 card-hover transition-all duration-300">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 rounded-full">
                        <i class="fas fa-user-times text-red-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Inactive Users</p>
                        <p id="inactiveUsers" class="text-2xl font-bold text-gray-900">0</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 card-hover transition-all duration-300">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-full">
                        <i class="fas fa-user-shield text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Admin Users</p>
                        <p id="adminUsers" class="text-2xl font-bold text-gray-900">0</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Search users by name, username, or ID..." 
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        <button id="clearSearchBtn" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 hidden">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                    <select id="userTypeFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Types</option>
                        <option value="Admin">Admin</option>
                        <option value="User">User</option>
                    </select>
                    <button id="searchBtn" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Users List</h3>
                    <div class="flex items-center space-x-4">
                        <span id="resultsCount" class="text-sm text-gray-600">Loading...</span>
                        <select id="rowsPerPage" class="px-3 py-1 border border-gray-300 rounded text-sm">
                            <option value="50">50 per page</option>
                            <option value="100" selected>100 per page</option>
                            <option value="250">250 per page</option>
                            <option value="500">500 per page</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="hidden p-8 text-center">
                <div class="loading-spinner mx-auto mb-4"></div>
                <p class="text-gray-600">Loading users...</p>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="hidden p-8 text-center">
                <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Users Found</h3>
                <p class="text-sm text-gray-500">Try adjusting your search criteria or add a new user.</p>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" id="selectAll" class="rounded border-gray-300">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- Users will be loaded here -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div id="paginationContainer" class="px-6 py-4 border-t border-gray-200">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Add New User</h3>
                        <button id="closeAddModal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <form id="addUserForm">
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Staff Search</label>
                            <input type="text" id="staffSearch" placeholder="Search for staff member..." 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div id="selectedStaff" class="hidden p-3 bg-blue-50 rounded-lg">
                            <p class="text-sm text-blue-800"><strong>Selected:</strong> <span id="selectedStaffName"></span></p>
                            <p class="text-xs text-blue-600">Staff ID: <span id="selectedStaffId"></span></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <input type="email" name="email" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input type="password" name="password" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                            <input type="password" name="confirmPassword" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">User Type</label>
                            <select name="userType" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select User Type</option>
                                <option value="Admin">Admin</option>
                                <option value="User">User</option>
                            </select>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                        <button type="button" id="cancelAddUser" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Edit User</h3>
                        <button id="closeEditModal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <form id="editUserForm">
                    <input type="hidden" name="userId" id="editUserId">
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <input type="text" name="username" id="editUsername" readonly 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <input type="text" name="fullName" id="editFullName" readonly 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <input type="email" name="email" id="editEmail" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">User Type</label>
                            <select name="userType" id="editUserType" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="Admin">Admin</option>
                                <option value="User">User</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" id="editStatus" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                        <button type="button" id="cancelEditUser" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div id="loadingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg shadow-xl p-8 text-center">
                <div class="loading-spinner mx-auto mb-4"></div>
                <p class="text-gray-600">Processing...</p>
            </div>
        </div>
    </div>

    <script>
    class UserManager {
        constructor() {
            this.currentPage = 1;
            this.recordsPerPage = parseInt($('#rowsPerPage').val()) || 100;
            this.searchTerm = '';
            this.statusFilter = '';
            this.userTypeFilter = '';
            this.usersData = [];
            this.totalRecords = 0;
            this.totalPages = 0;
            this.selectedStaff = null;
            this.init();
        }

        init() {
            this.bindEvents();
            this.loadUsers();
        }

        bindEvents() {
            // Search functionality
            $('#searchInput').on('input', (e) => {
                this.searchTerm = e.target.value;
                if (this.searchTerm.length > 0) {
                    $('#clearSearchBtn').removeClass('hidden');
                } else {
                    $('#clearSearchBtn').addClass('hidden');
                }
            });

            $('#clearSearchBtn').on('click', () => {
                $('#searchInput').val('');
                this.searchTerm = '';
                $('#clearSearchBtn').addClass('hidden');
                this.loadUsers();
            });

            $('#searchBtn').on('click', () => this.loadUsers());

            // Filter changes
            $('#statusFilter, #userTypeFilter, #rowsPerPage').on('change', () => {
                this.statusFilter = $('#statusFilter').val();
                this.userTypeFilter = $('#userTypeFilter').val();
                this.recordsPerPage = parseInt($('#rowsPerPage').val());
                this.currentPage = 1;
                this.loadUsers();
            });

            // Modal events
            $('#addUserBtn').on('click', () => this.showAddModal());
            $('#closeAddModal, #cancelAddUser').on('click', () => this.hideAddModal());
            $('#closeEditModal, #cancelEditUser').on('click', () => this.hideEditModal());

            // Form submissions
            $('#addUserForm').on('submit', (e) => this.handleAddUser(e));
            $('#editUserForm').on('submit', (e) => this.handleEditUser(e));

            // Staff search autocomplete
            $('#staffSearch').autocomplete({
                source: 'api/searchStaff.php',
                minLength: 2,
                select: (event, ui) => {
                    this.selectedStaff = ui.item;
                    this.showSelectedStaff();
                }
            });

            // Select all checkbox
            $('#selectAll').on('change', (e) => {
                $('.user-checkbox').prop('checked', e.target.checked);
            });
        }

        async loadUsers() {
            this.showLoading();

            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    records_per_page: this.recordsPerPage,
                    search: this.searchTerm,
                    status: this.statusFilter,
                    user_type: this.userTypeFilter
                });

                const response = await fetch(`api/users.php?${params}`);
                const data = await response.json();

                if (data.success) {
                    this.usersData = data.users;
                    this.totalRecords = data.total_records;
                    this.totalPages = data.total_pages;
                    this.currentPage = data.current_page;

                    this.displayUsers();
                    this.updateStatistics();
                    this.updatePagination();
                } else {
                    this.showError(data.message || 'Failed to load users');
                }
            } catch (error) {
                console.error('Error loading users:', error);
                this.showError('An error occurred while loading users');
            } finally {
                this.hideLoading();
            }
        }

        displayUsers() {
            const tbody = $('#usersTableBody');
            
            if (this.usersData.length === 0) {
                this.showEmptyState();
                return;
            }

            tbody.empty();
            this.usersData.forEach((user, index) => {
                const row = `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" class="user-checkbox rounded border-gray-300" value="${user.user_id}">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            ${user.user_id}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${user.Username || 'N/A'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${user.CompleteName || 'N/A'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${user.AdminType === 'Admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'}">
                                ${user.AdminType || 'User'}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${user.Status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                ${user.Status || 'Inactive'}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button onclick="userManager.editUser(${user.user_id})" 
                                        class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="userManager.toggleUserStatus(${user.user_id}, '${user.Status}')" 
                                        class="${user.Status === 'Active' ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'}">
                                    <i class="fas fa-${user.Status === 'Active' ? 'times' : 'check'}"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }

        updateStatistics() {
            const totalUsers = this.totalRecords;
            const activeUsers = this.usersData.filter(user => user.Status === 'Active').length;
            const inactiveUsers = this.usersData.filter(user => user.Status === 'Inactive').length;
            const adminUsers = this.usersData.filter(user => user.AdminType === 'Admin').length;

            $('#totalUsers').text(totalUsers);
            $('#activeUsers').text(activeUsers);
            $('#inactiveUsers').text(inactiveUsers);
            $('#adminUsers').text(adminUsers);
            $('#resultsCount').text(`${totalUsers} users found`);
        }

        updatePagination() {
            const container = $('#paginationContainer');
            container.empty();

            if (this.totalPages <= 1) return;

            let pagination = '<nav class="flex items-center justify-between"><div class="flex-1 flex justify-between sm:hidden">';
            
            // Previous button
            if (this.currentPage > 1) {
                pagination += `<button onclick="userManager.loadPage(${this.currentPage - 1})" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</button>`;
            }
            
            // Next button
            if (this.currentPage < this.totalPages) {
                pagination += `<button onclick="userManager.loadPage(${this.currentPage + 1})" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</button>`;
            }
            
            pagination += '</div><div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">';
            pagination += `<div><p class="text-sm text-gray-700">Showing <span class="font-medium">${((this.currentPage - 1) * this.recordsPerPage) + 1}</span> to <span class="font-medium">${Math.min(this.currentPage * this.recordsPerPage, this.totalRecords)}</span> of <span class="font-medium">${this.totalRecords}</span> results</p></div>`;
            
            pagination += '<div><div class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">';
            
            // Page numbers
            for (let i = 1; i <= this.totalPages; i++) {
                if (i === this.currentPage) {
                    pagination += `<button class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600">${i}</button>`;
                } else {
                    pagination += `<button onclick="userManager.loadPage(${i})" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">${i}</button>`;
                }
            }
            
            pagination += '</div></div></div></nav>';
            container.html(pagination);
        }

        loadPage(page) {
            this.currentPage = page;
            this.loadUsers();
        }

        showAddModal() {
            $('#addUserModal').removeClass('hidden');
            $('#staffSearch').focus();
        }

        hideAddModal() {
            $('#addUserModal').addClass('hidden');
            this.resetAddForm();
        }

        showEditModal(userId) {
            const user = this.usersData.find(u => u.user_id == userId);
            if (!user) return;

            $('#editUserId').val(user.user_id);
            $('#editUsername').val(user.Username);
            $('#editFullName').val(user.CompleteName);
            $('#editEmail').val(user.Email || '');
            $('#editUserType').val(user.AdminType);
            $('#editStatus').val(user.Status);

            $('#editUserModal').removeClass('hidden');
        }

        hideEditModal() {
            $('#editUserModal').addClass('hidden');
        }

        showSelectedStaff() {
            if (this.selectedStaff) {
                $('#selectedStaffName').text(this.selectedStaff.label);
                $('#selectedStaffId').text(this.selectedStaff.value);
                $('#selectedStaff').removeClass('hidden');
                $('#staffSearch').val('');
            }
        }

        resetAddForm() {
            $('#addUserForm')[0].reset();
            this.selectedStaff = null;
            $('#selectedStaff').addClass('hidden');
        }

        async handleAddUser(e) {
            e.preventDefault();
            
            if (!this.selectedStaff) {
                this.showError('Please select a staff member first');
                return;
            }

            const formData = new FormData(e.target);
            formData.append('staff_id', this.selectedStaff.value);
            formData.append('staff_name', this.selectedStaff.label);

            if (formData.get('password') !== formData.get('confirmPassword')) {
                this.showError('Passwords do not match');
                return;
            }

            this.showLoadingModal();

            try {
                const response = await fetch('api/users.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess('User created successfully');
                    this.hideAddModal();
                    this.loadUsers();
                } else {
                    this.showError(data.message || 'Failed to create user');
                }
            } catch (error) {
                console.error('Error creating user:', error);
                this.showError('An error occurred while creating user');
            } finally {
                this.hideLoadingModal();
            }
        }

        async handleEditUser(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            this.showLoadingModal();

            try {
                const response = await fetch('api/users.php', {
                    method: 'PUT',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess('User updated successfully');
                    this.hideEditModal();
                    this.loadUsers();
                } else {
                    this.showError(data.message || 'Failed to update user');
                }
            } catch (error) {
                console.error('Error updating user:', error);
                this.showError('An error occurred while updating user');
            } finally {
                this.hideLoadingModal();
            }
        }

        async toggleUserStatus(userId, currentStatus) {
            const newStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
            const action = newStatus === 'Active' ? 'activate' : 'deactivate';

            const result = await Swal.fire({
                title: `${action.charAt(0).toUpperCase() + action.slice(1)} User`,
                text: `Are you sure you want to ${action} this user?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: newStatus === 'Active' ? '#10B981' : '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: `Yes, ${action} user`
            });

            if (result.isConfirmed) {
                this.showLoadingModal();

                try {
                    const response = await fetch('api/users.php', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'toggle_status',
                            user_id: userId,
                            status: newStatus
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showSuccess(`User ${action}d successfully`);
                        this.loadUsers();
                    } else {
                        this.showError(data.message || `Failed to ${action} user`);
                    }
                } catch (error) {
                    console.error(`Error ${action}ing user:`, error);
                    this.showError(`An error occurred while ${action}ing user`);
                } finally {
                    this.hideLoadingModal();
                }
            }
        }

        editUser(userId) {
            this.showEditModal(userId);
        }

        showLoading() {
            $('#loadingState').removeClass('hidden');
            $('#emptyState').addClass('hidden');
        }

        hideLoading() {
            $('#loadingState').addClass('hidden');
        }

        showLoadingModal() {
            $('#loadingModal').removeClass('hidden');
        }

        hideLoadingModal() {
            $('#loadingModal').addClass('hidden');
        }

        showEmptyState() {
            $('#emptyState').removeClass('hidden');
        }

        showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: message,
                timer: 3000,
                showConfirmButton: false
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

    // Initialize the user manager
    const userManager = new UserManager();
    </script>

<?php include 'includes/footer.php'; ?>
