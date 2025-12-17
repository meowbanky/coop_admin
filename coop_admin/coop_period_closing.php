<?php

require_once('Connections/coop.php');
require_once('libs/services/PeriodClosingProcessor.php');
require_once('libs/services/AccountBalanceCalculator.php');
require_once('includes/header.php');

$closingProcessor = new PeriodClosingProcessor($coop, $database);
$calculator = new AccountBalanceCalculator($coop, $database);

// Get periods
$periods = [];
$periodQuery = "SELECT id, PayrollPeriod FROM tbpayrollperiods ORDER BY id DESC";
$periodResult = mysqli_query($coop, $periodQuery);
if ($periodResult) {
    while ($row = mysqli_fetch_assoc($periodResult)) {
        $periods[] = $row;
    }
}

$selectedPeriod = isset($_GET['periodid']) ? intval($_GET['periodid']) : 0;
$selectedPeriodName = '';
foreach ($periods as $period) {
    if ($period['id'] == $selectedPeriod) {
        $selectedPeriodName = $period['PayrollPeriod'];
        break;
    }
}

// Get validation if period selected
$validation = null;
$periodStatus = null;
$surplus = 0;

if ($selectedPeriod > 0) {
    $validation = $closingProcessor->validatePeriodForClosing($selectedPeriod);
    $periodStatus = $closingProcessor->getPeriodStatus($selectedPeriod);
    
    // Calculate surplus
    $revenue = $calculator->getTotalByType($selectedPeriod, 'revenue');
    $expenses = $calculator->getTotalByType($selectedPeriod, 'expense');
    $surplus = $revenue - $expenses;
}
?>

