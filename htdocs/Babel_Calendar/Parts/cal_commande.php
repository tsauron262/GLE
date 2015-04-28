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

$showCommande = $_REQUEST['showCommande']=='on'?true:false;
$showCommandeCreate = $_REQUEST['showCommandeCreate']=='on'?true:false;
$showCommandeValid = $_REQUEST['showCommandeValid']=='on'?true:false;
$showCommandeCloture = $_REQUEST['showCommandeCloture']=='on'?true:false;
$showCommandeDate = $_REQUEST['showCommandeDate']=='on'?true:false;


array_push($arrFilter,
        array(  "name" => "Commande" ,
                "data" => array( 0 => array( "checked" => $showCommande, "trans"=>"tous/aucun"  ) ,
                                 1 => array( "checked" => $showCommandeCreate, "trans" => "creation",     "idx" => "showCommandeCreate"),
                                 2 => array( "checked" => $showCommandeValid, "trans" => "validation",   "idx" => "showCommandeValid"),
                                 3 => array( "checked" => $showCommandeCloture, "trans" => "cloture",      "idx" => "showCommandeCloture"),
                                 4 => array( "checked" => $showCommandeDate, "trans" => "commande",     "idx" => "showCommandeDate"),
                               )
             )
);

//var_dump($arrRes);
  //commande
    //Date création
    //Date validation
    //Date cloture
    //Date commande
    //statut
    //ref
if ($showCommande)
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
                       note_public,
                       date_livraison
                   FROM ".MAIN_DB_PREFIX."commande
                   WHERE fk_soc =
    " . $socid;
        $resql = $db->query($requete);
        $id=0;
        if ($resql)
        {
            while($res=$db->fetch_object($resql))
            {
                $url = $dolibarr_main_url_root."/commande/fiche.php?id=".$res->rowid;
                $url = "";
                if ($res->date_creation && $showCommandeCreate)
                {
                   $arrRes = $BCalc->pushDateArr(
                                          $res->date_creation,
                                          "Créat. de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Cr&eacute;ation de la commande" . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "commande",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
                }
                if ($res->date_commande && $showCommandeDate)
                {
                    $arrRes = $BCalc->pushDateArr(
                                          $res->date_commande,
                                          "Date Com " . "".$res->ref."" . " (".$soc->nom.")",
                                          "commande " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "commande",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
                }
                if ($res->date_valid && $showCommandeValid)
                {
                    $arrRes = $BCalc->pushDateArr(
                                          $res->date_valid,
                                          "Valid de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Validation de la commande " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "commande",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
                }
                if ($res->date_cloture && $showCommandeCreate)
                {
                    $arrRes = $BCalc->pushDateArr(
                                          $res->date_cloture,
                                          "Clot de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Cloture commande " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "commande",
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