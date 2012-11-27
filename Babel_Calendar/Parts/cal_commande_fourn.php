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


/* show commande Filter */

$showCommandeFourn = $_REQUEST['showCommandeFourn']=='on'?true:false;
$showCommandeFournCreate = $_REQUEST['showCommandeFournCreate']=='on'?true:false;
$showCommandeFournValid = $_REQUEST['showCommandeFournValid']=='on'?true:false;
$showCommandeFournCloture = $_REQUEST['showCommandeFournCloture']=='on'?true:false;
$showCommandeFournDate = $_REQUEST['showCommandeFournDate']=='on'?true:false;


array_push($arrFilter,
        array(  "name" => "CommandeFourn" ,
                "data" => array( 0 => array( "checked" => $showCommandeFourn, "trans"=>"tous/aucun"  ) ,
                                 1 => array( "checked" => $showCommandeFournCreate, "trans" => "creation",     "idx" => "showCommandeFournCreate"),
                                 2 => array( "checked" => $showCommandeFournValid, "trans" => "validation",   "idx" => "showCommandeFournValid"),
                                 3 => array( "checked" => $showCommandeFournCloture, "trans" => "cloture",      "idx" => "showCommandeFournCloture"),
                                 4 => array( "checked" => $showCommandeFournDate, "trans" => "commande",     "idx" => "showCommandeFournDate"),
                               )
             )
);

//var_dump($arrRes);
  //commande
    //Date cr&eacute;ation
    //Date validation
    //Date cloture
    //Date commande
    //statut
    //ref
if ($showCommandeFourn)
{

    $requete = "SELECT rowid,
                       fk_soc,
                       ref,
                       date_creation,
                       date_valid,
                       date_cloture,
                       date_commande,
                       fk_user_author,
                       fk_user_valid,
                       fk_user_cloture,
                       fk_statut,
                       note,
                       note_public
                   FROM ".MAIN_DB_PREFIX."commande_fournisseur
                   WHERE fk_soc =
    " . $socid;
        $resql = $db->query($requete);
        $id=0;
        if ($resql)
        {
            while($res=$db->fetch_object($resql))
            {
                $url = $dolibarr_main_url_root."/fourn/commande/fiche.php?id=".$res->rowid;
                if ($res->date_creation && $showCommandeFournCreate)
                {
                   $arrRes = $BCalc->pushDateArr(
                                          $res->date_creation,
                                          "Cr&eacute;at. de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Cr&eacute;ation de la commande fournisseur" . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "commandefourn",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
                }
                if ($res->date_commande && $showCommandeFournDate)
                {
                   $arrRes = $BCalc->pushDateArr(
                                          $res->date_commande,
                                          "Date Com Fourn" . "".$res->ref."" . " (".$soc->nom.")",
                                          "commande fournisseur" . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "commandefourn",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
                }
                if ($res->date_valid && $showCommandeFournValid)
                {
                    $arrRes = $BCalc->pushDateArr(
                                          $res->date_valid,
                                          "Valid de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Validation de la commande fournisseur" . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "commandefourn",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
                }
                if ($res->date_cloture && $showCommandeFournCreate)
                {
                    $arrRes = $BCalc->pushDateArr(
                                          $res->date_cloture,
                                          "Clot de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Cloture commande fournisseur" . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "commandefourn",
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