<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 6 juil. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : index.php
  * GLE-1.1
  */

require_once("./pre.inc.php");
require_once('Affaire.class.php');
require_once('fct_affaire.php');
$affaireid=$_REQUEST['id'];

//$requete = "SELECT * FROM babel_categorie_template_key";
//$sql = $db->query($requete);
//while ($res = $db->fetch_object($sql)){
//    $requete = "UPDATE babel_categorie_template_key SET nom='".SynSanitize($res->nom)."' WHERE id = ".$res->id;
//    print $requete. "<br/>";
//    $sql1 = $db->query($requete);
//    if ($sql1) print $res->rowid ." OK <br/><br/><br/>";
//}
//exit;


if (!$user->rights->affaire->lire) accessforbidden();

// Securite acces client
if ($user->societe_id > 0)
{
  $socid = $user->societe_id;
}

$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';
$js = ''."\n";

/*
*    View
*/
$js.=<<<EOF
<script>
jQuery(document).ready(function(){
    jQuery.datepicker.setDefaults(jQuery.extend({
                    showMonthAfterYear: false,
                    dateFormat: 'dd/mm/yy',
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    buttonImage: 'cal.png',
                    buttonImageOnly: true,
                    showTime: true,
                    duration: '',
                    constrainInput: false,
                }, jQuery.datepicker.regional['fr']));

    jQuery('.datepicker').datepicker();

})
</script>
EOF;

//launchRunningProcess($db,'Affaire',$_GET['id']);
llxHeader($js,$langs->trans("Fiche affaire"));

print_fiche_titre($langs->trans("Espace affaire"));

if ($user->rights->affaire->valider && $_REQUEST['action']=="valid")
{
    $requete = "UPDATE Babel_Affaire SET statut = 1 WHERE id =".$affaireid;
    $sql=$db->query($requete);
    if (!$sql)
    {
        $msg = "Mise &agrave; jour impossible";
    }
}
if ($user->rights->affaire->creer && $_REQUEST['action']=="cloture")
{
    $requete = "UPDATE Babel_Affaire SET statut = 4 WHERE id =".$affaireid;
    $sql=$db->query($requete);
    if (!$sql)
    {
        $msg = "Mise &agrave; jour impossible";
    }
}


