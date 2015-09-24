<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 7-21-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : tests.php
  * GLE-1.0
  */



//Goal
//affiche les stats de la campagne
//affiche la fenetre de recherche des proscpect
//fenetre avec le client / prospect nom de la societe, ".MAIN_DB_PREFIX."c_forme_juridique la taille ,l'effectif, le secteur d'activite , le secteur geographique par alphabe


require_once("pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once("./Campagne.class.php");
$html = new Form($db);

$action = $_REQUEST['action'];
$id = $_REQUEST['id'];
//if ("x".$id == "x" && $action != "newAdd")
//{
//    $action = 'new';
//}
        global $user;
        global $langs;
        global $conf;


//Si acces au module de prospection
if ( ! $user->rights->prospectbabe->Prospection->Affiche || !$user->rights->prospectbabe->Prospection->permAccess )
{
    accessforbidden();
}

switch ($action)
{

    case 'add':
    case 'new':
        llxHeader();
        if ( ! $user->rights->prospectbabe->Prospection->Ecrire)
        {
            accessforbidden();
        }
        print "<br>";
        print "<form action='?action=newAdd' method='POST'>";
        print '<table width="100%" class="notopnoleftnoright">';
        print "<tr><th style='padding:5px;' class='ui-widget-hover ui-state-default'>".$langs->trans('Nouvelle campagne de prospection');
        print '<tr><td valign="top" class="notopnoleft">';

        print '<table class="border" width="100%">';

        print '<tr><td class="ui-state-default ui-widget-header" width="15%">'.$langs->trans("D&eacute;signation de la campagne").'</td>';
        print "<td width='40%' colspan='1' class='ui-widget-content'>";
        print "<input type='text' name='nom' id='nom'></input>";
        print '</td>';
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."user";
        $selectUserMain= "";
        $selectUserSec= "";
        if ($resql = $db->query($requete))
        {
            $selectUserMain = "<SELECT name='Responsable[]' id='Responsable' >";
            $selectUserSec = " <SELECT name='comm[]' id='comm' multiple='multiple' size='4' >";
            while ($res=$db->fetch_object($resql))
            {
                $nom = ($res->firstname?$res->firstname." ".$res->name:$res->name);
                if ($res->rowid == $user->id)
                {
                    $selectUserMain .= "<option value='".$res->rowid."' SELECTED >".$nom."</option>";
                } else {
                    $selectUserMain .= "<option value='".$res->rowid."'>".$nom."</option>";
                }
                $selectUserSec  .= "<option value='".$res->rowid."'  >".$nom."</option>";
            }
            $selectUserMain .= "</SELECT>";
        }
        print "<td colspan=1 rowspan=5 width=45%  class='ui-widget-content'>";
        // editeur wysiwyg
        if (isset($conf->fckeditor->enabled) && $conf->fckeditor->enabled )
        {
            require_once(DOL_DOCUMENT_ROOT."/core/lib/doleditor.class.php");
            $doleditor=new DolEditor('desc',$objp->description,164,'dolibarr_details');
            $doleditor->Create();
        } else {
            print '<textarea name="desc" cols="70" class="flat" rows="9">'.dol_htmlentitiesbr_decode(preg_replace('/^\[[\w]*\]/','',$objp->description)).'</textarea>';
        }
        print "</td></tr>";
        print '<tr><td class="ui-state-default ui-widget-header">'.$langs->trans('Responsable').'</td>
                   <td class="ui-widget-content" colspan="1">'.$selectUserMain.'</td></tr>';
        print '<tr><td class="ui-state-default ui-widget-header">'.$langs->trans('Commerciaux').'</td>
                   <td class="ui-widget-content" colspan="1">'.$selectUserSec.'</td></tr>';

        print '<tr><td class="ui-state-default ui-widget-header">'.$langs->trans('Date d&eacute;but').'</td>
                   <td  class="ui-widget-content" colspan="1">';
        $html->select_date('','dateDebut','1','1','0');
        print '</td></tr>';
        print '<tr><td class="ui-state-default ui-widget-header">'.$langs->trans('Date fin').'</td>
                   <td  class="ui-widget-content" colspan="1">';
        $html->select_date('','dateFin','1','1','0');
        print '</td></tr>';

        print '</table>';
        print '</table>';
        print "<input class='butAction' type='submit'></input>";
        //affiche un formulaire avec:
        // -> nom de la campagne
        // -> nom du responsable
        // -> commerciaux impliques dans la campagne
        // -> date prevue de la campagne
        // -> date finale prevue de la campagne
    break;
    case 'newAdd':
        //getInfo
        $campagne = new Campagne($db);

        $campagne->dateDebut = $_REQUEST['dateDebut'];
        $campagne->dateFin = $_REQUEST['dateFin'];
        $campagne->comm = $_REQUEST['comm']; //array
        $campagne->Responsable = $_REQUEST['Responsable'];//array
        $campagne->nom = $_REQUEST['nom'];
        $campagne->notePublic = $_REQUEST['desc'];

        $campagne->dateDebutday = $_REQUEST['dateDebutday'];
        $campagne->dateDebutmonth = $_REQUEST['dateDebutmonth'];
        $campagne->dateDebutyear = $_REQUEST['dateDebutyear'];
        $campagne->dateDebuthour = $_REQUEST['dateDebuthour'];
        $campagne->dateDebutmin = $_REQUEST['dateDebutmin'];

        $campagne->dateFinday = $_REQUEST['dateFinday'];
        $campagne->dateFinmonth = $_REQUEST['dateFinmonth'];
        $campagne->dateFinyear = $_REQUEST['dateFinyear'];
        $campagne->dateFinhour = $_REQUEST['dateFinhour'];
        $campagne->dateFinmin = $_REQUEST['dateFinmin'];
        if ($campagne->nom."x" == "x")
        {
            print " ".$langs->trans('NameEmpty');
        } else if  ($campagne->Responsable."x" == "x" || $campagne->Responsable."x" == "0x") {
            print " ".$langs->trans('NameEmpty');
        } else {
        $campagne->create($user);
        //redirect to config
        header("Location: ?action=config&id=".$campagne->id);

}

    break;


    default:

    case 'config':

        $campagne = new Campagne($db);
        $campagne->fetch($id);

        if ($campagne->statut<2)
        {

            //affiche 2 jggrid avec les societes
            //permet de passer des societes d'une liste a l'autre via des boutons
            $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
            $jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
            $css = DOL_URL_ROOT."/Synopsis_Common/css";
            $imgPath = DOL_URL_ROOT."/Synopsis_Common/images";



            $js = '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
            $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';
            $js .= '<script language="javascript" src="'.$jspath.'/jquery.FCKEditor.js"></script>'."\n";

            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."c_secteur WHERE active = 1";
            $sql = $db->query($requete);
            $jsEditopts["secteurActivite"]="";
             $jsEditopts["secteurActivite"].=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";
            while ($res = $db->fetch_object($sql))
            {
                $jsEditopts["secteurActivite"].= $res->id . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->libelle)))  . ";";
            }
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."c_departements WHERE active = 1  AND rowid <= 101 ";
            $sql = $db->query($requete);
            $jsEditopts["departements"]="";
            while ($res = $db->fetch_object($sql))
            {
                if ($res->rowid == 1)
                {
                    $jsEditopts["departements"].= -1 . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";
                } else {
                    $jsEditopts["departements"].= $res->rowid . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->code_departement. " " .$res->nom )))  . ";";
                }
            }
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."c_effectif WHERE active = 1";
            $sql = $db->query($requete);
            $jsEditopts["effectif"]="";
             $jsEditopts["effectif"].=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";
            while ($res = $db->fetch_object($sql))
            {
                $jsEditopts["effectif"].= $res->id . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->libelle)))  . ";";
            }

            $jsEditopts["secteurActivite"] = preg_replace('/;$/','',$jsEditopts["secteurActivite"]);
            $jsEditopts["departements"] = preg_replace('/;$/','',$jsEditopts["departements"]);
            $jsEditopts["effectif"] = preg_replace('/;$/','',$jsEditopts["effectif"]);


            $js .= "<script type='text/javascript'> var DOL_URL_ROOT='".DOL_URL_ROOT."';";
            $js .= "  var DOL_DOCUMENT_ROOT='".DOL_DOCUMENT_ROOT."';";
            $js .= "  var DOL_URL_ROOT='".DOL_URL_ROOT."';";
            $js .= "  var campId='".$id."';";
            $js .= "  var userId='".$user->id."';";
            $js .= "var EditOpt=new Array();\n";
            $js .= "    EditOpt['sectAct']='".$jsEditopts["secteurActivite"]."'\n";
            $js .= "    EditOpt['departements']='".$jsEditopts["departements"]."'\n";
            $js .= "    EditOpt['effectif']='".$jsEditopts["effectif"]."'";

            $js .= "   </script>\n";


            $js .= "<style type='text/css'>";
            $js .= "#editCampBut:hover { cursor: pointer; }";
            $js .= ".ui-datepicker { width: 17em; padding: .2em .2em 0;z-index: 100999; }";
            $js .= ".error { color: #FF0000; text-decoration: underline; }";
            $js .= "#fck1___Frame{ height: 100px; }";
            $js .= "</style>";
            llxHeader($js,"Campagne de prospection - Config",1);
        //saveHistoUser($campagne->id, "campagne",$campagne->nom);
            print "<div id='editCampDialog' style='display: none'>";

        ///  editCamp contact ************************

            print '<form id="formeditCampDialog" action="#"><fieldset>';
            print '<legend>';
            print 'Modifier la campagne '.$campagne->nom;
            print '</legend>';
