<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 24 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : config.php
  * GLE-1.2
  */
  //choix du chemin
  $GLOBALS['IS_ADMIN']=1;

  require_once('pre.inc.php');
  require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
  require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
  require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");

  $html = new Form($db);
  $js = <<<EOF
  <script>
  jQuery(document).ready(function(){
      jQuery('#tab').tabs({
          cache: true,
          fx: { opacity: 'toggle' },
          spinner: 'Chargement ...',
      });
  });
  </script>

EOF;
  llxHeader($js, "Config importation commande");
  print" <a href='index.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Retour</span></a>";
$msg="";
if ($_REQUEST["action"] == 'setPathImport')
{
    dolibarr_set_const($db, "BIMP_PATH_IMPORT",$_REQUEST["value"]);
}
if ($_REQUEST["action"] == 'setMailTo')
{
    dolibarr_set_const($db, "BIMP_MAIL_TO",$_REQUEST["value"]);
}
if ($_REQUEST["action"] == 'setMailCc')
{
    dolibarr_set_const($db, "BIMP_MAIL_CC",$_REQUEST["value"]);
}
if ($_REQUEST["action"] == 'setMailBcc')
{
    dolibarr_set_const($db, "BIMP_MAIL_BCC",$_REQUEST["value"]);
}
if ($_REQUEST["action"] == 'setMailFrom')
{
    dolibarr_set_const($db, "BIMP_MAIL_FROM",$_REQUEST["value"]);
}
if ($_REQUEST["action"] == 'setMailSubject')
{
    if (preg_match("/\"/",$_REQUEST['value']))
    {
        $msg = "Le caract&egrave;re \" n'est pas permis dans le sujet";
    } else {
        dolibarr_set_const($db, "BIMP_MAIL_SUBJECT",$_REQUEST["value"]);
    }
}
if ($_REQUEST["action"] == 'setMailSubjectHisto')
{
    if (preg_match("/\"/",$_REQUEST['value']))
    {
        $msg = "Le caract&egrave;re \" n'est pas permis dans le sujet";
    } else {
        dolibarr_set_const($db, "BIMP_MAIL_SUBJECT_HISTO",$_REQUEST["value"]);
    }
}



if ($_REQUEST['action'] == "setNewPattern")
{
    $rang = $_REQUEST['rang'];
    if (!$rang>0)
    {
        $requete = "SELECT max(rang) + 1 as mi FROM ".MAIN_DB_PREFIX."Synopsis_PrepaCom_import_product_type";
        $sql = $db->query($requete);
        $res=$db->fetch_object($sql);
        $rang = ($res->mi>0?$res->mi:1);
    }
    $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_PrepaCom_import_product_type (pattern, product_type,rang) VALUES ('".$_REQUEST['NewPattern']."',".$_REQUEST['type'].",".$rang.")";
    $sql = $db->query($requete);
}
if ($_REQUEST['action'] == 'modPattern')
{
    $rang = $_REQUEST['rang'];
    $id = $_REQUEST['id'];
    $pattern = $_REQUEST['pattern'];
    $type = $_REQUEST['type'];
    if ($_REQUEST['suppr'] == 'supprimer')
    {
        $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_PrepaCom_import_product_type WHERE id=".$id;
        $sql = $db->query($requete);
    } else {
        $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_PrepaCom_import_product_type SET pattern='".$pattern."', product_type=".$type.", rang=".$rang." WHERE id = ".$id;
        $sql = $db->query($requete);
    }
}
if ($_REQUEST['action'] == 'modCat')
{
    $id = $_REQUEST['id'];
    $pattern = $_REQUEST['pattern'];
    $type = $_REQUEST['catId'];
    if ($_REQUEST['suppr'] == 'supprimer')
    {
        $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_PrepaCom_import_product_cat WHERE id=".$id;
        $sql = $db->query($requete);
    } else {
        $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_PrepaCom_import_product_cat SET pattern='".$pattern."', categorie_refid=".$type." WHERE id = ".$id;
        $sql = $db->query($requete);
    }
}


