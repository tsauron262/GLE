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

/* show Contrat Filter */

$showContrat = $_REQUEST['showContrat']=='on'?true:false;
$showContratCreate = $_REQUEST['showContratCreate']=='on'?true:false;
$showContratValid = $_REQUEST['showContratValid']=='on'?true:false;
$showContratFinValid = $_REQUEST['showContratFinValid']=='on'?true:false;
$showContratDate = $_REQUEST['showContratDate']=='on'?true:false;
$showContratDateClot = $_REQUEST['showContratDateClot']=='on'?true:false;
$showContratDetDateOuvPerv = $_REQUEST['showContratDetDateOuvPerv']=='on'?true:false;
$showContratDateOuv = $_REQUEST['showContratDateOuv']=='on'?true:false;
$showContratDetFinValid = $_REQUEST['showContratDetFinValid']=='on'?true:false;
$showContratDetDateClot = $_REQUEST['showContratDetDateClot']=='on'?true:false;
$showContratDureeService = $_REQUEST['showContratDureeService']=='on'?true:false;

array_push($arrFilter,
        array(  "name" => "Contrat" ,
                "data" => array( 0 => array( "checked" => $showContrat, "trans"=>"tous/aucun"  ) ,
                                 1 => array( "checked" => $showContratCreate, "trans" => "creation",     "idx" => "showContratCreate"),
                                 2 => array( "checked" => $showContratValid, "trans" => "mise en service",   "idx" => "showContratValid"),
                                 3 => array( "checked" => $showContratFinValid, "trans" => "fin de validit&eacute;",     "idx" => "showContratFinValid"),
                                 4 => array( "checked" => $showContratDate, "trans" => "contrat",     "idx" => "showContratDate"),
                                 5 => array( "checked" => $showContratDateClot, "trans" => "cloture",     "idx" => "showContratDateClot"),
                                 6 => array( "checked" => $showContratDetDateOuvPerv, "trans" => "service pr&eacute;vue",     "idx" => "showContratDetDateOuvPerv"),
                                 7 => array( "checked" => $showContratDateOuv, "trans" => "service ouvert",     "idx" => "showContratDateOuv"),
                                 8 => array( "checked" => $showContratDetFinValid, "trans" => "fin de validit&eacute; service",     "idx" => "showContratDetFinValid"),
                                 9 => array( "checked" => $showContratDetDateClot, "trans" => "cloture service",     "idx" => "showContratDetDateClot"),
                                10 => array( "checked" => $showContratDureeService, "trans" => "dur&eacute;e service",     "idx" => "showContratDureeService"),
                               )
             )
);

  //Contrat
    //date creation datec
    //date_contrat
    //statut
    //mise en service
    //fin validite
    //date_cloture
    //note
    //note public
    //ref

$requete = "SELECT
        ".MAIN_DB_PREFIX."contrat.rowid,
        ".MAIN_DB_PREFIX."contrat.ref,
        ".MAIN_DB_PREFIX."contrat.datec,
        ".MAIN_DB_PREFIX."contrat.date_contrat,
        ".MAIN_DB_PREFIX."contrat.statut,
        ".MAIN_DB_PREFIX."contrat.mise_en_service,
        ".MAIN_DB_PREFIX."contrat.fin_validite,
        ".MAIN_DB_PREFIX."contrat.date_cloture,
        ".MAIN_DB_PREFIX."contrat.fk_soc,
        ".MAIN_DB_PREFIX."contrat.fk_commercial_signature,
        ".MAIN_DB_PREFIX."contrat.fk_commercial_suivi,
        ".MAIN_DB_PREFIX."contrat.fk_user_author,
        ".MAIN_DB_PREFIX."contrat.fk_user_mise_en_service,
        ".MAIN_DB_PREFIX."contrat.fk_user_cloture,
        ".MAIN_DB_PREFIX."contrat.note,
        ".MAIN_DB_PREFIX."contrat.note_public
    FROM
        ".MAIN_DB_PREFIX."contrat
    WHERE ".MAIN_DB_PREFIX."contrat.fk_soc = " .$socid;

