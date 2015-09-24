<?php
/*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 28 avr. 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : calDatas-json_response.php
  * GLE-1.2
  */

  require_once('../../main.inc.php');
  $start = $_REQUEST['start'];
  $end = $_REQUEST['end'];
  $type = $_REQUEST['type'];
  $id = $_REQUEST['id'];

    $arr = array();
    $arrColor = array('#D40808','#DF8713','#89DF00','#08D490','#D40890','#08D408','#9008D4','#90D408','#0808D4','#D49008', '#0890D4');

//1 Affaire
//2 Elements
//3 users

//TODO legende pour les affaire
if ($type == 'Affaire' && $id > 0)
{
    $requete = "SELECT UNIX_TIMESTAMP(date_format(str_to_date(value_affaire, '%d/%m/%Y %H:%i'), '%Y-%m-%d %H:%i')) as dateAff,
                       babel_affaire_template_value_view.id,
                       babel_affaire_template_value_view.description,
                       babel_affaire_template_value_view.affaire_id,
                       ".MAIN_DB_PREFIX."Synopsis_Affaire.ref
                  FROM babel_affaire_template_value_view,
                       ".MAIN_DB_PREFIX."Synopsis_Affaire
                 WHERE type_affaire = 'date'
                   AND value_affaire <> ''
                   AND UNIX_TIMESTAMP(date_format(str_to_date(value_affaire, '%d/%m/%Y %H:%i'), '%Y-%m-%d %H:%i')) BETWEEN ".$start." AND ".$end."
                   AND ".MAIN_DB_PREFIX."Synopsis_Affaire.id = babel_affaire_template_value_view.affaire_id";
    if($id > 0) $requete .= " AND ".MAIN_DB_PREFIX."Synopsis_Affaire.id = ".$id;
    $requete .= " ORDER BY affaire_id";
//print $requete;
    $sql = $db->query($requete);
    $iter = 0;
    $remAffaire = false;
    while($res = $db->fetch_object($sql))
    {
        $arr[]=array('id'=>$res->id,
                   'title'=>utf8_encode($res->ref." - ".$res->description),
                   'allDay' => false,
                   'start' => $res->dateAff,
                   'end'=> $res->dateAff + 3600,
                   'editable' => false,
                   'color'=> $arrColor[0],
                   'url' => DOL_URL_ROOT.'/Synopsis_Affaire/card.php?id='.$res->affaire_id
                   );
        if($res->affaire_id != $remAffaire && $remAffaire)
            $iter++;
        if($iter > 10) $iter = 0;
        $remAffaire = $res->affaire_id;
    }
        $requete1 = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Affaire_Element WHERE affaire_refid = ".$id;
        $sql1 = $db->query($requete1);
        while($res1=$db->fetch_object($sql1))
        {
            switch($res1->type){
                case 'contrat':{
                    $requete = "SELECT rowid,
                                       ref,
                                       UNIX_TIMESTAMP(date_contrat) as dc,
                                       UNIX_TIMESTAMP(mise_en_service) as dm,
                                       UNIX_TIMESTAMP(fin_validite) as dfv,
                                       UNIX_TIMESTAMP(date_cloture) as dcl,
                                       UNIX_TIMESTAMP(date_valid) as dv
                                  FROM ".MAIN_DB_PREFIX."contrat
                                 WHERE rowid = ".$res1->element_id;
                    $sql2 = $db->query($requete);
                    while($res2 = $db->fetch_object($sql2))
                    {
                        $color = $arrColor[1];
                        if($res2->dc >0)
                        $arr[]=array('id'=>"c-dc-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date contrat"),
                                   'allDay' => true,
                                   'start' => $res2->dc,
                                   'end'=> $res2->dc,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/contrat/card.php?id='.$res1->element_id
                                   );
                        if($res2->dm >0)
                        $arr[]=array('id'=>"c-dm-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - mise en service"),
                                   'allDay' => true,
                                   'start' => $res2->dm,
                                   'end'=> $res2->dm,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/contrat/card.php?id='.$res1->element_id
                                   );
                        if($res2->dfv >0)
                        $arr[]=array('id'=>"c-dfv-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - ")."date fin validité",
                                   'allDay' => true,
                                   'start' => $res2->dfv,
                                   'end'=> $res2->dfv,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/contrat/card.php?id='.$res1->element_id
                                   );
                        if($res2->dv >0)
                        $arr[]=array('id'=>"c-dv-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." validité",
                                   'allDay' => true,
                                   'start' => $res2->dv,
                                   'end'=> $res2->dv,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/contrat/card.php?id='.$res1->element_id
                                   );
                    }
                }
                break;
