<?php
/*
  ** BIMP-ERP by Synopsis et DRSI
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
  * BIMP-ERP-1.1
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
  <table id="list14" class="scroll" width=100%; cellpadding="0" cellspacing="0"></table>
   <div id="pager14" class="scroll" style="text-align:center;"></div>
   <div id="contratGADiv"></div>
</div>

<?php
}

?>