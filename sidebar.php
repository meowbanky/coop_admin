 <div id="sidebar" class="hidden-print minibar sales_minibar">

 	<ul style="display: block;"><?php $currentPage = (substr($_SERVER["SCRIPT_NAME"], strrpos($_SERVER["SCRIPT_NAME"], "/") + 1)) ?>

 		<li <?php if ($currentPage == 'home.php') { ?> class="active" <?php } ?>><a href="home.php"><i class="icon fa fa-dashboard"></i><span class="hidden-minibar">Dashboard</span></a></li>
 		<?php if ($_SESSION['role'] == 'Admin') { ?>
 			<li <?php if ($currentPage == 'processLoan.php') { ?> class="active" <?php } ?>><a href="processLoan.php"><i class="fa fa-shopping-cart"></i><span class="hidden-minibar">Process Loan</span></a></li>

 			<li <?php if ($currentPage == 'enquiry.php') { ?> class="active" <?php } ?>><a href="enquiry.php"><i class="fa fa-search"></i><span class="hidden-minibar">Enquiry</span></a></li>

 			<li <?php if ($currentPage == 'masterReport.php') { ?> class="active" <?php } ?>><a href="masterReport.php"><i class="fa fa-bar-chart-o"></i><span class="hidden-minibar">Reports</span></a></li>
 			<li <?php if ($currentPage == 'procesCommodity.php') { ?> class="active" <?php } ?>><a href="procesCommodity.php"><i class="fa fa-exchange"></i><span class="hidden-minibar">Commodity</span></a></li>

 			<li <?php if ($currentPage == 'payperiods.php') { ?> class="active" <?php } ?>><a href="payperiods.php"><i class="fa fa-table"></i><span class="hidden-minibar">Periods</span></a></li>

 			<li <?php if ($currentPage == 'Users.php') { ?> class="active" <?php } ?>><a href="Users.php"><i class="fa fa-group"></i><span class="hidden-minibar">users</span></a></li>

 		<?php } ?>
 		<li <?php if ($currentPage == 'employee.php') { ?> class="active" <?php } ?>><a href="employee.php"><i class="fa fa-user"></i><span class="hidden-minibar">Records</span></a></li>

 				<li <?php if ($currentPage == 'upload.php') { ?> class="active" <?php } ?>><a href="upload.php"><i class="fa fa-upload"></i><span class="hidden-minibar">Upload</span></a></li>

		<li <?php if ($currentPage == 'bank_statement_upload.php') { ?> class="active" <?php } ?>><a href="bank_statement_upload.php"><i class="fa fa-bank"></i><span class="hidden-minibar">Bank Statement Upload</span></a></li>

		<li <?php if ($currentPage == 'payprocess.php') { ?> class="active" <?php } ?>><a href="payprocess.php"><i class="fa fa-cog"></i><span class="hidden-minibar"> Process Deduction</span></a></li>

 		<li <?php if ($currentPage == 'menu_mail.php') { ?> class="active" <?php } ?>><a href="menu_mail.php"><i class="fa fa-envelope"></i><span class="hidden-minibar"> Send Mail</span></a></li>
 		<li>
 			<a href="logout.php"><i class="fa fa-power-off"></i><span class="hidden-minibar">Logout</span></a>
 		</li>
 	</ul>
 </div>