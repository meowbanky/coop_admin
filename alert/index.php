<?php require_once('Connections/alertsystem.php'); ?>
<?php
if (!function_exists("GetSQLValueString")) {
  function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "")
  {

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

mysqli_select_db($alertsystem, $database_alertsystem);
$query_masterTransaction = "SELECT Sum(tbl_mastertransact.savingsAmount) AS Savings, Sum(tbl_mastertransact.sharesAmount) AS Shares, Sum(tbl_mastertransact.loan) AS loan, Sum(tbl_mastertransact.loanRepayment) AS repayment, (Sum(tbl_mastertransact.loan) - Sum(tbl_mastertransact.loanRepayment)) AS outstanding, tbl_mastertransact.COOPID, tblemployees.MobileNumber, Max(tbl_mastertransact.TransactionPeriod) as MaxPeriodID FROM tbl_mastertransact INNER JOIN tblemployees ON tblemployees.CoopID = tbl_mastertransact.COOPID GROUP BY tbl_mastertransact.COOPID, tblemployees.MobileNumber";
$masterTransaction = mysqli_query($alertsystem, $query_masterTransaction) or die(mysqli_error($alertsystem));
$row_masterTransaction = mysqli_fetch_assoc($masterTransaction);
$totalRows_masterTransaction = mysqli_num_rows($masterTransaction);

mysqli_select_db($alertsystem, $database_alertsystem);
$query_grandTotal = "SELECT Sum(tbl_mastertransact.savingsAmount) as savings, sum(tbl_mastertransact.sharesAmount) as shares, ((sum(tbl_mastertransact.loan) )- (sum(tbl_mastertransact.loanRepayment))) as outstanding FROM tbl_mastertransact";
$grandTotal = mysqli_query($alertsystem, $query_grandTotal) or die(mysqli_error($alertsystem));
$row_grandTotal = mysqli_fetch_assoc($grandTotal);
$totalRows_grandTotal = mysqli_num_rows($grandTotal);

mysqli_select_db($alertsystem, $database_alertsystem);
$query_coopid = "SELECT right( tblemployees.CoopID,5) FROM tblemployees ";
$coopid = mysqli_query($alertsystem, $query_coopid) or die(mysqli_error($alertsystem));
$row_coopid = mysqli_fetch_assoc($coopid);
$totalRows_coopid = mysqli_num_rows($coopid);

mysqli_select_db($alertsystem, $database_alertsystem);
$query_MaxPeriod = "SELECT tbpayrollperiods.PayrollPeriod FROM tbpayrollperiods where id = " . $row_masterTransaction['MaxPeriodID'];
$MaxPeriod = mysqli_query($alertsystem, $query_MaxPeriod) or die(mysqli_error($alertsystem));
$row_MaxPeriod = mysqli_fetch_assoc($MaxPeriod);
$totalRows_MaxPeriod = mysqli_num_rows($MaxPeriod);

mysqli_select_db($alertsystem, $database_alertsystem);
$query_coopid2 = "SELECT tblemployees.CoopID,tblemployees.MobileNumber FROM tblemployees where Status = 'Active'";
$coopid2 = mysqli_query($alertsystem, $query_coopid2) or die(mysqli_error($alertsystem));
$row_coopid2 = mysqli_fetch_assoc($coopid2);
$totalRows_coopid2 = mysqli_num_rows($coopid2);

mysqli_select_db($alertsystem, $database_alertsystem);
$query_period = "SELECT tbpayrollperiods.id, tbpayrollperiods.PayrollPeriod FROM tbpayrollperiods order by id desc";
$period = mysqli_query($alertsystem, $query_period) or die(mysqli_error($alertsystem));
$row_period = mysqli_fetch_assoc($period);
$totalRows_period = mysqli_num_rows($period);


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<!-- DW6 -->

<head>
  <!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
  <title>..:OOUTH COOP SMS ALERT:..</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
  <link rel="stylesheet" href="mm_health_nutr.css" type="text/css" />
  <script language="JavaScript" type="text/javascript">
    //--------------- LOCALIZEABLE GLOBALS ---------------
    var d = new Date();
    var monthname = new Array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
    //Ensure correct for language. English is "January 1, 2004"
    var TODAY = monthname[d.getMonth()] + " " + d.getDate() + ", " + d.getFullYear();
    //---------------   END LOCALIZEABLE   ---------------
  </script>
  <script language="JavaScript" type="text/javascript">
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

    function makeRequest(url, divID) {

      //alert("ajax code");

      // alert(divID);

      //alert(url);

      var http_request = false;

      if (window.XMLHttpRequest) { // Mozilla, Safari, ...

        http_request = new XMLHttpRequest();

        if (http_request.overrideMimeType) {

          http_request.overrideMimeType('text/xml');

          // See note below about this line

        }

      } else

      if (window.ActiveXObject) { // IE

        //alert("fdsa");

        try {

          http_request = new ActiveXObject("Msxml2.XMLHTTP");

        } catch (e) {

          lgErr.error("this is exception1 in his_secpatientreg.jsp" + e);

          try {

            http_request = new ActiveXObject("Microsoft.XMLHTTP");

          } catch (e) {

            lgErr.error("this is exception2 in his_secpatientreg.jsp" + e);

          }

        }

      }

      if (!http_request) {

        alert('Giving up :( Cannot create an XMLHTTP instance');

        return false;

      }

      http_request.onreadystatechange = function() {
        alertContents(http_request, divID);
      };

      http_request.open('GET', url, true);

      http_request.send(null);

    }

    function alertContents(http_request, divid) {

      if (http_request.readyState == 4) {

        //alert(http_request.status);

        //alert(divid);

        if (http_request.status == 200) {

          document.getElementById(divid).innerHTML = http_request.responseText;

        } else {

          //document.getElementById(divid).innerHTML=http_request.responseText;

          alert("There was a problem with the request");

        }

      }

    }



    function sendsms(id1, id2, id3) {
      var equality = id1;
      var staffid = id2;
      var period = id3;



      window.location = 'sendsms.php?equality=' + equality + '&staffid=' + staffid + '&period=' + period;



    }

    function getStatus(id) {
      //alert("hi");

      //document.getElementById('status_old').style.display="none"; 
      var period = document.getElementById('period').value;

      if (period == 'na') {
        period = '-1';
      }
      //var period2 = document.getElementById('PeriodId2').value;
      var strURL = "getMasterTransaction.php?period=" + period;
      var req = getXMLHTTP();

      if (req) {

        req.onreadystatechange = function() {
          if (req.readyState == 4) {
            // only if "OK"
            //if (req.status == 200) {						
            document.getElementById('status').innerHTML = req.responseText;
            document.getElementById('status').style.visibility = "visible";
            document.getElementById('wait').style.visibility = "hidden";
          } else {
            //document.getElementById('wait').style.width = "100%";
            document.getElementById('wait').style.visibility = "visible";
            document.getElementById('status').style.visibility = "hidden";
          }
        }

        req.open("GET", strURL, true);
        req.send(null);
      }
    }
  </script>
  <style type="text/css">
    <!--
    .style5 {
      font-size: 14px;
      color: #000000;
    }

    .style6 {
      color: #000000;
      font-weight: bold;
      font-size: 14px;
    }

    .style8 {
      color: #000000;
      font-size: 15px;
    }
    -->
  </style>
</head>

<body bgcolor="#F4FFE4">
  <form id="form1" name="form1" method="post" action="coop.php">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr bgcolor="#D5EDB3">
        <td colspan="2" rowspan="2"><img src="mm_health_photo.jpg" alt="Header image" width="382" height="101" border="0" /></td>
        <td width="652" height="50" id="logo" valign="bottom" align="center" nowrap="nowrap">OOUTH COOP. SOCIETY SMS ALERT SYSTEM </td>
        <td width="64">&nbsp;</td>
      </tr>

      <tr bgcolor="#D5EDB3">
        <td height="51" id="tagline" valign="top" align="center">..sms system </td>
        <td width="64">&nbsp;</td>
      </tr>

      <tr>
        <td colspan="4" bgcolor="#5C743D"><img src="mm_spacer.gif" alt="" width="1" height="2" border="0" /></td>
      </tr>

      <tr>
        <td colspan="4" bgcolor="#99CC66" background="mm_dashed_line.gif"><img src="mm_dashed_line.gif" alt="line decor" width="4" height="3" border="0" /></td>
      </tr>

      <tr bgcolor="#99CC66">
        <td>&nbsp;</td>
        <td colspan="3" id="dateformat" height="20"><a href="javascript:;">HOME</a>&nbsp;&nbsp;::&nbsp;&nbsp;<script language="JavaScript" type="text/javascript">
            document.write(TODAY);
          </script>
        </td>
      </tr>

      <tr>
        <td colspan="4" bgcolor="#99CC66" background="mm_dashed_line.gif"><img src="mm_dashed_line.gif" alt="line decor" width="4" height="3" border="0" /></td>
      </tr>

      <tr>
        <td colspan="4" bgcolor="#5C743D"><img src="mm_spacer.gif" alt="" width="1" height="2" border="0" /></td>
      </tr>
      <tr>
        <td width="40">&nbsp;</td>
        <td colspan="2" valign="top">&nbsp;<br />
          &nbsp;<br />
          <table border="0" cellspacing="0" cellpadding="2" width="993">
            <tr>
              <td width="989" class="pageName">Master Transaction Listing - Month of <?php echo $row_MaxPeriod['PayrollPeriod']; ?></td>
            </tr>
            <tr>
              <td class="bodyText">
                <p>&nbsp;</p>
                <table width="99%" border="1" bordercolor="#00FF33">
                  <tr>
                    <th width="42%" scope="col"><strong>Period</strong></th>
                    <th width="58%" scope="col"><select name="period" id="period" onchange="getStatus(this.value)">
                        <option value="">Select Period</option>
                        <?php
                        do {
                        ?>
                          <option value="<?php echo $row_period['id'] ?>"><?php echo $row_period['PayrollPeriod'] ?></option>
                        <?php
                        } while ($row_period = mysqli_fetch_assoc($period));
                        $rows = mysqli_num_rows($period);
                        if ($rows > 0) {
                          mysqli_data_seek($period, 0);
                          $row_period = mysqli_fetch_assoc($period);
                        }
                        ?>
                      </select></th>
                  </tr>
                  <?php do { ?> <?php } while ($row_coopid2 = mysqli_fetch_assoc($coopid2)); ?>
                </table>
              </td>
            </tr>
          </table>
          �
          <div id="status"></div>
          <div align="center" id="wait" style="background-color:white;visibility:hidden;border: 1px solid black;padding:5px;" class="overlay"> <img src="img/wait.gif" alt="" class="area" />Please wait... </div>
          � �
          �
        </td>
        <td width="64">&nbsp;</td>
      </tr>

      <tr>
        <td width="40">&nbsp;</td>

        <td width="64">&nbsp;</td>
      </tr>
    </table>

  </form>
</body>

</html>
<?php
mysqli_free_result($masterTransaction);

mysqli_free_result($grandTotal);

mysqli_free_result($coopid);

mysqli_free_result($period);

mysqli_free_result($MaxPeriod);
?>