<div class="container mx-auto px-4 py-8 max-w-5xl">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h1 class="text-3xl font-bold text-blue-900">🔒 Period Closing</h1>
        <p class="text-gray-600 mt-1">Close accounting periods and process surplus appropriation</p>
    </div>

    <!-- Period Selection -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Select Period to Close <span class="text-red-500">*</span>
                    </label>
                    <select name="periodid" class="w-full border border-gray-300 rounded-lg px-4 py-2" onchange="this.form.submit()">
                        <option value="">-- Select Period --</option>
                        <?php foreach ($periods as $period): ?>
                            <option value="<?php echo $period['id']; ?>" <?php echo ($period['id'] == $selectedPeriod) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($period['PayrollPeriod']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <?php if ($selectedPeriod > 0): ?>

        <!-- Period Status -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Period Status</h3>
            
            <?php if ($periodStatus && $periodStatus['is_closed']): ?>
                <div class="bg-red-100 border-l-4 border-red-500 p-4 rounded">
                    <div class="flex items-center">
                        <div class="text-red-500 text-2xl mr-3">🔒</div>
                        <div>
                            <p class="font-bold text-red-800">Period is CLOSED</p>
                            <p class="text-sm text-red-700">Closed on: <?php echo date('d M Y, h:i A', strtotime($periodStatus['closed_at'])); ?></p>
                            <p class="text-sm text-red-700">By User ID: <?php echo $periodStatus['closed_by']; ?></p>
                        </div>
                    </div>
                    <button onclick="reopenPeriod()" class="mt-4 bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded text-sm">
                        <i class="fa fa-unlock mr-1"></i> Reopen Period
                    </button>
                </div>
            <?php else: ?>
                <div class="bg-green-100 border-l-4 border-green-500 p-4 rounded">
                    <div class="flex items-center">
                        <div class="text-green-500 text-2xl mr-3">🔓</div>
                        <div>
                            <p class="font-bold text-green-800">Period is OPEN</p>
                            <p class="text-sm text-green-700">Ready for closing process</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pre-Closing Validation -->
        <?php if (!$periodStatus['is_closed']): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Pre-Closing Validation</h3>
                
                <?php if ($validation['can_close']): ?>
                    <div class="bg-green-50 border border-green-300 rounded-lg p-4 mb-4">
                        <p class="text-green-800 font-bold mb-2">✓ All validation checks passed!</p>
                        <p class="text-sm text-green-700">Period is ready to be closed.</p>
                    </div>
                <?php else: ?>
                    <div class="bg-red-50 border border-red-300 rounded-lg p-4 mb-4">
                        <p class="text-red-800 font-bold mb-2">✗ Issues found - Cannot close period</p>
                        <ul class="list-disc list-inside text-sm text-red-700 mt-2">
                            <?php foreach ($validation['issues'] as $issue): ?>
                                <li><?php echo htmlspecialchars($issue); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($validation['warnings'])): ?>
                    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4">
                        <p class="text-yellow-800 font-bold mb-2">⚠️ Warnings:</p>
                        <ul class="list-disc list-inside text-sm text-yellow-700">
                            <?php foreach ($validation['warnings'] as $warning): ?>
                                <li><?php echo htmlspecialchars($warning); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Surplus Appropriation Form -->
            <?php if ($validation['can_close'] && $surplus > 0): ?>
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Surplus Appropriation</h3>
                    <p class="text-sm text-gray-600 mb-4">Surplus for the period: <span class="font-bold text-green-600">₦<?php echo number_format($surplus, 2); ?></span></p>
                    
                    <form id="appropriationForm">
                        <input type="hidden" name="periodid" value="<?php echo $selectedPeriod; ?>">
                        <input type="hidden" name="surplus_amount" value="<?php echo $surplus; ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Dividend (₦)</label>
                                <input type="number" name="dividend" step="0.01" min="0" max="<?php echo $surplus; ?>" 
                                       class="w-full border rounded px-3 py-2 appropriation-input" placeholder="0.00">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Interest to Members (₦)</label>
                                <input type="number" name="interest_to_members" step="0.01" min="0" max="<?php echo $surplus; ?>" 
                                       class="w-full border rounded px-3 py-2 appropriation-input" placeholder="0.00">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Reserve Fund (₦)</label>
                                <input type="number" name="reserve_fund" step="0.01" min="0" max="<?php echo $surplus; ?>" 
                                       class="w-full border rounded px-3 py-2 appropriation-input" placeholder="0.00">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Bonus (₦)</label>
                                <input type="number" name="bonus" step="0.01" min="0" max="<?php echo $surplus; ?>" 
                                       class="w-full border rounded px-3 py-2 appropriation-input" placeholder="0.00">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Education Fund (₦)</label>
                                <input type="number" name="education_fund" step="0.01" min="0" max="<?php echo $surplus; ?>" 
                                       class="w-full border rounded px-3 py-2 appropriation-input" placeholder="0.00">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Honorarium (₦)</label>
                                <input type="number" name="honorarium" step="0.01" min="0" max="<?php echo $surplus; ?>" 
                                       class="w-full border rounded px-3 py-2 appropriation-input" placeholder="0.00">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">General Reserve (₦)</label>
                                <input type="number" name="general_reserve" step="0.01" min="0" max="<?php echo $surplus; ?>" 
                                       class="w-full border rounded px-3 py-2 appropriation-input" placeholder="0.00">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Welfare Fund (₦)</label>
                                <input type="number" name="welfare_fund" step="0.01" min="0" max="<?php echo $surplus; ?>" 
                                       class="w-full border rounded px-3 py-2 appropriation-input" placeholder="0.00">
                            </div>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="font-semibold">Total Surplus:</span>
                                <span class="font-bold">₦<?php echo number_format($surplus, 2); ?></span>
                            </div>
                            <div class="flex justify-between text-sm mt-1">
                                <span class="font-semibold">Total Appropriated:</span>
                                <span id="totalAppropriated" class="font-bold text-blue-900">₦0.00</span>
                            </div>
                            <div class="flex justify-between text-sm mt-1 pt-2 border-t border-blue-300">
                                <span class="font-semibold">Retained Earnings:</span>
                                <span id="retainedEarnings" class="font-bold text-green-900">₦<?php echo number_format($surplus, 2); ?></span>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Close Period Button -->
            <?php if ($validation['can_close'] && !$periodStatus['is_closed']): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <button onclick="closePeriod()" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-4 rounded-lg text-lg">
                        <i class="fa fa-lock mr-2"></i> Close Period: <?php echo htmlspecialchars($selectedPeriodName); ?>
                    </button>
                    <p class="text-xs text-gray-500 text-center mt-2">This action will lock the period and prevent further edits</p>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    <?php else: ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
            <div class="flex items-center">
                <div class="text-yellow-400 text-3xl mr-4">⚠️</div>
                <div>
                    <h3 class="text-lg font-semibold text-yellow-800">No Period Selected</h3>
                    <p class="text-yellow-700 mt-1">Please select a period to close.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const surplus = <?php echo $surplus; ?>;

