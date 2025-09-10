<?php
// Set page title
$pageTitle = 'OOUTH COOP - Pay Periods';

// Include header
include 'includes/header.php';

?>

<?php 
require_once('Connections/coop.php');
include_once('classes/model.php');

// Start session
session_start();

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
                        <i class="fas fa-calendar-alt text-blue-600 mr-3"></i>Payroll Periods
                    </h2>
                    <p class="text-gray-600">Create and manage your organization's payroll periods</p>
                </div>
            </div>
        </div>

        <!-- Breadcrumb -->
        <nav class="mb-6 fade-in">
            <ol class="flex items-center space-x-2 text-sm">
                <li><a href="home.php" class="text-blue-600 hover:text-blue-800"><i
                            class="fas fa-home mr-1"></i>Dashboard</a></li>
                <li><i class="fas fa-chevron-right text-gray-400"></i></li>
                <li class="text-gray-500">Payroll Periods</li>
            </ol>
        </nav>

        <!-- Messages -->
        <?php if (isset($_SESSION['msg'])): ?>
        <div class="mb-6 fade-in">
            <div
                class="bg-<?php echo $_SESSION['alertcolor'] === 'success' ? 'green' : 'red'; ?>-50 border border-<?php echo $_SESSION['alertcolor'] === 'success' ? 'green' : 'red'; ?>-200 text-<?php echo $_SESSION['alertcolor'] === 'success' ? 'green' : 'red'; ?>-700 px-4 py-3 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i
                            class="fas fa-<?php echo $_SESSION['alertcolor'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
                        <span><?php echo $_SESSION['msg']; ?></span>
                    </div>
                    <button onclick="this.parentElement.parentElement.style.display='none'"
                        class="text-<?php echo $_SESSION['alertcolor'] === 'success' ? 'green' : 'red'; ?>-500 hover:text-<?php echo $_SESSION['alertcolor'] === 'success' ? 'green' : 'red'; ?>-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php 
            unset($_SESSION['msg']);
            unset($_SESSION['alertcolor']);
            ?>
        <?php endif; ?>
        <!-- Action Bar -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 fade-in">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <i class="fas fa-calendar-alt text-blue-600 mr-3"></i>
                    <h3 class="text-lg font-semibold text-gray-900">Period Management</h3>
                </div>
                <button onclick="openAddPeriodModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add New Period
                </button>
            </div>
        </div>

        <!-- Periods Table -->
        <div class="bg-white rounded-xl shadow-lg fade-in">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-th text-blue-600 mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900">Payroll Periods</h3>
                    </div>
                </div>
            </div>

            <!-- Table Container -->
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-hashtag mr-1"></i>#
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-calendar mr-1"></i>Payment Period
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-user mr-1"></i>Created By
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-clock mr-1"></i>Date Created
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-cog mr-1"></i>Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            try {
                                $query = $conn->prepare('SELECT * FROM tbpayrollperiods ORDER BY id DESC');
                                $fin = $query->execute();
                                $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                $counter = 1;

                                foreach ($res as $row => $link) {
                                    $thisperiod = $link['id'];
                                    ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <span class="text-blue-600 font-medium text-sm"><?php echo $counter; ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($link['PayrollPeriod']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($link['InsertedBy']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($link['DateInserted'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button onclick="viewPeriod(<?php echo $thisperiod; ?>)" class="text-blue-600 hover:text-blue-900 transition-colors" title="View Period">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick="closePeriod(<?php echo $thisperiod; ?>)" class="text-red-600 hover:text-red-900 transition-colors" title="Close Period">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                    $counter++;
                                }
                            } catch(PDOException $e) {
                                echo '<tr><td colspan="5" class="px-6 py-4 text-center text-red-600">Error loading periods: ' . $e->getMessage() . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>


    <!-- Add New Period Modal -->
    <div id="addPeriodModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Add New Payment Period</h3>
                        <button onclick="closeAddPeriodModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <form id="addPeriodForm" method="post" action="classes/controller.php?act=addperiod">
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                            <select name="perioddesc" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="January">January</option>
                                <option value="February">February</option>
                                <option value="March">March</option>
                                <option value="April">April</option>
                                <option value="May">May</option>
                                <option value="June">June</option>
                                <option value="July">July</option>
                                <option value="August">August</option>
                                <option value="September">September</option>
                                <option value="October">October</option>
                                <option value="November">November</option>
                                <option value="December">December</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                            <select name="periodyear" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="<?php echo date('Y'); ?>"><?php echo date('Y'); ?></option>
                                <option value="<?php echo date('Y')+1; ?>"><?php echo date('Y')+1; ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-end space-x-3">
                        <button type="button" onclick="closeAddPeriodModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                            Create Period
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Period Modal -->
    <div id="viewPeriodModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Reactivate Period</h3>
                        <button onclick="closeViewPeriodModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <form id="viewPeriodForm" method="post" action="assets/classes/controller.php?act=activateclosedperiod">
                    <div class="px-6 py-4">
                        <div class="mb-4">
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-800">
                                            Please confirm you would like to reactivate this <strong>CLOSED</strong> period to <strong>VIEW</strong> data. 
                                            <strong>Please note you cannot transact in this period.</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Period</label>
                            <input type="text" id="periodDisplay" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                            <input type="hidden" id="reactivatePeriodId" name="reactivateperiodid">
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-end space-x-3">
                        <button type="button" onclick="closeViewPeriodModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                            Reactivate Period
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Close Period Modal -->
    <div id="closePeriodModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Close Current Period</h3>
                        <button onclick="closeClosePeriodModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <form id="closePeriodForm" method="post" action="assets/classes/controller.php?act=closeActivePeriod">
                    <div class="px-6 py-4">
                        <div class="mb-4">
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-red-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-red-800">
                                            Please confirm you would like to close the period below. Ensure you have completed all transactional changes and processing for the current month. 
                                            <strong>This process is irreversible.</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Period</label>
                            <input type="text" id="currentPeriodDisplay" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-end space-x-3">
                        <button type="button" onclick="closeClosePeriodModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                            Close Period
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
    class PeriodManager {
        constructor() {
            this.init();
        }

        init() {
            this.setupEventListeners();
            this.setupFormHandlers();
        }

        setupEventListeners() {
            // Close modals when clicking outside
            window.onclick = (event) => {
                const addModal = document.getElementById('addPeriodModal');
                const viewModal = document.getElementById('viewPeriodModal');
                const closeModal = document.getElementById('closePeriodModal');
                
                if (event.target === addModal) {
                    this.closeAddPeriodModal();
                } else if (event.target === viewModal) {
                    this.closeViewPeriodModal();
                } else if (event.target === closeModal) {
                    this.closeClosePeriodModal();
                }
            }
        }

        setupFormHandlers() {
            // Form submission with loading states
            document.getElementById('addPeriodForm').addEventListener('submit', (e) => {
                const submitBtn = e.target.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating...';
                submitBtn.disabled = true;
            });

            document.getElementById('viewPeriodForm').addEventListener('submit', (e) => {
                const submitBtn = e.target.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Reactivating...';
                submitBtn.disabled = true;
            });

            document.getElementById('closePeriodForm').addEventListener('submit', (e) => {
                const submitBtn = e.target.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Closing...';
                submitBtn.disabled = true;
            });
        }

        // Modal functions
        openAddPeriodModal() {
            document.getElementById('addPeriodModal').classList.remove('hidden');
        }

        closeAddPeriodModal() {
            document.getElementById('addPeriodModal').classList.add('hidden');
        }

        viewPeriod(periodId) {
            document.getElementById('reactivatePeriodId').value = periodId;
            // You can add logic here to fetch and display period details
            document.getElementById('periodDisplay').value = 'Period ' + periodId;
            document.getElementById('viewPeriodModal').classList.remove('hidden');
        }

        closeViewPeriodModal() {
            document.getElementById('viewPeriodModal').classList.add('hidden');
        }

        closePeriod(periodId) {
            // You can add logic here to fetch and display current period details
            document.getElementById('currentPeriodDisplay').value = 'Current Period ' + periodId;
            document.getElementById('closePeriodModal').classList.remove('hidden');
        }

        closeClosePeriodModal() {
            document.getElementById('closePeriodModal').classList.add('hidden');
        }

        showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        }

        showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: message,
                timer: 2000,
                showConfirmButton: false
            });
        }
    }

    // Global functions for onclick handlers
    function openAddPeriodModal() {
        window.periodManager.openAddPeriodModal();
    }

    function closeAddPeriodModal() {
        window.periodManager.closeAddPeriodModal();
    }

    function viewPeriod(periodId) {
        window.periodManager.viewPeriod(periodId);
    }

    function closeViewPeriodModal() {
        window.periodManager.closeViewPeriodModal();
    }

    function closePeriod(periodId) {
        window.periodManager.closePeriod(periodId);
    }

    function closeClosePeriodModal() {
        window.periodManager.closeClosePeriodModal();
    }

    // Global variables for compatibility
    COMMON_SUCCESS = "Success";
    COMMON_ERROR = "Error";

    // AJAX setup
    $.ajaxSetup({
        cache: false,
        headers: {
            "cache-control": "no-cache"
        }
    });

    // Initialize when document is ready
    $(document).ready(() => {
        window.periodManager = new PeriodManager();
    });
    </script>

<?php include 'includes/footer.php'; ?>
