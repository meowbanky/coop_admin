# Accounting Engine - Usage Guide

Complete guide for using the core accounting classes

---

## 🚀 Quick Start

### Initialize the Classes

```php
<?php
require_once('Connections/cov.php');
require_once('libs/services/AccountingEngine.php');
require_once('libs/services/AccountBalanceCalculator.php');
require_once('libs/services/MemberAccountManager.php');

// Initialize classes
$accountingEngine = new AccountingEngine($cov, $database_cov);
$balanceCalculator = new AccountBalanceCalculator($cov, $database_cov);
$memberAccountManager = new MemberAccountManager($cov, $database_cov);
?>
```

---

## 1️⃣ AccountingEngine - Creating Journal Entries

### Example 1: Member Contribution (₦10,000)

**Transaction:** Member pays ₦10,000 (Savings: ₦5,000, Shares: ₦3,000, Loan Repayment: ₦2,000)

```php
<?php
$periodid = 83;
$memberid = 123;
$created_by = $_SESSION['UserID'];

// Define journal entry lines
$lines = [
    // DEBIT: Bank (Asset increases)
    [
        'account_id' => 3,  // Bank - Main Account (1102)
        'debit_amount' => 10000,
        'credit_amount' => 0,
        'description' => 'Receipt from John Doe',
        'reference_type' => 'member',
        'reference_id' => $memberid
    ],

    // CREDIT: Member Savings (Equity increases)
    [
        'account_id' => 37, // Ordinary Savings (3201)
        'debit_amount' => 0,
        'credit_amount' => 5000,
        'description' => 'Savings contribution',
        'reference_type' => 'member',
        'reference_id' => $memberid
    ],

    // CREDIT: Member Shares (Equity increases)
    [
        'account_id' => 33, // Ordinary Shares (3101)
        'debit_amount' => 0,
        'credit_amount' => 3000,
        'description' => 'Share contribution',
        'reference_type' => 'member',
        'reference_id' => $memberid
    ],

    // CREDIT: Member Loans (Asset decreases)
    [
        'account_id' => 6,  // Member Loans (1110)
        'debit_amount' => 0,
        'credit_amount' => 2000,
        'description' => 'Loan repayment',
        'reference_type' => 'member',
        'reference_id' => $memberid
    ]
];

// Create journal entry
$result = $accountingEngine->createJournalEntry(
    $periodid,                    // Period ID
    date('Y-m-d'),               // Entry date
    'member_transaction',         // Entry type
    "Member contribution - John Doe", // Description
    $lines,                       // Journal lines
    $created_by,                  // Created by user ID
    "CONTRIB-{$memberid}"         // Source document (optional)
);

if ($result['success']) {
    echo "Journal entry created: {$result['entry_number']}\n";
    echo "Entry ID: {$result['entry_id']}\n";

    // Post the entry (make it permanent)
    $postResult = $accountingEngine->postEntry($result['entry_id']);

    if ($postResult['success']) {
        echo "Entry posted successfully!\n";
    }
}
?>
```

### Example 2: Expense Payment (₦50,000 Salary)

```php
<?php
$lines = [
    // DEBIT: Salary Expense
    [
        'account_id' => 74, // Salary Cost (6011)
        'debit_amount' => 50000,
        'credit_amount' => 0,
        'description' => 'Monthly salary payment'
    ],

    // CREDIT: Bank
    [
        'account_id' => 3, // Bank - Main Account (1102)
        'debit_amount' => 0,
        'credit_amount' => 50000,
        'description' => 'Salary payment'
    ]
];

$result = $accountingEngine->createJournalEntry(
    $periodid,
    date('Y-m-d'),
    'system',
    "Salary payment for period",
    $lines,
    $created_by
);
?>
```

### Example 3: Loan Disbursement (₦100,000)

```php
<?php
$lines = [
    // DEBIT: Member Loans (Asset increases)
    [
        'account_id' => 6, // Member Loans (1110)
        'debit_amount' => 100000,
        'credit_amount' => 0,
        'description' => "Loan disbursed to member #{$memberid}"
    ],

    // CREDIT: Bank (Asset decreases)
    [
        'account_id' => 3, // Bank
        'debit_amount' => 0,
        'credit_amount' => 100000,
        'description' => "Loan payment to member #{$memberid}"
    ]
];

$result = $accountingEngine->createJournalEntry(
    $periodid,
    date('Y-m-d'),
    'member_transaction',
    "Loan disbursement - Member #{$memberid}",
    $lines,
    $created_by,
    "LOAN-{$memberid}"
);
?>
```