// Calculate appropriation totals
function calculateAppropriation() {
    let total = 0;
    document.querySelectorAll('.appropriation-input').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    
    const retained = surplus - total;
    
    document.getElementById('totalAppropriated').textContent = '₦' + total.toFixed(2);
    document.getElementById('retainedEarnings').textContent = '₦' + retained.toFixed(2);
    
    // Validate
    if (total > surplus) {
        document.getElementById('retainedEarnings').className = 'font-bold text-red-900';
    } else {
        document.getElementById('retainedEarnings').className = 'font-bold text-green-900';
    }
}

// Attach listeners
document.querySelectorAll('.appropriation-input').forEach(input => {
    input.addEventListener('input', calculateAppropriation);
});

// Close period
async function closePeriod() {
    const formData = new FormData(document.getElementById('appropriationForm'));
    
    // Confirm
    const result = await Swal.fire({
        title: 'Close Period?',
        html: `
            <p>You are about to close: <strong><?php echo htmlspecialchars($selectedPeriodName); ?></strong></p>
            <p class="text-sm text-red-600 mt-2">⚠️ This will lock the period and prevent further edits.</p>
            <p class="text-sm mt-2">Are you sure you want to continue?</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Close Period',
        cancelButtonText: 'Cancel'
    });
    
    if (!result.isConfirmed) return;
    
    // Show loading
    Swal.fire({
        title: 'Closing Period...',
        html: 'Processing closing entries and locking period',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    try {
        const response = await fetch('api/close_period.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Period Closed!',
                html: `
                    <p class="mb-2">Period has been successfully closed.</p>
                    ${data.entries_created ? `<p class="text-sm">Journal entries created: ${data.entries_created.length}</p>` : ''}
                    <p class="text-green-600 mt-2">✓ Period is now locked</p>
                `,
                confirmButtonColor: '#16a34a'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error Closing Period',
                text: data.error || 'An error occurred',
                confirmButtonColor: '#dc2626'
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Could not connect to server',
            confirmButtonColor: '#dc2626'
        });
    }
}

// Reopen period
async function reopenPeriod() {
    const { value: reason } = await Swal.fire({
        title: 'Reopen Period?',
        input: 'textarea',
        inputLabel: 'Reason for reopening (required)',
        inputPlaceholder: 'E.g., Correction needed, data entry error...',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        inputValidator: (value) => {
            if (!value) return 'You must provide a reason!';
        }
    });
    
    if (!reason) return;
    
    Swal.fire({
        title: 'Reopening Period...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    try {
        const response = await fetch('api/reopen_period.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                periodid: <?php echo $selectedPeriod; ?>,
                reason: reason
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Period Reopened!',
                text: 'Period is now open for editing',
                confirmButtonColor: '#16a34a'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.error,
                confirmButtonColor: '#dc2626'
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            confirmButtonColor: '#dc2626'
        });
    }
}
</script>

<?php require_once('includes/footer.php'); ?>

