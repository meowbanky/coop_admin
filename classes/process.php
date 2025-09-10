<?php
require_once('../Connections/coop.php');
require_once __DIR__ . '/services/NotificationService.php';

use App\Services\NotificationService;

// Configuration
ini_set('max_execution_time', 0);

// Initialize services
try {
    $notificationService = new NotificationService($coop);
} catch (Exception $e) {
    error_log("Failed to initialize notification service: " . $e->getMessage());
    exit;
}

include_once('model.php');

// Get parameters with defaults
$periodID = isset($_GET['PeriodID']) ? (int)$_GET['PeriodID'] : -1;
$memberID = isset($_GET['member_id']) ? $_GET['member_id'] : -1;

// Start transaction
mysqli_begin_transaction($coop);

// Fetch global settings
mysqli_select_db($coop, $database); // Ensure $database is defined in coop.php
$settingsQuery = "SELECT setting_id, `value` FROM tbl_globa_settings WHERE setting_id IN (1, 3, 4, 5, 7, 8, 9)";
$settingsResult = mysqli_query($coop, $settingsQuery) or die(mysqli_error($coop));

$settings = [];
while ($row = mysqli_fetch_assoc($settingsResult)) {
    $settings[$row['setting_id']] = $row['value'];
}

// Validate settings
$requiredSettings = [1, 3, 4, 5, 7, 8, 9];
foreach ($requiredSettings as $id) {
    if (!isset($settings[$id])) {
        error_log("Missing global setting ID: $id");
        mysqli_rollback($coop);
        echo "ERROR: Missing global settings. Processing aborted.\n";
        exit;
    }
}

// Extract settings
$title = $settings[1];
$savingsRate = floatval($settings[4]);
$sharesRate = floatval($settings[3]);
$interestRate = floatval($settings[5]);
$entryFee = floatval($settings[7]);
$devLevy = floatval($settings[8]);
$stationery = floatval($settings[9]);

// Validate savings and shares rates
if ($savingsRate < 0 || $sharesRate < 0 || ($savingsRate + $sharesRate) > 1) {
    error_log("Invalid savingsRate ($savingsRate) or sharesRate ($sharesRate) for period: $periodID");
    mysqli_rollback($coop);
    echo "ERROR: Invalid savings or shares rate configuration.\n";
    exit;
}

$memberQuery = "SELECT * FROM tblemployees WHERE `Status` = 'Active'";
if ((int)$memberID == 0) {
    // No filter
} else {
    $memberQuery .= " AND CoopID = ?";
}