### Reversing an Entry

```php
<?php
$entry_id = 123; // Entry to reverse
$user_id = $_SESSION['UserID'];
$reason = "Entry posted in error - wrong amount";

$result = $accountingEngine->reverseEntry($entry_id, $user_id, $reason);

if ($result['success']) {
    echo "Entry reversed. Reversing entry: {$result['reversing_entry_number']}\n";
}
?>
```

---

## 2️⃣ AccountBalanceCalculator - Getting Balances & Reports

### Get Account Balance

```php
<?php
$account_id = 3; // Bank account
$periodid = 83;

$balance = $balanceCalculator->getAccountBalance($account_id, $periodid);

echo "Bank Balance: ₦" . number_format($balance['balance'], 2) . "\n";
echo "Total Debits: ₦" . number_format($balance['debit'], 2) . "\n";
echo "Total Credits: ₦" . number_format($balance['credit'], 2) . "\n";
?>
```

### Generate Trial Balance

```php
<?php
$periodid = 83;

$trialBalance = $balanceCalculator->getTrialBalance($periodid);

if ($trialBalance['is_balanced']) {
    echo "✅ Trial Balance is BALANCED!\n\n";
} else {
    echo "❌ Trial Balance is OUT OF BALANCE!\n";
    echo "Difference: ₦" . number_format($trialBalance['totals']['difference'], 2) . "\n\n";
}

echo "TRIAL BALANCE\n";
echo "=============\n";
echo sprintf("%-10s %-40s %15s %15s\n", "Code", "Account", "Debit", "Credit");
echo str_repeat("-", 82) . "\n";

foreach ($trialBalance['accounts'] as $account) {
    echo sprintf(
        "%-10s %-40s %15s %15s\n",
        $account['account_code'],
        $account['account_name'],
        number_format($account['debit_balance'], 2),
        number_format($account['credit_balance'], 2)
    );
}

echo str_repeat("-", 82) . "\n";
echo sprintf(
    "%-10s %-40s %15s %15s\n",
    "",
    "TOTALS",
    number_format($trialBalance['totals']['debit'], 2),
    number_format($trialBalance['totals']['credit'], 2)
);
?>
```

### Verify Accounting Equation

```php
<?php
$verification = $balanceCalculator->verifyAccountingEquation($periodid);

if ($verification['valid']) {
    echo "✅ Accounting Equation is VALID!\n";
} else {
    echo "❌ Accounting Equation is INVALID!\n";
}

echo "\nAssets: ₦" . number_format($verification['assets'], 2) . "\n";
echo "Liabilities: ₦" . number_format($verification['liabilities'], 2) . "\n";
echo "Equity: ₦" . number_format($verification['equity'], 2) . "\n";
echo "L + E: ₦" . number_format($verification['liabilities_plus_equity'], 2) . "\n";
echo "Difference: ₦" . number_format($verification['difference'], 2) . "\n";
?>
```

### Get Totals by Account Type

```php
<?php
$summary = $balanceCalculator->getAccountSummaryByType($periodid);

echo "ACCOUNT SUMMARY\n";
echo "===============\n";
echo "Assets: ₦" . number_format($summary['asset'], 2) . "\n";
echo "Liabilities: ₦" . number_format($summary['liability'], 2) . "\n";
echo "Equity: ₦" . number_format($summary['equity'], 2) . "\n";
echo "Revenue: ₦" . number_format($summary['revenue'], 2) . "\n";
echo "Expenses: ₦" . number_format($summary['expense'], 2) . "\n";
echo "\nNet Profit: ₦" . number_format($summary['revenue'] - $summary['expense'], 2) . "\n";
?>
```

---

## 3️⃣ MemberAccountManager - Managing Member Accounts

### Record Member Transaction