//            print '<table style="width: 330px;">';
//
//
//            print '</table>';
print cartouche_campagne_edit($db,$id,'Configuration de la campagne',1,$campagne);
            //Type  => select fixe
            //Titre => champs libre
            //Action concernant la societe => recupere le socId / le name
            //Action concernant la contact => recupere la liste des contacts de la societe
            //Action affectee a  => select fixe
            //Date debut => ui.timepicker
            //Date fin => ui.timepicker
            //Note => champs libre

            print '</fieldset></form>';


            print "</div>";
            //cartouche

            cartouche_campagne($db,$id,'Configuration de la campagne',1);
            print "<input type='hidden' name='campId' id='campId'  value='".$id."'/>";
            print '<table width="100%" border="0" class="notopnoleftnoright"><tr><td class="notopnoleftnoright" valign="middle"><div class="titre">Espace Prospection</div></td></tr></table>';
            print $langs->trans("HelpProspection").'<br><br>';
            print '<div style="clear: both;">';
            print '<div class="tabs">';
            if ($user->rights->prospectbabe->Prospection->Ecrire)
            {
                print '<a id="active" class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=config&id='.$id.'">Configuration</a>';
            }
            if ($user->rights->prospectbabe->Prospection->Affiche)
            {
                print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=list">'.$langs->Trans('Toutes les campagnes').'</a>';
            }
            if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS && $user->rights->process->lire &&  DoesElementhasProcess($db,'Campagne'))
            {
                print '<a class="tab" href="'.DOL_URL_ROOT.'/Synopsis_Process/listProcessForElement.php?type=Campagne&id='.$id.'">Process</a>';
            }