if($_REQUEST['action'] == 'setNewPatternCat')
{
    $rang = $_REQUEST['rang'];
    if (!$rang>0)
    {
        $requete = "SELECT max(rang) + 1 as mi FROM ".MAIN_DB_PREFIX."Synopsis_PrepaCom_import_product_cat";
        $sql = $db->query($requete);
        $res=$db->fetch_object($sql);
        $rang = ($res->mi>0?$res->mi:1);
    }
    $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_PrepaCom_import_product_cat (pattern, categorie_refid,rang) VALUES ('".$_REQUEST['NewPattern']."',".$_REQUEST['catId'].",".$rang.")";
    $sql = $db->query($requete);
}
if (strlen($msg)>0) print "<div class='error ui-error'>".$msg."</div>";
  print "<div class='titre'>Configuration du module d'import</div>";
  print "<br/>";
  print "<div class='tabs'>";

print "<div id='tab'>";
print "<ul>";
print '  <li><a href="#fragment-1"><span>Chemin</span></a></li>';
print '  <li><a href="#fragment-2"><span>Type de produit</span></a></li>';
print '  <li><a href="#fragment-3"><span>Cat&eacute;gorie</span></a></li>';
print '  <li><a href="#fragment-4"><span>Mail</span></a></li>';
print '</ul>';

  print "<div id='fragment-1'>";
  print "<form method='POST' action='".$_SERVER['PHP_SELF']."#fragment-1'>";
  print "<input type='hidden' name='action' value='setPathImport'>";
  print "<table cellpadding=15 width=650>";
  print "<tr><th width=200 style='border:1px Solid' class='ui-widget-header ui-state-default'>Chemin complet";
  print "<td width=320 class='ui-widget-content'><input size=60 name='value' value='".$conf->global->BIMP_PATH_IMPORT."'>";
  print "<td width=130 class='ui-widget-content'><button class='butAction'>Modifier</button>";
  print "</table>";
  print "</form>";
  print "<br>";
  print "</div>";
  print "<div id='fragment-2'>";
  //list les ref liant l'import à un type
  print "<table cellpadding=15>";
  print "<tr><th class='ui-widget-header ui-state-default' style='width:200px; border:1px solid;' width=200>Motif ref";
  print "    <th class='ui-widget-header ui-state-default' style='width:200px; border:1px solid;'>Type";
  print "    <th class='ui-widget-header ui-state-default' style='width:50px; border:1px solid;'>Rang";
  print "    <th colspan=2 class='ui-widget-header ui-state-default' style='width:292px; border:1px solid;' width=292>Action";
  print "</table>";
  $requete = "SELECT id,pattern, product_type as type ,rang FROM ".MAIN_DB_PREFIX."Synopsis_PrepaCom_import_product_type ORDER BY rang";
  $sql = $db->query($requete);
  while ($res = $db->fetch_object($sql)){
    print "<form method='POST' action='".$_SERVER['PHP_SELF']."#fragment-2'>";
    print "<input type='hidden' name='action' value='modPattern'>";
    print "<input type='hidden' name='id' value='".$res->id."'>";
    print "<table cellpadding=15 >";
    print "<tr><td width=200 style='width:200px;' class='ui-widget-content'><input type='text' value='".$res->pattern."' name='pattern'>";
    $sel ="<SELECT name='type' >";
//    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."product_type";
//    $sql1 = $db->query($requete);
//    
//    while($res1 = $db->fetch_object($sql1))
//    {
//        if ($res->type == $res1->id){
//            $sel .= "<OPTION SELECTED value='".$res1->id."'>".$res1->label."</OPTION>";
//        } else {
//            $sel .= "<OPTION value='".$res1->id."'>".$res1->label."</OPTION>";
//        }
//    }
    
    foreach($tabProductType as $id => $label){
        if ($res->type == $id){
            $sel .= "<OPTION SELECTED value='".$id."'>".$label."</OPTION>";
        } else {
            $sel .= "<OPTION value='".$id."'>".$label."</OPTION>";
        }
    }
    $sel .= "</SELECT>";
    print "<td class='ui-widget-content' style='width:200px;'>".$sel;
    print "<td class='ui-widget-content' align=center style='width:50px;'><input type='text' name='rang' style='text-align:center; width:32px' value='".$res->rang."'>";
    print "<td width=130 style='width:130px;' align=center class='ui-widget-content'><button class='butAction'>Modifier</button>";
    print "<td width=130 style='width:130px;' align=center class='ui-widget-content'><button name='suppr' value='supprimer' class='butAction'>Supprimer</button>";
    print "</table>";
    print "</form>";

  }

  print "<form method='POST' action='".$_SERVER['PHP_SELF']."#fragment-2'>";
  print "<input type='hidden' name='action' value='setNewPattern'>";
  print "<br/>";
  print "<table cellpadding=15>";
  print "<tr><th class='ui-state-hover ui-widget-header' style='padding:5px; border:1px Solid;' colspan=4>Nouveau";
  print "<tr><td style='width:198px;' width=198 class='ui-widget-content'><input type='text' value='".$res->pattern."' name='NewPattern'>";
  $sel ="<SELECT name='type' >";
