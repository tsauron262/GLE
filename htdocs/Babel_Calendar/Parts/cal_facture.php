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



/* show Facture Filter */

$showFacture = $_REQUEST['showFacture']=='on'?true:false;
$showFactureCreate=$_REQUEST['showFactureCreate']?true:false;
$showFactureValid =$_REQUEST['showFactureValid']?true:false;
$showFactureDate =$_REQUEST['showFactureDate']?true:false;
$showFactureDateLim=$_REQUEST['showFactureDateLim']?true:false;

array_push($arrFilter,
        array(  "name" => "Facture" ,
                "data" => array( 0 => array( "checked" => $showFacture, "trans"=>"tous/aucun"  ) ,
                                 1 => array( "checked" => $showFactureCreate, "trans" => "creation",     "idx" => "showFactureCreate"),
                                 2 => array( "checked" => $showFactureValid, "trans" => "validation",   "idx" => "showFactureValid"),
                                 3 => array( "checked" => $showFactureDate, "trans" => "dateFacture",  "idx" => "showFactureDate"),
                                 4 => array( "checked" => $showFactureDateLim, "trans" => "limite de r&eacute;glement", "idx" => "showFactureDateLim"),
                               )
             )
);


  //facture
    //Date création
    //Date facture
    //Date lim reglement
    //Date de payment
    //statut
    //ref
if ($showFacture){

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
                  FROM ".MAIN_DB_PREFIX."facture
                 WHERE fk_soc = ".$socid;


        $resql = $db->query($requete);
        $id=0;
        if ($resql)
        {
            while($res=$db->fetch_object($resql))
            {
               $url = $dolibarr_main_url_root."/compta/facture.php?facid=".$res->rowid;
                if ($res->datec && $showFactureCreate)
                {
                   $arrRes = $BCalc->pushDateArr(
                                          $res->datec,
                                          "Créat. de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Cr&eacute;ation de la facture" . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "facture",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
                }
                if ($res->datef && $showFactureDate)
                {
                    $arrRes = $BCalc->pushDateArr(
                                          $res->datef,
                                          "Date fact " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Facture " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "facture",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
                }
                if ($res->date_valid && $showFactureValid)
                {
                    $arrRes = $BCalc->pushDateArr(
                                          $res->date_valid,
                                          "Valid de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Validation de la facture " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "facture",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
                }
                if ($res->date_lim_reglement && $showFactureDateLim)
                {
                    $arrRes = $BCalc->pushDateArr(
                                          $res->date_lim_reglement,
                                          "Lim de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Date limite du réeacute;glement de la facture " . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "facture",
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