//            print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=new">'.$langs->Trans('Cr&eacute;er une campagne').'</a>';
            print '</div>';
            print '<div class="tabBar"><div>';
            print "<table width=100% >";
            print "<tbody>";
            print "<tr><td style='width:641px;'>";

            print '<table id="gridListSocL" class="scroll" cellpadding="0" cellspacing="0"></table>';
            print '<div id="gridListSocLPager" class="scroll" style="text-align:center;"></div>';

            print "</td>";
            print "<td><center>";

            print "<table width=90%>";
            print "<tbody style='text-align: center;'>";
            print "<tr><td><input type='button' id='sel' style='padding: 5pt; width: 100%; font-size: 90%;' value='S&eacute;lection &gt;'></td></tr>";
            print "<tr><td><input type='button' id='desel' style='padding: 5pt; width: 100%; font-size: 90%;' value='&lt; D&eacute;s&eacute;lection'></td></tr>";
            print "</tbody>";
            print "</table></center>";

            print "</td>";
            print "<td  style='width:641px;'>";

            print '<table id="gridListSocNL" class="scroll" cellpadding="0" cellspacing="0"></table>';
            print '<div id="gridListSocNLPager" class="scroll" style="text-align:center;"></div>';

            print "</tr></td>";

            print "</tbody>";
            print "</table>";
            print '</div>';

            //lignes boutons
            print '<div class="tabsAction"  style="vertical-align: bottom; margin-top: 35px; margin-left: 80%;"><right>';
            print '<a class="butAction" href="nouvelleProspection.php?action=validate&amp;id='.$id.'">'.$langs->trans('Valider').'</a>';
            print '<a class="butAction" href="nouvelleProspection.php?action=delete&amp;id='.$id.'">'.$langs->trans('Supprimer').'</a>';
            print "</right></div>";
            break; //break seleument si pas validé = $campagne->statut >= 2



            /******************************************************/
        }


        case 'listCamp': //campagne valider aussi en mode config => quand une campagne est valider, on affiche le mode apercu



            //saveHistoUser($campagne->id, "campagne",$campagne->nom);
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."c_secteur WHERE active = 1";
            $sql = $db->query($requete);
            $jsEditopts["secteurActivite"]="";
             $jsEditopts["secteurActivite"].=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";
            while ($res = $db->fetch_object($sql))
            {
                $jsEditopts["secteurActivite"].= $res->id . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($langs->Trans($res->libelle)))) . ";";
            }
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."c_departements WHERE active = 1 AND rowid <= 101";
            $sql = $db->query($requete);
            $jsEditopts["departements"]="";
            while ($res = $db->fetch_object($sql))
            {
                if ($res->rowid == 1)
                {
                    $jsEditopts["departements"].= -1 . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";
                } else {
                    $jsEditopts["departements"].= $res->rowid . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->code_departement. " " .$res->nom )))  . ";";
                }
            }
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."c_effectif WHERE active = 1";
            $sql = $db->query($requete);
            $jsEditopts["effectif"]="";
             $jsEditopts["effectif"].=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";
            while ($res = $db->fetch_object($sql))
            {
                $jsEditopts["effectif"].= $res->id . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->libelle)))  . ";";
            }
            $jsEditopts["secteurActivite"] = preg_replace('/;$/','',$jsEditopts["secteurActivite"]);
            $jsEditopts["departements"] = preg_replace('/;$/','',$jsEditopts["departements"]);
            $jsEditopts["effectif"] = preg_replace('/;$/','',$jsEditopts["effectif"]);

           //affiche 2 jggrid avec les societes
            //permet de passer des societes d'une liste a l'autre via des boutons
            $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
            $jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
            $css = DOL_URL_ROOT."/Synopsis_Common/css";
            $imgPath = DOL_URL_ROOT."/Synopsis_Common/images";