try {
    $stmt = mysqli_prepare($coop, $memberQuery);
    if ((int)$memberID != 0) {
        mysqli_stmt_bind_param($stmt, "s", $memberID);
    }

    mysqli_stmt_execute($stmt);
    $memberResult = mysqli_stmt_get_result($stmt);
    $totalRows = mysqli_num_rows($memberResult);

    // Initialize progress display
    echo "PROGRESS_DATA: Starting processing... - 0%\n";

    if ($totalRows > 0) {
        $i = 1;
        while ($member = mysqli_fetch_assoc($memberResult)) {
            if (isTransactionCompleted($coop, $member['CoopID'], $periodID)) {
                continue;
            }

            $balances = calculateBalances($coop, $member['CoopID'], $periodID);
            $contri = $balances['contri'];

            if (!hasEntryFeePaid($coop, $member['CoopID'])) {
                $contri = processEntryFee($coop, $member['CoopID'], $periodID, $contri, $entryFee);
            }
            processPendingCommodity($coop, $member['CoopID'], $periodID);

            if ($contri > 0) {
                $contri = processDevLevy($coop, $member['CoopID'], $periodID, $contri, $devLevy);
                if (hasPendingLoan($coop, $member['CoopID'], $periodID)) {
                    $contri = processStationery($coop, $member['CoopID'], $periodID, $contri, $stationery);
                }
            }

            // Handle all balance scenarios
            if ($contri > 0 && $balances['cb'] == 0 && $balances['lb'] == 0) {
                processSavingsAndShares($coop, $member['CoopID'], $periodID, $contri, $savingsRate, $sharesRate);
            }
            
            if ($balances['cb'] > 0 && $balances['lb'] > 0) {
                $contri = processCommodityRepayment($coop, $member['CoopID'], $periodID, $contri, $balances['cb']);
                $contri = processLoanRepayment($coop, $member['CoopID'], $periodID, $contri, $balances['lb'], $interestRate);
                if ($contri > 0) {
                    processSavingsAndShares($coop, $member['CoopID'], $periodID, $contri, $savingsRate, $sharesRate);
                }
            }
            if ($balances['cb'] == 0 && $balances['lb'] > 0) {
                $contri = processLoanRepayment($coop, $member['CoopID'], $periodID, $contri, $balances['lb'], $interestRate);
                if ($contri > 0) {
                    processSavingsAndShares($coop, $member['CoopID'], $periodID, $contri, $savingsRate, $sharesRate);
                }
            }
            if ($contri > 0 && $balances['cb'] > 0) {
                $contri = processCommodityRepayment($coop, $member['CoopID'], $periodID, $contri, $balances['cb']);
                if ($contri > 0) {
                    processSavingsAndShares($coop, $member['CoopID'], $periodID, $contri, $savingsRate, $sharesRate);
                }
            }
            // if ($contri == 0 && $balances['lb'] > 0) {
            //     $intCharge = $balances['lb'] * $interestRate;
            //     insertTransaction($coop, 'tbl_loans', $member['CoopID'], $periodID, 'LoanAmount', $intCharge, 'LoanPeriod');
            //     insertTransaction($coop, 'tbl_mastertransact', $member['CoopID'], $periodID, 'loan', $intCharge);
            // }

            error_log("coop id: {$member['CoopID']} contri: $contri commodity_balance: {$balances['cb']} loan_balance: {$balances['lb']} period: {$periodID}");

            processPendingLoans($coop, $member['CoopID'], $periodID);
            processLoanSavings($coop, $member['CoopID'], $periodID);

            try {
                $notificationService->sendTransactionNotification($member['CoopID'], $periodID);
            } catch (Exception $e) {
                error_log("Failed to send notification for {$member['CoopID']}: " . $e->getMessage());
            }

            updateProgress($i, $totalRows, $member['CoopID']);
            $i++;
        }

        mysqli_commit($coop);
        echo "PROGRESS_DATA: Process completed successfully for " . ($i - 1) . " employees - 100%\n";
        echo "COMPLETION: SUCCESS\n";
    } else {
        echo "ERROR: No active employees found for the provided criteria.\n";
    }

    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    mysqli_rollback($coop);
    error_log("Transaction failed: " . $e->getMessage());
    echo "ERROR: Error during processing. No transactions were applied.\n";
}

// Helper Functions
function isTransactionCompleted($coop, $coopID, $periodID) {
    $query = "SELECT COUNT(*) FROM tbl_mastertransact WHERE CoopID = ? AND TransactionPeriod = ?";
    $stmt = mysqli_prepare($coop, $query);
    mysqli_stmt_bind_param($stmt, "si", $coopID, $periodID);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $count > 0; // Consider any transaction as completed
}

