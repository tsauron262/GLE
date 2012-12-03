<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 12 dec. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : configType.php
  * GLE-1.2
  */



  require_once('pre.inc.php');
    if (!$user->rights->synopsisficheinter->config) { accessforbidden(); exit();}
  require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
  $html = new Form($db);

  if ($_REQUEST['action'] == "moveUp")
  {
      $requete = "SELECT * FROM llx_Synopsis_fichinter_c_typeInterv WHERE id = ".$_REQUEST['id'];
      $sql = $db->query($requete);
      $res = $db->fetch_object($sql);
      $oldRang = $res->rang;
      $newRang = $oldRang - 1;
      $requete = "UPDATE llx_Synopsis_fichinter_c_typeInterv SET rang = ".$oldRang . " WHERE rang = ".$newRang;
      $sql = $db->query($requete);
      $requete = "UPDATE llx_Synopsis_fichinter_c_typeInterv SET rang = ".$newRang . " WHERE id = ".$_REQUEST['id'];
      $sql = $db->query($requete);
      header('location:configType.php');
  }
  if ($_REQUEST['action'] == "moveDown")
  {
      $requete = "SELECT * FROM llx_Synopsis_fichinter_c_typeInterv WHERE id = ".$_REQUEST['id'];
      $sql = $db->query($requete);
      $res = $db->fetch_object($sql);
      $oldRang = $res->rang;
      $newRang = $oldRang + 1;
      $requete = "UPDATE llx_Synopsis_fichinter_c_typeInterv SET rang = ".$oldRang . " WHERE rang = ".$newRang;
      $sql = $db->query($requete);
      $requete = "UPDATE llx_Synopsis_fichinter_c_typeInterv SET rang = ".$newRang . " WHERE id = ".$_REQUEST['id'];
      $sql = $db->query($requete);
      header('location:configType.php');
  }
  if ($_REQUEST['action']=='add')
  {
      $label=addslashes($_REQUEST['label']);
      $default=$_REQUEST['default'];
      $deplacement=$_REQUEST['deplacement'];
      $recap=$_REQUEST['recap'];
      $decountTkt=$_REQUEST['decountTkt'];
      $actif=$_REQUEST['actif'];
      $requete = "SELECT max(rang) + 1 as rg FROM llx_Synopsis_fichinter_c_typeInterv";
      $sql = $db->query($requete);
      $res = $db->fetch_object($sql);
      $max = $res->rg;
      $requete = "INSERT INTO llx_Synopsis_fichinter_c_typeInterv
                              (label,active,rang,isDeplacement,inTotalRecap,decountTkt,`default`)
                       VALUES ('".$label."',".($actif==1?1:0).",".$max.",".($deplacement==1?1:'NULL').",".($recap==1?1:0).",".($decountTkt==1?1:'NULL').",".($default."x"=="x"?'NULL':1).")";
      $sql = $db->query($requete);
      $newId = $db->last_insert_id($sql);
      if ($default."x" != "x"){
            $requete = "UPDATE llx_Synopsis_fichinter_c_typeInterv SET `default` = NULL WHERE id !=".$newId;
            $sql=$db->query($requete);
      }

  }
  if($_REQUEST['action']=='config')
  {
        $default=$_REQUEST['default'];
        //TODO Pour chaque ligne=> modifier
        foreach($_REQUEST as $key=>$val)
        {
            if (preg_match('/^label-([0-9]*)$/',$key,$arr)){
                $id = $arr[1];
                $label = $val;
//                $isDefault = $_REQUEST['default-'.$id];
                $isDeplacement = $_REQUEST['deplacement-'.$id];
                $inRecap = $_REQUEST['recap-'.$id];
                $isdecountTkt = $_REQUEST['decountTkt-'.$id];
                $actif = $_REQUEST['actif-'.$id];
                $rang = 1;
                $requete = " SELECT max(rang) + 1 as mx FROM llx_Synopsis_fichinter_c_typeInterv ";
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                $newRang = ($res->mx > 0?$res->mx:$rang);
                //`default` =
                $requete = "UPDATE llx_Synopsis_fichinter_c_typeInterv
                               SET inTotalRecap = ".($inRecap>0?1:'NULL')." ,
                                   isDeplacement = ".($isDeplacement>0?1:'NULL').",
                                   active = ".$actif.",
                                   decountTkt = ".($isdecountTkt>0?1:'NULL').",
                                   label = '".addslashes($label)."',
                                   `default` = NULL
                             WHERE id =". $id;
                $db->query($requete);
//                print $requete.";<br/>";
            }
        }
        if ($default > 0){
            $requete = "UPDATE llx_Synopsis_fichinter_c_typeInterv SET `default` = 1 WHERE id =".$default;
            $sql=$db->query($requete);
  //          print $requete.";<br/>";
        }
  }
  $js = <<<EOF
