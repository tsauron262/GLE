<?php
/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */



require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once("./Campagne.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");

$html = new Form($db);
$langs->load("synopsisGene@synopsistools");
$langs->load('companies');
$langs->load('compta');
$langs->load('orders');
$langs->load('propal');

$campagne_id = $_REQUEST['campagneId'];
$campagne = new Campagne($db);
$campagne->fetch($campagne_id);

$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";


//Load jquery
$js .= ' <script src="'.$jspath.'/jqGrid-3.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';


$js .= '<script language="javascript" src="'.$jspath.'/jquery.scrollTo.js"></script>'."\n";

$js .= '<script language="javascript" src="'.$jspath.'/jquery.metadata.js"></script>'."\n";
$js .= '<script language="javascript" src="'.$jspath.'/jquery.rating.js"></script>'."\n";


$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/jquery.rating.css" />';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="css/afficheProspection.css" />';

    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';

$js .= ' <script src="js/afficheprospect.js" type="text/javascript"></script>';
$js .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu({style: 'dropdown', maxHeight: 300 }); });  </script>\n";


$js .= "<script type='text/javascript'> var DOL_URL_ROOT='".DOL_URL_ROOT."';";
$js .= "  var DOL_DOCUMENT_ROOT='".DOL_DOCUMENT_ROOT."';";
$js .= "  var DOL_URL_ROOT='".DOL_URL_ROOT."';";
$js .= "  var campId='".$campagne_id."';";
$js .= "  var userId='".$user->id."';";
$campagne->stats();
$js .= "  var Progress='".$campagne->statCampagne['avancement']."';";
$nexSoc = $campagne->getNextSoc();
$js .= "  var SOCID='".$nexSoc."';";
$soc=new Societe($db);
$soc->fetch($nexSoc);
$js .= "  var SOCNAME='".$soc->nom."';";
$js .= "  var SOCNAMELONG='".$soc->getNomUrl(1)."';";

$js .= "   </script>\n";

//launchRunningProcess($db,'Campagne',$_GET['campagneId']);

llxHeader($js,"Prospection","1");


if ( ! $user->rights->prospectbabe->Prospection->Affiche || ! $user->rights->prospectbabe->Prospection->permAccess)
{
    accessforbidden();
}

print "<a rel='toptop'>&nbsp;</a>";

//Dialog ************************************************************************
    print '<div id="newActComDialog" style="display: none;">';
    print '<form id="formDialog" action="#"><fieldset>';
    print '<legend>';
    print 'Ajouter une action commerciale : ';
    print '</legend>';
    print '<table style="width: 330px;">';

    $requete = "SELECT *
                  FROM ".MAIN_DB_PREFIX."c_actioncomm
                 WHERE active = 1
              ORDER BY libelle";
    $sql = $db->query($requete);
    print '<tr><td style="width: 75px;">Soci&eacute;t&eacute; :</td><td><div id="societeNameActComm"></div></td></tr>';
    print '<tr><td style="width: 75px;">Titre<em>*</em>&nbsp;&nbsp;</td><td><input id="TitreActComm"  name="TitreActComm" type="text"></td></tr>';
    print '<tr><td style="width: 75px;">D&eacute;but<em>*</em>&nbsp;&nbsp;</td><td>'.img_object("Date de d&eacute;but",'calendar',16,16,"absmiddle").'&nbsp;&nbsp;<input id="dateDebActComm" name="dateDebActComm" type="text"></td></tr>';
    print '<tr><td style="width: 75px;">Fin<em>*</em>&nbsp;&nbsp;</td><td>'.img_object("Date de fin",'calendar',16,16,"absmiddle").'&nbsp;&nbsp;<input id="dateFinActComm" name="dateFinActComm" type="text"></td></tr>';
    print '<tr><td id="TypeActCommTD" style="width: 75px;">Type<em>*</em>&nbsp;&nbsp;</td><td><select name="TypeActComm" id="TypeActComm" ><option value="">Selection-></option>';
    while ($res = $db->fetch_object($sql))
    {
        print '<option value="'.$res->code.'">'.$langs->trans($res->code).'</option>';
    }
    print '</select></td></tr>';


    print '<tr><td style="width: 75px;">Contact :</td><td><select id="ContactActComm" name="ContactActComm"><option val="-1">Selection-></option></select></td></tr>';

    print '<tr><td style="width: 75px;">Affect&eacute; &agrave; :<em>*</em>&nbsp;&nbsp;</td><td><select  name="AffecteAActComm" id="AffecteAActComm">';

    if ($user->rights->agenda->myactions->create)
        print "<option SELECTED value='".$user->id."'>Moi m&ecirc;me</option>";

    if ($user->rights->agenda->allactions->create)
    {
        $requete = "SELECT *
                      FROM ".MAIN_DB_PREFIX."user
                     WHERE statut = 1
                       AND rowid <> ".$user->id."
                  ORDER BY firstname, lastname";
        $sql = $db->query($requete);
        while ($res = $db->fetch_object($sql))
        {
            print "<option value='".$res->rowid."'>".$res->firstname. " " .$res->name."</option>";
        }
    }
    print '</select></td></tr>';
    print '<tr><td style="width: 75px;">Note</td><td><textarea id="noteActComm" name="noteActComm" style="width: 100%; height: 100%"></textarea></td></tr>';
    print '</table>';

    //Type  => select fixe
    //Titre => champs libre
    //Action concernant la societe => recupere le socId / le name
    //Action concernant la contact => recupere la liste des contacts de la societe
    //Action affectee a  => select fixe
    //Date debut => ui.timepicker
    //Date fin => ui.timepicker
    //Note => champs libre

    print '</fieldset></form>';
    print '</div>';
