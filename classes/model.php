<?php
//session_start(); // Uncomment if session management is needed
/*if (!defined('DIRECTACC')) {
    header('Status: 200');
    header('Location: ../../index.php');
    exit;
}*/

include_once('class.db.php');
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function retrieveDescSingleFilter($table, $basevar, $filter1, $val1)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ?');
        $query->execute([$val1]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            echo $row[$basevar];
            return $row[$basevar];
        }
        return null;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function retrieveDescSingleFilter_deduction($table, $basevar, $filter1, $val1)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ?');
        $query->execute([$val1]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return $row[$basevar];
        }
        return null;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function retrievePeriod($table, $basevar, $filter1, $val1)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ?');
        $query->execute([$val1]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return $row[$basevar];
        }
        return null;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function &returnDescSingleFilter($table, $basevar, $filter1, $val1)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ?');
        $query->execute([$val1]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result = $row[$basevar];
            return $result;
        }
        $result = null;
        return $result;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        $result = null;
        return $result;
    }
}

function retrieveDescSingleFilterlessthan($table, $basevar, $val1, $filter1)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT SUM(' . $basevar . ') as ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ?');
        $query->execute([$val1]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return $row[$basevar] ?? 0;
        }
        return 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function returnNextNumber()
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT MAX(RIGHT(tblemployees.CoopID, 5)) + 1 as nextNumber FROM tblemployees');
        $query->execute();
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return 'COOP-' . str_pad($row['nextNumber'], 5, '0', STR_PAD_LEFT);
        }
        return 'COOP-00001';
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function retrieveCompanyDepartment($table, $basevar, $val1, $filter1)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ?');
        $query->execute([$val1]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            echo $row[$basevar];
            return $row[$basevar];
        }
        return null;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function retrieveSettings($table, $basevar, $val1, $filter1)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ?');
        $query->execute([$val1]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            echo $row[$basevar];
            return $row[$basevar];
        }
        return null;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function retrieveDescDualFilter($table, $basevar, $val1, $filter1, $filter2, $val2)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ? AND ' . $filter2 . ' = ?');
        $query->execute([$val1, $val2]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return $row[$basevar];
        }
        return null;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function insertInto($table, $basevar1, $basevar2, $val1, $val2)
{
    global $conn;
    try {
        $query = $conn->prepare('INSERT INTO ' . $table . ' (' . $basevar1 . ', ' . $basevar2 . ') VALUES (?, ?)');
        $res = $query->execute([$val1, $val2]);
        return $res ? 1 : 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function getSum($table, $basevar1, $index, $indexVal)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT SUM(' . $basevar1 . ') as ' . $basevar1 . ' FROM ' . $table . ' WHERE ' . $index . ' = ? GROUP BY ' . $index);
        $query->execute([$indexVal]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return $row[$basevar1] ?? 0;
        }
        return 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function Insert3Column($table, $column1, $column2, $column3, $columVal1, $columVal2, $columVal3)
{
    global $conn;
    try {
        $query = $conn->prepare('INSERT INTO ' . $table . ' (' . $column1 . ', ' . $column2 . ', ' . $column3 . ') VALUES (?, ?, ?)');
        $res = $query->execute([$columVal1, $columVal2, $columVal3]);
        return $res ? 1 : 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function Insert2Column($table, $column1, $column2, $columVal1, $columVal2)
{
    global $conn;
    try {
        $query = $conn->prepare('INSERT INTO ' . $table . ' (' . $column1 . ', ' . $column2 . ') VALUES (?, ?)');
        $res = $query->execute([$columVal1, $columVal2]);
        return $res ? 1 : 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function deleteItem($table, $filter1, $val1)
{
    global $conn;
    try {
        $query = $conn->prepare('DELETE FROM ' . $table . ' WHERE ' . $filter1 . ' = ?');
        $res = $query->execute([$val1]);
        return $res ? 1 : 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function delete2Item($table, $filter1, $filter2, $val1, $val2)
{
    global $conn;
    try {
        $query = $conn->prepare('DELETE FROM ' . $table . ' WHERE ' . $filter1 . ' = ? AND ' . $filter2 . ' = ?');
        $res = $query->execute([$val1, $val2]);
        return $res ? 1 : 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function retrieveDescDualFilterlessthan($table, $basevar, $val1, $filter1, $filter2, $val2)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT SUM(' . $basevar . ') as ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ? AND ' . $filter2 . ' <= ?');
        $query->execute([$val1, $val2]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return $row[$basevar] ?? 0;
        }
        return 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function retrieveDescDualFilterequalTo($table, $basevar, $val1, $filter1, $filter2, $val2)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT SUM(' . $basevar . ') as ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ? AND ' . $filter2 . ' = ?');
        $query->execute([$val1, $val2]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return $row[$basevar] ?? 0;
        }
        return 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function retrieveDescDualFilterequalToLessThan($table, $basevar, $val1, $filter1, $filter2, $val2)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT SUM(' . $basevar . ') as ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ? AND ' . $filter2 . ' <= ?');
        $query->execute([$val1, $val2]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return $row[$basevar] ?? 0;
        }
        return 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function retrieveSumTwoTables($table1, $table2, $basevar, $val1, $filter1, $join1, $join2)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT SUM(' . $basevar . ') as ' . $basevar . ' FROM ' . $table1 . ' INNER JOIN ' . $table2 . ' ON ' . $join1 . ' = ' . $join2 . ' WHERE ' . $filter1 . ' = ?');
        $query->execute([$val1]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return $row[$basevar] ?? 0;
        }
        return 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function retrieveDescQuadFilter($table, $basevar, $val1, $filter1, $filter2, $val2, $filter3, $val3, $filter4, $val4)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ? AND ' . $filter2 . ' = ? AND ' . $filter3 . ' = ? AND ' . $filter4 . ' = ?');
        $query->execute([$val1, $val2, $val3, $val4]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $value = $row[$basevar] ?? 0;
            echo number_format($value);
            return $value;
        }
        echo '0';
        return null;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function retrieveDescPentaFilter($table, $basevar, $val1, $filter1, $filter2, $val2, $filter3, $val3, $filter4, $val4, $filter5

, $val5)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ? AND ' . $filter2 . ' = ? AND ' . $filter3 . ' = ? AND ' . $filter4 . ' = ? AND ' . $filter5 . ' = ?');
        $query->execute([$val1, $val2, $val3, $val4, $val5]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $value = $row[$basevar] ?? 0;
            echo number_format($value);
            return $value;
        }
        echo '0';
        return null;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function retrieveSingFilterString($table, $basevar, $filter1, $val1)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ?');
        $query->execute([$val1]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return $row[$basevar];
        }
        return null;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function retrieveMonthlyContribution($val1, $val2)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT IFNULL(SUM(tbl_mastertransact.savingsAmount), 0) +
                                        IFNULL(SUM(tbl_mastertransact.sharesAmount), 0) +
                                        IFNULL(SUM(tbl_mastertransact.InterestPaid), 0) +
                                        IFNULL(SUM(tbl_mastertransact.DevLevy), 0) +
                                        IFNULL(SUM(tbl_mastertransact.Stationery), 0) +
                                        IFNULL(SUM(tbl_mastertransact.EntryFee), 0) +
                                        IFNULL(SUM(tbl_mastertransact.CommodityRepayment), 0) +
                                        IFNULL(SUM(tbl_mastertransact.loanRepayment), 0) as monthlyContri
                                 FROM tbl_mastertransact
                                 WHERE COOPID = ? AND TransactionPeriod = ?');
        $query->execute([$val1, $val2]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $value = $row['monthlyContri'] ?? 0;
            echo number_format($value, 2);
            return $value;
        }
        echo '0';
        return null;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function retrievePeriodText($val1)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT PayrollPeriod FROM tbpayrollperiods WHERE id = ?');
        $query->execute([$val1]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            echo $row['PayrollPeriod'];
            return $row['PayrollPeriod'];
        }
        echo '0';
        return null;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function &returnDescPentaFilter($table, $basevar, $val1, $filter1, $filter2, $val2, $filter3, $val3, $filter4, $val4, $filter5, $val5)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ? AND ' . $filter2 . ' = ? AND ' . $filter3 . ' = ? AND ' . $filter4 . ' = ? AND ' . $filter5 . ' = ?');
        $query->execute([$val1, $val2, $val3, $val4, $val5]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result = $row[$basevar];
            return $result;
        }
        $result = null;
        return $result;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        $result = null;
        return $result;
    }
}

function styleLabelColor($labelType)
{
    try {
        switch ($labelType) {
            case 'Earning':
                return "success";
            case 'Deduction':
                return "danger";
            case 'Union Deduction':
                return "warning";
            case 'Loan':
                return "info";
            default:
                return null;
        }
    } catch (Exception $e) {
        error_log("Error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Error occurred";
        return null;
    }
}

function retrieveSelect($table, $filter1, $filter2, $basevar, $sortvar)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $filter1 . ' FROM ' . $table . ' WHERE ' . $filter2 . ' = ? AND status = ? ORDER BY ' . $sortvar);
        $query->execute([$basevar, 'Active']);
        $out = $query->fetchAll(PDO::FETCH_ASSOC);
        foreach ($out as $row) {
            echo '<option value="' . htmlspecialchars($row['ed_id']) . '">' . htmlspecialchars($row['edDesc']) . ' - ' . htmlspecialchars($row['ed_id']) . '</option>';
        }
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function getContributions($coopid)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT SUM(IFNULL(tbl_monthlycontribution.MonthlyContribution, 0)) + SUM(IFNULL(tbl_loansavings.Amount, 0)) as contribution
                                 FROM tbl_monthlycontribution 
                                 LEFT JOIN tbl_loansavings ON tbl_monthlycontribution.coopID = tbl_loansavings.COOPID
                                 WHERE tbl_monthlycontribution.coopID = ?');
        $query->execute([$coopid]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return number_format($row['contribution'] ?? 0);
        }
        return number_format(0);
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return number_format(0);
    }
}

function retrieveSelectAllWithFilter1($table, $filter1, $basevar, $sortvar, $index)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $filter1 . ' FROM ' . $table . ' WHERE status = ? ORDER BY ' . $sortvar);
        $query->execute(['Active']);
        $out = $query->fetchAll(PDO::FETCH_ASSOC);
        foreach ($out as $row) {
            $contribution = getContributions($row['CoopID']);
            echo '<option value="' . htmlspecialchars($row[$index]) . '">' . htmlspecialchars($row['LastName']) . ' ' . htmlspecialchars($row['FirstName']) . ' - ' . htmlspecialchars($row['CoopID']) . '</option>';
        }
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function retrieveSelectAll($table, $filter1, $filter2, $basevar, $sortvar)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $filter1 . ' FROM ' . $table . ' WHERE ' . $filter2 . ' <> ? AND status = ? ORDER BY ' . $sortvar);
        $query->execute([$basevar, 'Active']);
        $out = $query->fetchAll(PDO::FETCH_ASSOC);
        foreach ($out as $row) {
            echo '<option value="' . htmlspecialchars($row['ed_id']) . '">' . htmlspecialchars($row['edDesc']) . ' - ' . htmlspecialchars($row['ed_id']) . '</option>';
        }
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function retrieveSelectwithoutFilter($table, $filter1, $filter2, $basevar, $sortvar)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $filter1 . ' FROM ' . $table . ' WHERE ' . $filter2 . ' = ? AND status = ? ORDER BY ' . $sortvar);
        $query->execute([$basevar, 'Active']);
        $out = $query->fetchAll(PDO::FETCH_ASSOC);
        foreach ($out as $row) {
            echo '<option value="' . htmlspecialchars($row['ed_id']) . '">' . htmlspecialchars($row['edDesc']) . ' - ' . htmlspecialchars($row['ed_id']) . '</option>';
        }
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function retrieveSelectwithoutWhere($table, $filter1, $sortvar, $value1, $value2)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $filter1 . ' FROM ' . $table . ' ORDER BY ' . $sortvar);
        $query->execute();
        $out = $query->fetchAll(PDO::FETCH_ASSOC);
        foreach ($out as $row) {
            echo '<option value="' . htmlspecialchars($row[$value1]) . '">' . htmlspecialchars($row[$value2]) . ' - ' . htmlspecialchars($row[$value1]) . '</option>';
        }
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function retrievePayrollSubTotal($basevar, $table, $filter1, $filter2, $filter3, $filter4, $var1, $var2)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ? AND ' . $filter2 . ' = ? AND ' . $filter3 . ' = ? AND ' . $filter4 . ' = ?');
        $query->execute([$var1, $var2, $_SESSION['currentactiveperiod'], '1']);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $value = $row[$basevar] ?? 0;
            echo number_format($value);
            return $value;
        }
        echo '0';
        return null;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function retrieveEmployees($table, $filter1, $filter2, $basevar, $sortvar)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $filter1 . ' FROM ' . $table . ' WHERE ' . $filter2 . ' = ? ORDER BY Name');
        $query->execute([$basevar]);
        $out = $query->fetchAll(PDO::FETCH_ASSOC);
        foreach ($out as $row) {
            echo '<option value="' . htmlspecialchars($row['staff_id']) . '">' . htmlspecialchars($row['NAME']) . ' - ' . htmlspecialchars($row['staff_id']) . '</option>';
        }
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function retrieveLeaveStatus($table, $filter1, $filter2, $basevar)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $filter1 . ' FROM ' . $table . ' WHERE ' . $filter2 . ' = ?');
        $query->execute([$basevar]);
        $out = $query->fetchAll(PDO::FETCH_ASSOC);
        foreach ($out as $row) {
            echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['statusDescription']) . '</option>';
        }
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function retrieveLeaveTypes($table, $filter1, $filter2, $basevar)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT ' . $filter1 . ' FROM ' . $table . ' WHERE ' . $filter2 . ' = ?');
        $query->execute([$basevar]);
        $out = $query->fetchAll(PDO::FETCH_ASSOC);
        foreach ($out as $row) {
            echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['Leave_type']) . ' Leave </option>';
        }
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function returnNumberOfEmployees()
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT empNumber FROM employees WHERE companyId = ? AND active = ? ORDER BY id ASC');
        $query->execute([$_SESSION['companyid'], '1']);
        $count = $query->rowCount();
        echo $count;
        return $count;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return null;
    }
}