function calculateBalances($coop, $coopID, $period) {
    $queries = [
        'loan' => "SELECT COALESCE(SUM(LoanAmount), 0) FROM tbl_loans WHERE CoopID = ? AND loanperiod <= ?",
        'loanRepay' => "SELECT COALESCE(SUM(Repayment), 0) FROM tbl_loanrepayment WHERE CoopID = ? AND loanrepaymentPeriod <= ?",
        'commodity' => "SELECT COALESCE(SUM(amount), 0) FROM tbl_commodity WHERE CoopID = ? AND Period <= ?",
        'commRepay' => "SELECT COALESCE(SUM(CommodityPayment), 0) FROM tbl_commodityrepayment WHERE CoopID = ? AND PaymentPeriod <= ?",
        'contri' => "SELECT COALESCE(SUM(MonthlyContribution), 0) FROM tbl_monthlycontribution WHERE CoopID = ? AND period = ?"
    ];

    $balances = [];
    foreach ($queries as $key => $query) {
        $stmt = mysqli_prepare($coop, $query);
        if (!$stmt) {
            error_log("Prepare failed for $key: " . mysqli_error($coop));
            return ['loan' => 0, 'loanRepay' => 0, 'commodity' => 0, 'commRepay' => 0, 'contri' => 0, 'lb' => 0, 'cb' => 0];
        }
        mysqli_stmt_bind_param($stmt, "si", $coopID, $period);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $sum);
        mysqli_stmt_fetch($stmt);
        $balances[$key] = floatval($sum);
        mysqli_stmt_close($stmt);
    }

    $loanCents = (int) round($balances['loan'] * 100);
    $loanRepayCents = (int) round($balances['loanRepay'] * 100);
    $balances['lb'] = ($loanCents - $loanRepayCents) / 100;

    $commCents = (int) round($balances['commodity'] * 100);
    $commRepayCents = (int) round($balances['commRepay'] * 100);
    $balances['cb'] = ($commCents - $commRepayCents) / 100;

    // Validate negative balances
    if ($balances['cb'] < 0) {
        error_log("Negative commodity balance for CoopID: $coopID, period: $period, cb: {$balances['cb']}");
        $balances['cb'] = 0;
    }
    if ($balances['lb'] < 0) {
        error_log("Negative loan balance for CoopID: $coopID, period: $period, lb: {$balances['lb']}");
        $balances['lb'] = 0;
    }

    error_log(sprintf(
        "Raw balances - loan: %.2f, loanRepay: %.2f, commodity: %.2f, commRepay: %.2f, contri: %.2f, lb: %.2f, cb: %.2f for CoopID: %s, period: %s",
        $balances['loan'], $balances['loanRepay'], $balances['commodity'], $balances['commRepay'], $balances['contri'], $balances['lb'], $balances['cb'], $coopID, $period
    ));

    return $balances;
}

function hasEntryFeePaid($coop, $coopID) {
    $query = "SELECT COUNT(*) FROM tbl_entryfee WHERE CoopID = ?";
    $stmt = mysqli_prepare($coop, $query);
    mysqli_stmt_bind_param($stmt, "s", $coopID);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $count > 0;
}

function hasPendingLoan($coop, $coopID, $period) {
    $query = "SELECT COALESCE(SUM(LoanAmount), 0) FROM tbl_loanapproval WHERE CoopID = ? AND period = ?";
    $stmt = mysqli_prepare($coop, $query);
    mysqli_stmt_bind_param($stmt, "si", $coopID, $period);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $amount);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $amount > 0;
}

