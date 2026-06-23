 <div id="sidebar" class="hidden-print minibar sales_minibar">

     <ul style="display: block;">
         <?php $currentPage = (substr($_SERVER["SCRIPT_NAME"], strrpos($_SERVER["SCRIPT_NAME"], "/") + 1)) ?>

         <li <?php if ($currentPage == 'home.php') { ?> class="active" <?php } ?>><a href="home.php"><i
                     class="icon fa fa-dashboard"></i><span class="hidden-minibar">Dashboard</span></a></li>
         <?php if ($_SESSION['role'] == 'Admin') { ?>
         <li <?php if ($currentPage == 'processLoan.php') { ?> class="active" <?php } ?>><a href="processLoan.php"><i
                     class="fa fa-shopping-cart"></i><span class="hidden-minibar">Process Loan</span></a></li>

         <li <?php if ($currentPage == 'loan-request-admin.php') { ?> class="active" <?php } ?>><a
                 href="loan-request-admin.php"><i class="fa fa-money-bill-wave"></i><span class="hidden-minibar">Loan
                     Request Admin</span></a></li>

         <li <?php if ($currentPage == 'event-management.php') { ?> class="active" <?php } ?>><a
                 href="event-management.php"><i class="fa fa-calendar-alt"></i><span class="hidden-minibar">Event
                     Management</span></a></li>

         <li <?php if ($currentPage == 'enquiry.php') { ?> class="active" <?php } ?>><a href="enquiry.php"><i
                     class="fa fa-search"></i><span class="hidden-minibar">Enquiry</span></a></li>

         <li <?php if ($currentPage == 'masterReport.php') { ?> class="active" <?php } ?>><a href="masterReport.php"><i
                     class="fa fa-bar-chart-o"></i><span class="hidden-minibar">Reports</span></a></li>
         <li <?php if ($currentPage == 'procesCommodity.php') { ?> class="active" <?php } ?>><a
                 href="procesCommodity.php"><i class="fa fa-exchange"></i><span
                     class="hidden-minibar">Commodity</span></a></li>

         <li <?php if ($currentPage == 'payperiods.php') { ?> class="active" <?php } ?>><a href="payperiods.php"><i
                     class="fa fa-table"></i><span class="hidden-minibar">Periods</span></a></li>

         <?php
         $acctPages = ['coop_annual_report.php','coop_financial_statements.php','coop_trial_balance.php',
                       'coop_general_ledger.php','coop_journal_entries.php','coop_chart_of_accounts.php',
                       'coop_period_closing.php','coop_bank_reconciliation.php','coop_member_statement.php',
                       'coop_comparative_reports.php'];
         $acctOpen  = in_array($currentPage, $acctPages);
         ?>
         <li class="<?php echo $acctOpen ? 'active' : ''; ?>" id="acct-group">
             <a href="#" onclick="toggleAcctMenu(event)"
                style="display:flex;align-items:center;justify-content:space-between;">
                 <span><i class="fa fa-book"></i><span class="hidden-minibar"> Accounting</span></span>
                 <i class="fa fa-chevron-<?php echo $acctOpen ? 'down' : 'right'; ?> hidden-minibar"
                    id="acct-chevron" style="font-size:0.7rem;opacity:0.7;"></i>
             </a>
             <ul id="acct-submenu"
                 style="display:<?php echo $acctOpen ? 'block' : 'none'; ?>;
                        list-style:none;padding:0;margin:0;background:rgba(0,0,0,0.15);">
                 <?php
                 $acctLinks = [
                     'coop_annual_report.php'       => ['fa-file-text-o', 'Annual Report'],
                     'coop_financial_statements.php'=> ['fa-bar-chart',   'Financials'],
                     'coop_trial_balance.php'       => ['fa-balance-scale','Trial Balance'],
                     'coop_general_ledger.php'      => ['fa-list-alt',    'General Ledger'],
                     'coop_journal_entries.php'     => ['fa-pencil-square-o','Journal Entries'],
                     'coop_chart_of_accounts.php'   => ['fa-sitemap',     'Chart of Accounts'],
                     'coop_member_statement.php'    => ['fa-user-o',      'Member Statement'],
                     'coop_comparative_reports.php' => ['fa-line-chart',  'Comparative'],
                     'coop_bank_reconciliation.php' => ['fa-exchange',    'Bank Reconciliation'],
                     'coop_period_closing.php'      => ['fa-lock',        'Period Closing'],
                 ];
                 foreach ($acctLinks as $file => [$icon, $label]):
                 ?>
                 <li <?php echo ($currentPage == $file) ? 'class="active"' : ''; ?>>
                     <a href="<?php echo $file; ?>" style="padding-left:28px;font-size:0.85em;">
                         <i class="fa <?php echo $icon; ?>"></i>
                         <span class="hidden-minibar"> <?php echo $label; ?></span>
                     </a>
                 </li>
                 <?php endforeach; ?>
             </ul>
         </li>
         <script>
         function toggleAcctMenu(e) {
             e.preventDefault();
             var menu    = document.getElementById('acct-submenu');
             var chevron = document.getElementById('acct-chevron');
             var open    = menu.style.display !== 'none';
             menu.style.display = open ? 'none' : 'block';
             if (chevron) chevron.className = open ? 'fa fa-chevron-right hidden-minibar' : 'fa fa-chevron-down hidden-minibar';
             chevron.style.fontSize = '0.7rem'; chevron.style.opacity = '0.7';
         }
         </script>

         <li <?php if ($currentPage == 'Users.php') { ?> class="active" <?php } ?>><a href="Users.php"><i
                     class="fa fa-group"></i><span class="hidden-minibar">users</span></a></li>

         <?php } ?>
         <li <?php if ($currentPage == 'employee.php') { ?> class="active" <?php } ?>><a href="employee.php"><i
                     class="fa fa-user"></i><span class="hidden-minibar">Records</span></a></li>

         <li <?php if ($currentPage == 'upload.php') { ?> class="active" <?php } ?>><a href="upload.php"><i
                     class="fa fa-upload"></i><span class="hidden-minibar">Upload</span></a></li>

         <li <?php if ($currentPage == 'bank_statement_upload.php') { ?> class="active" <?php } ?>><a
                 href="bank_statement_upload.php"><i class="fa fa-bank"></i><span class="hidden-minibar">Bank Statement
                     Upload</span></a></li>

         <li <?php if ($currentPage == 'payprocess.php') { ?> class="active" <?php } ?>><a href="payprocess.php"><i
                     class="fa fa-cog"></i><span class="hidden-minibar"> Process Deduction</span></a></li>

         <li <?php if ($currentPage == 'menu_mail.php') { ?> class="active" <?php } ?>><a href="menu_mail.php"><i
                     class="fa fa-envelope"></i><span class="hidden-minibar"> Send Mail</span></a></li>
         <li>
             <a href="logout.php"><i class="fa fa-power-off"></i><span class="hidden-minibar">Logout</span></a>
         </li>
     </ul>
 </div>