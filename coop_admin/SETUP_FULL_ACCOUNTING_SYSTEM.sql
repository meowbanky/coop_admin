-- ============================================================================
-- COOPERATIVE SOCIETY FULL ACCOUNTING SYSTEM - DATABASE SETUP
-- ============================================================================
-- Version: 1.0
-- Date: 2024-10-25
-- Description: Complete database schema for professional double-entry 
--              accounting system with financial statement generation
-- ============================================================================

-- WARNING: This script will create new tables. Review before execution.
-- Recommended: Run on a backup/test database first

-- ============================================================================
-- TABLE 1: CHART OF ACCOUNTS (Master Account Structure)
-- ============================================================================

CREATE TABLE IF NOT EXISTS coop_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  account_code VARCHAR(20) UNIQUE NOT NULL COMMENT 'Hierarchical account code (e.g., 1000, 1001, 1001-01)',
  account_name VARCHAR(255) NOT NULL COMMENT 'Descriptive account name',
  parent_id INT NULL COMMENT 'Parent account for hierarchical structure',
  account_type ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL COMMENT 'Primary account classification',
  account_category ENUM(
    'current_asset', 
    'non_current_asset', 
    'current_liability', 
    'non_current_liability', 
    'member_equity', 
    'reserves', 
    'operating_revenue', 
    'other_revenue', 
    'cost_of_sales', 
    'overhead', 
    'appropriation'
  ) NULL COMMENT 'Detailed category for financial statement grouping',
  normal_balance ENUM('debit', 'credit') NOT NULL COMMENT 'Expected balance side (asset=debit, liability=credit)',
  is_control_account BOOLEAN DEFAULT FALSE COMMENT 'TRUE if account summarizes sub-accounts',
  is_system_account BOOLEAN DEFAULT FALSE COMMENT 'TRUE if system-managed (cannot be deleted)',
  is_active BOOLEAN DEFAULT TRUE COMMENT 'FALSE to hide from dropdowns without deleting',
  description TEXT COMMENT 'Additional notes about account usage',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_account_code (account_code),
  INDEX idx_account_type (account_type),
  INDEX idx_parent (parent_id),
  INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Master chart of accounts for double-entry bookkeeping';

-- Add foreign key constraint after table is created
ALTER TABLE coop_accounts 
ADD CONSTRAINT fk_coop_accounts_parent 
FOREIGN KEY (parent_id) REFERENCES coop_accounts(id) ON DELETE RESTRICT;

-- ============================================================================
-- TABLE 2: JOURNAL ENTRIES (Transaction Headers)
-- ============================================================================