function insertTransaction($coop, $table, $coopID, $periodID, $field, $amount, $periodField = 'TransactionPeriod') {
    if ($table === 'tbl_loanapproval') {
        $query = "INSERT INTO tbl_loanapproval (CoopID, approvalDate, $field) VALUES (?, CURRENT_DATE, ?)";
        $stmt = mysqli_prepare($coop, $query);
        if (!$stmt) {
            error_log("Prepare failed for $table: " . mysqli_error($coop));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "sd", $coopID, $amount);
    } else {
        $query = "INSERT INTO $table (CoopID, $periodField, $field) VALUES (?, ?, ?)";
    
        $stmt = mysqli_prepare($coop, $query);
        if (!$stmt) {
            error_log("Prepare failed for $table: " . mysqli_error($coop));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "sid", $coopID, $periodID, $amount);
    }
    $result = mysqli_stmt_execute($stmt);
    if (!$result) {
        error_log("Execute failed for $table: " . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
    return $result;
}

function processEntryFee($coop, $coopID, $periodID, $contri, $entryFee) {
    if ($contri > 0 && $contri < $entryFee) {
        error_log("Insufficient contribution ($contri) for entry fee ($entryFee) for CoopID: $coopID, period: $periodID");
        return $contri; // Could insert partial payment if desired
    }
    if ($contri >= $entryFee) {
        insertTransaction($coop, 'tbl_mastertransact', $coopID, $periodID, 'EntryFee', $entryFee);
        insertTransaction($coop, 'tbl_entryfee', $coopID, $periodID, 'AmountPaid', $entryFee, 'DeductionPeriod');
        return $contri - $entryFee;
    }
    return $contri;
}

function processDevLevy($coop, $coopID, $periodID, $contri, $devLevy) {
    if ($contri > 0 && $contri < $devLevy) {
        error_log("Insufficient contribution ($contri) for dev levy ($devLevy) for CoopID: $coopID, period: $periodID");
        return $contri;
    }
    if ($contri >= $devLevy) {
        insertTransaction($coop, 'tbl_devlevy', $coopID, $periodID, 'AmountPaid', $devLevy, 'DevPeriod');
        insertTransaction($coop, 'tbl_mastertransact', $coopID, $periodID, 'DevLevy', $devLevy);
        return $contri - $devLevy;
    }
    return $contri;
}

function processStationery($coop, $coopID, $periodID, $contri, $stationery) {
    if ($contri > 0 && $contri < $stationery) {
        error_log("Insufficient contribution ($contri) for stationery ($stationery) for CoopID: $coopID, period: $periodID");
        return $contri;
    }
    if ($contri >= $stationery) {
        insertTransaction($coop, 'tbl_mastertransact', $coopID, $periodID, 'Stationery', $stationery);
        insertTransaction($coop, 'tbl_stationery', $coopID, $periodID, 'AmountPaid', $stationery, 'StationeryPeriod');
        return $contri - $stationery;
    }
    return $contri;
}

function processCommodityRepayment($coop, $coopID, $periodID, $contri, $cb) {
    $paymentCents = (int) round(min($contri, $cb) * 100);
    $payment = $paymentCents / 100;
    if ($payment > 0) {
        insertTransaction($coop, 'tbl_commodityrepayment', $coopID, $periodID, 'CommodityPayment', $payment, 'PaymentPeriod');
        insertTransaction($coop, 'tbl_mastertransact', $coopID, $periodID, 'CommodityRepayment', $payment);
    }
    return $contri - $payment;
}

function processLoanRepayment($coop, $coopID, $periodID, $contri, $lb, $interestRate) {
    $intChargeCents = (int) round($lb * $interestRate * 100);
    $intCharge = $intChargeCents / 100;

    if ($contri == 0) {
        if ($intCharge > 0) {
            insertTransaction($coop, 'tbl_loans', $coopID, $periodID, 'LoanAmount', $intCharge, 'LoanPeriod');
            insertTransaction($coop, 'tbl_mastertransact', $coopID, $periodID, 'loan', $intCharge);
            error_log("Zero contribution for CoopID: $coopID, period: $periodID; interest $intCharge added to loan balance");
        }
        return 0;
    }

    if ($contri >= $intCharge) {
        $balanceAfterInterestCents = (int) round(($contri - $intCharge) * 100);
        $balanceAfterInterest = $balanceAfterInterestCents / 100;
        $lbCents = (int) round($lb * 100);
        $repaymentCents = ($balanceAfterInterestCents < $lbCents) ? floor($balanceAfterInterestCents / 1000) * 1000 : $lbCents;
        $repayment = $repaymentCents / 100;
        $savingsCents = $balanceAfterInterestCents - $repaymentCents;
        $savings = $savingsCents / 100;

        insertTransaction($coop, 'tbl_mastertransact', $coopID, $periodID, 'InterestPaid', $intCharge);
        insertTransaction($coop, 'tbl_interest', $coopID, $periodID, 'IntAmount', $intCharge, 'InterestPeriod');
        if ($repayment > 0) {
            insertTransaction($coop, 'tbl_mastertransact', $coopID, $periodID, 'loanRepayment', $repayment);
            insertTransaction($coop, 'tbl_loanrepayment', $coopID, $periodID, 'Repayment', $repayment, 'LoanRepaymentPeriod');
        }
        if ($savings > 0) {
            insertTransaction($coop, 'tbl_mastertransact', $coopID, $periodID, 'savingsAmount', $savings);
            insertTransaction($coop, 'tbl_savings', $coopID, $periodID, 'AmountPaid', $savings, 'DeductionPeriod');
        }
        error_log("Sufficient contribution ($contri) for CoopID: $coopID, period: $periodID; interest paid: $intCharge, repayment: $repayment, savings: $savings");
        return 0;
    }

    // Partial interest payment
    $balanceAfterInterestCents = (int) round(($intCharge - $contri) * 100);
    $balanceAfterInterest = $balanceAfterInterestCents / 100;
    insertTransaction($coop, 'tbl_loans', $coopID, $periodID, 'LoanAmount', $balanceAfterInterest, 'LoanPeriod');
    insertTransaction($coop, 'tbl_mastertransact', $coopID, $periodID, 'loan', $balanceAfterInterest);
    insertTransaction($coop, 'tbl_mastertransact', $coopID, $periodID, 'InterestPaid', $contri);
    insertTransaction($coop, 'tbl_interest', $coopID, $periodID, 'IntAmount', $contri, 'InterestPeriod');
    error_log("Partial interest payment ($contri) for CoopID: $coopID, period: $periodID; remaining interest $balanceAfterInterest added to loan");
    return 0;
}

function processSavingsAndShares($coop, $coopID, $periodID, $contri, $savingsRate, $sharesRate) {
    $savingsAmountCents = (int) round($contri * $savingsRate * 100);
    $savingsAmount = $savingsAmountCents / 100;
    $sharesAmountCents = (int) round($contri * $sharesRate * 100);
    $sharesAmount = $sharesAmountCents / 100;

    error_log("Processing savings: $savingsAmount, shares: $sharesAmount for CoopID: $coopID, period: $periodID");

    insertTransaction($coop, 'tbl_shares', $coopID, $periodID, 'sharesAmount', $sharesAmount, 'SharesPeriod');
    insertTransaction($coop, 'tbl_savings', $coopID, $periodID, 'AmountPaid', $savingsAmount, 'DeductionPeriod');
    insertTransaction($coop, 'tbl_mastertransact', $coopID, $periodID, 'savingsAmount', $savingsAmount);
    insertTransaction($coop, 'tbl_mastertransact', $coopID, $periodID, 'sharesAmount', $sharesAmount);
}

function processPendingLoans($coop, $coopID, $periodID) {
    $query = "SELECT COALESCE(SUM(LoanAmount), 0) FROM tbl_loanapproval WHERE CoopID = ? AND period = ?";
    $stmt = mysqli_prepare($coop, $query);
    mysqli_stmt_bind_param($stmt, "si", $coopID, $periodID);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $lapp);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($lapp > 0) {
        insertTransaction($coop, 'tbl_loans', $coopID, $periodID, 'LoanAmount', $lapp, 'LoanPeriod');
        insertTransaction($coop, 'tbl_mastertransact', $coopID, $periodID, 'loan', $lapp);
        // $deleteStmt = mysqli_prepare($coop, "DELETE FROM tbl_loanapproval WHERE CoopID = ? AND period = ?");
        // mysqli_stmt_bind_param($deleteStmt, "si", $coopID, $periodID);
        // mysqli_stmt_execute($deleteStmt);
        // mysqli_stmt_close($deleteStmt);
    }
}

function processPendingCommodity($coop, $coopID, $periodID) {
    $query = "SELECT COALESCE(SUM(amount), 0) FROM tbl_commodity WHERE coopID = ? AND Period = ?";
    $stmt = mysqli_prepare($coop, $query);
    mysqli_stmt_bind_param($stmt, "si", $coopID, $periodID);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $capp);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($capp > 0) {
        insertTransaction($coop, 'tbl_mastertransact', $coopID, $periodID, 'Commodity', $capp, 'TransactionPeriod');
    }
}

