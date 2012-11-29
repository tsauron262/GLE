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
//1 fichie dintervention => ficheInterv et intervention dans ficheIntervDet

/* show intervention Filter */

$showIntervention = $_REQUEST['showIntervention']=='on'?true:false;
$showInterventionCreate = $_REQUEST['showInterventionCreate']=='on'?true:false;
$showInterventionValid = $_REQUEST['showInterventionValid']=='on'?true:false;
$showInterventionDate = $_REQUEST['showInterventionDate']=='on'?true:false;
$showInterventionFin = $_REQUEST['showInterventionFin']=='on'?true:false;


array_push($arrFilter,
        array(  "name" => "Intervention" ,
                "data" => array( 0 => array( "checked" => $showIntervention, "trans"=>"tous/aucun"  ) ,
                                 1 => array( "checked" => $showInterventionCreate, "trans" => "creation",     "idx" => "showInterventionCreate"),
                                 2 => array( "checked" => $showInterventionValid, "trans" => "validation",   "idx" => "showInterventionValid"),
                                 3 => array( "checked" => $showInterventionDate, "trans" => "intervention",     "idx" => "showInterventionDate"),
                                 4 => array( "checked" => $showInterventionFin, "trans" => "finIntervention",     "idx" => "showInterventionFin"),
                               )
             )
);

if ($showIntervention)
{

$requete = "SELECT
                    rowid,
                    fk_soc,
                    fk_contrat,
                    datec,
                    date_valid,
                    datei,
                    fk_user_author,
                    fk_user_valid,
                    fk_statut,
                    description,
                    note_private,
                    note_public
                FROM
                    ".MAIN_DB_PREFIX."fichinter
                WHERE
                    fk_soc =
                ". $socid;
          $id=0;
        //print $socid;
        //var_dump($db);
            if ($resql)
            {
                while($res=$db->fetch_object($resql))
                {
                    $url = $dolibarr_main_url_root ."/fichinter/fiche.php?id=".$res->rowid;
                    if ($res->datec && $showInterventionCreate)
                    {

                           $arrRes = $BCalc->pushDateArr(
                                                  $res->datec,
                                                  "Créat. de " . "".$res->ref."" . " (".$soc->nom.")",
                                                  "Cr&eacute;ation de la fiche d'intervention " . $res->ref . "<BR><P>" . $res->note_private. "<BR><P>" . $res->note_public,
                                                  $res->ref,
                                                  $id,
                                                  "intervention",
                                                  1,
                                                  "",
                                                  1,
                                                  'null',
                                                  $url);
                                         $id++;
                    }
                    if ($res->date_valid && $showInterventionValid)
                    {
                            $arrRes = $BCalc->pushDateArr(
                                                  $res->date_valid,
                                                  "Valid de " . "".$res->ref."" . " (".$soc->nom.")",
                                                  "Validation de la fiche d'intervention " . $res->ref . "<BR><P>" . $res->note_private. "<BR><P>" . $res->note_public,
                                                  $res->ref,
                                                  $id,
                                                  "intervention",
                                                  1,
                                                  "",
                                                  1,
                                                  'null',
                                                  $url);
                                         $id++;
                    }
                    if ($res->datei && $showInterventionDate)
                    {
                        $arrRes = $BCalc->pushDateArr(
                                              $res->datei,
                                              "Intervention " . "".$res->ref."" . " (".$soc->nom.")",
                                              "Intervention :  " . $res->ref . "<BR><P>" . $res->note_private. "<BR><P>" . $res->note_public,
                                              $res->ref,
                                              $id,
                                              "intervention",
                                              1,
                                              "",
                                              1,
                                              'null',
                                                  $url);
                                     $id++;
                    }
                    if ($res->duree && $showInterventionFin)
                    {
                        //duree => s

                        $arrRes = $BCalc->pushDateArr(
                                              $res->datei,
                                              "Intervention " . "".$res->ref."" . " (".$soc->nom.")",
                                              "Intervention :  " . $res->ref . "<BR><P>" . $res->note_private. "<BR><P>" . $res->note_public,
                                              $res->ref,
                                              $id,
                                              "intervention",
                                              1,
                                              "",
                                              1,
                                              'null',
                                                  $url);
                                     $id++;
                    }


                }
            }

}

?>