///  fin dialgog action comm ***************
///  dialog contact ************************

    print '<div id="newContDialog" style="display: none;">';
    print '<form id="formDialogCont" action="#"><fieldset>';
    print '<legend>';
    print 'Ajouter un contact : ';
    print '</legend>';
    print '<table style="width: 330px;">';

    print '<tr><td style="width: 75px;">Soci&eacute;t&eacute; :</td><td><div id="societeNameCont"></div></td></tr>';
    print '<tr><td style="width: 75px;">Civilit&eacute; :</td><td>';
    print '<select id="contactCivil" name="contactCivil">';
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."c_civility WHERE active = 1 ORDER BY rowid";
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($sql))
    {
        print "<option value='".$res->civility."' >".$res->civility."</option>";
    }
    print '</select></td></tr>';
    print '<tr><td style="width: 75px;"><em>*</em>&nbsp;&nbsp;Nom :</td><td><input id="contactNom"  name="contactNom" type="text"></td></tr>';
    print '<tr><td style="width: 75px;">Pr&eacute;nom</td><td><input id="contactPrenom"  name="contactPrenom" type="text"></td></tr>';
    print '<tr><td style="width: 75px;">Email</td><td><input id="contactEmail"  name="contactEmail" type="text"></td></tr>';
    print '<tr><td style="width: 75px;">GSM</td><td><input id="contactGSM"  name="contactGSM" type="text"></td></tr>';
    print '<tr><td style="width: 75px;">Tel</td><td><input id="contactTEL"  name="contactTEL" type="text"></td></tr>';
    print '<tr><td style="width: 75px;">Note</td><td><textarea id="noteContact" name="noteContact" style="width: 100%; height: 100%"></textarea></td></tr>';
    print '</table>';

    //Type  => select fixe
    //Titre => champs libre
    //Action concernant la societe => recupere le socId / le name
    //Action concernant la contact => recupere la liste des contacts de la societe
    //Action affectee a  => select fixe
    //Date debut => ui.timepicker
    //Date fin => ui.timepicker
    //Note => champs libre

    print '</fieldset></form>';
    print '</div>';

