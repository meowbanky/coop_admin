<?php
require_once('Connections/coop.php');
include_once('classes/model.php');

// Set page title
$pageTitle = 'OOUTH COOP - Dashboard';

// Include header
include 'includes/header.php';
?>

<style>
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
                Welcome to OOUTH COOP Management System - Your trusted partner in cooperative management and financial services. 
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

<!-- Main Navigation Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <!-- Employee Management -->
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-users-cog text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Employee Management</h3>
        </div>
        <p class="text-gray-600 mb-4">Manage employee records, departments, and personal information.</p>
        <a href="employee.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Manage Employees
        </a>
    </div>

    <!-- Deductions Processing -->
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-calculator text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Deductions Processing</h3>
        </div>
        <p class="text-gray-600 mb-4">Process monthly deductions and salary calculations.</p>
        <a href="payprocess.php" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Process Deductions
        </a>
    </div>

    <!-- Master Report -->
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <i class="fas fa-chart-bar text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Master Report</h3>
        </div>
        <p class="text-gray-600 mb-4">Generate comprehensive reports and analytics.</p>
        <a href="masterReportModern.php" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            View Reports
        </a>
    </div>

    <!-- Member Contributions -->
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <i class="fas fa-file-invoice text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Member Contributions</h3>
        </div>
        <p class="text-gray-600 mb-4">View and manage member contribution records.</p>
        <a href="print_member.php" class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            View Contributions
        </a>
    </div>

    <!-- Commodity Processing -->
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                <i class="fas fa-boxes text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Commodity Processing</h3>
        </div>
        <p class="text-gray-600 mb-4">Manage commodity processing and inventory.</p>
        <a href="procesCommodity.php" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Process Commodities
        </a>
    </div>

    <!-- Deduction Management -->
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-red-100 text-red-600">
                <i class="fas fa-edit text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">Deduction Management</h3>
        </div>
        <p class="text-gray-600 mb-4">Update and manage deduction settings.</p>
        <a href="update_deduction.php" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Manage Deductions
        </a>
    </div>
</div>

<!-- Admin Only Section -->
<?php if ($userRole === 'Admin'): ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <!-- User Management -->
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-gray-100 text-gray-600">
                <i class="fas fa-user-shield text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">User Management</h3>
        </div>
        <p class="text-gray-600 mb-4">Manage system users and access permissions.</p>
        <a href="users.php" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Manage Users
        </a>
    </div>

    <!-- File Upload -->
    <div class="bg-white rounded-lg shadow-lg p-6 card-hover">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-teal-100 text-teal-600">
                <i class="fas fa-upload text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 ml-4">File Upload</h3>
        </div>
        <p class="text-gray-600 mb-4">Upload and manage system files and documents.</p>
        <a href="upload.php" class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
            <i class="fas fa-arrow-right mr-2"></i>
            Upload Files
        </a>
    </div>
</div>
<?php endif; ?>

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

<?php include 'includes/footer.php'; ?>
