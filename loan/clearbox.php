<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Untitled Document</title>
</head>
<SCRIPT>
function clearBox()
{
document.forms[0].name.value = ""
document.forms[0].school.value = ""

}

function resetBox()
{
document.forms[0].name.value = "-Email Address-";
document.forms[0].school.value = "password";
}
</SCRIPT>
<body>
<form action="" method="get">
  <p>
    <input name="name" type="text" value="rice" / ondblclick=clearBox() />
    <label>
    <select name="select" onchange="clearBox()">
      <option>w</option>
      <option>dfg</option>
      <option>fgd</option>
    </select>
    </label>
  </p>
  <p>
    <input name="school" type="text" value="ewa" / onfocus=clearBox()>
    </p>
</form>
</body>
</html>