<script>
jQuery(document).ready(function(){
    jQuery('.rangCol span.ui-icon').mouseover(function(){
        jQuery(this).parent().removeClass('ui-state-default');
    });
    jQuery('.rangCol span.ui-icon').mouseout(function(){
        jQuery(this).parent().addClass('ui-state-default');
    });
});
function moveUp(id){
    location.href='configType.php?action=moveUp&id='+id;
}
function moveDown(id){
    location.href='configType.php?action=moveDown&id='+id;
}
</script>
<style>
.rangCol{
    cursor: pointer;
}
</style>
EOF;

  llxHeader($js,'Configuration des types');
  $requete = "SELECT * FROM llx_Synopsis_fichinter_c_typeInterv ORDER by rang ";
  $sql = $db->query($requete);
  print "<form action='configType.php?action=config' method='post'>";
  print "<ul>";
  print "<table cellpadding=15 width=100%>";
  $thclass='class="ui-widget-header ui-state-default"';
  print "<tr><th ".$thclass.">Label<th ".$thclass.">defaut<th ".$thclass.">D&eacute;placement?<th ".$thclass.">Dans le recap?<th ".$thclass.">Decompte ticket?<th ".$thclass.">actif?<th ".$thclass.">rang";
  $i=0;
  $tot = $db->num_rows($sql);
  while ($res=$db->fetch_object($sql))
  {
      print "<tr><td align=center class='ui-widget-content'><input value='".$res->label."' name='label-".$res->id."' id='label-".$res->id."'>";
      print "    <td align=center class='ui-widget-content'><input type='radio' value='".$res->id."' name='default' ".($res->default==1?"checked":"").">";
      print "    <td align=center class='ui-widget-content'>".$html->selectyesno("deplacement-".$res->id."",$res->isDeplacement,1);
      print "    <td align=center class='ui-widget-content'>".$html->selectyesno("recap-".$res->id."",$res->inTotalRecap,1);
      print "    <td align=center class='ui-widget-content'>".$html->selectyesno("decountTkt-".$res->id."",$res->decountTkt,1);
      print "    <td align=center class='ui-widget-content'>".$html->selectyesno("actif-".$res->id."",$res->active,1);
      print "    <td align=center class='ui-widget-content'><div class='rangCol' style='border:0px Solid; background: none;'>";
      if ($i > 0){
        print "<div style='border:0px Solid; background: none;' class='ui-state-default'><span onclick='moveUp(".$res->id.")' class='ui-icon ui-icon-circle-triangle-n'></span></div>";
      }
      if ($i < $tot - 1){
        print "<div style='border:0px Solid; background: none;' class='ui-state-default'><span onclick='moveDown(".$res->id.")'  class='ui-icon ui-icon-circle-triangle-s'></span></div>";
      }
      print "</div>";
      $i++;
  }
  print "<tr> <th class='ui-state-default' colspan='7'><button class='butAction'>Modifier</button></td>";
  print "</table></ul></form>";
  print "<br/>";
  print "<div class='titre'>Ajouter un type d'intervention</div>";
  print "<form action='configType.php?action=add' method=post>";
  print "<ul>";
  print "<table cellpadding=15 width=100%>";
  $thclass='class="ui-widget-header ui-state-default"';
  print "<tr><th ".$thclass.">Label<th ".$thclass.">Defaut ?<th ".$thclass.">D&eacute;placement?<th ".$thclass.">Dans le recap?<th ".$thclass.">Decompte ticket?<th ".$thclass.">Actif?";
  print "<tr><td align=center class='ui-widget-content'><input value='' name='label' id='label'>";
  print "    <td align=center class='ui-widget-content'><input type='checkbox' name='default' >";
  print "    <td align=center class='ui-widget-content'>".$html->selectyesno("deplacement",0,1);
  print "    <td align=center class='ui-widget-content'>".$html->selectyesno("recap",0,1);
  print "    <td align=center class='ui-widget-content'>".$html->selectyesno("decountTkt",0,1);
  print "    <td align=center class='ui-widget-content'>".$html->selectyesno("actif",1,1);
  print "<tr> <th class='ui-state-default' colspan='6'><button class='butAction'>Ajouter</button></td>";

  print "</table></ul></form>";

  
  llxFooter();
?>
