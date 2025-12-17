<?php

require_once('Connections/coop.php');
require_once('includes/header.php');

// Get periods for dropdown
$periods = [];
$periodQuery = "SELECT id, PayrollPeriod FROM tbpayrollperiods ORDER BY id DESC";
$periodResult = mysqli_query($coop, $periodQuery);
if ($periodResult) {
    while ($row = mysqli_fetch_assoc($periodResult)) {
        $periods[] = $row;
    }
}

// Get all active accounts for dropdown
$accountsQuery = "SELECT id, account_code, account_name, account_type, normal_balance 
                  FROM coop_accounts 
                  WHERE is_active = TRUE 
                  AND is_control_account = FALSE
                  ORDER BY account_code";
$accountsResult = mysqli_query($coop, $accountsQuery);
$accounts = [];
if ($accountsResult) {
    while ($row = mysqli_fetch_assoc($accountsResult)) {
        $accounts[] = $row;
    }
}
?>

<div class="container mx-auto px-4 py-8 max-w-5xl">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-blue-900">✍️ Manual Journal Entry</h1>
                <p class="text-gray-600 mt-1">Create accounting entries for expenses, adjustments, and other transactions</p>
            </div>
            <a href="coop_journal_entries.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                <i class="fa fa-list mr-1"></i> View All Entries
            </a>
        </div>
    </div>

    <!-- Quick Examples -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded">
        <h3 class="font-bold text-blue-900 mb-2">💡 Common Examples:</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-sm text-blue-800">
            <div><strong>Salary Payment:</strong> DR Salary (6011) | CR Bank (1102)</div>
            <div><strong>Utility Bill:</strong> DR Office Exp (6008) | CR Bank (1102)</div>
            <div><strong>Asset Purchase:</strong> DR Asset (12XX) | CR Bank (1102)</div>
        </div>
    </div>

    <!-- Journal Entry Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <form id="journalEntryForm">
            <!-- Entry Header -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 pb-6 border-b">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Period <span class="text-red-500">*</span>
                    </label>
                    <select name="periodid" id="periodid" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
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
                        Entry Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="entry_date" id="entry_date" value="<?php echo date('Y-m-d'); ?>" 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Description <span class="text-red-500">*</span>
                    </label>
                    <textarea name="description" id="description" rows="2" 
                              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" 
                              placeholder="E.g., Monthly salary payment for October 2024"
                              required></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Entry Type
                    </label>
                    <select name="entry_type" id="entry_type" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="manual">Manual</option>
                        <option value="adjustment">Adjustment</option>
                        <option value="system">System</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Source Document
                    </label>
                    <input type="text" name="source_document" id="source_document" 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                           placeholder="E.g., Invoice #123, Receipt #456">
                </div>
            </div>

            <!-- Journal Entry Lines -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Journal Entry Lines</h3>
                    <div class="text-sm text-gray-600">
                        <span class="mr-4">Total Debits: <span id="totalDebits" class="font-bold text-blue-900">₦0.00</span></span>
                        <span>Total Credits: <span id="totalCredits" class="font-bold text-purple-900">₦0.00</span></span>
                    </div>
                </div>

                <div id="journalLines">
                    <!-- Line 1 (Debit) -->
                    <div class="grid grid-cols-12 gap-2 mb-3 items-end journal-line" data-line="1">
                        <div class="col-span-1 text-center font-bold text-gray-700">1</div>
                        <div class="col-span-5">
                            <label class="block text-xs text-gray-600 mb-1">Account</label>
                            <select name="lines[0][account_id]" class="w-full border rounded px-2 py-2 text-sm account-select" required>
                                <option value="">-- Select Account --</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>" 
                                            data-type="<?php echo $account['account_type']; ?>"
                                            data-balance="<?php echo $account['normal_balance']; ?>">
                                        <?php echo htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-600 mb-1">Debit (₦)</label>
                            <input type="number" name="lines[0][debit]" step="0.01" min="0" 
                                   class="w-full border rounded px-2 py-2 text-sm text-right debit-input"
                                   placeholder="0.00">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-600 mb-1">Credit (₦)</label>
                            <input type="number" name="lines[0][credit]" step="0.01" min="0" 
                                   class="w-full border rounded px-2 py-2 text-sm text-right credit-input"
                                   placeholder="0.00">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-600 mb-1">Description</label>
                            <input type="text" name="lines[0][description]" 
                                   class="w-full border rounded px-2 py-2 text-sm"
                                   placeholder="Optional">
                        </div>
                    </div>

                    <!-- Line 2 (Credit) -->
                    <div class="grid grid-cols-12 gap-2 mb-3 items-end journal-line" data-line="2">
                        <div class="col-span-1 text-center font-bold text-gray-700">2</div>
                        <div class="col-span-5">
                            <select name="lines[1][account_id]" class="w-full border rounded px-2 py-2 text-sm account-select" required>
                                <option value="">-- Select Account --</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>">
                                        <?php echo htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <input type="number" name="lines[1][debit]" step="0.01" min="0" 
                                   class="w-full border rounded px-2 py-2 text-sm text-right debit-input"
                                   placeholder="0.00">
                        </div>
                        <div class="col-span-2">
                            <input type="number" name="lines[1][credit]" step="0.01" min="0" 
                                   class="w-full border rounded px-2 py-2 text-sm text-right credit-input"
                                   placeholder="0.00">
                        </div>
                        <div class="col-span-2">
                            <input type="text" name="lines[1][description]" 
                                   class="w-full border rounded px-2 py-2 text-sm"
                                   placeholder="Optional">
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center mt-4">
                    <button type="button" onclick="addLine()" class="text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fa fa-plus-circle mr-1"></i> Add Another Line
                    </button>
                    
                    <div class="text-right">
                        <div id="balanceStatus" class="text-sm mb-2"></div>
                        <div class="text-lg font-bold" id="difference"></div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4 justify-end">
                <button type="button" onclick="resetForm()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                    <i class="fa fa-redo mr-1"></i> Reset
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                    <i class="fa fa-save mr-1"></i> Create & Post Entry
                </button>
            </div>
        </form>
    </div>

    <!-- Quick Reference -->
    <div class="mt-6 bg-gray-50 rounded-lg p-4">
        <h4 class="font-bold text-gray-900 mb-2">📖 Quick Reference - Common Account Codes</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 text-xs">
            <div><strong>1102</strong> - Bank Main Account</div>
            <div><strong>1110</strong> - Member Loans</div>
            <div><strong>6011</strong> - Salary Cost</div>
            <div><strong>6002</strong> - Printing & Stationery</div>
            <div><strong>6003</strong> - Telephone</div>
            <div><strong>6004</strong> - Internet Cost</div>
            <div><strong>6008</strong> - Office Expenses</div>
            <div><strong>6009</strong> - Bank Charges</div>
            <div><strong>6010</strong> - Fueling</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let lineCount = 2;

