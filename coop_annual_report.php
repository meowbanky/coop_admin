<?php
/**
 * OOUTH Cooperative Society — Annual Report
 *
 * Produces the full statutory financial report per Nigerian cooperative law:
 *  - Revenue & Expenditure Account
 *  - Appropriation Account
 *  - Statement of Affairs (Balance Sheet)
 *  - Statement of Cash Flows
 *  - Notes to the Accounts (Notes 1-8)
 */

session_start();
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) === '') {
    header('Location: index.php');
    exit;
}

require_once('Connections/coop.php');
require_once('libs/reports/IncomeExpenditureStatement.php');
require_once('libs/reports/BalanceSheet.php');
require_once('libs/reports/CashflowStatement.php');
require_once('libs/reports/NotesGenerator.php');
require_once('libs/services/AccountBalanceCalculator.php');

/* ---- Period list ---- */
$periods = [];
try {
    $stmt = $coop->query("SELECT id, PayrollPeriod FROM tbpayrollperiods ORDER BY id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $periods[] = $row;
    }
} catch (PDOException $e) { /* silently continue */ }

$selectedPeriod     = isset($_GET['periodid']) ? intval($_GET['periodid']) : (count($periods) > 0 ? $periods[0]['id'] : 0);
$selectedPeriodName = '';
$prevPeriodName     = '';
$prevPeriodId       = 0;

foreach ($periods as $i => $p) {
    if ($p['id'] == $selectedPeriod) {
        $selectedPeriodName = $p['PayrollPeriod'];
        if (isset($periods[$i + 1])) {
            $prevPeriodId   = $periods[$i + 1]['id'];
            $prevPeriodName = $periods[$i + 1]['PayrollPeriod'];
        }
        break;
    }
}

/* ---- Generate all statements ---- */
$incomeData    = null;
$balanceData   = null;
$cashflowData  = null;
$trialBalance  = null;
$notes         = [];

if ($selectedPeriod > 0) {
    $incomeGen   = new IncomeExpenditureStatement($coop, $database);
    $balanceGen  = new BalanceSheet($coop, $database);
    $cashGen     = new CashflowStatement($coop, $database);
    $notesGen    = new NotesGenerator($coop, $database);
    $calculator  = new AccountBalanceCalculator($coop, $database);

    $incomeResult  = $incomeGen->generateStatement($selectedPeriod);
    $balanceResult = $balanceGen->generateStatement($selectedPeriod);
    $cashResult    = $cashGen->generateStatement($selectedPeriod);

    if ($incomeResult['success'])  $incomeData   = $incomeResult['statement'][$selectedPeriod];
    if ($balanceResult['success']) $balanceData  = $balanceResult['statement'][$selectedPeriod];
    if ($cashResult['success'])    $cashflowData = $cashResult['statement'][$selectedPeriod];

    $trialBalance = $calculator->getTrialBalance($selectedPeriod);

    try { $notes['loan']     = $notesGen->generateNote1($selectedPeriod); } catch (Throwable $e) { $notes['loan'] = null; }
    try { $notes['members']  = $notesGen->generateNote2($selectedPeriod); } catch (Throwable $e) { $notes['members'] = null; }
    try { $notes['reserves'] = $notesGen->generateReserveNotes($selectedPeriod); } catch (Throwable $e) { $notes['reserves'] = null; }
    try { $notes['strength'] = $notesGen->generateNote7($selectedPeriod); } catch (Throwable $e) { $notes['strength'] = null; }
    try { $notes['assets']   = $notesGen->generateFixedAssetsNote($selectedPeriod); } catch (Throwable $e) { $notes['assets'] = null; }
}

/* ---- Helper: format amounts ---- */
function fmt(float $n, bool $showSign = false): string {
    $abs = number_format(abs($n), 2);
    if ($showSign && $n < 0) return '(' . $abs . ')';
    return ($n < 0 ? '-' : '') . $abs;
}

require_once('includes/header.php');
require_once('includes/accounting_nav.php');
?>