//  $requete = "SELECT * FROM ".MAIN_DB_PREFIX."product_type";
//  $sql1 = $db->query($requete);
//  while($res1 = $db->fetch_object($sql1))
//  {
//      $sel .= "<OPTION value='".$res1->id."'>".$res1->label."</OPTION>";
//  }
  
  foreach($tabProductType as $id => $label){
        if ($res->type == $id){
            $sel .= "<OPTION SELECTED value='".$id."'>".$label."</OPTION>";
        } else {
            $sel .= "<OPTION value='".$id."'>".$label."</OPTION>";
        }
    }
  
  $sel .= "</SELECT>";

  print "<td  class='ui-widget-content' style='width:203px;'>".$sel;
  print "<td class='ui-widget-content' align=center style='width:50px;'><input type='text' name='rang' style='text-align:center; width:32px' value=''>";
  print "<td  width=292 align=center class='ui-widget-content'><button class='butAction'>Ajouter</button>";

  print "</table>";
  print "</form>";
  print "<br>";

print "</div>";
print "<div id='fragment-3'>";
  //list les ref liant l'import à un type

  $requete = "SELECT id,pattern, categorie_refid,rang  FROM ".MAIN_DB_PREFIX."Synopsis_PrepaCom_import_product_cat ORDER BY rang";
  $sql = $db->query($requete);
  $i=0;
  while ($res = $db->fetch_object($sql)){
    if($i==0)
    {
      print "<table cellpadding=15>";
      print "<tr style='border:1 px Solid;'><th class='ui-widget-header ui-state-default' style='width:200px;border: 1px solid;' width=200>Motif ref";
      print "    <th class='ui-widget-header ui-state-default' style='width:200px;border: 1px solid;'>Type";
      print "    <th class='ui-widget-header ui-state-default' style='width:50px;border: 1px solid;'>Rang";
      print "    <th colspan=2 class='ui-widget-header ui-state-default' style='width:292px;border: 1px solid;' width=292>Action";
      $i++;
    }

    print "<form method='POST' action='".$_SERVER['PHP_SELF']."#fragment-3'>";
    print "<input type='hidden' name='action' value='modCat'>";
    print "<input type='hidden' name='id' value='".$res->id."'>";
    print "<tr><td width=200 style='width:200px;' class='ui-widget-content'><input type='text' value='".$res->pattern."' name='pattern'>";
    print "<td class='ui-widget-content' style='width:200px;'>";
    print $html->select_all_categories(0,$res->categorie_refid,"catId");
    print "<td class='ui-widget-content' align=center style='width:50px;'><input type='text' name='rang' style='text-align:center; width:32px' value='".$res->rang."'>";
    print "<td width=130 style='width:130px;' align=center class='ui-widget-content'><button class='butAction'>Modifier</button>";
    print "<td width=130 style='width:130px;' align=center class='ui-widget-content'><button name='suppr' value='supprimer'  class='butAction'>Supprimer</button>";
    print "</form>";

  }
  if ($i > 0)
    print "</table>";
  //Ajoute le produit dans un catégorie
  print "<form method='POST' action='".$_SERVER['PHP_SELF']."#fragment-3'>";
  print "<input type='hidden' name='action' value='setNewPatternCat'>";
  print "<br/>";
  print "<table cellpadding=15>";
  print "<tr><th class='ui-state-hover ui-widget-header' style='padding:5px;' colspan=4>Nouveau";
  print "<tr><td style='width:200px;' width=200 class='ui-widget-content'><input type='text' value='".$res->pattern."' name='NewPattern'>";
  print "<td  class='ui-widget-content' style='width:200px;'>";
  print $html->select_all_categories(0,"","catId");
  print "<td class='ui-widget-content' align=center style='width:50px;'><input type='text' name='rang' style='text-align:center; width:32px' value=''>";
  print "<td  width=292 align=center class='ui-widget-content'><button class='butAction'>Ajouter</button>";

  print "</table>";
  print "</form>";

  print "<br>";
  print "</div>";

  print "<div id='fragment-4'>";
  print "<form method='POST' action='".$_SERVER['PHP_SELF']."#fragment-4'>";
  print "<input type='hidden' name='action' value='setMailTo'>";
  print "<table cellpadding=15 >";
  print "<tr><th width=200 style='border:1px Solid' class='ui-widget-header ui-state-default'>Destinataire";
  print "<td width=320 class='ui-widget-content'><input size=30 name='value' value='".$conf->global->BIMP_MAIL_TO."'>";
  print "<td width=130 class='ui-widget-content'><button class='butAction'>Modifier</button>";
  print "</table>";
  print "</form>";

  print "<form method='POST' action='".$_SERVER['PHP_SELF']."#fragment-4'>";
  print "<input type='hidden' name='action' value='setMailFrom'>";
  print "<table cellpadding=15 >";
  print "<tr><th width=200 style='border:1px Solid' class='ui-widget-header ui-state-default'>Emetteur";
  print "<td width=320 class='ui-widget-content'><input size=30 name='value' value='".$conf->global->BIMP_MAIL_FROM."'>";
  print "<td width=130 class='ui-widget-content'><button class='butAction'>Modifier</button>";
  print "</table>";
  print "</form>";

  print "<form method='POST' action='".$_SERVER['PHP_SELF']."#fragment-4'>";
  print "<input type='hidden' name='action' value='setMailCc'>";
  print "<table cellpadding=15 >";
  print "<tr><th width=200 style='border:1px Solid' class='ui-widget-header ui-state-default'>Copie &agrave;<br><em>user1@mail.com, user2@mail.com ...</em>";
  print "<td width=320 class='ui-widget-content'><input size=30 name='value' value='".$conf->global->BIMP_MAIL_CC."'>";
  print "<td width=130 class='ui-widget-content'><button class='butAction'>Modifier</button>";
  print "</table>";
  print "</form>";

  print "<form method='POST' action='".$_SERVER['PHP_SELF']."#fragment-4'>";
  print "<input type='hidden' name='action' value='setMailBcc'>";
  print "<table cellpadding=15>";
  print "<tr><th width=200 style='border:1px Solid' class='ui-widget-header ui-state-default'>Copie cach&eacute;e &agrave;";
  print "<td width=320 class='ui-widget-content'><input size=30 name='value' value='".$conf->global->BIMP_MAIL_BCC."'>";
  print "<td width=130 class='ui-widget-content'><button class='butAction'>Modifier</button>";
  print "</table>";
  print "</form>";

  print "<form method='POST' action='".$_SERVER['PHP_SELF']."#fragment-4'>";
  print "<input type='hidden' name='action' value='setMailSubject'>";
  print "<table cellpadding=15>";
  print "<tr><th width=200 style='border:1px Solid' class='ui-widget-header ui-state-default'>Sujet du mail";
  print '<td width=320 class="ui-widget-content"><input size=30 name="value" value="'. stripslashes($conf->global->BIMP_MAIL_SUBJECT).'">';
  print "<td width=130 class='ui-widget-content'><button class='butAction'>Modifier</button>";
  print "</table>";
  print "</form>";
  print "<form method='POST' action='".$_SERVER['PHP_SELF']."#fragment-4'>";
  print "<input type='hidden' name='action' value='setMailSubjectHisto'>";
  print "<table cellpadding=15>";
  print "<tr><th width=200 style='border:1px Solid' class='ui-widget-header ui-state-default'>Sujet du mail<br/><em>(historique)</em>";
  print '<td width=320 class="ui-widget-content"><input size=30 name="value" value="'.stripslashes($conf->global->BIMP_MAIL_SUBJECT_HISTO).'">';
  print "<td width=130 class='ui-widget-content'><button class='butAction'>Modifier</button>";
  print "</table>";
  print "</form>";


  print "<br>";
  print "</div>";


  print "</div>";
  print "</div>";
llxFooter();
?>