///Dialog fermeture


    print '<div id="closeDialog" style="display: none;">';
    print '<form id="formDialogClose" action="#"><fieldset>';
    print '<legend>';
    print 'Cl&ocirc;turer la campagne pour ce prospect : ';
    print '</legend>';
    print '<table style="width: 330px;">';

    print '<tr><td style="width: 75px;"><em>*</em>&nbsp;&nbsp;Note</td><td><textarea id="noteClose" name="noteClose" style="width: 100%; height: 100%"></textarea></td></tr>';
    print '<tr><td style="width: 75px;"><em>*</em>&nbsp;&nbsp;Statut</td><td id="colStcomm">&nbsp';
    print ''.img_action_jquery(0,-1).' &nbsp;&nbsp;';
    print ''.img_action_jquery(0,0).' &nbsp;&nbsp;';
    print ''.img_action_jquery(0,1).' &nbsp;&nbsp;';
    print ''.img_action_jquery(0,2).' &nbsp;&nbsp;';
    print ''.img_action_jquery(0,3).'';
    print "<input type='hidden' name='StcommClose' id='StcommClose' value=''></input>";

function img_action_jquery($alt = "default", $numaction)
{
    global $conf,$langs;
    if ($alt=="default") {
        if ($numaction == -1) $alt=$langs->trans("ChangeDoNotContact");
        if ($numaction == 0)  $alt=$langs->trans("ChangeNeverContacted");
        if ($numaction == 1)  $alt=$langs->trans("ChangeToContact");
        if ($numaction == 2)  $alt=$langs->trans("ChangeContactInProcess");
        if ($numaction == 3)  $alt=$langs->trans("ChangeContactDone");
    }
    return '<div class="imgSel" id="stCommImg'.$numaction.'" onClick="jqSelImage(this);"><img align="absmiddle"    src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/stcomm'.$numaction.'.png" border="0" alt="'.$alt.'" title="'.$alt.'"></div>';
}


    print '</td></tr>';

    print '</td></tr>';
    print '<tr><td style="width: 75px;"><em>*</em>&nbsp;&nbsp;R&eacute;sultat</td><td><select name="resultClose" id="resultClose"><option value="">S&eacute;lection</option>';
    print '<option value="1">Positif</option>';
    print '<option value="2">N&eacute;gatif</option>';
    print '</select> </td></tr>';
    print '</table>';

    //Type  => select fixe
    //Titre => champs libre
    //Action concernant la societe => recupere le socId / le name
    //Action concernant la contact => recupere la liste des contacts de la societe
    //Action affectee a => select fixe
    //Date debut => ui.timepicker
    //Date fin => ui.timepicker
    //Note => champs libre

    print '</fieldset></form>';
    print '</div>';

///Dialog GiveTo


    print '<div id="giveToDialog" style="display: none;">';
    print '<form id="formDialogGiveTo" action="#"><fieldset>';
    print '<legend>';
    print 'Repasser le contact &agrave; : ';
    print '</legend>';
    print '<table style="width: 330px;">';

    print '<tr><td style="width: 75px;"><em>*</em>&nbsp;&nbsp;Note</td><td><textarea id="noteGiveTo" name="noteGiveTo" style="width: 100%; height: 100%"></textarea></td></tr>';

    $requete = "SELECT Babel_campagne_people.isResponsable,
                       ".MAIN_DB_PREFIX."user.rowid as uid,
                       ".MAIN_DB_PREFIX."user.lastname as uname,
                       ".MAIN_DB_PREFIX."user.firstname as ufirstname
                  FROM ".MAIN_DB_PREFIX."user,
                       Babel_campagne_people
                 WHERE ".MAIN_DB_PREFIX."user.rowid = Babel_campagne_people.user_refid
                   AND campagne_refid = ". $campagne->id."
                   AND ".MAIN_DB_PREFIX."user.rowid <> ".$user->id."
              ORDER BY ".MAIN_DB_PREFIX."user.lastname, ".MAIN_DB_PREFIX."user.firstname";
    $sql = $db->query($requete);
    print "<tr><td style='width: 75px;'><em>*</em>&nbsp;&nbsp;Passer &agrave;</td><td><select name='userGiveTo' id='userGiveTo' ><option value=''>S&eacute;lectionner-></option>";
    while ($res = $db->fetch_object($sql))
    {
        print "<option value='".$res->uid."'>".$res->ufirstname . " " . $res->uname."</option>";
    }
    print "</select>";
    print "</td></tr>";



    print '</table>';

    //Type  => select fixe
    //Titre => champs libre
    //Action concernant la societe => recupere le socId / le name
    //Action concernant la contact => recupere la liste des contacts de la societe
    //Action affectee a  => select fixe
    //Date debut => ui.timepicker
    //Date fin => ui.timepicker
    //Note => champs libre

    print '</fieldset></form>';
    print '</div>';