//            $js .= ' <script src="'.$jspath.'/jqGrid-3.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
            $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
            $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';
            $js .= ' <script src="js/jqueryProspect.js" type="text/javascript"></script>';
            $js .= '<script language="javascript" src="'.$jspath.'/jquery.FCKEditor.js"></script>'."\n";
            $js .= ' <script src="'.$jqueryuipath.'/ui.tabs.js" type="text/javascript"></script>';
            $js .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
            $js .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu({style: 'dropdown', maxHeight: 300 }); });  </script>\n";


            $js .= "<script type='text/javascript'>\n";
            $js .= "var EditOpt=new Array();\n";
            $js .= "    EditOpt['sectAct']='".$jsEditopts["secteurActivite"]."'\n";
            $js .= "    EditOpt['departements']='".$jsEditopts["departements"]."'\n";
            $js .= "    EditOpt['effectif']='".$jsEditopts["effectif"]."'";

            $js .= "</script>";

            $js .= '<STYLE type="text/css">
            .ui-datepicker { width: 17em; padding: .2em .2em 0;z-index: 100999; }
            .error { color: #FF0000; text-decoration: underline; }
            .fck1___Frame{ height: 100px; }
            </STYLE>';

            llxHeader($js,"Campagne de prospection - config",1);

            //cartouche
            cartouche_campagne($db,$id,"Aper&ccedil;u de la campagne");
            print "<input type='hidden' name='campId' id='campId'  value='".$id."'/>";
            print '<table width="100%" border="0" class="notopnoleftnoright"><tr><td class="notopnoleftnoright" valign="middle"><div class="titre">Espace Prospection</div></td></tr></table>';
            print $langs->trans("HelpProspection").'<br><br>';
            print '<div style="clear: both;">';
            print '<div class="tabs">';
            if ($user->rights->prospectbabe->Prospection->Ecrire)
            {
                print '<a id="active" class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=config&id='.$id.'">Configuration</a>';
            }
            if ($user->rights->prospectbabe->Prospection->Affiche)
            {
                print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=list">'.$langs->Trans('Toutes les campagnes').'</a>';
            }
            if ($user->rights->prospectbabe->Prospection->Affiche)
            {
                print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/affichePropection.php?action=list&campagneId='.$id.'">Prospection</a>';
            }
            if ($user->rights->prospectbabe->Prospection->recap)
            {
                print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/recapCamp.php?campagneId='.$id.'">R&eacute;capitulatif</a>';
            }
            if ($user->rights->prospectbabe->Prospection->stats)
            {
                print '<a  class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/statsCamp.php?campagneId='.$id.'">Statistiques</a>';
            }
            if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS && $user->rights->process->lire &&  DoesElementhasProcess($db,'Campagne'))
            {
                print '<a class="tab" href="'.DOL_URL_ROOT.'/Synopsis_Process/listProcessForElement.php?type=Campagne&id='.$id.'">Process</a>';
            }

            print '</div>';
            print '<div class="tabBar"><div>';
            print '<table id="gridListSocNLRo" class="scroll" cellpadding="0" cellspacing="0"></table>';
            print '<div id="gridListSocNLRoPager" class="scroll" style="text-align:center;"></div>';
            print '</div>';


            print '<div class="tabsAction">';
            $camp=new Campagne($db);
            $camp->fetch($id);
            if ($camp->statut < 3)
            {
                print '<a class="butAction" href="nouvelleProspection.php?action=lancer&amp;id='.$id.'">'.$langs->trans('Lancer').'</a>';
            }
            if ($camp->statut < 5)
            {
                print '<a class="butAction" href="nouvelleProspection.php?action=cloturer&amp;id='.$id.'">'.$langs->trans('Cloturer').'</a>';
            }

            print "</div>";


    break;
    case 'delete':
        $db->begin();
        $requete1 = "DELETE FROM Babel_campagne WHERE id = ".$id;
        $requete2 = "DELETE FROM Babel_campagne_people WHERE campagne_refid = ".$id;
        $requete3 = "DELETE FROM Babel_campagne_societe WHERE campagne_refid = ".$id;
        if ($res=$db->query($requete1) && $res=$db->query($requete2) && $res=$db->query($requete3)){
            $db->commit();
            header("Location: ".DOL_URL_ROOT."/BabelProspect/nouvelleProspection.php?action=list");
        }
        else {
            $db->rollback();
            header("Location:  ".DOL_URL_ROOT."/BabelProspect/nouvelleProspection.php?action=config&id=".$id);
        }
        //confirmation
        //si confirmation => efface dans la base
        // appel trigger
    break;
    case 'validate':
        $campagne = new Campagne($db);
        $campagne->validate($id);
        header("Location: ".$_SERVER['PHP_SELF']."?action=config&id=".$id);
        //confirmation
        //si confirmation => place la prospection comme valide
        // appel trigger
    break;
    case 'lancer':
        $campagne = new Campagne($db);
        $campagne->lancer($id);
        header("Location: ".$_SERVER['PHP_SELF']."?action=config&id=".$id);
        //confirmation
        //si confirmation => place la prospection comme en cours
        // appel trigger
    break;
    case 'cloturer':
        $campagne = new Campagne($db);
        $campagne->cloturer($id);
        header("Location: ".$_SERVER['PHP_SELF']."?action=config&id=".$id);
        //confirmation
        //si confirmation => place la prospection comme cloturer
        // appel trigger
    break;
