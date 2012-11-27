<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 28 dec. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : diffInterv-html_response.php
  * GLE-1.2
  */


  //Affiche Le prevu et le realise par technicien par intervention et total + total HT


require_once('../../main.inc.php');
$id = $_REQUEST['id'];
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT."/Synopsis_DemandeInterv/demandeInterv.class.php");
$com = new Synopsis_Commande($db);
$html = new Form($db);
$res=$com->fetch($id);

$arrGrpCom = array($id=>$id);
$arrGrp = $com->listGroupMember(true);
foreach($arrGrp as $key=>$commandeMember)
{
      $arrGrpCom[$commandeMember->id]=$commandeMember->id;
}


if ($res>0)
{
      $requete =  "SELECT label
                     FROM ".MAIN_DB_PREFIX."Synopsis_fichinter_c_typeInterv
                    WHERE id in 
                        (SELECT fk_typeinterv 
                            FROM ".MAIN_DB_PREFIX."Synopsis_demandeInterv as d, 
                                ".MAIN_DB_PREFIX."Synopsis_demandeIntervdet as dt WHERE dt.fk_demandeInterv=d.rowid AND fk_commande IN (".join(',',$arrGrpCom)."))
                       OR id in 
                       (SELECT fk_typeinterv FROM 
                            ".MAIN_DB_PREFIX."Synopsis_fichinter as f, 
                            ".MAIN_DB_PREFIX."Synopsis_fichinterdet as ft WHERE ft.fk_fichinter=f.rowid AND fk_commande IN (".join(',',$arrGrpCom)."))";
      $sql = $db->query($requete);
      $arrLabel = array();
      while ($res = $db->fetch_object($sql))
      {
          $arrLabel[$res->label]=utf8_encode($res->label);
      }

      $requete="SELECT ifnull(d.fk_user_prisencharge,d.fk_user_target) as fk_user,
                       d.ref,
                       d.fk_statut,
                       dt.duree,
                       dt.total_ht,
                       t.label
                  FROM llx_Synopsis_demandeInterv as d,
                       llx_Synopsis_fichinter_c_typeInterv as t,
                       llx_Synopsis_demandeIntervdet as dt
                 WHERE d.fk_commande IN (".join(',',$arrGrpCom).")
                   AND dt.fk_demandeinterv = d.rowid
                   AND dt.fk_typeinterv = t.id ";
      $sql = $db->query($requete);
      $arr=array();
      while($res = $db->fetch_object($sql)){
          $arr[$res->fk_user]['DI'][utf8_encode($res->ref)][utf8_encode($res->label)]+=$res->total_ht;
      }

      $requete="SELECT f.fk_user_author as fk_user,
                       f.ref,
                       f.fk_statut,
                       ft.duree,
                       ft.total_ht,
                       t.label
                  FROM ".MAIN_DB_PREFIX."Synopsis_fichinter as f,
                       ".MAIN_DB_PREFIX."Synopsis_fichinter_c_typeInterv as t,
                       ".MAIN_DB_PREFIX."Synopsis_fichinterdet as ft
                 WHERE f.fk_commande IN (".join(',',$arrGrpCom).")
                   AND ft.fk_fichinter = f.rowid
                   AND ft.fk_typeinterv = t.id ";
      $sql = $db->query($requete);
      while($res = $db->fetch_object($sql)){
          $arr[$res->fk_user]['FI'][utf8_encode($res->ref)][utf8_encode($res->label)]+=$res->total_ht;
      }

      //meme cat ds FI et DI
      foreach($arr as $userid=>$val)
      {
            foreach($val as $type=>$val1)
          {
                foreach($val1 as $ref=>$val2)
              {
                foreach($arrLabel as $label)
                {
                    if (!$val2[$label])
                    {
                        $arr[$userid][$type][$ref][$label]=0;
                    }
                }
            }
        }
    }

    if (count($arr) > 0)
    {
        print "<table cellpadding=15>";
        foreach($arr as $userid=>$val)
        {
              $tmpUser = new User($db);
              $tmpUser->id = $userid;
              $tmpUser->fetch($userid);
              print "<tr><th colspan=1 class='ui-state-hover' style='color: white!important;'>".utf8_encode($tmpUser->getNomUrl(1));
              print "<tr><td class='ui-widget-content'>";
              print "        <table width=100% cellpadding=10>";
              $tmpArr1=array();
              $tmpArr2=array();
              $tmpArr3=array();
              $tmpArr4=array();
              $iter1=0;
              $iter2=0;
              foreach($val as $type=>$val1){
                  if ($type =='DI')
                  {
                      $totDIPerUser=0;
                      $colspan=0;
                      foreach($val1 as $ref=>$val2)
                      {
                          $tmpArr3=array();
                          $tmpArr3[]='<tr><th valign="top" colspan=2  class="ui-widget-header ui-state-default">'.$ref;
                          $total=0;
                          foreach($val2 as $label=>$totHT)
                          {
//                                if ($totHT > 0)
//                                {
                              $tmpArr3[]='<tr><td width=75 class="ui-widget-content">'.$label."<td class='ui-widget-content'>".price($totHT);
                              $total+= $totHT;
//                                }
                          }
                          $tmpArr3[]='<tr><td class="ui-widget-header ui-state-default">Total<td class="ui-widget-content">'.price($total);
                          $tmpArr1[]= '<td style="padding:0"><table width=100% cellpadding=10>' .join(' ',$tmpArr3)."</table>";
                          $totDIPerUser+=$total;
                          $colspan++;
                      }
                      $tmpArr1[]='<tr><td colspan="'.$colspan.'" style="padding:0"><table cellpadding=10 width=100%><tr><td width=75 class="ui-widget-header ui-state-focus">Total DI<td class="ui-widget-content">'.price($totDIPerUser)."</table>";
                      $iter1++;
                  } else if ($type =='FI'){
                      $totFIPerUser=0;
                      $colspan=0;
                      foreach($val1 as $ref=>$val2)
                      {
                          $tmpArr4=array();
                          $tmpArr4[]='<tr><th valign="top" colspan=2  class="ui-widget-header ui-state-default">'.$ref;
                          $total=0;
                          foreach($val2 as $label=>$totHT)
                          {
//                                if ($totHT > 0){
                               $tmpArr4[]='<tr><td width=75 class="ui-widget-content">'.$label."<td class='ui-widget-content'>".price($totHT);
                               $total+= $totHT;
//                                }
                          }
                          $tmpArr4[]='<tr><td width=75 class="ui-widget-header ui-state-default">Total<td class="ui-widget-content">'.price($total);
                          $tmpArr2[]= '<td style="padding:0"><table width=100% cellpadding=10>' .join(' ',$tmpArr4)."</table>";
                          $totFIPerUser+=$total;
                          $colspan++;
                      }
                      $tmpArr2[]='<tr><td colspan="'.$colspan.'" style="padding:0"><table cellpadding=10 width=100%><tr><td width=75 class="ui-widget-content ui-state-focus">Total FI<td class="ui-widget-content">'.price($totFIPerUser)."</table>";
                      $iter2++;
                 }
             }
             print "<tr>";
             print "<td valign=top><table cellpadding=10>";
             print join(' ',$tmpArr1);
             print "</table><td valign=top><table cellpadding=10>";
             print join(' ',$tmpArr2);
             print "</table>";
             print "</table>";
        }
        print "        </table>";
    }
}


?>