///  dialog postpone ************************

    print '<div id="newPostPoneDialog" style="display: none;">';
    print '<fieldset>';
    print '<legend>';
    print 'Repousser le RDV : ';
    print '</legend>';
    print '<table style="width: 330px;">';

    print '<tr><td style="width: 75px;">Soci&eacute;t&eacute;</td><td><div id="societeNamePostPone"></div></td></tr>';
    print '<tr><td style="width: 75px;">Accomplissement</td><td><div id="PostPoneAvancement"  name="PostPoneAvancement" style="width: 148px;"></div></td></tr>';
    print '</table>';
    print '<form id="formDialogPostPone" action="#">';
    print '<table style="width: 330px;">';

    print '<tr><td style="width: 75px;"><em>*</em>&nbsp;&nbsp;Raison</td><td><input id="PostPoneRaison"  name="PostPoneRaison" type="text"></td></tr>';
    print '<tr><td style="width: 75px;"><em>*</em>&nbsp;&nbsp;Date</td><td>'.img_object("Prochaine date : ",'calendar',16,16,"absmiddle").'&nbsp;&nbsp;<input id="PostPoneDate"  name="PostPoneDate" type="text" style="width: 125px"></td></tr>';
    print '<tr><td style="width: 75px;"><em>*</em>&nbsp;&nbsp;Note</td><td><textarea id="PostPoneNote"  name="PostPoneNote" type="text"></textarea></td></tr>';
    print '</table>';
    print '</form>';
    print '<table style="width: 330px;">';
    print '<tr><td style="width: 75px;">Avis</td><td><div id="PostPoneAvisSR">';
print <<<EOF
    <div>
<input class="star {half:true}" type="radio" name="PostPoneAvis" value="0.5"/>
<input class="star {half:true}" type="radio" name="PostPoneAvis" value="1.0"/>
<input class="star {half:true}" type="radio" name="PostPoneAvis" value="1.5"/>
<input class="star {half:true}" type="radio" name="PostPoneAvis" value="2.0"/>
<input class="star {half:true}" type="radio" name="PostPoneAvis" value="2.5"/>
<input class="star {half:true}" type="radio" name="PostPoneAvis" value="3.0"/>
<input class="star {half:true}" type="radio" name="PostPoneAvis" value="3.5" checked="checked"/>
<input class="star {half:true}" type="radio" name="PostPoneAvis" value="4.0"/>
<input class="star {half:true}" type="radio" name="PostPoneAvis" value="4.5"/>
<input class="star {half:true}" type="radio" name="PostPoneAvis" value="5.0"/>
</div>
EOF;
    print '</div></td></tr>';
    //starratting

    print '</table>';

//Note
//Avancement vi icone comme prospect
//

    print '</fieldset></form>';
    print '</div>';

        //saveHistoUser($campagne->id, "campagne",$campagne->nom);

print '<div class="fiche" style="min-width: 1100px;"> ';

print '<div class="tabs">';
print '<a class="tabTitle">'.$langs->trans('Campagne Prospection').'</a>
       <a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=listCamp&id='.$campagne_id.'">'.$langs->trans('Fiche Campagne').'</a>';
//print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=stats&id='.$campagne_id.'">'.$langs->trans('Statistiques').'</a>';
if ($user->rights->prospectbabe->Prospection->Affiche)
{
    print '<a id="active" class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/affichePropection.php?action=list&campagneId='.$campagne_id.'">Prospection</a>';
}

