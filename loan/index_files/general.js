//

function inputFocus()
{	
	if(document.getElementById("txtMsisdn"))
	{			
	document.getElementById("txtMsisdn").focus()
	}
}

//
function MM_jumpMenu(targ,selObj,restore){ //v3.0 JUMP MENU
  eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
  if (restore) selObj.selectedIndex=0;
}

function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function YY_checkform() { //v4.66  FORM VALIDATION
//copyright (c)1998,2002 Yaromat.com
  var args = YY_checkform.arguments; var myDot=true; var myV=''; var myErr='';var addErr=false;var myReq;
  for (var i=1; i<args.length;i=i+4){
    if (args[i+1].charAt(0)=='#'){myReq=true; args[i+1]=args[i+1].substring(1);}else{myReq=false}
    var myObj = MM_findObj(args[i].replace(/\[\d+\]/ig,""));
    myV=myObj.value;
    if (myObj.type=='text'||myObj.type=='password'||myObj.type=='hidden'){
      if (myReq&&myObj.value.length==0){addErr=true}
      if ((myV.length>0)&&(args[i+2]==1)){ //fromto
        var myMa=args[i+1].split('_');if(isNaN(myV)||myV<myMa[0]/1||myV > myMa[1]/1){addErr=true}
      } else if ((myV.length>0)&&(args[i+2]==2)){
          var rx=new RegExp("^[\\w\.=-]+@[\\w\\.-]+\\.[a-z]{2,4}$");if(!rx.test(myV))addErr=true;
      } else if ((myV.length>0)&&(args[i+2]==3)){ // date
        var myMa=args[i+1].split("#"); var myAt=myV.match(myMa[0]);
        if(myAt){
          var myD=(myAt[myMa[1]])?myAt[myMa[1]]:1; var myM=myAt[myMa[2]]-1; var myY=myAt[myMa[3]];
          var myDate=new Date(myY,myM,myD);
          if(myDate.getFullYear()!=myY||myDate.getDate()!=myD||myDate.getMonth()!=myM){addErr=true};
        }else{addErr=true}
      } else if ((myV.length>0)&&(args[i+2]==4)){ // time
        var myMa=args[i+1].split("#"); var myAt=myV.match(myMa[0]);if(!myAt){addErr=true}
      } else if (myV.length>0&&args[i+2]==5){ // check this 2
            var myObj1 = MM_findObj(args[i+1].replace(/\[\d+\]/ig,""));
            if(myObj1.length)myObj1=myObj1[args[i+1].replace(/(.*\[)|(\].*)/ig,"")];
            if(!myObj1.checked){addErr=true}
      } else if (myV.length>0&&args[i+2]==6){ // the same
            var myObj1 = MM_findObj(args[i+1]);
            if(myV!=myObj1.value){addErr=true}
      }
    } else
    if (!myObj.type&&myObj.length>0&&myObj[0].type=='radio'){
          var myTest = args[i].match(/(.*)\[(\d+)\].*/i);
          var myObj1=(myObj.length>1)?myObj[myTest[2]]:myObj;
      if (args[i+2]==1&&myObj1&&myObj1.checked&&MM_findObj(args[i+1]).value.length/1==0){addErr=true}
      if (args[i+2]==2){
        var myDot=false;
        for(var j=0;j<myObj.length;j++){myDot=myDot||myObj[j].checked}
        if(!myDot){myErr+='* ' +args[i+3]+'\n'}
      }
    } else if (myObj.type=='checkbox'){
      if(args[i+2]==1&&myObj.checked==false){addErr=true}
      if(args[i+2]==2&&myObj.checked&&MM_findObj(args[i+1]).value.length/1==0){addErr=true}
    } else if (myObj.type=='select-one'||myObj.type=='select-multiple'){
      if(args[i+2]==1&&myObj.selectedIndex/1==0){addErr=true}
    }else if (myObj.type=='textarea'){
      if(myV.length<args[i+1]){addErr=true}
    }
    if (addErr){myErr+='* '+args[i+3]+'\n'; addErr=false}
  }
  if (myErr!=''){alert('The required information is incomplete or contains errors:\t\t\t\t\t\n\n'+myErr)}
  document.MM_returnValue = (myErr=='');
}

