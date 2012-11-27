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



  //livraison
    //Date création
    //Date validation


/* show livraison Filter */

$showLivraison = $_REQUEST['showLivraison']=='on'?true:false;
$showLivraisonCreate = $_REQUEST['showLivraisonCreate']=='on'?true:false;
$showLivraisonValid = $_REQUEST['showLivraisonValid']=='on'?true:false;
$showLivraisonCloture = $_REQUEST['showLivraisonCloture']=='on'?true:false;
$showLivraisonDate = $_REQUEST['showLivraisonDate']=='on'?true:false;


array_push($arrFilter,
        array(  "name" => "Livraison" ,
                "data" => array( 0 => array( "checked" => $showLivraison, "trans"=>"tous/aucun"  ) ,
                                 1 => array( "checked" => $showLivraisonCreate, "trans" => "creation",     "idx" => "showLivraisonCreate"),
                                 2 => array( "checked" => $showLivraisonValid, "trans" => "validation",   "idx" => "showLivraisonValid"),
                                 3 => array( "checked" => $showLivraisonCloture, "trans" => "cloture",      "idx" => "showLivraisonCloture"),
                                 4 => array( "checked" => $showLivraisonDate, "trans" => "commande",     "idx" => "showLivraisonDate"),
                               )
             )
);

$requete = "SELECT
                    rowid,
                    fk_expedition,
                    ref,
                    fk_soc,
                    date_creation,
                    date_valid,
                    fk_user_author,
                    fk_user_valid,
                    fk_statut,
                    note,
                    note_public,
                    date_livraison
                FROM
                    ".MAIN_DB_PREFIX."livraison
                WHERE fk_soc = ".$socid;

    $id=0;
    if ($resql)
    {
        while($res=$db->fetch_object($resql))
        {
            $rul = $dolibarr_main_url_root . DOL_URL_ROOT."/fourn/livraison/fiche.php?id=".$res->rowid;
            if ($res->date_creation && $showLivraisonCreate)
            {
               $arrRes = $BCalc->pushDateArr(
                                      $res->date_creation,
                                      "Créat. de " . "".$res->ref."" . " (".$soc->nom.")",
                                      "Cr&eacute;ation de la livraison" . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                      $res->ref,
                                      $id,
                                      "livraison",
                                      1,
                                      "",
                                      1,
                                      'null',
                                          $url);
                             $id++;
            }
            if ($res->date_livraison && $showLivraisonDate)
            {
                $arrRes = $BCalc->pushDateArr(
                                      $res->date_livraison,
                                      "Date Liv " . "".$res->ref."" . " (".$soc->nom.")",
                                      "Livraison  " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                      $res->ref,
                                      $id,
                                      "livraison",
                                      1,
                                      "",
                                      1,
                                      'null',
                                          $url);
                             $id++;
            }
            if ($res->date_valid && $showLivraisonValid)
            {
                $arrRes = $BCalc->pushDateArr(
                                      $res->date_valid,
                                      "Valid de " . "".$res->ref."" . " (".$soc->nom.")",
                                      "Validation de la livraison " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                      $res->ref,
                                      $id,
                                      "livraison",
                                      1,
                                      "",
                                      1,
                                      'null',
                                          $url);
                             $id++;
            }
            if ($res->date_cloture && $showLivraisonCloture)
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



?>