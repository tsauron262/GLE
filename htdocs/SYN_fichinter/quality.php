<?php
/*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 24 nov. 2010
  *
  * Infos on http://www.synopsis-erp.com
  *
  */
 /**
  *
  * Name : quality.php
  * GLE-1.2
  */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/modules/synopsisficheinter/modules_synopsisfichinter.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/fichinter.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
if ($conf->projet->enabled)
{
    require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");
    require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
}
if (defined("FICHEINTER_ADDON") && is_readable(DOL_DOCUMENT_ROOT ."/includes/modules/synopsisficheinter/mod_".FICHEINTER_ADDON.".php"))
{
    require_once(DOL_DOCUMENT_ROOT ."/includes/modules/synopsisficheinter/mod_".FICHEINTER_ADDON.".php");
}

$langs->load("companies");
$langs->load("interventions");

// Get parameters
$fichinterid = isset($_REQUEST["id"])?$_REQUEST["id"]:'';


// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'synopsisficheinter', $fichinterid, 'fichinter');

$html = new Form($db);
$formfile = new FormFile($db);
$js  = "<script type='text/javascript' src='".DOL_URL_ROOT."/Synopsis_Common/jquery/jquery.rating.js'></script>";
$js .= '<link rel="stylesheet"  href="'.DOL_URL_ROOT.'/Synopsis_Common/css/jquery.rating.css" type="text/css" ></link>';

llxHeader($js);

    if ($_REQUEST['action']=="validate")
    {
//        var_dump($_REQUEST);
        $remId = array();
        foreach($_REQUEST as $key=>$val){
            if (preg_match('/^extraKey-([0-9]*)/',$key,$arr) ||preg_match('/^type-([0-9]*)/',$key,$arr)){
                $idExtraKey = $arr[1];
                if ($remId[$idExtraKey]){
                    continue;
                } else {
                    $remId[$idExtraKey] = true;
                }
                $type = $_REQUEST['type-'.$idExtraKey];
                //Y'a quelque chose ?
                $requete = "DELETE
                              FROM llx_Synopsis_fichinter_extra_value
                             WHERE interv_refid = ".$_REQUEST['id']. "
                               AND typeI = 'FI'
                               AND extra_key_refid= ".$idExtraKey;
                $sql = $db->query($requete);
                if ($type == 'checkbox')
                {
                    if($val == 'On' || $val =='on' || $val=='ON'){
                         $requete = "INSERT INTO llx_Synopsis_fichinter_extra_value
                                                 (interv_refid,extra_key_refid,extra_value,typeI)
                                          VALUES (".$_REQUEST['id'].",".$idExtraKey.",1,'FI')";
                          $sql = $db->query($requete);
                    } else {
                         $requete = "INSERT INTO llx_Synopsis_fichinter_extra_value
                                                 (interv_refid,extra_key_refid,extra_value,typeI)
                                          VALUES (".$_REQUEST['id'].",".$idExtraKey.",0,'FI')";
                         $sql = $db->query($requete);
                    }
                } else if($type=='3stars' && $_REQUEST['extraKey-'.$idExtraKey]."x" =="x"){
                     $requete = "INSERT INTO llx_Synopsis_fichinter_extra_value
                                             (interv_refid,extra_key_refid,extra_value,typeI)
                                      VALUES (".$_REQUEST['id'].",".$idExtraKey.",NULL,'FI')";
                      $sql = $db->query($requete);
                } else {
                     $requete = "INSERT INTO llx_Synopsis_fichinter_extra_value
                                             (interv_refid,extra_key_refid,extra_value,typeI)
                                      VALUES (".$_REQUEST['id'].",".$idExtraKey.",'".addslashes($val)."','FI')";
                      $sql = $db->query($requete);
                }
            }
        }
    }