if ($user->rights->prospectbabe->Prospection->recap)
{
    print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/recapCamp.php?campagneId='.$campagne_id.'">R&eacute;capitulatif</a>';
}
if ($user->rights->prospectbabe->Prospection->stats)
{
    print '<a  class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/statsCamp.php?campagneId='.$campagne_id.'">Statistiques</a>';
}
if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS && $user->rights->process->lire &&  DoesElementhasProcess($db,'Campagne'))
{
    print '<a class="tab" href="'.DOL_URL_ROOT.'/Synopsis_Process/listProcessForElement.php?type=Campagne&id='.$id.'">Process</a>';
}



print '</div>';
print '<div class="tabBar">';
print '<table cellpadding=10 class="border" width=100%>';
print '<tr><th class="ui-widget-header ui-state-default" width="20%">Nom<td class="pair">'.$campagne->nom."</td>";
print "<td rowspan='4' colspan='2' width='60%' style='color: black; background-color: rgb(253, 245, 163);'>".$campagne->notePublic."</td>";
print '<tr><th class="ui-widget-header ui-state-default">D&eacute;but<td class="impair">'.$campagne->dateDebut."</td>";
print '<tr><th class="ui-widget-header ui-state-default">Fin pr&eacute;vue<td class="pair">'.$campagne->dateFin."</td>";
print '<tr><th class="ui-widget-header ui-state-default">Nb Soci&eacute;t&eacute;<td class="impair">'.$campagne->statCampagne['qty']."</td>";
print '<tr><th class="ui-widget-header ui-state-default">Moyenne<td class="pair" id="statAvgDay">'.$campagne->statCampagne['avg_day']." Client(s)/jour</td>";
print '    <th class="ui-widget-header ui-state-default">Avancement<td class="pair"><div id="progressbar" style="width: 380px; border: 1px Solid #000000; float: left; height: 12px;"></div></td>';
print '<tr><th class="ui-widget-header ui-state-default">Statut<td colspan="3" class="impair">'.$campagne->getLibStatut(5)."</td>";
print '<tr><th class="ui-widget-header ui-state-default" id="noteCampForHeight" ">Note sur la soci&eacute;t&eacute; pour la campagne </td><td class="pair" colspan="3" id="note"><div style="width: 100%; " >&nbsp;</div></td>';
print '<tr><th class="ui-widget-header ui-state-default" id="HistoCamp">Historique de la soci&eacute;t&eacute; pour la campagne </td><td class="impair" colspan="3" id="noteAvancement"><div id="scrollDown" style="width: 100%; overflow-y:auto; max-height: 55px;" >';
print "<div style='padding: 20pt;'>";
print "<center><div class='ui-corner-all' style='border: 1px #4B4D7F Solid; background-color: #EEEEFF; font-size: 12pt; padding: 0pt;margin: 2pt; width: 300px; position: relative;'><img align=absmiddle src='".DOL_URL_ROOT."/theme/auguria/img/ajax-loaer-big.gif'/><span style='color:#4B4D7F; font-weight: 900;'>&nbsp;&nbsp;&nbsp;&nbsp;Chargement en cours</span></div></center>";
print '</div>';
print '</div></td>';
$campagne->getSocNoteAvanc($soc->id);
$socAvanc = ($campagne->SocAvanc . "x" == "x"?0:$campagne->SocAvanc * 10);
$socNote = ($campagne->SocNote . "x" == "x"?0:$campagne->SocNote * 1);
print '<script type="text/javascript">var ProgressSoc = '.$socAvanc .';</script>';
print '<script type="text/javascript">var NoteSoc = '.$socNote .';</script>';
print '<tr><th class="ui-widget-header ui-state-default" id="AvancCamp">Progression prospection<td colspan=2 class="impair" id="AvancementMain"><div id="MainProgress" style="width: 458px; border: 1px Solid #000000; float: left; height: 12px;"></div></td>';
print '<td id="NoteSocCamp" class="ui-widget-content>Avis de la soci&eacute;t&eacute; pour la campagne <td class="pair" style="max-height: 18px; line-height: 12px;"  ><div style=" max-height: 18px;width: 100%;" id="AvisMain" >';
//
for ($i=0.5;$i<=5;$i+=0.5)
{
//    if ($i == $campagne->SocNote * 1 )
//    {
//        print '<input class="star {half:true}" type="radio" name="MainAvis" disabled="disabled" checked value="'.$i.'"/>';
//    } else {
        print '<input class="star {half:true}" type="radio" name="MainAvis" disabled="disabled" value="n'.preg_replace('/,/','.',$i).'"/>';
//    }
}
//print <<<EOF
//    <div>
//<input class="star {half:true}" type="radio" name="MainAvis" disabled="disabled" value="0.5"/>
//<input class="star {half:true}" type="radio" name="MainAvis" disabled="disabled" value="1.0"/>
//<input class="star {half:true}" type="radio" name="MainAvis" disabled="disabled" value="1.5"/>
//<input class="star {half:true}" type="radio" name="MainAvis" disabled="disabled" value="2.0"/>
//<input class="star {half:true}" type="radio" name="MainAvis" disabled="disabled" value="2.5"/>
//<input class="star {half:true}" type="radio" name="MainAvis" disabled="disabled" value="3.0"/>
//<input class="star {half:true}" type="radio" name="MainAvis" disabled="disabled" value="3.5"/>
//<input class="star {half:true}" type="radio" name="MainAvis" disabled="disabled" value="4.0"/>
//<input class="star {half:true}" type="radio" name="MainAvis" disabled="disabled" value="4.5"/>
//<input class="star {half:true}" type="radio" name="MainAvis" disabled="disabled" value="5.0"/>
//</div>
//EOF;

