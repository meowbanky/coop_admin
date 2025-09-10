<?php require_once('Connections/alertsystem.php'); ?>
<?php
$period = -1;
if (isset($_GET['period'])) {
  $period = $_GET['period'];
}

mysqli_select_db($alertsystem, $database_alertsystem);
$query_masterTransaction = "SELECT Sum(tbl_mastertransact.savingsAmount) AS Savings, Sum(tbl_mastertransact.sharesAmount) AS Shares, Sum(tbl_mastertransact.loan) AS loan, Sum(tbl_mastertransact.loanRepayment) AS repayment, (Sum(tbl_mastertransact.loan) - Sum(tbl_mastertransact.loanRepayment)) AS outstanding, tbl_mastertransact.COOPID, tblemployees.MobileNumber, Max(tbl_mastertransact.TransactionPeriod) as MaxPeriodID FROM tbl_mastertransact INNER JOIN tblemployees ON tblemployees.CoopID = tbl_mastertransact.COOPID GROUP BY tbl_mastertransact.COOPID, tblemployees.MobileNumber";
$masterTransaction = mysqli_query($alertsystem, $query_masterTransaction) or die(mysqli_error($alertsystem));
$row_masterTransaction = mysqli_fetch_assoc($masterTransaction);
$totalRows_masterTransaction = mysqli_num_rows($masterTransaction);

mysqli_select_db($alertsystem, $database_alertsystem);
$query_grandTotal = "SELECT Sum(tbl_mastertransact.savingsAmount) as savings, sum(tbl_mastertransact.sharesAmount) as shares, ((sum(tbl_mastertransact.loan) )- (sum(tbl_mastertransact.loanRepayment))) as outstanding FROM tbl_mastertransact where TransactionPeriod <= '" . $period . "'";
$grandTotal = mysqli_query($alertsystem, $query_grandTotal) or die(mysqli_error($alertsystem));
$row_grandTotal = mysqli_fetch_assoc($grandTotal);
$totalRows_grandTotal = mysqli_num_rows($grandTotal);

mysqli_select_db($alertsystem, $database_alertsystem);
$query_coopid = "SELECT right( tblemployees.CoopID,5), LastName FROM tblemployees ";
$coopid = mysqli_query($alertsystem, $query_coopid) or die(mysqli_error($alertsystem));
$row_coopid = mysqli_fetch_assoc($coopid);
$totalRows_coopid = mysqli_num_rows($coopid);

mysqli_select_db($alertsystem, $database_alertsystem);
$query_MaxPeriod = "SELECT tbpayrollperiods.PayrollPeriod FROM tbpayrollperiods where id = '" . $period . "'";
$MaxPeriod = mysqli_query($alertsystem, $query_MaxPeriod) or die(mysqli_error($alertsystem));
$row_MaxPeriod = mysqli_fetch_assoc($MaxPeriod);
$totalRows_MaxPeriod = mysqli_num_rows($MaxPeriod);

mysqli_select_db($alertsystem, $database_alertsystem);
$query_coopid2 = "SELECT tblemployees.CoopID,tblemployees.MobileNumber FROM tblemployees where Status = 'Active'";
$coopid2 = mysqli_query($alertsystem, $query_coopid2) or die(mysqli_error($alertsystem));
$row_coopid2 = mysqli_fetch_assoc($coopid2);
$totalRows_coopid2 = mysqli_num_rows($coopid2);


?>