//    case 'stats':
//        //Affiche la cartouche de présentation de la campagne
//        llxHeader();
//        cartouche_campagne($db,$id,'Statistiques de la campagne');
//
//            print '<div class="tabs">';
//
//            print '<a class="tabTitle">'.$langs->trans('Campagne Prospection').'</a>';
//
//        //Affiche stat campagne (par effectif, par region, ...)
//        //Affiche stat campagnes (qte de societe , resultat ...)
//        //Affiche les stats d'appel par jour
//        //Affiche les stats d'appel tot
//        //Affiche la progression => barre a 3 niveau :> cloturer success, cloturer perdu, en cours, pas pris, restant
//
//        // appel trigger
//    break;
    case 'list':
    default:
    //list all


           //affiche 2 jggrid avec les societes
            //permet de passer des societes d'une liste a l'autre via des boutons
            $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
            $jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
            $css = DOL_URL_ROOT."/Synopsis_Common/css";
            $imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."c_secteur WHERE active = 1";
            $sql = $db->query($requete);
            $jsEditopts["secteurActivite"]="";
             $jsEditopts["secteurActivite"].=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";
            while ($res = $db->fetch_object($sql))
            {
                $jsEditopts["secteurActivite"].= $res->id . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($langs->Trans($res->libelle)))) . ";";
            }
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."c_departements WHERE active = 1 AND rowid <= 101";
            $sql = $db->query($requete);
            $jsEditopts["departements"]="";
            while ($res = $db->fetch_object($sql))
            {
                if ($res->rowid == 1)
                {
                    $jsEditopts["departements"].= -1 . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";
                } else {
                    $jsEditopts["departements"].= $res->rowid . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->code_departement. " " .$res->nom )))  . ";";
                }
            }
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."c_effectif WHERE active = 1";
            $sql = $db->query($requete);
            $jsEditopts["effectif"]="";
             $jsEditopts["effectif"].=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("S&eacute;lection ->"))))  . ";";
            while ($res = $db->fetch_object($sql))
            {
                $jsEditopts["effectif"].= $res->id . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->libelle)))  . ";";
            }
            $jsEditopts["secteurActivite"] = preg_replace('/;$/','',$jsEditopts["secteurActivite"]);
            $jsEditopts["departements"] = preg_replace('/;$/','',$jsEditopts["departements"]);
            $jsEditopts["effectif"] = preg_replace('/;$/','',$jsEditopts["effectif"]);



