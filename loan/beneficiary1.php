<?php require_once('Connections/coopSky.php'); ?>
<?php
if (!function_exists("GetSQLValueString")) {
function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  if (PHP_VERSION < 6) {
    $theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue;
  }

  $theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);

  switch ($theType) {
    case "text":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;    
    case "long":
    case "int":
      $theValue = ($theValue != "") ? intval($theValue) : "NULL";
      break;
    case "double":
      $theValue = ($theValue != "") ? doubleval($theValue) : "NULL";
      break;
    case "date":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;
    case "defined":
      $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
      break;
  }
  return $theValue;
}
}

if ((isset($_GET['Session_batch']))){

session_start();
//session_register('Batch');
//session_register('BatchId');
$_SESSION['Batch'] =      $_GET['Session_batch'];
//$_SESSION['BatchId'] = $_GET['Batchid_session'];

}else{

header("Location:index.php");
}

function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  $theValue = (!get_magic_quotes_gpc()) ? addslashes($theValue) : $theValue;

  switch ($theType) {
    case "text":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;    
    case "long":
    case "int":
      $theValue = ($theValue != "") ? intval($theValue) : "NULL";
      break;
    case "double":
      $theValue = ($theValue != "") ? "'" . doubleval($theValue) . "'" : "NULL";
      break;
    case "date":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;
    case "defined":
      $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
      break;
  }
  return $theValue;
}

if ((isset($_GET['beneficiaryCode'])) && ($_GET['Batch'] != "")) {
  $deleteSQL = sprintf("DELETE FROM excel WHERE beneficiaryCode=%s and Batch = %s",
                       GetSQLValueString($_GET['beneficiaryCode'], "text"),
					   GetSQLValueString($_GET['Batch'], "text"));

  mysql_select_db($database_coopSky, $coopSky);
  $Result1 = mysql_query($deleteSQL, $coopSky) or die(mysql_error());
}

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "eduEntry")) {
  $updateSQL = sprintf("UPDATE excel SET BeneficiaryName=%s, AccountNumber=%s, CBNCode=%s, Bank=%s, Narration=%s, Amount=%s, Batch=%s WHERE BeneficiaryCode=%s",
                       GetSQLValueString($_POST['CoopName'], "text"),
                       GetSQLValueString($_POST['txtBankAccountNo'], "text"),
                       GetSQLValueString($_POST['txtbankcode'], "text"),
                       GetSQLValueString($_POST['txtBankName'], "text"),
                       GetSQLValueString($_POST['txNarration'], "text"),
                       GetSQLValueString($_POST['txtAmount'], "double"),
                       GetSQLValueString($_POST['Batch'], "text"),
                       GetSQLValueString($_POST['coopid'], "text"));

  mysql_select_db($database_coopSky, $coopSky);
  $Result1 = mysql_query($updateSQL, $coopSky) or die(mysql_error());
}

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "eduEntry")) {

$_POST['txtAmount'] = str_replace(",","",$_POST['txtAmount']);
     $insertSQL = sprintf("INSERT INTO excel (BeneficiaryCode, BeneficiaryName, AccountNumber, CBNCode, Narration, Amount, Batch, Bank) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)",
    
  
                       GetSQLValueString($_POST['txtCoopid'], "text"),
                       GetSQLValueString($_POST['CoopName'], "text"),
                       GetSQLValueString($_POST['txtBankAccountNo'], "text"),
                       GetSQLValueString($_POST['txtbankcode'], "text"),
                       GetSQLValueString($_POST['txNarration'], "text"),
                       GetSQLValueString($_POST['txtAmount'], "double"),
					   GetSQLValueString($_POST['Batch'], "text"),
					   GetSQLValueString($_POST['txtBankName'], "text"));
  mysql_select_db($database_coopSky, $coopSky);
  $Result1 = mysql_query($insertSQL, $coopSky) or die(mysql_error());

  $insertGoTo = "beneficiary.php";
  if (isset($_SERVER['QUERY_STRING'])) {
    $insertGoTo .= (strpos($insertGoTo, '?')) ? "&" : "?";
    $insertGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $insertGoTo));
  
}

mysql_select_db($database_coopSky, $coopSky);
$query_coopid = "SELECT concat(tblemployees.CoopID, ' - ',tblemployees. lastname, '  ',tblemployees. firstname, '  ', middlename) as coopname, tblemployees.coopid FROM tblemployees";
$coopid = mysql_query($query_coopid, $coopSky) or die(mysql_error());
$row_coopid = mysql_fetch_assoc($coopid);
$totalRows_coopid = mysql_num_rows($coopid);

$colname_excel = "-1";
if (isset($_SESSION['Batch'])) {
  $colname_excel = (get_magic_quotes_gpc()) ? $_SESSION['Batch'] : addslashes($_SESSION['Batch']);
}
mysql_select_db($database_coopSky, $coopSky);
$query_excel = sprintf("SELECT concat(excel.Narration,' ',excel.PaymentRefID) as PaymentReference, excel.BeneficiaryName, excel.AccountNumber, excel.AccountType, excel.CBNCode, excel.IsCashCard, excel.Narration, excel.Amount, excel.EMailAddress, excel.NGN, excel.BeneficiaryCode, excel.batch,Bank FROM excel WHERE batch = '%s'", $colname_excel);
$excel = mysql_query($query_excel, $coopSky) or die(mysql_error());
$row_excel = mysql_fetch_assoc($excel);
$totalRows_excel = mysql_num_rows($excel);

$colname_BatchSum = "-1";
if (isset($_SESSION['Batch'])) {
  $colname_BatchSum = (get_magic_quotes_gpc()) ? $_SESSION['Batch'] : addslashes($_SESSION['Batch']);
}
mysql_select_db($database_coopSky, $coopSky);
$query_BatchSum = sprintf("SELECT Sum(excel.Amount) as Sum  FROM excel where Batch ='%s'", $colname_BatchSum);
$BatchSum = mysql_query($query_BatchSum, $coopSky) or die(mysql_error());
$row_BatchSum = mysql_fetch_assoc($BatchSum);
$totalRows_BatchSum = mysql_num_rows($BatchSum);

