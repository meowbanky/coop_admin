<?php require_once('Connections/alertsystem.php'); 

mysql_select_db($database_alertsystem, $alertsystem);
//$query_commodity = "SELECT tbl_commodity.amount, tbl_commodity.coopID, tbl_commodity.Period FROM tbl_commodity WHERE tbl_commodity.Period = 51";
//$result = mysql_query($query_commodity) or
//die( mysql_error() );
//
//while ($row = mysql_fetch_row($result)){
//
//echo $row[0] . "\n";
//$upatequery = "update tbl_mastertransact set Commodity = " . $row[0]." where COOPID = '" . $row[1]. "' and TransactionPeriod = 51";
//set_time_limit(0);
//$result2 = mysql_query($upatequery);
//
//echo "success" ;
//}



$query_commodity = "SELECT tbl_commodity.coopID, sum(tbl_commodity.amount) FROM tbl_commodity where coopid = 'coop-00045' group by tbl_commodity.coopID ";
$result3 = mysql_query($query_commodity) or
die( mysql_error() );

while ($row3 = mysql_fetch_row($result3)){
set_time_limit(0);
echo $row3[0] . "\n";
$upatequery4 = "SELECT tbl_commodityrepayment.coopid, Sum(tbl_commodityrepayment.CommodityPayment) FROM tbl_commodityrepayment where tbl_commodityrepayment.coopid = 	COOP-00044 GROUP BY tbl_commodityrepayment.coopid";
set_time_limit(0);
$result4 = mysql_query($upatequery4);
$row4 = mysql_fetch_row($result4);
// if (is_null($row4[1])){
//	 $row4[1] = 0 ;
// }
 echo "coopid = ".$row3[0]."\r\n";
 echo "commodity = " . $row3[1]."\r\n";
 echo "commodityPayment = " .$row4[1]. "\r\n" ;
 $commbalan = ($row3[1]-$row4[1])."\r\n";

echo "balance = " . $commbalan ;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
</head>

<body>
</body>
</html>