```php
<?php
$memberid = 123;
$periodid = 83;

// Record shares contribution
$result = $memberAccountManager->recordMemberTransaction(
    $memberid,
    'shares',      // Account type: shares, savings, special_savings, loan
    3000,          // Amount (positive = credit/increase, negative = debit/decrease)
    $periodid,
    'Monthly share contribution'
);

// Record savings contribution
$memberAccountManager->recordMemberTransaction(
    $memberid,
    'savings',
    5000,
    $periodid,
    'Monthly savings contribution'
);

// Record loan repayment (loan decreases)
$memberAccountManager->recordMemberTransaction(
    $memberid,
    'loan',
    -2000,         // Negative = debit/decrease loan balance
    $periodid,
    'Loan repayment'
);
?>
```

### Get Member Balance

```php
<?php
$memberid = 123;
$periodid = 83;

$shares = $memberAccountManager->getMemberBalance($memberid, 'shares', $periodid);
$savings = $memberAccountManager->getMemberBalance($memberid, 'savings', $periodid);
$loan = $memberAccountManager->getMemberBalance($memberid, 'loan', $periodid);

echo "Member #{$memberid} Balances:\n";
echo "Shares: ₦" . number_format($shares, 2) . "\n";
echo "Savings: ₦" . number_format($savings, 2) . "\n";
echo "Loan: ₦" . number_format($loan, 2) . "\n";
?>
```

### Generate Member Statement

```php
<?php
$memberid = 123;
$from_periodid = 75;
$to_periodid = 83;

$statement = $memberAccountManager->generateMemberStatement(
    $memberid,
    $from_periodid,
    $to_periodid
);

if ($statement['success']) {
    $member = $statement['member'];

    echo "MEMBER STATEMENT\n";
    echo "================\n";
    echo "Member: {$member['full_name']}\n";
    echo "Coop No: {$member['CooperativeNo']}\n\n";

    foreach ($statement['statement'] as $account_type => $transactions) {
        echo strtoupper($account_type) . "\n";
        echo str_repeat("-", 80) . "\n";
        echo sprintf("%-20s %12s %12s %12s %12s\n",
            "Period", "Opening", "Debit", "Credit", "Closing");
        echo str_repeat("-", 80) . "\n";

        foreach ($transactions as $tx) {
            echo sprintf("%-20s %12s %12s %12s %12s\n",
                $tx['PayrollPeriod'],
                number_format($tx['opening_balance'], 2),
                number_format($tx['debit_amount'], 2),
                number_format($tx['credit_amount'], 2),
                number_format($tx['closing_balance'], 2)
            );
        }
        echo "\n";
    }
}
?>
```

### Reconcile Member Accounts with Control Accounts

```php
<?php
$periodid = 83;

$reconciliation = $memberAccountManager->reconcileMemberAccounts($periodid);

if ($reconciliation['all_match']) {
    echo "✅ All member accounts reconcile with control accounts!\n";
} else {
    echo "❌ RECONCILIATION MISMATCHES FOUND:\n\n";

    foreach ($reconciliation['mismatches'] as $mismatch) {
        echo "{$mismatch['account_type']}:\n";
        echo "  Member Total: ₦" . number_format($mismatch['member_total'], 2) . "\n";
        echo "  Control Total: ₦" . number_format($mismatch['control_total'], 2) . "\n";
        echo "  Difference: ₦" . number_format($mismatch['difference'], 2) . "\n\n";
    }
}
?>
```

### Get Member Account Summary

```php
<?php
$memberid = 123;
$periodid = 83;

$summary = $memberAccountManager->getMemberAccountSummary($memberid, $periodid);

echo "MEMBER ACCOUNT SUMMARY\n";
echo "======================\n";
echo "Shares: ₦" . number_format($summary['shares'], 2) . "\n";
echo "Savings: ₦" . number_format($summary['savings'], 2) . "\n";
echo "Special Savings: ₦" . number_format($summary['special_savings'], 2) . "\n";
echo "Loan: ₦" . number_format($summary['loan'], 2) . "\n";
echo "---\n";
echo "Total Equity: ₦" . number_format($summary['total_equity'], 2) . "\n";
echo "Net Position: ₦" . number_format($summary['net_position'], 2) . "\n";
?>
```