//TODO where date between
                case 'commande':{
                    $color=$arrColor[2];
                    $requete = "SELECT rowid,
                                       ref,
                                       UNIX_TIMESTAMP(date_commande) as dc,
                                       UNIX_TIMESTAMP(logistique_date_dispo) as dl,
                                       UNIX_TIMESTAMP(date_livraison) as dliv,
                                       UNIX_TIMESTAMP(date_cloture) as dcl,
                                       UNIX_TIMESTAMP(date_valid) as dv
                                  FROM ".MAIN_DB_PREFIX."commande
                                 WHERE rowid = ".$res1->element_id;
                    $sql2 = $db->query($requete);
                    while($res2 = $db->fetch_object($sql2))
                    {
                        if($res2->dc >0)
                        $arr[]=array('id'=>"co-dc-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date commande"),
                                   'allDay' => true,
                                   'start' => $res2->dc,
                                   'end'=> $res2->dc,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/commande/card.php?id='.$res1->element_id
                                   );
                        if($res2->dl >0)
                        $arr[]=array('id'=>"co-dl-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date dispo logistique"),
                                   'allDay' => true,
                                   'start' => $res2->dl,
                                   'end'=> $res2->dl,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/commande/card.php?id='.$res1->element_id
                                   );
                        if($res2->dliv >0)
                        $arr[]=array('id'=>"co-dliv-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date livraison"),
                                   'allDay' => true,
                                   'start' => $res2->dliv,
                                   'end'=> $res2->dliv,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/commande/card.php?id='.$res1->element_id
                                   );
                        if($res2->dcl >0)
                        $arr[]=array('id'=>"co-dcl-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date ")."clôture",
                                   'allDay' => true,
                                   'start' => $res2->dcl,
                                   'end'=> $res2->dcll,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/commande/card.php?id='.$res1->element_id
                                   );
                        if($res2->dv >0)
                        $arr[]=array('id'=>"co-dv-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." validation",
                                   'allDay' => true,
                                   'start' => $res2->dv,
                                   'end'=> $res2->dv,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/commande/card.php?id='.$res1->element_id
                                   );
                    }
                }
                break;
                case 'commande fournisseur':{
                    $color=$arrColor[3];
                    $requete = "SELECT rowid,
                                       ref,
                                       UNIX_TIMESTAMP(date_commande) as dc,
                                       UNIX_TIMESTAMP(date_cloture) as dcl,
                                       UNIX_TIMESTAMP(date_valid) as dv
                                  FROM ".MAIN_DB_PREFIX."commande_fournisseur
                                 WHERE rowid = ".$res1->element_id;
                    $sql2 = $db->query($requete);
                    while($res2 = $db->fetch_object($sql2))
                    {
                        if($res2->dc >0)
                        $arr[]=array('id'=>"cof-dc-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date commande fournisseur"),
                                   'allDay' => true,
                                   'start' => $res2->dc,
                                   'end'=> $res2->dc,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/commande/card.php?id='.$res1->element_id
                                   );
                        if($res2->dcl >0)
                        $arr[]=array('id'=>"cof-dcl-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date ")."clôture commande fournisseur",
                                   'allDay' => true,
                                   'start' => $res2->dcl,
                                   'end'=> $res2->dcl,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/commande/card.php?id='.$res1->element_id
                                   );
                        if($res2->dv >0)
                        $arr[]=array('id'=>"cof-dv-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." validation commande fournisseur",
                                   'allDay' => true,
                                   'start' => $res2->dv,
                                   'end'=> $res2->dv,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/commande/card.php?id='.$res1->element_id
                                   );
                    }
                }
                break;
                case 'expedition':{
                    $color=$arrColor[4];
                    $requete = "SELECT rowid,
                                       ref,
                                       UNIX_TIMESTAMP(date_expedition) as dc,
                                       UNIX_TIMESTAMP(date_valid) as dv
                                  FROM ".MAIN_DB_PREFIX."expedition
                                 WHERE rowid = ".$res1->element_id;
                    $sql2 = $db->query($requete);
                    while($res2 = $db->fetch_object($sql2))
                    {
                        if($res2->dc >0)
                        $arr[]=array('id'=>"e-dc-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." expédition",
                                   'allDay' => true,
                                   'start' => $res2->dc,
                                   'end'=> $res2->dc,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/expedition/card.php?id='.$res1->element_id
                                   );
                        if($res2->dv >0)
                        $arr[]=array('id'=>"e-dv-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." validation expédition",
                                   'allDay' => true,
                                   'start' => $res2->dv,
                                   'end'=> $res2->dv,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/expedition/card.php?id='.$res1->element_id
                                   );
                    }
                }
                break;
                case 'facture':{
                    $color=$arrColor[5];
                    $requete = "SELECT f.rowid,
                                       f.facnumber as ref,
                                       f.UNIX_TIMESTAMP(datef) as dc,
                                       f.UNIX_TIMESTAMP(date_valid) as dv,
                                       f.UNIX_TIMESTAMP(date_lim_reglement) as dlr
                                  FROM ".MAIN_DB_PREFIX."facture as f
                                 WHERE rowid = ".$res1->element_id;
                    $sql2 = $db->query($requete);
                    while($res2 = $db->fetch_object($sql2))
                    {
                        if($res2->dc >0)
                        $arr[]=array('id'=>"f-dc-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." facture",
                                   'allDay' => true,
                                   'start' => $res2->dc,
                                   'end'=> $res2->dc,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/facture.php?id='.$res1->element_id
                                   );
                        if($res2->dv >0)
                        $arr[]=array('id'=>"f-dv-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." validation facture",
                                   'allDay' => true,
                                   'start' => $res2->dv,
                                   'end'=> $res2->dv,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/facture.php?id='.$res1->element_id
                                   );
                        if($res2->dlr >0)
                        $arr[]=array('id'=>"f-dlr-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." limite règlement",
                                   'allDay' => true,
                                   'start' => $res2->datef,
                                   'end'=> $res2->dlr,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/facture.php?id='.$res1->element_id
                                   );
                        $requete= "SELECT UNIX_TIMESTAMP(datep) as dc
                                     FROM ".MAIN_DB_PREFIX."paiement_facture as pf,
                                          ".MAIN_DB_PREFIX."paiement as p
                                    WHERE p.rowid = pf.fk_paiement
                                      AND pf.fk_facture = ".$res1->element_id;
                        $sql3 = $db->query($requete);
                        while($res3 = $db->fetch_object($sql3))
                        {
                            if($res3->dc >0)
                            $arr[]=array('id'=>"f-dlr-".$res2->rowid,
                                       'title'=>utf8_encode($res2->ref." - paiement"),
                                       'allDay' => true,
                                       'start' => $res3->dc,
                                       'end'=> $res3->dc,
                                       'editable' => false,
                                       'color'=> $color,
                                       'url' => DOL_URL_ROOT.'/facture.php?id='.$res1->element_id
                                       );

                        }
                    }
                }
                break;
                case 'facture fournisseur':{
                    $color=$arrColor[6];
                    $requete = "SELECT f.rowid,
                                       f.facnumber as ref,
                                       UNIX_TIMESTAMP(f.datef) as dc,
                                       UNIX_TIMESTAMP(f.date_valid) as dv,
                                       UNIX_TIMESTAMP(f.date_lim_reglement) as dlr
                                  FROM ".MAIN_DB_PREFIX."facture_fourn as f
                                 WHERE rowid = ".$res1->element_id;
                    $sql2 = $db->query($requete);
                    while($res2 = $db->fetch_object($sql2))
                    {
                        if($res2->dc >0)
                        $arr[]=array('id'=>"ff-dc-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." facture fournisseur",
                                   'allDay' => true,
                                   'start' => $res2->dc,
                                   'end'=> $res2->dc,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/fourn/facture/card.php?id='.$res1->element_id
                                   );
                        if($res2->dv >0)
                        $arr[]=array('id'=>"ff-dv-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." validation facture fournisseur",
                                   'allDay' => true,
                                   'start' => $res2->dv,
                                   'end'=> $res2->dv,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/fourn/facture/card.php?id='.$res1->element_id
                                   );
                        if($res2->dlr >0)
                        $arr[]=array('id'=>"ff-dlr-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." limite règlement facture fournisseur",
                                   'allDay' => true,
                                   'start' => $res2->datef,
                                   'end'=> $res2->dlr,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/fourn/facture/card.php?id='.$res1->element_id
                                   );
                        $requete= "SELECT UNIX_TIMESTAMP(datep) as dc
                                     FROM ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf,
                                          ".MAIN_DB_PREFIX."paiementfourn as p
                                    WHERE p.rowid = pf.fk_paiementfourn
                                      AND pf.fk_facturefourn = ".$res1->element_id;
                        $sql3 = $db->query($requete);
                        while($res3 = $db->fetch_object($sql3))
                        {
                            if($res3->dc >0)
                            $arr[]=array('id'=>"f-dlr-".$res2->rowid,
                                       'title'=>utf8_encode($res2->ref." - paiement"),
                                       'allDay' => true,
                                       'start' => $res3->dc,
                                       'end'=> $res3->dc,
                                       'editable' => false,
                                       'color'=> $color,
                                       'url' => DOL_URL_ROOT.'/fourn/facture/card.php?id='.$res1->element_id
                                       );

                        }
                    }
                }
                break;
                case 'livraison':{
                    $color=$arrColor[7];
                    $requete = "SELECT f.rowid,
                                       f.ref,
                                       UNIX_TIMESTAMP(f.date_livraison) as dc,
                                       UNIX_TIMESTAMP(f.date_valid) as dv
                                  FROM ".MAIN_DB_PREFIX."livraison as f
                                 WHERE rowid = ".$res1->element_id;
                    $sql2 = $db->query($requete);
                    while($res2 = $db->fetch_object($sql2))
                    {
                        if($res2->dc >0)
                        $arr[]=array('id'=>"liv-dc-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." livraison",
                                   'allDay' => true,
                                   'start' => $res2->dc,
                                   'end'=> $res2->dc,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/livraison/card.php?id='.$res1->element_id
                                   );
                        if($res2->dv >0)
                        $arr[]=array('id'=>"liv-dv-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." validation livraison",
                                   'allDay' => true,
                                   'start' => $res2->dv,
                                   'end'=> $res2->dv,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/livraison/card.php?id='.$res1->element_id
                                   );
                        }
                }
                break;
                case 'projet':{
                    $color=$arrColor[8];
                    $requete = "SELECT f.rowid,
                                       f.ref,
                                       UNIX_TIMESTAMP(f.dateo) as dc,
                                       UNIX_TIMESTAMP(f.date_valid) as dv,
                                       UNIX_TIMESTAMP(f.date_launch) as dl,
                                       UNIX_TIMESTAMP(f.date_cloture) as dcl
                                  FROM ".MAIN_DB_PREFIX."Synopsis_projet_view as f
                                 WHERE rowid = ".$res1->element_id;
                    $sql2 = $db->query($requete);
                    while($res2 = $db->fetch_object($sql2))
                    {
                        if($res2->dc >0)
                        $arr[]=array('id'=>"proj-dc-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." ouverture projet",
                                   'allDay' => true,
                                   'start' => $res2->dc,
                                   'end'=> $res2->dc,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/projet/card.php?id='.$res1->element_id
                                   );
                        if($res2->dv >0)
                        $arr[]=array('id'=>"proj-dv-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." validation projet",
                                   'allDay' => true,
                                   'start' => $res2->dv,
                                   'end'=> $res2->dv,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/projet/card.php?id='.$res1->element_id
                                   );
                        if($res2->dl >0)
                        $arr[]=array('id'=>"proj-dl-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." lancement projet",
                                   'allDay' => true,
                                   'start' => $res2->dl,
                                   'end'=> $res2->dl,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/projet/card.php?id='.$res1->element_id
                                   );
                        if($res2->dcl >0)
                        $arr[]=array('id'=>"proj-dv-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." clôture projet",
                                   'allDay' => true,
                                   'start' => $res2->dcl,
                                   'end'=> $res2->dcl,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/projet/card.php?id='.$res1->element_id
                                   );
                        }
                }
                break;
                case 'propale':{
                    $color=$arrColor[9];
                    $requete = "SELECT f.rowid,
                                       f.ref,
                                       UNIX_TIMESTAMP(f.datep) as dc,
                                       UNIX_TIMESTAMP(f.date_valid) as dv,
                                       UNIX_TIMESTAMP(f.date_abandon) as da,
                                       UNIX_TIMESTAMP(f.fin_validite) as dfv,
                                       UNIX_TIMESTAMP(f.date_cloture) as dcl,
                                       UNIX_TIMESTAMP(f.date_demandeValid) as ddv,
                                       UNIX_TIMESTAMP(f.date_devis_fourn) as ddf,
                                       UNIX_TIMESTAMP(f.date_livraison) as dliv
                                  FROM ".MAIN_DB_PREFIX."propal as f
                                 WHERE rowid = ".$res1->element_id;
                    $sql2 = $db->query($requete);
                    while($res2 = $db->fetch_object($sql2))
                    {
                        if($res2->dc >0)
                        $arr[]=array('id'=>"prop-dc-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." proposition commerciale",
                                   'allDay' => true,
                                   'start' => $res2->dc,
                                   'end'=> $res2->dc,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/comm/propal.php?propalid='.$res1->element_id
                                   );
                        if($res2->dv >0)
                        $arr[]=array('id'=>"prop-dv-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." validation proposition commerciale",
                                   'allDay' => true,
                                   'start' => $res2->dv,
                                   'end'=> $res2->dv,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/comm/propal.php?propalid='.$res1->element_id
                                   );
                        if($res2->dl >0)
                        $arr[]=array('id'=>"prop-da-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." abandon proposition commerciale",
                                   'allDay' => true,
                                   'start' => $res2->da,
                                   'end'=> $res2->da,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/comm/propal.php?propalid='.$res1->element_id
                                   );
                        if($res2->dfv >0)
                        $arr[]=array('id'=>"prop-dfv-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." validité proposition commerciale",
                                   'allDay' => true,
                                   'start' => $res2->dc,
                                   'end'=> $res2->dfv,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/comm/propal.php?propalid='.$res1->element_id
                                   );
                        if($res2->dcl >0)
                        $arr[]=array('id'=>"prop-dcl-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." clôture  proposition commerciale",
                                   'allDay' => true,
                                   'start' => $res2->dcl,
                                   'end'=> $res2->dcl,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/comm/propal.php?propalid='.$res1->element_id
                                   );
                        if($res2->ddv >0)
                        $arr[]=array('id'=>"prop-ddv-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." demande de validation  proposition commerciale",
                                   'allDay' => true,
                                   'start' => $res2->ddv,
                                   'end'=> $res2->ddv,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/comm/propal.php?propalid='.$res1->element_id
                                   );
                        if($res2->dliv >0)
                        $arr[]=array('id'=>"prop-dliv-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." livraison  proposition commerciale",
                                   'allDay' => true,
                                   'start' => $res2->dliv,
                                   'end'=> $res2->dliv,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/comm/propal.php?propalid='.$res1->element_id
                                   );
                        if($res2->ddf >0)
                        $arr[]=array('id'=>"prop-ddf-".$res2->rowid,
                                   'title'=>utf8_encode($res2->ref." - date")." devis fournisseur -  proposition commerciale",
                                   'allDay' => true,
                                   'start' => $res2->ddf,
                                   'end'=> $res2->ddf,
                                   'editable' => false,
                                   'color'=> $color,
                                   'url' => DOL_URL_ROOT.'/comm/propal.php?propalid='.$res1->element_id
                                   );
                        }
//date propale
//date validation
//date cloture
//date revision ?
//date date limite => duree validite
                }
                break;
            }