if ($_REQUEST["id"] > 0) {
    /*
    * Affichage en mode visu
    */
    $fichinter = new Fichinter($db);
    $result=$fichinter->fetch($_REQUEST["id"]);
    if (! $result > 0)
    {
        dol_print_error($db);
        exit;
    }
    $fichinter->fetch_client();

    if ($mesg) print $mesg."<br>";

    $head = Synopsis_fichinter_prepare_head($fichinter);

    dol_fiche_head($head, 'quality', $langs->trans("InterventionCard"));

    /*
    * Confirmation de la suppression de la fiche d'intervention
    */
    if ($_REQUEST['action'] == 'delete')
    {
        $html->form_confirm($_SERVER["PHP_SELF"].'?id='.$fichinter->id, $langs->trans('DeleteIntervention'), $langs->trans('ConfirmDeleteIntervention'), 'confirm_delete');
        print '<br>';
    }

    /*
    * Confirmation de la validation de la fiche d'intervention
    */


    print '<table class="border" cellpadding=15 width="100%">';

    // Ref
    print '<tr><th class="ui-widget-header ui-state-default" width="25%">'.$langs->trans("Ref").'</th>
               <td colspan=3 class="ui-widget-content">'.$fichinter->ref.'</td></tr>';

    // Societe
    print "<tr><th class='ui-widget-header ui-state-default'>".$langs->trans("Company")."</th>
               <td colspan=3 class='ui-widget-content'>".$fichinter->client->getNomUrl(1)."</td></tr>";

//Ref contrat ou commande
    if ($fichinter->fk_contrat > 0)
    {
        print "<tr><th class='ui-widget-header ui-state-default'>Contrat</th>";
        require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
        $contrat=new Contrat($db);
        $contrat->fetch($fichinter->fk_contrat);
        print "    <td class='ui-widget-content'>".$contrat->getNomUrl(1)."</td> ";
    } else if ($fichinter->fk_commande > 0){
        print "<tr><th class='ui-widget-header ui-state-default'>Commande</th>";
        require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
        $com=new Commande($db);
        $com->fetch($fichinter->fk_commande);
        print "<td colspan=3 class='ui-widget-content'>".$com->getNomUrl(1);

    }
    // Date
    print '<tr><th class="ui-widget-header ui-state-default">';
    print $langs->trans('Date');
    if ($_REQUEST['action'] != 'editdate_delivery' && $fichinter->brouillon) print '<a href="'.$_SERVER["PHP_SELF"].'?action=editdate_delivery&amp;id='.$fichinter->id.'">'.img_edit($langs->trans('SetDateCreate'),1).'</a>';
    print '</th><td colspan="3" class="ui-widget-content">';
    if ($_REQUEST['action'] == 'editdate_delivery')
    {
        print '<form name="editdate_delivery" action="'.$_SERVER["PHP_SELF"].'?id='.$fichinter->id.'" method="post">';
        print '<input type="hidden" name="action" value="setdate_delivery">';
        $html->select_date($fichinter->date,'liv_','','','',"editdate_delivery");
        print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
        print '</form>';
    } else {
        print dol_print_date($fichinter->date,'day');
    }
    print '</td>';
    print '</tr></table><br><br/>';
    print "<div class='titre'>Questionnaire</div>";
    if ($_REQUEST['action']=="validate")
    {
        print "<div class='ui-state-highlight'>Le questionnaire a &eacute;t&eacute; valid&eacute;";
    }
    print "<form action='quality.php?id=".$_REQUEST['id']."' method='POST'>";
    print "<input type='hidden' name='action' value='validate'>";
    print '<table class="border" cellpadding=15 width="100%">';

    $requete = "SELECT k.label,
                       k.type,
                       k.id,
                       v.extra_value, description
                  FROM llx_Synopsis_fichinter_extra_key as k
             LEFT JOIN llx_Synopsis_fichinter_extra_value as v ON v.extra_key_refid = k.id AND v.interv_refid = ".$fichinter->id." AND typeI = 'FI'
                 WHERE k.active = 1 AND isQuality = 1";
    $sql = $db->query($requete);
    while ($res=$db->fetch_object($sql))
    {
        print '<tr><th align=left colspan=2 class="ui-widget-header ui-state-default">'.$res->description;
//        if ($_REQUEST['action']=='editExtra-'.$res->id)
//        {
            switch ($res->type)
            {
                case "date":
                {
                    print "<td colspan=2 valign='middle' class='ui-widget-content'>";
                    print "<input type='hidden' name='type-".$res->id."' value='date'>";
                }
                break;
                case "textarea":
                {
                    print "<td colspan=2 valign='middle' class='ui-widget-content'><textarea style='width:100%; height: 4em;' name='extraKey-".$res->id."'>".$res->extra_value."</textarea>";
                    print "<input type='hidden' name='type-".$res->id."' value='comment'>";
                }
                break;
                default:
                case "text":
                {
                    print '<td colspan=2 valign="middle" class="ui-widget-content">&nbsp;&nbsp;<input value="'.$res->extra_value.'" type="text" name="extraKey-'.$res->id.'">';
                    print "<input type='hidden' name='type-".$res->id."' value='text'>";
                }
                break;
                case "datetime":
                {
                    print "<td colspan=2 valign='middle' class='ui-widget-content'>&nbsp;&nbsp;<input type='text' value='".$res->extra_value."' name='extraKey-".$res->id."' class='dateTimePicker'>";
                    print "<input type='hidden' name='type-".$res->id."' value='datetime'>";
                }
                break;
                case "checkbox":
                {
                    print "<td colspan=2 valign='middle' class='ui-widget-content'>&nbsp;&nbsp;<input type='checkbox' ".($res->extra_value==1?'CHECKED':"")."  name='extraKey-".$res->id."'>";
                    print "<input type='hidden' name='type-".$res->id."' value='checkbox'>";
                }
                break;
                case "3stars":
                {
                    print "<td colspan=2 valign='middle' class='ui-widget-content'>&nbsp;&nbsp;";
//                    print "<input type='checkbox' ".($res->extra_value==1?'CHECKED':"")."  name='extraKey-".$res->id."'>";
                    print starratingPhp("extraKey-".$res->id,($res->extra_value=='Moyen'?1:($res->extra_value=='Non'?0:($res->extra_value?1:-1))),3,$iter=1);
                    print "<input type='hidden' name='type-".$res->id."' value='3stars'>";
                }
                break;
                case "5stars":
                {
                    print "<td colspan=2 valign='middle' class='ui-widget-content'>&nbsp;&nbsp;";
//                    print "<input type='checkbox' ".($res->extra_value==1?'CHECKED':"")."  name='extraKey-".$res->id."'>";
                    print starratingPhp("extraKey-".$res->id,$res->extra_value,5,$iter=1);
                    print "<input type='hidden' name='type-".$res->id."' value='5stars'>";
                }
                break;
                case "radio":
                {
                    print "<td colspan=2 valign='middle' class='ui-widget-content'>";
                    $requete= "SELECT * FROM llx_Synopsis_fichinter_extra_values_choice WHERE key_refid = ".$res->id;
                    $sql1 = $db->query($requete);
                    if ($db->num_rows($sql1)> 0)
                    {
                        print "<table width=100%>";
                        while ($res1 = $db->fetch_object($sql1))
                        {
                            print "<tr><td width=100%>".$res1->label."<td>";
                            print "<input type='radio' value='".$res1->value."' name='extraKey-".$res->id."'>";
                        }
                        print "</table>";
                    }
                    print "<input type='hidden' name='type-".$res->id."' value='radio'></td>";
                }
                break;

            }
//        } else {
//            print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$_REQUEST['id'].'&action=editExtra-'.$res->id.'">'.img_edit($langs->trans('Editer'),1).'</a>';
//            if ($res->type=='checkbox')
//            {
//                print '    <td colspan=3 class="ui-widget-content">'.($res->extra_value==1?'Oui':'Non').'</td></tr>';
//            } else if($res->type=='radio'){
//                $requete = "SELECT * FROM llx_Synopsis_fichinter_extra_values_choice WHERE key_refid = ".$res->id." AND value = ".$res->extra_value;
//                $sql1 = $db->query($requete);
//                $res1 = $db->fetch_object($sql1);
//                print '    <td colspan=3 class="ui-widget-content">'.$res1->label.'</td></tr>';
//            } else {
//                print '    <td colspan=3 class="ui-widget-content">'.$res->extra_value.'</td></tr>';
//            }
//        }
    }
        print '<tr><th colspan=4 class="ui-widget-header"><button class="ui-button">Valider</button>';
        print "</table></form>";
print <<<EOF
<script>
jQuery(document).ready(function(){
        jQuery.datepicker.setDefaults(jQuery.extend({showMonthAfterYear: false,
                        dateFormat: 'dd/mm/yy',
                        changeMonth: true,
                        changeYear: true,
                        showButtonPanel: true,
                        buttonImage: 'cal.png',
                        buttonImageOnly: true,
                        showTime: false,
                        duration: '',
                        constrainInput: false,}, jQuery.datepicker.regional['fr']));
        jQuery('.datePicker').datepicker();
        jQuery('.dateTimePicker').datepicker({showTime:true});
});
</script>
EOF;

}