//
print '</td></tr>';


print "</table>\n";
print '</div>';

print "<table style='margin-left: 10pt;'><tbody>";
print "<tr><td style='width: 150px;'>";

print "<div  id='giveToBut' class='ui-state-default ui-corner-all butaction'><center>";
print "<a href='#toptop'  class='butaction'>Donner &agrave;</a>";
print '</center></div>';

print "</td>";
if ($user->rights->societe->contact->creer == 1){
    print "<td style='width: 150px;'>";
    print "<div  id='newCont' class='ui-state-default ui-corner-all butaction'><center>";
    print "<a href='#toptop'  class='butaction'>Cr&eacute;er contact</a>";
    print '</center></div>';
    print "</td>";

}

if ($user->rights->agenda->myactions->create == 1 ||$user->rights->agenda->allactions->create == 1){
    print "<td style='width: 150px;'>";

    print "<div  id='newActComTop' class='ui-state-default ui-corner-all butaction'><center>";
    print "<a href='#toptop'  class='butaction'>Cr&eacute;er action</a>";
    print '</center></div>';
    print "</td>";
}
print "<td style='width: 150px;'>";

print "<div  id='postponeSoc' class='ui-state-default ui-corner-all butaction'><center>";
print "<a href='#toptop'  class='butaction'>Repousser</a>";
print '</center></div>';

print "</td>";
print "<td style='width: 150px;'>";

print "<div id='nextSoc' class='ui-state-default ui-corner-all butaction'><center>";
print "<a href='#toptop'  class='butaction'>Fermer</a>";
print '</center></div>';

print "</td></tr></tbody></table>";


print '<div id="socInfo">';
print "<div style='padding: 20pt;'>";
print "<center><div class='ui-corner-all' style='border: 1px #4B4D7F Solid; background-color: #EEEEFF; font-size: 16pt; padding: 10pt;margin: 10pt; width: 400px; position: relative;'><img align=absmiddle src='".DOL_URL_ROOT."/theme/auguria/img/ajax-loaer-big.gif'/><span style='color:#4B4D7F; font-weight: 900;'>&nbsp;&nbsp;&nbsp;&nbsp;Chargement en cours</span></div></center>";
print '</div>';
print '</div>';

?>
