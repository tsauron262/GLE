<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
require_once('./pre.inc.php');
require DOL_DOCUMENT_ROOT."/Synopsis_Common/js/rico2/plugins/php/dbClass2.php";
$appName="NDF";
$appDB=$conf->db->name;

function CreateDbClass() {
  global $oDB;
  $oDB = new dbClass();
  $oDB->Provider = $conf->db->host;
  //$oDB->Dialect="Oracle";
  //$oDB->Dialect="TSQL";
  //$oDB->Dialect="Access";
}

function OpenDB() {
  CreateDbClass();
  global $conf;

  return $GLOBALS['oDB']->MySqlLogon($GLOBALS['appDB'], $conf->db->user, $conf->db->pass);
  //return $GLOBALS['oDB']->OdbcLogon("northwindDSN","Northwind","userid","password");
  //return $GLOBALS['oDB']->OracleLogon("XE","northwind","password");
}

function OpenApp($title) {
  $_retval=false;
  if (!OpenDB()) {
    return $_retval;
  }
  if (!empty($title)) {
    AppHeader($GLOBALS['appName']."-".$title);
  }
  $GLOBALS['accessRights']="rw";
  // CHECK APPLICATION SECURITY HERE  (in this example, "r" gives read-only access and "rw" gives read/write access)
  if (empty($GLOBALS['accessRights']) || !isset($GLOBALS['accessRights']) || substr($GLOBALS['accessRights'],0,1) != "r") {
    echo "<p class='error'>You do not have permission to access this application";
  }
  else {
    $_retval=true;
  }
  return $_retval;
}

function OpenTableEdit($tabname) {
  $obj= new TableEditClass();
  $obj->SetTableName($tabname);
  $obj->options["XMLprovider"]="ricoXMLquery.php";
  $obj->convertCharSet=true;   // because sample database is ISO-8859-1 encoded
  return $obj;
}

function OpenGridForm($title, $tabname) {
  $_retval=false;
  if (!OpenApp($title)) {
    return $_retval;
  }
  $GLOBALS['oForm']= OpenTableEdit($tabname);
  $CanModify=($GLOBALS['accessRights'] == "rw");
  $GLOBALS['oForm']->options["canAdd"]=$CanModify;
  $GLOBALS['oForm']->options["canEdit"]=$CanModify;
  $GLOBALS['oForm']->options["canDelete"]=$CanModify;
  session_set_cookie_params(60*60);
  $GLOBALS['sqltext']='.';
  return true;
}

function CloseApp() {
  global $oDB;
  if (is_object($oDB)) $oDB->dbClose();
  $oDB=NULL;
  $GLOBALS['oForm']=NULL;
}

function AppHeader($hdg) {
  echo "<h2 class='appHeader'>".str_replace("<dialect>",$GLOBALS['oDB']->Dialect,$hdg)."</h2>";
}
?>

