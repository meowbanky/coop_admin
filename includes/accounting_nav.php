<?php
/**
 * Shared navigation bar for all accounting pages.
 * Include this immediately after require_once('includes/header.php').
 */
$_acct_page = basename($_SERVER['SCRIPT_NAME']);

$_acct_links = [
    ['file' => 'coop_annual_report.php',       'icon' => 'fa-book',          'label' => 'Annual Report'],
    ['file' => 'coop_financial_statements.php', 'icon' => 'fa-bar-chart',     'label' => 'Financials'],
    ['file' => 'coop_trial_balance.php',        'icon' => 'fa-balance-scale',  'label' => 'Trial Balance'],
    ['file' => 'coop_general_ledger.php',       'icon' => 'fa-list-alt',      'label' => 'Ledger'],
    ['file' => 'coop_journal_entries.php',      'icon' => 'fa-pencil-square-o','label' => 'Journal'],
    ['file' => 'coop_chart_of_accounts.php',    'icon' => 'fa-sitemap',       'label' => 'Chart of A/Cs'],
    ['file' => 'coop_member_statement.php',     'icon' => 'fa-user-o',        'label' => 'Member Stmt'],
    ['file' => 'coop_comparative_reports.php',  'icon' => 'fa-line-chart',    'label' => 'Comparative'],
    ['file' => 'coop_bank_reconciliation.php',  'icon' => 'fa-exchange',      'label' => 'Bank Recon'],
    ['file' => 'coop_period_closing.php',       'icon' => 'fa-lock',          'label' => 'Period Close'],
];
?>
<nav style="background:#1e3a5f; padding:0 16px; margin-bottom:24px; overflow-x:auto; white-space:nowrap;">
    <?php foreach ($_acct_links as $_lnk): ?>
        <?php $_active = ($_lnk['file'] === $_acct_page); ?>
        <a href="<?php echo $_lnk['file']; ?>"
           style="display:inline-block; padding:10px 14px; font-size:0.78rem; font-weight:600; text-decoration:none;
                  color:<?php echo $_active ? '#fff' : 'rgba(255,255,255,0.65)'; ?>;
                  border-bottom:3px solid <?php echo $_active ? '#f59e0b' : 'transparent'; ?>;
                  transition:color 0.15s;">
            <i class="fa <?php echo $_lnk['icon']; ?>" style="margin-right:4px;"></i><?php echo $_lnk['label']; ?>
        </a>
    <?php endforeach; ?>
</nav>
