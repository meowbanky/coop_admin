<?php 
ini_set('max_execution_time', '300');
require_once('Connections/coop.php');
include_once('classes/model.php'); 

$coopid = isset($_POST['coopid']) ? trim($_POST['coopid']) : '';
$period = isset($_POST['period']) ? (int)$_POST['period'] : null;

if (!$period) {
    echo '<div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg mb-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span>Please select a period.</span>
            </div>
          </div>';
    exit;
}

if (!$coopid) {
    echo '<div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg mb-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span>Please select a cooperative member.</span>
            </div>
          </div>';
    exit;
}

// Get employee details for display
$emp_query = $conn->prepare('SELECT FirstName, LastName, CoopID FROM tblemployees WHERE CoopID = ?');
$emp_query->execute([$coopid]);
$employee = $emp_query->fetch(PDO::FETCH_ASSOC);
$employeeName = $employee ? $employee['FirstName'] . ' ' . $employee['LastName'] : 'Unknown Employee';

// Get period details
$period_query = $conn->prepare('SELECT PayrollPeriod FROM tbpayrollperiods WHERE id = ?');
$period_query->execute([$period]);
$periodData = $period_query->fetch(PDO::FETCH_ASSOC);
$periodName = $periodData ? $periodData['PayrollPeriod'] : 'Unknown Period';
?>

<?php
// Get current deduction and savings values
$query = $conn->prepare('SELECT MonthlyContribution FROM tbl_monthlycontribution WHERE coopID = ? AND period = ?');
$query->execute([$coopid, $period]);
$deduction = $query->fetchColumn() ?: 0;

$query = $conn->prepare('SELECT Amount FROM tbl_loansavings WHERE COOPID = ? AND period = ?');
$query->execute([$coopid, $period]);
$savings = $query->fetchColumn() ?: 0;

$total = $deduction + $savings;

// Get grand totals for the period
$query = $conn->prepare("SELECT SUM(mc.MonthlyContribution) AS total FROM tbl_monthlycontribution mc INNER JOIN tblemployees e ON mc.coopID = e.CoopID WHERE mc.period = ?");
$query->execute([$period]);
$grandTotal1 = $query->fetchColumn() ?: 0;

$query = $conn->prepare("SELECT SUM(ls.Amount) AS total FROM tbl_loansavings ls INNER JOIN tblemployees e ON ls.COOPID = e.CoopID WHERE ls.period = ?");
$query->execute([$period]);
$grandTotal2 = $query->fetchColumn() ?: 0;

$grandTotal = $grandTotal1 + $grandTotal2;
?>

<!-- Employee & Period Info -->
<div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 mb-6 border border-blue-200">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <div class="bg-blue-100 p-3 rounded-full">
                <i class="fas fa-user text-blue-600 text-xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($employeeName); ?></h3>
                <p class="text-sm text-gray-600">ID: <?php echo htmlspecialchars($coopid); ?></p>
            </div>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-600">Period</p>
            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($periodName); ?></p>
        </div>
    </div>
</div>

<!-- Current Deduction Details -->
<div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
    <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
        <i class="fas fa-chart-line text-green-600 mr-2"></i>
        Current Deduction Details
    </h4>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Deduction -->
        <div class="bg-red-50 rounded-lg p-4 border border-red-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-red-600">Deduction</p>
                    <p class="text-2xl font-bold text-red-900">₦<?php echo number_format(floatval($deduction), 2); ?></p>
                </div>
                <div class="bg-red-100 p-2 rounded-full">
                    <i class="fas fa-minus text-red-600"></i>
                </div>
            </div>
        </div>

        <!-- Savings -->
        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-600">Savings</p>
                    <p class="text-2xl font-bold text-green-900">₦<?php echo number_format(floatval($savings), 2); ?></p>
                </div>
                <div class="bg-green-100 p-2 rounded-full">
                    <i class="fas fa-plus text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Total -->
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-600">Total</p>
                    <p class="text-2xl font-bold text-blue-900">₦<?php echo number_format($total, 2); ?></p>
                </div>
                <div class="bg-blue-100 p-2 rounded-full">
                    <i class="fas fa-equals text-blue-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Grand Total -->
    <div class="mt-6 pt-4 border-t border-gray-200">
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Grand Total (All Employees)</p>
                    <p class="text-3xl font-bold text-gray-900">₦<?php echo number_format($grandTotal, 2); ?></p>
                </div>
                <div class="bg-gray-100 p-3 rounded-full">
                    <i class="fas fa-calculator text-gray-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Form -->
