<?php ini_set('max_execution_time','3000');
require_once('../Connections/coop.php');
include_once('model.php'); 

$i = 0;
if(isset($_POST['myarray'])){
foreach ($_POST['myarray'] as $myarray){
$splitter = explode(',', $_POST['myarray'][$i]); 
$coopid = $splitter[0];
$period = $splitter[1];


if(($coopid != -1) and ($period != -1)) {


delete2Item('tbl_shares','coopid','SharesPeriod',$coopid,$period);
delete2Item('tbl_savings','coopid','deductionperiod',$coopid,$period);
// delete2Item('tbl_commodity','coopid','period',$coopid,$period);
delete2Item('tbl_commodityrepayment','coopid','paymentperiod',$coopid,$period);
delete2Item('tbl_loans','coopid','loanperiod',$coopid,$period);
delete2Item('tbl_loanrepayment','coopid','loanrepaymentPeriod',$coopid,$period);
delete2Item('tbl_entryfee','coopid','deductionperiod',$coopid,$period);
delete2Item('tbl_stationery','coopid','stationeryperiodperiod',$coopid,$period);
delete2Item('tbl_mastertransact','coopid','TransactionPeriod',$coopid,$period);


}
++$i;
}
echo 1;
}
?>