// Calculate totals in real-time
function calculateTotals() {
    let totalDebits = 0;
    let totalCredits = 0;
    
    document.querySelectorAll('.debit-input').forEach(input => {
        totalDebits += parseFloat(input.value) || 0;
    });
    
    document.querySelectorAll('.credit-input').forEach(input => {
        totalCredits += parseFloat(input.value) || 0;
    });
    
    document.getElementById('totalDebits').textContent = '₦' + totalDebits.toFixed(2);
    document.getElementById('totalCredits').textContent = '₦' + totalCredits.toFixed(2);
    
    const difference = totalDebits - totalCredits;
    const diffElement = document.getElementById('difference');
    const statusElement = document.getElementById('balanceStatus');
    
    if (Math.abs(difference) < 0.01) {
        diffElement.className = 'text-lg font-bold text-green-600';
        diffElement.textContent = '✓ Balanced';
        statusElement.textContent = 'Entry is balanced and ready to post';
        statusElement.className = 'text-sm mb-2 text-green-600';
    } else {
        diffElement.className = 'text-lg font-bold text-red-600';
        diffElement.textContent = '✗ Out of Balance: ₦' + Math.abs(difference).toFixed(2);
        statusElement.textContent = difference > 0 ? 'Need more credits' : 'Need more debits';
        statusElement.className = 'text-sm mb-2 text-red-600';
    }
}

