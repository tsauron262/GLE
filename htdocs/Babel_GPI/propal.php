<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 2 mai 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : contrat.php
  * GLE-1.1
  */



    require_once("./pre.inc.php");
//Auth ajax local
if ($_COOKIE['logged'] != "OK")
{
    header('Location: index.php');
} else {
?>

<br/><br/>
<div id='fiche'>
  <table id="list13" class="scroll" width=100%; cellpadding="0" cellspacing="0"></table>
   <div id="pager13" class="scroll" style="text-align:center;"></div>
   <div id="propalDiv"></div>
</div>

<?php
}

?>