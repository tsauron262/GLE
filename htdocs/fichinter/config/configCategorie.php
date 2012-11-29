<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 15 nov. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : configCategorie.php
  * GLE-1.2
  */

    require_once('pre.inc.php');

    if (!$user->rights->synopsisficheinter->config) { accessforbidden(); exit();}

    if($_REQUEST['action'] == "moveDown"){
        $id = $_REQUEST['id'];
        $requete = "UPDATE llx_Synopsis_fichinter_extra_key
                       SET rang = ifnull(rang+1,1)
                     WHERE id =".$id;
        $db->query($requete);
    }
    if($_REQUEST['action'] == "moveUp"){
        $id = $_REQUEST['id'];
        $requete = "UPDATE llx_Synopsis_fichinter_extra_key
                       SET rang = ifnull(rang-1,1)
                     WHERE id =".$id;
        $db->query($requete);
    }

    if($_REQUEST['action']=="addChoice")
    {
        $requete = "INSERT INTO llx_Synopsis_fichinter_extra_values_choice
                                (label,value,key_refid)
                         VALUES ('".$_REQUEST['label']."','".$_REQUEST['value']."',".$_REQUEST['keyId'].")";
        $sql = $db->query($requete);
    }
    if(preg_match('/^delChoice-([0-9]*)/',$_REQUEST['action'],$arr))
    {
        $requete = "DELETE FROM llx_Synopsis_fichinter_extra_values_choice
                          WHERE id=".$arr[1];
//print $requete;
        $sql = $db->query($requete);
    }
    if($_REQUEST['action']=="addField")
    {
        $requete="INSERT INTO llx_Synopsis_fichinter_extra_key
                              (label,type)
                       VALUES ('".$_REQUEST['label']."','".$_REQUEST['typeField']."')";
        $sql = $db->query($requete);
    }
    if($_REQUEST['action']=="delField")
    {
        $requete="DELETE FROM llx_Synopsis_fichinter_extra_key
                        WHERE id = ".$_REQUEST['line'];
        $sql = $db->query($requete);
    }
    $js = "<script type='text/javascript' src='".DOL_URL_ROOT."/Synopsis_Common/jquery/jquery.rating.js'></script>";
    $js .= '<link rel="stylesheet"  href="'.DOL_URL_ROOT.'/Synopsis_Common/jquery.rating.css" type="text/css" ></link>';

    llxHeader($js,"Configuration des interventions");
    $requete = "SELECT *
                  FROM llx_Synopsis_fichinter_extra_key
                 WHERE active = 1
              ORDER BY rang,
                       label";
    $sql = $db->query($requete);
    //1 liste les extra fields possible
    print "<table cellpadding=15 width=1100>";
    print "<tr><th  width=242 class='ui-widget-header ui-state-default'>Label
               <th width=205 class='ui-widget-header ui-state-default'>Type
               <th class='ui-widget-header ui-state-default'>Valeurs
               <th class='ui-widget-header ui-state-default'>Description
               <th class='ui-widget-header ui-state-default'>Qualit&eacute;?
               <th class='ui-widget-header ui-state-default'>Rang
               <th class='ui-widget-header ui-state-default'>Action";
    while ($res=$db->fetch_object($sql))
    {
        print "<tr><td class='ui-widget-content'>".$res->label."<td class='ui-widget-content'>".$res->type;
        if ($res->type == "radio")
        {
            print "<td class='ui-widget-content' style='padding: 0;'>";
            print "<form method='post' action='configCategorie.php'>";
            print "<input type='hidden' name='keyId' value='".$res->id."'>";
            print "<table cellpadding=3 width=100%>";
            $requete = "SELECT *
                          FROM llx_Synopsis_fichinter_extra_values_choice
                         WHERE key_refid = ".$res->id;
            $sql1 = $db->query($requete);
            print "<tr><th class='ui-widget-header ui-state-default'>Valeur<th class='ui-widget-header ui-state-default'>Label<th class='ui-widget-header ui-state-default'>&nbsp;";
            while ($res1 = $db->fetch_object($sql1)){
                print "<tr><td class='ui-widget-content'>".$res1->value."<td class='ui-widget-content'>".$res1->label."<td class='ui-widget-content'><button class='butAction' name='action' value='delChoice-".$res1->id."'>Supprimer</button>";
            }
            print "<tr><td align=center class='ui-widget-content'><input type='text' name='value' size=2 value=''><td align=center class='ui-widget-content'><input type='text' name='label' size=10 value=''><td  align=center class='ui-widget-content'><button class='butAction' name='action' value='addChoice'>Ajouter</button>";
            print "</table></form>";
        } else
        {
            print "<td class='ui-widget-content'>&nbsp;";
        }
        print "<td class='ui-widget-content'>".$res->description."";
        print "<td class='ui-widget-content'>".($res->isQuality==1?"Oui":"Non");
        print "<td class='ui-widget-content'><table><tr><td rowspan=2>".($res->rang)."<td><span onClick='moveUp(".$res->id.");' class='selectable ui-icon ui-icon-triangle-1-n'></span><tr><td><span onClick='moveDown(".$res->id.");' style='margin-left:-1px;' class='ui-icon ui-icon-triangle-1-s selectable'></span></table>";
        print "<td class='ui-widget-content' align=center><form method='post' action='configCategorie.php'><input type='hidden' name='action' value='delField'><input type='hidden' name='line' value='".$res->id."'><button class='butAction'>Supprimer</button></form>";
    }
    $selType=<<<EOF
<style>
.selectable{ cursor: pointer;  }
</style>
<script>
function moveUp(pId){
    location.href='configCategorie.php?id='+pId+"&action=moveUp";
}
function moveDown(pId){
    location.href='configCategorie.php?id='+pId+"&action=moveDown";
}
</script>
<select name="typeField">
<option value="date">Date</option>
<option value="datetime">Date + heure</option>
<option value="text">Texte</option>
<option value="textarea">Commentaire</option>
<option value="checkbox">Case &agrave; cocher</option>
<option value="radio">Choix multiple</option>
<option value="3stars">3 &eacute;toiles</option>
<option value="5stars">5 &eacute;toiles</option>
</select>
EOF;
    print "</table><br/><div class='titre'>Ajouter</div>";

    print "<form method='post' action='configCategorie.php'>";
    print "<table cellpadding=15 width=1100>";
    print "<input type='hidden' name='action' value='addField'>";

    print "<tr><th  width=240 class='ui-widget-header ui-state-default'>Label
               <th width=200 class='ui-widget-header ui-state-default'>Type
               <th width=200 class='ui-widget-header ui-state-default'>Description
               <th class='ui-widget-header ui-state-default'>Qualit&eacute;?
               <th class='ui-widget-header ui-state-default'>Rang
               <th class='ui-widget-header ui-state-default'>Action";
    print "<tr><td width=242 align=center class='ui-widget-content'><input name='label'>
               <td width=205 align=center class='ui-widget-content'>".$selType;
    print "<td class='ui-widget-content' align=center><textarea name'description'></textarea>";
    print "<td class='ui-widget-content' align=center><input name='isQuality' type='checkbox'>";
    $requete = "SELECT max(rang) +1 as Mxrang
                  FROM llx_Synopsis_fichinter_extra_key;";
    $sql1 = $db->query($requete);
    $res1 = $db->fetch_object($sql1);
    print "<td class='ui-widget-content'><input name='rang' type='text' size=3 value='".($res1->Mxrang)."'>";

    print "<td colspan=2 width=358 class='ui-widget-content' align=center><button class='butAction'>Ajouter</button>";
    print "</table>";
    print "</form>";
    
    llxFooter();

?>