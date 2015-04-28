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

/* show Propal Filter */

$showCommPropal = $_REQUEST['showCommPropal']=='on'?true:false;
$showCommPropalCreate = $_REQUEST['showCommPropalCreate']=='on'?true:false;
$showCommPropalValid = $_REQUEST['showCommPropalValid']=='on'?true:false;
$showCommPropalCloture = $_REQUEST['showCommPropalCloture']=='on'?true:false;
$showCommPropalDate = $_REQUEST['showCommPropalDate']=='on'?true:false;
$showCommPropalFinValid = $_REQUEST['showCommPropalFinValid']=='on'?true:false;

array_push($arrFilter,
        array(  "name" => "CommPropal" ,
                "data" => array( 0 => array( "checked" => $showCommPropal, "trans"=>"tous/aucun"  ) ,
                                 1 => array( "checked" => $showCommPropalCreate, "trans" => "creation",     "idx" => "showCommPropalCreate"),
                                 2 => array( "checked" => $showCommPropalValid, "trans" => "validation",   "idx" => "showCommPropalValid"),
                                 3 => array( "checked" => $showCommPropalCloture, "trans" => "cloture",      "idx" => "showCommPropalCloture"),
                                 4 => array( "checked" => $showCommPropalDate, "trans" => "date propale", "idx" => "showCommPropalDate"),
                                 5 => array( "checked" => $showCommPropalFinValid, "trans" => "fin de validit&eacute",   "idx" => "showCommPropalFinValid"),
                               )
             )
);
  //Date Création
  $requete = "SELECT datec
                FROM ".MAIN_DB_PREFIX."societe
               WHERE rowid = " . $usrid;


if ($showCommPropal)
{
  //Propal
    //Date création
    //Date validation
    //Date cloture
    //Date fin_validite
    //statut
    //ref
  $requete = "SELECT rowid,
                     ref,
                     datec,
                     datep,
                     fin_validite,
                     date_valid,
                     date_cloture,
                     fk_user_author,
                     fk_user_valid,
                     fk_user_cloture,
                     fk_statut,
                     note,
                     note_public,
                     date_livraison
                FROM ".MAIN_DB_PREFIX."propal
               WHERE fk_user_author =" . $usrid;
    $resql = $db->query($requete);
    $id=0;
//print $usrid;
    if ($resql)
    {
        while($res=$db->fetch_object($resql))
        {

            $url =  $dolibarr_main_url_root."/comm/propal.php?propalid=".$res->rowid;
            if ($res->datec && $showCommPropalCreate)
            {

                   $arrRes = $BCalc->pushDateArr(
                                          $res->datec,
                                          "Créat. de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Cr&eacute;ation de la proposition commerciale " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "CommPropal",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
            }
            if ($res->datep && $showCommPropalDate)
            {
                    $arrRes = $BCalc->pushDateArr(
                                          $res->datep,
                                          "Date Prop " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Proposition commerciale " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "CommPropal",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
            }
            if ($res->fin_validite && $showCommPropalFinValid)
            {
                $arrRes = $BCalc->pushDateArr(
                                      $res->fin_validite,
                                      "Fin valid de " . "".$res->ref."" . " (".$soc->nom.")",
                                      "Fin de validiter de la proposition commerciale " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                      $res->ref,
                                      $id,
                                      "CommPropal",
                                      1,
                                      "",
                                      1,
                                      'null',
                                          $url);
                             $id++;

            }
            if ($res->date_valid && $showCommPropalValid)
            {
                    $arrRes = $BCalc->pushDateArr(
                                          $res->date_valid,
                                          "Valid de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Validation de la proposition commerciale " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "CommPropal",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
            }
            if ($res->date_cloture && $showCommPropalFinValid)
            {
                $arrRes = $BCalc->pushDateArr(
                                      $res->date_cloture,
                                      "Clot de " . "".$res->ref."" . " (".$soc->nom.")",
                                      "Cloture Proposition commerciale " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                      $res->ref,
                                      $id,
                                      "CommPropal",
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