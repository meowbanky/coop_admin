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
#### fell free to visit my blog http://roshanbh.com.np
?>

<? $country=intval($_GET['country']);
$link = mysql_connect('localhost', 'root', 'oluwaseyi'); //changet the configuration in required
if (!$link) {
    die('Could not connect: ' . mysql_error());
}
mysql_select_db('db_ajax');
$query="SELECT id,statename FROM state WHERE countryid='$country'";
$result=mysql_query($query);

?>
<html>
<head>
<script language="javascript" type="text/javascript">
function getCity(countryId,stateId)
{
  var strURL="findCity.php?country="+countryId+"&state="+stateId;
  var req = getXMLHTTP();
  if (req)
  {
    req.onreadystatechange = function()
    {
      if (req.readyState == 4) // only if "OK"
      {
        if (req.status == 200)
        {
          document.getElementById('citydiv').innerHTML=req.responseText;
        } else {
          alert("There was a problem while using XMLHTTP:\n" + req.statusText);
        }
      }
    }
    req.open("GET", strURL, true);
    req.send(null);
  }
}
</script>
</head>
<select name="state" onchange="getCity(<?=$country?>,this.value)">
<option>Select State</option>
<? while($row=mysql_fetch_array($result)) { ?>
<option value=<?=$row['id']?>><?=$row['statename']?></option>
<? } ?>
</select>