function processLoanSavings($coop, $coopID, $periodID) {
    $query = "SELECT Amount FROM tbl_loansavings 
              INNER JOIN tblemployees ON tblemployees.CoopID = tbl_loansavings.COOPID 
              WHERE `Status` = 'Active' AND tbl_loansavings.COOPID = ? and tbl_loansavings.period = ?";
    $stmt = mysqli_prepare($coop, $query);
    mysqli_stmt_bind_param($stmt, "si", $coopID, $periodID);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $amount);
    $hasSavings = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($hasSavings) {
        insertTransaction($coop, 'tbl_shares', $coopID, $periodID, 'sharesAmount', $amount, 'SharesPeriod');
        insertTransaction($coop, 'tbl_mastertransact', $coopID, $periodID, 'sharesAmount', $amount);
    }
}

function updateProgress($current, $total, $coopID) {
    $percent = intval(($current / $total) * 100);
    $percentDisplay = $percent . "%";
    
    // Debug logging
    error_log("Progress Update: $current/$total = $percentDisplay for $coopID");
    
    // Send clean progress data that can be easily parsed
    echo str_repeat(' ', 1024 * 64);
    echo "PROGRESS_DATA: Processing $coopID ($current of $total employees) - $percentDisplay\n";
    // echo "DEBUG: Sent progress data for $coopID\n";
    ob_flush();
    flush();
}
?>