//TODO legende et couleur
//TODO anim chargement
    }
} else if ($type == 'Affaire')
{
    $requete = "SELECT UNIX_TIMESTAMP(date_format(str_to_date(value_affaire, '%d/%m/%Y %H:%i'), '%Y-%m-%d %H:%i')) as dateAff,
                       babel_affaire_template_value_view.id,
                       babel_affaire_template_value_view.description,
                       babel_affaire_template_value_view.affaire_id,
                       ".MAIN_DB_PREFIX."Synopsis_Affaire.ref
                  FROM babel_affaire_template_value_view,
                       ".MAIN_DB_PREFIX."Synopsis_Affaire
                 WHERE type_affaire = 'date'
                   AND value_affaire <> ''
                   AND UNIX_TIMESTAMP(date_format(str_to_date(value_affaire, '%d/%m/%Y %H:%i'), '%Y-%m-%d %H:%i')) BETWEEN ".$start." AND ".$end."
                   AND ".MAIN_DB_PREFIX."Synopsis_Affaire.id = babel_affaire_template_value_view.affaire_id";
    if($id > 0) $requete .= " AND ".MAIN_DB_PREFIX."Synopsis_Affaire.id = ".$id;
    $requete .= " ORDER BY affaire_id";
//print $requete;
    $sql = $db->query($requete);
    $iter = 0;
    $remAffaire = false;
    while($res = $db->fetch_object($sql))
    {
        $color = $arrColor[$iter];
        $arr[]=array('id'=>$res->id,
                   'title'=>utf8_encode($res->ref." - ".$res->description),
                   'allDay' => false,
                   'start' => $res->dateAff,
                   'end'=> $res->dateAff + 3600,
                   'editable' => false,
                   'color'=> $color,
                   'url' => DOL_URL_ROOT.'/Synopsis_Affaire/card.php?id='.$res->affaire_id
                   );
        if($res->affaire_id != $remAffaire && $remAffaire)
            $iter++;
        if($iter > 10) $iter = 0;
        $remAffaire = $res->affaire_id;
    }
} else {
  $arr[]=array('id'=>1,
               'title'=>'test',
               'allDay' => false,
               'start' => date('U'),
               'end'=> date('U')+7200,
               'editable' => true,
               'color'=> '#00ff00',
               );
  $arr[]=array('id'=>2,
               'title'=>'test',
               'allDay' => false,
               'start' => date('U') - 24*3600,
               'end'=> date('U') -24*3600  +7200,
               'editable' => true,
               'color'=> '#00ffff',
               );
  $arr[]=array('id'=>3,
               'title'=>'test',
               'allDay' => false,
               'start' => date('U') + 24*3600,
               'end'=> date('U') + 24*3600 + 7200,
               'editable' => true,
               'color'=> '#00ffff',
               );
}
echo json_encode($arr);
?>