function MM_openBrWindow(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}
//-->
// GLOBAL SITE POP-UPS
function payg()
{
window.open("http://www.mtnonline.com/payg_sim.htm","payg","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,copyhistory=no,width=300,height=300")
}

function scam()

{
window.open("http://www.mtnonline.com/scam.htm","scam","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,copyhistory=no,width=300,height=300")
}

function mtn()
{
window.open("http://www.mtnonline.com/","mtn","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=800,height=600")
}

function mtn4u()
{
window.open("http://www.mtnonline.com/products/mtn4u.asp","mtn4u","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=800,height=600")
}

function dwnlds()
{
window.open("https://217.117.0.236","mtn4u","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=800,height=600")
}
function ecare()
{
window.open("https://217.117.0.237/zipCare","ecare","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=800,height=600")
}
function openwindow(){
window.open("credits.asp","credits","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,copyhistory=no,width=400,height=320")
}

//
function showhide()
{
if (tblInfo.style.display=="none")
tblInfo.style.display=="";
else
tblInfo.style.display=="none";
}

//begin IE 4+ And NS6 dHTML Outlines
function hideshow(which)
{
if (!document.getElementById|document.all)
	{
	return
	}
else
	{
	if (document.getElementById)
		{
		oWhich = eval ("document.getElementById('" + which + "')")
		}
	else
		{
		oWhich = eval ("document.all." + which)
		}
	}

	window.focus()

	if (oWhich.style.display=="none")
		{
		oWhich.style.display=""
		}
	else
		{
		oWhich.style.display="none"
		}
}
//end IE 4+ And NS6 dHTML Outlines
function initoutlineExpandible()
{
hideshow('outlineChildFurther registration details')
}
//

//
function mergeDateFields(){
    document.form3.txtDOB.value = document.form3.mnuMonth.value + '/' + document.form3.mnuDay.value + '/' + document.form3.mnuYear.value;
    
    }
//


//
function ChkIDNo()
{
   var valPassport = document.form3.txtPassport.value;

   
   if (( valPassport.length == "")){
      alert("Please enter an identification number");
      document.form3.txtPassport.focus();
      document.form3.txtPassport.select();
      return false;
   }    
   return true;
}


function ChkCellNo()
{
/* var ChkData = new String();
ChkData=form3.txtMobile.value;
ChkData=ChkData.substring(0,9);

*/

var val = document.form3.txtMobile.value;

if (isNaN(parseInt(val))) {
   alert("You must enter a numeric cell number");
       document.form3.txtMobile.focus();
	     document.form3.txtMobile.select();
		 return false;
}

if (( val.length != 11) || (val.substring(0,4) != "0803") ){
   alert("You must enter a cell number in the format 0803XXXXXXX");
   document.form3.txtMobile.focus();
	    document.form3.txtMobile.select();
		 return false;
}      
 
switch (val.substring(0,7)){

 case "0803200":
    alert("This cell number is not eligible to register for MTN4U")
    document.form3.txtMobile.focus();
    document.form3.txtMobile.select();
    return false;
    break;
  case "0803201":
    alert("This cell number is not eligible to register for MTN4U")
    document.form3.txtMobile.focus();
    document.form3.txtMobile.select();
    return false;
    break;
  case "0803203":
    alert("This cell number is not eligible to register for MTN4U")
    document.form3.txtMobile.focus();
    document.form3.txtMobile.select();
    return false;
    break;
  case "0803209":
    alert("This cell number is not eligible to register for MTN4U")
    document.form3.txtMobile.focus();
    document.form3.txtMobile.select();
    return false;
    break;
 case "0803900":
    alert("This cell number is not eligible to register for MTN4U")
    document.form3.txtMobile.focus();
    document.form3.txtMobile.select();
    return false;
    break;
}
 return true;
}
//

