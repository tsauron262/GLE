/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
// JavaScript (sCal-05m) Calculator in HEAD:'JavaScript' area. Tested IE5/6, Netscape6 OK
// This is free, public domain, user-modifiable code.  R.Mohaupt-Feb,2003. NO WARRANTEE.

var x = "";  // by Javascript loose typing, 'x' mostly a string, sometimes a number
var m = "";  // stores Memory.  Note: x & m are GLOBAL--of concern if sCal embedded.

function Ix() {x = document.sCal.IOx.value;} // Input synch to x-string.
             // Required since TextArea can (must, in cases) be changed from keyboard
function Ox() {document.sCal.IOx.value = x;} // Output x-string change to display

var a=0; var b=0; var d=0; var f=0;          // GLOBAL (or LOCAL to xEval fcn if moved).
var g=32.15; var h=0; var I=0; var i=0;      // Note: g=gravity.  Variables & Mr fcn used
var l=0; var M=0; var N=0; var P=0; var R=0; // in *}programs. User may add more if needed,
var s=0; var S=0; var v=0; var y=0; var w=0; // with attention to CASE Sensitivity.
function Mr(val,place) {var d=Math.pow(10,place); // --- set output decimal places
 return Math.round(d*val)/d;}

function xEval() {Ix();    // JavaScript eval allows multi-statements, see examples.
 var n = x.indexOf('^');
 if (n > 0) {
  if (x.indexOf('^',n+1) > 0) {alert("WARNING! Only 1 [^] allowed in expression!");}
  else {  // all to left of '^' is taken as base, and all right as exponent
  document.sCal.IOx.value = Math.pow(eval(x.substring(0,n)),eval(x.substring(n+1)));}
  }       // likewise, entire x-value is used as function argument, not just last term
 else {document.sCal.IOx.value = eval(x);}
 Ix();
 }

function xPlusEq(s) {Ix(); x += s; Ox();} // --- DISPLAY-x functions ---
function xMultEq(s) {xEval(); x *= s; Ox();}
function Clear() {x = ""; Ox();}
function BkSpace() {Ix(); x = x.substring(0,x.length-1) ; Ox();}
function recip() {xEval(); x = 1/(x); Ox();}

function Xwork(s)  // --- determines what to do with incoming MENU (s)-values ---
 {if (isNaN(s))
  {if (s.indexOf('x')>-1)       //-if expression is f(x), i.e.Method,
   {xEval(); x = eval(s); Ox();}// figure x, & substiture in function
  else {x += eval(s); Ox();} }  //-if a Property (eg. Math.PI), add value
 else {xPlusEq(s);}  }          //-if numeric constant, append like Jwork

function Im() {m = document.sCal.IOm.value;} // --- MEMORY fcns: see Ix() & Ox() ---
function Om() {document.sCal.IOm.value = m;}
function XtoM()  {Ix(); Im(); m += x; Om(); x=""; Ox();}
function MtoX()  {Ix(); Im(); x += m; Ox();}
function Mplus() {xEval(); if (m=="")
 {m=0;} m = parseFloat(m) + parseFloat(x); Om(); x=""; Ox();}
function Mclear() {m = ""; Om();}

//  End of JavaScript Calculator in HEAD -->

isCalcShow = false;
function scal_clearInnerHTML(obj)// tool to delete the content of an HTML object.
{
    if (debug == 1) {alert ('clearInnerHTML');}
    while(obj.firstChild) obj.removeChild(obj.firstChild);
}

var RemPath = "";
function scal_showCacl(path)
{
    RemPath = path;
    if (isCalcShow)
    {
        //save data before close
        var tmp = false;
        var tmp1 = false;
        if (document.getElementById('IOx') && document.getElementById('IOx').value)
        {
            tmp = urlencode(document.getElementById('IOx').value);
            if (document.getElementById('IOm') && document.getElementById('IOm').value)
            {
                tmp1 = urlencode(document.getElementById('IOm').value);
            }
        }
        var url = path+"/Babel_sCal.php?action=remRes&wasOpen=0";
        if (tmp){ url += "&remRes="+tmp;}
        if (tmp1){ url += "&remMem="+tmp1;}
        scal_xmlhttpPost(url,1);

    } else { //open
        var url = path+"/Babel_sCal.php?wasOpen=1";
        scal_xmlhttpPost(url,0);
    }
}
function urlencode(str) {
    return escape(str.replace(/%/g, '%25').replace(/\+/g, '%2B')).replace(/%25/g, '%');
}
function scal_getquerystring(lev)
{
//    alert(lev);
    return(true);
}


var debug = false;
function scal_xmlhttpPost(strURL, lev)//Ajax engine
{
    if (debug == 1) {
        alert('xmlhttpPost');
    }
    //  alert('ajax'+lev);
    var xmlHttpReq = false;
    var self = this;
    // Mozilla/Safari
    if (window.XMLHttpRequest) {
        self.xmlHttpReq = new XMLHttpRequest();
    }
    // IE
    else
        if (window.ActiveXObject) {
            self.xmlHttpReq = new ActiveXObject('Microsoft.XMLHTTP');
        }
    self.xmlHttpReq.open('POST', strURL, true);
    self.xmlHttpReq.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    self.xmlHttpReq.onreadystatechange = function(){
        if (self.xmlHttpReq.readyState == 4) {
            if (lev == 1)
            {
                scal_updatepage_save(self.xmlHttpReq,lev);
            } else if (lev == 2)
            {
                scal_updatepage_save(self.xmlHttpReq,lev);
            } else if (lev == 0){
                scal_updatepage(self.xmlHttpReq);
            }
        }
    }
        self.xmlHttpReq.send(scal_getquerystring(lev));
}


function scal_updatepage(xmlDoc)
{
    var html = xmlDoc.responseText;
    var div = document.createElement("DIV");
        div.id = "calcDiv";
        div.innerHTML = html;

        var body = document.getElementById('mainbody');
        body.appendChild(div);
        isCalcShow = true;
        document.getElementById('mainbody').setAttribute('onUnload','scal_save_datas()');
}
function scal_updatepage_save(xmlDoc,slev)
{
    if (slev==1)
    {
        var calcDiv = document.getElementById('calcDiv');
            scal_clearInnerHTML(calcDiv);
            calcDiv.parentNode.removeChild(calcDiv);
            isCalcShow = false;
    }
}

function scal_save_datas()
{
        var tmp = false;
        if (document.getElementById('IOx') && document.getElementById('IOx').value)
        {
            var tmp1 =false;
                tmp = urlencode(document.getElementById('IOx').value);
            if (document.getElementById('IOm') && document.getElementById('IOm').value)
            {
                tmp1 = urlencode(document.getElementById('IOm').value);
            }
            var url = RemPath+"Babel_sCal.php?action=remRes&remRes="+tmp+"&wasOpen=1";
            if (tmp1){ url += "&remMem="+tmp1; }
            scal_xmlhttpPost(url,1);
        }
}
