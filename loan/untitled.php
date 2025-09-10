<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>

<script language="JavaScript">
		        function getXMLHTTP() {
           var x = false;
           try {
              x = new XMLHttpRequest();
           }catch(e) {
             try {
                x = new ActiveXObject("Microsoft.XMLHTTP");
             }catch(ex) {
                try {
                    req = new ActiveXObject("Msxml2.XMLHTTP");
                }
                catch(e1) {
                    x = false;
                }
             }
          }
          return x;
        }
	
</script>	


<script language="JavaScript">
//var strURL="vitalsignsave.php?uom=weigjt&value=90&vitalSign=Weight&pid=5&vid=6&MM_insert=form1&username=1";
function saveVitalsign() {		
		
	var strURL="vitalsignsave.php?uom=weigjt&value=90&vitalSign=Weight&pid=5&vid=6&MM_insert=form1&username=1";
          var req = getXMLHTTP();

          if (req){
            req.onreadystatechange = function(){
              if (req.readyState == 4){
                if (req.status == 200){
                  document.getElementById('statediv').innerHTML=req.responseText;
                } else {
                  alert("There was a problem while using XMLHTTP:\n" + req.statusText);
                }
              }
            }
            req.open("GET", strURL, true);
            req.send(null);
           }
        }

function getName(coopid) {		
		coopid = "iui";
		var strURL="name.php?id="+coopid;
		var req = getXMLHTTP();
		
		if (req) {
			
			req.onreadystatechange = function() {
				if (req.readyState == 4) {
					// only if "OK"
					if (req.status == 200) {						
						document.getElementById('txtCoopName').innerHTML=req.responseText;						
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

<body>
<form id="form1" name="form1" method="post" action=""><input type="submit" name="button" id="button" onClick="saveVitalsign()" value="Submit" />
  
</form>
</body>
</html>