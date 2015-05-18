<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 24 fevr. 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : validatationProcess-xml_response.php
  * GLE-1.2
  */

  require_once('../../main.inc.php');
  require_once(DOL_DOCUMENT_ROOT."/Synopsis_Process/class/process.class.php");

  $processdet_refid = $_REQUEST['processDetId'];
  $process_refid = $_REQUEST['processId'];
  $element_refid = $_REQUEST['element_refid'];
  $note = addslashes($_REQUEST['note']);
  $valeur = $_REQUEST['valeur'];
  $code = $_REQUEST['code'];

  $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def WHERE code = '".$code."'";
  $sql = $db->query($requete);
  $res = $db->fetch_object($sql);
  $validation_type_refid = $res->id;
  $isValForAll = false;
  if ($res->isValidationForAll == 1) $isValForAll = true;


  $requete = "SELECT ifnull(max(validation_number),0) as vn FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet_validation WHERE processdet_refid = '".$processdet_refid."'";
  $sql = $db->query($requete);
  $res = $db->fetch_object($sql);
  $validation_number = ($_REQUEST['validation_number']>0?$_REQUEST['validation_number']:1);

  $tmp = 'process'.$process_refid;
  $process = new Process($db);
  $process->fetch($process_refid);
  $process->getGlobalRights();
  if ($user->rights->process_user->$tmp->valider) $isValForAll = true;
  if($user->rights->process->valider) $isValForAll = true;


  $user_refid = $user->id;
  $db->begin();

  $xml = "";

  $ok=false;
  if ($isValForAll)
  {
      $requete = "SELECT *
                    FROM " . MAIN_DB_PREFIX . "Synopsis_Process_rights_def
                   WHERE isValidationRight = 1
                     AND active = 1
                     AND isValidationForAll <> 1";
      $sql = $db->query($requete);
      $ok=true;
      while($res = $db->fetch_object($sql) )
      {
          $requetePre = "SELECT *
                           FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet_validation
                          WHERE validation_number = ".$validation_number."
                            AND processdet_refid = ".$processdet_refid."
                            AND element_refid = ".$element_refid."
                            AND validation_type_refid = ".$res->id;
          $sqlPre = $db->query($requetePre);

          if ($db->num_rows($sqlPre) > 0) continue;
          $requete1= " INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Processdet_validation
                                   (user_refid,
                                    process_refid,
                                    processdet_refid,
                                    validation_type_refid,
                                    element_refid,
                                    valeur,
                                    validation_number,
                                    note
                                   )
                            VALUES ( ".$user_refid." ,
                                     ".$process_refid." ,
                                     ".$processdet_refid." ,
                                     ".$res->id." ,
                                     ".$element_refid." ,
                                     ".$valeur." ,
                                     ".$validation_number." ,
                                     '".$note."' )";
          if($ok)
          {
              $sql1 = $db->query($requete1);
              if(!$sql1)$ok=false;
          }
      }
  } else {

      $requete = " INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Processdet_validation
                               (user_refid,
                                process_refid,
                                processdet_refid,
                                validation_type_refid,
                                element_refid,
                                valeur,
                                validation_number,
                                note
                               )
                        VALUES ( ".$user_refid." ,
                                 ".$process_refid." ,
                                 ".$processdet_refid." ,
                                 ".$validation_type_refid." ,
                                 ".$element_refid." ,
                                 ".$valeur." ,
                                 ".$validation_number." ,
                                 '".$note."' )";
      $sql = $db->query($requete);
      if($sql)$ok=true;
  }

  //Si validation OK pour tous => valid le processDet
  $processDet = new processDet($db);
  $processDet->fetch($processdet_refid);
  $processDet->validate($valeur);


  if($ok)
  {
    $xml = "<OK>OK</OK>";
    $db->commit();
  } else {
    $xml = "<KO>KO<![CDATA[".print_r($db,1)."]]></KO>";
    $db->rollback();
  }

    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
    } else {
        header("Content-type: text/xml;charset=utf-8");
    } $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
    echo "<ajax-response>";
    echo $xml;
    echo "</ajax-response>";
?>
