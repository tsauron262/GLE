<?php
/*
 * BIMP-ERP by Synopsis et DRSI
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



/* show FactureFourn Filter */

$showFactureFourn = $_REQUEST['showFactureFourn']=='on'?true:false;
$showFactureFournCreate=$_REQUEST['showFactureFournCreate']?true:false;
$showFactureFournValid =$_REQUEST['showFactureFournValid']?true:false;
$showFactureFournDate =$_REQUEST['showFactureFournDate']?true:false;
$showFactureFournDateLim=$_REQUEST['showFactureFournDateLim']?true:false;

array_push($arrFilter,
        array(  "name" => "FactureFourn" ,
                "data" => array( 0 => array( "checked" => $showFactureFourn, "trans"=>"tous/aucun"  ) ,
                                 1 => array( "checked" => $showFactureFournCreate, "trans" => "creation",     "idx" => "showFactureFournCreate"),
                                 2 => array( "checked" => $showFactureFournDate, "trans" => "dateFacture",   "idx" => "showFactureFournDate"),
                                 3 => array( "checked" => $showFactureFournDateLim, "trans" => "limite de r&eacute;glement",  "idx" => "showFactureFournDateLim"),
                               )
             )
);


  //FactureFourn
    //Date création
    //Date FactureFourn
    //Date lim reglement
    //Date de payment
    //statut
    //ref
if ($showFactureFourn){

    $requete = "SELECT rowid,
                       ref,
                       fk_soc,
                       datec,
                       datef,
                       date_valid,
                       paye,
                       fk_statut,
                       fk_user_author,
                       fk_user_valid,
                       date_lim_reglement,
                       note,
                       note_public
                  FROM ".MAIN_DB_PREFIX."facture_fourn
                 WHERE fk_soc = ".$socid;


        $resql = $db->query($requete);
        $id=0;
        if ($resql)
        {
            while($res=$db->fetch_object($resql))
            {
                $url = $dolibarr_main_url_root."/fourn/facture/card.php?facid=".$res->rowid;
                if ($res->datec && $showFactureFournCreate)
                {
                   $arrRes = $BCalc->pushDateArr(
                                          $res->datec,
                                          "Créat. de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Cr&eacute;ation de la facture fournisseur" . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "FactureFourn",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
                }
                if ($res->datef && $showFactureFournDate)
                {
                    $arrRes = $BCalc->pushDateArr(
                                          $res->datef,
                                          "Date fact fourn " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Facture fournisseur " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "FactureFourn",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
                }
                if ($res->date_valid && $showFactureFournValid)
                {
                    $arrRes = $BCalc->pushDateArr(
                                          $res->date_valid,
                                          "Valid de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Validation de la Facture Fournisseur " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "FactureFourn",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
                }
                if ($res->date_lim_reglement && $showFactureFournDateLim)
                {
                    $arrRes = $BCalc->pushDateArr(
                                          $res->date_lim_reglement,
                                          "Lim de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Date limite du réeacute;glement de la Facture fournisseur " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "FactureFourn",
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