// Add new line
function addLine() {
    lineCount++;
    const container = document.getElementById('journalLines');
    const newLine = document.createElement('div');
    newLine.className = 'grid grid-cols-12 gap-2 mb-3 items-end journal-line';
    newLine.setAttribute('data-line', lineCount);
    
    newLine.innerHTML = `
        <div class="col-span-1 text-center font-bold text-gray-700">${lineCount}</div>
        <div class="col-span-5">
            <select name="lines[${lineCount - 1}][account_id]" class="w-full border rounded px-2 py-2 text-sm account-select" required>
                <option value="">-- Select Account --</option>
                <?php foreach ($accounts as $account): ?>
                    <option value="<?php echo $account['id']; ?>">
                        <?php echo htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-span-2">
            <input type="number" name="lines[${lineCount - 1}][debit]" step="0.01" min="0" 
                   class="w-full border rounded px-2 py-2 text-sm text-right debit-input"
                   placeholder="0.00">
        </div>
        <div class="col-span-2">
            <input type="number" name="lines[${lineCount - 1}][credit]" step="0.01" min="0" 
                   class="w-full border rounded px-2 py-2 text-sm text-right credit-input"
                   placeholder="0.00">
        </div>
        <div class="col-span-1">
            <input type="text" name="lines[${lineCount - 1}][description]" 
                   class="w-full border rounded px-2 py-2 text-sm"
                   placeholder="Optional">
        </div>
        <div class="col-span-1">
            <button type="button" onclick="removeLine(${lineCount})" 
                    class="w-full bg-red-500 hover:bg-red-600 text-white px-2 py-2 rounded">
                <i class="fa fa-trash"></i>
            </button>
        </div>
    `;
    
    container.appendChild(newLine);
    
    // Attach event listeners to new inputs
    newLine.querySelectorAll('.debit-input, .credit-input').forEach(input => {
        input.addEventListener('input', calculateTotals);
        input.addEventListener('input', enforceDebitOrCredit);
    });
}

// Remove line
function removeLine(lineNumber) {
    const line = document.querySelector(`[data-line="${lineNumber}"]`);
    if (line) {
        line.remove();
        calculateTotals();
    }
}

// Ensure only debit OR credit is filled (not both)
function enforceDebitOrCredit(e) {
    const row = e.target.closest('.journal-line');
    if (!row) return;
    
    const debitInput = row.querySelector('.debit-input');
    const creditInput = row.querySelector('.credit-input');
    
    if (e.target.classList.contains('debit-input') && parseFloat(e.target.value) > 0) {
        creditInput.value = '';
    } else if (e.target.classList.contains('credit-input') && parseFloat(e.target.value) > 0) {
        debitInput.value = '';
    }
}

// Reset form
function resetForm() {
    document.getElementById('journalEntryForm').reset();
    const container = document.getElementById('journalLines');
    container.querySelectorAll('.journal-line').forEach((line, index) => {
        if (index > 1) { // Keep first 2 lines
            line.remove();
        }
    });
    lineCount = 2;
    calculateTotals();
}

// Submit form
document.getElementById('journalEntryForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Validate balance
    const debits = parseFloat(document.getElementById('totalDebits').textContent.replace('₦', ''));
    const credits = parseFloat(document.getElementById('totalCredits').textContent.replace('₦', ''));
    
    if (Math.abs(debits - credits) > 0.01) {
        Swal.fire({
            icon: 'error',
            title: 'Entry Not Balanced',
            text: 'Total debits must equal total credits before posting.',
            confirmButtonColor: '#dc2626'
        });
        return;
    }
    
    if (debits === 0 || credits === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Entry',
            text: 'Please enter at least one debit and one credit amount.',
            confirmButtonColor: '#dc2626'
        });
        return;
    }
    
    // Collect form data
    const formData = new FormData(this);
    
    // Show loading
    Swal.fire({
        title: 'Creating Journal Entry...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    try {
        const response = await fetch('api/create_journal_entry.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Entry Created!',
                html: `
                    <p>Entry Number: <strong>${result.entry_number}</strong></p>
                    <p>Total Amount: <strong>₦${result.total_amount.toLocaleString('en-NG', {minimumFractionDigits: 2})}</strong></p>
                    <p class="text-green-600 mt-2">✓ Entry has been posted to accounts</p>
                `,
                confirmButtonColor: '#16a34a'
            }).then(() => {
                // Reset form or redirect
                resetForm();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error Creating Entry',
                text: result.error || 'An unknown error occurred',
                confirmButtonColor: '#dc2626'
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Could not connect to server. Please try again.',
            confirmButtonColor: '#dc2626'
        });
    }
});

// Attach event listeners on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.debit-input, .credit-input').forEach(input => {
        input.addEventListener('input', calculateTotals);
        input.addEventListener('input', enforceDebitOrCredit);
    });
    calculateTotals();
});
</script>

<?php require_once('includes/footer.php'); ?>

