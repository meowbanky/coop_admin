<?php

require_once('Connections/coop.php');
require_once('libs/services/BankReconciliationService.php');
require_once('libs/services/AccountBalanceCalculator.php');
require_once('includes/header.php');

$reconService = new BankReconciliationService($coop, $database);
$calculator = new AccountBalanceCalculator($coop, $database);

// Get bank accounts
$bankAccounts = [];
$bankQuery = "SELECT id, account_code, account_name 
              FROM coop_accounts 
              WHERE account_code LIKE '110%' AND is_active = TRUE
              ORDER BY account_code";
$bankResult = mysqli_query($coop, $bankQuery);
if ($bankResult) {
    while ($row = mysqli_fetch_assoc($bankResult)) {
        $bankAccounts[] = $row;
    }
}

// Get periods
$periods = [];
$periodQuery = "SELECT id, PayrollPeriod FROM tbpayrollperiods ORDER BY id DESC";
$periodResult = mysqli_query($coop, $periodQuery);
if ($periodResult) {
    while ($row = mysqli_fetch_assoc($periodResult)) {
        $periods[] = $row;
    }
}

// Get reconciliation history
$history = $reconService->getReconciliationHistory(null, 10);
?>

<div class="container mx-auto px-4 py-8 max-w-6xl">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h1 class="text-3xl font-bold text-blue-900">🏦 Bank Reconciliation</h1>
        <p class="text-gray-600 mt-1">Match your books with bank statements</p>
    </div>

    <!-- Reconciliation Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">New Reconciliation</h2>

        <form id="reconForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Bank Account <span class="text-red-500">*</span>
                    </label>
                    <select name="bank_account_id" id="bankAccountSelect"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                        <option value="">-- Select Bank Account --</option>
                        <?php foreach ($bankAccounts as $account): ?>
                        <option value="<?php echo $account['id']; ?>">
                            <?php echo htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Period <span class="text-red-500">*</span>
                    </label>
                    <select name="periodid" id="periodSelect" class="w-full border border-gray-300 rounded-lg px-4 py-2"
                        required>
                        <option value="">-- Select Period --</option>
                        <?php foreach ($periods as $period): ?>
                        <option value="<?php echo $period['id']; ?>">
                            <?php echo htmlspecialchars($period['PayrollPeriod']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Reconciliation Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="reconciliation_date" value="<?php echo date('Y-m-d'); ?>"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Book Balance (₦) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="book_balance" id="bookBalance" step="0.01"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 font-mono" placeholder="0.00"
                        readonly>
                    <p class="text-xs text-gray-500 mt-1">Auto-calculated from your accounts</p>
                </div>

                <div class="md:col-span-2 border-t pt-4">
                    <h3 class="font-bold text-gray-900 mb-3">Bank Statement Details</h3>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Bank Statement Balance (₦) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="bank_statement_balance" id="bankBalance" step="0.01"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 font-mono" placeholder="0.00"
                        required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Outstanding Deposits (₦)
                    </label>
                    <input type="number" name="outstanding_deposits" id="outstandingDeposits" step="0.01" min="0"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 font-mono recon-input"
                        placeholder="0.00">
                    <p class="text-xs text-gray-500 mt-1">Deposits made but not yet on statement</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Outstanding Withdrawals (₦)
                    </label>
                    <input type="number" name="outstanding_withdrawals" id="outstandingWithdrawals" step="0.01" min="0"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 font-mono recon-input"
                        placeholder="0.00">
                    <p class="text-xs text-gray-500 mt-1">Checks/payments not yet cleared</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Bank Charges (₦)
                    </label>
                    <input type="number" name="bank_charges" id="bankCharges" step="0.01" min="0"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 font-mono recon-input"
                        placeholder="0.00">
                    <p class="text-xs text-gray-500 mt-1">Charges not yet recorded in books</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Bank Interest (₦)
                    </label>
                    <input type="number" name="bank_interest" id="bankInterest" step="0.01" min="0"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 font-mono recon-input"
                        placeholder="0.00">
                    <p class="text-xs text-gray-500 mt-1">Interest earned not yet recorded</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2"
                        placeholder="Any additional notes about this reconciliation..."></textarea>
                </div>
            </div>

            <!-- Reconciliation Calculation -->
            <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-6 mb-4">
                <h3 class="font-bold text-gray-900 mb-4">Reconciliation Calculation</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Bank Side -->
                    <div class="bg-white rounded-lg p-4">
                        <h4 class="font-semibold text-blue-900 mb-3">Bank Statement Side</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Bank Statement Balance:</span>
                                <span id="displayBankBalance" class="font-mono">₦0.00</span>
                            </div>
                            <div class="flex justify-between text-green-600">
                                <span>Add: Outstanding Deposits:</span>
                                <span id="displayOutDeposits" class="font-mono">₦0.00</span>
                            </div>
                            <div class="flex justify-between text-red-600">
                                <span>Less: Outstanding Withdrawals:</span>
                                <span id="displayOutWithdrawals" class="font-mono">₦0.00</span>
                            </div>
                            <div class="flex justify-between font-bold text-lg pt-2 border-t">
                                <span>Adjusted Bank Balance:</span>
                                <span id="adjustedBankBalance" class="font-mono">₦0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Book Side -->
                    <div class="bg-white rounded-lg p-4">
                        <h4 class="font-semibold text-purple-900 mb-3">Book Balance Side</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Book Balance:</span>
                                <span id="displayBookBalance" class="font-mono">₦0.00</span>
                            </div>
                            <div class="flex justify-between text-green-600">
                                <span>Add: Bank Interest:</span>
                                <span id="displayBankInterest" class="font-mono">₦0.00</span>
                            </div>
                            <div class="flex justify-between text-red-600">
                                <span>Less: Bank Charges:</span>
                                <span id="displayBankCharges" class="font-mono">₦0.00</span>
                            </div>
                            <div class="flex justify-between font-bold text-lg pt-2 border-t">
                                <span>Adjusted Book Balance:</span>
                                <span id="adjustedBookBalance" class="font-mono">₦0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Variance -->
                <div class="mt-4 p-4 rounded-lg" id="varianceDisplay">
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-lg">VARIANCE:</span>
                        <span id="variance" class="font-bold text-2xl font-mono">₦0.00</span>
                    </div>
                    <p id="varianceStatus" class="text-sm mt-2"></p>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end gap-4">
                <button type="button" onclick="resetForm()"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                    <i class="fa fa-redo mr-1"></i> Reset
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                    <i class="fa fa-check mr-1"></i> Complete Reconciliation
                </button>
            </div>
        </form>
    </div>

    <!-- Reconciliation History -->
    <?php if (count($history) > 0): ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-teal-600 to-teal-700 text-white">
            <h2 class="text-xl font-bold">Reconciliation History</h2>
            <p class="text-sm text-teal-100">Recent reconciliations</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Account</th>
                        <th class="px-4 py-3 text-left">Period</th>
                        <th class="px-4 py-3 text-right">Bank Balance</th>
                        <th class="px-4 py-3 text-right">Book Balance</th>
                        <th class="px-4 py-3 text-right">Variance</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($history as $recon): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3"><?php echo date('d M Y', strtotime($recon['reconciliation_date'])); ?>
                        </td>
                        <td class="px-4 py-3 font-mono text-sm"><?php echo htmlspecialchars($recon['account_code']); ?>
                        </td>
                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($recon['PayrollPeriod']); ?></td>
                        <td class="px-4 py-3 text-right font-mono">
                            ₦<?php echo number_format($recon['bank_statement_balance'], 2); ?></td>
                        <td class="px-4 py-3 text-right font-mono">
                            ₦<?php echo number_format($recon['book_balance'], 2); ?></td>
                        <td
                            class="px-4 py-3 text-right font-mono <?php echo abs($recon['variance']) < 0.01 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo abs($recon['variance']) < 0.01 ? '-' : '₦' . number_format(abs($recon['variance']), 2); ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($recon['is_balanced']): ?>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                ✓ Balanced
                            </span>
                            <?php else: ?>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                ✗ Variance
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Auto-fetch book balance when account and period selected
document.getElementById('bankAccountSelect').addEventListener('change', fetchBookBalance);
document.getElementById('periodSelect').addEventListener('change', fetchBookBalance);

async function fetchBookBalance() {
    const accountId = document.getElementById('bankAccountSelect').value;
    const periodid = document.getElementById('periodSelect').value;

    if (!accountId || !periodid) return;

    try {
        const response = await fetch(`api/get_book_balance.php?account_id=${accountId}&periodid=${periodid}`);
        const data = await response.json();

        if (data.success) {
            document.getElementById('bookBalance').value = data.balance.toFixed(2);
            calculateReconciliation();
        }
    } catch (error) {
        console.error('Error fetching book balance:', error);
    }
}

// Calculate reconciliation in real-time
function calculateReconciliation() {
    const bookBalance = parseFloat(document.getElementById('bookBalance').value) || 0;
    const bankBalance = parseFloat(document.getElementById('bankBalance').value) || 0;
    const outDeposits = parseFloat(document.getElementById('outstandingDeposits').value) || 0;
    const outWithdrawals = parseFloat(document.getElementById('outstandingWithdrawals').value) || 0;
    const bankCharges = parseFloat(document.getElementById('bankCharges').value) || 0;
    const bankInterest = parseFloat(document.getElementById('bankInterest').value) || 0;

    // Display values
    document.getElementById('displayBankBalance').textContent = '₦' + bankBalance.toFixed(2);
    document.getElementById('displayOutDeposits').textContent = '₦' + outDeposits.toFixed(2);
    document.getElementById('displayOutWithdrawals').textContent = '₦' + outWithdrawals.toFixed(2);
    document.getElementById('displayBookBalance').textContent = '₦' + bookBalance.toFixed(2);
    document.getElementById('displayBankCharges').textContent = '₦' + bankCharges.toFixed(2);
    document.getElementById('displayBankInterest').textContent = '₦' + bankInterest.toFixed(2);

    // Calculate adjusted balances
    const adjustedBankBal = bankBalance + outDeposits - outWithdrawals;
    const adjustedBookBal = bookBalance + bankInterest - bankCharges;

    document.getElementById('adjustedBankBalance').textContent = '₦' + adjustedBankBal.toFixed(2);
    document.getElementById('adjustedBookBalance').textContent = '₦' + adjustedBookBal.toFixed(2);

    // Calculate variance
    const variance = adjustedBankBal - adjustedBookBal;
    const varianceDisplay = document.getElementById('varianceDisplay');
    const varianceValue = document.getElementById('variance');
    const varianceStatus = document.getElementById('varianceStatus');

    varianceValue.textContent = '₦' + Math.abs(variance).toFixed(2);

    if (Math.abs(variance) < 0.01) {
        varianceDisplay.className = 'mt-4 p-4 rounded-lg bg-green-100 border-2 border-green-500';
        varianceValue.className = 'font-bold text-2xl font-mono text-green-900';
        varianceStatus.textContent = '✓ Reconciliation is balanced!';
        varianceStatus.className = 'text-sm mt-2 text-green-800';
    } else {
        varianceDisplay.className = 'mt-4 p-4 rounded-lg bg-red-100 border-2 border-red-500';
        varianceValue.className = 'font-bold text-2xl font-mono text-red-900';
        varianceStatus.textContent = variance > 0 ?
            '⚠️ Bank balance is higher - Check for unrecorded income or overstated expenses' :
            '⚠️ Book balance is higher - Check for unrecorded expenses or overstated income';
        varianceStatus.className = 'text-sm mt-2 text-red-800';
    }
}

// Attach event listeners
document.querySelectorAll('.recon-input').forEach(input => {
    input.addEventListener('input', calculateReconciliation);
});
document.getElementById('bankBalance').addEventListener('input', calculateReconciliation);

function resetForm() {
    document.getElementById('reconForm').reset();
    calculateReconciliation();
}

// Submit form
document.getElementById('reconForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('reconciled_by', <?php echo $_SESSION['user_id']; ?>);

    Swal.fire({
        title: 'Processing...',
        text: 'Creating bank reconciliation',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch('api/create_bank_reconciliation.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Reconciliation Complete!',
                html: `
                    <p class="mb-2">Bank reconciliation has been saved.</p>
                    <p class="text-sm ${result.is_balanced ? 'text-green-600' : 'text-red-600'}">
                        ${result.is_balanced ? '✓ Balanced' : '✗ Variance: ₦' + Math.abs(result.variance).toFixed(2)}
                    </p>
                    ${result.adjusting_entries ? '<p class="text-xs text-blue-600 mt-2">Adjusting journal entries created</p>' : ''}
                `,
                confirmButtonColor: '#16a34a'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.error || 'An error occurred',
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
});
</script>

<?php require_once('includes/footer.php'); ?>