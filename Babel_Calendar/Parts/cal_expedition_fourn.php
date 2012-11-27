<?php
/*
 * GLE by Synopsis et DRSI
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
 //
//
//
//
///* show expedition Filter */
//
//$showExpedition = $_REQUEST['showExpedition']=='on'?true:false;
//$showExpeditionCreate = $_REQUEST['showExpeditionCreate']=='on'?true:false;
//$showExpeditionValid = $_REQUEST['showExpeditionValid']=='on'?true:false;
//$showExpeditionDate = $_REQUEST['showExpeditionDate']=='on'?true:false;
//
//
//array_push($arrFilter,
//        array(  "name" => "Expedition" ,
//                "data" => array( 0 => array( "checked" => $showExpedition, "trans"=>"tous/aucun"  ) ,
//                                 1 => array( "checked" => $showExpeditionCreate, "trans" => "creation",     "idx" => "showExpeditionCreate"),
//                                 2 => array( "checked" => $showExpeditionValid, "trans" => "validation",   "idx" => "showExpeditionValid"),
//                                 3 => array( "checked" => $showExpeditionDate, "trans" => "expedition",     "idx" => "showExpeditionDate"),
//                               )
//             )
//);
//
//  //expedition
//    //Date création
//    //Date expedition
//    //Date validation
//
//    $requete = "SELECT
//    ".MAIN_DB_PREFIX."expedition.rowid,
//    ".MAIN_DB_PREFIX."expedition.ref,
//    ".MAIN_DB_PREFIX."expedition.fk_soc,
//    ".MAIN_DB_PREFIX."expedition.date_creation,
//    ".MAIN_DB_PREFIX."expedition.date_valid,
//    ".MAIN_DB_PREFIX."expedition.date_expedition,
//    ".MAIN_DB_PREFIX."expedition.fk_user_author,
//    ".MAIN_DB_PREFIX."expedition.fk_user_valid,
//    ".MAIN_DB_PREFIX."expedition.fk_expedition_methode,
//    ".MAIN_DB_PREFIX."expedition.fk_statut,
//    ".MAIN_DB_PREFIX."expedition.note,
//    ".MAIN_DB_PREFIX."expedition_methode.rowid,
//    ".MAIN_DB_PREFIX."expedition_methode.code,
//    ".MAIN_DB_PREFIX."expedition_methode.libelle,
//    ".MAIN_DB_PREFIX."expedition_methode.description,
//    ".MAIN_DB_PREFIX."expedition_methode.statut
//FROM
//    ".MAIN_DB_PREFIX."expedition ".MAIN_DB_PREFIX."expedition
//LEFT JOIN
//    ".MAIN_DB_PREFIX."expedition_methode ".MAIN_DB_PREFIX."expedition_methode
//ON
//    ".MAIN_DB_PREFIX."expedition.fk_expedition_methode = ".MAIN_DB_PREFIX."expedition_methode.rowid
//WHERE
//    ".MAIN_DB_PREFIX."expedition.fk_soc =
//" . $socid;
//
//
//    $id=0;
//    if ($resql)
//    {
//        while($res=$db->fetch_object($resql))
//        {
//            if ($res->date_creation && $showExpeditionCreate)
//            {
//               $arrRes = $BCalc->pushDateArr(
//                                      $res->date_creation,
//                                      "Créat. de " . "".$res->ref."" . " (".$soc->nom.")",
//                                      "Cr&eacute;ation de l'expedition " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
//                                      $res->ref,
//                                      $id,
//                                      "expedition",
//                                      1,
//                                      "",
//                                      1,
//                                      'null',
//                                          $url);
//                             $id++;
//            }
//            if ($res->date_expedition && $showExpeditionDate)
//            {
//                $arrRes = $BCalc->pushDateArr(
//                                      $res->date_expedition,
//                                      "Date Exp " . "".$res->ref."" . " (".$soc->nom.")",
//                                      "Expedition " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
//                                      $res->ref,
//                                      $id,
//                                      "expedition",
//                                      1,
//                                      "",
//                                      1,
//                                      'null',
//                                          $url);
//                             $id++;
//            }
//            if ($res->date_valid && $showExpeditionValid)
//            {
//                $arrRes = $BCalc->pushDateArr(
//                                      $res->date_valid,
//                                      "Valid de " . "".$res->ref."" . " (".$soc->nom.")",
//                                      "Validation de l'expedition " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
//                                      $res->ref,
//                                      $id,
//                                      "expedition",
//                                      1,
//                                      "",
//                                      1,
//                                      'null',
//                                          $url);
//                             $id++;
//            }
//        }
//    }
//
//
//
?>