$col_coopid_edit_query = "-1";
if (isset($_POST['coopid'])) {
  $col_coopid_edit_query = $_get['coopid'];
}
$col_batch_edit_query = "-1";
if (isset($_POST['batch'])) {
  $col_batch_edit_query = $_Postp['batch'];
}
mysql_select_db($database_coopSky, $coopSky);
$query_edit_query = sprintf("SELECT excel.PaymentRefID, excel.BeneficiaryCode, excel.BeneficiaryName, excel.AccountNumber, excel.AccountType, excel.CBNCode, excel.Bank, excel.Narration, excel.Amount, excel.Batch FROM excel where excel.BeneficiaryCode = %s and Batch = %s", GetSQLValueString($col_coopid_edit_query, "text"),GetSQLValueString($col_batch_edit_query, "text"));
$edit_query = mysql_query($query_edit_query, $coopSky) or die(mysql_error());
$row_edit_query = mysql_fetch_assoc($edit_query);
$totalRows_edit_query = mysql_num_rows($edit_query);

//session_start();
//if (!isset($_SESSION['UserID'])){
//header("Location:index.php");}elseif (!isset($_GET['action'])){
//header("Location:mycv.php");} else{


 ?>
<?php

?>
<html>

<head>


    <title>Add Beneficary to Batch</title>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <!--Fireworks MX 2004 Dreamweaver MX 2004 target.  Created Sat Dec 04 17:23:24 GMT+0100 2004-->
    <link href="education_files/oouth.css" rel="stylesheet" type="text/css">
    <script language="JavaScript" src="education_files/general.js" type="text/javascript"></script>
    <script type="text/javascript" src="education_files/popcalendar.js"></script>
    <script language="javascript" type="text/javascript">
    // Roshan's Ajax dropdown code with php
    // This notice must stay intact for legal use
    // Copyright reserved to Roshan Bhattarai - nepaliboy007@yahoo.com
    // If you have any problem contact me at http://roshanbh.com.np
    function getXMLHTTP() { //fuction to return the xml http object
        var xmlhttp = false;
        try {
            xmlhttp = new XMLHttpRequest();
        } catch (e) {
            try {
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            } catch (e) {
                try {
                    xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
                } catch (e1) {
                    xmlhttp = false;
                }
            }
        }

        return xmlhttp;
    }

    function getName(coopid) {

        var strURL = "name.php?id=" + coopid;
        var req = getXMLHTTP();

        if (req) {

            req.onreadystatechange = function() {
                if (req.readyState == 4) {
                    // only if "OK"
                    if (req.status == 200) {
                        document.getElementById('txtCoopName').innerHTML = req.responseText;
                    } else {
                        alert("There was a problem while using XMLHTTP:\n" + req.statusText);
                    }
                }
            }
            req.open("GET", strURL, true);
            req.send(null);
        }
    }


    function deleteBeneficiary(coopid) {

        if (confirm("Are you sure your want to delete selected item(s)")) {

            var ln = 0;
            var checkbox = document.getElementsByName('coop_id');
            var i;
            for (i = 0; i < checkbox.length; i++) {

                if (checkbox[i].checked) {

                    //alert(checkbox[i].value);
                    //alert("Test1");
                    ln++;
                    //if (ln == 1){

                    var batchcode = document.forms[1].batch.value;
                    var coopid = checkbox[i].value;

                    var strURL = "delete.php?beneficiaryCode=" + coopid + "&Batch=" + batchcode;
                    var req = getXMLHTTP();

                    if (req) {

                        req.onreadystatechange = function() {
                            if (req.readyState == 4) {
                                // only if "OK"
                                if (req.status == 200) {
                                    window.location.href = "beneficiary.php?Session_batch=" + batchcode;
                                    //alert ("Delete Successful"); //document.getElementById('BankName').innerHTML=req.responseText;						

                                } else {
                                    alert("There was a problem while using XMLHTTP:\n" + req.statusText);
                                }
                            }
                        }
                        req.open("GET", strURL, true);
                        req.send(null);
                    }
                }
            }
            if (ln > 0) {
                alert("Selected item(s) Deleted Successfully");
            }
            if (ln == 0) {
                alert("Pls Select at least one item(s)  to Delete");
            }
        }
    }

    function getBankName(coopid) {

        var strURL = "bankName.php?id=" + coopid;
        var req = getXMLHTTP();

        if (req) {

            req.onreadystatechange = function() {
                if (req.readyState == 4) {
                    // only if "OK"
                    if (req.status == 200) {
                        document.getElementById('BankName').innerHTML = req.responseText;
                    } else {
                        alert("There was a problem while using XMLHTTP:\n" + req.statusText);
                    }
                }
            }
            req.open("GET", strURL, true);
            req.send(null);
        }
    }

    function getAccountNo(BankCode) {
        var strURL = "accountNo.php?id=" + BankCode;
        var req = getXMLHTTP();

        if (req) {

            req.onreadystatechange = function() {
                if (req.readyState == 4) {
                    // only if "OK"
                    if (req.status == 200) {
                        document.getElementById('BankAccountNo').innerHTML = req.responseText;
                    } else {
                        alert("There was a problem while using XMLHTTP:\n" + req.statusText);
                    }
                }
            }
            req.open("GET", strURL, true);
            req.send(null);
        }

    }

    function getBankCode(id) {
        var strURL = "bankCode.php?id=" + id;
        var req = getXMLHTTP();

        if (req) {

            req.onreadystatechange = function() {
                if (req.readyState == 4) {
                    // only if "OK"
                    if (req.status == 200) {
                        document.getElementById('bankcode').innerHTML = req.responseText;
                    } else {
                        alert("There was a problem while using XMLHTTP:\n" + req.statusText);
                    }
                }
            }
            req.open("GET", strURL, true);
            req.send(null);
        }

    }
    </script>
    <script language="javascript" type="text/javascript">
    function commaFormat(inputString) {
        inputString = inputString.toString();
        var decimalPart = "";
        if (inputString.indexOf('.') != -1) {
            //alert("decimal number");
            inputString = inputString.split(".");
            decimalPart = "." + inputString[1];
            inputString = inputString[0];
            //alert(inputString);
            //alert(decimalPart);

        }
        var outputString = "";
        var count = 0;
        for (var i = inputString.length - 1; i >= 0 && inputString.charAt(i) != '-'; i--) {
            //alert("inside for" + inputString.charAt(i) + "and count=" + count + " and outputString=" + outputString);
            if (count == 3) {
                outputString += ",";
                count = 0;
            }
            outputString += inputString.charAt(i);
            count++;
        }
        if (inputString.charAt(0) == '-') {
            outputString += "-";
        }
        //alert(outputString);
        //alert(outputString.split("").reverse().join(""));
        return outputString.split("").reverse().join("") + decimalPart;

    }
    </script>
    <script type='text/javascript'>
    function formatNumber(myElement) { // JavaScript function to insert thousand separators
        var myVal = ""; // The number part
        var myDec = ""; // The digits pars
        // Splitting the value in parts using a dot as decimal separator
        var parts = myElement.value.toString().split(".");
        // Filtering out the trash!
        parts[0] = parts[0].replace(/[^0-9]/g, "");
        // Setting up the decimal part
        if (!parts[1] && myElement.value.indexOf(".") > 1) {
            myDec = ".00"
        }
        if (parts[1]) {
            myDec = "." + parts[1]
        }
        // Adding the thousand separator
        while (parts[0].length > 3) {
            myVal = "'" + parts[0].substr(parts[0].length - 3, parts[0].length) + myVal;
            parts[0] = parts[0].substr(0, parts[0].length - 3)
        }
        myElement.value = parts[0] + myVal + myDec;
    }
    </script>

    <script language="JavaScript">
    <!--
    /*
     * convert to two digits and currency format
     */
    function number_format(number, decimals, dec_point, thousands_sep) {
        // http://kevin.vanzonneveld.net
        // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
        // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +     bugfix by: Michael White (http://getsprink.com)
        // +     bugfix by: Benjamin Lupton
        // +     bugfix by: Allan Jensen (http://www.winternet.no)
        // +    revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
        // +     bugfix by: Howard Yeend
        // +    revised by: Luke Smith (http://lucassmith.name)
        // +     bugfix by: Diogo Resende
        // +     bugfix by: Rival
        // +      input by: Kheang Hok Chin (http://www.distantia.ca/)
        // +   improved by: davook
        // +   improved by: Brett Zamir (http://brett-zamir.me)
        // +      input by: Jay Klehr
        // +   improved by: Brett Zamir (http://brett-zamir.me)
        // +      input by: Amir Habibi (http://www.residence-mixte.com/)
        // +     bugfix by: Brett Zamir (http://brett-zamir.me)
        // +   improved by: Theriault
        // +      input by: Amirouche
        // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // *     example 1: number_format(1234.56);
        // *     returns 1: '1,235'
        // *     example 2: number_format(1234.56, 2, ',', ' ');
        // *     returns 2: '1 234,56'
        // *     example 3: number_format(1234.5678, 2, '.', '');
        // *     returns 3: '1234.57'
        // *     example 4: number_format(67, 2, ',', '.');
        // *     returns 4: '67,00'
        // *     example 5: number_format(1000);
        // *     returns 5: '1,000'
        // *     example 6: number_format(67.311, 2);
        // *     returns 6: '67.31'
        // *     example 7: number_format(1000.55, 1);
        // *     returns 7: '1,000.6'
        // *     example 8: number_format(67000, 5, ',', '.');
        // *     returns 8: '67.000,00000'
        // *     example 9: number_format(0.9, 0);
        // *     returns 9: '1'
        // *    example 10: number_format('1.20', 2);
        // *    returns 10: '1.20'
        // *    example 11: number_format('1.20', 4);
        // *    returns 11: '1.2000'
        // *    example 12: number_format('1.2000', 3);
        // *    returns 12: '1.200'
        // *    example 13: number_format('1 000,50', 2, '.', ' ');
        // *    returns 13: '100 050.00'
        // Strip all characters but numerical ones.
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function(n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        // Fix for IE parseFloat(0.55).toFixed(0) = 0;
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    } <
    /script> <
    script >
        function clearBox() {
            document.forms[0].txtBankAccountNo.value = ""
            document.forms[0].txtAmount.value = ""
            document.forms[0].txtBankName.value = ""
            document.forms[0].txNarration.value = ""
            document.forms[0].txtbankcode.value = ""
        } <
        /script> <
    script language = "JavaScript"
    type = "text/JavaScript" >
        <
        !--

    function GP_popupConfirmMsg(msg) { //v1.0
        document.MM_returnValue = confirm(msg);
    }
    //
    -->
    </script>
    <script>
    function hide() {
        document.getElementById("hide").style.display = "none";
    }
    </script>
    <script>
    function hide2() {
        document.getElementById("hide2").style.display = "none";
    }
    </script>
    <script>
    function onSelectedEditAmount(coopid) {

        //alert(document.forms[1].batch.value);
        var ln = 0;
        var checkbox = document.getElementsByName('chkcoopid');
        var i;
        for (i = 0; i < checkbox.length; i++) {

            if (checkbox[i].checked) {
                //alert("Test1");
                ln++;
            }
        }
        if (ln == 1) {

            var m = document.eduEntry.Batch.value

            //alert(m);
            //var m='coop-00004'

            var url = "editAmount.php?batch=" + m + "&coopid=" + coopid;

            //alert(url);

            //document.getElementById("patocpdetails").height="500";

            document.getElementById("opatdetails").width = "500";

            document.getElementById("opatdetails").height = "400";
            document.getElementById("opatdetails").style.overflowY = "hidden";

            document.getElementById("opatdetails").src = url;

            document.getElementById("hide").style.display = "block";



            //makeRequest(url,"patdisplay");
        }
        if (ln > 1) { //alert ("error"); 
            //
            for (i = 0; i < checkbox.length; i++) {

                if (checkbox[i].checked) {
                    //alert("Test1");
                    checkbox[i].checked = false;
                    var morethanone = "Yes";
                    document.getElementById("hide").style.display = "none";

                }
            }
            if (morethanone == "Yes") {
                alert("Select only one item to Edit");
            }

            //alert(ln-1);
            return;
        }
        if (ln == 0) {
            document.getElementById("hide").style.display = "none";
            //alert("Select only one item to Edit");
            return;
        }

    }


    function onSelectedEdit() {

        // alert("dddddddddddddddd"+oForm.value);

        var m = document.eduEntry.txtCoopid.value

        //alert(m);
        //var m='coop-00004'

        var url = "editAccountNo.php?coopid=" + m;

        //alert(url);

        //document.getElementById("patocpdetails").height="500";

        document.getElementById("opatdetails").width = "500";

        document.getElementById("opatdetails").height = "400";
        document.getElementById("opatdetails").style.overflowY = "hidden";

        document.getElementById("opatdetails").src = url;

        document.getElementById("hide").style.display = "block";



        //makeRequest(url,"patdisplay");

    }

    function MM_validateForm() { //v4.0
        if (document.getElementById) {
            var i, p, q, nm, test, num, min, max, errors = '',
                args = MM_validateForm.arguments;
            for (i = 0; i < (args.length - 2); i += 3) {
                test = args[i + 2];
                val = document.getElementById(args[i]);
                if (val) {
                    nm = val.name;
                    if ((val = val.value) != "") {
                        if (test.indexOf('isEmail') != -1) {
                            p = val.indexOf('@');
                            if (p < 1 || p == (val.length - 1)) errors += '- ' + nm +
                                ' must contain an e-mail address.\n';
                        } else if (test != 'R') {
                            num = parseFloat(val);
                            if (isNaN(val)) errors += '- ' + nm + ' must contain a number.\n';
                            if (test.indexOf('inRange') != -1) {
                                p = test.indexOf(':');
                                min = test.substring(8, p);
                                max = test.substring(p + 1);
                                if (num < min || max < num) errors += '- ' + nm + ' must contain a number between ' +
                                    min + ' and ' + max + '.\n';
                            }
                        }
                    } else if (test.charAt(0) == 'R') errors += '- ' + nm + ' is required.\n';
                }
            }
            if (errors) alert('The following error(s) occurred:\n' + errors);
            document.MM_returnValue = (errors == '');
        }
    }
    </script>

</head>

<body checktodate()="" onLoad="">
    <div onClick="bShow=true" id="calendar" style="z-index: 999; position: absolute; visibility: hidden;">
        <table style="border: 1px solid rgb(160, 160, 160); font-size: 11px; font-family: arial;" width="220"
            bgcolor="#ffffff">
            <tbody>
                <tr bgcolor="#0000aa">
                    <td>
                        <table width="218">
                            <tbody>
                                <tr>
                                    <td style="padding: 2px; font-family: arial; font-size: 11px;">
                                        <font color="#ffffff"><b><span id="caption"><span id="spanLeft"
                                                        style="border: 1px solid rgb(51, 102, 255); cursor: pointer;"
                                                        onmouseover='swapImage("changeLeft","left2.gif");this.style.borderColor="#88AAFF";window.status="Click to scroll to previous month. Hold mouse button to scroll automatically."'
                                                        onClick="javascript:decMonth()"
                                                        onmouseout='clearInterval(intervalID1);swapImage("changeLeft","left1.gif");this.style.borderColor="#3366FF";window.status=""'
                                                        onmousedown='clearTimeout(timeoutID1);timeoutID1=setTimeout("StartDecMonth()",500)'
                                                        onMouseUp="clearTimeout(timeoutID1);clearInterval(intervalID1)">&nbsp;<img
                                                            id="changeLeft" src="education_files/left1.gif" width="10"
                                                            border="0" height="11">&nbsp;</span>&nbsp;<span
                                                        id="spanRight"
                                                        style="border: 1px solid rgb(51, 102, 255); cursor: pointer;"
                                                        onmouseover='swapImage("changeRight","right2.gif");this.style.borderColor="#88AAFF";window.status="Click to scroll to next month. Hold mouse button to scroll automatically."'
                                                        onmouseout='clearInterval(intervalID1);swapImage("changeRight","right1.gif");this.style.borderColor="#3366FF";window.status=""'
                                                        onClick="incMonth()"
                                                        onmousedown='clearTimeout(timeoutID1);timeoutID1=setTimeout("StartIncMonth()",500)'
                                                        onMouseUp="clearTimeout(timeoutID1);clearInterval(intervalID1)">&nbsp;<img
                                                            id="changeRight" src="education_files/right1.gif" width="10"
                                                            border="0" height="11">&nbsp;</span>&nbsp;<span
                                                        id="spanMonth"
                                                        style="border: 1px solid rgb(51, 102, 255); cursor: pointer;"
                                                        onmouseover='swapImage("changeMonth","drop2.gif");this.style.borderColor="#88AAFF";window.status="Click to select a month."'
                                                        onmouseout='swapImage("changeMonth","drop1.gif");this.style.borderColor="#3366FF";window.status=""'
                                                        onClick="popUpMonth()"></span>&nbsp;<span id="spanYear"
                                                        style="border: 1px solid rgb(51, 102, 255); cursor: pointer;"
                                                        onmouseover='swapImage("changeYear","drop2.gif");this.style.borderColor="#88AAFF";window.status="Click to select a year."'
                                                        onmouseout='swapImage("changeYear","drop1.gif");this.style.borderColor="#3366FF";window.status=""'
                                                        onClick="popUpYear()"></span>&nbsp;</span></b></font>
                                    </td>
                                    <td align="right"><a href="javascript:hideCalendar()"><img
                                                src="education_files/close.gif" alt="Close the Calendar" width="15"
                                                border="0" height="13"></a></td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px;" bgcolor="#ffffff"><span id="content"></span></td>
                </tr>
                <tr bgcolor="#f0f0f0">
                    <td style="padding: 5px;" align="center"><span id="lblToday">Today is <a
                                onmousemove='window.status="Go To Current Month"' onmouseout='window.status=""'
                                title="Go To Current Month" style="text-decoration: none; color: black;"
                                href="javascript:monthSelected=monthNow;yearSelected=yearNow;constructCalendar();">Wed,
                                8 Jun 2011</a></span></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div id="selectMonth" style="z-index: 999; position: absolute; visibility: hidden;"></div>
    <div id="selectYear" style="z-index: 999; position: absolute; visibility: hidden;"></div>



    <table width="100%" border="0" cellpadding="0" cellspacing="0" height="100%">
        <!-- fwtable fwsrc="MTN4U.png" fwbase="index.jpg" fwstyle="Dreamweaver" fwdocid = "1226677029" fwnested="0" -->
        <tbody>
            <tr>
                <td><img src="education_files/spacer.gif" alt="" width="750" border="0" height="1"></td>
            </tr>

            <tr>
                <td class="centerAligned" valign="top" height="100">
                    <div align="center"></div>
                    <table width="750" border="0" cellpadding="0" cellspacing="0">
                        <!-- fwtable fwsrc="Untitled" fwbase="top.gif" fwstyle="Dreamweaver" fwdocid = "2000728079" fwnested="0" -->
                        <tbody>
                            <tr>
                                <td><img src="education_files/spacer.gif" alt="" width="7" border="0" height="1"></td>
                                <td><img src="education_files/spacer.gif" alt="" width="78" border="0" height="1"></td>
                                <td><img src="education_files/spacer.gif" alt="" width="491" border="0" height="1"></td>
                                <td><img src="education_files/spacer.gif" alt="" width="153" border="0" height="1"></td>
                                <td><img src="education_files/spacer.gif" alt="" width="21" border="0" height="1"></td>
                                <td><img src="education_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
                            </tr>

                            <tr>
                                <td colspan="5"><img name="top_r1_c1" src="education_files/spacer.gif" alt="" width="1"
                                        border="0" height="1"></td>
                                <td><img src="education_files/spacer.gif" alt="" width="1" border="0" height="11"></td>
                            </tr>
                            <tr>
                                <td rowspan="4"><img name="top_r2_c1" src="education_files/spacer.gif" alt="" width="1"
                                        border="0" height="1"></td>
                                <td rowspan="4"><a href="http://www.oouth.com"><img src="education_files/oouthLogo.gif"
                                            width="79" border="0" height="80"></a></td>
                                <td colspan="2" rowspan="4" align="right"><img
                                        src="education_files/careers_at_oouth.gif" width="300" height="40"><img
                                        name="top_r4_c4" src="education_files/spacer.gif" alt="" width="1" border="0"
                                        height="1"></td>
                                <td>&nbsp;</td>
                                <td><img src="education_files/spacer.gif" alt="" width="1" border="0" height="17"></td>
                            </tr>
                            <tr>
                                <td rowspan="3"><img name="top_r3_c5" src="education_files/spacer.gif" alt="" width="1"
                                        border="0" height="1"></td>
                                <td><img src="education_files/spacer.gif" alt="" width="1" border="0" height="37"></td>
                            </tr>
                            <tr>
                                <td><img src="education_files/spacer.gif" alt="" width="1" border="0" height="25"></td>
                            </tr>
                            <tr>
                                <td><img src="education_files/spacer.gif" alt="" width="1" border="0" height="11"></td>
                            </tr>
                        </tbody>
                    </table>

                </td>
            </tr>
            <tr>
                <td class="mainNav" valign="top" height="21">&nbsp;</td>
            </tr>
            <tr>
                <td class="dividerCenterAligned" valign="top" height="1"><img name="index_r3_c1"
                        src="education_files/index_r3_c1.jpg" alt="" width="750" border="0" height="1"></td>
            </tr>
            <tr>
                <td class="globalNav" valign="top" height="25">&nbsp;</td>
            </tr>
            <tr>
                <td class="dividerCenterAligned" valign="top" height="1"><img name="index_r5_c1"
                        src="education_files/index_r5_c1.jpg" alt="" width="750" border="0" height="1"></td>
            </tr>
            <tr>
                <td class="innerPg" valign="top">
                    <table width="750" border="0" cellpadding="0" cellspacing="0">
                        <tbody>
                            <tr>
                                <td rowspan="2" width="8"><img src="education_files/spacer.gif" width="1" height="1">
                                </td>
                                <td colspan="2" class="breadcrumbs" valign="bottom" height="20"><a
                                        href="http://www.oouth.com">Home</a> / Add Beneficiary </td>
                                <td rowspan="2" width="12"><img src="education_files/spacer.gif" width="1" height="1">
                                </td>
                            </tr>
                            <tr>
                                <td class="Content" valign="top" width="180">

                                    <p>&nbsp;</p><br>

                                    <table class="innerWhiteBox" width="96%" border="0" cellpadding="4" cellspacing="0">
                                        <tbody>
                                            <tr>
                                                <td class="sidenavtxt" align=""> <em>
                                                        <font size="1" face="Verdana, Arial, Helvetica, sans-serif">
                                                            Welcome,</font>
                                                    </em>
                                                    <font size="1" face="Verdana, Arial, Helvetica, sans-serif"><span>
                                                            <br>
                                                            <img src="education_files/spacer.gif" width="1" border="0"
                                                                height="8"><img src="education_files/arrow_bullets2.gif"
                                                                border="0">
                                                            <a href="changepasswd.php"></a>
                                                            <input name="Submit" onClick="location.href='index.php'"
                                                                class="formbutton" value="Back to Create Batch"
                                                                type="button">
                                                            <br>
                                                            <img src="education_files/spacer.gif" width="1" border="0"
                                                                height="8"><img src="education_files/arrow_bullets2.gif"
                                                                border="0">
                                                            <a href="personal.php"></a> <br>
                                                            <img src="education_files/spacer.gif" width="1" border="0"
                                                                height="8"><img src="education_files/arrow_bullets2.gif"
                                                                border="0">
                                                            <a href="http://careers.mtnonline.com/logout.asp"></a>
                                                        </span></font>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <br>
                                    <table class="innerWhiteBox" width="96%" border="0" cellpadding="4" cellspacing="0">
                                        <tbody>
                                            <tr>
                                                <td colspan="2" class="sidenavtxt" width="100%" align="">
                                                    <p><a href="vacancies.php"></a> <br>
                                                    </p>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td align=""><img src="education_files/spacer.gif" width="1" border="0"
                                                        height="8"><img src="education_files/arrow_bullets2.gif"
                                                        border="0"></td>
                                                <td class="sidenavtxt" width="100%" align=""><a
                                                        href="http://careers.mtnonline.com/myapplications.asp"></a>
                                                </td>
                                            </tr>

                                        </tbody>
                                    </table>
                                    <br>

                                    <br>
                                    <table class="innerWhiteBox" width="96%" border="0" cellpadding="4" cellspacing="0">
                                        <tbody>
                                            <tr>
                                                <td colspan="2" class="sidenavtxt" align="">
                                                    <p><br>

                                                    </p>
                                                </td>
                                            </tr>


                                            <tr valign="top">
                                                <td align=""><img src="education_files/spacer.gif" width="1" border="0"
                                                        height="8"><img src="education_files/arrow_bullets2.gif"
                                                        border="0"></td>
                                                <td class="sidenavtxt" width="100%" align=""> <a
                                                        href="personal.php"></a></td>
                                            </tr>
                                            <tr valign="top">
                                                <td align=""><img src="education_files/spacer.gif" width="1" border="0"
                                                        height="8"><img src="education_files/arrow_bullets2.gif"
                                                        border="0"></td>
                                                <td class="sidenavtxt" align=""> <a
                                                        href="beneficiary.php?action=add"></a></td>
                                            </tr>
                                            <tr valign="top">
                                                <td align=""><img src="education_files/spacer.gif" width="1" border="0"
                                                        height="8"><img src="education_files/arrow_bullets2.gif"
                                                        border="0"></td>
                                                <td class="sidenavtxt" align=""> <a
                                                        href="workhistory.php?action=add"></a></td>
                                            </tr>
                                            <tr valign="top">
                                                <td align=""><img src="education_files/spacer.gif" width="1" border="0"
                                                        height="8"><img src="education_files/arrow_bullets2.gif"
                                                        border="0"></td>
                                                <td class="sidenavtxt" align=""> <a href="profcert.php?action=add"></a>
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <td align=""><img src="education_files/spacer.gif" width="1" border="0"
                                                        height="8"><img src="education_files/arrow_bullets2.gif"
                                                        border="0"></td>
                                                <td class="sidenavtxt" align=""> <a
                                                        href="http://careers.mtnonline.com/skills.asp"></a><br>
                                                    <br>
                                                </td>
                                            </tr>
                                            <tr>

                                                <td colspan="2" class="legend" align="">&nbsp;</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <br>
                                    <script language="JavaScript1.2" src="education_files/misc.htm"></script>
                                </td>
                                <td rowspan="2" class="Content" valign="top"><img src="education_files/mycv.gif"
                                        width="350" height="30">
                                    <hr size="1" width="500" align="left" color="#cccccc">
                                    <span class="homeContentSmaller"><br>
                                    </span>
                                    <table width="500" border="0" cellpadding="0" cellspacing="0">
                                        <tbody>
                                            <tr>
                                                <td class="toplinks2" valign="top">
                                                    <div align="justify">
                                                        <table class="Content" width="100%" border="0" cellpadding="4"
                                                            cellspacing="0">
                                                            <tbody>
                                                                <tr>
                                                                    <td valign="top">

                                                                        <form action="<?php echo $editFormAction; ?>"
                                                                            method="POST" name="eduEntry"
                                                                            onSubmit="MM_validateForm('txtBankName','','R','txtBankAccountNo','','R','txtbankcode','','R','txtAmount','','R','txNarration','','R');return document.MM_returnValue">
                                                                            <fieldset>
                                                                                <legend class="contentHeader1">Batch
                                                                                    <?php echo $_SESSION['Batch']; ?>
                                                                                </legend>
                                                                                <table width="97%" align="center"
                                                                                    cellpadding="4" cellspacing="0">
                                                                                    <tbody>
                                                                                        <tr valign="top" align="left">
                                                                                            <td colspan="3" height="1">
                                                                                                <img src="education_files/spacer.gif"
                                                                                                    width="1"
                                                                                                    height="1">
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr valign="top" align="left">
                                                                                            <td class="greyBgd"
                                                                                                valign="middle"
                                                                                                width="31%"
                                                                                                align="right"
                                                                                                height="35">Coop id
                                                                                            </td>
                                                                                            <td class="greyBgd"
                                                                                                valign="middle"
                                                                                                width="69%"
                                                                                                align="left">
                                                                                                <select name="txtCoopid"
                                                                                                    class="innerBox"
                                                                                                    id="txtCoopid"
                                                                                                    onChange="clearBox(); getName(this.value)">
                                                                                                    <option value="-1">
                                                                                                        Select</option>
                                                                                                    <?php
do {  
?>
                                                                                                    <option
                                                                                                        value="<?php echo $row_coopid['coopid']?>">
                                                                                                        <?php echo $row_coopid['coopname']?>
                                                                                                    </option>
                                                                                                    <?php
} while ($row_coopid = mysql_fetch_assoc($coopid));
  $rows = mysql_num_rows($coopid);
  if($rows > 0) {
      mysql_data_seek($coopid, 0);
	  $row_coopid = mysql_fetch_assoc($coopid);
  }
?>
                                                                                                </select>

                                                                                                *
                                                                                                <input type="hidden"
                                                                                                    name="coopid"><br>
                                                                                                <input type="button"
                                                                                                    class="formbutton"
                                                                                                    onClick="javascript:onSelectedEdit()"
                                                                                                    value="Edit Bank Account">
                                                                                            </td>
                                                                                            <td width="69%" rowspan="8"
                                                                                                align="left"
                                                                                                valign="top"
                                                                                                class="greyBgd">
                                                                                                <div id="hide"><iframe
                                                                                                        id="opatdetails"
                                                                                                        frameborder="0"
                                                                                                        src="" width=0
                                                                                                        height=0
                                                                                                        style="overflow-style:auto"></iframe>
                                                                                                </div><br>
                                                                                                <div id="hide2"><iframe
                                                                                                        id="editAmount"
                                                                                                        frameborder="0"
                                                                                                        src="" width=0
                                                                                                        height=0
                                                                                                        style="overflow-style:auto"></iframe>
                                                                                                </div>
                                                                                            </td>

                                                                                        </tr>
                                                                                        <tr valign="top" align="left">
                                                                                            <td class="greyBgd"
                                                                                                valign="middle"
                                                                                                width="31%"
                                                                                                align="right"
                                                                                                height="35">Name:</td>
                                                                                            <td class="greyBgd"
                                                                                                valign="middle"
                                                                                                width="69%"
                                                                                                align="left">
                                                                                                <div id="txtCoopName">
                                                                                                    <select
                                                                                                        name="CoopName"
                                                                                                        id="CoopName">
                                                                                                    </select>
                                                                                                </div>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr valign="top" align="left">
                                                                                            <td class="greyBgd"
                                                                                                valign="middle"
                                                                                                width="31%"
                                                                                                align="right"
                                                                                                height="35">Bank

                                                                                                Name:</td>
                                                                                            <td class="greyBgd"
                                                                                                valign="middle"
                                                                                                width="69%"
                                                                                                align="left">
                                                                                                <div id="BankName">
                                                                                                    <input
                                                                                                        name="txtBankName"
                                                                                                        type="text"
                                                                                                        class="innerBox"
                                                                                                        id="txtBankName"
                                                                                                        size="60"
                                                                                                        readonly>
                                                                                                    *
                                                                                                </div>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr valign="top" align="left">
                                                                                            <td class="greyBgd"
                                                                                                valign="middle"
                                                                                                width="31%"
                                                                                                align="right"
                                                                                                height="28">Account No.
                                                                                                :</td>
                                                                                            <td class="greyBgd"
                                                                                                valign="middle"
                                                                                                width="69%"
                                                                                                align="left">
                                                                                                <label></label>
                                                                                                <div id="BankAccountNo">
                                                                                                    <input
                                                                                                        name="txtBankAccountNo"
                                                                                                        type="text"
                                                                                                        class="innerBox"
                                                                                                        id="txtBankAccountNo"
                                                                                                        size="60"
                                                                                                        readonly>
                                                                                                    *
                                                                                                </div>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr valign="top" align="left">
                                                                                            <td class="greyBgd"
                                                                                                valign="middle"
                                                                                                width="31%"
                                                                                                align="right"
                                                                                                height="28">BankCode:
                                                                                            </td>
                                                                                            <td class="greyBgd"
                                                                                                valign="middle"
                                                                                                width="69%"
                                                                                                align="left">
                                                                                                <div id="bankcode">
                                                                                                    <input
                                                                                                        name="txtbankcode"
                                                                                                        type="text"
                                                                                                        class="innerBox"
                                                                                                        readonly
                                                                                                        id="txtbankcode"
                                                                                                        size="60" />
                                                                                                    *
                                                                                                </div>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr valign="top" align="left">
                                                                                            <td class="greyBgd"
                                                                                                valign="middle"
                                                                                                width="31%"
                                                                                                align="right"
                                                                                                height="28">Amount:</td>
                                                                                            <td class="greyBgd"
                                                                                                valign="middle"
                                                                                                width="69%"
                                                                                                align="left">
                                                                                                <div id="amount"><input
                                                                                                        name="txtAmount"
                                                                                                        type="text"
                                                                                                        class="innerBox"
                                                                                                        id="txtAmount"
                                                                                                        size="60"
                                                                                                        onKeyUp="this.value=number_format (this.value);" />
                                                                                                </div>
                                                                                                *
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr valign="top" align="left">
                                                                                            <td class="greyBgd"
                                                                                                valign="middle"
                                                                                                align="right"
                                                                                                height="35">Narration
                                                                                            </td>
                                                                                            <td class="greyBgd"
                                                                                                valign="middle"
                                                                                                align="left">
                                                                                                <div id="div">
                                                                                                    <input
                                                                                                        name="txNarration"
                                                                                                        type="text"
                                                                                                        class="innerBox"
                                                                                                        id="txNarration"
                                                                                                        size="60" />
                                                                                                </div>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr valign="top" align="left">
                                                                                            <td class="greyBgd"
                                                                                                valign="middle"
                                                                                                align="right"
                                                                                                height="35">&nbsp;</td>
                                                                                            <td class="greyBgd"
                                                                                                valign="middle"
                                                                                                align="left">&nbsp;</td>
                                                                                        </tr>
                                                                                        <tr valign="top" align="left">
                                                                                            <td colspan="3"
                                                                                                valign="middle"
                                                                                                align="center"
                                                                                                height="10"><input
                                                                                                    name="Submit2"
                                                                                                    type="submit"
                                                                                                    class="formbutton"
                                                                                                    value="Add Loan"
                                                                                                    onClick="submith()">
                                                                                                <!-- <input name="Submit" onClick="location.href='editAccountNo.php'" class="formbutton" value="Edit Account No." type="button"></td>
                         -->
                                                                                        </tr>
                                                                                        <tr valign="top" align="left">
                                                                                            <td colspan="3" height="3">
                                                                                                <img src="education_files/spacer.gif"
                                                                                                    width="1"
                                                                                                    height="1">
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </fieldset>
                                                                            <input type="hidden" name="MM_update">
                                                                            <input type="hidden" name="MM_insert"
                                                                                value="eduEntry">
                                                                            <input name="Batch" type="hidden" id="Batch"
                                                                                value="<?php echo $_SESSION['Batch']; ?>">
                                                                            <input type="hidden" name="MM_update"
                                                                                value="eduEntry">
                                                                        </form>

                                                                        <br>
                                                                        <fieldset>
                                                                            <legend class="contentHeader1">Benficiary
                                                                                Preview </legend>


                                                                            <script language="JavaScript"
                                                                                type="text/JavaScript">
                                                                                <!--
function GP_popupConfirmMsg(msg) { //v1.0
  document.MM_returnValue = confirm(msg);
}
//-->
                                                                            </script>


                                                                            <table width="96%" align="center"
                                                                                cellpadding="4" cellspacing="0">
                                                                                <tbody>
                                                                                    <tr valign="top" align="right">
                                                                                        <td class="content" align="left"
                                                                                            height="1"><a
                                                                                                href="beneficiary.php?action=add">Add</a>
                                                                                        </td>
                                                                                        <td colspan="7" class="content"
                                                                                            height="1"> <a
                                                                                                href="export.php?BATCH=<?php echo $_SESSION['Batch']; ?>">Export
                                                                                            </a></td>
                                                                                    </tr>
                                                                                    <tr valign="top">
                                                                                        <td class="greyBgdHeader"
                                                                                            valign="middle" height="35">
                                                                                            <strong>Name</strong>
                                                                                        </td>
                                                                                        <td class="greyBgdHeader"
                                                                                            valign="middle">
                                                                                            <strong>Bank</strong>
                                                                                        </td>
                                                                                        <td class="greyBgdHeader"
                                                                                            valign="middle">
                                                                                            <strong>Account No.
                                                                                            </strong>
                                                                                        </td>
                                                                                        <td valign="middle"
                                                                                            class="greyBgdHeader">
                                                                                            <div align="right">
                                                                                                <strong>Amount</strong>
                                                                                            </div>
                                                                                        </td>
                                                                                        <td class="greyBgdHeader"
                                                                                            valign="middle">&nbsp;</td>
                                                                                        <td class="greyBgdHeader"
                                                                                            valign="middle">&nbsp;</td>
                                                                                        <td colspan="2"
                                                                                            class="greyBgdHeader"
                                                                                            valign="middle"><input
                                                                                                name="button"
                                                                                                type="button"
                                                                                                class="tableHeaderContentDarkBlue"
                                                                                                id="button"
                                                                                                value="Delete Selected"
                                                                                                onClick="javascript:deleteBeneficiary(document.forms[2].coop_id.value);">
                                                                                        </td>
                                                                                    </tr>
                                                                                    <?php do { ?>
                                                                                    <tr valign="top">
                                                                                        <td class="greyBgd"
                                                                                            valign="middle" height="35">
                                                                                            <?php echo $row_excel['BeneficiaryName']; ?>
                                                                                        </td>
                                                                                        <td class="greyBgd"
                                                                                            valign="middle">
                                                                                            <?php echo $row_excel['Bank']; ?>
                                                                                        </td>
                                                                                        <td class="greyBgd"
                                                                                            valign="middle">
                                                                                            <?php echo $row_excel['AccountNumber']; ?>
                                                                                        </td>
                                                                                        <td class="greyBgd"
                                                                                            valign="middle">
                                                                                            <div align="right">
                                                                                                <?php echo number_format($row_excel['Amount'] ,2,'.',','); ?>
                                                                                            </div>
                                                                                        </td>
                                                                                        <td class="greyBgd"
                                                                                            valign="middle">
                                                                                            <form name="form1"
                                                                                                method="post" action="">
                                                                                                <input
                                                                                                    onClick="javascript:onSelectedEditAmount(this.value)"
                                                                                                    name="chkcoopid"
                                                                                                    type="checkbox"
                                                                                                    id="chkcoopid"
                                                                                                    value="<?php echo $row_excel['BeneficiaryCode']; ?>">
                                                                                                <br>Edit
                                                                                                <input name="batch"
                                                                                                    type="hidden"
                                                                                                    id="batch"
                                                                                                    value="<?php echo $row_excel['batch']; ?>">
                                                                                            </form>
                                                                                        </td>
                                                                                        <td class="greyBgd"
                                                                                            valign="middle">&nbsp;</td>
                                                                                        <td class="greyBgd"
                                                                                            valign="middle">&nbsp;</td>
                                                                                        <td class="greyBgd"
                                                                                            valign="middle">
                                                                                            <?php if ($totalRows_excel > 0) { // Show if recordset not empty ?>
                                                                                            <form name="form2"
                                                                                                method="post" action="">
                                                                                                <input name="coop_id"
                                                                                                    type="checkbox"
                                                                                                    id="coop_id"
                                                                                                    value="<?php echo $row_excel['BeneficiaryCode']; ?>">


                                                                                            </form>
                                                                                            <?php } // Show if recordset not empty ?>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <?php } while ($row_excel = mysql_fetch_assoc($excel)); ?>
                                                                                    <tr valign="top" align="left">
                                                                                        <td colspan="8" height="3"><img
                                                                                                src="education_files/spacer.gif"
                                                                                                width="1"
                                                                                                height="1"><strong>Sum
                                                                                                of Loan =
                                                                                                <?php echo number_format($row_BatchSum['Sum'] ,2,'.',','); ?></strong>
                                                                                        </td>
                                                                                    </tr>
                                                                                </tbody>
                                                                            </table>
                                                                        </fieldset>
                                                                        <p>&nbsp;</p>


                                                                        <p><br>
                                                                        </p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <br>
                                    <br> <br>
                                </td>
                            </tr>
                            <tr>
                                <td class="Content" valign="top">&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="innerPg" valign="top" height="1"><img name="index_r7_c1"
                        src="education_files/index_r7_c1.jpg" alt="" width="750" border="0" height="1"></td>
            </tr>
            <tr>
                <td class="innerPg" valign="top" height="21">
                    <table class="contentHeader1" width="750" border="0" cellpadding="0" cellspacing="0" height="21">
                        <tbody>
                            <tr>
                                <td class="rightAligned" width="10">&nbsp;</td>
                                <td class="baseNavTxt">&nbsp;</td>
                                <td class="leftAligned" width="12">&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="innerPg" valign="top" height="1"><img name="index_r9_c1"
                        src="education_files/index_r9_c1.jpg" alt="" width="750" border="0" height="1"></td>
            </tr>
            <tr>
                <td class="innerPg" valign="top">&nbsp;</td>
            </tr>
        </tbody>
    </table>
</body>

</html>
<?php
mysql_free_result($coopid);

mysql_free_result($excel);

mysql_free_result($BatchSum);

mysql_free_result($edit_query);
?>