//            $js .= ' <script src="'.$jspath.'/jqGrid-3.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
            $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';
            $js .= ' <script src="js/jqueryProspect.js" type="text/javascript"></script>';
            $js .= '<script language="javascript" src="'.$jspath.'/jquery.FCKEditor.js"></script>'."\n";
            $js .= "<script type='text/javascript'>\n";
            $js .= "var EditOpt=new Array();\n";
            $js .= "    EditOpt['sectAct']='".$jsEditopts["secteurActivite"]."'\n";
            $js .= "    EditOpt['departements']='".$jsEditopts["departements"]."'\n";
            $js .= "    EditOpt['effectif']='".$jsEditopts["effectif"]."'";

            $js .= "</script>";


            llxHeader($js,"Campagne de prospection",1);

            print '<table width="100%" border="0" class="notopnoleftnoright"><tr><td class="notopnoleftnoright" valign="middle"><div class="titre">Espace Prospection</div></td></tr></table>';
            print $langs->trans("HelpProspection").'<br><br>';
            print '<div style="clear: both;">';
            print '<div class="tabs">';
            if ($user->rights->prospectbabe->Prospection->Affiche)
            {
                print '<a id="active" class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=list">'.$langs->trans('Campagne de prospection').'</a>';
            }
//            print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=list">'.$langs->Trans('Index').'</a>';
            if ($user->rights->prospectbabe->Prospection->Ecrire)
            {
                print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=new">'.$langs->Trans('Cr&eacute;er une campagne').'</a>';
            }
            if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS && $user->rights->process->lire &&  DoesElementhasProcess($db,'Campagne'))
            {
                print '<a class="tab" href="'.DOL_URL_ROOT.'/Synopsis_Process/listProcessForElement.php?type=Campagne&id='.$id.'">Process</a>';
            }

            print '</div>';
            print '<div class="tabBar">';
            print '<table with= 60% id="gridList" class="scroll" cellpadding="0" cellspacing="0"></table>';
            print '<div id="gridListPager" class="scroll" style="text-align:center;"></div>';
            print '<br />';