//
function EnableDisableObject(objThing, strWhich) 
{
   if (strWhich == "D") {
      objThing.disabled = true;
	  objThing.value = "";
	  objThing.style.backgroundColor = "#FFCA79";
   }else{
      objThing.disabled = false;
	  objThing.style.backgroundColor = "#FFFFFF";
   }
}     	  
function disableTxtReligion(val)
{
var valOfInterest ="Other"
if(val.substring(0,5) !=valOfInterest)
   EnableDisableObject(form3.txtOtherReligions, "D");
else
   EnableDisableObject(form3.txtOtherReligions, "E");
}
function PreferedLang(val)
{
var valOfInterest ="Other"
if(val.substring(0,5) !=valOfInterest)
   EnableDisableObject(form3.txtOtherLang, "D");
else
   EnableDisableObject(form3.txtOtherLang, "E");
}

function Occupation(val)
{
var valOfInterest ="Other"
if(val.substring(0,5) !=valOfInterest)
   EnableDisableObject(form3.txtOtherOcup, "D");
else
   EnableDisableObject(form3.txtOtherOcup, "E");
}
function Banks(val)
{
var valOfInterest ="Other"
if(val.substring(0,5) !=valOfInterest)
   EnableDisableObject(form3.txtOtherBanks, "D");
else
   EnableDisableObject(form3.txtOtherBanks, "E");
}
function OtherTitles(val)
{
var valOfInterest ="Other"
if(val.substring(0,5)!=valOfInterest)
   EnableDisableObject(form3.txtOtherTitles, "D");
else
   EnableDisableObject(form3.txtOtherTitles, "E");
}

function OtherJobs(val)
{
var valOfInterest ="Other"
if(val.substring(0,5) !=valOfInterest)
   EnableDisableObject(form3.txtOtherJobTitles, "D");
else
   EnableDisableObject(form3.txtOtherJobTitles, "E");
}

function Cellmake(val)
{
var valOfInterest ="Other"
if(val.substring(0,5) !=valOfInterest)
   EnableDisableObject(form3.txtOtherCellmake, "D");
else
   EnableDisableObject(form3.txtOtherCellmake, "E");
}

function PhoneUse(val)
{
var valOfInterest ="Other"
if(val.substring(0,5) !=valOfInterest)
   EnableDisableObject(form3.txtOtherPhoneUse, "D");
else
   EnableDisableObject(form3.txtOtherPhoneUse, "E");
}
//

//
   var yyDatevar ='YYnull';
   var yyDiv=null;var YYLang='de';
   var dom= new Array(12);
   dom[0]=31;dom[1]=28;dom[2]=31;dom[3]=30;dom[4]=31;dom[5]=30;dom[6]=31;dom[7]=31;dom[8]=30;dom[9]=31;dom[10]=30;dom[11]=31;
   var YYstrm= new Array(12);
   YYstrm[0]='January';YYstrm[1]='February';YYstrm[2]='March';YYstrm[3]='April';YYstrm[4]='May';YYstrm[5]='June';YYstrm[6]='July';
   YYstrm[7]='August'; YYstrm[8]='September';YYstrm[9]='October';YYstrm[10]='November';YYstrm[11]='December';
   
//

//
function GP_popupConfirmMsg(msg) { //v1.0
  document.MM_returnValue = confirm(msg);
}
//

function ChkPin(){

   var val = document.form1.txtNewPin.value;
   if (( val== "password")){
      alert("You cannot use that password as an Internal User.Please choose something else.");
      document.form1.txtNewPin.focus();
      document.form1.txtNewPin.select();		 
   }    
}

//
function MM_openBrWindow(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}
//
function checkToDate(){
	theMonth = "txtEndMonth";
	theMonth = document.getElementsByName(theMonth)
	theYear = "txtEndYear";
	theYear = document.getElementsByName(theYear)
	if (theMonth[0].selectedIndex == 1){
		theYear[0].className = "hideElement";
		theYear[0].selectedIndex = 0;
	} else {
		theYear[0].className = "innerBox";
	}
}