<style type="text/css">
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
</style>
<table width="99%" border="1" bordercolor="#00FF33">
  <tr>
    <th width="10%" scope="col"><span class="style8">COOP ID </span></th>
    <th width="18%" scope="col"><span class="style8">SAVINGS</span></th>
    <th width="19%" scope="col"><span class="style8">SHARES </span></th>
    <th scope="col" width="15%"><span class="style8">COMM.<br />
        BALANCE </span></th>
    <th scope="col" width="15%"><span class="style8">LOAN BALANCE </span></th>
    <th width="10%" scope="col"><span class="style8">TEL. NO </span></th>
    <th width="65%" scope="col"><span class="style8">PERIOD</span></th>
  </tr>
  <?php set_time_limit(0);
  do { ?>
    <tr>
      <?php
      mysqli_select_db($alertsystem, $database_alertsystem);
      $query_savings = "SELECT Sum(tbl_savings.AmountPaid) as Savings FROM tbl_savings WHERE tbl_savings.CoopID = '" . $row_coopid2['CoopID'] . "' AND DeductionPeriod <= '" . $period . "'";
      $savings = mysqli_query($alertsystem, $query_savings) or die(mysqli_error($alertsystem));
      $row_savings = mysqli_fetch_assoc($savings);
      $totalRows_savings = mysqli_num_rows($savings);

      $query_shares = "SELECT Sum(tbl_shares.sharesAmount) as Shares FROM tbl_shares WHERE tbl_shares.CoopID = '" . $row_coopid2['CoopID'] . "' AND SharesPeriod <= '" . $period . "'";
      $shares = mysqli_query($alertsystem, $query_shares) or die(mysqli_error($alertsystem));
      $row_shares = mysqli_fetch_assoc($shares);
      $totalRows_shares = mysqli_num_rows($shares);

      $query_loan = "SELECT Sum(tbl_loans.LoanAmount) as Loan FROM tbl_loans WHERE tbl_loans.CoopID = '" . $row_coopid2['CoopID'] . "' AND LoanPeriod <= '" . $period . "'";
      $loan = mysqli_query($alertsystem, $query_loan) or die(mysqli_error($alertsystem));
      $row_loan = mysqli_fetch_assoc($loan);
      $totalRows_loan = mysqli_num_rows($loan);
      $loanV = $row_loan['Loan'];

      $query_loanRepayment = "SELECT Sum(tbl_loanrepayment.Repayment) as Repayment FROM tbl_loanrepayment WHERE tbl_loanrepayment.CoopID = '" . $row_coopid2['CoopID'] . "' AND LoanRepaymentPeriod <= '" . $period . "'";
      $loanRepayment = mysqli_query($alertsystem, $query_loanRepayment) or die(mysqli_error($alertsystem));
      $row_loanRepayment = mysqli_fetch_assoc($loanRepayment);
      $totalRows_loanRepayment = mysqli_num_rows($loanRepayment);
      $loanRepaymentV = $row_loanRepayment['Repayment'];


      $Balance = $loanV - $loanRepaymentV;

      $query_commodity = "SELECT Sum(tbl_commodity.amount) as commodity FROM tbl_commodity WHERE tbl_commodity.coopID = '" . $row_coopid2['CoopID'] . "' AND Period <= '" . $period . "'";
      $commodity = mysqli_query($alertsystem, $query_commodity) or die(mysqli_error($alertsystem));
      $row_commodity = mysqli_fetch_assoc($commodity);
      $totalRows_commodity = mysqli_num_rows($commodity);
      $commodityV = $row_commodity['commodity'];


      $query_commodityRepay = "SELECT Sum(tbl_commodityrepayment.CommodityPayment) as ComRepay FROM tbl_commodityrepayment WHERE tbl_commodityrepayment.coopid = '" . $row_coopid2['CoopID'] . "' AND PaymentPeriod <= '" . $period . "'";
      $commodityRepay = mysqli_query($alertsystem, $query_commodityRepay) or die(mysqli_error($alertsystem));
      $row_commodityRepay = mysqli_fetch_assoc($commodityRepay);
      $totalRows_commodityRepay = mysqli_num_rows($commodityRepay);
      $commodityRepayV = $row_commodityRepay['ComRepay'];

      $commodityBalance = $commodityV - $commodityRepayV;

      ?>
      <th scope="row"><span class="style5"><?php echo $row_coopid2['CoopID']; ?></span></th>
      <td align="right"><span class="style5"><strong>
            <?php echo number_format($row_savings['Savings'], 2, '.', ','); ?>
          </strong></span></td>
      <td align="right"><span class="style5"><strong>
            <?php echo number_format($row_shares['Shares'], 2, '.', ','); ?>
          </strong></span></td>
      <td align="right"><span class="style5"><strong>
            <?php echo number_format($commodityBalance, 2, '.', ','); ?>
          </strong></span></td>
      <td align="right"><span class="style5"><strong>
            <?php echo number_format($Balance, 2, '.', ','); ?>
          </strong></span></td>
      <td><span class="style5"><strong><?php echo $row_coopid2['MobileNumber']; ?></strong></span></td>
      <td width="100%"><span class="style5"><strong><?php echo $row_MaxPeriod['PayrollPeriod']; ?></strong></span></td>
    </tr>
  <?php } while ($row_coopid2 = mysqli_fetch_assoc($coopid2)); ?>
  <tr>
    <th scope="row"><span class="style5"><strong>GRAND TOTAL </strong></span></th>
    <td align="right"><span class="style6"><?php echo number_format($row_grandTotal['savings'], 2, '.', ','); ?></span></td>
    <td align="right"><span class="style6"><?php echo number_format($row_grandTotal['shares'], 2, '.', ','); ?></span></td>
    <td align="right">&nbsp;</td>
    <td align="right"><span class="style6"><?php echo number_format($row_grandTotal['outstanding'], 2, '.', ','); ?></span></td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
</table>
<p>
  <?php require_once('Connections/alertsystem.php'); ?>
  <?php
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


  ?>
<div align="center"><br />
  <table width="50%" border="0">
    <tr>
      <th width="25%" height="34" scope="col">
        <div align="right">Equality</div>
      </th>
      <th width="26%" scope="col">
        <div align="left">
          <select name="equality" id="equality">
            <option value="&gt;">Equality</option>
            <option value="=">Equals</option>
            <option value="&gt;">Greater Than</option>
            <option value="&lt;">Less Than</option>
            <option value="&gt;=">Greater Than or Equals To</option>
            <option value="&lt;=">Less Than or Equals To</option>
          </select>
        </div>
      </th>
      <th width="17%" scope="col">Coop ID </th>
      <th width="32%" scope="col"><select name="coopid" id="coopid">
          <option value="0">COOP ID</option>
          <?php
          do {
          ?>
            <option value="<?php echo $row_coopid['right( tblemployees.CoopID,5)'] ?>"><?php echo $row_coopid['right( tblemployees.CoopID,5)']; ?> </option>
          <?php
          } while ($row_coopid = mysqli_fetch_assoc($coopid));
          $rows = mysqli_num_rows($coopid);
          if ($rows > 0) {
            mysqli_data_seek($coopid, 0);
            $row_coopid = mysqli_fetch_assoc($coopid);
          }
          ?>
        </select>
      </th>
    </tr>
  </table>
   
  <input type="submit" name="Submit" value="SEND SMS" />
  <br />
</div>