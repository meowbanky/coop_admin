<!DOCTYPE html>
<?php 
require_once('../Connections/hms.php');

mysql_select_db($database_hms, $hms);
				$query_Period = "SELECT tbpayrollperiods.Periodid, tbpayrollperiods.PayrollPeriod FROM tbpayrollperiods order by Periodid DESC";
				$Period = mysql_query($query_Period, $hms) or die(mysql_error());
				$row_Period = mysql_fetch_assoc($Period);
				$totalRows_Period = mysql_num_rows($Period);
 
?>	
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>Import Excel To Mysql Database Using PHP </title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="Import Excel File To MySql Database Using php">
 
		<link rel="stylesheet" href="css/bootstrap.min.css">
		<link rel="stylesheet" href="css/bootstrap-responsive.min.css">
		<link rel="stylesheet" href="css/bootstrap-custom.css">
 
 <script language="javascript">
 function getXMLHTTP() { //fuction to return the xml http object
		var xmlhttp=false;	
		try{
			xmlhttp=new XMLHttpRequest();
		}
		catch(e)	{		
			try{			
				xmlhttp= new ActiveXObject("Microsoft.XMLHTTP");
			}
			catch(e){
				try{
				xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
				}
				catch(e1){
					xmlhttp=false;
				}
			}
		}
		 	
		return xmlhttp;
    }
	
			function makeRequest(url,divID) {

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

                }

                else

                    if (window.ActiveXObject) { // IE

                        //alert("fdsa");

                        try {

                            http_request = new ActiveXObject("Msxml2.XMLHTTP");

                        } catch (e) {

                            lgErr.error("this is exception1 in his_secpatientreg.jsp"+e);

                            try {

                                http_request = new ActiveXObject("Microsoft.XMLHTTP");

                            } catch (e) {

                                lgErr.error("this is exception2 in his_secpatientreg.jsp"+e);

                            }

                    }

                }

                if (!http_request) {

                    alert('Giving up :( Cannot create an XMLHTTP instance');

                    return false;

                }

                http_request.onreadystatechange = function() {  alertContents(http_request,divID); };

                http_request.open('GET', url, true);

                http_request.send(null);

            }
			function alertContents(http_request,divid) {

                if (http_request.readyState == 4) {

                    //alert(http_request.status);

                    //alert(divid);

                    if (http_request.status == 200) {

                        document.getElementById(divid).innerHTML=http_request.responseText;

                    } else {

                        //document.getElementById(divid).innerHTML=http_request.responseText;

                        alert("There was a problem with the request");

                    }

                }

            }
			
			function getContribution(id){
	
			
		//document.getElementById('Save').style.display="none";
		
			
		var strURL="getContribution.php?period_id="+id;
		var req = getXMLHTTP();
		
		if (req) {
			
			req.onreadystatechange = function() {
				if (req.readyState == 4) {
					// only if "OK"
					document.getElementById('contribution').innerHTML=req.responseText;	
					document.getElementById('contribution').style.visibility = "visible";
					document.getElementById('wait').style.visibility = "hidden";
				}else {
					document.getElementById('wait').style.height = "100%";
						document.getElementById('wait').style.width = "100%";
						document.getElementById('wait').style.visibility = "visible";
						document.getElementById('contribution').style.visibility = "hidden";
					}				
			}			
			req.open("GET", strURL, true);
			req.send(null);
		}		
	}
	function validatee(){
		if (document.getElementById('Period').value == ''){
			alert('Select Period to Upload');
			return false;
			}
		
		
		}
 </script>
	</head>
	<body>    
 
	<!-- Navbar
    ================================================== -->
 
	<div class="navbar navbar-inverse navbar-fixed-top">
		<div class="navbar-inner">
			<div class="container"> 
				<strong>
					<a class="brand" href="#">Import Excel To Contribution</a></strong>
 
			</div>
		</div>
	</div>
 
	<div id="wrap">
	<div class="container">
	  <div class="row">
			<div class="span3 hidden-phone">Done Uploading? <a href="../editContributions.php">Go Back</a></div>
			<div class="span6" id="form-login">
				<form class="form-horizontal well"  onSubmit="return validatee()" action="import.php" method="post" name="upload_excel" enctype="multipart/form-data">
					<fieldset>
						<legend>Import CSV/Excel file</legend>
						<div class="control-group">
							<div class="control-label">
								<label>CSV/Excel File:</label>
							</div>
							<div class="controls">
								<input type="file" name="file" id="file" class="input-large">
							</div>
                            <div class="controls">
                              <select name="Period" id="Period" class="input-group-lg" onChange="getContribution(this.value)">
                                <option value="">Select Period</option>
                                <?php
do {  
?>
                                <option value="<?php echo $row_Period['Periodid']?>"><?php echo $row_Period['PayrollPeriod']?></option>
                                <?php
} while ($row_Period = mysql_fetch_assoc($Period));
  $rows = mysql_num_rows($Period);
  if($rows > 0) {
      mysql_data_seek($Period, 0);
	  $row_Period = mysql_fetch_assoc($Period);
  }
?>
                              </select>
                            </div>
						</div>
 
						<div class="control-group">
							<div class="controls">
							<button type="submit" id="submit" name="Import" class="btn btn-primary button-loading" data-loading-text="Loading...">Upload</button>
							</div>
						</div>
					</fieldset>
				</form>
			</div>
			<div class="span3 hidden-phone"></div>
		</div>
 
		<div id="contribution"></div>
	</div>
 
	</div>
 <div id="wait" style="background-color:white;visibility:hidden;border: 1px solid black;padding:5px;width:100%;height:100%;" class="overlay" > <img src="pageloading.gif" alt="" class="area">Please wait... </div>
	</body>
</html>