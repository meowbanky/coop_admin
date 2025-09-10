<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

session_start();
ini_set('max_execution_time', '0');
$connect = mysqli_connect("localhost", "emmaggic_root", "Oluwaseyi", "emmaggic_coop");
include_once('functions.php');
include_once('model.php');
include_once('sendsms.php');

//include_once('passwordHash.php');
//$act = strip_tags(addslashes($_GET['act'));
$act = filter_var($_GET['act']);
$source = $_SERVER['HTTP_REFERER'];
//$hostname = gethostbyname($_SERVER['REMOTE_ADDR');
//session variables
$comp = '1';

require "../../oouthcoop_update/mail/mail/vendor/autoload.php";


function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*:<>?';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $password;
}

switch ($act) {
    case 'login':
        $uname = filter_var((filter_var($_POST['uname'], FILTER_SANITIZE_EMAIL)), FILTER_VALIDATE_EMAIL);
        $pass = filter_var($_POST['upassword']);
        //$upass = password_hash(htmlspecialchars(trim($_POST['upassword']), ENT_QUOTES, 'UTF-8'), PASSWORD_BCRYPT);

        try {
            $query = $conn->prepare('SELECT * FROM users WHERE emailAddress = ? AND active = ?');
            $fin = $query->execute(array($uname, '1'));

            //unset($_SESSION('email'));
            //unset($_SESSION('first_name'));
            //unset($_SESSION('last_name'));

            if (isset($_SESSION['periodstatuschange'])) {
                unset($_SESSION['periodstatuschange']);
            }

            if (($row = $query->fetch()) || (password_verify($pass, $row['password']))) {

                $_SESSION['logged_in'] = '1';
                $_SESSION['user'] = $row['userId'];
                $_SESSION['email'] = $row['emailAddress'];
                $_SESSION['first_name'] = $row['firstName'];
                $_SESSION['last_name'] = $row['lastName'];
                $_SESSION['companyid'] = $row['companyId'];
                $_SESSION['emptrack'] = 0;
                $_SESSION['empDataTrack'] = 'next';

                //Get current active period for the organization
                $payp = $conn->prepare('SELECT periodId, description, periodYear FROM payperiods WHERE companyId = ? AND active = ? ORDER BY periodId ASC LIMIT 1');
                $myperiod = $payp->execute(array($_SESSION['companyid'], 1));
                $final = $payp->fetch();
                $_SESSION['currentactiveperiod'] = $final['periodId'];
                $_SESSION['activeperiodDescription'] = $final['description'] . " " . $final['periodYear'];
                //exit($_SESSION['currentactiveperiod');

                //If temp period change, reset session
                if (isset($_SESSION['periodstatuschange'])) {
                    unset($_SESSION['periodstatuschange']);
                }

                $page = "../../dashboard.php";
                $_SESSION['msg'] = $msg = "Welcome " . $_SESSION['first_name'] . " " . $_SESSION['last_name'];
                $_SESSION['alertcolor'] = $type = "success";
                header('Location: ../../dashboard.php');
                //redirect($msg, $type, $page);
            } else {

                $_SESSION['msg'] = $msg = "Invalid login. Please try again.";
                $_SESSION['alertcolor'] = 'danger';
                header('Location: ' . $source);
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;


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

                // Handle tbl_loansavings
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

    case 'adduser':
        //
        $staff_id = htmlspecialchars(trim($_POST['staff_id']), ENT_QUOTES, 'UTF-8');
        //$ulname = htmlspecialchars(trim($_POST['ulname']), ENT_QUOTES, 'UTF-8');
        $uemail = filter_var((filter_var($_POST['uemail'], FILTER_SANITIZE_EMAIL)), FILTER_VALIDATE_EMAIL);
        $upass1 = htmlspecialchars(trim($_POST['upass']), ENT_QUOTES, 'UTF-8');
        $upass2 = htmlspecialchars(trim($_POST['upass1']), ENT_QUOTES, 'UTF-8');

        if ($upass1 == $upass2) {
            try {

                $query = $conn->prepare('SELECT * FROM username WHERE staff_id = ? ');
                $res = $query->execute(array($staff_id));
                $existtrans = $query->fetch();

                if ($existtrans) {
                    //user exists
                    $_SESSION['msg'] = "A user account associated with the supplied Staff ID exists.";
                    $_SESSION['alertcolor'] = "danger";
                    $source = $_SERVER['HTTP_REFERER'];
                    header('Location: ' . $source);
                } else {
                    $upass = password_hash($upass1, PASSWORD_DEFAULT);

                    $query = 'INSERT INTO username (staff_id, username, password, position, role, deleted) VALUES (?,?,?,?,?,?)';
                    $conn->prepare($query)->execute(array($staff_id, $staff_id, $upass, 'Admin', 'Admin', '0'));

                    $_SESSION['msg'] = $msg = 'User Successfully Created';
                    $_SESSION['alertcolor'] = $type = 'success';
                    $source = $_SERVER['HTTP_REFERER'];
                    header('Location: ' . $source);
                }
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
        } else {

            $_SESSION['msg'] = $msg = 'Entered passwords are not matching.';
            $_SESSION['alertcolor'] = $type = 'danger';
            header('Location: ' . $source);
        }

        break;

    case 'createcompanyaccount':
        //create new company
        $title = "New Payroll Account";
        $companyname = htmlspecialchars(trim($_POST['fullname']), ENT_QUOTES, 'UTF-8');
        $contactemail = filter_var((filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)), FILTER_VALIDATE_EMAIL);
        $contactphone = filter_var($_POST['phone'], FILTER_VALIDATE_INT);
        $companyaddress = htmlspecialchars(trim($_POST['address']), ENT_QUOTES, 'UTF-8');
        $compcity = htmlspecialchars(trim($_POST['city']), ENT_QUOTES, 'UTF-8');

        $useremail = filter_var((filter_var($_POST['username'], FILTER_SANITIZE_EMAIL)), FILTER_VALIDATE_EMAIL);
        $userfname = htmlspecialchars(trim($_POST['ufname']), ENT_QUOTES, 'UTF-8');
        $userlname = htmlspecialchars(trim($_POST['ulname']), ENT_QUOTES, 'UTF-8');
        $userpass1 = htmlspecialchars(trim($_POST['password']), ENT_QUOTES, 'UTF-8');
        $userpass = password_hash($userpass1, PASSWORD_DEFAULT);

        try {
            $query = $conn->prepare('SELECT * FROM users WHERE emailAddress = ? AND active = ? ');
            $res = $query->execute(array($useremail, '1'));
            $existtrans = $query->fetch();

            if ($existtrans) {
                //same transaction for current employee, current period posted
                $_SESSION['msg'] = "A user account associated with the supplied email exists.";
                $_SESSION['alertcolor'] = "danger";
                $source = $_SERVER['HTTP_REFERER'];
                header('Location: ' . $source);
            } else {

                $query = 'INSERT INTO company (companyName, city, companyAddress, companyEmail, contactTelephone) VALUES (?,?,?,?,?)';
                $conn->prepare($query)->execute(array($companyname, $compcity, $companyaddress, $contactemail, $contactphone));
                $last_id = $conn->lastInsertId();

                $query = 'INSERT INTO users (emailAddress, password, userTypeId, firstName, lastName, companyId, active) VALUES (?,?,?,?,?,?,?)';
                $conn->prepare($query)->execute(array($useremail, $userpass, '1', $userfname, $userlname, $last_id, '0'));
                $latestuserinsert = $conn->lastInsertId();

                //user account becomes active after validating emailed link
                //Send email validation
                //Generate update token
                $reset_token = bin2hex(openssl_random_pseudo_bytes(32));

                //write token to token table and assign validity state, creation timestamp
                $tokenrecordtime = date('Y-m-d H:i:s');


                //check for any previous tokens and invalidate
                $tokquery = $conn->prepare('SELECT * FROM reset_token WHERE userEmail = ? AND valid = ? AND type = ?');
                $fin = $tokquery->execute(array($useremail, '1', '2'));

                if ($row = $tokquery->fetch()) {
                    $upquery = 'UPDATE reset_token SET valid = ? WHERE userEmail = ? AND valid = ?';
                    $conn->prepare($upquery)->execute('0', $useremail, '1');
                }

                $tokenquery = 'INSERT INTO reset_token (userEmail, token, creationTime, valid, type) VALUES (?,?,?,?,?)';
                $conn->prepare($tokenquery)->execute(array($useremail, $reset_token, $tokenrecordtime, '1', '2'));

                //exit($resetemail . " " . $reset_token);

                $sendmessage = "You've recently created a new Red Payroll account linked to the email address: " . $useremail . "<br /><br />To activate your account, click the link below:<br /><br /> " . $sysurl . 'validate.php?act=auth&jam=' . $latestuserinsert . '&queue=' . $last_id . '&token=' . $reset_token;
                //generate reset cdde and append to email submitted

                require 'phpmailer/PHPMailerAutoload.php';

                $mail = new PHPMailer;

                $mail->SMTPDebug = 3;                               // Enable verbose debug output

                $mail->isSMTP();                                      // Set mailer to use SMTP
                $mail->Host = 'smtp.zoho.com';  // Specify main and backup SMTP servers
                $mail->SMTPAuth = true;                               // Enable SMTP authentication
                $mail->Username = 'noreply@redsphere.co.ke';                 // SMTP username
                $mail->Password = 'redsphere_2017***';                           // SMTP password
                $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
                $mail->Port = 587;                                    // TCP port to connect to

                $mail->setFrom('noreply@redsphere.co.ke', 'Red Payroll');
                $mail->addAddress($useremail, 'Redsphere Payroll');     // Add a recipient
                //$mail->addAddress('ellen@example.com');               // Name is optional
                $mail->addReplyTo('noreply@redsphere.co.ke', 'Red Payroll');
                //$mail->addCC('fgesora@gmail.com');
                $mail->addBCC('fgesora@gmail.com');

                //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
                //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
                $mail->isHTML(true);                                  // Set email format to HTML

                $mail->Subject = $title;
                $mail->Body    = $sendmessage;
                $mail->AltBody = $sendmessage;

                if (!$mail->send()) {
                    //exit($mail->ErrorInfo);
                    echo 'Mailer Error: ' . $mail->ErrorInfo;
                    //  $_SESSION('msg') = "Failed. Error sending email.";
                    $_SESSION['alertcolor'] = "danger";
                    header("Location: " . $source);
                } else {
                    $status = "Success";
                    $_SESSION['msg'] = "An activation link has been sent to the provided email address. Please activate your account in order to log in.";
                    $_SESSION['alertcolor'] = "success";
                    header("Location: " . $source);
                }
            }

            /*
                    ********
                    ********
                    Check if user account exists
                    ********
                    ********
                    */
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        //exit($companyname . ', ' . $contactemail . ', ' . $last_id);
        break;


    case 'addcostcenter':
        $ccname = htmlspecialchars(trim($_POST['cctrname']), ENT_QUOTES, 'UTF-8');

        try {
            $query = 'INSERT INTO company_costcenters (companyId, costCenterName, active) VALUES (?,?,?)';
            $conn->prepare($query)->execute(array($_SESSION['companyid'], $ccname, '1'));
            $_SESSION['msg'] = $msg = 'Cost Center successfully Created';
            $_SESSION['alertcolor'] = $type = 'success';
            $source = $_SERVER['HTTP_REFERER'];
            header('Location: ' . $source);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;

    case 'earningchange':
        $ccname = htmlspecialchars(trim($_POST['cctrname']), ENT_QUOTES, 'UTF-8');

        try {
            $query = 'INSERT INTO company_costcenters (companyId, costCenterName, active) VALUES (?,?,?)';
            $conn->prepare($query)->execute(array($_SESSION['companyid'], $ccname, '1'));
            $_SESSION['msg'] = $msg = 'Cost Center successfully Created';
            $_SESSION['alertcolor'] = $type = 'success';
            $source = $_SERVER['HTTP_REFERER'];
            header('Location: ' . $source);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;


    case 'adddepartment':
        $dept = htmlspecialchars(trim($_POST['deptname']), ENT_QUOTES, 'UTF-8');

        try {

            $query = $conn->prepare('SELECT * FROM tbl_dept WHERE dept = ? ');
            $res = $query->execute(array($dept));
            $existtrans = $query->fetch();

            if ($existtrans) {
                //same transaction for current employee, current period posted
                $_SESSION['msg'] = "Department already existing";
                $_SESSION['alertcolor'] = "danger";
                $source = $_SERVER['HTTP_REFERER'];
                header('Location: ' . $source);
            } else {

                $query = 'INSERT INTO tbl_dept (dept) VALUES (?)';
                $conn->prepare($query)->execute(array($dept));
                $_SESSION['msg'] = $msg = 'Department successfully Created';
                $_SESSION['alertcolor'] = $type = 'success';
                $source = $_SERVER['HTTP_REFERER'];
                header('Location: ' . $source);
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;

    case 'amount':
        $amount = htmlspecialchars(trim($_POST['amount']), ENT_QUOTES, 'UTF-8');
        $temp_id = filter_var($_POST['temp_id'], FILTER_VALIDATE_INT);
        if (isset($amount)) {
            if ($amount != "") {
                mysqli_select_db($database_salary, $salary);
                $updateSQL = sprintf(
                    "update  tbl_workingFile SET `value` = %s where temp_id = %s",
                    filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT),
                    filter_var($_POST['temp_id'], FILTER_VALIDATE_INT)
                );

                try { 	 // code to try
                    $Result1 = mysqli_query($salary,$updateSQL);
                    $source = $_SERVER['HTTP_REFERER'];
                    header('Location: ' . $source);
                } catch (PDOException $e) {	  // error handling }
                    // error handling }
                }
            }
        }
        break;


    case 'addearning':
        $newearning = htmlspecialchars(trim($_POST['eddescription']), ENT_QUOTES, 'UTF-8');
        $recordtime = date('Y-m-d H:i:s');
        //	$recurrent = filter_var($_POST['recurrent'], FILTER_VALIDATE_INT);

        try {
            $getlast = $conn->prepare('SELECT edDesc FROM tbl_earning_deduction WHERE edDesc = ?');
            $res = $getlast->execute(array($newearning));

            if ($row = $getlast->fetch()) {
                $_SESSION['alertcolor'] = $type = "danger";
                $msg = "Duplicate Earning not allowed";
                $source = $_SERVER['HTTP_REFERER'];
                $_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
            } else {


                $query = 'INSERT INTO tbl_earning_deduction (ed,edDesc, edType, status, operator, edCreatedBy,edCreatedDate) VALUES (?,?,?,?,?,?,?)';
                $conn->prepare($query)->execute(array($newearning, $newearning, '1', 'Active', '+', $_SESSION['SESS_MEMBER_ID'], $recordtime));

                $_SESSION['msg'] = $msg = 'New earning Created';
                $_SESSION['alertcolor'] = $type = 'success';
                $source = $_SERVER['HTTP_REFERER'];
                header('Location: ' . $source);
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;


    case 'adddeduction':
        $newearning = htmlspecialchars(trim($_POST['eddescription']), ENT_QUOTES, 'UTF-8');
        //$recurrent = filter_var($_POST['recurrent'], FILTER_VALIDATE_INT);

        try {
            $getlast = $conn->prepare('SELECT edDesc FROM tbl_earning_deduction WHERE edDesc = ?');
            $res = $getlast->execute(array($newearning));

            if ($row = $getlast->fetch()) {
                $_SESSION['alertcolor'] = $type = "danger";
                $msg = "Duplicate Earning not allowed";
                $source = $_SERVER['HTTP_REFERER'];
                $_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
            } else {


                $query = 'INSERT INTO tbl_earning_deduction (ed,edDesc, edType, status, operator, edCreatedBy,edCreatedDate) VALUES (?,?,?,?,?,?,?)';
                $conn->prepare($query)->execute(array($newearning, $newearning, '2', 'Active', '-', $_SESSION['SESS_MEMBER_ID'], $recordtime));

                $_SESSION['msg'] = $msg = 'New earning Created';
                $_SESSION['alertcolor'] = $type = 'success';
                $source = $_SERVER['HTTP_REFERER'];
                header('Location: ' . $source);
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;

    case 'addloanparameter':
        $newearning = htmlspecialchars(trim($_POST['newloandesc']), ENT_QUOTES, 'UTF-8');
        //$recurrent = filter_var($_POST['recurrent'], FILTER_VALIDATE_INT);

        try {
            $getlast = $conn->prepare('SELECT edDesc FROM tbl_earning_deduction WHERE edDesc = ?');
            $res = $getlast->execute(array($newearning));

            if ($row = $getlast->fetch()) {
                $_SESSION['alertcolor'] = $type = "danger";
                $msg = "Duplicate Earning not allowed";
                $source = $_SERVER['HTTP_REFERER'];
                $_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
            } else {


                $query = 'INSERT INTO tbl_earning_deduction (ed,edDesc, edType, status, operator, edCreatedBy,edCreatedDate) VALUES (?,?,?,?,?,?,?)';
                $conn->prepare($query)->execute(array($newearning, $newearning, '4', 'Active', '-', $_SESSION['SESS_MEMBER_ID'], $recordtime));

                $_SESSION['msg'] = $msg = 'New earning Created';
                $_SESSION['alertcolor'] = $type = 'success';
                $source = $_SERVER['HTTP_REFERER'];
                header('Location: ' . $source);
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;

    case 'addunion':
        $newearning = htmlspecialchars(trim($_POST['newunion']), ENT_QUOTES, 'UTF-8');
        //$recurrent = filter_var($_POST['recurrent'], FILTER_VALIDATE_INT);

        try {
            $getlast = $conn->prepare('SELECT edDesc FROM tbl_earning_deduction WHERE edDesc = ?');
            $res = $getlast->execute(array($newearning));

            if ($row = $getlast->fetch()) {
                $_SESSION['alertcolor'] = $type = "danger";
                $msg = "Duplicate Earning not allowed";
                $source = $_SERVER['HTTP_REFERER'];
                $_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
            } else {


                $query = 'INSERT INTO tbl_earning_deduction (ed,edDesc, edType, status, operator, edCreatedBy,edCreatedDate) VALUES (?,?,?,?,?,?,?)';
                $conn->prepare($query)->execute(array($newearning, $newearning, '3', 'Active', '-', $_SESSION['SESS_MEMBER_ID'], $recordtime));

                $_SESSION['msg'] = $msg = 'New earning Created';
                $_SESSION['alertcolor'] = $type = 'success';
                $source = $_SERVER['HTTP_REFERER'];
                header('Location: ' . $source);
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;


    case 'addloan':
        $newloandesc = htmlspecialchars(trim($_POST['newloandesc']), ENT_QUOTES, 'UTF-8');
        //define 900** as the loan ED Code
        try {
            $getlast = $conn->prepare('SELECT edCode FROM earnings_deductions WHERE edType = ? AND companyId = ? AND active = ? ORDER BY id DESC');
            $res = $getlast->execute(array('Loan', $_SESSION['companyid'], '1'));

            if ($row = $getlast->fetch()) {
                $latestcode = intval($row['edCode']);
                $principleinsertcode = $latestcode + 1;
                $repaymentinsertcode = $latestcode + 2;
            }
            $principleinsertdesc = $newloandesc . 'Loan Principle';
            $repaymentinsertdesc = $newloandesc . 'Loan Repayment';
            exit($principleinsertcode . ',' . $repaymentinsertcode);

            $query = 'INSERT INTO earnings_deductions (edCode, edDesc, edType, companyId, active, recurrentEd) VALUES (?,?,?,?,?,?)';
            $conn->prepare($query)->execute(array($principleinsertcode, $principleinsertdesc, 'Loan', $_SESSION['companyid'], '1', '0'));

            $query = 'INSERT INTO earnings_deductions (edCode, edDesc, edType, companyId, active, recurrentEd) VALUES (?,?,?,?,?,?)';
            $conn->prepare($query)->execute(array($repaymentinsertcode, $repaymentinsertdesc, 'Deduction', $_SESSION['companyid'], '1', '1'));
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        exit($newloandesc);
        break;



    case 'loan_corporate':
        $currentempl = $_POST['curremployee'];
        $edcode = $_POST['newdeductioncodeloan'];
        $earningamount = trim($_POST['monthlyRepayment']);
        $principal = trim($_POST['Principal']);
        $interest = trim($_POST['interest']);
        $recordtime = date('Y-m-d H:i:s');

        try {
            $query = $conn->prepare('SELECT * FROM allow_deduc WHERE staff_id = ?  AND allow_id = ? ');
            $res = $query->execute(array($currentempl, $edcode));
            $existtrans = $query->fetch();

            if ($existtrans) {
                //same transaction for current employee, current period posted
                $query2 = 'INSERT INTO tbl_debt (staff_id, allow_id,date_insert, inserted_by,principal,interest) VALUES (?,?,?,?,?,?)';
                $conn->prepare($query2)->execute(array($currentempl, $edcode, $recordtime, $_SESSION['SESS_MEMBER_ID'], $principal, $interest));


                $query = 'update allow_deduc SET value = ? date_insert = ? , inserted_by = ? where staff_id = ? AND allow_id = ?';
                $conn->prepare($query)->execute(array($earningamount, $recordtime, $_SESSION['SESS_MEMBER_ID'], $currentempl, $edcode));

                $_SESSION['alertcolor'] = $type = "danger";
                $msg = "Duplicate Earning not allowed";
                $source = $_SERVER['HTTP_REFERER'];
                $_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
            } else {
                if ($earningamount > 0) {

                    $query2 = 'INSERT INTO tbl_debt (staff_id, allow_id,date_insert, inserted_by,principal,interest) VALUES (?,?,?,?,?,?)';
                    $conn->prepare($query2)->execute(array($currentempl, $edcode, $recordtime, $_SESSION['SESS_MEMBER_ID'], $principal, $interest));


                    $query = 'INSERT INTO allow_deduc (staff_id, allow_id, value, transcode, date_insert, inserted_by) VALUES (?,?,?,?,?,?)';
                    $conn->prepare($query)->execute(array($currentempl, $edcode, $earningamount, '2', $recordtime, $_SESSION['SESS_MEMBER_ID']));


                    $_SESSION['msg'] = $msg = "Earning successfully saved";
                    $_SESSION['alertcolor'] = $type = "success";
                    $source = $_SERVER['HTTP_REFERER'];
                    //$_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                    header('Location: ' . $source);
                } else {



                    $_SESSION['msg'] = $msg = "Employee not Entitiled to the Allowance";
                    $_SESSION['alertcolor'] = $type = "danger";
                    $source = $_SERVER['HTTP_REFERER'];
                    //$_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                    header('Location: ' . $source);
                }
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        break;

    case 'cash_corporate':
        $currentempl = $_POST['curremployee'];
        $edcode = $_POST['newCashcodeloan'];
        $earningamount = trim($_POST['cashAmount']);
        $recordtime = date('Y-m-d H:i:s');

        try {

            if ($earningamount > 0) {

                $query2 = 'INSERT INTO tbl_repayment (tbl_repayment.staff_id,tbl_repayment.allow_id,tbl_repayment.period,tbl_repayment.cashPay,tbl_repayment.userID,tbl_repayment.editTime) VALUES (?,?,?,?,?,?)';
                $conn->prepare($query2)->execute(array($currentempl, $edcode, $_SESSION['currentactiveperiod'], $earningamount, $_SESSION['SESS_MEMBER_ID'], $recordtime));


                $loan = 0;
                $repayment = 0;
                $query_loan = $conn->prepare('SELECT tbl_debt.loan_id, tbl_debt.staff_id,tbl_debt.allow_id, SUM(ifnull(tbl_debt.principal,0))+SUM(ifnull(tbl_debt.interest,0)) as loan FROM tbl_debt WHERE staff_id = ? AND allow_id = ? ');
                $res = $query_loan->execute(array($currentempl, $edcode));
                if ($row = $query_loan->fetch()) {
                    $loan  = $row['loan'];
                }

                $query_repayment = $conn->prepare('SELECT tbl_repayment.staff_id, tbl_repayment.allow_id, (SUM(ifnull(tbl_repayment.value,0)) + SUM(ifnull(tbl_repayment.cashPay,0))) as repayment FROM tbl_repayment WHERE staff_id = ? AND allow_id = ? ');
                $res = $query_repayment->execute(array($currentempl, $edcode));
                if ($row = $query_repayment->fetch()) {
                    $repayment  = $row['repayment'];
                }
                $balance = $loan - $repayment;
                if ($balance == 0) {
                    $query2 = 'delete from allow_deduc WHERE staff_id = ?  AND allow_id = ? ';
                    $conn->prepare($query2)->execute(array($currentempl, $edcode));
                }

                $_SESSION['msg'] = $msg = "Cash Payment successfully saved";
                $_SESSION['alertcolor'] = $type = "success";
                $source = $_SERVER['HTTP_REFERER'];
                //$_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                header('Location: ' . $source);
            } else {

                $_SESSION['msg'] = $msg = "Error Saving Cash Information";
                $_SESSION['alertcolor'] = $type = "danger";
                $source = $_SERVER['HTTP_REFERER'];
                //$_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                header('Location: ' . $source);
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        break;

    case 'addemployeeearning':
        $currentempl = $_POST['curremployee'];
        if (isset($_POST['newearningcodeAll'])) {
            $edcode = $_POST['newearningcodeAll'];
        } elseif (isset($_POST['SelNewContinueLoan'])) {
            $edcode = $_POST['SelNewContinueLoan'];
        }

        if (isset($_POST['no_times'])) {
            $counter = $_POST['no_times']; //filter_var($_POST['no_times'], FILTER_SANITIZE_INT);
        } else {
            $counter = 0;
        }


        if (isset($_POST['earningamount'])) {
            $earningamount = str_replace(',', '', trim($_POST['earningamount']));
        } elseif (isset($_POST['continueDeductionAmount'])) {
            $earningamount = str_replace(',', '', trim($_POST['continueDeductionAmount']));
        }

        $recordtime = date('Y-m-d H:i:s');

        try {
            $query = $conn->prepare('SELECT * FROM allow_deduc WHERE staff_id = ?  AND allow_id = ? ');
            $res = $query->execute(array($currentempl, $edcode));
            $existtrans = $query->fetch();

            if ($existtrans) {
                //same transaction for current employee, current period posted
                $query = 'UPDATE allow_deduc SET value = ?, counter = ? WHERE staff_id = ?  AND allow_id = ? ';
                $conn->prepare($query)->execute(array($earningamount, $counter, $currentempl, $edcode));

                auditTrailInsert($currentempl, $edcode, $earningamount, $_SESSION['currentactiveperiod']);

                $_SESSION['alertcolor'] = $type = "danger";
                $msg = "Duplicate Earning not allowed";
                $source = $_SERVER['HTTP_REFERER'];
                $_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
            } else {
                if ($earningamount > -1) {

                    $query1 = $conn->prepare('SELECT code, tbl_earning_deduction.ed_id, tbl_earning_deduction.ed FROM tbl_earning_deduction WHERE tbl_earning_deduction.ed_id = ?');
                    $res1 = $query1->execute(array($edcode));
                    $existtrans1 = $query1->fetch();
                    if ($existtrans1) {
                        $transcode =  $existtrans1['code'];
                    }

                    $query = 'INSERT INTO allow_deduc (staff_id, allow_id, value, date_insert, inserted_by,transcode,counter) VALUES (?,?,?,?,?,?,?)';
                    $conn->prepare($query)->execute(array($currentempl, $edcode, $earningamount, $recordtime, $_SESSION['SESS_MEMBER_ID'], $transcode, $counter));

                    auditTrailInsert($currentempl, $edcode, $earningamount, $_SESSION['currentactiveperiod']);

                    $_SESSION['msg'] = $msg = "Earning successfully saved";
                    $_SESSION['alertcolor'] = $type = "success";
                    $source = $_SERVER['HTTP_REFERER'];
                    //$_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                    header('Location: ' . $source);
                } else {
                    $_SESSION['msg'] = $msg = "Employee not Entitiled to the Allowance";
                    $_SESSION['alertcolor'] = $type = "danger";
                    $source = $_SERVER['HTTP_REFERER'];
                    //$_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                    header('Location: ' . $source);
                }
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        break;


    case 'addemployeededuction':
        $currentempl = $_POST['curremployee'];
        $edcode = $_POST['newdeductioncode'];
        $deductionamount = trim($_POST['deductionamount']);
        $recordtime = date('Y-m-d H:i:s');
        $counter = $_POST['no_times']; //filter_var($_POST['no_times'], FILTER_SANITIZE_INT);

        try {
            $query = $conn->prepare('SELECT * FROM allow_deduc WHERE staff_id = ?  AND allow_id = ? ');
            $res = $query->execute(array($currentempl, $edcode));
            $existtrans = $query->fetch();

            if ($existtrans) {
                //same transaction for current employee, current period posted
                $query = 'UPDATE allow_deduc SET value = ?, date_insert = ?, inserted_by = ?, counter = ? WHERE staff_id = ?  AND allow_id = ? ';
                $conn->prepare($query)->execute(array($deductionamount, $recordtime, $_SESSION['SESS_MEMBER_ID'], $counter, $currentempl, $edcode));
                $_SESSION['msg'] = $msg = "Deduction UPdated successfully saved";
                $_SESSION['alertcolor'] = $type = "success";
                $source = $_SERVER['HTTP_REFERER'];
                //$_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                header('Location: ' . $source);
            } else {
                if ($deductionamount > 0) {
                    $query = 'INSERT INTO allow_deduc (staff_id, allow_id, value, date_insert, inserted_by,transcode,counter) VALUES (?,?,?,?,?,?,?)';
                    $conn->prepare($query)->execute(array($currentempl, $edcode, $deductionamount, $recordtime, $_SESSION['SESS_MEMBER_ID'], '02', $counter));
                    $_SESSION['msg'] = $msg = "Deduction successfully saved";
                    $_SESSION['alertcolor'] = $type = "success";
                    $source = $_SERVER['HTTP_REFERER'];
                    //$_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                    header('Location: ' . $source);
                } else {
                    $_SESSION['msg'] = $msg = "Employee not Entitiled to the Deduction";
                    $_SESSION['alertcolor'] = $type = "danger";
                    $source = $_SERVER['HTTP_REFERER'];
                    //$_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                    header('Location: ' . $source);
                }
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        break;


    case 'addemployeedeductionunion':

        $currentempl = $_POST['curremployee'];
        $edcode = $_POST['newdeductioncodeunion'];
        $deductionamount = trim($_POST['deductionamountunion']);
        $recordtime = date('Y-m-d H:i:s');
        $counter = $_POST['no_times']; //filter_var($_POST['no_times'], FILTER_SANITIZE_INT);

        try {
            $query = $conn->prepare('SELECT * FROM allow_deduc WHERE staff_id = ?  AND allow_id = ? ');
            $res = $query->execute(array($currentempl, $edcode));
            $existtrans = $query->fetch();

            if ($existtrans) {
                //same transaction for current employee, current period posted
                $query = 'UPDATE allow_deduc SET counter = ?,value = ?, date_insert = ?, inserted_by = ? WHERE staff_id = ?  AND allow_id = ? ';
                $conn->prepare($query)->execute(array($counter, $deductionamount, $recordtime, $_SESSION['SESS_MEMBER_ID'], $currentempl, $edcode));
                $_SESSION['msg'] = $msg = "Deduction UPdated successfully saved";
                $_SESSION['alertcolor'] = $type = "success";
                $source = $_SERVER['HTTP_REFERER'];
                //$_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                header('Location: ' . $source);
            } else {
                if ($deductionamount > 0) {
                    $query = 'INSERT INTO allow_deduc (counter,staff_id, allow_id, value, date_insert, inserted_by,transcode) VALUES (?,?,?,?,?,?,?)';
                    $conn->prepare($query)->execute(array($counter, $currentempl, $edcode, $deductionamount, $recordtime, $_SESSION['SESS_MEMBER_ID'], '02'));
                    $_SESSION['msg'] = $msg = "Deduction successfully saved";
                    $_SESSION['alertcolor'] = $type = "success";
                    $source = $_SERVER['HTTP_REFERER'];
                    //$_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                    header('Location: ' . $source);
                } else {
                    $_SESSION['msg'] = $msg = "Employee not Entitiled to the Deduction";
                    $_SESSION['alertcolor'] = $type = "danger";
                    $source = $_SERVER['HTTP_REFERER'];
                    //$_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                    header('Location: ' . $source);
                }
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        break;

    case 'newtempemployeededuction':
        $currentempl = $_POST['curremployee'];
        $edtype = $_POST['payType_id'];
        $edcode = $_POST['newdeductioncode'];
        $earningamount = trim($_POST['deductionamount']);
        $counter = $_POST['no_times'];
        $recordtime = date('Y-m-d H:i:s');

        $query = $conn->prepare('SELECT * FROM tbl_earning_deduction WHERE ed_id = ?');
        $res = $query->execute(array($edcode));
        if ($row = $query->fetch()) {
            echo ($row['operator']);
        }

        try {
            $query = $conn->prepare('SELECT * FROM tbl_temp WHERE staff_id = ?  AND allow_id = ? ');
            $res = $query->execute(array($currentempl, $edcode));
            $existtrans = $query->fetch();

            if ($existtrans) {
                //same transaction for current employee, current period posted
                $query = 'UPDATE tbl_temp SET value = ?, date_insert = ?, inserted_by = ?,counter = ? WHERE staff_id = ?  AND allow_id = ? ';
                $conn->prepare($query)->execute(array($earningamount, $recordtime, $_SESSION['SESS_MEMBER_ID'], $counter, $currentempl, $edcode));
                $_SESSION['msg'] = $msg = "Temp Deduction / Allowance successfully saved";
                $_SESSION['alertcolor'] = $type = "success";
                $source = $_SERVER['HTTP_REFERER'];
                //$_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                header('Location: ' . $source);
            } else {
                if ($earningamount > 0) {
                    $query = 'INSERT INTO tbl_temp (staff_id, allow_id, value, date_insert, inserted_by,type,counter) VALUES (?,?,?,?,?,?,?)';
                    $conn->prepare($query)->execute(array($currentempl, $edcode, $earningamount, $recordtime, $_SESSION['SESS_MEMBER_ID'], $row['operator'], $counter));
                    $_SESSION['msg'] = $msg = "Temp Deduction/Earning successfully saved";
                    $_SESSION['alertcolor'] = $type = "success";
                    $source = $_SERVER['HTTP_REFERER'];
                    //$_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                    header('Location: ' . $source);
                } else {
                    $_SESSION['msg'] = $msg = "Employee not Entitiled to the Deduction";
                    $_SESSION['alertcolor'] = $type = "danger";
                    $source = $_SERVER['HTTP_REFERER'];
                    //$_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                    header('Location: ' . $source);
                }
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        break;


    case 'lastpay':

        $currentempl = $_POST['lastPaystaffid'];
        $period = $_POST['currentPeriod'];
        $check = $_POST['check'];

        if ($check == 1) {

            $query = $conn->prepare('SELECT * FROM tbl_lastpay WHERE staff_id = ? AND  period = ? ');
            $res = $query->execute(array($currentempl, $period));
            $existtrans = $query->fetch();
            if ($existtrans) {
            } else {


                try {




                    $query = 'INSERT INTO tbl_lastpay (staff_id, period) VALUES (?,?)';
                    $conn->prepare($query)->execute(array($currentempl, $period));
                    $msg = "Last Pay Settings Saved Successfully";
                    $type = "success";
                    $source = $_SERVER['HTTP_REFERER'];
                    $_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }
            }
        } else {
            try {
                $query = 'DELETE FROM tbl_lastpay WHERE staff_id = ? AND  period = ?';
                $conn->prepare($query)->execute(array($currentempl, $period));
                $msg = "Last Pay Settings Saved Successfully";
                $type = "success";
                $source = $_SERVER['HTTP_REFERER'];
                $_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
        }



        break;

    case 'cash_cheque':

        $currentempl = $_POST['cashstaffid'];
        //$period = $_POST['currentPeriod'];
        $check = $_POST['check'];

        if ($check == 1) {

            $query = $conn->prepare('SELECT * FROM tbl_cash_cheque WHERE staff_id = ?');
            $res = $query->execute(array($currentempl));
            $existtrans = $query->fetch();
            if ($existtrans) {
            } else {


                try {
                    $query = 'INSERT INTO tbl_cash_cheque (staff_id,bcode,acctno) SELECT staff_id,BCODE,ACCTNO FROM employee WHERE staff_id = ?';
                    $conn->prepare($query)->execute(array($currentempl));

                    $query = 'UPDATE employee SET BCODE = ? WHERE staff_id = ?';
                    $conn->prepare($query)->execute(array('00', $currentempl));

                    $msg = "Employee Pay Method Settings Saved Successfully";
                    $type = "success";
                    $source = $_SERVER['HTTP_REFERER'];
                    $_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }
            }
        } else {
            try {

                $query = 'UPDATE employee SET BCODE = (SELECT BCODE FROM tbl_cash_cheque WHERE staff_id = ?) WHERE staff_id = ?';
                $conn->prepare($query)->execute(array($currentempl, $currentempl));

                $query = 'DELETE FROM tbl_cash_cheque WHERE staff_id = ?';
                $conn->prepare($query)->execute(array($currentempl));
                $msg = "Employee Pay Method Settings Saved Successfully";
                $type = "success";
                $source = $_SERVER['HTTP_REFERER'];
                $_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
        }



        break;

    case 'addallowance_deduction':
        $deductionname = htmlspecialchars(trim($_POST['deductionname']), ENT_QUOTES, 'UTF-8');
        $newdeductionCode = trim($_POST['newdeductionCode']);
        $recordtime = date('Y-m-d H:i:s');
        if ($newdeductionCode < 2) {
            $operator = '+';
        } else {

            $operator = '-';
        }

        try {
            $query = $conn->prepare('SELECT * FROM tbl_earning_deduction WHERE ed = ? or edDesc = ?');
            $res = $query->execute(array($deductionname, $deductionname));
            $existtrans = $query->fetch();

            if ($existtrans) {
                //same transaction for current employee, current period posted
                $_SESSION['msg'] = $msg = "Deduction Already Existing";
                $_SESSION['alertcolor'] = $type = "danger";
                $source = $_SERVER['HTTP_REFERER'];
                //$_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                header('Location: ' . $source);
            } else {

                $query = 'INSERT INTO tbl_earning_deduction (ed, edType, edDesc, edCreatedDate,edCreatedBy,operator ) VALUES (?,?,?,?,?,?)';
                $conn->prepare($query)->execute(array($deductionname, $newdeductionCode, $deductionname, $recordtime, $_SESSION['SESS_MEMBER_ID'], $operator));
                $_SESSION['msg'] = $msg = "Earning successfully saved";
                $_SESSION['alertcolor'] = $type = "success";
                $source = $_SERVER['HTTP_REFERER'];
                //$_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
                header('Location: ' . $source);
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        break;

    case 'editorganization':
        $compname = htmlspecialchars(trim($_POST['compname']), ENT_QUOTES, 'UTF-8');
        $city = htmlspecialchars(trim($_POST['city']), ENT_QUOTES, 'UTF-8');
        $county = htmlspecialchars(trim($_POST['county']), ENT_QUOTES, 'UTF-8');
        $compemail = filter_var((htmlspecialchars(trim($_POST['compemail']), ENT_QUOTES, 'UTF-8')), FILTER_VALIDATE_EMAIL);
        $compphone = htmlspecialchars(trim($_POST['compphone']), ENT_QUOTES, 'UTF-8');
        $companypin = htmlspecialchars(trim($_POST['companypin']), ENT_QUOTES, 'UTF-8');
        $nssfnumber = htmlspecialchars(trim($_POST['nssfnumber']), ENT_QUOTES, 'UTF-8');
        $nhifnumber = htmlspecialchars(trim($_POST['nhifnumber']), ENT_QUOTES, 'UTF-8');

        $startyear = date('Y-m-d', strtotime(htmlspecialchars(trim($_POST['startyear']), ENT_QUOTES, 'UTF-8')));
        $endyear = date('Y-m-d', strtotime(htmlspecialchars(trim($_POST['endyear']), ENT_QUOTES, 'UTF-8')));

        try {
            $query = 'INSERT INTO company (companyName, city, county, companyEmail, contactTelephone, companyPin, companyNssf, companyNhif, companyId, yearStart, yearEnd) VALUES (?,?,?,?,?,?,?,?,?,?,?)';
            $conn->prepare($query)->execute(array($compname, $city, $county, $compemail, $compphone, $companypin, $nssfnumber, $nhifnumber, $_SESSION['companyid'], $startyear, $endyear));
            $msg = "Company Details successfully saved";
            $type = "success";
            $source = $_SERVER['HTTP_REFERER'];
            $_SESSION['msg'] = $msg;
                $_SESSION['alertcolor'] = $type;
                header('Location: ' . $source);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;


    case 'addperiod':
        $periodname = htmlspecialchars(trim($_POST['perioddesc']), ENT_QUOTES, 'UTF-8');
        $periodyear = htmlspecialchars(trim($_POST['periodyear']), ENT_QUOTES, 'UTF-8');
        $periodDescription = $periodname . " - " . $periodyear;
        //exit(var_dump(is_int($_SESSION['currentactiveperiod')));
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



                $_SESSION['msg'] = "New Period Succesfully Created";
                $_SESSION['alertcolor'] = "success";
                header('Location: ' . $source);
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        break;


    case 'closeActivePeriod':
        try {

            //reset period id
            //reset assigned active period id

            exit('closeActivePeriod');
        } catch (PDOEXception $e) {
            echo $e->getMessage();
        }
        break;

    case 'deletecurrentperiod':

        $period = htmlspecialchars(trim($_GET['activeperiodID']), ENT_QUOTES, 'UTF-8');

        $payrollquery2 = $conn->prepare('SELECT * FROM payperiods WHERE periodId = ? and payrollRun = ?');
        $payrollquery2->execute(array($period, 1));
        //$deduc = $payrollquery2->fetchAll(PDO::FETCH_ASSOC);

        if ($row = $payrollquery2->fetch()) {
            try {

                $query = 'DELETE FROM tbl_devlevy where period = ?';
                $conn->prepare($query)->execute(array($period));

                $query = 'DELETE FROM tbl_master where period = ?';
                $conn->prepare($query)->execute(array($period));

                $query = 'DELETE FROM master_staff where period = ?';
                $conn->prepare($query)->execute(array($period));


                $query = 'DELETE FROM tbl_repayment where period = ?';
                $conn->prepare($query)->execute(array($period));

                $payrollquery2 = $conn->prepare('SELECT completedloan.id, completedloan.type,completedloan.staff_id, completedloan.allow_id, completedloan.period, completedloan.`value` FROM completedloan WHERE period = ?');
                $payrollquery2->execute(array($period));
                $deduc = $payrollquery2->fetchAll(PDO::FETCH_ASSOC);
                foreach ($deduc as $row => $link2) {
                    $query = 'INSERT INTO allow_deduc (staff_id,allow_id,`value`,transcode) VALUES (?,?,?,?)';
                    $conn->prepare($query)->execute(array($link2['staff_id'], $link2['allow_id'], $link2['value'], $link2['type']));
                }
                $query = 'DELETE FROM completedloan where period = ?';
                $conn->prepare($query)->execute(array($period));

                //Update employee status to Active

                $payrollquery2 = $conn->prepare('SELECT * FROM tbl_lastpay WHERE period = ?');
                $payrollquery2->execute(array($period));
                $deduc = $payrollquery2->fetchAll(PDO::FETCH_ASSOC);
                foreach ($deduc as $row => $link2) {
                    $query = 'UPDATE employee SET STATUSCD = ?  WHERE staff_id = ?';
                    $conn->prepare($query)->execute(array('A', $link2['staff_id']));
                }

                $query = 'UPDATE payperiods SET payrollRun = ? where periodId = ?';
                $conn->prepare($query)->execute(array(0, $period));



                $_SESSION['msg'] = $msg = $_SESSION['activeperiodDescription'] . " Succesfully Deleted.";
                $_SESSION['alertcolor'] = 'success';
                header('Location: ' . $source);
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
        } else {
            echo '0';
        }
        break;

    case 'deletecurrentstaffPayslip':

        $period = htmlspecialchars(trim($_SESSION['currentactiveperiod']), ENT_QUOTES, 'UTF-8');
        $staff_id = htmlspecialchars(trim($_POST['thisemployee']), ENT_QUOTES, 'UTF-8');

        $payrollquery2 = $conn->prepare('SELECT * FROM payperiods WHERE periodId = ? and payrollRun = ?');
        $payrollquery2->execute(array($period, 1));
        //$deduc = $payrollquery2->fetchAll(PDO::FETCH_ASSOC);

        if ($row = $payrollquery2->fetch()) {
            try {

                $query = 'DELETE FROM tbl_devlevy where period = ? and staff_id = ?';
                $conn->prepare($query)->execute(array($period, $staff_id));

                $query = 'DELETE FROM tbl_master where period = ? and staff_id = ?';
                $conn->prepare($query)->execute(array($period, $staff_id));

                $query = 'DELETE FROM master_staff where period = ? and staff_id = ?';
                $conn->prepare($query)->execute(array($period, $staff_id));


                $query = 'DELETE FROM tbl_repayment where period = ? and staff_id = ?';
                $conn->prepare($query)->execute(array($period, $staff_id));

                $payrollquery2 = $conn->prepare('SELECT completedloan.id, completedloan.type,completedloan.staff_id, completedloan.allow_id, completedloan.period, completedloan.`value` FROM completedloan WHERE period = ? and staff_id = ?');
                $payrollquery2->execute(array($period, $staff_id));
                $deduc = $payrollquery2->fetchAll(PDO::FETCH_ASSOC);
                foreach ($deduc as $row => $link2) {

                    $query = $conn->prepare('SELECT allow_id FROM allow_deduc WHERE staff_id = ? AND allow_id = ? ');
                    $allowCheck = $query->execute(array($link2['staff_id'], $link2['allow_id']));

                    if (!$row = $query->fetch()) {

                        $query = 'INSERT INTO allow_deduc (staff_id,allow_id,`value`,transcode) VALUES (?,?,?,?)';
                        $conn->prepare($query)->execute(array($link2['staff_id'], $link2['allow_id'], $link2['value'], $link2['type']));
                    }
                }
                $query = 'DELETE FROM completedloan where period = ? AND staff_id = ?';
                $conn->prepare($query)->execute(array($period, $staff_id));

                //$query = 'UPDATE payperiods SET payrollRun = ? where periodId = ?';
                //$conn->prepare($query)->execute(array(0,$period));



                $_SESSION['msg'] = $msg = $staff_id . " Succesfully Deleted.";
                $_SESSION['alertcolor'] = 'success';
                header('Location: ' . $source);
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
        } else {
            $_SESSION['msg'] = $msg = "Payroll for {$staff_id} has not been Run.";
            $_SESSION['alertcolor'] = 'warning';
            header('Location: ' . $source);
        }
        break;

    case 'activateclosedperiod':
        try {
            $reactivateperiodid = htmlspecialchars(trim($_POST['reactivateperiodid']), ENT_QUOTES, 'UTF-8');
            //exit('activateclosedperiod ' . $reactivateperiodid);

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


    case 'addNewEmp':
        //check for existing same employee number

        // ID values, likely numeric
        $StaffID = filter_var($_POST['StaffID'], FILTER_VALIDATE_INT);
        $CoopID = $_POST['CoopID'];

        // String values, possibly for display
        $CourtesyTitle = htmlspecialchars($_POST['CourtesyTitle'], ENT_QUOTES, 'UTF-8');
        $Gender = htmlspecialchars($_POST['Gender'], ENT_QUOTES, 'UTF-8');
        $LastName = htmlspecialchars(ucfirst($_POST['LastName']), ENT_QUOTES, 'UTF-8');
        $FirstName = htmlspecialchars(ucfirst($_POST['FirstName']), ENT_QUOTES, 'UTF-8');
        $MiddleName = htmlspecialchars(ucfirst($_POST['MiddleName']), ENT_QUOTES, 'UTF-8');
        $Town = htmlspecialchars($_POST['Town'], ENT_QUOTES, 'UTF-8');
        $State = htmlspecialchars($_POST['State'], ENT_QUOTES, 'UTF-8');
        $Department = htmlspecialchars($_POST['Department'], ENT_QUOTES, 'UTF-8');
        $JobPosition = htmlspecialchars($_POST['JobPosition'], ENT_QUOTES, 'UTF-8');
        $NOKFirstName = htmlspecialchars(ucfirst($_POST['NOKFirstName']), ENT_QUOTES, 'UTF-8');
        $NOKMiddleName = htmlspecialchars(ucfirst($_POST['NOKMiddleName']), ENT_QUOTES, 'UTF-8');
        $NOKLastName = htmlspecialchars(ucfirst($_POST['NOKLastName']), ENT_QUOTES, 'UTF-8');

        // Specific Formats

        // Mobile Number
        $MobileNumber = preg_replace('/[^0-9+]/', '', $_POST['MobileNumber']); // Remove non-numeric and non-plus characters

        // Email Address (no changes needed)
        $EmailAddress = filter_var($_POST['EmailAddress'], FILTER_VALIDATE_EMAIL);

        // Hire Date and Birth Date (assuming YYYY-MM-DD format)
        $HireDate = $_POST['HireDate']; // Remove non-alphanumeric characters
        $BirthDate = $_POST['BirthDate']; // Remove non-alphanumeric characters

        // NOKTel (similar to MobileNumber)
        $NOKTel = preg_replace('/[^0-9+]/', '', $_POST['NOKTel']); // Remove non-numeric and non-plus characters

        // Monthly Contribution (using floatval for type safety)
        $monthlyContri = filter_var($_POST['monthlyContri'], FILTER_VALIDATE_FLOAT);
        if ($monthlyContri !== false) {
            $monthlyContri = floatval($monthlyContri);
        } else {
            // Handle invalid input (e.g., set to 0 or show an error)
            $monthlyContri = 0.0; // Example: set to 0 if invalid
        }


        // Status (specific values)
        if (!isset($_POST['Status'])) {
            $_POST['Status'] = 'In-Active';
        }
        $allowedStatuses = ['Active', 'In-Active']; // Define allowed statuses
        $Status = in_array($_POST['Status'], $allowedStatuses) ? $_POST['Status'] : 'In-Active';

        // Sanitize StreetAddress for potential HTML input
        $StreetAddress = trim(htmlspecialchars($_POST['StreetAddress'], ENT_QUOTES, 'UTF-8'));


        //validate for empty mandatory fields

        try {

            $query = 'INSERT INTO tblemployees (tblemployees.CoopID,
					tblemployees.StaffID,
					tblemployees.CourtesyTitle,
					tblemployees.FirstName,
					tblemployees.MiddleName,
					tblemployees.LastName,
					tblemployees.Gender,
					tblemployees.StreetAddress,
					tblemployees.Town,
					tblemployees.State,
					tblemployees.MobileNumber,
					tblemployees.EmailAddress,
					tblemployees.Department,
					tblemployees.BirthDate,
					tblemployees.`Status`,
					tblemployees.HireDate,
					tblemployees.JobPosition,tblemployees.NOKFirstName,tblemployees.NOKMiddleName,tblemployees.NOKLastName,tblemployees.NOKTel) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';

            $conn->prepare($query)->execute(array(
                $CoopID, $StaffID, $CourtesyTitle, $FirstName,
                $MiddleName,  $LastName, $Gender, $StreetAddress, $Town, $State, $MobileNumber,
                $EmailAddress, $Department, $BirthDate, 'Active', $HireDate, $JobPosition, $NOKFirstName, $NOKMiddleName, $NOKLastName, $NOKTel
            ));

            $query = 'INSERT INTO tbl_monthlycontribution (coopID, MonthlyContribution) VALUES (?,?)';
            $conn->prepare($query)->execute(array($CoopID, $monthlyContri));

            $password = generateRandomPassword(6);

            $query = "INSERT INTO tblusers_online (firstname, middlename,lastname,Username,PlainPassword,UPassword,CPassword ) VALUES (?,?,?,?,?,password('".$password."'),password('".$password."'))";
            $conn->prepare($query)->execute(array($FirstName, $MiddleName,$LastName,$CoopID,$password));

            $sendmessage = "Dear {$FirstName}, this is to noitfy you that your information has been registred on oouth cooperative plateform. Your coop no is {$CoopID}.<br> Your login details can be found below: <br>username = {$CoopID}<br> Password = {$password} <br />
		    To download the app to your phone, click the link below:<br /> <a href='https://play.google.com/store/apps/details?id=bankole_adesoji.oouthsagamu.cms'>Download oouth coop mobile App here</a>";

            // send sms
            //sendsms($sendmessage, $MobileNumber);
            doSendMessage($MobileNumber,$sendmessage) ;

            $mail = new PHPMailer;

            //Tell PHPMailer to use SMTP
            $mail->isSMTP();

            //Enable SMTP debugging
            //SMTP::DEBUG_OFF = off (for production use)
            //SMTP::DEBUG_CLIENT = client messages
            //SMTP::DEBUG_SERVER = client and server messages
            $mail->SMTPDebug = SMTP::DEBUG_OFF;

            //Set the hostname of the mail server
            $mail->Host = "mail.emmaggi.com";
            //Use `$mail->Host = gethostbyname('smtp.gmail.com');`
            //if your network does not support SMTP over IPv6,
            //though this may cause issues with TLS

            //Set the SMTP port number:
            // - 465 for SMTP with implicit TLS, a.k.a. RFC8314 SMTPS or
            // - 587 for SMTP+STARTTLS
            $mail->Port = 465;

            //Set the encryption mechanism to use:
            // - SMTPS (implicit TLS on port 465) or
            // - STARTTLS (explicit TLS on port 587)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

            //Whether to use SMTP authentication
            $mail->SMTPAuth = true;

            //Username to use for SMTP authentication - use full email address for gmail
            $mail->Username = "no-reply@emmaggi.com";

            //Password to use for SMTP authentication
            $mail->Password = "Banzoo@7980";

            //Set who the message is to be sent from
            //Note that with gmail you can only use your account address (same as `Username`)
            //or predefined aliases that you have configured within your account.
            //Do not use user-submitted addresses in here
            $mail->setFrom("no-reply@emmaggi.com", "OOUTH COOP");

            //Set an alternative reply-to address
            //This is a good place to put user-submitted addresses
            $mail->setFrom("no-reply@emmaggi.com", "OOUTH COOP");

            //Set who the message is to be sent to
            $mail->addAddress($EmailAddress, $FirstName);
            $mail->addBCC('bankole.adesoji@gmail.com');

            //Set the subject line
            $mail->Subject = "OOUTH COOP MOBILE APP";

            //Read an HTML message body from an external file, convert referenced images to embedded,
            //convert HTML into a basic plain-text alternative body
            //$mail->msgHTML(file_get_contents('contents.html'), __DIR__);

            //Replace the plain text body with one created manually
            $mail->AltBody = "This is a plain-text message body";

            $mail->Body = $sendmessage;

            //Attach an image file
            //$mail->addAttachment('images/phpmailer_mini.png');

            //send the message, check for errors

            if (!$mail->send()) {
                //	echo "Mailer Error: " . $mail->ErrorInfo;
            } else {
                //	echo "2";
            }


            $_SESSION['msg'] = $msg = "Employee Successfully added.";
            $_SESSION['alertcolor'] = 'success';
            $_SESSION['emptNumTack'] = $CoopID;
            header('Location: ' . $source);
            //redirect($msg,$type,$source);

        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;

    case 'upgradeGrade_Step':
        //check for existing same employee number


        $emp_no = htmlspecialchars(trim($_POST['curremployee']), ENT_QUOTES, 'UTF-8');
        $callType = htmlspecialchars(trim($_POST['callType']), ENT_QUOTES, 'UTF-8');
        $grade = htmlspecialchars(trim($_POST['new_grade']), ENT_QUOTES, 'UTF-8');
        $gradestep = htmlspecialchars(trim($_POST['new_step']), ENT_QUOTES, 'UTF-8');
        $oldstep = htmlspecialchars(trim($_POST['step']), ENT_QUOTES, 'UTF-8');
        $oldgrade = htmlspecialchars(trim($_POST['grade']), ENT_QUOTES, 'UTF-8');
        $recordtime	= $recordtime = date('Y-m-d H:i:s');




        try {

            $payrollquery2 = $conn->prepare('SELECT * FROM allow_deduc WHERE staff_id = ? and transcode = ?');
            $payrollquery2->execute(array($emp_no, 1));
            $deduc = $payrollquery2->fetchAll(PDO::FETCH_ASSOC);
            foreach ($deduc as $row => $link2) {
                $earningamount = $link2['value'];
                $earningamount = str_replace(',', '', trim($earningamount));
                if ($earningamount > 0) {
                    $query = 'UPDATE allow_deduc SET `value` = ? WHERE staff_id = ? and allow_id = ?';
                    $conn->prepare($query)->execute(array($earningamount, $emp_no, $link2['allow_id']));

                    auditTrailInsert($emp_no, $link2['allow_id'], $earningamount, $_SESSION['currentactiveperiod']);
                }
            }
            //update deductions
            $payrollquery2 = $conn->prepare('SELECT * FROM allow_deduc WHERE staff_id = ? and transcode = ?');
            $payrollquery2->execute(array($emp_no, 2));
            $deduc = $payrollquery2->fetchAll(PDO::FETCH_ASSOC);
            foreach ($deduc as $row => $link2) {
                $earningamount = $link2['value'];
                $earningamount = str_replace(',', '', trim($earningamount));
                if ($earningamount > 0) {
                    $query = 'UPDATE allow_deduc SET `value` = ? WHERE staff_id = ? and allow_id = ?';
                    $conn->prepare($query)->execute(array($earningamount, $emp_no, $link2['allow_id']));

                    auditTrailInsert($emp_no, $link2['allow_id'], $earningamount, $_SESSION['currentactiveperiod']);
                }
            }

            if ($oldgrade < 5 & $grade > 6) {
                $earningamount = 0; // Default value since getAmount function is undefined
                $earningamount = str_replace(',', '', trim($earningamount));

                $query = $conn->prepare('SELECT * FROM allow_deduc WHERE staff_id = ? and allow_id = ?');
                $res = $query->execute(array($emp_no, 23));
                $existtrans = $query->fetch();
                if ($existtrans) {
                    $query = 'UPDATE allow_deduc SET `value` = ? WHERE staff_id = ? and allow_id = ?';
                    $conn->prepare($query)->execute(array($earningamount, $emp_no, 23));
                } else {
                    $query = 'INSERT INTO allow_deduc (staff_id, allow_id, value, date_insert, inserted_by,transcode) VALUES (?,?,?,?,?,?)';
                    $conn->prepare($query)->execute(array($emp_no, 23, $earningamount, $recordtime, $_SESSION['SESS_MEMBER_ID'], 1));

                    auditTrailInsert($emp_no, 23, $earningamount, $_SESSION['currentactiveperiod']);
                }
            }
            $query = $conn->prepare('UPDATE employee SET STEP = ?, GRADE = ? WHERE staff_id = ?');
            $res = $query->execute(array($gradestep, $grade, $emp_no));
            $_SESSION['msg'] = $msg = "Employee Successfully added.";
            $_SESSION['alertcolor'] = 'success';
            header('Location: ' . $source);
            //redirect($msg,$type,$source);



        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;


    case 'updateEmp':
        //check for existing same employee number

        //$Gender = ucwords(strtolower(strip_tags(addslashes($_POST['namee']))));


        $CoopID = htmlspecialchars($_POST['CoopID'], ENT_QUOTES, 'UTF-8');


        // ID values, likely numeric
        $StaffID = filter_var($_POST['StaffID'], FILTER_VALIDATE_INT);

        // String values, possibly for display
        $CourtesyTitle = htmlspecialchars($_POST['CourtesyTitle'], ENT_QUOTES, 'UTF-8');
        $Gender = htmlspecialchars($_POST['Gender'], ENT_QUOTES, 'UTF-8');
        $Town = htmlspecialchars($_POST['Town'], ENT_QUOTES, 'UTF-8');
        $State = htmlspecialchars($_POST['State'], ENT_QUOTES, 'UTF-8');
        $Department = htmlspecialchars($_POST['Department'], ENT_QUOTES, 'UTF-8');
        $JobPosition = htmlspecialchars($_POST['JobPosition'], ENT_QUOTES, 'UTF-8');

        // Names, with formatting
        $LastName = ucfirst(htmlspecialchars(strtolower($_POST['LastName']), ENT_QUOTES, 'UTF-8'));
        $FirstName = ucfirst(htmlspecialchars(strtolower($_POST['FirstName']), ENT_QUOTES, 'UTF-8'));
        $MiddleName = ucfirst(htmlspecialchars(strtolower($_POST['MiddleName']), ENT_QUOTES, 'UTF-8'));
        $NOKFirstName = ucfirst(htmlspecialchars(strtolower($_POST['NOKFirstName']), ENT_QUOTES, 'UTF-8'));
        $NOKMiddleName = ucfirst(htmlspecialchars(strtolower($_POST['NOKMiddleName']), ENT_QUOTES, 'UTF-8'));
        $NOKLastName = ucfirst(htmlspecialchars(strtolower($_POST['NOKLastName']), ENT_QUOTES, 'UTF-8'));

        // Specific Formats

        // Mobile Number and NOKTel
        $MobileNumber = preg_replace('/[^0-9+]/', '', $_POST['MobileNumber']); // Remove non-numeric and non-plus characters

        // Validate the MobileNumber format
        if (!preg_match('/^[0-9]{10}$/', $MobileNumber)) {
            // Handle invalid MobileNumber format
            $MobileNumber = ''; // Clear or set to a default value
        }

        // Email Address (no changes needed)
        $EmailAddress = filter_var($_POST['EmailAddress'], FILTER_VALIDATE_EMAIL);

        if (isset($_POST['HireDate'])) {
            $HireDate = $_POST['HireDate'];
            $hireDateObject = DateTime::createFromFormat('Y-m-d', $HireDate);
            if ($hireDateObject && $hireDateObject->format('Y-m-d') === $HireDate) {
                $HireDate = $hireDateObject->format('Y-m-d'); // Convert to string format
            } else {
                $HireDate = null; // Invalid date format
            }
        } else {
            $HireDate = null; // Not set
        }

// Validate and sanitize Birth Date
        if (isset($_POST['BirthDate'])) {
            $BirthDate = $_POST['BirthDate'];
            $birthDateObject = DateTime::createFromFormat('Y-m-d', $BirthDate);
            if ($birthDateObject && $birthDateObject->format('Y-m-d') === $BirthDate) {
                $BirthDate = $birthDateObject->format('Y-m-d'); // Convert to string format
            } else {
                $BirthDate = null; // Invalid date format
            }
        } else {
            $BirthDate = null; // Not set
        }
        // NOKTel (similar to MobileNumber, with validation)
        $NOKTel = preg_replace('/[^0-9+]/', '', $_POST['NOKTel']); // Remove non-numeric and non-plus characters

        if (!preg_match('/^[0-9]{10}$/', $NOKTel)) {
            // Handle invalid NOKTel format
            $NOKTel = ''; // Clear or set to a default value
        }

        // Monthly Contribution (using floatval for type safety)
        $monthlyContri = filter_var($_POST['monthlyContri'], FILTER_VALIDATE_FLOAT);
        if ($monthlyContri !== false) {
            $monthlyContri = floatval($monthlyContri);
        } else {
            // Handle invalid input (e.g., set to 0 or show an error)
            $monthlyContri = 0.0; // Example: set to 0 if invalid
        }


        // Status (specific values)
        if (!isset($_POST['Status'])) {
            $_POST['Status'] = 'In-Active';
        }
        $allowedStatuses = ['Active', 'In-Active']; // Define allowed statuses
        $Status = in_array($_POST['Status'], $allowedStatuses) ? $_POST['Status'] : 'In-Active';

        // Sanitize StreetAddress for potential HTML input
        $StreetAddress = trim(htmlspecialchars($_POST['StreetAddress'], ENT_QUOTES, 'UTF-8'));



        try {




            $query = 'UPDATE tblemployees SET tblemployees.StaffID = ?,
							tblemployees.CourtesyTitle = ?,
							tblemployees.FirstName= ?,
							tblemployees.MiddleName = ?,
							tblemployees.LastName = ?,
							tblemployees.Gender = ?,
							tblemployees.StreetAddress = ?,
							tblemployees.Town = ?,
							tblemployees.State = ?,
							tblemployees.MobileNumber = ?,
							tblemployees.EmailAddress = ?,
							tblemployees.Department = ?,
							tblemployees.BirthDate = ?,
							tblemployees.`Status` = ?,
							tblemployees.HireDate = ?,tblemployees.JobPosition = ?,tblemployees.NOKFirstName = ?,
							tblemployees.NOKMiddleName = ?,tblemployees.NOKLastName = ?,tblemployees.NOKTel = ?	WHERE CoopID = ?';


            $conn->prepare($query)->execute(array(
                $StaffID, $CourtesyTitle, $FirstName,
                $MiddleName,  $LastName, $Gender, $StreetAddress, $Town, $State, $MobileNumber,
                $EmailAddress, $Department, $BirthDate, $Status, $HireDate, $JobPosition, $NOKFirstName, $NOKMiddleName, $NOKLastName,
                $NOKTel, $CoopID
            ));


            $_SESSION['msg'] = $msg = "Coop Member's Successfully updated.";
            $_SESSION['alertcolor'] = 'success';
            //	header('Location: ' . $source);
            //redirect($msg,$type,$source);



        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;

    case 'getPreviousEmployee':

        $_SESSION['emptrack'] = $_SESSION['emptrack'] - 1;
        header('Location: ' . $source);
        break;


    case 'getNextEmployee':

        $_SESSION['emptrack'] = $_SESSION['emptrack'] + 1;
        $_SESSION['empDataTrack'] = 'next';
        header('Location: ' . $source);

        break;


    case 'retrieveLeaveData':

        $leavestate = htmlspecialchars(trim($_GET['state']), ENT_QUOTES, 'UTF-8');
        $_SESSION['leavestate'] = $leavestate;

        header('Location: ' . $source);


        break;

    case 'retrieveSingleEmployeeData':

        if (isset($_POST['item'])) {

            $_POST['item'] = $_POST['item'];
        } else {

            $_POST['item'] = -1;
        }
        $empnumber = htmlspecialchars(trim($_POST['item']), ENT_QUOTES, 'UTF-8');
        $_SESSION['empDataTrack'] = 'option';
        $_SESSION['emptNumTack'] = $empnumber;

        header('Location: ' . $source);

        break;

    case 'retrieveSingleEmployee1':

        $empnumber = htmlspecialchars(trim($_POST['item']), ENT_QUOTES, 'UTF-8');
        $_SESSION['empDataTrack'] = 'option';
        $_SESSION['emptNumTack'] = $empnumber;
        //$source = 'employee.php?staff_id=$empnumber';
        //echo $source;
        header('Location: ' . $source);

        break;

    case 'vtrans':
        $empRecordId = htmlspecialchars(trim($_GET['td']), ENT_QUOTES, 'UTF-8');
        //exit($empRecordId);
        $_SESSION['empDataTrack'] = 'option';
        $_SESSION['emptNumTack'] = $empRecordId;

        header('Location: ../empearnings.php');
        break;


    case 'runCurrentEmployeePayroll':

        define('TAX_RELIEF', '1280');

        $thisemployee = htmlspecialchars(trim($_POST['thisemployee']), ENT_QUOTES, 'UTF-8');

        //check if employee has basic salary, if not return error & exit
        $query = $conn->prepare('SELECT earningDeductionCode FROM employee_earnings_deductions WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ? ');
        $rerun = $query->execute(array($thisemployee, $_SESSION['companyid'], '200', $_SESSION['currentactiveperiod'], '1'));

        if (!$row = $query->fetch()) {
            $_SESSION['msg'] = $msg = "This employee has no basic salary. Please assign basic salary in order to process employee's earnings.";
            $_SESSION['alertcolor'] = 'danger';
            header('Location: ' . $source);
        } else {

            //check if employee rerun
            try {
                $query = $conn->prepare('SELECT * FROM employee_earnings_deductions WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ? ');
                $rerun = $query->execute(array($thisemployee, $_SESSION['companyid'], '601', $_SESSION['currentactiveperiod'], '1'));

                if ($row = $query->fetch()) {

                    $query = $conn->prepare('SELECT * FROM employee_earnings_deductions WHERE employeeId = ? AND companyId = ? AND transactionType = ? AND payPeriod = ? AND active = ? ');
                    $fin = $query->execute(array($thisemployee, $_SESSION['companyid'], 'Earning', $_SESSION['currentactiveperiod'], '1'));
                    $res = $query->fetchAll(PDO::FETCH_ASSOC);
                    $thisemployeeearnings = 0;

                    foreach ($res as $row => $link) {
                        $thisemployeeearnings = $thisemployeeearnings + $link['amount'];
                    }

                    $recordtime = date('Y-m-d H:i:s');
                    //Run with an update query
                    $grossquery = 'UPDATE employee_earnings_deductions SET amount = ?, editTime = ?, userId = ? WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ?';
                    $conn->prepare($grossquery)->execute(array($thisemployeeearnings, $recordtime, $_SESSION['user'], $thisemployee, $_SESSION['companyid'], '601',  $_SESSION['currentactiveperiod'], '1'));

                    //NHIF Bands
                    if ($thisemployeeearnings > 0 && $thisemployeeearnings < 5999) {
                        $thisEmpNhif = 150;
                    } elseif ($thisemployeeearnings > 5999 && $thisemployeeearnings <= 7999) {
                        $thisEmpNhif = 300;
                    } elseif ($thisemployeeearnings > 7999 && $thisemployeeearnings <= 11999) {
                        $thisEmpNhif = 400;
                    } elseif ($thisemployeeearnings > 11999 && $thisemployeeearnings <= 14999) {
                        $thisEmpNhif = 500;
                    } elseif ($thisemployeeearnings > 14999 && $thisemployeeearnings <= 19999) {
                        $thisEmpNhif = 600;
                    } elseif ($thisemployeeearnings > 19999 && $thisemployeeearnings <= 24999) {
                        $thisEmpNhif = 750;
                    } elseif ($thisemployeeearnings > 24999 && $thisemployeeearnings <= 29999) {
                        $thisEmpNhif = 850;
                    } elseif ($thisemployeeearnings > 29999 && $thisemployeeearnings <= 34999) {
                        $thisEmpNhif = 900;
                    } elseif ($thisemployeeearnings > 34999 && $thisemployeeearnings <= 39999) {
                        $thisEmpNhif = 950;
                    } elseif ($thisemployeeearnings > 39999 && $thisemployeeearnings <= 44999) {
                        $thisEmpNhif = 1000;
                    } elseif ($thisemployeeearnings > 44999 && $thisemployeeearnings <= 49999) {
                        $thisEmpNhif = 1100;
                    } elseif ($thisemployeeearnings > 49999 && $thisemployeeearnings <= 59999) {
                        $thisEmpNhif = 1200;
                    } elseif ($thisemployeeearnings > 59999 && $thisemployeeearnings <= 69999) {
                        $thisEmpNhif = 1300;
                    } elseif ($thisemployeeearnings > 69999 && $thisemployeeearnings <= 79999) {
                        $thisEmpNhif = 1400;
                    } elseif ($thisemployeeearnings > 79999 && $thisemployeeearnings <= 89999) {
                        $thisEmpNhif = 1500;
                    } elseif ($thisemployeeearnings > 89999 && $thisemployeeearnings <= 99999) {
                        $thisEmpNhif = 1600;
                    } elseif ($thisemployeeearnings > 99999) {
                        $thisEmpNhif = 1700;
                    }

                    $nhifquery = 'UPDATE employee_earnings_deductions SET amount = ?, editTime = ?, userId = ? WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ?';
                    $conn->prepare($nhifquery)->execute(array($thisEmpNhif, $recordtime, $_SESSION['user'], $thisemployee, $_SESSION['companyid'], '481',  $_SESSION['currentactiveperiod'], '1'));

                    //NSSF is standard. No recalculation
                    $thisemployeeNssfBand1 = 200;
                    //Compute Taxable Income
                    $thisEmpTaxablePay = $thisemployeeearnings - $thisemployeeNssfBand1;
                    $taxpayquery = 'UPDATE employee_earnings_deductions SET amount = ?, editTime = ?, userId = ? WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ?';
                    $conn->prepare($taxpayquery)->execute(array($thisEmpTaxablePay, $recordtime, $_SESSION['user'], $thisemployee, $_SESSION['companyid'], '400',  $_SESSION['currentactiveperiod'], '1'));

                    //Compute PAYE
                    $employeepayee = 0;
                    $taxpay = $thisEmpTaxablePay;
                    if ($taxpay > 0 && $taxpay <= 11180) {
                        $employeepayee = $taxpay * 0.1;
                    } elseif ($taxpay > 11180 && $taxpay <= 21714) {
                        $employeepayee = (11180 * 0.1) + (($taxpay - 11180) * 0.15);
                    } elseif ($taxpay > 21714 && $taxpay <= 32248) {
                        $employeepayee = (11180 * 0.1) + (10534 * 0.15) + (($taxpay - 11181 - 10533) * 0.2);
                    } elseif ($taxpay > 32248 && $taxpay <= 42782) {
                        $employeepayee = (11180 * 0.1) + (10534 * 0.15) + (10534 * 0.2) + (($taxpay - 11181 - 10533 - 10534) * 0.25);
                    } elseif ($taxpay > 42782) {
                        $employeepayee = (11180 * 0.1) + (10534 * 0.15) + (10534 * 0.2) + (10534 * 0.25) + (($taxpay - 11181 - 10533 - 10534 - 10534) * 0.3);
                    }

                    $taxcharged = $employeepayee;
                    $taxchargequery = 'UPDATE employee_earnings_deductions SET amount = ?, editTime = ?, userId = ? WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ?';
                    $conn->prepare($taxchargequery)->execute(array($taxcharged, $recordtime, $_SESSION['user'], $thisemployee, $_SESSION['companyid'], '399',  $_SESSION['currentactiveperiod'], '1'));


                    $finalEmployeePayee = $employeepayee - TAX_RELIEF;

                    if ($finalEmployeePayee  <= 0) {
                        $finalEmployeePayee = 0;
                    }

                    $taxpayequery = 'UPDATE employee_earnings_deductions SET amount = ?, editTime = ?, userId = ? WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ?';
                    $conn->prepare($taxpayequery)->execute(array($finalEmployeePayee, $recordtime, $_SESSION['user'], $thisemployee, $_SESSION['companyid'], '550',  $_SESSION['currentactiveperiod'], '1'));


                    //Fetch and populate all deductions and write total
                    $query = $conn->prepare('SELECT * FROM employee_earnings_deductions WHERE employeeId = ? AND companyId = ? AND transactionType = ? AND payPeriod = ? AND active = ? ');
                    $fin = $query->execute(array($thisemployee, $_SESSION['companyid'], 'Deduction', $_SESSION['currentactiveperiod'], '1'));
                    $res = $query->fetchAll(PDO::FETCH_ASSOC);
                    $thisemployeeearnings = 0;

                    foreach ($res as $row => $link) {
                        $thisemployeedeductions = $thisemployeedeductions + $link['amount'];
                    }

                    $recordtime = date('Y-m-d H:i:s');
                    $deductionsquery = 'UPDATE employee_earnings_deductions SET amount = ?, editTime = ?, userId = ? WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ?';
                    $conn->prepare($deductionsquery)->execute(array($thisemployeedeductions, $recordtime, $_SESSION['user'], $thisemployee, $_SESSION['companyid'], '603',  $_SESSION['currentactiveperiod'], '1'));

                    //Calculate Net Salary
                    $thisemployeeNet = $thisEmpTaxablePay - $thisemployeedeductions;

                    $netquery = 'UPDATE employee_earnings_deductions SET amount = ?, editTime = ?, userId = ? WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ?';
                    $conn->prepare($netquery)->execute(array($thisemployeeNet, $recordtime, $_SESSION['user'], $thisemployee, $_SESSION['companyid'], '600',  $_SESSION['currentactiveperiod'], '1'));


                    $_SESSION['msg'] = 'Employee payroll re-run successful';
                    $_SESSION['alertcolor'] = 'success';
                    //echo $thisemployeeearnings;
                    //exit("Re run");
                    header('Location: ' . $source);
                } else {
                    //new; insert records
                    //Fetch and populate all taxable earnings and write total
                    $query = $conn->prepare('SELECT * FROM employee_earnings_deductions WHERE employeeId = ? AND companyId = ? AND transactionType = ? AND payPeriod = ? AND active = ? ');
                    $fin = $query->execute(array($thisemployee, $_SESSION['companyid'], 'Earning', $_SESSION['currentactiveperiod'], '1'));
                    $res = $query->fetchAll(PDO::FETCH_ASSOC);
                    $thisemployeeearnings = 0;

                    foreach ($res as $row => $link) {
                        $thisemployeeearnings = $thisemployeeearnings + $link['amount'];
                    }

                    $recordtime = date('Y-m-d H:i:s');
                    $grossquery = 'INSERT INTO employee_earnings_deductions (employeeId, companyId, transactionType, earningDeductionCode, amount, payPeriod, standardRecurrent, active, editTime, userId) VALUES (?,?,?,?,?,?,?,?,?,?)';
                    $conn->prepare($grossquery)->execute(array($thisemployee, $_SESSION['companyid'], 'Calc', '601', $thisemployeeearnings, $_SESSION['currentactiveperiod'], '0', '1', $recordtime, $_SESSION['user']));

                    //Get initial statutories - NHIF, NSSF, Tax relief
                    //NHIF Bands
                    if ($thisemployeeearnings > 0 && $thisemployeeearnings < 5999) {
                        $thisEmpNhif = 150;
                    } elseif ($thisemployeeearnings > 5999 && $thisemployeeearnings <= 7999) {
                        $thisEmpNhif = 300;
                    } elseif ($thisemployeeearnings > 7999 && $thisemployeeearnings <= 11999) {
                        $thisEmpNhif = 400;
                    } elseif ($thisemployeeearnings > 11999 && $thisemployeeearnings <= 14999) {
                        $thisEmpNhif = 500;
                    } elseif ($thisemployeeearnings > 14999 && $thisemployeeearnings <= 19999) {
                        $thisEmpNhif = 600;
                    } elseif ($thisemployeeearnings > 19999 && $thisemployeeearnings <= 24999) {
                        $thisEmpNhif = 750;
                    } elseif ($thisemployeeearnings > 24999 && $thisemployeeearnings <= 29999) {
                        $thisEmpNhif = 850;
                    } elseif ($thisemployeeearnings > 29999 && $thisemployeeearnings <= 34999) {
                        $thisEmpNhif = 900;
                    } elseif ($thisemployeeearnings > 34999 && $thisemployeeearnings <= 39999) {
                        $thisEmpNhif = 950;
                    } elseif ($thisemployeeearnings > 39999 && $thisemployeeearnings <= 44999) {
                        $thisEmpNhif = 1000;
                    } elseif ($thisemployeeearnings > 44999 && $thisemployeeearnings <= 49999) {
                        $thisEmpNhif = 1100;
                    } elseif ($thisemployeeearnings > 49999 && $thisemployeeearnings <= 59999) {
                        $thisEmpNhif = 1200;
                    } elseif ($thisemployeeearnings > 59999 && $thisemployeeearnings <= 69999) {
                        $thisEmpNhif = 1300;
                    } elseif ($thisemployeeearnings > 69999 && $thisemployeeearnings <= 79999) {
                        $thisEmpNhif = 1400;
                    } elseif ($thisemployeeearnings > 79999 && $thisemployeeearnings <= 89999) {
                        $thisEmpNhif = 1500;
                    } elseif ($thisemployeeearnings > 89999 && $thisemployeeearnings <= 99999) {
                        $thisEmpNhif = 1600;
                    } elseif ($thisemployeeearnings > 99999) {
                        $thisEmpNhif = 1700;
                    }

                    $nhifquery = 'INSERT INTO employee_earnings_deductions (employeeId, companyId, transactionType, earningDeductionCode, amount, payPeriod, standardRecurrent, active, editTime, userId) VALUES (?,?,?,?,?,?,?,?,?,?)';
                    $conn->prepare($nhifquery)->execute(array($thisemployee, $_SESSION['companyid'], 'Deduction', '481', $thisEmpNhif, $_SESSION['currentactiveperiod'], '0', '1', $recordtime, $_SESSION['user']));

                    //NSSF Band Calculation
                    $thisemployeeNssfBand1 = 200;

                    /*$thisemployeeNssfBand1 = $thisemployeeearnings * 0.06;
                            if ($thisemployeeNssfBand1 > 360) {
                                $thisemployeeNssfBand1 = 360;
                            }*/
                    $nssfquery = 'INSERT INTO employee_earnings_deductions (employeeId, companyId, transactionType, earningDeductionCode, amount, payPeriod, standardRecurrent, active, editTime, userId) VALUES (?,?,?,?,?,?,?,?,?,?)';
                    $conn->prepare($nssfquery)->execute(array($thisemployee, $_SESSION['companyid'], 'Deduction', '482', $thisemployeeNssfBand1, $_SESSION['currentactiveperiod'], '0', '1', $recordtime, $_SESSION['user']));

                    //Compute Taxable Income
                    $thisEmpTaxablePay = $thisemployeeearnings - $thisemployeeNssfBand1;
                    $taxpayquery = 'INSERT INTO employee_earnings_deductions (employeeId, companyId, transactionType, earningDeductionCode, amount, payPeriod, standardRecurrent, active, editTime, userId) VALUES (?,?,?,?,?,?,?,?,?,?)';
                    $conn->prepare($taxpayquery)->execute(array($thisemployee, $_SESSION['companyid'], 'Calc', '400', $thisEmpTaxablePay, $_SESSION['currentactiveperiod'], '0', '1', $recordtime, $_SESSION['user']));


                    //Compute PAYE
                    $employeepayee = 0;
                    $taxpay = $thisEmpTaxablePay;
                    if ($taxpay > 0 && $taxpay <= 11180) {
                        $employeepayee = $taxpay * 0.1;
                    } elseif ($taxpay > 11180 && $taxpay <= 21714) {
                        $employeepayee = (11180 * 0.1) + (($taxpay - 11180) * 0.15);
                    } elseif ($taxpay > 21714 && $taxpay <= 32248) {
                        $employeepayee = (11180 * 0.1) + (10534 * 0.15) + (($taxpay - 11181 - 10533) * 0.2);
                    } elseif ($taxpay > 32248 && $taxpay <= 42782) {
                        $employeepayee = (11180 * 0.1) + (10534 * 0.15) + (10534 * 0.2) + (($taxpay - 11181 - 10533 - 10534) * 0.25);
                    } elseif ($taxpay > 42782) {
                        $employeepayee = (11180 * 0.1) + (10534 * 0.15) + (10534 * 0.2) + (10534 * 0.25) + (($taxpay - 11181 - 10533 - 10534 - 10534) * 0.3);
                    }

                    $taxcharged = $employeepayee;
                    $taxchargequery = 'INSERT INTO employee_earnings_deductions (employeeId, companyId, transactionType, earningDeductionCode, amount, payPeriod, standardRecurrent, active, editTime, userId) VALUES (?,?,?,?,?,?,?,?,?,?)';
                    $conn->prepare($taxchargequery)->execute(array($thisemployee, $_SESSION['companyid'], 'Calc', '399', $taxcharged, $_SESSION['currentactiveperiod'], '0', '1', $recordtime, $_SESSION['user']));

                    $finalEmployeePayee = $employeepayee - TAX_RELIEF;

                    if ($finalEmployeePayee  <= 0) {
                        $finalEmployeePayee = 0;
                    }

                    $taxpayequery = 'INSERT INTO employee_earnings_deductions (employeeId, companyId, transactionType, earningDeductionCode, amount, payPeriod, standardRecurrent, active, editTime, userId) VALUES (?,?,?,?,?,?,?,?,?,?)';
                    $conn->prepare($taxpayequery)->execute(array($thisemployee, $_SESSION['companyid'], 'Deduction', '550', $finalEmployeePayee, $_SESSION['currentactiveperiod'], '0', '1', $recordtime, $_SESSION['user']));


                    //Fetch and populate all deductions and write total
                    $query = $conn->prepare('SELECT * FROM employee_earnings_deductions WHERE employeeId = ? AND companyId = ? AND transactionType = ? AND payPeriod = ? AND active = ? ');
                    $fin = $query->execute(array($thisemployee, $_SESSION['companyid'], 'Deduction', $_SESSION['currentactiveperiod'], '1'));
                    $res = $query->fetchAll(PDO::FETCH_ASSOC);
                    $thisemployeedeductions = 0;

                    foreach ($res as $row => $link) {
                        $thisemployeedeductions = $thisemployeedeductions + $link['amount'];
                    }

                    $recordtime = date('Y-m-d H:i:s');
                    $deductionsquery = 'INSERT INTO employee_earnings_deductions (employeeId, companyId, transactionType, earningDeductionCode, amount, payPeriod, standardRecurrent, active, editTime, userId) VALUES (?,?,?,?,?,?,?,?,?,?)';
                    $conn->prepare($deductionsquery)->execute(array($thisemployee, $_SESSION['companyid'], 'Calc', '603', $thisemployeedeductions, $_SESSION['currentactiveperiod'], '0', '1', $recordtime, $_SESSION['user']));

                    //Calculate Net Salary
                    $thisemployeeNet = $thisEmpTaxablePay - $thisemployeedeductions;

                    $netquery = 'INSERT INTO employee_earnings_deductions (employeeId, companyId, transactionType, earningDeductionCode, amount, payPeriod, standardRecurrent, active, editTime, userId) VALUES (?,?,?,?,?,?,?,?,?,?)';
                    $conn->prepare($netquery)->execute(array($thisemployee, $_SESSION['companyid'], 'Calc', '600', $thisemployeeNet, $_SESSION['currentactiveperiod'], '0', '1', $recordtime, $_SESSION['user']));

                    $_SESSION['msg'] = 'Employee payroll run successful';
                    $_SESSION['alertcolor'] = 'success';
                    header('Location: ' . $source);
                }
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
        }



        break;


    case 'runGlobalPayroll':
        ini_set('max_execution_time', '0');
        $connect = mysqli_connect("localhost", "root", "oluwaseyi", "salary");
        //include_once('functions.php');
        //session_start();


        $period = $_SESSION['currentactiveperiod'];



        $query = $conn->prepare('SELECT * FROM payperiods WHERE payrollRun = ? and periodId = ?');
        $fin = $query->execute(array(0, $_SESSION['currentactiveperiod']));
        $existtrans = $query->fetch();

        if ($existtrans) {

            try { //echo $period ;
                global $conn;
                $query = $conn->prepare('SELECT * FROM employee WHERE STATUSCD = ?');
                $res = $query->execute(array('A'));
                $out = $query->fetchAll(PDO::FETCH_ASSOC);
                //get employee info
                while ($row = array_shift($out)) {
                    $queryMaster = $conn->prepare('INSERT INTO master_staff (staff_id,NAME,DEPTCD,BCODE,ACCTNO,GRADE,STEP,period,PFACODE,PFAACCTNO) VALUES (?,?,?,?,?,?,?,?,?,?)');
                    $master = $queryMaster->execute(array($row['staff_id'], $row['NAME'], $row['DEPTCD'], $row['BCODE'], $row['ACCTNO'], $row['GRADE'], $row['STEP'], $period, $row['PFACODE'], $row['PFAACCTNO']));

                    echo 'staff id' . ' ' . $row['staff_id'] . '<br>';
                    $query_allow = $conn->prepare('SELECT allow_deduc.temp_id, allow_deduc.staff_id, allow_deduc.allow_id, allow_deduc.`value`, allow_deduc.transcode, allow_deduc.counter,  allow_deduc.running_counter, allow_deduc.inserted_by, allow_deduc.date_insert,tbl_earning_deduction.edDesc FROM allow_deduc
 																				INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = allow_deduc.allow_id WHERE staff_id = ? and transcode = ? order by allow_deduc.allow_id asc');
                    $res_allow = $query_allow->execute(array($row['staff_id'], '1'));
                    $out_allow = $query_allow->fetchAll(PDO::FETCH_ASSOC);
                    while ($row_allow = array_shift($out_allow)) {


                        if ($row_allow['allow_id'] == '21') {

                            $query_value = $conn->prepare('SELECT allowancetable.`value` FROM allowancetable WHERE allowancetable.grade = ? AND allowancetable.step = ? AND allowcode = ? AND category = ?');
                            $rerun_value = $query_value->execute(array($row['GRADE'], $row['STEP'], $row_allow['allow_id'], $row['CALLTYPE']));
                        } else {

                            $query_value = $conn->prepare('SELECT allowancetable.`value` FROM allowancetable WHERE allowancetable.grade = ? AND allowancetable.step = ? AND allowcode = ?');
                            $rerun_value = $query_value->execute(array($row['GRADE'], $row['STEP'], $row_allow['allow_id']));
                        }


                        if ($row_value = $query_value->fetch()) {
                            $output = $row_value['value'];
                        } else {

                            $output = $row_allow['value'];
                        }


                        echo $row_allow['allow_id'] . ' ' . $row_allow['edDesc'] . ' ' . number_format($output) . '<br>';
                        try {
                            $recordtime = date('Y-m-d H:i:s');
                            $query = 'INSERT INTO tbl_master (staff_id, allow_id, allow, type, period,editTime,userID) VALUES (?,?,?,?,?,?,?)';
                            $conn->prepare($query)->execute(array($row['staff_id'], $row_allow['allow_id'], $output, '1',  $period, $recordtime, $_SESSION['SESS_MEMBER_ID']));
                        } catch (PDOException $e) {
                            echo $e->getMessage();
                        }
                        if (intval($row_allow['counter']) > 0) {
                            echo 'allowance deduction counter check';
                            $running_counter = intval($row_allow['running_counter']);
                            $running_counter = $running_counter + 1;
                            if (($running_counter) == intval($row_allow['counter'])) {

                                $query = 'INSERT INTO completedLoan (staff_id,allow_id,period,value,type)VALUES (?,?,?,?,?)';
                                $conn->prepare($query)->execute(array($row['staff_id'], $row_allow['allow_id'], $period, $output, '1'));

                                //delete allow once cycle is complete
                                $sqlDelete = "DELETE FROM allow_deduc WHERE temp_id = '" . $row_allow['temp_id'] . "'";
                                $conn->exec($sqlDelete);
                            } else {
                                $sqlUpdate = "update allow_deduc set running_counter = '" . $running_counter . "' WHERE temp_id = '" . $row_allow['temp_id'] . "'";
                                $conn->exec($sqlUpdate);
                            }
                        }
                    }


                    // deduction process


                    $total_rows = '';

                    $query_deduct = $conn->prepare('SELECT allow_deduc.temp_id, allow_deduc.staff_id, allow_deduc.allow_id, allow_deduc.`value`, allow_deduc.transcode, allow_deduc.counter,  allow_deduc.running_counter, allow_deduc.inserted_by, allow_deduc.date_insert,tbl_earning_deduction.edDesc,tbl_earning_deduction.edType FROM allow_deduc
																			 INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = allow_deduc.allow_id WHERE staff_id = ? and transcode = ? order by allow_deduc.allow_id asc');
                    $res_deduct = $query_deduct->execute(array($row['staff_id'], '2'));
                    $out_deduct = $query_deduct->fetchAll(PDO::FETCH_ASSOC);
                    while ($row_deduct = array_shift($out_deduct)) {
                        $output = 0;
                        //Process Normal deduction
                        if (intval($row_deduct['edType']) == '2') {

                            if (intval($row_deduct['allow_id']) == 50) { //process pension
                                $sql_consolidated = "SELECT allowancetable.`value` FROM allowancetable WHERE allowancetable.allowcode = 1 and grade = '" . $row['GRADE'] . "' and step = '" . $row['STEP'] . "'";
                                $result_consolidated = mysqli_query($connect, $sql_consolidated);
                                $row_consolidated = mysqli_fetch_assoc($result_consolidated);
                                $total_rowsConsolidated = mysqli_num_rows($result_consolidated);

                                $sql_pensionRate = "SELECT (pension.PENSON/100) as rate FROM pension WHERE grade = '" . $row['GRADE'] . "' and step = '" . $row['STEP'] . "'";
                                $result_pensionRate = mysqli_query($connect, $sql_pensionRate);
                                $row_pensionRate = mysqli_fetch_assoc($result_pensionRate);
                                $total_pensionRate = mysqli_num_rows($result_pensionRate);

                                $output = ceil($row_consolidated['value'] * $row_pensionRate['rate']);
                                //echo $output;

                            } else {
                                $output = $row_deduct['value'];
                            }
                            //Save into db
                            //echo $row_allow['allow_id'].' '.$row_allow['edDesc'].' '.number_format($output).'<br>';
                            try {
                                $recordtime = date('Y-m-d H:i:s');
                                $query = 'INSERT INTO tbl_master (staff_id, allow_id, deduc, type, period,editTime,userID) VALUES (?,?,?,?,?,?,?)';
                                $conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $output, '2',  $period, $recordtime, $_SESSION['SESS_MEMBER_ID']));
                                //delete temp deduction
                                if (intval($row_deduct['counter']) > 0) {
                                    echo 'Normal deduction counter check';
                                    $running_counter = intval($row_deduct['running_counter']);
                                    $running_counter = intval($row_deduct['running_counter']) + 1;
                                    if (($running_counter) == intval($row_deduct['counter'])) {
                                        echo 'normal deduction counter check';
                                        $query = 'INSERT INTO completedLoan (staff_id,allow_id,period,value,type)VALUES (?,?,?,?,?)';
                                        $conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $period, $output, '2'));
                                        //delete allow once cycle is complete
                                        $sqlDelete = "DELETE FROM allow_deduc WHERE temp_id = '" . $row_deduct['temp_id'] . "'";
                                        $conn->exec($sqlDelete);
                                    } else {
                                        $sqlUpdate = "update allow_deduc set running_counter = '" . $running_counter . "' WHERE temp_id = '" . $row_deduct['temp_id'] . "'";
                                        $conn->exec($sqlUpdate);
                                    }
                                }
                            } catch (PDOException $e) {
                                echo $e->getMessage();
                            }
                        } else if (intval($row_deduct['edType']) == '3') {
                            //Process Union deduction
                            $sql_numberOfRows = "SELECT deductiontable.ded_id, deductiontable.allowcode, deductiontable.grade, deductiontable.step, deductiontable.`value`, deductiontable.category, deductiontable.ratetype, deductiontable.percentage FROM deductiontable WHERE allowcode = '" . $row_deduct['allow_id'] . "'";
                            $result_numberOfRows = mysqli_query($connect, $sql_numberOfRows);
                            $row_numberOfRows = mysqli_fetch_assoc($result_numberOfRows);
                            $total_rows = mysqli_num_rows($result_numberOfRows);
                            if ($total_rows == 1) {
                                if ($row_numberOfRows['ratetype'] == 1) {
                                    $output = $row_numberOfRows['value'];
                                } else {
                                    $sql_consolidated = "SELECT allowancetable.allow_id, allowancetable.allowcode, allowancetable.grade, allowancetable.step, allowancetable.`value`, allowancetable.category, allowancetable.ratetype, allowancetable.percentage FROM allowancetable WHERE allowancetable.allowcode = 1 and grade = '" . $row['GRADE'] . "' and step = '" . $row['STEP'] . "'";
                                    $result_consolidated = mysqli_query($connect, $sql_consolidated);
                                    $row_consolidated = mysqli_fetch_assoc($result_consolidated);
                                    $total_rowsConsolidated = mysqli_num_rows($result_consolidated);
                                    $output = ($row_numberOfRows['percentage'] * $row_consolidated['value']) / 100;
                                }
                                // if deduction is found in the table
                            } else if ($total_rows > 1) {
                                $sql_mulitple = "SELECT deductiontable.ded_id, deductiontable.allowcode, deductiontable.grade, deductiontable.step, deductiontable.`value`, deductiontable.category, deductiontable.ratetype, deductiontable.percentage FROM deductiontable WHERE allowcode = '" . $row_deduct['allow_id'] . "' and grade = '" . $row['GRADE'] . "'";
                                $result_mulitple = mysqli_query($connect, $sql_mulitple);
                                $row_mulitple = mysqli_fetch_assoc($result_mulitple);
                                $total_mulitple = mysqli_num_rows($result_mulitple);
                                if ($total_mulitple > 0) {
                                    if ($row_mulitple['ratetype'] == 1) {
                                        $output = $row_mulitple['value'];
                                        //echo $sql_mulitple ;
                                    } else {
                                        $sql_consolidated = "SELECT allowancetable.allow_id, allowancetable.allowcode, allowancetable.grade, allowancetable.step, allowancetable.`value`, allowancetable.category, allowancetable.ratetype, allowancetable.percentage FROM allowancetable WHERE allowancetable.allowcode = 1 and grade = '" . $row['GRADE'] . "' and step = '" . $row['STEP'] . "'";
                                        $result_consolidated = mysqli_query($connect, $sql_consolidated);
                                        $row_consolidated = mysqli_fetch_assoc($result_consolidated);
                                        $total_rowsConsolidated = mysqli_num_rows($result_consolidated);
                                        $output = ceil(($row_mulitple['percentage'] * $row_consolidated['value']) / 100);
                                    }
                                } else {
                                    $output = $row_deduct['value'];
                                }
                            } else {
                                $output = $row_deduct['value'];
                            }
                            echo $row_allow['allow_id'] . ' ' . $row_allow['edDesc'] . ' ' . number_format($output) . '<br>';
                            try {
                                $recordtime = date('Y-m-d H:i:s');
                                $query = 'INSERT INTO tbl_master (staff_id, allow_id, deduc, type, period,editTime,userID) VALUES (?,?,?,?,?,?,?)';
                                $conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $output, '2',  $period, $recordtime, $_SESSION['SESS_MEMBER_ID']));
                                //process temp allow id

                                if (intval($row_deduct['counter']) > 0) {
                                    echo 'union deduction counter check';
                                    $running_counter = intval($row_deduct['running_counter']);
                                    $running_counter = intval($row_deduct['running_counter']) + 1;
                                    if (($running_counter) == intval($row_deduct['counter'])) {
                                        //delete allow once cycle is complete
                                        $query = 'INSERT INTO completedLoan (staff_id,allow_id,period,value,type)VALUES (?,?,?,?,?)';
                                        $conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $period, $output, '2'));

                                        $sqlDelete = "DELETE FROM allow_deduc WHERE temp_id = '" . $row_deduct['temp_id'] . "'";
                                        $conn->exec($sqlDelete);
                                    } else {
                                        $sqlUpdate = "update allow_deduc set running_counter = '" . $running_counter . "' WHERE temp_id = '" . $row_deduct['temp_id'] . "'";
                                        $conn->exec($sqlUpdate);
                                    }
                                }
                            } catch (PDOException $e) {
                                echo $e->getMessage();
                            }
                            //process loan deduction
                        } else if (intval($row_deduct['edType']) == '4') {
                            $sql_loancheck = "SELECT tbl_earning_deduction_type.edType FROM tbl_earning_deduction_type INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.edType = tbl_earning_deduction_type.edType WHERE tbl_earning_deduction.ed_id = '" . $row_deduct['allow_id'] . "' and tbl_earning_deduction_type.edType = 4";
                            $result_loancheck = mysqli_query($connect, $sql_loancheck);
                            $row_loan = mysqli_fetch_assoc($result_loancheck);
                            $total_loancheck = mysqli_num_rows($result_loancheck);
                            //echo 'sql check ='. $sql_loancheck. '<br>';
                            //echo 'loan check ='. $total_loancheck. '<br>';
                            if ($total_loancheck > 0) {

                                $sql_loan = "SELECT tbl_debt.loan_id, tbl_debt.staff_id,tbl_debt.allow_id, SUM(ifnull(tbl_debt.principal,0))+SUM(ifnull(tbl_debt.interest,0)) as loan FROM tbl_debt WHERE staff_id = '" . $row['staff_id'] . "' AND allow_id = '" . $row_deduct['allow_id'] . "'";
                                $result_loan = mysqli_query($connect, $sql_loan);
                                $row_loan = mysqli_fetch_assoc($result_loan);
                                $total_loan = mysqli_num_rows($result_loan);

                                $sql_repayment = "SELECT tbl_repayment.staff_id, tbl_repayment.allow_id, SUM(ifnull(tbl_repayment.value,0)) as repayment FROM tbl_repayment WHERE staff_id = '" . $row['staff_id'] . "' and allow_id = '" . $row_deduct['allow_id'] . "'";
                                $result_repayment = mysqli_query($connect, $sql_repayment);
                                $row_repayment = mysqli_fetch_assoc($result_repayment);
                                $total_repayment = mysqli_num_rows($result_repayment);

                                $balance = $row_loan['loan'] - $row_repayment['repayment'];
                                //print number_format($balance);
                                //echo $sql_repayment ;
                                if (floatval($balance) > floatval($row_deduct['value'])) {
                                    $output = floatval($row_deduct['value']);
                                    //add payment
                                    try {
                                        $recordtime = date('Y-m-d H:i:s');
                                        $query_repayment = 'INSERT INTO tbl_repayment (staff_id, allow_id, value,  period,userID,editTime) VALUES (?,?,?,?,?,?)';
                                        $conn->prepare($query_repayment)->execute(array($row['staff_id'], $row_deduct['allow_id'], $output, $period, $period, $recordtime));
                                    } catch (PDOException $e) {
                                        echo $e->getMessage();
                                    }
                                } else if (floatval($balance) <= floatval($row_deduct['value'])) {
                                    $output = floatval($balance);
                                    try {
                                        echo 'loan deduction counter check';
                                        $recordtime = date('Y-m-d H:i:s');

                                        $query = 'INSERT INTO completedLoan (staff_id,allow_id,period,value,type)VALUES (?,?,?,?,?)';
                                        $conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $period, $output, '2'));

                                        $query_repayment = 'INSERT INTO tbl_repayment (staff_id, allow_id, value,  period,userID,editTime) VALUES (?,?,?,?,?,?)';
                                        $conn->prepare($query_repayment)->execute(array($row['staff_id'], $row_deduct['allow_id'], $output, $period, $period, $recordtime));
                                        //delete loan id
                                        $query = 'DELETE FROM allow_deduc where allow_id = ? and staff_id = ?';
                                        $conn->prepare($query)->execute(array($row_deduct['allow_id'], $row['staff_id']));
                                    } catch (PDOException $e) {
                                        echo $e->getMessage();
                                    }
                                }
                            }
                            echo $row_deduct['allow_id'] . ' ' . $row_deduct['edDesc'] . ' ' . number_format($output) . '<br>';
                            try {




                                $recordtime = date('Y-m-d H:i:s');
                                $query = 'INSERT INTO tbl_master (staff_id, allow_id, deduc, type, period,editTime,userID) VALUES (?,?,?,?,?,?,?)';
                                $conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $output, '2',  $period, $recordtime, $_SESSION['SESS_MEMBER_ID']));
                            } catch (PDOException $e) {
                                echo $e->getMessage();
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                echo $e->getMessage();
            }

            //set openview status
            $statuschange = $conn->prepare('UPDATE payperiods SET payrollRun = ? WHERE periodId = ?');
            $perres = $statuschange->execute(array('1', $period));

            //exit ($_SESSION['companyid'] . 'Entire Employee Run');
            echo 0;
        }
        break;


    case 'addNewLeave':
        //check for existing same employee number

        $empnumber = htmlspecialchars(trim($_POST['empnumber']), ENT_QUOTES, 'UTF-8');
        $leavetype = htmlspecialchars(trim($_POST['leavetype']), ENT_QUOTES, 'UTF-8');
        $startleave = date('Y-m-d', strtotime(htmlspecialchars(trim($_POST['startleave']), ENT_QUOTES, 'UTF-8')));
        $day1 = strtotime(htmlspecialchars(trim($_POST['startleave']), ENT_QUOTES, 'UTF-8'));
        $endleave = date('Y-m-d', strtotime(htmlspecialchars(trim($_POST['endleave']), ENT_QUOTES, 'UTF-8')));
        $day2 = strtotime(htmlspecialchars(trim($_POST['endleave']), ENT_QUOTES, 'UTF-8'));

        $days_diff = $day2 - $day1;
        $numofdays = date('d', $days_diff);

        $currdate = date('Y-m-d');
        //validate for empty mandatory fields

        try {
            //check for same leave request for same staffer
            $leavequery = $conn->prepare('SELECT * FROM hr_leave_requests WHERE employeeNumber = ? AND leaveType = ? AND status = ? OR status = ? AND active = ?');
            $res = $leavequery->execute(array($empnumber, $leavetype, '1', '2', '1'));

            if ($row = $leavequery->fetch()) {
                $_SESSION['msg'] = $msg = "Active / Pending similar leave type for this employee. Please review all approved or pending leave requests.";
                $_SESSION['alertcolor'] = 'danger';
                header('Location: ' . $source);
            } else {
                $query = 'INSERT INTO hr_leave_requests (employeeNumber, leaveType, fromDate, toDate, applicationDate, numberOfDays, status) VALUES (?,?,?,?,?,?,?)';

                $conn->prepare($query)->execute(array($empnumber, $leavetype, $startleave, $endleave, $currdate, $numofdays, '2'));

                $_SESSION['msg'] = $msg = "New Leave Successfully added.";
                $_SESSION['alertcolor'] = 'success';
                header('Location: ' . $source);
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;


    case 'manageLeave':

        $empalternumber = htmlspecialchars(trim($_POST['empalternumber']), ENT_QUOTES, 'UTF-8');
        $empalterid = htmlspecialchars(trim($_POST['empalterid']), ENT_QUOTES, 'UTF-8');
        $leaveaction = htmlspecialchars(trim($_POST['leaveaction']), ENT_QUOTES, 'UTF-8');
        //exit($empalternumber . ",". $empalterid. "," .$leaveaction);

        try {

            $query = ('UPDATE hr_leave_requests SET status = ? WHERE id = ? AND employeeNumber = ?');
            $conn->prepare($query)->execute(array($leaveaction, $empalterid, $empalternumber));

            $_SESSION['msg'] = $msg = "Leave status successfully amended";
            $_SESSION['alertcolor'] = 'success';
            header('Location: ' . $source);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;


    case 'deactivateEmployee':
        $empalterid = filter_var($_POST['empalterid']);
        $empalternumber = filter_var($_POST['empalternumber']);
        $deactivate = filter_var($_POST['deactivate']);
        $editDate = date('Y-m-d H:i:s');

        $query = 'UPDATE tblemployees SET Status = ? WHERE CoopID = ?';
        $conn->prepare($query)->execute(array($deactivate, $empalterid));

        //	$deactivatequery = 'INSERT INTO hr_exited_employees (employeeId, exitDate, exitReason, editTime, userEditorId) VALUES (?,?,?,?,?)';
        //	$conn->prepare($deactivatequery)->execute (array($empalternumber, $exitdate, $exitreason, $editDate, $_SESSION['user']));

        $_SESSION['msg'] = $msg = "Employee successfully deactivated.";
        $_SESSION['alertcolor'] = 'success';
        header('Location: ' . $source);
        break;


    case 'suspendEmployee':
        $empalterid = filter_var($_POST['empalterid'], FILTER_VALIDATE_INT);
        $empalternumber = htmlspecialchars(trim($_POST['empalternumber']), ENT_QUOTES, 'UTF-8');
        $startsuspension = date('Y-m-d', strtotime(htmlspecialchars(trim($_POST['startsuspension']), ENT_QUOTES, 'UTF-8')));
        $endsuspension = date('Y-m-d', strtotime(htmlspecialchars(trim($_POST['endsuspension']), ENT_QUOTES, 'UTF-8')));
        $suspendreason = htmlspecialchars(trim($_POST['suspendreason']), ENT_QUOTES, 'UTF-8');
        $editDate = date('Y-m-d H:i:s');

        try {

            $susquery = $conn->prepare('SELECT * FROM employees WHERE empNumber = ? AND companyId = ? AND active = ? AND suspended = ?');
            $fin = $susquery->execute(array($empalternumber, $_SESSION['companyid'], '1', '1'));

            if ($row = $susquery->fetch()) {
                $_SESSION['msg'] = "Selected employee currently on suspension.";
                $_SESSION['alertcolor'] = "danger";
                header('Location: ' . $source);
            } else {
                //exit($empalternumber . ", " . $empalterid . ", " . $exitdate . ", " . $exitreason);
                $query = 'UPDATE employees SET suspended = ? WHERE empNumber = ? AND companyId = ? AND active = ? AND suspended = ?';
                $conn->prepare($query)->execute(array('1', $empalternumber, $_SESSION['companyid'], '1', '0'));

                $deactivatequery = 'INSERT INTO employee_suspensions (employeeId, suspendStartDate, suspendEndDate, suspenReason, editTime, userEditorId) VALUES (?,?,?,?,?,?)';
                $conn->prepare($deactivatequery)->execute(array($empalternumber, $startsuspension, $endsuspension, $suspendreason, $editDate, $_SESSION['user']));

                $_SESSION['msg'] = $msg = "Employee successfully suspended.";
                $_SESSION['alertcolor'] = 'success';
                header('Location: ' . $source);
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;


    case 'editemployeeearning':
        //exit('Edit Employee Earning');
        $empedit = htmlspecialchars(trim($_POST['empeditnum']), ENT_QUOTES, 'UTF-8');
        $edited = filter_var($_POST['edited'], FILTER_VALIDATE_INT);
        $editname = filter_var($_POST['editname'], FILTER_VALIDATE_INT);
        $editvalue = filter_var($_POST['editvalue'], FILTER_VALIDATE_INT);
        $grossquery = 'UPDATE employee_earnings_deductions SET amount = ? WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ?';
        $conn->prepare($grossquery)->execute(array($editvalue, $empedit, $_SESSION['companyid'], $edited,  $_SESSION['currentactiveperiod'], '1'));

        $_SESSION['msg'] = 'Successfully Edited Earning / Deduction';
        $_SESSION['alertcolor'] = 'success';
        header('Location: ' . $source);
        break;


    case 'deactivateEd':
        $empeditnum = htmlspecialchars(trim($_GET['empeditnum']), ENT_QUOTES, 'UTF-8');
        //$edited = filter_var($_POST['edited'], FILTER_VALIDATE_INT);
        //exit($empeditnum . " " . $edited . " " . $_SESSION['currentactiveperiod');
        try {

            $query = $conn->prepare('SELECT * FROM allow_deduc WHERE temp_id = ?');
            $res = $query->execute(array($empeditnum));
            $existtrans = $query->fetch();

            if ($existtrans) {

                $query = 'DELETE FROM allow_deduc where temp_id = ?';
            }
            $conn->prepare($query)->execute(array($empeditnum));

            $_SESSION['msg'] = $msg = "E/D successfully deactivated.";
            $_SESSION['alertcolor'] = 'success';
            header('Location: ' . $source);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;


    case 'batchprocess':
        exit('Batch Process');
        break;


    case 'resetpass':
        //exit('reset');

        $title = "Password Reset";
        $resetemail = filter_var((filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)), FILTER_VALIDATE_EMAIL);

        //check if account exists with emailaddress
        $query = $conn->prepare('SELECT emailAddress FROM users WHERE emailAddress = ? AND active = ?');
        $fin = $query->execute(array($resetemail, '1'));

        if ($row = $query->fetch()) {

            //Generate update token
            $reset_token = bin2hex(openssl_random_pseudo_bytes(32));

            //write token to token table and assign validity state, creation timestamp
            $tokenrecordtime = date('Y-m-d H:i:s');

            //check for any previous tokens and invalidate
            $tokquery = $conn->prepare('SELECT * FROM reset_token WHERE userEmail = ? AND valid = ? AND type = ?');
            $fin = $tokquery->execute(array($resetemail, '1', '1'));

            if ($row = $tokquery->fetch()) {
                $upquery = 'UPDATE reset_token SET valid = ? WHERE userEmail = ? AND valid = ?';
                $conn->prepare($upquery)->execute(array('0', $resetemail, '1'));
            }

            $tokenquery = 'INSERT INTO reset_token (userEmail, token, creationTime, valid, type) VALUES (?,?,?,?,?)';
            $conn->prepare($tokenquery)->execute(array($resetemail, $reset_token, $tokenrecordtime, '1', '1'));

            //exit($resetemail . " " . $reset_token);

            $sendmessage = "You've recently asked to reset the password for this Redsphere Payroll account: " . $resetemail . "<br /><br />To update your password, click the link below:<br /><br /> " . $sysurl . 'password_reset.php?token=' . $reset_token;
            //generate reset cdde and append to email submitted

            require 'phpmailer/PHPMailerAutoload.php';

            $mail = new PHPMailer;

            $mail->SMTPDebug = 3;                               // Enable verbose debug output

            $mail->isSMTP();                                      // Set mailer to use SMTP
            $mail->Host = 'smtp.zoho.com';  // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = 'noreply@redsphere.co.ke';                 // SMTP username
            $mail->Password = 'redsphere_2017***';                           // SMTP password
            $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
            $mail->Port = 587;                                    // TCP port to connect to

            $mail->setFrom('noreply@redsphere.co.ke', 'Redsphere Payroll');
            $mail->addAddress($resetemail, 'Redsphere Payroll');     // Add a recipient
            //$mail->addAddress('ellen@example.com');               // Name is optional
            $mail->addReplyTo('info@example.com', 'Information');
            $mail->addCC('fgesora@gmail.com');
            //$mail->addBCC('bcc@example.com');

            //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
            //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
            $mail->isHTML(true);                                  // Set email format to HTML

            $mail->Subject = $title;
            $mail->Body    = $sendmessage;
            $mail->AltBody = $sendmessage;

            if (!$mail->send()) {
                //exit($mail->ErrorInfo);
                echo 'Mailer Error: ' . $mail->ErrorInfo;
                $_SESSION['msg'] = "Failed. Error sending email.";
                $_SESSION['alertcolor'] = "danger";
                header("Location: " . $source);
            } else {
                $status = "Success";
                $_SESSION['msg'] = "If there is an account associated with this email address, an email has been sent to reset your password.";
                $_SESSION['alertcolor'] = "success";
                header("Location: " . $source);
            }
        } else {

            $_SESSION['msg'] = "If there is an account associated with this email address, an email has been sent to reset your password.";
            $_SESSION['alertcolor'] = "success";
            header("Location: " . $source);
        }

        break;



    case 'deactivateuser':
        $thisuser = htmlspecialchars(trim($_POST['thisuser']), ENT_QUOTES, 'UTF-8');
        //$useremail = htmlspecialchars(trim($_POST['useremail']), ENT_QUOTES, 'UTF-8');
        $status = htmlspecialchars(trim($_POST['status']), ENT_QUOTES, 'UTF-8');
        if ($status == 'In-Active') {
            $status = 'Active';
        } else {
            $status = 'In-Active';
        }

        try {
            $query = 'UPDATE tblusers SET Status = ? WHERE user_id = ?';
            $conn->prepare($query)->execute(array($status, $thisuser));

            $_SESSION['msg'] = $msg = "User successfully deactivated.";
            $_SESSION['alertcolor'] = 'success';
            header('Location: ' . $source);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;



    case 'logout':
        $_SESSION['logged_in'] = '0';
        unset($_SESSION['user']);
        unset($_SESSION['email']);
        unset($_SESSION['first_name']);
        unset($_SESSION['last_name']);
        unset($_SESSION['companyid']);
        unset($_SESSION['emptrack']);
        unset($_SESSION['currentactiveperiod']);
        unset($_SESSION['activeperiodDescription']);
        unset($_SESSION['msg']);
        unset($_SESSION['alertcolor']);
        unset($_SESSION['empDataTrack']);
        unset($_SESSION['emptNumTack']);

        if (isset($_SESSION['leavestate'])) {
            unset($_SESSION['leavestate']);
        }

        if (isset($_SESSION['periodstatuschange'])) {
            unset($_SESSION['periodstatuschange']);
        }

        //reset global openview status
        $statuschange = $conn->prepare('UPDATE payperiods SET openview = ? ');
        $perres = $statuschange->execute(array('0'));

        $_SESSION['msg'] = $msg = "Successfully logged out";
        $_SESSION['alertcolor'] = $type = "success";
        $page = "../../index.php";
        header('Location: ' . $page);
        break;


    default:
        exit('Unexpected route. Please contact administrator.');
        break;
}