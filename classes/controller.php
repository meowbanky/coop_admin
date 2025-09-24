<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

session_start();
ini_set('max_execution_time', '0');
$connect = mysqli_connect("localhost", "emmaggic_root", "Oluwaseyi", "emmaggic_coop");
include_once('functions.php');
include_once('model.php');
include_once('sendsms.php');

$act = filter_var($_GET['act']);
$source = $_SERVER['HTTP_REFERER'];

require "../../oouthcoop_update/mail/mail/vendor/autoload.php";

switch ($act) {
    case 'update_deductions':
        if (!isset($_POST['deduction'])) {
            $_POST['deduction'] = 0;
        }
        $deduction = (float)$_POST['deduction'];
    
        if (!isset($_POST['saving'])) {
            $_POST['saving'] = 0;
        }
        $savings = (float)$_POST['saving'];
    
        $coop_id = trim($_POST['coop_id']);
        $period_id = isset($_POST['period_id']) ? (int)$_POST['period_id'] : null;
    
        if (!$period_id) {
            echo 'Error: Period is required.';
            exit;
        }
    
        if (!$coop_id) {
            echo 'Error: COOP ID is required.';
            exit;
        }
    
        try {
            // Handle tbl_monthlycontribution
            $query = $conn->prepare('SELECT * FROM tbl_monthlycontribution WHERE coopID = ? AND period = ?');
            $query->execute([$coop_id, $period_id]);
            $existtrans = $query->fetch();
    
            if ($existtrans) {
                $query = 'UPDATE tbl_monthlycontribution SET MonthlyContribution = ? WHERE coopID = ? AND period = ?';
                $conn->prepare($query)->execute([$deduction, $coop_id, $period_id]);
            } else {
                $query = 'INSERT INTO tbl_monthlycontribution (MonthlyContribution, coopID, period) VALUES (?, ?, ?)';
                $conn->prepare($query)->execute([$deduction, $coop_id, $period_id]);
            }
    
            // Handle tbl_loansavings
            $query = $conn->prepare('SELECT * FROM tbl_loansavings WHERE COOPID = ? AND period = ?');
            $query->execute([$coop_id,$period_id]);
            $existtrans = $query->fetch();
    
            if ($existtrans) {
                $query = 'UPDATE tbl_loansavings SET Amount = ? WHERE COOPID = ? AND period = ? ';
                $conn->prepare($query)->execute([$savings, $coop_id, $period_id]);
            } else { 
                $query = 'INSERT INTO tbl_loansavings (COOPID, Amount,period) VALUES (?, ?,?)';
                $conn->prepare($query)->execute([$coop_id, $savings,$period_id]);
            }

            // Handle tbl_extra
            $query = $conn->prepare('SELECT * FROM tbl_extra WHERE COOPID = ?');
            $query->execute([$coop_id]);
            $existtrans = $query->fetch();
            if ($existtrans) {
                $query = 'UPDATE tbl_extra SET Amount = ? WHERE COOPID = ?';
                $conn->prepare($query)->execute([$savings, $coop_id]);
            } else { 
                $query = 'INSERT INTO tbl_extra (COOPID, Amount) VALUES (?, ?)';
                $conn->prepare($query)->execute([$coop_id, $savings]);
            }
    
            echo '1';
        } catch (PDOException $e) {
            echo 'Error: ' . $e->getMessage();
        }
        break;

    case 'addperiod':
        $periodname = htmlspecialchars(trim($_POST['perioddesc']), ENT_QUOTES, 'UTF-8');
        $periodyear = htmlspecialchars(trim($_POST['periodyear']), ENT_QUOTES, 'UTF-8');
        $periodDescription = $periodname . " - " . $periodyear;
        
        try {
            //check for replication and create period
            $query = $conn->prepare('SELECT * FROM tbpayrollperiods WHERE PhysicalYear = ? AND PhysicalMonth = ? ');
            $fin = $query->execute(array($periodyear, $periodname));

            if ($row = $query->fetch()) {
                $_SESSION['msg'] = "Selected period values already exist.";
                $_SESSION['alertcolor'] = "danger";
                header('Location: ' . $source);
            } else {
                $query = 'INSERT INTO tbpayrollperiods (PayrollPeriod, PhysicalYear, PhysicalMonth, InsertedBy,DateInserted) VALUES (?,?,?,?,now())';
                $conn->prepare($query)->execute(array($periodDescription, $periodyear, $periodname, $_SESSION['SESS_FIRST_NAME']));

                $_SESSION['msg'] = "New Period Successfully Created";
                $_SESSION['alertcolor'] = "success";
                header('Location: ' . $source);
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        break;

    case 'activateclosedperiod':
        try {
            $reactivateperiodid = htmlspecialchars(trim($_POST['reactivateperiodid']), ENT_QUOTES, 'UTF-8');

            //Change period session variables
            $_SESSION['currentactiveperiod'] = $reactivateperiodid;

            $periodquery = $conn->prepare('SELECT description, periodYear FROM payperiods WHERE periodId = ?');
            $perres = $periodquery->execute(array($_SESSION['currentactiveperiod']));
            if ($rowp = $periodquery->fetch()) {
                $reactivatedperioddesc = $rowp['description'];
                $reactivatedperiodyear = $rowp['periodYear'];
            }

            $_SESSION['activeperiodDescription'] = $reactivatedperioddesc . ' ' . $reactivatedperiodyear;

            //Ensure all openview status are reset before activating particular one
            $statuschange = $conn->prepare('UPDATE payperiods SET openview = ? ');
            $perres = $statuschange->execute(array('0'));

            //set openview status
            $statuschange = $conn->prepare('UPDATE payperiods SET openview = ? WHERE periodId = ?');
            $perres = $statuschange->execute(array('1', $_SESSION['currentactiveperiod']));
            $_SESSION['periodstatuschange'] = '1';

            $_SESSION['msg'] = "You are now viewing data from a closed period. Transactions are not allowed.";
            $_SESSION['alertcolor'] = "success";
            header('Location: ' . $source);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        break;

    case 'closeActivePeriod':
        try {
            //reset period id
            //reset assigned active period id
            exit('closeActivePeriod');
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        break;

    default:
        exit('Unexpected route. Please contact administrator.');
        break;
}