<div class="bg-white rounded-lg border border-gray-200 p-6">
    <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
        <i class="fas fa-edit text-blue-600 mr-2"></i>
        Update Deduction & Savings
    </h4>
    
    <form id="deduction-form" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Deduction Input -->
            <div>
                <label for="deduction" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-minus-circle text-red-500 mr-1"></i>
                    Deduction Amount
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">₦</span>
                    </div>
                    <input type="number" 
                           id="deduction" 
                           name="deduction" 
                           value="<?php echo floatval($deduction); ?>"
                           class="block w-full pl-8 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors"
                           placeholder="0.00"
                           step="0.01"
                           min="0">
                </div>
            </div>

            <!-- Savings Input -->
            <div>
                <label for="savings" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-plus-circle text-green-500 mr-1"></i>
                    Savings Amount
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">₦</span>
                    </div>
                    <input type="number" 
                           id="savings" 
                           name="savings" 
                           value="<?php echo floatval($savings); ?>"
                           class="block w-full pl-8 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                           placeholder="0.00"
                           step="0.01"
                           min="0">
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
            <button type="button" 
                    id="reset-form"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                <i class="fas fa-undo mr-2"></i>Reset
            </button>
            <button type="button" 
                    id="add_savings" 
                    name="add_savings"
                    class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                <i class="fas fa-save mr-2"></i>Save Changes
            </button>
        </div>
    </form>
</div>

<!-- Hidden Fields -->
<input type="hidden" name="coop_id" id="coop_id" value="<?php echo htmlspecialchars($coopid); ?>">
<input type="hidden" name="period_id" id="period_id" value="<?php echo htmlspecialchars($period); ?>">

<script>
$(document).ready(function() {
    // Store original values for reset functionality
    let originalDeduction = $('#deduction').val();
    let originalSavings = $('#savings').val();

    // Reset form functionality
    $('#reset-form').on('click', function() {
        $('#deduction').val(originalDeduction);
        $('#savings').val(originalSavings);
        
        // Add visual feedback
        $(this).addClass('bg-gray-200');
        setTimeout(() => {
            $(this).removeClass('bg-gray-200');
        }, 200);
    });

    // Real-time calculation display
    function updateCalculation() {
        const deduction = parseFloat($('#deduction').val()) || 0;
        const savings = parseFloat($('#savings').val()) || 0;
        const total = deduction + savings;
        
        // Update the display if there's a calculation element
        if ($('#live-total').length) {
            $('#live-total').text('₦' + total.toLocaleString('en-NG', {minimumFractionDigits: 2}));
        }
    }

    // Add live calculation
    $('#deduction, #savings').on('input', updateCalculation);

    // Form validation and submission
    $('#add_savings').on('click', function() {
        const deduction = $('#deduction').val();
        const savings = $('#savings').val();
        const coop_id = $('#coop_id').val();
        const period_id = $('#period_id').val();

        // Validation
        if (!deduction || deduction === '') {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please enter the deduction amount',
                confirmButtonColor: '#ef4444'
            });
            $('#deduction').focus();
            return false;
        }

        if (!savings || savings === '') {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please enter the savings amount',
                confirmButtonColor: '#ef4444'
            });
            $('#savings').focus();
            return false;
        }

        if (!period_id) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please select a period',
                confirmButtonColor: '#ef4444'
            });
            return false;
        }

        // Show loading state
        const $btn = $(this);
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Saving...');

        // Submit data
        $.post("classes/controller.php?act=update_deductions", {
            deduction: deduction,
            saving: savings,
            coop_id: coop_id,
            period_id: period_id
        })
        .done(function(data) {
            if (data == 1) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: `Deduction (₦${parseFloat(deduction).toLocaleString('en-NG', {minimumFractionDigits: 2})}) and Savings (₦${parseFloat(savings).toLocaleString('en-NG', {minimumFractionDigits: 2})}) saved successfully!`,
                    confirmButtonColor: '#10b981'
                });
                
                // Update original values for reset
                originalDeduction = deduction;
                originalSavings = savings;
                
                // Refresh the deduction details
                if (window.deductionManager) {
                    window.deductionManager.loadDeductionDetails();
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to save deductions: ' + data,
                    confirmButtonColor: '#ef4444'
                });
            }
        })
        .fail(function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to save deductions: ' + error,
                confirmButtonColor: '#ef4444'
            });
        })
        .always(function() {
            // Reset button state
            $btn.prop('disabled', false).html(originalText);
        });
    });

    // Add number formatting on blur
    $('#deduction, #savings').on('blur', function() {
        const value = parseFloat($(this).val());
        if (!isNaN(value)) {
            $(this).val(value.toFixed(2));
        }
    });

    // Add keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+S to save
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            $('#add_savings').click();
        }
        
        // Escape to reset
        if (e.key === 'Escape') {
            $('#reset-form').click();
        }
    });
});
</script>