function starratingPhp($name,$value,$max=5,$iter=1)
{
    $ret = '<div id="starrating">';
    $arrStar = array();
    if ($max==3)
    {
        $arrStar = array(0=>"Non",1=>"Moyen",2=>"Oui");
    } else if ($max == 5)
    {
        $arrStar = array(0=>"0%",1=>"25%",2=>"50%",3=>"75%",4=>"100%");
    }
    for($i=0;$i<$max;$i+=$iter)
    {
        $valDisp = preg_replace('/,/',".",$i);
        if ($arrStar[$valDisp])
        {
            $valDisp = $arrStar[$valDisp];
        }
        if ($value == $i)
        {
            $ret .= '<input class="star" type="radio" name="'.$name.'" value="'.$valDisp.'" checked="checked"/>';
        } else {
            $ret .= '<input class="star" type="radio" name="'.$name.'" value="'.$valDisp.'"/>';
        }
    }
    $ret .= '</div>';
//    var_dump($ret);
    return ($ret);
}



function sec2time($sec){
    $returnstring = " ";
    $days = intval($sec/86400);
    $hours = intval ( ($sec/3600) - ($days*24));
    $minutes = intval( ($sec - (($days*86400)+ ($hours*3600)))/60);
    $seconds = $sec - ( ($days*86400)+($hours*3600)+($minutes * 60));

    $returnstring .= ($days)?(($days == 1)? "1 j":$days."j"):"";
    $returnstring .= ($days && $hours && !$minutes && !$seconds)?"":"";
    $returnstring .= ($hours)?( ($hours == 1)?" 1h":" " .$hours."h"):"";
    $returnstring .= (($days || $hours) && ($minutes && !$seconds))?"  ":" ";
    $returnstring .= ($minutes)?( ($minutes == 1)?" 1 min":" ".$minutes."min"):"";
    //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
    //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
    return ($returnstring);
}
?>