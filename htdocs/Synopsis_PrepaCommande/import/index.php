<?php
/*
  ** BIMP-ERP by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 26 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : index.php
  * BIMP-ERP-1.2
  */

  require_once('pre.inc.php');
  llxHeader();

  print "<div class='titre'>Importation BIMP</div>";
  print "<br/>";
  print "<br/>";
  print "<br/>";
  print" <a href='config.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Configuration</span></a>";
  print "<br/>";
  print "<br/>";
  print" <a href='testImport.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Import</span></a>";
  print "<br/>";
  print "<br/>";
  print" <a href='history.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Historique</span></a>";
  print "<br/>";

  llxFooter();

?>