<style>
/* ===================== PRINT STYLES ===================== */
@media print {
    .no-print, nav, aside, .sidebar, #sidebar, header nav, .topbar { display: none !important; }
    body { font-size: 10pt; background: #fff; color: #000; }
    .report-section { page-break-inside: avoid; }
    .page-break { page-break-before: always; }
    .print-container { max-width: 100%; padding: 0; margin: 0; }
    .report-table th, .report-table td { padding: 3px 6px !important; }
    .section-header { background: #1e3a5f !important; color: #fff !important; print-color-adjust: exact; }
    .subtotal-row { background: #e8f0fe !important; print-color-adjust: exact; }
    .total-row { background: #1e3a5f !important; color: #fff !important; print-color-adjust: exact; }
    .cover-page { page-break-after: always; }
}

/* ===================== SCREEN STYLES ===================== */
.report-container { max-width: 960px; margin: 0 auto; padding: 24px 16px; font-family: 'Segoe UI', Arial, sans-serif; }
.cover-box { text-align: center; border: 3px double #1e3a5f; padding: 40px; margin-bottom: 32px; }
.cover-box h1 { font-size: 1.6rem; font-weight: 800; color: #1e3a5f; letter-spacing: 0.05em; text-transform: uppercase; }
.cover-box h2 { font-size: 1.2rem; font-weight: 600; color: #333; margin-top: 8px; }
.cover-box p  { color: #555; margin-top: 4px; }

.section-card { background: #fff; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 28px; overflow: hidden; }
.section-header { background: #1e3a5f; color: #fff; padding: 10px 18px; }
.section-header h2 { font-size: 1rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; margin: 0; }
.section-header p  { font-size: 0.78rem; opacity: 0.85; margin: 2px 0 0; }

.report-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.report-table th { background: #f0f4ff; font-weight: 700; text-align: left; padding: 7px 12px; border-bottom: 2px solid #c0cfe0; }
.report-table th.num { text-align: right; }
.report-table td { padding: 5px 12px; border-bottom: 1px solid #eee; }
.report-table td.num { text-align: right; font-family: monospace; white-space: nowrap; }
.report-table td.indent { padding-left: 32px; }
.report-table td.indent2 { padding-left: 52px; }
.subtotal-row td { background: #eef2ff; font-weight: 600; }
.total-row td { background: #1e3a5f; color: #fff; font-weight: 700; border-top: 2px solid #0f2444; }
.gross-row td { background: #0e7c3a; color: #fff; font-weight: 700; }
.surplus-row td { background: #7c3c0e; color: #fff; font-weight: 700; }
.approp-row td { background: #5a1e5a; color: #fff; font-weight: 700; }
.positive { color: #065f46; }
.negative { color: #991b1b; }

.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
@media (max-width: 720px) { .two-col { grid-template-columns: 1fr; } }

.note-heading { font-size: 0.85rem; font-weight: 700; color: #1e3a5f; padding: 10px 18px; border-bottom: 1px solid #dde4f0; background: #f7f9ff; }
.balance-check-ok  { background: #d1fae5; border: 1px solid #10b981; border-radius: 4px; padding: 8px 14px; font-weight: 600; color: #065f46; margin-top: 12px; font-size: 0.82rem; }
.balance-check-err { background: #fee2e2; border: 1px solid #ef4444; border-radius: 4px; padding: 8px 14px; font-weight: 600; color: #991b1b; margin-top: 12px; font-size: 0.82rem; }
</style>

<div class="report-container print-container">

<!-- =========================================================
     TOOLBAR (no-print)
========================================================= -->
<div class="no-print" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
    <div>
        <h1 style="font-size:1.5rem; font-weight:800; color:#1e3a5f;">Annual Financial Report</h1>
        <p style="color:#666; font-size:0.85rem;">OOUTH Cooperative Society — Full statutory reporting package</p>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <button onclick="window.print()" style="background:#1e3a5f; color:#fff; border:none; padding:9px 18px; border-radius:6px; cursor:pointer; font-size:0.875rem;">
            &#128438; Print / Save PDF
        </button>
        <a href="api/export_financial_statements.php?periodid=<?php echo $selectedPeriod; ?>&type=income" target="_blank"
           style="background:#065f46; color:#fff; padding:9px 18px; border-radius:6px; text-decoration:none; font-size:0.875rem;">
            &#128229; Export I&E (CSV)
        </a>
        <a href="api/export_financial_statements.php?periodid=<?php echo $selectedPeriod; ?>&type=balance" target="_blank"
           style="background:#065f46; color:#fff; padding:9px 18px; border-radius:6px; text-decoration:none; font-size:0.875rem;">
            &#128229; Export Balance Sheet
        </a>
    </div>
</div>

<!-- Period selector -->
<div class="no-print section-card" style="padding:18px 20px; margin-bottom:24px;">
    <form method="GET" style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
        <label style="font-weight:700; color:#1e3a5f;">Reporting Period:</label>
        <select name="periodid" onchange="this.form.submit()"
                style="border:1px solid #ccc; border-radius:5px; padding:7px 14px; font-size:0.875rem; min-width:220px;">
            <option value="">-- Select Period --</option>
            <?php foreach ($periods as $p): ?>
                <option value="<?php echo $p['id']; ?>" <?php echo ($p['id'] == $selectedPeriod) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($p['PayrollPeriod']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if (!$selectedPeriod || !$incomeData): ?>
<div style="background:#fffbeb; border:1px solid #f59e0b; border-radius:6px; padding:24px; text-align:center;">
    <p style="color:#92400e; font-weight:600;">Please select a reporting period above to generate the annual report.</p>
</div>
<?php require_once('includes/footer.php'); exit; ?>
<?php endif; ?>


<!-- =========================================================
     COVER PAGE
========================================================= -->
<div class="cover-box cover-page">
    <p style="font-size:0.78rem; color:#888; letter-spacing:0.1em; text-transform:uppercase;">Olabisi Onabanjo University Teaching Hospital</p>
    <h1>Cooperative Multi-Purpose Society Limited</h1>
    <h2>Annual Report &amp; Financial Statements</h2>
    <p style="margin-top:12px; font-size:1rem; color:#1e3a5f; font-weight:600;"><?php echo htmlspecialchars($selectedPeriodName); ?></p>
    <hr style="border:none; border-top:2px solid #1e3a5f; margin:20px auto; width:60%;"/>
    <p style="font-size:0.78rem; color:#999;">
        Prepared in accordance with the Nigerian Cooperative Societies Act,<br/>
        ICAN Accounting Standards for Cooperative Societies, and<br/>
        the Companies &amp; Allied Matters Act (CAMA) 2020
    </p>
    <p style="margin-top:16px; font-size:0.8rem; color:#aaa;">Generated: <?php echo date('d F Y'); ?></p>
</div>


<!-- =========================================================
     SECTION 1: REVENUE & EXPENDITURE ACCOUNT
========================================================= -->
<div class="section-card report-section">
    <div class="section-header">
        <h2>Revenue &amp; Expenditure Account</h2>
        <p>For the Period Ended: <?php echo htmlspecialchars($selectedPeriodName); ?></p>
    </div>
    <div style="padding:0;">
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width:60%">Particulars</th>
                    <th class="num" style="width:20%">&#8358;</th>
                    <th class="num" style="width:20%">&#8358;</th>
                </tr>
            </thead>
            <tbody>
                <!-- INCOME -->
                <tr><td colspan="3" style="font-weight:700; color:#1e3a5f; background:#f7f9ff; padding:8px 12px;">INCOME</td></tr>
                <tr>
                    <td class="indent">Entrance Fees</td>
                    <td class="num"><?php echo fmt($incomeData['revenue']['entrance_fee']); ?></td>
                    <td class="num"></td>
                </tr>
                <tr>
                    <td class="indent">Interest on Member Loans</td>
                    <td class="num"><?php echo fmt($incomeData['revenue']['interest_charges']); ?></td>
                    <td class="num"></td>
                </tr>
                <tr>
                    <td class="indent">Other Income</td>
                    <td class="num"><?php echo fmt($incomeData['revenue']['other_income']); ?></td>
                    <td class="num"></td>
                </tr>
                <tr class="subtotal-row">
                    <td><strong>TOTAL INCOME</strong></td>
                    <td class="num"></td>
                    <td class="num"><strong><?php echo fmt($incomeData['revenue']['total_revenue']); ?></strong></td>
                </tr>

                <!-- COST OF SALES -->
                <?php if ($incomeData['cost_of_sales'] > 0): ?>
                <tr><td colspan="3" style="font-weight:700; color:#1e3a5f; background:#f7f9ff; padding:8px 12px;">COST OF SALES</td></tr>
                <tr>
                    <td class="indent">Cost of Trading Stock Sold</td>
                    <td class="num"><?php echo fmt($incomeData['cost_of_sales']); ?></td>
                    <td class="num"></td>
                </tr>
                <tr class="gross-row">
                    <td colspan="2"><strong>GROSS SURPLUS FROM TRADING</strong></td>
                    <td class="num"><strong><?php echo fmt($incomeData['gross_profit']); ?></strong></td>
                </tr>
                <?php endif; ?>

                <!-- EXPENDITURE -->
                <tr><td colspan="3" style="font-weight:700; color:#1e3a5f; background:#f7f9ff; padding:8px 12px;">ADMINISTRATIVE &amp; MANAGEMENT EXPENSES</td></tr>
                <?php
                $expense_labels = [
                    'professional_services' => 'Audit &amp; Professional Fees',
                    'printing_stationery'   => 'Printing &amp; Stationery',
                    'telephone'             => 'Telephone &amp; Postage',
                    'internet'              => 'Internet Charges',
                    'transport_travelling'  => 'Transport &amp; Travelling',
                    'sundry_expenses'       => 'Sundry Expenses',
                    'advertisement'         => 'Advertisement &amp; Publicity',
                    'office_expenses'       => 'Office Expenses',
                    'bank_charges'          => 'Bank Charges',
                    'fueling'               => 'Fueling &amp; Maintenance',
                    'salary_cost'           => 'Staff Salaries &amp; Emoluments',
                    'training'              => 'Staff Training &amp; Development',
                    'entertainment'         => 'Entertainment &amp; Gifts',
                    'agm_expenses'          => 'AGM / General Meeting Expenses',
                    'award'                 => 'Long Service Award',
                    'sitting_allowance'     => 'Committee Sitting Allowance',
                    'discount_allowed'      => 'Discount Allowed',
                    'depreciation'          => 'Depreciation of Fixed Assets',
                ];
                foreach ($expense_labels as $key => $label):
                    $val = $incomeData['overhead'][$key] ?? 0;
                    if ($val == 0) continue;
                ?>
                <tr>
                    <td class="indent"><?php echo $label; ?></td>
                    <td class="num"><?php echo fmt($val); ?></td>
                    <td class="num"></td>
                </tr>
                <?php endforeach; ?>
                <tr class="subtotal-row">
                    <td><strong>TOTAL EXPENDITURE</strong></td>
                    <td class="num"></td>
                    <td class="num"><strong><?php echo fmt($incomeData['overhead']['total_expenses']); ?></strong></td>
                </tr>

                <!-- SURPLUS / DEFICIT -->
                <tr class="surplus-row">
                    <td colspan="2"><strong>SURPLUS (DEFICIT) FOR THE PERIOD</strong></td>
                    <td class="num"><strong><?php echo fmt($incomeData['surplus']); ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>


<!-- =========================================================
     SECTION 2: APPROPRIATION ACCOUNT
========================================================= -->
<div class="section-card report-section page-break">
    <div class="section-header">
        <h2>Appropriation Account</h2>
        <p>Distribution of Surplus for the Period Ended: <?php echo htmlspecialchars($selectedPeriodName); ?></p>
    </div>
    <div style="padding:0;">
        <table class="report-table">
            <tbody>
                <tr>
                    <td style="padding:8px 12px; font-weight:700;">Surplus for the period (from Revenue &amp; Expenditure Account)</td>
                    <td class="num" style="font-weight:700;"><?php echo fmt($incomeData['surplus']); ?></td>
                </tr>

                <?php $approp = $incomeData['appropriation']; $total_approp = $approp['total_appropriation']; ?>

                <?php if ($total_approp > 0): ?>
                <tr><td colspan="2" style="font-weight:700; color:#1e3a5f; background:#f7f9ff; padding:8px 12px;">APPROPRIATION OF SURPLUS PURSUANT TO COOPERATIVE BYLAWS</td></tr>

                <?php
                $approp_labels = [
                    'dividend'            => 'Dividend to Members (on Shares)',
                    'interest_to_members' => 'Interest on Savings to Members',
                    'reserve_fund'        => 'Statutory Reserve Fund (10% minimum — Cooperative Act)',
                    'education_fund'      => 'Education &amp; Training Fund',
                    'welfare_fund'        => 'Social Welfare Fund',
                    'honorarium'          => 'Management Committee Honorarium',
                    'bonus'               => 'Staff Bonus',
                    'general_reserve'     => 'General Reserve / Building Fund',
                ];
                foreach ($approp_labels as $key => $label):
                    $val = $approp[$key] ?? 0;
                    if ($val == 0) continue;
                ?>
                <tr>
                    <td class="indent"><?php echo $label; ?></td>
                    <td class="num"><?php echo fmt($val); ?></td>
                </tr>
                <?php endforeach; ?>

                <tr class="subtotal-row">
                    <td><strong>Total Appropriation</strong></td>
                    <td class="num"><strong>(<?php echo fmt($total_approp); ?>)</strong></td>
                </tr>
                <?php endif; ?>

                <tr class="approp-row">
                    <td><strong>BALANCE CARRIED FORWARD TO STATEMENT OF AFFAIRS</strong></td>
                    <td class="num"><strong><?php echo fmt($incomeData['net_profit_bd']); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <div style="padding:14px 18px; font-size:0.78rem; color:#666; border-top:1px solid #eee;">
            <strong>Note:</strong> Appropriations are made in compliance with the Cooperative Societies Act, society bylaws, and
            resolutions of the Annual General Meeting. The Statutory Reserve Fund is mandatory at a minimum of 10% of surplus.
        </div>
    </div>
</div>


<!-- =========================================================
     SECTION 3: STATEMENT OF AFFAIRS (BALANCE SHEET)
========================================================= -->
<?php if ($balanceData): ?>
<div class="section-card report-section page-break">
    <div class="section-header">
        <h2>Statement of Affairs (Balance Sheet)</h2>
        <p>As at: <?php echo htmlspecialchars($selectedPeriodName); ?></p>
    </div>
    <div style="padding:16px 18px;">
        <div class="two-col">

            <!-- LEFT: ASSETS -->
            <div>
                <!-- NON-CURRENT ASSETS -->
                <table class="report-table" style="margin-bottom:16px;">
                    <thead><tr><th colspan="2">FIXED ASSETS (NON-CURRENT)</th></tr></thead>
                    <tbody>
                    <?php foreach ($balanceData['non_current_assets'] as $k => $v): if ($v == 0) continue; ?>
                        <tr><td class="indent"><?php echo str_replace('_', ' ', ucwords($k, '_')); ?></td><td class="num"><?php echo fmt($v); ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="subtotal-row">
                        <td><strong>Total Fixed Assets</strong></td>
                        <td class="num"><strong><?php echo fmt($balanceData['total_non_current_assets']); ?></strong></td>
                    </tr>
                    </tbody>
                </table>

                <!-- CURRENT ASSETS -->
                <table class="report-table" style="margin-bottom:16px;">
                    <thead><tr><th colspan="2">CURRENT ASSETS</th></tr></thead>
                    <tbody>
                    <tr><td class="indent">Cash on Hand</td><td class="num"><?php echo fmt($balanceData['current_assets']['cash']); ?></td></tr>
                    <tr><td class="indent">Cash at Bank</td><td class="num"><?php echo fmt($balanceData['current_assets']['total_bank']); ?></td></tr>
                    <tr><td class="indent">Member Loans Receivable</td><td class="num"><?php echo fmt($balanceData['current_assets']['member_loans']); ?></td></tr>
                    <tr><td class="indent">Account Receivables &amp; Debtors</td><td class="num"><?php echo fmt($balanceData['current_assets']['receivables']); ?></td></tr>
                    <?php if ($balanceData['current_assets']['inventory'] > 0): ?>
                    <tr><td class="indent">Stock in Trade</td><td class="num"><?php echo fmt($balanceData['current_assets']['inventory']); ?></td></tr>
                    <?php endif; ?>
                    <?php if ($balanceData['current_assets']['prepaid'] > 0): ?>
                    <tr><td class="indent">Prepayments</td><td class="num"><?php echo fmt($balanceData['current_assets']['prepaid']); ?></td></tr>
                    <?php endif; ?>
                    <tr class="subtotal-row"><td><strong>Total Current Assets</strong></td><td class="num"><strong><?php echo fmt($balanceData['total_current_assets']); ?></strong></td></tr>
                    </tbody>
                </table>

                <!-- TOTAL ASSETS -->
                <table class="report-table">
                    <tbody>
                    <tr class="total-row">
                        <td><strong>TOTAL ASSETS</strong></td>
                        <td class="num"><strong><?php echo fmt($balanceData['total_non_current_assets'] + $balanceData['total_current_assets']); ?></strong></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- RIGHT: LIABILITIES & EQUITY -->
            <div>
                <!-- CURRENT LIABILITIES -->
                <table class="report-table" style="margin-bottom:16px;">
                    <thead><tr><th colspan="2">CURRENT LIABILITIES</th></tr></thead>
                    <tbody>
                    <?php
                    $liability_labels = [
                        'payables'           => 'Trade Creditors &amp; Payables',
                        'accrued_expenses'   => 'Accrued Expenses',
                        'dividend_payable'   => 'Dividend Payable',
                        'interest_payable'   => 'Interest Payable to Members',
                        'bonus_payable'      => 'Bonus Payable',
                        'honorarium_payable' => 'Honorarium Payable',
                    ];
                    foreach ($liability_labels as $k => $label):
                        $v = $balanceData['current_liabilities'][$k] ?? 0;
                        if ($v == 0) continue;
                    ?>
                    <tr><td class="indent"><?php echo $label; ?></td><td class="num"><?php echo fmt($v); ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="subtotal-row"><td><strong>Total Current Liabilities</strong></td><td class="num"><strong><?php echo fmt($balanceData['total_current_liabilities']); ?></strong></td></tr>
                    </tbody>
                </table>

                <!-- NET CURRENT ASSETS -->
                <table class="report-table" style="margin-bottom:16px;">
                    <tbody>
                    <tr class="subtotal-row">
                        <td><strong>Net Current Assets (Working Capital)</strong></td>
                        <td class="num"><strong><?php echo fmt($balanceData['net_current_assets']); ?></strong></td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>NET ASSETS</strong></td>
                        <td class="num"><strong><?php echo fmt($balanceData['net_assets']); ?></strong></td>
                    </tr>
                    </tbody>
                </table>

                <!-- FINANCED BY -->
                <table class="report-table" style="margin-bottom:8px;">
                    <thead><tr><th colspan="2">FINANCED BY — MEMBERS&apos; FUNDS</th></tr></thead>
                    <tbody>
                    <tr><td class="indent">Ordinary Shares Capital</td><td class="num"><?php echo fmt($balanceData['members_fund']['shares']); ?></td></tr>
                    <tr><td class="indent">Ordinary Savings</td><td class="num"><?php echo fmt($balanceData['members_fund']['ordinary_savings']); ?></td></tr>
                    <tr><td class="indent">Special Savings</td><td class="num"><?php echo fmt($balanceData['members_fund']['special_savings']); ?></td></tr>
                    <tr class="subtotal-row"><td><strong>Total Members&apos; Fund</strong></td><td class="num"><strong><?php echo fmt($balanceData['total_members_fund']); ?></strong></td></tr>
                    </tbody>
                </table>

                <table class="report-table" style="margin-bottom:8px;">
                    <thead><tr><th colspan="2">REVENUE RESERVES</th></tr></thead>
                    <tbody>
                    <?php
                    $reserve_labels = [
                        'statutory_reserve' => 'Statutory Reserve Fund',
                        'general_reserve'   => 'General Reserve',
                        'education_fund'    => 'Education Fund',
                        'welfare_fund'      => 'Welfare Fund',
                        'building_fund'     => 'Building / Development Fund',
                    ];
                    foreach ($reserve_labels as $k => $label):
                        $v = $balanceData['reserves'][$k] ?? 0;
                        if ($v == 0) continue;
                    ?>
                    <tr><td class="indent"><?php echo $label; ?></td><td class="num"><?php echo fmt($v); ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="subtotal-row"><td><strong>Total Revenue Reserves</strong></td><td class="num"><strong><?php echo fmt($balanceData['total_reserves']); ?></strong></td></tr>
                    </tbody>
                </table>

                <table class="report-table" style="margin-bottom:12px;">
                    <tbody>
                    <tr>
                        <td>Accumulated Surplus / (Deficit)</td>
                        <td class="num"><?php echo fmt($balanceData['retained_earnings']); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>TOTAL MEMBERS&apos; EQUITY</strong></td>
                        <td class="num"><strong><?php echo fmt($balanceData['total_equity']); ?></strong></td>
                    </tr>
                    </tbody>
                </table>

                <?php if ($balanceData['is_balanced']): ?>
                    <div class="balance-check-ok">&#10003; Balance Sheet verified — Assets = Liabilities + Equity</div>
                <?php else: ?>
                    <div class="balance-check-err">&#9888; Balance sheet difference: &#8358;<?php echo fmt(abs($balanceData['difference'])); ?> — please investigate</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<!-- =========================================================
     SECTION 4: CASH FLOW STATEMENT
========================================================= -->
<?php if ($cashflowData): ?>
<div class="section-card report-section page-break">
    <div class="section-header">
        <h2>Statement of Cash Flows (Indirect Method)</h2>
        <p>For the Period Ended: <?php echo htmlspecialchars($selectedPeriodName); ?></p>
    </div>
    <div style="padding:0;">
        <table class="report-table">
            <thead>
                <tr><th style="width:65%">Particulars</th><th class="num" style="width:17%">&#8358;</th><th class="num" style="width:18%">&#8358;</th></tr>
            </thead>
            <tbody>
                <!-- OPERATING -->
                <tr><td colspan="3" style="font-weight:700; color:#1e3a5f; background:#f7f9ff; padding:8px 12px;">OPERATING ACTIVITIES</td></tr>
                <tr><td class="indent">Surplus for the period</td><td class="num"><?php echo fmt($cashflowData['operating']['net_profit']); ?></td><td></td></tr>
                <?php if ($cashflowData['operating']['depreciation'] > 0): ?>
                <tr><td class="indent2">Add: Depreciation (non-cash charge)</td><td class="num"><?php echo fmt($cashflowData['operating']['depreciation']); ?></td><td></td></tr>
                <?php endif; ?>
                <?php foreach ($cashflowData['operating']['working_capital'] as $k => $v): if ($v == 0) continue; ?>
                <tr>
                    <td class="indent2">
                        <?php
                        $wc_labels = [
                            'member_loans'  => '(Increase)/Decrease in Member Loans',
                            'receivables'   => '(Increase)/Decrease in Receivables',
                            'payables'      => 'Increase/(Decrease) in Payables',
                            'other_payables'=> 'Increase/(Decrease) in Other Payables',
                            'inventory'     => '(Increase)/Decrease in Stock in Trade',
                        ];
                        echo $wc_labels[$k] ?? str_replace('_', ' ', ucwords($k, '_'));
                        ?>
                    </td>
                    <td class="num"><?php echo fmt($v, true); ?></td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
                <tr class="subtotal-row">
                    <td><strong>Net Cash from Operating Activities</strong></td>
                    <td></td>
                    <td class="num"><strong><?php echo fmt($cashflowData['operating']['net_cashflow_operating'], true); ?></strong></td>
                </tr>

                <!-- INVESTING -->
                <tr><td colspan="3" style="font-weight:700; color:#1e3a5f; background:#f7f9ff; padding:8px 12px;">INVESTING ACTIVITIES</td></tr>
                <?php if ($cashflowData['investing']['fixed_asset_purchases'] > 0): ?>
                <tr><td class="indent">Purchase of Fixed Assets</td><td class="num">(<?php echo fmt($cashflowData['investing']['fixed_asset_purchases']); ?>)</td><td></td></tr>
                <?php endif; ?>
                <?php if ($cashflowData['investing']['fixed_asset_proceeds'] > 0): ?>
                <tr><td class="indent">Proceeds from Disposal of Fixed Assets</td><td class="num"><?php echo fmt($cashflowData['investing']['fixed_asset_proceeds']); ?></td><td></td></tr>
                <?php endif; ?>
                <tr class="subtotal-row">
                    <td><strong>Net Cash from Investing Activities</strong></td>
                    <td></td>
                    <td class="num"><strong><?php echo fmt($cashflowData['investing']['net_cashflow_investing'], true); ?></strong></td>
                </tr>

                <!-- FINANCING -->
                <tr><td colspan="3" style="font-weight:700; color:#1e3a5f; background:#f7f9ff; padding:8px 12px;">FINANCING ACTIVITIES</td></tr>
                <tr><td class="indent">Net Change in Members&apos; Shares &amp; Savings</td><td class="num"><?php echo fmt($cashflowData['financing']['members_fund_change'], true); ?></td><td></td></tr>
                <?php if ($cashflowData['financing']['borrowed_loans_change'] != 0): ?>
                <tr><td class="indent">Proceeds/(Repayment) of External Borrowings</td><td class="num"><?php echo fmt($cashflowData['financing']['borrowed_loans_change'], true); ?></td><td></td></tr>
                <?php endif; ?>
                <tr class="subtotal-row">
                    <td><strong>Net Cash from Financing Activities</strong></td>
                    <td></td>
                    <td class="num"><strong><?php echo fmt($cashflowData['financing']['net_cashflow_financing'], true); ?></strong></td>
                </tr>

                <!-- SUMMARY -->
                <tr><td style="padding:6px 12px;"></td><td></td><td></td></tr>
                <tr class="subtotal-row">
                    <td><strong>NET INCREASE / (DECREASE) IN CASH &amp; CASH EQUIVALENTS</strong></td>
                    <td></td>
                    <td class="num"><strong><?php echo fmt($cashflowData['net_cashflow'], true); ?></strong></td>
                </tr>
                <tr>
                    <td class="indent">Cash and Cash Equivalents — Beginning of Period</td>
                    <td></td>
                    <td class="num"><?php echo fmt($cashflowData['cash_beginning']); ?></td>
                </tr>
                <tr class="total-row">
                    <td><strong>CASH AND CASH EQUIVALENTS — END OF PERIOD</strong></td>
                    <td></td>
                    <td class="num"><strong><?php echo fmt($cashflowData['cash_ending']); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <?php if (isset($cashflowData['verification'])): ?>
        <div style="padding:10px 18px;">
            <?php if ($cashflowData['verification']): ?>
                <div class="balance-check-ok">&#10003; Cash flow verified — opening + net movement = closing balance</div>
            <?php else: ?>
                <div class="balance-check-err">&#9888; Cash flow mismatch detected — reconciliation required</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>


<!-- =========================================================
     SECTION 5: TRIAL BALANCE (SUMMARY)
========================================================= -->
<?php if ($trialBalance && !empty($trialBalance['accounts'])): ?>
<div class="section-card report-section page-break">
    <div class="section-header">
        <h2>Trial Balance</h2>
        <p>As at: <?php echo htmlspecialchars($selectedPeriodName); ?></p>
    </div>
    <div style="padding:0;">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th>Type</th>
                    <th class="num">Debit &#8358;</th>
                    <th class="num">Credit &#8358;</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($trialBalance['accounts'] as $acc): ?>
                <tr>
                    <td><?php echo htmlspecialchars($acc['account_code']); ?></td>
                    <td><?php echo htmlspecialchars($acc['account_name']); ?></td>
                    <td style="font-size:0.75rem; color:#666;"><?php echo ucfirst($acc['account_type']); ?></td>
                    <td class="num"><?php echo $acc['debit_balance'] > 0 ? fmt($acc['debit_balance']) : '—'; ?></td>
                    <td class="num"><?php echo $acc['credit_balance'] > 0 ? fmt($acc['credit_balance']) : '—'; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3"><strong>TOTALS</strong></td>
                    <td class="num"><strong><?php echo fmt($trialBalance['totals']['debit']); ?></strong></td>
                    <td class="num"><strong><?php echo fmt($trialBalance['totals']['credit']); ?></strong></td>
                </tr>
                <tr>
                    <td colspan="3"
                        style="font-size:0.8rem; padding:8px 12px;
                               color:<?php echo $trialBalance['is_balanced'] ? '#065f46' : '#991b1b'; ?>; font-weight:600;">
                        <?php if ($trialBalance['is_balanced']): ?>
                            &#10003; Trial Balance is balanced
                        <?php else: ?>
                            &#9888; Difference: &#8358;<?php echo fmt(abs($trialBalance['totals']['difference'])); ?>
                        <?php endif; ?>
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>


<!-- =========================================================
     SECTION 6: NOTES TO THE FINANCIAL STATEMENTS
========================================================= -->
<div class="section-card report-section page-break">
    <div class="section-header">
        <h2>Notes to the Financial Statements</h2>
        <p>For the Period Ended: <?php echo htmlspecialchars($selectedPeriodName); ?></p>
    </div>

    <!-- NOTE 1: Member Loan Account -->
    <?php if (!empty($notes['loan'])): $n = $notes['loan']; ?>
    <div class="note-heading">Note 1 — Member Loan Account (Account 1110)</div>
    <div style="padding:0 0 0 0;">
        <table class="report-table">
            <tbody>
            <tr><td class="indent">Opening Balance</td><td class="num"><?php echo fmt($n['opening_balance']); ?></td></tr>
            <tr><td class="indent">Add: Loans Disbursed During Period</td><td class="num"><?php echo fmt($n['loans_disbursed']); ?></td></tr>
            <tr><td class="indent">Less: Loans Recovered During Period</td><td class="num">(<?php echo fmt($n['loans_recovered']); ?>)</td></tr>
            <tr class="subtotal-row"><td><strong>Closing Balance</strong></td><td class="num"><strong><?php echo fmt($n['closing_balance']); ?></strong></td></tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- NOTE 2: Members' Fund Movement -->
    <?php if (!empty($notes['members'])): $n = $notes['members']; ?>
    <div class="note-heading">Note 2 — Members&apos; Fund Movement</div>
    <div style="padding:0;">
        <table class="report-table">
            <thead>
                <tr>
                    <th></th>
                    <th class="num">Opening &#8358;</th>
                    <th class="num">Contributions &#8358;</th>
                    <th class="num">Withdrawals &#8358;</th>
                    <th class="num">Closing &#8358;</th>
                </tr>
            </thead>
            <tbody>
            <tr>
                <td>Ordinary Shares</td>
                <td class="num"><?php echo fmt($n['shares']['opening']); ?></td>
                <td class="num"><?php echo fmt($n['shares']['contributions']); ?></td>
                <td class="num">(<?php echo fmt($n['shares']['withdrawals']); ?>)</td>
                <td class="num"><?php echo fmt($n['shares']['closing']); ?></td>
            </tr>
            <tr>
                <td>Ordinary Savings</td>
                <td class="num"><?php echo fmt($n['savings']['opening']); ?></td>
                <td class="num"><?php echo fmt($n['savings']['contributions']); ?></td>
                <td class="num">(<?php echo fmt($n['savings']['withdrawals']); ?>)</td>
                <td class="num"><?php echo fmt($n['savings']['closing']); ?></td>
            </tr>
            <tr>
                <td>Special Savings</td>
                <td class="num"><?php echo fmt($n['special_savings']['opening']); ?></td>
                <td class="num"><?php echo fmt($n['special_savings']['contributions']); ?></td>
                <td class="num">(<?php echo fmt($n['special_savings']['withdrawals']); ?>)</td>
                <td class="num"><?php echo fmt($n['special_savings']['closing']); ?></td>
            </tr>
            <tr class="subtotal-row">
                <td><strong>Total Members&apos; Fund</strong></td>
                <td class="num"></td><td class="num"></td><td class="num"></td>
                <td class="num"><strong><?php echo fmt($n['total_members_fund']); ?></strong></td>
            </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- NOTE 3-6: Reserve Fund Movements -->
    <?php if (!empty($notes['reserves'])): ?>
    <div class="note-heading">Notes 3–7 — Reserve Fund Movements</div>
    <div style="padding:0;">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Fund</th>
                    <th class="num">Opening &#8358;</th>
                    <th class="num">Transfers In &#8358;</th>
                    <th class="num">Expenditure &#8358;</th>
                    <th class="num">Closing &#8358;</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $reserve_note_labels = [
                'statutory_reserve' => 'Statutory Reserve Fund (Note 3)',
                'general_reserve'   => 'General Reserve (Note 4)',
                'education_fund'    => 'Education Fund (Note 5)',
                'welfare_fund'      => 'Welfare Fund (Note 6)',
                'building_fund'     => 'Building / Development Fund (Note 7)',
            ];
            foreach ($notes['reserves'] as $key => $r):
                if ($r['closing'] == 0 && $r['opening'] == 0) continue;
            ?>
            <tr>
                <td><?php echo $reserve_note_labels[$key] ?? ucwords(str_replace('_', ' ', $key)); ?></td>
                <td class="num"><?php echo fmt($r['opening']); ?></td>
                <td class="num"><?php echo fmt($r['transfers_in']); ?></td>
                <td class="num">(<?php echo fmt($r['expenses_paid']); ?>)</td>
                <td class="num"><?php echo fmt($r['closing']); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- NOTE 8: Fixed Assets -->
    <?php if (!empty($notes['assets']) && !empty($notes['assets']['rows'])): $fa = $notes['assets']; ?>
    <div class="note-heading">Note 8 — Fixed Assets Register</div>
    <div style="padding:0;">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Asset Category</th>
                    <th class="num">Cost &#8358;</th>
                    <th class="num">Acc. Depreciation &#8358;</th>
                    <th class="num">Net Book Value &#8358;</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($fa['rows'] as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['label']); ?></td>
                <td class="num"><?php echo fmt($row['cost']); ?></td>
                <td class="num">(<?php echo fmt($row['depn']); ?>)</td>
                <td class="num"><?php echo fmt($row['nbv']); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="subtotal-row">
                <td><strong>Total Fixed Assets</strong></td>
                <td class="num"><strong><?php echo fmt($fa['total_cost']); ?></strong></td>
                <td class="num"><strong>(<?php echo fmt($fa['total_depn']); ?>)</strong></td>
                <td class="num"><strong><?php echo fmt($fa['total_nbv']); ?></strong></td>
            </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- NOTE 9: Membership Strength -->
    <?php if (!empty($notes['strength'])): $n = $notes['strength']; ?>
    <div class="note-heading">Note 9 — Membership Strength</div>
    <div style="padding:0;">
        <table class="report-table">
            <tbody>
            <tr><td class="indent">Membership at beginning of period</td><td class="num"><?php echo number_format($n['opening_members']); ?></td></tr>
            <tr><td class="indent">Add: New members admitted</td><td class="num"><?php echo number_format($n['new_members']); ?></td></tr>
            <tr><td class="indent">Less: Members withdrawn / ceased</td><td class="num">(<?php echo number_format($n['exited_members']); ?>)</td></tr>
            <tr class="subtotal-row"><td><strong>Membership at end of period</strong></td><td class="num"><strong><?php echo number_format($n['closing_members']); ?></strong></td></tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Accounting Policies -->
    <div class="note-heading">Note 10 — Summary of Significant Accounting Policies</div>
    <div style="padding:14px 18px; font-size:0.8rem; color:#444; line-height:1.7;">
        <p><strong>Basis of Preparation:</strong> These financial statements have been prepared on the historical cost basis in accordance with
        the Nigerian Statement of Accounting Standards and generally accepted accounting principles applicable to cooperative societies
        in Nigeria as prescribed by the Cooperative Societies Act and ICAN guidelines.</p>
        <p style="margin-top:8px;"><strong>Revenue Recognition:</strong> Entrance fees are recognised in the period of admission. Interest on member loans is
        recognised on the accruals basis using the effective interest method.</p>
        <p style="margin-top:8px;"><strong>Member Loans:</strong> Loans to members are stated at outstanding principal amounts. Provisions for doubtful loans
        are made where recovery is considered uncertain.</p>
        <p style="margin-top:8px;"><strong>Fixed Assets &amp; Depreciation:</strong> Fixed assets are stated at cost less accumulated depreciation. Depreciation is
        charged on a straight-line basis over the estimated useful lives of the assets.</p>
        <p style="margin-top:8px;"><strong>Statutory Reserve Fund:</strong> A minimum of 10% of annual surplus is transferred to the Statutory Reserve Fund in
        compliance with the Cooperative Societies Act.</p>
        <p style="margin-top:8px;"><strong>Period of Account:</strong> These financial statements cover the payroll period designated as
        "<?php echo htmlspecialchars($selectedPeriodName); ?>".</p>
    </div>
</div>


<!-- =========================================================
     SECTION 7: CERTIFICATION & SIGNATURES
========================================================= -->
<div class="section-card report-section">
    <div class="section-header">
        <h2>Certification &amp; Approval</h2>
    </div>
    <div style="padding:24px 28px;">
        <p style="font-size:0.85rem; color:#444; margin-bottom:24px;">
            We, the undersigned officers of <strong>OOUTH Cooperative Multi-Purpose Society Limited</strong>, certify that
            the foregoing financial statements present a true and fair view of the affairs of the Society as at the end of the
            reporting period and of its surplus for the period then ended, in accordance with the Nigerian Cooperative Societies Act
            and generally accepted accounting principles.
        </p>
        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:32px; margin-top:32px;">
            <?php
            $signatories = [
                'President / Chairman'  => 'Name: ____________________________<br/>Date: ____________________________<br/>Signature: ________________________',
                'Secretary'             => 'Name: ____________________________<br/>Date: ____________________________<br/>Signature: ________________________',
                'Treasurer'             => 'Name: ____________________________<br/>Date: ____________________________<br/>Signature: ________________________',
            ];
            foreach ($signatories as $role => $fields):
            ?>
            <div style="text-align:center;">
                <div style="border-top: 2px solid #1e3a5f; padding-top:8px; margin-top:40px; font-size:0.78rem; color:#333; line-height:2;">
                    <strong><?php echo $role; ?></strong><br/>
                    <?php echo $fields; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:32px; padding:16px; background:#f7f9ff; border:1px solid #c0cfe0; border-radius:4px; font-size:0.78rem; color:#666;">
            <strong>Auditor&apos;s Report:</strong> &nbsp;
            These accounts have been audited in accordance with Nigerian Standards on Auditing (NSA).<br/>
            Firm: ______________________________________________ &nbsp;&nbsp;
            Date: ___________________ &nbsp;&nbsp;
            Stamp: ___________________________
        </div>
    </div>
</div>

<!-- footer note -->
<div style="text-align:center; font-size:0.72rem; color:#aaa; margin-top:24px; padding-bottom:32px;">
    Generated by OOUTH Cooperative Management System &mdash; <?php echo date('d F Y, g:i A'); ?><br/>
    This report is for the exclusive use of OOUTH Cooperative Society officers and members.
</div>

</div><!-- .report-container -->

<?php require_once('includes/footer.php'); ?>