if ($_REQUEST['action']=='modify')
{
    $nom = preg_replace('/\'/','\\\'',$_REQUEST['nom']);
    $description = preg_replace('/\'/','\\\'',$_REQUEST['description']);
    $requete = "UPDATE Babel_Affaire SET nom = '".$nom."', description='".$description."' WHERE id = ".$_REQUEST['id'];
    $sql = $db->query($requete);
    if ($sql)
    {
        //1 detecte si y'a des parametres extra
        require_once(DOL_DOCUMENT_ROOT.'/categories/template.class.php');
        require_once(DOL_DOCUMENT_ROOT.'/categories/categorie.class.php');

        $c = new Categorie($db);
        $cats = $c->containing($_REQUEST['id'],"affaire",3);

        $template = new Template($db);
        //Ajoute les parents
        $arrId = array();
        foreach($cats as $cat=>$c)
        {
            $c->db=$db;
            $meres = $c->get_meres();
            foreach($meres as $k=>$v)
            {
                $v->db=$db;
                $arrId=array_merge($arrId,recursParent($v));
            }
        }
        foreach($arrId as $k=>$v)
        {
            $tmpC = new Categorie($db,$v);
            $addElement=true;
            foreach($cats as $k1=>$v1)
            {
                if ($v1->id == $v)
                {
                    $addElement=false;
                }
            }
            if ($addElement)
            {
                $cats[]=$tmpC;
            }
        }

        if (sizeof($cats) > 0)
        {
            $title=$langs->trans("AffaireIsInCategories");
            $extraArr = array();
            //Liste des templates associes
            foreach ($cats as $cat)
            {
                $tplList = $template->fetch_by_cat($cat->id);
                if (count($tplList) > 0){
                    foreach($tplList as $key=>$val)
                    {
                        $i = 0;
                        $var=true;
                        $template->fetch($key);
                        $template->getKeys();
                        $template->getValues($_REQUEST['id'],"affaire");
                        foreach ($template->keysList as $key1=>$val1)
                        {
                            $var=!$var;
                            //2 recupere les parametres
                            $extraArr[$cat->id][$val1['nom']]=array("value"=>$_REQUEST[$val1['nom']], "template_id" => $val1['template_id']);
                        }
                    }
                }
            }
            //3 ajoute dans la database via template->create / template->update
//require_once('Var_Dump.php');
//
//            var_dump::display($extraArr);
            $template->setDatas($_REQUEST['id'],$extraArr,'affaire');
        }
    }
}



    $affaire = new Affaire($db);
    $affaire->fetch($affaireid);
    if ($_REQUEST['action'] == 'edit')
    {
        print "<form method=post action='".$_SERVER['PHP_SELF']."?id=".$affaire->id."'>";
        print "<input type='hidden' name='action' id='action' value='modify'>";
    }
    if ($msg)
    {
        print "<div class='ui-state-error error'>".$msg."</div>";
    }
    print_cartoucheAffaire($affaire,'index',$_REQUEST['action']);

    require_once(DOL_DOCUMENT_ROOT.'/categories/template.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/categories/categorie.class.php');

    //Get Categories
    $c = new Categorie($db);
    $cats = $c->containing($affaire->id,"affaire",3);
    //saveHistoUser($affaire->id, "affaire",$affaire->ref);

    $template = new Template($db);
    //Ajoute les parents
    $arrId = array();
    foreach($cats as $cat=>$c)
    {
        $c->db=$db;
        $meres = $c->get_meres();
        foreach($meres as $k=>$v)
        {
            $v->db=$db;
            $arrId=array_merge($arrId,recursParent($v));
        }
    }
    foreach($arrId as $k=>$v)
    {
        $tmpC = new Categorie($db,$v);
        $addElement=true;
        foreach($cats as $k1=>$v1)
        {
            if ($v1->id == $v)
            {
                $addElement=false;
            }
        }
        if ($addElement)
        {
            $cats[]=$tmpC;
        }
    }

    if (sizeof($cats) > 0)
    {
        print "<table cellpadding=15 width=100%>";

        $title=$langs->trans("AffaireIsInCategories");
        $var = true;
        foreach ($cats as $cat)
        {
            $tplList = $template->fetch_by_cat($cat->id);
            if (count($tplList) > 0){
                foreach($tplList as $key=>$val)
                {

                    $i = 0;
                    $var=true;
                    $template->fetch($key);
                    $template->getKeys();
                    global $affaire;
                    $template->getValues($affaire->id,'affaire');
                    $extra = "style='border-top: 0px transparent;'";
                    foreach ($template->keysList as $key1=>$val1)
                    {
                        if ($_REQUEST['action']=='edit')
                        {
                            $var=!$var;
                            print "<tr ".$bc[$var].">";
                            print '<th  width=200 '.$extra.'  class="ui-widget-header ui-state-default" valign="middle">'.$val1['description'].'</td>';
                            $value = $template->extraValue[$affaire->id][$val1['nom']]['value'];
                            $class='';
                            if ($val1['type']=='date')
                            {
                                $class='datepicker';
                            }
                            print '<td '.$extra.' class="ui-widget-content" valign="middle" colspan=1><input type="text" class="'.$class.'" value="'.$value.'" name="'.$val1['nom'].'" id="'.$val1['nom'].'"</td>';
                            $extra = "";
                            print "</tr>";
                        } else {
                            $var=!$var;
                            print "<tr ".$bc[$var].">";
                            print '<th  width=200 '.$extra.'  class="ui-widget-header ui-state-default" valign="middle">'.$val1['description']."</td>";
                            $value = $template->extraValue[$affaire->id][$val1['nom']]['value'];
                            print '<td '.$extra.' class="ui-widget-content" valign="middle" colspan=1>'.$value."</td>";
                            $extra = "";
                            print "</tr>";
                        }
                    }
                    print "</tbody>";
                    if ($_REQUEST['action'] == "edit")
                    {
                        print "<tfoot><tr><td colspan=2 align=center class='ui-widget-content'><button style='padding: 5px 10px' class='ui-widget-header ui-state-default butAction ui-corner-all'>Modifier</button>";
                    }
                }
            } else {
    /*            print "<tr><td valign=top colspan=3>Pas d'extension produit pour cette cat&eacute;gorie";*/
            }
        }
        print "</table>";
        if ($_REQUEST['action'] == "edit")
        {
            print "</form>";
        }
   }

print "\n<div class=\"tabsAction\">\n";

if ($_GET["action"] != 'edit')
{
    if ( $user->rights->affaire->creer && $affaire->statut < 2)
    {
        print '<a class="butAction" href="fiche.php?action=edit&amp;id='.$affaire->id.'">'.$langs->trans("Modify").'</a>';
    }
    if ( $user->rights->affaire->valider && $affaire->statut == 0)
    {
        print '<a class="butAction" href="fiche.php?action=valid&amp;id='.$affaire->id.'">'.$langs->trans("Validate").'</a>';
    }
    if ( $user->rights->affaire->creer && $affaire->statut == 1)
    {
        print '<a class="butAction" href="fiche.php?action=cloture&amp;id='.$affaire->id.'">'.$langs->trans("Cl&ocirc;turer").'</a>';
    }
    if ($user->rights->affaire->effacer)
    {
        print '<a class="butActionDelete" href="fiche.php?action=delete&amp;id='.$affaire->id.'">'.$langs->trans("Delete").'</a>';
    }
}



    function recursParent($c)
    {
        global $db;
        $c->db=$db;
        $meres = $c->get_meres();
        $arrId=array();
        $arrId[]=$c->id;
        foreach($meres as $k=>$v)
        {
            $arrId=array_merge($arrId,recursParent($v));
        }
        return($arrId);
    }

?>
