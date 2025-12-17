<?php
ini_set('max_execution_time', '0');
require_once('../Connections/paymaster.php');
$recordtime = date('Y-m-d H:i:s');
session_start();

//if(isset($_POST["Import"])){


$filename = $_FILES["file"]["tmp_name"];




if ($_FILES["file"]["size"] > 0) {


	$file = fopen($filename, "r");
	while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
		//$emapData[0] = str_replace(',', '', $emapData[0]);
		//$emapData[1] = str_replace(',', '', $emapData[1]);
		//				$emapData[3] = str_replace(',','',$emapData[3]);
		//				$emapData[4] = str_replace(',','',$emapData[4]);
		//				$emapData[5] = str_replace(',','',$emapData[5]);
		//				$emapData[6] = str_replace(',','',$emapData[6]);
		//				$emapData[7] = str_replace(',','',$emapData[7]);
		//				$emapData[8] = str_replace(',','',$emapData[8]);

		mysqli_select_db($salary, $database_salary);
		$sqlStaff_id = "select staff_id from employee where staff_id ='" . $emapData[0] . "'";
		echo $emapData[0];
		$Staff_id = mysqli_query($salary, $sqlStaff_id) or die(mysqli_error($salary));
		$row_Staff_id = mysqli_fetch_assoc($Staff_id);
		$Staff_id = $row_Staff_id['staff_id'];

		$qryCheck = "select * from allow_deduc where allow_id = '41' AND staff_id = '" . $emapData[0] . "'";
		$Check = mysqli_query($salary, $qryCheck) or die(mysqli_error($salary));
		$row_qryCheck = mysqli_fetch_assoc($Check);
		$total_Check = mysqli_num_rows($Check);
		if ($total_Check > 0) {
			//It wiil insert a row to our subject table from our csv file`
			$sql = "UPDATE allow_deduc SET value = '" . $emapData[1] . "' where allow_id = '41' AND staff_id = '" . $emapData[0] . "'";
			//we are using mysql_query function. it returns a resource on true else False on error
		} else {
			$sql = "INSERT INTO allow_deduc (staff_id, allow_id, value, transcode, date_insert, inserted_by) VALUES ({$emapData[0]}, 41, {$emapData[1]}, 2, $recordtime, {$_SESSION['SESS_MEMBER_ID']})";
		}
		mysqli_select_db($salary, $database_salary);
		$result = mysqli_query($salary, $sql) or die(mysqli_error($salary));
		if (!$result) {
			echo "<script type=\"text/javascript\">
							alert(\"Invalid File:Please Upload CSV File.\");
							window.location = \"index.php\"
						</script>";
		}
	}
	fclose($file);
	//throws a message if data successfully imported to mysql database from excel file
	echo "<script type=\"text/javascript\">
						alert(\"CSV File has been successfully Imported.\");
						window.location = \"index.php\"
					</script>";



	//close of connection
	mysqli_close($salary);
}
	//}	 