function retrievePayroll($val1, $val2, $val3, $val4)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT tbl_master.period,
                                        CASE type
                                            WHEN 1 THEN SUM(tbl_master.allow)
                                            WHEN 2 THEN SUM(tbl_master.deduc)
                                        END as amount
                                 FROM tbl_master
                                 INNER JOIN employee ON employee.staff_id = tbl_master.staff_id
                                 RIGHT JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id
                                 INNER JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD
                                 INNER JOIN payperiods ON payperiods.periodId = tbl_master.period
                                 WHERE tbl_master.period BETWEEN ? AND ? AND employee.staff_id = ? AND allow_id = ?
                                 GROUP BY employee.staff_id');
        $query->execute([$val1, $val2, $val3, $val4]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return $row['amount'] ?? 0;
        }
        return 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function retrievegross($val1, $val2)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT tbl_master.period,
                                        CASE type
                                            WHEN 1 THEN SUM(tbl_master.allow)
                                            WHEN 2 THEN SUM(tbl_master.deduc)
                                        END as amount
                                 FROM tbl_master
                                 INNER JOIN employee ON employee.staff_id = tbl_master.staff_id
                                 RIGHT JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id
                                 INNER JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD
                                 INNER JOIN payperiods ON payperiods.periodId = tbl_master.period
                                 WHERE tbl_master.period = ? AND employee.staff_id = ?
                                 GROUP BY employee.staff_id');
        $query->execute([$val1, $val2]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return $row['amount'] ?? 0;
        }
        return 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function exportTax_new($val1, $val2)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT allow_deduc.staff_id,
                                        allow_deduc.allow_id,
                                        allow_deduc.`value` as amount,
                                        allow_deduc.transcode,
                                        tbl_earning_deduction.edDesc
                                 FROM allow_deduc
                                 INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = allow_deduc.allow_id
                                 WHERE staff_id = ? AND allow_id = ?');
        $query->execute([$val1, $val2]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return $row['amount'] ?? 0;
        }
        return 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function calTaxableIncome($val1, $val2)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT tbl_master.period,
                                        CASE type
                                            WHEN 1 THEN SUM(tbl_master.allow)
                                            WHEN 2 THEN SUM(tbl_master.deduc)
                                        END as amount,
                                        employee.GRADE
                                 FROM tbl_master
                                 INNER JOIN employee ON employee.staff_id = tbl_master.staff_id
                                 RIGHT JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id
                                 INNER JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD
                                 INNER JOIN payperiods ON payperiods.periodId = tbl_master.period
                                 WHERE tbl_master.period = ? AND employee.staff_id = ?
                                 GROUP BY employee.staff_id');
        $query->execute([$val1, $val2]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return $row['amount'] ?? 0;
        }
        return 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function retrievePayrollRunStatus($val1, $val2)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT staff_id, period FROM master_staff WHERE staff_id = ? AND period = ?');
        $query->execute([$val1, $val2]);
        if ($query->fetch(PDO::FETCH_ASSOC)) {
            return 1;
        }
        return 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function lastPayCheck($val1, $val2)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT lastpay_id, staff_id, period FROM tbl_lastpay WHERE staff_id = ? AND period = ?');
        $query->execute([$val1, $val2]);
        if ($query->fetch(PDO::FETCH_ASSOC)) {
            return '1';
        }
        return '0';
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return '0';
    }
}

