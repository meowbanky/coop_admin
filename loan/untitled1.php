<!DOCTYPE html>
<html>
<body>

<p>How would you like your coffee?</p>

<form action="form_action.asp" name="test">
<input type="radio" name="coffee" value="cream">With cream<br>
<input type="radio" name="coffee" value="sugar">With milk<br>
<br>
<input type="button" onclick="myFunction()" value="Send order">
<br><br>
<input type="text" id="order" size="50">
<input type="submit" value="Submit">
</form>

<script>
function myFunction()
{
var coffee = document.forms["test"].coffee;
var txt = "";
var i;
for (i=0;i<coffee.length;i++)
  {
  if (coffee[i].checked)
    {
    txt = txt + coffee[i].value + " ";
    }
  }
document.getElementById("order").value = "You ordered a coffee with: " + txt;
}
</script>

</body>
</html>