//            print ' <a href="javascript:void(0)" id="m1">Get Selected id\'s</a>';
//            print ' <a href="javascript:void(0)" id="m1s">Select(Unselect) row 13</a>';

        print "</script>";
    break;

}





//secteur
//            -> taille
//            -> Region geographique
//            -> les n derniers qui ont commande
//            -> les n derniers client qui n'ont pas commandes
//            -> les n derniers client qui ont commandes
//            -> les n derniers prospects
//            -> les clients qui ont commande tel produits / services + idem avec factures + negate
//            -> les clients/prospect a qui ont a proposer tel produits / services + negate
//            -> les clients qui ont ete contacte a tel date + negate (table actioncom)
//            -> clients prospects sans activite depuis temps de temps / tel date
//            -> choix libre avec affiche alphabetique , par 100
//        -> faire une campagne avec suivie + action com
//        -> matrice pour enchainer et suivre les prospect
//        -> BI => resultat de la campagne
function cartouche_campagne($db,$id,$title='Nouvelle campagne de prospection',$mod=false)
{
    global $langs;
     ///**************************** Cartouche campagne **********************//
    $campagne = new Campagne($db);
    $campagne->fetch($id);
    print "<br>";
    print "<form action='?action=newAdd' method='POST'>";
    print '<table width="100%" class="notopnoleftnoright">';
    print "<tr  class='liste_titre' align='center' style='font-size: 120%; font-weight: 00;'><td>".$langs->trans($title)."&nbsp;&nbsp;";
//    if ($mod)
//    {
//        print "<span id='editCampBut'>".img_edit("Editer la campagne",0,"align='absmiddle'")."</span></td>";
//    }
    print '<tr><td valign="top" class="notopnoleft">';

    print '<table cellpadding=10 class="border" width="100%">';

    print '<tr><th align="left" class="ui-widget-header ui-state-default" width="15%">'.$langs->trans("D&eacute;signation de la campagne").'</th>';
    print "<td width='40%' colspan='1' class='ui-widget-content'>";
    print $campagne->nom;
    print '</td>';
    $requete = "SELECT Babel_campagne_people.isResponsable,
                       ".MAIN_DB_PREFIX."user.rowid as uid,
                       ".MAIN_DB_PREFIX."user.lastname as uname,
                       ".MAIN_DB_PREFIX."user.firstname as ufirstname
                  FROM ".MAIN_DB_PREFIX."user,
                       Babel_campagne_people
                 WHERE ".MAIN_DB_PREFIX."user.rowid = Babel_campagne_people.user_refid
                   AND campagne_refid = ". $campagne->id."
              ORDER BY ".MAIN_DB_PREFIX."user.lastname, ".MAIN_DB_PREFIX."user.firstname";
    $strResponsable= "";
    $strCommerciaux= "";
//        print $requete
    if ($resql = $db->query($requete))
    {
        while ($res=$db->fetch_object($resql))
        {
            $nom = ($res->ufirstname?$res->ufirstname." ".$res->uname:$res->uname);
            if ($res->isResponsable == 1)
            {
                $strResponsable = '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$res->uid.'">'.img_object('',"user")." ".$nom."</a>";
            } else {
                $strCommerciaux .= '<tr><td class="ui-widget-content"><a href="'.DOL_URL_ROOT.'/user/card.php?id='.$res->uid.'">'.img_object('',"user")." ".$nom."</a></td></tr>";
            }
        }
    }
    print "<td colspan=1 rowspan=5 width=45% style='background-color: rgb(253,245,163);'>";
    print '<div name="desc" width="70em" class="flat"  rows="9">'.$campagne->notePublic.'</div>';
    print "</td></tr>";
    print '<tr><th align="left" class="ui-widget-header ui-state-default">'.$langs->trans('Responsable').'</th><td class="ui-widget-content" colspan="1">'.$strResponsable.'</td></tr>';
    print '<tr><th align="left" class="ui-widget-header ui-state-default">'.$langs->trans('Commerciaux').'</th><td class="ui-widget-content" colspan="1"><table class="nobordernopadding" style="width:100%">'.$strCommerciaux.'</table></td></tr>';
    print '<tr><th align="left" class="ui-widget-header ui-state-default">'.$langs->trans('Date d&eacute;but').'</th><td class="ui-widget-content" colspan="1">';
     if ('x'.$campagne->dateDebutEffective !='x')
     {
        print $campagne->dateDebutEffectiveday.'/'.$campagne->dateDebutEffectivemonth.'/'.$campagne->dateDebutEffectiveyear;
     } else {
        print $campagne->dateDebutday.'/'.$campagne->dateDebutmonth.'/'.$campagne->dateDebutyear;
     }
    print '</td></tr>';
    print '<tr><th align="left" class="ui-widget-header ui-state-default">'.$langs->trans('Date fin').'</th><td class="ui-widget-content" colspan="1">';
     if ('x'.$campagne->dateFinEffective !='x')
     {
        print $campagne->dateFinEffectiveday.'/'.$campagne->dateFinEffectivemonth.'/'.$campagne->dateFinEffectiveyear;
     } else {
        print $campagne->dateFinday.'/'.$campagne->dateFinmonth.'/'.$campagne->dateFinyear;
     }
    print '</td></tr>';

    print '</table><br><br>';

    ///**************************** Cartouche campagne **********************//

}
function cartouche_campagne_edit($db,$id,$title='Nouvelle campagne de prospection',$mod=false,$camp)
{
    global $langs;
        print '<table cellpadding=10 class="border" width="100%">';

        print '<tr><td width="15%">'.$langs->trans("D&eacute;signation de la campagne").'</td>';
        print "<td width='40%' colspan='1'>";
        print "<input type='text' name='ModCampnom' id='ModCampnom' value='".$camp->nom."'></input>";
        print '</td>';
        //print "toto".$camp->Responsable;
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."user";
        $selectUserMain= "";
        $selectUserSec= "";
        $testArr = array();

        foreach($camp->comm as $key=>$val)
        {
            $testArr[$val]=$val;
        }
        if ($resql = $db->query($requete))
        {
            $selectUserMain = "<SELECT name='Responsable[]' id='Responsable' >";
            $selectUserSec = " <SELECT name='comm[]' id='comm' multiple='multiple' size='4' >";
            while ($res=$db->fetch_object($resql))
            {
                $nom = ($res->firstname?$res->firstname." ".$res->name:$res->name);
                if ($res->rowid == $camp->Responsable[0])
                {
                    $selectUserMain .= "<option value='".$res->rowid."' SELECTED >".$nom."</option>";
                } else {
                    $selectUserMain .= "<option value='".$res->rowid."'>".$nom."</option>";
                }
                if (in_array($res->rowid,$testArr))
                {
                    $selectUserSec  .= "<option SELECTED value='".$res->rowid."'  >".$nom."</option>";
                } else {
                    $selectUserSec  .= "<option value='".$res->rowid."'  >".$nom."</option>";
                }

            }
            $selectUserMain .= "</SELECT>";
        }
        print "</tr>";
        print '<tr><td>'.$langs->trans('Responsable').'</td><td colspan="1">'.$selectUserMain.'</td></tr>';
        print '<tr><td>'.$langs->trans('Commerciaux').'</td><td colspan="1">'.$selectUserSec.'</td></tr>';
        $datedeb = date('d/m/Y',strtotime( $camp->dateDebut));
        print '<tr><td>'.$langs->trans('Date d&eacute;but').'</td><td colspan="1"><input id="modCampdateDebut" type="text" value="'.$datedeb.'">';
        print '</td></tr>';
        $datefin = date('d/m/Y',strtotime( $camp->dateFin));
        print '<tr><td>'.$langs->trans('Date fin').'</td><td colspan="1"><input id="modCampdateFin"  value="'.$datefin.'"></td></tr>';
        print '<tr><td>'.$langs->trans('Note');
        print '</td>';
        print "<td colspan=1 rowspan=1 width=45%>";
        print "<div style='height: 100px; overflow: scroll;'>";
        print '<textarea name="modNote" id="fckGLE" cols="10" class="fck" rows="9">'.dol_htmlentitiesbr_decode(preg_replace('/^\[[\w]*\]/','',$camp->note)).'</textarea>';
        print "</div>";
//                    }
        print "</td></tr>";

        print '</table>';

    ///**************************** Cartouche campagne **********************//

}
?>