function cash_chequeCheck($val2)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT * FROM tbl_cash_cheque WHERE staff_id = ?');
        $query->execute([$val2]);
        if ($query->fetch(PDO::FETCH_ASSOC)) {
            return '1';
        }
        return '0';
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return '0';
    }
}

function devlevyCheck($val1, $val2)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT dev_id, staff_id, period_year FROM tbl_devlevy WHERE staff_id = ? AND period_year = ?');
        $query->execute([$val1, $val2]);
        if ($query->fetch(PDO::FETCH_ASSOC)) {
            return '1';
        }
        return '0';
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return '0';
    }
}

function retrieveLoanStatus($val1, $val2)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT SUM(tbl_debt.principal) + SUM(tbl_debt.interest) as loan FROM tbl_debt WHERE staff_id = ? AND allow_id = ? GROUP BY staff_id, allow_id');
        $query->execute([$val1, $val2]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return $row['loan'] ?? 0;
        }
        return 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function retrieveLoanBalanceStatus($val1, $val2, $val3)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT SUM(tbl_repayment.`value`) as repayment FROM tbl_repayment WHERE staff_id = ? AND allow_id = ? AND period <= ? GROUP BY staff_id, allow_id');
        $query->execute([$val1, $val2, $val3]);
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            return $row['repayment'] ?? 0;
        }
        return 0;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}

function auditTrailInsert($staff_id, $allow_id, $value, $period)
{
    global $conn;
    try {
        $query = $conn->prepare('SELECT * FROM tbl_audit WHERE staff_id = ? AND allow_id = ? AND period = ?');
        $query->execute([$staff_id, $allow_id, $period]);
        if ($query->fetch(PDO::FETCH_ASSOC)) {
            $query = $conn->prepare('UPDATE tbl_audit SET value = ? WHERE staff_id = ? AND allow_id = ? AND period = ?');
            $query->execute([$value, $staff_id, $allow_id, $period]);
        } else {
            $query = $conn->prepare('INSERT INTO tbl_audit (staff_id, allow_id, value, period) VALUES (?, ?, ?, ?)');
            $query->execute([$staff_id, $allow_id, $value, $period]);
        }
        return 1;
    } catch (PDOException $e) {
        error_log("Database error in " . __FUNCTION__ . ": " . $e->getMessage());
        echo "Database error occurred";
        return 0;
    }
}
?>