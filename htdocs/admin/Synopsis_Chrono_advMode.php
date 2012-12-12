<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 27 janv. 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : Synopsis_Chrono.php
  * GLE-1.2
  */

  //liste les type de chronos disponible


require_once("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Chrono/Chrono.class.php');

if (!$user->admin && !$user->local_admin)
    accessforbidden();


$langs->load("admin");
$langs->load("other");

$msg = "";
$id = $_REQUEST['id'];

/**
 * Affichage du formulaire de saisie
 */
 if($_REQUEST['action'] == 'genWidget')
 {
    //1 gen/place le widget
    $db->begin();
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_conf WHERE id = ".$id;
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $titre = $res->titre;

    $widgetTxt = file_get_contents(DOL_DOCUMENT_ROOT."/Synopsis_Chrono/widget_modele/widget-modele.tpl");
    $widgetTxt = preg_replace('/\[MARK1\]/',$id,$widgetTxt);
    $widgetTxt = preg_replace('/\[MARK2\]/',utf8_encode($titre),$widgetTxt);
    $newFile = DOL_DOCUMENT_ROOT."/Synopsis_Common/jquery/dashboard/widgets/listChronoModele".$id.".inc";
    if(is_file($newFile))
    {
        unlink ($newFile);
    }
    $newFilet = "/tmp/listChronoModele".$id.".inc";
    $res = file_put_contents($newFilet,$widgetTxt);
    $res = file_put_contents($newFile,$widgetTxt);
    //2 active le widget
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_dashboard_widget WHERE module = 'listChronoModele".$id."'";
    $sql = $db->query($requete);
    if ($db->num_rows($sql) > 0)
    {
        $sql1 = true;
        $sql2 = true;
        $sql3 = true;
    } else {
        $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_dashboard_widget (nom, module, active) VALUES ('".addslashes('Chrono - Derniers chronos ('.$titre.')')."','listChronoModele".$id."',1)";
        $sql1 = $db->query($requete);
        $newId = $db->last_insert_id("".MAIN_DB_PREFIX."Synopsis_dashboard_widget");
        $sql2 = false;
        $sql3 = false;
        //3 page chrono (46) et page principale (4)
        if ($newId > 0)
        {
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_dashboard_module (module_refid, type_refid) VALUES (".$newId.",4)";
            $sql2 = $db->query($requete);
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_dashboard_module (module_refid, type_refid) VALUES (".$newId.",46)";
            $sql3 = $db->query($requete);
        }
    }
    if($sql1 && $sql2 && $sql3 && $res > 0)
    {
        $db->commit();
        $msg = "G&eacute;n&eacute;ration OK";
    } else {
        $db->rollback();
        $msg = "G&eacute;n&eacute;ration KO";
    }
 }
 if ($_REQUEST['action'] == 'setRevisionModel')
 {
    $revModel = $_REQUEST['revision_model'];
    $id = $_REQUEST['id'];
    $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_Chrono_conf SET revision_model_refid = ".$revModel . " WHERE id = ".$id;
    $sql = $db->query($requete);
    if ($sql) header('Location: Synopsis_Chrono_advMode.php?panelRevision=1&id='.$id);
    else $msg="Erreur : ".$db->error;

 }
 if ($_REQUEST['action']=='del')
 {
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_key WHERE id = " .$_REQUEST['delid'];
    $sql = $db->query($requete);
 }
 if ($_REQUEST['action']=='addKey')
 {
    $nom = addslashes($_REQUEST['nom']);
    $description = addslashes($_REQUEST['description']);
    $id = $_REQUEST['id'];
    $inDetList = ($_REQUEST['inDetList'] > 0?1:0);

    $type_valeur = $_REQUEST['type_valeur_jDS'];
    if($type_valeur == '')
        $type_valeur = $_REQUEST['type_valeur'];
    $extraCss = $_REQUEST['extraCss'];
    $subVal2 = $_REQUEST['subVal2'];
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_key_type_valeur WHERE nom='".$type_valeur."'";
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    if ($res->id > 0)
        $type_valeur = $res->id;
    else if ($subVal2 > 0)
    {
        $type_valeur = $subVal2;
        $subVal2 = false;
    }

    $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_Chrono_key (nom, description,model_refid, type_valeur,type_subvaleur,extraCss,inDetList)
                     VALUES ('".$nom."','".$description."',".$id.",'".$type_valeur."',".($subVal2?"'".$subVal2."'":"NULL").",'".$extraCss."',".$inDetList.")";
    $sql = $db->query($requete);
 }
if ($_REQUEST['action'] == 'setDroits')
{
    $id = $_REQUEST['id'];
    //Droits
    $db->begin();
    $commit = true;
    //1 reset tous les droits
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_rights WHERE chrono_refid = ".$id;
    $sql = $db->query($requete);
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_group_rights WHERE chrono_refid = ".$id;
    $sql1 = $db->query($requete);
    $commit = ($sql&& $commit?true:false);
    if ($sql1)
    //2 mets les droits
    foreach($_REQUEST as $key=>$val)
    {
        if (preg_match('/([u|g]{1})([0-9]+)-([0-9]+)/',$key,$arr))
        {
            $type= $arr[1];
            $userid= $arr[2];
            $rightId= $arr[3];
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_Chrono_rights (chrono_refid,user_refid,right_refid,valeur)
                             VALUES (".$id.",".$userid.",".$rightId.",1)";
            if ($type == 'g')
            {
                $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_Chrono_group_rights (chrono_refid,group_refid,right_refid,valeur)
                                 VALUES (".$id.",".$userid.",".$rightId.",1)";
            }
            $sql = $db->query($requete);
            $commit = ($sql&& $commit?true:false);
        }
    }
    if ($commit){ $db->commit(); } else { $db->rollback(); }
    if ($commit) header('Location: Synopsis_Chrono_advMode.php?panelDroit=1&id='.$id);
    else $msg="Erreur : ".$db->error;
}


$js = "<script type='text/javascript' src='".DOL_URL_ROOT."/Synopsis_Common/jquery/jquery.jDoubleSelect.js'>";
$js .= "<script src='".DOL_URL_ROOT."/Synopsis_Common/jquery/jquery-css-transform.js' type='text/javascript'></script>";
$js .= "<script src='".DOL_URL_ROOT."/Synopsis_Common/jquery/jquery-animate-css-rotate-scale.js' type='text/javascript'></script>";
$js .= '<script language="javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.validate.js"></script>'."\n";


$js .='<script>';
$js .= 'var chronoId='.$_REQUEST['id'].';';
$js .= <<<EOF
var dialogId = false;
function openDialog(pId)
{
    dialogId = pId;
    jQuery('#delDialog').dialog('open');
}


jQuery(document).ready(function(){
    jQuery('#tabs').tabs({
        spinner: 'Chargement ...',
        fx: { height: 'toggle', opacity: "toggle" },
        cache: true,
EOF;
    if($_REQUEST['panelDroit']."x" != "x")
    {
        $js .= 'selected: 1,';
    }
    if($_REQUEST['panelRevision']."x" != "x")
    {
        $js .= 'selected: 2,';
    }

$js .= <<<EOF
    })
    jQuery('.tb').each(function(){ jQuery(this).rotate('45deg');});
    jQuery('#delDialog').dialog({
        buttons:{
            "OK": function(){
                location.href="Synopsis_Chrono_advMode.php?delid="+dialogId+"&action=del&id="+chronoId
            },
            "Annuler": function(){
                jQuery('#delDialog').dialog('close');
            },
        },
        autoOpen: false,
        width: 520,
        minWidth: 520,
        modal: true,
        title: "Supprimer une clef",
    })
    jQuery('#addForm').validate();
    jQuery('#accordion').accordion({animated: 'bounceslide',  navigation: true,autoHeight: false, fillSpace: false});

    jQuery('#type_valeur').jDoubleSelect({
        text:'',
        finish: function(){
            /*jQuery('#type_valeur_jDS').selectmenu({
                style:'dropdown',
                maxHeight: 300
            });*/
        },
        el1_change: function(){
            /*jQuery('#type_valeur_jDS_2').selectmenu({
                style:'dropdown',
                maxHeight: 300
            });*/
        },
        destName:'subVal2',
        el2_dest:jQuery('#dest2el')
    });
});
</script>
EOF;
$js .= " <style> .tb {-moz-transform: rotate(45deg); -transform: rotate(45deg);} </style>";

llxHeader($js,"Chrono - Adv Mode");
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($langs->trans("Chrono"),$linkback,'setup');

require_once(DOL_DOCUMENT_ROOT."/Synopsis_Chrono/Chrono.class.php");
require_once(DOL_DOCUMENT_ROOT."/user/class/usergroup.class.php");
$chr = new Chrono($db);
$chr->model_refid=$_REQUEST['id'];

if ($msg."x" != "x"){ print "<div class='ui-error ui-state-error' style='width:100%; padding:3px;'><span style='float: left;' class='ui-icon ui-icon-info'></span>".$msg."</div>"; }


print '<br>';

//config avancée
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/admin/Synopsis_Chrono.php';
    $head[$h][1] = $langs->trans("Config");
    $head[$h][2] = 'main';
    $h++;

//    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_conf WHERE id = ".$_REQUEST['id'];
//    $sql = $db->query($requete);
//    $res = $db->fetch_object($sql);




    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_conf WHERE id = ".$_REQUEST['id'];
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);

    $head[$h][0] = DOL_URL_ROOT.'/admin/Synopsis_Chrono_advMode.php?id='.$_REQUEST['id'];
    $head[$h][1] = $res->titre;
    $head[$h][2] = 'Chrono';
    $h++;
    dol_fiche_head($head, 'Chrono', $langs->trans("Chrono"));

    $hasRevision=false;
    $revision_model = false;

    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_conf WHERE id = ".$_REQUEST['id'];
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    if ($res->hasRevision == 1) $hasRevision = true;
    if ($res->hasRevision == 1) $revision_model = $res->revision_model_refid;

    print "<div id='tabs'>";

    print "<ul><li><a href='#clef'>Clef</a></li>";
    print "    <li><a href='#droits'>Droits</a></li>";
    print "    <li><a href='#widget'>Widget</a></li>";
    if ($hasRevision)
        print "    <li><a href='#revision'>R&eacute;vision</a></li>";
    print "</ul>";



    if ($hasRevision)
    {
        print "<div id='revision'>";
        //Modele de revision
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_revision_model ORDER BY nom";
        $sql = $db->query($requete);
        print "<table width=100% cellpadding=10>";
        print "<tr><th class='ui-widget-header ui-state-default'>Nom";
        print "    <th class='ui-widget-header ui-state-default'>Description";
        print "    <th class='ui-widget-header ui-state-default'>Exemple";
        print "    <th class='ui-widget-header ui-state-default'>Action";
        while($res = $db->fetch_object($sql))
        {
            require_once(DOL_DOCUMENT_ROOT."/Synopsis_Revision/modele/".$res->phpClass.".class.php");
            $tmp = $res->phpClass;
            $obj = new $tmp($db);
            print "<tr><th class='ui-widget-header ui-state-hover'>".$obj->nom;
            print "    <td class='ui-widget-content'>".$obj->description;
            print "    <td class='ui-widget-content'>[ref]-".$obj->convert_revision(rand(10,100));
            print "    <td class='ui-widget-content'>";
            if ($res->id == $revision_model)
                print img_picto('S&eacute;l&eacute;ctionner', 'tick');
            else
                print "       <a href='Synopsis_Chrono_advMode.php?action=setRevisionModel&id=".$_REQUEST['id']."&revision_model=".$res->id."'>Activer</a>";
        }
        print "</table>";
        print "</div>";
    }
    print "<div id='widget'>";
    print "<table width=100% cellpadding=10>";
    print "<tr><th class='ui-widget-header ui-state-default'>G&eacute;n&eacute;rer/Re-g&eacute;n&eacute;rer le widget de ce mod&egrave;le?";
    print "    <td class='ui-widget-content'><button onclick=\"location.href='Synopsis_Chrono_advMode.php?action=genWidget&id=".$id."';\" class='butAction'>OK</button>";
    print "</table>";
    print "</div>";

    print "<div id='droits'>";

    print "<form action='Synopsis_Chrono_advMode.php?id=".$_REQUEST['id']."' method='POST'>";
    print "<input type='hidden' name='action' value='setDroits'>";
    print '<div id="accordion">';
    print '<h3><a href="#">Utilisateurs</a></h3>';
    print '<div>';
    print "<table cellpadding=10 width=100%>";
    print "<tr><th class='ui-widget-header ui-state-default' title='Nom de l\'utilisateur'>Utilisateur";
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_rights_def WHERE active=1 ORDER BY rang ";
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($sql))
    {
        print "    <th style='height: 7em' class='ui-widget-header ui-state-default' title='".$res->description."'><span class='tb'>".$res->label."</span>";
    }

    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."user WHERE statut = 1";
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($sql))
    {
        $tmpuser = new User($db);
        $tmpuser->fetch($res->rowid);
        $chr->getRights($tmpuser);

        print "<tr><td class='ui-widget-content'>".$tmpuser->getNomUrl(1);
        $chrono_right_ref = "chrono".$chr->model_refid;

        $requete1 = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_rights_def WHERE active=1 ORDER BY rang ";
        $sql1 = $db->query($requete1);
        $col=1;
//var_dump($tmpuser->rights->chrono_user->$chrono_right_ref);
        while ($res1 = $db->fetch_object($sql1))
        {
            $col++;
            $type = $res1->code;
            print "    <td class='ui-widget-content' align=center><input type='checkbox' name='u".$tmpuser->id."-".$res1->id."' ".($tmpuser->rights->chrono_user->$chrono_right_ref && $tmpuser->rights->chrono_user->$chrono_right_ref->$type?"Checked >":">");
            print ($tmpuser->rights->chrono_user->$chrono_right_ref && $tmpuser->rights->chrono_user->$chrono_right_ref->$type?($tmpuser->rights->chrono_user->$chrono_right_ref->$type=='g'?img_tick_group('H&eacute;rit&eacute;'):img_tick('Oui')):"");
        }
    }
    print "    <tr><th class='ui-widget-header' align=center colspan='".$col."'><button class='butAction'>Modifier</button></th>";
    print "</table>";
    print '</div>';
    print '<h3><a href="#">Groupes</a></h3>';
    print '<div>';
    print "<table cellpadding=10 width=100%>";
    print "<tr><th class='ui-widget-header ui-state-default' title='Groupe d\'utilisateur'>Groupe";
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_rights_def WHERE active=1 ORDER BY rang ";
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($sql))
    {
        print "    <th style='height: 7em' class='ui-widget-header ui-state-default' title='".$res->description."'><span class='tb'>".$res->label."</span>";
    }

    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."usergroup ";
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($sql))
    {
        $tmpgrp = new UserGroup($db);
        $tmpgrp->fetch($res->rowid);
        $tmpgrp = $chr->getGrpRights($tmpgrp);
//var_dump($tmpgrp->rights);
        print "<tr><td class='ui-widget-content'>".$tmpgrp->nom;
        $chrono_right_ref = "chrono".$chr->model_refid;

        $requete1 = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_rights_def WHERE active=1 ORDER BY rang ";
        $sql1 = $db->query($requete1);
        $col = 1;
        while ($res1 = $db->fetch_object($sql1))
        {
            $col++;
            $type = $res1->code;
//                    print "    <td class='ui-widget-content' align=center>".($tmpgrp->rights->chrono->$chrono_right_ref && $tmpgrp->rights->chrono->$chrono_right_ref->$type?"Oui":"Non");
            print "    <td class='ui-widget-content' align=center><input type='checkbox' name='g".$tmpgrp->id."-".$res1->id."' ".($tmpgrp->rights->chrono_group->$chrono_right_ref && $tmpgrp->rights->chrono_group->$chrono_right_ref->$type?"Checked >":">");
            print ($tmpgrp->rights->chrono_group->$chrono_right_ref && $tmpgrp->rights->chrono_group->$chrono_right_ref->$type?img_tick("Oui"):"");
        }
    }
    print "    <tr><th class='ui-widget-header' align=center colspan='".$col."'><button class='butAction'>Modifier</button></th>";
    print "</table>";
    print '</div>';
    print '</div>';
    print "</form>";



    print "</div>";
    print "<div id='clef'>";
    print "<table width=100% cellpadding=10>";
    print "<tr><th width=20% class='ui-widget-header ui-state-default'>Nom";
    print "    <th width=40% class='ui-widget-header ui-state-default'>Description";
    print "    <th width=10% class='ui-widget-header ui-state-default'>Css";
    print "    <th width=10% class='ui-widget-header ui-state-default'>List d&eacute;tails";
    print "    <th class='ui-widget-header ui-state-default'>Type de valeur";
    print "    <th class='ui-widget-header ui-state-default'>Action";
    $requete = "SELECT k.nom,
                       k.description,
                       v.nom as valLabel,
                       v.hasSubValeur,
                       v.subValeur_table,
                       v.subValeur_idx,
                       v.subValeur_text,
                       k.type_subvaleur,
                       k.extraCss,
                       k.inDetList,
                       k.id
                  FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_key as k ,
                       ".MAIN_DB_PREFIX."Synopsis_Chrono_key_type_valeur  as v
                 WHERE v.id=k.type_valeur
                   AND model_refid = ".$_REQUEST['id'];
    $sql = $db->query($requete);
    //TODO reorder
    //TODO duplicate name
    while ($res = $db->fetch_object($sql)){
        print "<tr><td width=20% class='ui-widget-content'>".$res->nom;
        print "    <td width=40% class='ui-widget-content'>".$res->description;
        print "    <td width=10% class='ui-widget-content'>".$res->extraCss;
        print "    <td width=10% class='ui-widget-content'>".($res->inDetList==1?"Oui":"Non");
        print "    <td class='ui-widget-content'>".$res->valLabel;
        if ($res->hasSubValeur == 1){
            $requete1 = "SELECT ".$res->subValeur_idx." as idx, ".$res->subValeur_text." as val FROM ".$res->subValeur_table." WHERE  ".$res->subValeur_idx."=".$res->type_subvaleur;
            
            $sql1 = $db->query($requete1);
            $res1=$db->fetch_object($sql1);
            print " - ".$res1->val;
        }
        print "    <td width=60 align=center class='ui-widget-content'><a href='#' onClick='openDialog(".$res->id.")'>".img_delete()."</a>";
    }
    print '</table><br/><br/><br/>';
    print '<form name="addForm" id="addForm" action="Synopsis_Chrono_advMode.php?id='.$_REQUEST['id'].'" method="POST" onSubmit="if(!jQuery(\'#addForm\').validate().form()) return(false);">';
    print '<input type="hidden" name="action" value="addKey">';
    print "<table width=100% cellpadding=10>";
    print "<tr><th width=20% class='ui-widget-header ui-state-default'>Nom";
    print "    <th width=40% class='ui-widget-header ui-state-default'>description";
    print "    <th width=10% class='ui-widget-header ui-state-default'>Css";
    print "    <th width=10% class='ui-widget-header ui-state-default'>Liste D&eacute;tails";
    print "    <th class='ui-widget-header ui-state-default' colspan=2>Type de valeur";
    print "<tr><td class='ui-widget-content'><input type='text' class='required' name='nom'>";
    print "    <td class='ui-widget-content'><textarea style='width:100%' name='description'></textarea>";
    print "    <td class='ui-widget-content'><input style='width:100%' name='extraCss'>";
    print "    <td class='ui-widget-content'><SELECT name='inDetList' id='inDetList' class='required'><option value=1>Oui</option><option value=0>Non</option>";
    print "    <td class='ui-widget-content'><SELECT name='type_valeur' id='type_valeur' class='required double noSelDeco'>";

    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Chrono_key_type_valeur ORDER BY hasSubValeur, nom";
    $sql = $db->query($requete);
    print '<OPTION value="">S&eacute;lection -></OPTION>';
    $i=0;
    while($res = $db->fetch_object($sql))
    {
        if ($res->hasSubValeur == 1)
        {
            print "</OPTGROUP>";
            print "<OPTGROUP label='".$res->nom."'>";
            $requete1 = "SELECT ".$res->subValeur_idx." as idx, ".$res->subValeur_text." as val FROM ".$res->subValeur_table."";
            
            $sql1 = $db->query($requete1);
            while ($res1=$db->fetch_object($sql1))
            {
                print '<OPTION value="'.$res1->idx.'">'.$res1->val.'</OPTION>';
            }
            print "</OPTGROUP>";
        } else {
            if ($i==0) print "<OPTGROUP label='Simple'>";
            print '<OPTION value="'.$res->id.'">'.$res->nom.'</OPTION>';
        }
        $i++;
    }
    print "<td class='ui-widget-content'><div id='dest2el'></div>";
    print "<tr><th class='ui-widget-header ui-state-default' colspan=5><button class='butAction'>Ajouter</button>";
    print '</table>';
    print "</div>";
    print "</div>";
    print "<div id='delDialog'>&Ecirc;tes vous sur de vouloir supprimer cette clef ?</div>";


llxFooter('$Date: 2005/10/03 01:36:21 $ - $Revision: 1.23 $');
?>