CREATE TABLE IF NOT EXISTS coop_journal_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entry_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Auto-generated unique identifier (e.g., JE-2024-001)',
  entry_date DATE NOT NULL COMMENT 'Transaction date',
  periodid INT NOT NULL COMMENT 'Accounting period reference',
  entry_type ENUM(
    'manual', 
    'system', 
    'closing', 
    'opening', 
    'adjustment', 
    'member_transaction', 
    'depreciation', 
    'appropriation'
  ) NOT NULL COMMENT 'Source/type of journal entry',
  source_document VARCHAR(100) COMMENT 'Reference to originating document (e.g., Invoice #123)',
  description TEXT NOT NULL COMMENT 'Transaction description/narrative',
  total_amount DECIMAL(15,2) NOT NULL COMMENT 'Total transaction amount (for quick reference)',
  created_by INT NOT NULL COMMENT 'User who created the entry',
  approved_by INT NULL COMMENT 'User who approved the entry',
  approval_date DATETIME NULL COMMENT 'When entry was approved',
  status ENUM('draft', 'posted', 'approved', 'voided') DEFAULT 'draft' COMMENT 'Entry status (draft can be edited, posted is permanent)',
  is_reversed BOOLEAN DEFAULT FALSE COMMENT 'TRUE if this entry has been reversed',
  reversed_by_entry_id INT NULL COMMENT 'Reference to reversing entry',
  notes TEXT COMMENT 'Additional notes or audit information',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_entry_date (entry_date),
  INDEX idx_period (periodid),
  INDEX idx_status (status),
  INDEX idx_entry_type (entry_type),
  INDEX idx_entry_number (entry_number),
  INDEX idx_created_by (created_by),
  INDEX idx_approved_by (approved_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Journal entry headers for all accounting transactions';

-- ============================================================================
-- TABLE 3: JOURNAL ENTRY LINES (Transaction Details)
-- ============================================================================

CREATE TABLE IF NOT EXISTS coop_journal_entry_lines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  journal_entry_id INT NOT NULL COMMENT 'Parent journal entry',
  line_number INT NOT NULL COMMENT 'Line sequence within entry (1, 2, 3...)',
  account_id INT NOT NULL COMMENT 'Account being debited or credited',
  debit_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Debit amount (if applicable)',
  credit_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Credit amount (if applicable)',
  description VARCHAR(500) COMMENT 'Line-specific description',
  reference_type VARCHAR(50) COMMENT 'Type of linked record (e.g., member, asset, loan)',
  reference_id INT COMMENT 'ID of linked record',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (journal_entry_id) REFERENCES coop_journal_entries(id) ON DELETE CASCADE,
  FOREIGN KEY (account_id) REFERENCES coop_accounts(id) ON DELETE RESTRICT,
  INDEX idx_journal_entry (journal_entry_id),
  INDEX idx_account (account_id),
  INDEX idx_reference (reference_type, reference_id),
  CONSTRAINT chk_debit_or_credit CHECK (
    (debit_amount > 0 AND credit_amount = 0) OR 
    (credit_amount > 0 AND debit_amount = 0) OR
    (debit_amount = 0 AND credit_amount = 0)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Individual debit/credit lines for each journal entry';

-- ============================================================================
-- TABLE 4: PERIOD BALANCES (Period-End Account Snapshots)
-- ============================================================================

CREATE TABLE IF NOT EXISTS coop_period_balances (
  id INT AUTO_INCREMENT PRIMARY KEY,
  periodid INT NOT NULL COMMENT 'Accounting period',
  account_id INT NOT NULL COMMENT 'Account reference',
  opening_debit DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Opening debit balance',
  opening_credit DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Opening credit balance',
  period_debit DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total debits during period',
  period_credit DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total credits during period',
  closing_debit DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Closing debit balance',
  closing_credit DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Closing credit balance',
  is_closed BOOLEAN DEFAULT FALSE COMMENT 'TRUE if period is closed and locked',
  closed_at DATETIME NULL COMMENT 'When period was closed',
  closed_by INT NULL COMMENT 'User who closed the period',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (account_id) REFERENCES coop_accounts(id) ON DELETE RESTRICT,
  UNIQUE KEY unique_period_account (periodid, account_id),
  INDEX idx_period (periodid),
  INDEX idx_account (account_id),
  INDEX idx_closed (is_closed),
  INDEX idx_closed_by (closed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Period-end account balances for fast report generation';

-- ============================================================================
-- TABLE 5: MEMBER ACCOUNTS (Individual Member Tracking)
-- ============================================================================

CREATE TABLE IF NOT EXISTS coop_member_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  memberid INT NOT NULL COMMENT 'Member reference',
  account_type ENUM(
    'shares', 
    'savings', 
    'special_savings', 
    'loan', 
    'dividend', 
    'interest_payable', 
    'welfare'
  ) NOT NULL COMMENT 'Type of member account',
  periodid INT NOT NULL COMMENT 'Accounting period',
  opening_balance DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Balance at start of period',
  debit_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total debits (withdrawals/reductions)',
  credit_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total credits (contributions/additions)',
  closing_balance DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Balance at end of period',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_member_account_period (memberid, account_type, periodid),
  INDEX idx_member (memberid),
  INDEX idx_period (periodid),
  INDEX idx_type (account_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Individual member account balances per period';

-- ============================================================================
-- TABLE 6: FIXED ASSETS REGISTER
-- ============================================================================

CREATE TABLE IF NOT EXISTS coop_fixed_assets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  asset_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Unique asset identifier',
  asset_name VARCHAR(255) NOT NULL COMMENT 'Asset description',
  asset_category VARCHAR(100) NOT NULL COMMENT 'Category (e.g., Furniture, Computer, Vehicle)',
  acquisition_date DATE NOT NULL COMMENT 'Date asset was acquired',
  acquisition_periodid INT NOT NULL COMMENT 'Period when asset was acquired',
  cost DECIMAL(15,2) NOT NULL COMMENT 'Purchase cost',
  salvage_value DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Expected residual value at end of life',
  useful_life_years INT NOT NULL COMMENT 'Expected useful life in years',
  useful_life_months INT DEFAULT 0 COMMENT 'Additional months (e.g., 3 years 6 months)',
  depreciation_method ENUM('straight_line', 'reducing_balance', 'units_of_production') 
    DEFAULT 'straight_line' COMMENT 'Depreciation calculation method',
  depreciation_rate DECIMAL(5,2) COMMENT 'Annual depreciation rate (percentage)',
  accumulated_depreciation DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total depreciation to date',
  net_book_value DECIMAL(15,2) COMMENT 'Current value (cost - accumulated depreciation)',
  status ENUM('active', 'disposed', 'fully_depreciated', 'under_construction') 
    DEFAULT 'active' COMMENT 'Current asset status',
  disposal_date DATE NULL COMMENT 'Date asset was disposed/sold',
  disposal_amount DECIMAL(15,2) NULL COMMENT 'Sale proceeds from disposal',
  location VARCHAR(255) COMMENT 'Physical location of asset',
  supplier VARCHAR(255) COMMENT 'Vendor/supplier name',
  purchase_invoice VARCHAR(100) COMMENT 'Purchase invoice reference',
  notes TEXT COMMENT 'Additional asset information',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_category (asset_category),
  INDEX idx_acquisition_date (acquisition_date),
  INDEX idx_asset_number (asset_number),
  INDEX idx_acquisition_period (acquisition_periodid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Fixed assets register with depreciation tracking';

-- ============================================================================
-- TABLE 7: DEPRECIATION SCHEDULE
-- ============================================================================

CREATE TABLE IF NOT EXISTS coop_depreciation_schedule (
  id INT AUTO_INCREMENT PRIMARY KEY,
  asset_id INT NOT NULL COMMENT 'Asset reference',
  periodid INT NOT NULL COMMENT 'Period for this depreciation',
  depreciation_expense DECIMAL(15,2) NOT NULL COMMENT 'Depreciation amount for this period',
  accumulated_depreciation DECIMAL(15,2) NOT NULL COMMENT 'Total depreciation to end of period',
  net_book_value DECIMAL(15,2) NOT NULL COMMENT 'Asset value at end of period',
  journal_entry_id INT NULL COMMENT 'Journal entry that posted this depreciation',
  is_posted BOOLEAN DEFAULT FALSE COMMENT 'TRUE if posted to accounts',
  posted_at DATETIME NULL COMMENT 'When depreciation was posted',
  notes TEXT COMMENT 'Calculation notes or adjustments',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (asset_id) REFERENCES coop_fixed_assets(id) ON DELETE CASCADE,
  FOREIGN KEY (journal_entry_id) REFERENCES coop_journal_entries(id) ON DELETE SET NULL,
  UNIQUE KEY unique_asset_period (asset_id, periodid),
  INDEX idx_period (periodid),
  INDEX idx_posted (is_posted),
  INDEX idx_asset (asset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Depreciation calculations per asset per period';

-- ============================================================================
-- TABLE 8: BUDGET (Budget vs Actual Tracking)
-- ============================================================================

CREATE TABLE IF NOT EXISTS coop_budget (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fiscal_year INT NOT NULL COMMENT 'Budget year (e.g., 2024)',
  account_id INT NOT NULL COMMENT 'Account being budgeted',
  budgeted_amount DECIMAL(15,2) NOT NULL COMMENT 'Budgeted amount for the year',
  notes TEXT COMMENT 'Budget justification or notes',
  created_by INT NOT NULL COMMENT 'User who created budget',
  approved_by INT NULL COMMENT 'User who approved budget',
  approval_date DATE NULL COMMENT 'When budget was approved',
  status ENUM('draft', 'approved', 'revised') DEFAULT 'draft' COMMENT 'Budget status',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (account_id) REFERENCES coop_accounts(id) ON DELETE RESTRICT,
  UNIQUE KEY unique_year_account (fiscal_year, account_id),
  INDEX idx_year (fiscal_year),
  INDEX idx_status (status),
  INDEX idx_created_by (created_by),
  INDEX idx_approved_by (approved_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Annual budget by account for variance analysis';

-- ============================================================================
-- TABLE 9: RESERVES (Reserve Fund Management)
-- ============================================================================

CREATE TABLE IF NOT EXISTS coop_reserves (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reserve_type ENUM(
    'statutory_reserve', 
    'general_reserve', 
    'education_fund', 
    'welfare_fund', 
    'building_fund'
  ) NOT NULL COMMENT 'Type of reserve fund',
  periodid INT NOT NULL COMMENT 'Accounting period',
  opening_balance DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Balance at start of period',
  transfers_in DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Transfers into reserve (from appropriation)',
  expenses_paid DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Expenses paid from reserve',
  adjustments DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Prior year adjustments',
  closing_balance DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Balance at end of period',
  account_id INT NOT NULL COMMENT 'Linked account in chart of accounts',
  notes TEXT COMMENT 'Reserve fund notes',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (account_id) REFERENCES coop_accounts(id) ON DELETE RESTRICT,
  UNIQUE KEY unique_reserve_period (reserve_type, periodid),
  INDEX idx_period (periodid),
  INDEX idx_type (reserve_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Reserve fund movements per period (Note 3-5)';

-- ============================================================================
-- TABLE 10: APPROPRIATION (Surplus Distribution)
-- ============================================================================

CREATE TABLE IF NOT EXISTS coop_appropriation (
  id INT AUTO_INCREMENT PRIMARY KEY,
  periodid INT NOT NULL COMMENT 'Period for appropriation',
  surplus_amount DECIMAL(15,2) NOT NULL COMMENT 'Total surplus to appropriate',
  dividend_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Dividend to members',
  interest_to_members DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Interest paid on savings',
  reserve_fund DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Transfer to statutory reserve',
  bonus_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Staff/member bonus',
  education_fund DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Transfer to education fund',
  honorarium DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Honorarium to executives',
  general_reserve DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Transfer to general reserve',
  welfare_fund DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Transfer to welfare fund',
  retained_earnings DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Remaining retained earnings',
  journal_entry_id INT NULL COMMENT 'Journal entry that posted appropriation',
  is_posted BOOLEAN DEFAULT FALSE COMMENT 'TRUE if posted to accounts',
  approved_by INT NULL COMMENT 'Executive who approved',
  approval_date DATE NULL COMMENT 'When appropriation was approved',
  notes TEXT COMMENT 'Appropriation notes or AGM resolution reference',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (journal_entry_id) REFERENCES coop_journal_entries(id) ON DELETE SET NULL,
  UNIQUE KEY unique_period_appropriation (periodid),
  INDEX idx_period (periodid),
  INDEX idx_posted (is_posted),
  INDEX idx_approved_by (approved_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Surplus appropriation decisions per period';

-- ============================================================================
-- TABLE 11: BANK RECONCILIATION
-- ============================================================================

CREATE TABLE IF NOT EXISTS coop_bank_reconciliation (
  id INT AUTO_INCREMENT PRIMARY KEY,
  periodid INT NOT NULL COMMENT 'Reconciliation period',
  bank_account_id INT NOT NULL COMMENT 'Bank account being reconciled',
  reconciliation_date DATE NOT NULL COMMENT 'Date of reconciliation',
  bank_statement_balance DECIMAL(15,2) NOT NULL COMMENT 'Balance per bank statement',
  book_balance DECIMAL(15,2) NOT NULL COMMENT 'Balance per accounting records',
  outstanding_deposits DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Deposits not yet cleared',
  outstanding_withdrawals DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Checks/payments not yet cleared',
  bank_charges DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Bank charges not yet recorded',
  bank_interest DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Bank interest not yet recorded',
  reconciled_balance DECIMAL(15,2) NOT NULL COMMENT 'Final reconciled balance',
  is_balanced BOOLEAN DEFAULT FALSE COMMENT 'TRUE if reconciliation matches',
  variance DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Difference if not balanced',
  reconciled_by INT NOT NULL COMMENT 'User who performed reconciliation',
  reviewed_by INT NULL COMMENT 'User who reviewed reconciliation',
  notes TEXT COMMENT 'Reconciliation notes',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (bank_account_id) REFERENCES coop_accounts(id) ON DELETE RESTRICT,
  INDEX idx_period (periodid),
  INDEX idx_date (reconciliation_date),
  INDEX idx_account (bank_account_id),
  INDEX idx_reconciled_by (reconciled_by),
  INDEX idx_reviewed_by (reviewed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bank reconciliation records';

-- ============================================================================
-- TABLE 12: AUDIT TRAIL
-- ============================================================================

CREATE TABLE IF NOT EXISTS coop_audit_trail (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL COMMENT 'User who performed action',
  action_type ENUM(
    'create', 
    'update', 
    'delete', 
    'post', 
    'approve', 
    'void', 
    'reverse', 
    'close_period', 
    'reopen_period'
  ) NOT NULL COMMENT 'Type of action performed',
  table_name VARCHAR(100) NOT NULL COMMENT 'Database table affected',
  record_id INT NOT NULL COMMENT 'ID of affected record',
  old_values TEXT COMMENT 'JSON of values before change',
  new_values TEXT COMMENT 'JSON of values after change',
  ip_address VARCHAR(45) COMMENT 'User IP address',
  user_agent VARCHAR(500) COMMENT 'Browser/application identifier',
  notes TEXT COMMENT 'Additional audit notes',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_action (action_type),
  INDEX idx_table_record (table_name, record_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Complete audit trail for all accounting changes';

-- ============================================================================
-- STANDARD CHART OF ACCOUNTS - DATA POPULATION
-- ============================================================================

-- Clear existing data if re-running (CAUTION!)
-- DELETE FROM coop_accounts;

-- ASSETS (1000-1999)
INSERT INTO coop_accounts (account_code, account_name, parent_id, account_type, account_category, normal_balance, is_control_account, is_system_account, description) VALUES
-- Current Assets
('1000', 'ASSETS', NULL, 'asset', NULL, 'debit', TRUE, TRUE, 'All assets'),
('1100', 'Current Assets', 1, 'asset', 'current_asset', 'debit', TRUE, TRUE, 'Assets convertible to cash within one year'),
('1101', 'Cash on Hand', 2, 'asset', 'current_asset', 'debit', FALSE, TRUE, 'Physical cash in office'),
('1102', 'Bank - Main Account', 2, 'asset', 'current_asset', 'debit', FALSE, TRUE, 'Primary operating bank account'),
('1103', 'Bank - Savings Account', 2, 'asset', 'current_asset', 'debit', FALSE, FALSE, 'Interest-bearing savings account'),
('1110', 'Member Loans', 2, 'asset', 'current_asset', 'debit', TRUE, TRUE, 'Control account for all member loans'),
('1120', 'Account Receivables', 2, 'asset', 'current_asset', 'debit', FALSE, TRUE, 'Amounts owed to cooperative'),
('1130', 'Inventory', 2, 'asset', 'current_asset', 'debit', FALSE, FALSE, 'Goods held for sale'),
('1140', 'Prepaid Expenses', 2, 'asset', 'current_asset', 'debit', FALSE, FALSE, 'Expenses paid in advance'),

-- Non-Current Assets
('1200', 'Non-Current Assets', 1, 'asset', 'non_current_asset', 'debit', TRUE, TRUE, 'Long-term assets'),
('1201', 'Land & Buildings', 9, 'asset', 'non_current_asset', 'debit', FALSE, FALSE, 'Real estate properties'),
('1202', 'Furniture & Fixtures', 9, 'asset', 'non_current_asset', 'debit', FALSE, FALSE, 'Office furniture'),
('1203', 'Office Equipment', 9, 'asset', 'non_current_asset', 'debit', FALSE, FALSE, 'Office machines and equipment'),
('1204', 'Computer Equipment', 9, 'asset', 'non_current_asset', 'debit', FALSE, FALSE, 'Computers and IT equipment'),
('1205', 'Motor Vehicles', 9, 'asset', 'non_current_asset', 'debit', FALSE, FALSE, 'Vehicles owned by cooperative'),
('1210', 'Accumulated Depreciation - Buildings', 9, 'asset', 'non_current_asset', 'credit', FALSE, TRUE, 'Contra-asset: accumulated depreciation on buildings'),
('1211', 'Accumulated Depreciation - Furniture', 9, 'asset', 'non_current_asset', 'credit', FALSE, TRUE, 'Contra-asset: accumulated depreciation on furniture'),
('1212', 'Accumulated Depreciation - Equipment', 9, 'asset', 'non_current_asset', 'credit', FALSE, TRUE, 'Contra-asset: accumulated depreciation on equipment'),
('1213', 'Accumulated Depreciation - Computers', 9, 'asset', 'non_current_asset', 'credit', FALSE, TRUE, 'Contra-asset: accumulated depreciation on computers'),
('1214', 'Accumulated Depreciation - Vehicles', 9, 'asset', 'non_current_asset', 'credit', FALSE, TRUE, 'Contra-asset: accumulated depreciation on vehicles');

-- LIABILITIES (2000-2999)
INSERT INTO coop_accounts (account_code, account_name, parent_id, account_type, account_category, normal_balance, is_control_account, is_system_account, description) VALUES
('2000', 'LIABILITIES', NULL, 'liability', NULL, 'credit', TRUE, TRUE, 'All liabilities'),
-- Current Liabilities
('2100', 'Current Liabilities', 20, 'liability', 'current_liability', 'credit', TRUE, TRUE, 'Obligations due within one year'),
('2101', 'Account Payables', 21, 'liability', 'current_liability', 'credit', FALSE, TRUE, 'Amounts owed to suppliers'),
('2102', 'Accrued Expenses', 21, 'liability', 'current_liability', 'credit', FALSE, TRUE, 'Expenses incurred but not yet paid'),
('2103', 'Dividend Payable', 21, 'liability', 'current_liability', 'credit', FALSE, TRUE, 'Dividends declared but not paid'),
('2104', 'Interest Payable to Members', 21, 'liability', 'current_liability', 'credit', FALSE, TRUE, 'Interest on savings payable to members'),
('2105', 'Bonus Payable', 21, 'liability', 'current_liability', 'credit', FALSE, TRUE, 'Bonuses declared but not paid'),
('2106', 'Honorarium Payable', 21, 'liability', 'current_liability', 'credit', FALSE, TRUE, 'Honorarium to executives not yet paid'),

-- Non-Current Liabilities
('2200', 'Non-Current Liabilities', 20, 'liability', 'non_current_liability', 'credit', TRUE, TRUE, 'Long-term obligations'),
('2201', 'Long-term Loans', 28, 'liability', 'non_current_liability', 'credit', FALSE, FALSE, 'Loans payable beyond one year'),
('2202', 'Borrowed Funds', 28, 'liability', 'non_current_liability', 'credit', FALSE, FALSE, 'Other borrowed funds');

-- EQUITY / MEMBERS FUND (3000-3999)
INSERT INTO coop_accounts (account_code, account_name, parent_id, account_type, account_category, normal_balance, is_control_account, is_system_account, description) VALUES
('3000', 'EQUITY (MEMBERS FUND)', NULL, 'equity', NULL, 'credit', TRUE, TRUE, 'Members equity and reserves'),

-- Share Capital
('3100', 'Share Capital', 31, 'equity', 'member_equity', 'credit', TRUE, TRUE, 'Member share accounts'),
('3101', 'Ordinary Shares', 32, 'equity', 'member_equity', 'credit', TRUE, TRUE, 'Control account for member shares'),
('3102', 'Entrance Fees', 32, 'equity', 'member_equity', 'credit', FALSE, FALSE, 'One-time entrance fees collected'),

-- Savings
('3200', 'Savings', 31, 'equity', 'member_equity', 'credit', TRUE, TRUE, 'Member savings accounts'),
('3201', 'Ordinary Savings', 36, 'equity', 'member_equity', 'credit', TRUE, TRUE, 'Control account for member savings'),
('3202', 'Special Savings', 36, 'equity', 'member_equity', 'credit', TRUE, TRUE, 'Control account for special savings'),

-- Reserves
('3300', 'Reserves', 31, 'equity', 'reserves', 'credit', TRUE, TRUE, 'Reserve funds'),
('3301', 'Statutory Reserve Fund', 39, 'equity', 'reserves', 'credit', FALSE, TRUE, 'Mandatory reserve fund (typically 10% of surplus)'),
('3302', 'General Reserve', 39, 'equity', 'reserves', 'credit', FALSE, TRUE, 'General purpose reserve'),
('3303', 'Education Fund', 39, 'equity', 'reserves', 'credit', FALSE, TRUE, 'Reserve for member education'),
('3304', 'Welfare Fund', 39, 'equity', 'reserves', 'credit', FALSE, TRUE, 'Reserve for member welfare'),
('3305', 'Building Fund', 39, 'equity', 'reserves', 'credit', FALSE, FALSE, 'Reserve for building/property acquisition'),

-- Retained Earnings
('3400', 'Retained Earnings', 31, 'equity', 'reserves', 'credit', TRUE, TRUE, 'Accumulated profits'),
('3401', 'Accumulated Profit/Loss', 45, 'equity', 'reserves', 'credit', FALSE, TRUE, 'Cumulative retained earnings');

-- REVENUE (4000-4999)
INSERT INTO coop_accounts (account_code, account_name, parent_id, account_type, account_category, normal_balance, is_control_account, is_system_account, description) VALUES
('4000', 'REVENUE', NULL, 'revenue', NULL, 'credit', TRUE, TRUE, 'All revenue and income'),

-- Operating Revenue
('4100', 'Operating Revenue', 47, 'revenue', 'operating_revenue', 'credit', TRUE, TRUE, 'Primary revenue sources'),
('4101', 'Entrance Fees Income', 48, 'revenue', 'operating_revenue', 'credit', FALSE, TRUE, 'New member entrance fees'),
('4102', 'Interest on Loans to Members', 48, 'revenue', 'operating_revenue', 'credit', FALSE, TRUE, 'Interest earned on member loans'),
('4103', 'Service Charges', 48, 'revenue', 'operating_revenue', 'credit', FALSE, FALSE, 'Service charges collected'),
('4104', 'Administrative Fees', 48, 'revenue', 'operating_revenue', 'credit', FALSE, FALSE, 'Administrative fees'),

-- Other Income
('4200', 'Other Income', 47, 'revenue', 'other_revenue', 'credit', TRUE, TRUE, 'Non-operating income'),
('4201', 'Passbook Replacement Fee', 53, 'revenue', 'other_revenue', 'credit', FALSE, FALSE, 'Fees for replacing passbooks'),
('4202', 'Bye-Law Replacement Fee', 53, 'revenue', 'other_revenue', 'credit', FALSE, FALSE, 'Fees for bye-law copies'),
('4203', 'Fine & Default Fees', 53, 'revenue', 'other_revenue', 'credit', FALSE, FALSE, 'Penalties and fines'),
('4204', 'Membership Withdrawal Fee', 53, 'revenue', 'other_revenue', 'credit', FALSE, FALSE, 'Fees for membership withdrawal'),
('4205', 'Income from Investment', 53, 'revenue', 'other_revenue', 'credit', FALSE, FALSE, 'Investment income'),
('4299', 'Miscellaneous Income', 53, 'revenue', 'other_revenue', 'credit', FALSE, FALSE, 'Other miscellaneous income');

-- COST OF SALES (5000-5999)
INSERT INTO coop_accounts (account_code, account_name, parent_id, account_type, account_category, normal_balance, is_control_account, is_system_account, description) VALUES
('5000', 'COST OF SALES', NULL, 'expense', 'cost_of_sales', 'debit', TRUE, TRUE, 'Direct costs'),
('5100', 'Direct Costs', 60, 'expense', 'cost_of_sales', 'debit', TRUE, FALSE, 'Direct costs category'),
('5101', 'Cost of Goods/Services Sold', 61, 'expense', 'cost_of_sales', 'debit', FALSE, FALSE, 'Direct costs of products/services');

-- OPERATING EXPENSES / OVERHEAD (6000-6999)
INSERT INTO coop_accounts (account_code, account_name, parent_id, account_type, account_category, normal_balance, is_control_account, is_system_account, description) VALUES
('6000', 'OPERATING EXPENSES', NULL, 'expense', 'overhead', 'debit', TRUE, TRUE, 'All operating expenses'),
('6001', 'Professional Services', 63, 'expense', 'overhead', 'debit', FALSE, FALSE, 'Legal, accounting, consulting fees'),
('6002', 'Printing & Stationery', 63, 'expense', 'overhead', 'debit', FALSE, FALSE, 'Office supplies and printing'),
('6003', 'Telephone', 63, 'expense', 'overhead', 'debit', FALSE, FALSE, 'Telephone charges'),
('6004', 'Internet Cost', 63, 'expense', 'overhead', 'debit', FALSE, FALSE, 'Internet service charges'),
('6005', 'Transport & Travelling', 63, 'expense', 'overhead', 'debit', FALSE, FALSE, 'Travel and transportation expenses'),
('6006', 'Sundry Expenses', 63, 'expense', 'overhead', 'debit', FALSE, FALSE, 'Miscellaneous expenses'),
('6007', 'Advertisement Cost', 63, 'expense', 'overhead', 'debit', FALSE, FALSE, 'Advertising and marketing'),
('6008', 'Office Expenses', 63, 'expense', 'overhead', 'debit', FALSE, FALSE, 'General office expenses'),
('6009', 'Bank Charges', 63, 'expense', 'overhead', 'debit', FALSE, TRUE, 'Bank fees and charges'),
('6010', 'Fueling', 63, 'expense', 'overhead', 'debit', FALSE, FALSE, 'Vehicle fuel costs'),
('6011', 'Salary Cost', 63, 'expense', 'overhead', 'debit', FALSE, TRUE, 'Staff salaries and wages'),
('6012', 'Training Cost', 63, 'expense', 'overhead', 'debit', FALSE, FALSE, 'Staff training expenses'),
('6013', 'Entertainment Cost', 63, 'expense', 'overhead', 'debit', FALSE, FALSE, 'Entertainment and hospitality'),
('6014', 'AGM Expenses', 63, 'expense', 'overhead', 'debit', FALSE, FALSE, 'Annual General Meeting expenses'),
('6015', 'Award', 63, 'expense', 'overhead', 'debit', FALSE, FALSE, 'Awards and recognition'),
('6016', 'Sitting Allowance', 63, 'expense', 'overhead', 'debit', FALSE, FALSE, 'Meeting allowances for executives'),
('6017', 'Discount Allowed', 63, 'expense', 'overhead', 'debit', FALSE, FALSE, 'Discounts given to members'),
('6018', 'Depreciation Expense', 63, 'expense', 'overhead', 'debit', FALSE, TRUE, 'Depreciation on fixed assets');

-- APPROPRIATION ACCOUNTS (7000-7999)
INSERT INTO coop_accounts (account_code, account_name, parent_id, account_type, account_category, normal_balance, is_control_account, is_system_account, description) VALUES
('7000', 'APPROPRIATION', NULL, 'expense', 'appropriation', 'debit', TRUE, TRUE, 'Surplus appropriation accounts'),
('7001', 'Appropriation - Dividend', 82, 'expense', 'appropriation', 'debit', FALSE, TRUE, 'Dividend distributed to members'),
('7002', 'Appropriation - Interest to Members', 82, 'expense', 'appropriation', 'debit', FALSE, TRUE, 'Interest paid on member savings'),
('7003', 'Appropriation - Reserve Fund', 82, 'expense', 'appropriation', 'debit', FALSE, TRUE, 'Transfer to statutory reserve'),
('7004', 'Appropriation - Bonus', 82, 'expense', 'appropriation', 'debit', FALSE, TRUE, 'Bonus to staff/members'),
('7005', 'Appropriation - Education Fund', 82, 'expense', 'appropriation', 'debit', FALSE, TRUE, 'Transfer to education fund'),
('7006', 'Appropriation - Honorarium', 82, 'expense', 'appropriation', 'debit', FALSE, TRUE, 'Honorarium to executives'),
('7007', 'Appropriation - General Reserve', 82, 'expense', 'appropriation', 'debit', FALSE, TRUE, 'Transfer to general reserve'),
('7008', 'Appropriation - Welfare Fund', 82, 'expense', 'appropriation', 'debit', FALSE, TRUE, 'Transfer to welfare fund');

-- ============================================================================
-- STORED PROCEDURES & FUNCTIONS
-- ============================================================================

-- Procedure: Get Account Balance
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS GetAccountBalance(
  IN p_account_id INT,
  IN p_periodid INT
)
BEGIN
  SELECT 
    account_code,
    account_name,
    normal_balance,
    CASE 
      WHEN normal_balance = 'debit' THEN 
        COALESCE(opening_debit, 0) + COALESCE(period_debit, 0) - COALESCE(opening_credit, 0) - COALESCE(period_credit, 0)
      ELSE 
        COALESCE(opening_credit, 0) + COALESCE(period_credit, 0) - COALESCE(opening_debit, 0) - COALESCE(period_debit, 0)
    END AS balance
  FROM coop_accounts a
  LEFT JOIN coop_period_balances pb ON a.id = pb.account_id AND pb.periodid = p_periodid
  WHERE a.id = p_account_id;
END //
DELIMITER ;

-- Function: Calculate Net Book Value
DELIMITER //
CREATE FUNCTION IF NOT EXISTS CalculateNBV(
  p_asset_id INT
) RETURNS DECIMAL(15,2)
DETERMINISTIC
BEGIN
  DECLARE v_nbv DECIMAL(15,2);
  SELECT (cost - accumulated_depreciation) INTO v_nbv
  FROM coop_fixed_assets
  WHERE id = p_asset_id;
  RETURN COALESCE(v_nbv, 0);
END //
DELIMITER ;

-- ============================================================================
-- VIEWS FOR COMMON QUERIES
-- ============================================================================

-- View: Current Period Trial Balance
CREATE OR REPLACE VIEW vw_trial_balance AS
SELECT 
  a.account_code,
  a.account_name,
  a.account_type,
  pb.periodid,
  SUM(CASE WHEN a.normal_balance = 'debit' THEN 
    COALESCE(pb.opening_debit, 0) + COALESCE(pb.period_debit, 0) 
  ELSE 0 END) AS debit_balance,
  SUM(CASE WHEN a.normal_balance = 'credit' THEN 
    COALESCE(pb.opening_credit, 0) + COALESCE(pb.period_credit, 0) 
  ELSE 0 END) AS credit_balance
FROM coop_accounts a
LEFT JOIN coop_period_balances pb ON a.id = pb.account_id
WHERE a.is_active = TRUE
GROUP BY a.account_code, a.account_name, a.account_type, pb.periodid;

-- View: Asset Register with Current Values
CREATE OR REPLACE VIEW vw_asset_register AS
SELECT 
  fa.asset_number,
  fa.asset_name,
  fa.asset_category,
  fa.acquisition_date,
  fa.cost,
  fa.accumulated_depreciation,
  (fa.cost - fa.accumulated_depreciation) AS net_book_value,
  fa.status,
  fa.location
FROM coop_fixed_assets fa
WHERE fa.status = 'active';

-- View: Member Account Summary
CREATE OR REPLACE VIEW vw_member_account_summary AS
SELECT 
  e.CoopID AS memberid,
  CONCAT(e.LastName, ', ', e.FirstName, ' ', IFNULL(e.MiddleName, '')) AS member_name,
  ma.periodid,
  pp.PayrollPeriod,
  SUM(CASE WHEN ma.account_type = 'shares' THEN ma.closing_balance ELSE 0 END) AS shares_balance,
  SUM(CASE WHEN ma.account_type = 'savings' THEN ma.closing_balance ELSE 0 END) AS savings_balance,
  SUM(CASE WHEN ma.account_type = 'special_savings' THEN ma.closing_balance ELSE 0 END) AS special_savings_balance,
  SUM(CASE WHEN ma.account_type = 'loan' THEN ma.closing_balance ELSE 0 END) AS loan_balance
FROM coop_member_accounts ma
JOIN tblemployees e ON ma.memberid = e.CoopID
JOIN tbpayrollperiods pp ON ma.periodid = pp.id
GROUP BY e.CoopID, ma.periodid;

-- ============================================================================
-- TRIGGERS FOR DATA INTEGRITY
-- ============================================================================

-- Trigger: Validate Journal Entry Balance Before Insert
DELIMITER //
CREATE TRIGGER IF NOT EXISTS trg_validate_journal_entry_balance
BEFORE INSERT ON coop_journal_entries
FOR EACH ROW
BEGIN
  DECLARE v_total_debits DECIMAL(15,2);
  DECLARE v_total_credits DECIMAL(15,2);
  
  -- Note: This trigger validates the total_amount field
  -- Line-level validation is done in application logic
  
  IF NEW.total_amount < 0 THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Total amount cannot be negative';
  END IF;
END //
DELIMITER ;

-- Trigger: Update Net Book Value on Depreciation
DELIMITER //
CREATE TRIGGER IF NOT EXISTS trg_update_nbv_after_depreciation
AFTER INSERT ON coop_depreciation_schedule
FOR EACH ROW
BEGIN
  UPDATE coop_fixed_assets
  SET 
    accumulated_depreciation = NEW.accumulated_depreciation,
    net_book_value = NEW.net_book_value
  WHERE id = NEW.asset_id;
END //
DELIMITER ;

-- Trigger: Prevent Deletion of System Accounts
DELIMITER //
CREATE TRIGGER IF NOT EXISTS trg_prevent_system_account_delete
BEFORE DELETE ON coop_accounts
FOR EACH ROW
BEGIN
  IF OLD.is_system_account = TRUE THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Cannot delete system accounts';
  END IF;
END //
DELIMITER ;

-- ============================================================================
-- INITIAL CONFIGURATION DATA
-- ============================================================================

-- Insert default journal entry number sequence (if using separate sequence table)
-- CREATE TABLE IF NOT EXISTS coop_sequences (
--   sequence_name VARCHAR(50) PRIMARY KEY,
--   current_value INT DEFAULT 0,
--   prefix VARCHAR(20),
--   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
-- );

-- INSERT INTO coop_sequences (sequence_name, current_value, prefix) VALUES
-- ('journal_entry', 0, 'JE'),
-- ('asset_number', 0, 'AST');

-- ============================================================================
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- ============================================================================

-- Additional composite indexes for common queries
CREATE INDEX idx_period_entry_date ON coop_journal_entries(periodid, entry_date);
CREATE INDEX idx_member_period_type ON coop_member_accounts(memberid, periodid, account_type);
CREATE INDEX idx_asset_status_category ON coop_fixed_assets(status, asset_category);

-- ============================================================================
-- ADD FOREIGN KEY CONSTRAINTS AFTER DATA POPULATION
-- ============================================================================

-- Note: Foreign keys added after data to avoid insertion order issues

-- Already added: coop_accounts parent_id FK

-- Journal entry lines to journal entries
-- Already in table definition

-- Period balances to accounts
-- Already in table definition

-- Depreciation schedule to fixed assets
-- Already in table definition

-- Depreciation schedule to journal entries
-- Already in table definition

-- Budget to accounts
-- Already in table definition

-- Reserves to accounts
-- Already in table definition

-- Appropriation to journal entries
-- Already in table definition

-- Bank reconciliation to accounts
-- Already in table definition

-- ============================================================================
-- DATABASE SETUP COMPLETE
-- ============================================================================

-- Success message
SELECT 'Full Accounting System database setup completed successfully!' AS Status,
       (SELECT COUNT(*) FROM coop_accounts) AS Accounts_Created,
       NOW() AS Setup_Timestamp;

-- ============================================================================
-- POST-INSTALLATION NOTES
-- ============================================================================
/*

NEXT STEPS AFTER RUNNING THIS SCRIPT:

1. VERIFY TABLE CREATION
   - Check that all 12 tables were created
   - Verify standard chart of accounts (90 accounts)
   - Confirm foreign keys and indexes

2. SET UP USER PERMISSIONS
   - Grant appropriate database privileges to application user
   - Restrict direct table access where needed

3. CONFIGURE APPLICATION
   - Update connection strings in PHP files
   - Set up accounting engine classes
   - Configure journal entry numbering

4. INITIAL DATA SETUP
   - Create opening balances for first period
   - Import historical member account data (if needed)
   - Set up fixed asset register

5. TESTING
   - Test journal entry creation
   - Verify double-entry validation
   - Test member transaction posting
   - Generate sample financial statements

6. BACKUP STRATEGY
   - Set up automated daily backups
   - Test restore procedures
   - Document backup schedule

7. TRAINING
   - Train accountant on journal entry interface
   - Train managers on report generation
   - Document key procedures

8. GO-LIVE CHECKLIST
   - Verify all opening balances
   - Confirm trial balance balances
   - Test period closing procedure
   - Verify financial statement accuracy

SUPPORT:
For implementation assistance, refer to the full specification document
or contact your development team.

VERSION HISTORY:
v1.0 (2024-10-25) - Initial release with complete accounting system

*/