---

## 🔗 Integration with process.php

### Complete Example: Member Contribution Processing

```php
<?php
// In process.php - after successful transaction processing

require_once('libs/services/AccountingEngine.php');
require_once('libs/services/MemberAccountManager.php');

$accountingEngine = new AccountingEngine($cov, $database_cov);
$memberAccountManager = new MemberAccountManager($cov, $database_cov);

// Prepare journal entry lines
$lines = [];

// Debit: Bank
if ($total_contribution > 0) {
    $lines[] = [
        'account_id' => 3, // Bank account
        'debit_amount' => $total_contribution,
        'credit_amount' => 0,
        'description' => "Receipt from {$member_name}",
        'reference_type' => 'member',
        'reference_id' => $memberid
    ];
}

// Credit: Savings
if ($savings > 0) {
    $lines[] = [
        'account_id' => 37, // Ordinary Savings
        'debit_amount' => 0,
        'credit_amount' => $savings,
        'description' => 'Savings contribution',
        'reference_type' => 'member',
        'reference_id' => $memberid
    ];

    // Update member account
    $memberAccountManager->recordMemberTransaction(
        $memberid, 'savings', $savings, $periodid, 'Monthly savings'
    );
}

// Credit: Shares
if ($shares > 0) {
    $lines[] = [
        'account_id' => 33, // Ordinary Shares
        'debit_amount' => 0,
        'credit_amount' => $shares,
        'description' => 'Share contribution',
        'reference_type' => 'member',
        'reference_id' => $memberid
    ];

    $memberAccountManager->recordMemberTransaction(
        $memberid, 'shares', $shares, $periodid, 'Monthly shares'
    );
}

// Credit: Loan Repayment
if ($loan_repayment > 0) {
    $lines[] = [
        'account_id' => 6, // Member Loans
        'debit_amount' => 0,
        'credit_amount' => $loan_repayment,
        'description' => 'Loan repayment',
        'reference_type' => 'member',
        'reference_id' => $memberid
    ];

    $memberAccountManager->recordMemberTransaction(
        $memberid, 'loan', -$loan_repayment, $periodid, 'Loan repayment'
    );
}

// Create and post journal entry
$result = $accountingEngine->createJournalEntry(
    $periodid,
    date('Y-m-d'),
    'member_transaction',
    "Member contribution - {$member_name}",
    $lines,
    $_SESSION['UserID'],
    "MEMBER-{$memberid}-{$periodid}"
);

if ($result['success']) {
    $accountingEngine->postEntry($result['entry_id']);
    error_log("Journal entry posted: {$result['entry_number']}");
}
?>
```

---

## ✅ Best Practices

1. **Always Validate Before Posting**

   - Create entries as 'draft' first
   - Review before posting
   - Only posted entries update balances

2. **Use Transactions**

   - All operations use database transactions
   - Automatic rollback on errors
   - Data integrity guaranteed

3. **Reference Links**

   - Always set reference_type and reference_id
   - Links journal entries to source documents
   - Enables audit trail

4. **Error Handling**

   - All methods return ['success' => bool, 'error' => string]
   - Check success before proceeding
   - Log errors for debugging

5. **Period Management**
   - Initialize new periods before use
   - Close periods to lock them
   - Reconcile before closing

---

## 📊 Next Steps

Now that you have the core engine, you can:

1. **Integrate with process.php** - Auto-post member contributions
2. **Create UI Pages** - Journal entry form, trial balance viewer
3. **Build Reports** - Financial statements, member statements
4. **Add Period Closing** - Close periods, generate appropriations
5. **Implement Budget** - Budget entry and variance analysis

---

## 🔧 Troubleshooting

**Trial Balance Doesn't Balance:**

- Run `verifyAccountingEquation()` to check equation
- Check for unposted entries
- Review journal entries for errors

**Control Accounts Don't Match:**

- Run `reconcileMemberAccounts()` to identify mismatches
- Check for missing member transactions
- Verify account IDs in CONTROL_ACCOUNTS array

**Entry Won't Post:**

- Verify entry is in 'draft' status
- Check that all accounts exist
- Ensure period is not closed

---

For more help, see the inline documentation in each class file.
