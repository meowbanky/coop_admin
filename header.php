<div id="user-nav" class="hidden-print hidden-xs">
			<ul class="btn-group ">
				<li class="btn  hidden-xs"><a title="" href="switch_user" data-toggle="modal" data-target="#myModal"><i class="icon fa fa-user fa-2x"></i> <span class="text">	Welcome <b> <?php echo $_SESSION['SESS_FIRST_NAME']; ?> </b></span></a></li>
				
				
				<li class="btn  hidden-xs disabled">
					<a title="" href="pos/" onclick="return false;"><i class="icon fa fa-clock-o fa-2x"></i> <span class="text">
				  <?php
								$Today = date('y:m:d',time());
								$new = date('l, F d, Y', strtotime($Today));
								echo $new;
								?>				</span></a>
				</li>
									<li class="btn "><a href="#"><i class="icon fa fa-cog"></i><span class="text">Settings</span></a></li>
                
                <li class="btn  "><a href="pendingRequest.php"><i class="fa fa-angle-double-up fa-2x"></i>
                    <span id="bell" class="badge1">

			

		</span><div id="bell1"></div></a></li>
                
                <li class="btn  "><a href="pendingRequest.php"><i class="fa fa-angle-double-down fa-2x"></i>
                    <span id="bell2" class="badge1">

			

		</span><div id="bell1"></div></a></li>
                
				        <li class="btn  ">
					<a href="logout.php"><i class="fa fa-power-off"></i><span class="text">Logout</span></a>				</li>
			</ul>
		</div>

<script type="text/javascript" language="javascript">

    var submitting = false;
	
        
</script>