$resql = $db->query($requete);
if ($resql)
{
    while ($res = $db->fetch_object($resql))
    {


        $url = $dolibarr_main_url_root."/contrat/fiche.php?id=".$res->rowid;
        if ($res->datec && $showContratCreate)
        {
            $arrRes = $BCalc->pushDateArr(
                                          $res->datec,
                                          "Créat. de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Cr&eacute;ation du contrat" . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "contrat",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
        }
        if ($res->date_contrat && $showContratDate)
        {
            $arrRes = $BCalc->pushDateArr(
                                          $res->date_contrat,
                                          "Créat. de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Cr&eacute;ation du contrat" . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "contrat",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
        }
        if ($res->mise_en_service && $showContratValid)
        {
            $arrRes = $BCalc->pushDateArr(
                                          $res->mise_en_service,
                                          "Mise en serv. de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Mise en service du contrat" . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "contrat",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
        }
        if ($res->fin_validite && $showContratFinValid)
        {
            $arrRes = $BCalc->pushDateArr(
                                          $res->fin_validite,
                                          "Fin de valid. " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Fin de validit&eacute; du contrat" . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "contrat",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
        }
        if ($res->date_cloture && $showContratDateClot)
        {
            $arrRes = $BCalc->pushDateArr(
                                          $res->date_cloture,
                                          "Clot. de " . "".$res->ref."" . " (".$soc->nom.")",
                                          "Cloture du contrat" . $res->ref . "<BR><P>" . $res->note. "<BR><P>" . $res->note_public,
                                          $res->ref,
                                          $id,
                                          "contrat",
                                          1,
                                          "",
                                          1,
                                          'null',
                                          $url);
                                 $id++;
        }

        $requete1= "SELECT
                        ".MAIN_DB_PREFIX."contratdet.statut,
                        ".MAIN_DB_PREFIX."contratdet.label,
                        ".MAIN_DB_PREFIX."contratdet.description,
                        ".MAIN_DB_PREFIX."contratdet.date_commande,
                        ".MAIN_DB_PREFIX."contratdet.date_ouverture_prevue,
                        ".MAIN_DB_PREFIX."contratdet.date_ouverture,
                        ".MAIN_DB_PREFIX."contratdet.date_fin_validite,
                        ".MAIN_DB_PREFIX."contratdet.date_cloture,
                        ".MAIN_DB_PREFIX."contratdet.fk_user_author,
                        ".MAIN_DB_PREFIX."contratdet.fk_user_ouverture,
                        ".MAIN_DB_PREFIX."contratdet.fk_user_cloture,
                        ".MAIN_DB_PREFIX."contratdet.commentaire
                   FROM ".MAIN_DB_PREFIX."contratdet ".MAIN_DB_PREFIX."contratdet
                  WHERE ".MAIN_DB_PREFIX."contratdet.fk_contrat =". $res->rowid;
        $resql1 = $db->query($requete1);
        if ($resql1)
        {
            while ($res1 = $db->fetch_object($resql1))
            {
                if ($res1->date_ouverture_prevue && $showContratDetDateOuvPerv)
                {
                    $arrRes = $BCalc->pushDateArr(
                                                  $res->date_ouverture_prevue,
                                                  "Prev. ouver. de " . "".$res1->label."" . " (".$soc->nom.")",
                                                  "Pr&eacute;visionel de l'ouverture du service " . $res1->label . "<BR>Contrat :".$res->ref."<P>" . $res1->commentaire,
                                                  $res1->label,
                                                  $id,
                                                  "contratdet",
                                                  1,
                                                  "",
                                                  1,
                                                  'null',
                                                  $url);
                                         $id++;
                }
                if ($res1->date_ouverture && $showContratDateOuv)
                {
                    $arrRes = $BCalc->pushDateArr(
                                                  $res1->date_ouverture,
                                                  "Ouvert. de " . "".$res1->label."" . " (".$soc->nom.")",
                                                  "Ouverture du service : " . $res1->label . "<BR>Contrat :".$res->ref."<P>" . $res1->commentaire,
                                                  $res1->label,
                                                  $id,
                                                  "contratdet",
                                                  1,
                                                  "",
                                                  1,
                                                  'null',
                                                  $url);
                                         $id++;
                }
                if ($res1->date_fin_validite && $showContratDetFinValid)
                {
                    $arrRes = $BCalc->pushDateArr(
                                                  $res1->date_fin_validite,
                                                  "Fin de valid. du serv. " . "".$res1->label."" . " (".$soc->nom.")",
                                                  "Fin du service : " . $res1->label . "<BR>Contrat :".$res->ref."<P>" . $res->commentaire,
                                                  $res1->label,
                                                  $id,
                                                  "contratdet",
                                                  1,
                                                  "",
                                                  1,
                                                  'null',
                                                  $url);
                                         $id++;
                }
                if ($res1->date_cloture && $showContratDetDateClot)
                {
                    $arrRes = $BCalc->pushDateArr(
                                                  $res1->date_cloture,
                                                  "Cloture du serv. " . "".$res1->label."" . " (".$soc->nom.")",
                                                  "Fin du service : " . $res1->label . "<BR>Contrat :".$res->ref."<P>" . $res->commentaire,
                                                  $res1->label,
                                                  $id,
                                                  "contratdet",
                                                  1,
                                                  "",
                                                  1,
                                                  'null',
                                                  $url);
                                         $id++;
                }


                if ($showContratDureeService)
                {
                    $dateDebut = false;
                    if ($res1->date_ouverture ."x" != "x")
                    {
                        $dateDebut = $res1->date_ouverture;
                    } else if ($res1->date_ouverture_prevue ."x" != "x"){
                        $dateDebut = $res1->date_ouverture_prevue;
                    }
                    $dateFin = false;
                    if ($res1->date_cloture ."x" != "x")
                    {
                        $dateFin = $res1->date_cloture;
                    } else if ($res1->date_fin_validite)
                    {
                        $dateFin = $res1->date_fin_validite;
                    }

                    if ($dateDebut && $dateFin )
                    {
                        $arrRes = $BCalc->pushDateArr(
                                                      array('debut' => $dateDebut, 'fin' => $dateFin),
                                                      "Test Prev. ouver. de " . "".$res1->description."" . " (".$soc->nom.")",
                                                      "Test Pr&eacute;visionel de l'ouverture du service " . $res1->description . "<BR>Contrat :".$res->ref."<P>" . $res1->commentaire,
                                                      "d".$res1->label,
                                                      "d".$id,
                                                      "contratdet",
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
    }

}
//print $requete;
//var_dump($arrRes);

?>