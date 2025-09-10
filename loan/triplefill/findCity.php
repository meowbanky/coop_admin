<!--//---------------------------------+
//  Developed by Roshan Bhattarai    |
//	http://roshanbh.com.np           |
//  Contact for custom scripts       |
//  or implementation help.          |
//  email-nepaliboy007@yahoo.com     |
//---------------------------------+-->
<?
#### Roshan's Ajax dropdown code with php
#### Copyright reserved to Roshan Bhattarai - nepaliboy007@yahoo.com
#### if you have any problem contact me at http://roshanbh.com.np
#### fell free to visit my blog http://php-ajax-guru.blogspot.com
?>

<? $countryId=intval($_GET['country']);
$stateId=intval($_GET['state']);
$link = mysql_connect('localhost', 'root', 'oluwaseyi'); //changet the configuration in required
if (!$link) {
    die('Could not connect: ' . mysql_error());
}
mysql_select_db('db_ajax');
$query="SELECT id,city FROM city WHERE countryid='$countryId' AND stateid='$stateId'";
$result=mysql_query($query);

?>
<select name="city">
<option>Select City</option>
<? while($row=mysql_fetch_array($result)) { ?>
<option value><?=$row['city']?></option>
<? } ?>
</select>
