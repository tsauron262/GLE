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

//affiche les stats de la campagne
//affiche la fenetre de recherche des proscpect
//fenetre avec le client / prospect nom de la societe, ".MAIN_DB_PREFIX."c_forme_juridique la taille ,l'effectif, le secteur d'activite , le secteur geographique par alphabe


//Affiche en 2 fenetre, les seléctionne, et les pas selectionnees
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

switch ($action)
{

    case 'add':
    case 'new':
        llxHeader();
        print "<br>";
        print "<form action='?action=newAdd' method='POST'>";
        print '<table width="100%" class="notopnoleftnoright">';
        print "<tr><th>".$langs->trans('Nouvelle campagne de prospection');
        print '<tr><td valign="top" class="notopnoleft">';

        print '<table class="border" width="100%">';

        print '<tr><td width="15%">'.$langs->trans("D&eacute;signation de la campagne").'</td>';
        print "<td width='40%' colspan='1'>";
        print "<input type='text' name='nom' id='nom'></input>";
        print '</td>';
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."user";
        $selectUserMain= "";
        $selectUserSec= "";
        if ($resql = $db->query($requete))
        {
            $selectUserMain = "<SELECT name='Responsable[]' id='Responsable' >";
            $selectUserSec = "<SELECT name='comm[]' id='comm' multiple='multiple' size='4' >";
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
        print "<td colspan=1 rowspan=5 width=45%>";
                    // editeur wysiwyg
                    if (isset($conf->fckeditor->enabled) && $conf->fckeditor->enabled )
                    {
                        require_once(DOL_DOCUMENT_ROOT."/core/lib/doleditor.class.php");
                        $doleditor=new DolEditor('desc',$objp->description,164,'dolibarr_details');
                        $doleditor->Create();
                    }
                    else
                    {
                        print '<textarea name="desc" cols="70" class="flat" rows="9">'.dol_htmlentitiesbr_decode(preg_replace('/^\[[\w]*\]/','',$objp->description)).'</textarea>';
                    }
        print "</td></tr>";
        print '<tr><td>'.$langs->trans('Responsable').'</td><td colspan="1">'.$selectUserMain.'</td></tr>';
        print '<tr><td>'.$langs->trans('Commerciaux').'</td><td colspan="1">'.$selectUserSec.'</td></tr>';

        print '<tr><td>'.$langs->trans('Date d&eacute;but').'</td><td colspan="1">';
        $html->select_date('','dateDebut','1','1','0');
        print '</td></tr>';
        print '<tr><td>'.$langs->trans('Date fin').'</td><td colspan="1">';
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

        //Save db

//        require('Var_Dump.php');
//        Var_Dump::displayInit(array('display_mode' => 'HTML4_Text'), array('mode' => 'normal','offset' => 4));
//        Var_Dump::Display($_REQUEST);
//        Var_Dump::Display($_POST);
//        Var_Dump::Display($HTTP_POST_VARS);
        // -> appel trigger


    break;


    default:

    case 'config':

        $campagne = new Campagne($db);
        $campagne->fetch($id);
        if ($campagne->statut<2)
        {
            $js = ' <script src="js/rico/rico.js" type="text/javascript"></script>';
            $js .= ' <script src="js/Babel_prospect.js" type="text/javascript"></script>';
            llxHeader($js);
            cartouche_campagne($db,$id,'Configuration de la campagne');
            print "<input type='hidden' name='campId' value='".$id."'/>";
            print '<table width="100%" border="0" class="notopnoleftnoright"><tr><td class="notopnoleftnoright" valign="middle"><div class="titre">Espace Prospection</div></td></tr></table>';
            print $langs->trans("HelpProspection").'<br><br>';
            print '<div style="clear: both;">';
            print '<div class="tabs">';
            print '<a id="active" class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=config&id='.$id.'">Configuration</a>';
            print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=list">'.$langs->Trans('Index').'</a>';
            print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=new">'.$langs->Trans('Cr&eacute;er une campagne').'</a>';
            print '</div>';
            print '<div class="tabBar">';

            print '<input type="hidden" name="campagne_id" id="campagne_id" value="'.$id.'" ></input>';
            $campagne_id=$_REQUEST['id'];

//index pour filtrage dans filterCol_xmlresponse.php

            $_SESSION['data_grid'][0]="id";
            $_SESSION['data_grid'][1]="Client";
            $_SESSION['data_grid'][2]="Nom";
            $_SESSION['data_grid'][3]="Ville";
            $_SESSION['data_grid'][4]="Departement";
            $_SESSION['data_grid'][5]=null;
            $_SESSION['data_grid'][6]="Effectif";
            $_SESSION['data_grid'][7]=null;
            $_SESSION['data_grid'][8]="Secteur";

            $_SESSION['data_grid1'][0]="id";
            $_SESSION['data_grid1'][1]="Client";
            $_SESSION['data_grid1'][2]="Nom";
            $_SESSION['data_grid1'][3]="Ville";
            $_SESSION['data_grid1'][4]="Departement";
            $_SESSION['data_grid1'][5]=null;
            $_SESSION['data_grid1'][6]="Effectif";
            $_SESSION['data_grid1'][7]=null;
            $_SESSION['data_grid1'][8]="Secteur";

            //livegrid + filtre
            print "<table style='width: 100%; clear: both; border: 1px Solid #000000;'>";
            print '<tr><td style="width: 45%;min-width: 45%; max-width: 800px;">';
            print '      <span id="data_grid_bookmark"> </span>';
            print '    </td>';
            print '    <td align="right" style="width: 5%;min-width: 5%;max-width: 5%;">';
            print '        <a class="butAction" style="margin-right: 15pt; "  onClick="AddAllCamp();" href="#"><img  border=0 height="15pt" title="'.$langs->trans('Ajoute tout').'" src="'.DOL_URL_ROOT.'/theme/common/send_all.png"/></a>';
            print '    </td>';
            print '    <td  align="left" style="width: 5%;min-width: 5%;max-width: 5%;">';
            print '        <a class="butAction" href="#" onClick="resetCamp();" ><img border=0 height="15pt" title="'.$langs->trans('Enleve tout').'" src="'.DOL_URL_ROOT.'/theme/common/rcvd_all.png"/></a>';
            print '    </td>';
            print '    <td style="width: 45%;min-width: 45%; max-width: 800px;">';
            print '      <span id="data_grid1_bookmark"> </span>';
            print '    </td>';
            print "<tr><td colspan=2 style='width: 45%;min-width: 45%;max-width: 45%; vertical-align: top;'>";
            print "      <div style='max-width: 100%;width: 100%;'>";
            print "        <table  class='ricoLiveGrid'  id='data_grid' style='max-width: 100%;width: 100%;'  cellspacing='0' cellpadding='0'>";
            print "          <thead><tr><th>&nbsb;</th>
                               <th>".$langs->trans('Client ?')."</th>
                               <th>".$langs->trans('Nom')."</th>
                               <th>".$langs->trans('Ville')."</th>
                               <th>".$langs->trans('D&eacute;partement')."</th>
                               <th>&nbsp;</th>
                               <th>".$langs->trans('Effectif')."</th>
                               <th>&nbsp;</th>
                               <th>".$langs->trans('Secteur')."</th></tr></thead>";
            print '        </table>';
            print '      </div>';
            print '    </td>';
            print "    <td colspan=2 style='width: 45%;min-width: 45%;max-width: 45%; vertical-align: top;'>"; //main table
            print '      <div style="max-width: 100%; width: 100%; ">';
            print '        <table id="data_grid1" style="max-width: 100%;width: 100%;">';
            print "          <tr><th>&nbsp;</th>
                               <th>".$langs->trans('Client ?')."</th>
                               <th>".$langs->trans('Nom')."</th>
                               <th>".$langs->trans('Ville')."</th>
                               <th>".$langs->trans('D&eacute;partement')."</th>
                               <th></th>
                               <th>".$langs->trans('Effectif')."</th>
                               <th></th>
                               <th>".$langs->trans('Secteur')."</th></tr>";
            print '          </tr>';
            print '        </table>';
            print "      </div>";
            print "    </td>";
            print "</tr>";
            print '</table>';
            /**
             *
             *
             *
             * Export :
             * Export and print

            maxPrint
            The maximum number of rows that the user is allowed to Print/Export. Set to 0 to disable print/export. (default: 1000)
            exportWindow
            Options string passed to window.open() when the export window is created. (default: "height=400,width=500,scrollbars=1,menubar=1,resizable=1")
            exportStyleList
            An array of CSS attributes that will be extracted from the first visible row of the grid and used to format all rows of the exported table. (default: ['background-color', 'color', 'text-align', 'font-weight', 'font-size', 'font-family'])


             *
             * Image :
             *  sortAscendImg
            * Image to use to indicate that the column is sorted in ascending order.
            * (default: 'sort_asc.gif')
            * sortDescendImg
            * Image to use to indicate that the column is sorted in descending order.
            * (default: 'sort_desc.gif')
            * filterImg
            * Image used to indicate an active filter on a column. (default: 'filtercol.
            * gif')
            */

                            //livegrid + filtre
            $requete = "SELECT ".MAIN_DB_PREFIX."societe.rowid,
                               ".MAIN_DB_PREFIX."societe.client,
                               ".MAIN_DB_PREFIX."societe.nom,
                               ".MAIN_DB_PREFIX."societe.ville,
                               ".MAIN_DB_PREFIX."societe.fk_effectif,
                               ".MAIN_DB_PREFIX."c_effectif.libelle as effectifStr,
                               ".MAIN_DB_PREFIX."societe.fk_departement,
                               CONCAT(".MAIN_DB_PREFIX."c_departements.code_departement,' ',".MAIN_DB_PREFIX."c_departements.nom)  as departmentStr,
                               ".MAIN_DB_PREFIX."societe.fk_secteur,
                               ".MAIN_DB_PREFIX."c_secteur.libelle AS secteurStr
                              FROM ".MAIN_DB_PREFIX."societe
                         LEFT JOIN ".MAIN_DB_PREFIX."c_country on ".MAIN_DB_PREFIX."c_country.rowid=".MAIN_DB_PREFIX."societe.fk_pays
                         LEFT JOIN ".MAIN_DB_PREFIX."c_typent on ".MAIN_DB_PREFIX."c_typent.id=".MAIN_DB_PREFIX."societe.fk_typent
                         LEFT JOIN ".MAIN_DB_PREFIX."c_forme_juridique on ".MAIN_DB_PREFIX."c_forme_juridique.rowid = ".MAIN_DB_PREFIX."societe.fk_forme_juridique
                         LEFT JOIN ".MAIN_DB_PREFIX."c_departements on ".MAIN_DB_PREFIX."c_departements.rowid = ".MAIN_DB_PREFIX."societe.fk_departement
                         LEFT JOIN ".MAIN_DB_PREFIX."c_effectif on ".MAIN_DB_PREFIX."c_effectif.id = ".MAIN_DB_PREFIX."societe.fk_effectif AND ".MAIN_DB_PREFIX."c_effectif.active = 1
                         LEFT JOIN ".MAIN_DB_PREFIX."c_prospectlevel on ".MAIN_DB_PREFIX."c_prospectlevel.sortorder = ".MAIN_DB_PREFIX."societe.fk_prospectlevel
                         LEFT JOIN ".MAIN_DB_PREFIX."c_stcomm on ".MAIN_DB_PREFIX."c_stcomm.id = ".MAIN_DB_PREFIX."societe.fk_stcomm
                         LEFT JOIN ".MAIN_DB_PREFIX."c_secteur on ".MAIN_DB_PREFIX."c_secteur.id = ".MAIN_DB_PREFIX."societe.fk_secteur  AND ".MAIN_DB_PREFIX."c_secteur.active = 1
                             WHERE client > 0
                               AND ".MAIN_DB_PREFIX."societe.rowid not in (SELECT societe_refid FROM Babel_campagne_societe WHERE campagne_refid = ".$campagne_id.")
                          ORDER BY ".MAIN_DB_PREFIX."societe.nom ,
                                   ".MAIN_DB_PREFIX."societe.client,
                                   ".MAIN_DB_PREFIX."societe.ville,
                                   ".MAIN_DB_PREFIX."societe.fk_departement,
                                   ".MAIN_DB_PREFIX."societe.fk_effectif,
                                   ".MAIN_DB_PREFIX."societe.fk_secteur ";
            $_SESSION['data_grid']['requete']=$requete;

            print "<script type='text/javascript'>";
    //                           print $requete;
            print 'var names = [';
            if ($resql = $db->query($requete))
            {
                while ($res=$db->fetch_object($resql))
                {
                    $client = $langs->trans('Client');
                    if ($res->client > 1)
                    {
                        $client = $langs->trans('Prospect');
                    }
                    print '    [ "'.$res->rowid.'", "'.$client.'", "'. htmlentities($res->nom).'","'. htmlentities($res->ville).'", "'. htmlentities($res->departmentStr).'", "'.$res->fk_effectif.'", "'.$res->effectifStr.'","'.$res->fk_secteur.'", "'.$res->secteurStr.'"],'."\n";
                }
            }
            print '];';
            $requete = "SELECT ".MAIN_DB_PREFIX."societe.rowid,
                               ".MAIN_DB_PREFIX."societe.client,
                               ".MAIN_DB_PREFIX."societe.nom,
                               ".MAIN_DB_PREFIX."societe.ville,
                               ".MAIN_DB_PREFIX."societe.fk_effectif,
                               ".MAIN_DB_PREFIX."c_effectif.libelle as effectifStr,
                               ".MAIN_DB_PREFIX."societe.fk_departement,
                               CONCAT(".MAIN_DB_PREFIX."c_departements.code_departement,' ',".MAIN_DB_PREFIX."c_departements.nom)  as departmentStr,
                               ".MAIN_DB_PREFIX."societe.fk_secteur,
                               ".MAIN_DB_PREFIX."c_secteur.libelle AS secteurStr
                              FROM ".MAIN_DB_PREFIX."societe
                         LEFT JOIN ".MAIN_DB_PREFIX."c_country on ".MAIN_DB_PREFIX."c_country.rowid=".MAIN_DB_PREFIX."societe.fk_pays
                         LEFT JOIN ".MAIN_DB_PREFIX."c_typent on ".MAIN_DB_PREFIX."c_typent.id=".MAIN_DB_PREFIX."societe.fk_typent
                         LEFT JOIN ".MAIN_DB_PREFIX."c_forme_juridique on ".MAIN_DB_PREFIX."c_forme_juridique.rowid = ".MAIN_DB_PREFIX."societe.fk_forme_juridique
                         LEFT JOIN ".MAIN_DB_PREFIX."c_departements on ".MAIN_DB_PREFIX."c_departements.rowid = ".MAIN_DB_PREFIX."societe.fk_departement
                         LEFT JOIN ".MAIN_DB_PREFIX."c_effectif on ".MAIN_DB_PREFIX."c_effectif.id = ".MAIN_DB_PREFIX."societe.fk_effectif AND ".MAIN_DB_PREFIX."c_effectif.active = 1
                         LEFT JOIN ".MAIN_DB_PREFIX."c_prospectlevel on ".MAIN_DB_PREFIX."c_prospectlevel.sortorder = ".MAIN_DB_PREFIX."societe.fk_prospectlevel
                         LEFT JOIN ".MAIN_DB_PREFIX."c_stcomm on ".MAIN_DB_PREFIX."c_stcomm.id = ".MAIN_DB_PREFIX."societe.fk_stcomm
                         LEFT JOIN ".MAIN_DB_PREFIX."c_secteur on ".MAIN_DB_PREFIX."c_secteur.id = ".MAIN_DB_PREFIX."societe.fk_secteur  AND ".MAIN_DB_PREFIX."c_secteur.active = 1
                             WHERE client > 0
                               AND ".MAIN_DB_PREFIX."societe.rowid in (SELECT societe_refid FROM Babel_campagne_societe WHERE campagne_refid = ".$campagne_id.")
                          ORDER BY ".MAIN_DB_PREFIX."societe.nom ,
                                   ".MAIN_DB_PREFIX."societe.client,
                                   ".MAIN_DB_PREFIX."societe.ville,
                                   ".MAIN_DB_PREFIX."societe.fk_departement,
                                   ".MAIN_DB_PREFIX."societe.fk_effectif,
                                   ".MAIN_DB_PREFIX."societe.fk_secteur ";
            $_SESSION['data_grid1']['requete']=$requete;

            print 'var names1 = [';
            if ($resql = $db->query($requete))
            {
                while ($res=$db->fetch_object($resql))
                {
                    $client = $langs->trans('Client');
                    if ($res->client > 1)
                    {
                        $client = $langs->trans('Prospect');
                    }
                    print '    [ "'.$res->rowid.'", "'.$client.'", "'. htmlentities($res->nom).'","'. htmlentities($res->ville).'", "'. htmlentities($res->departmentStr).'", "'.$res->fk_effectif.'", "'.$res->effectifStr.'","'.$res->fk_secteur.'", "'.$res->secteurStr.'"],'."\n";
                }
            }
            print '];';

            print "</script>";

            //lignes boutons
            print '<div class="tabsAction">';
            print '<a class="butAction" href="nouvelleProspection.php?action=validate&amp;id='.$id.'">'.$langs->trans('Valider').'</a>';
            print '<a class="butAction" href="nouvelleProspection.php?action=delete&amp;id='.$id.'">'.$langs->trans('Supprimer').'</a>';
            print "</div>";
            break; //break seleument si pas valide
            /******************************************************/
        }


        case 'listCamp': //campagne valider aussi en mode config

            $js = ' <script src="js/rico/rico.js" type="text/javascript"></script>';
            $js .= ' <script src="js/Babel_prospectro.js" type="text/javascript"></script>';
            llxHeader($js);
            cartouche_campagne($db,$id,"Aper&ccedil;u de la campagne");
            print '<div class="fiche"> ';

            print '<div class="tabs">';
            print '<a class="tabTitle">'.$langs->trans('Campagne prospection').'</a>
                   <a id="active" class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=listCamp&id='.$id.'">'.$langs->trans('Fiche').'</a>';
            print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=stats&id='.$id.'">'.$langs->trans('Statistiques').'</a>';
            print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/affichePropection.php?action=list&campagneId='.$id.'">Suivi</a>';
            print '</div>';
            print '<div class="tabBar">';
            print '<input type="hidden" name="campagne_id" id="campagne_id" value="'.$id.'" ></input>';
            $campagne_id=$_REQUEST['id'];
            //livegrid + filtre
            print "<table style='width: 100%; clear: both; border: 1px Solid #000000;'>";
            print '<tr><td style="width: 100%;min-width: 100%;max-width: 100%;">';
            print '      <span id="data_grid_bookmark"> </span>';
            print '    </td>';
            print "<tr><td  style='width: 100%;min-width: 100%;max-width: 100%; vertical-align: top;'>";
            print "      <div style='max-width: 100%;width: 100%;'>";
            print "        <table  class='ricoLiveGrid'  id='data_grid' style='max-width: 100%;width: 100%;'  cellspacing='0' cellpadding='0'>";
            print "          <thead><tr><th>&nbsb;</th>
                               <th>".$langs->trans('Client ?')."</th>
                               <th>".$langs->trans('Nom')."</th>
                               <th>".$langs->trans('Ville')."</th>
                               <th>".$langs->trans('D&eacute;partement')."</th>
                               <th>&nbsp;</th>
                               <th>".$langs->trans('Effectif')."</th>
                               <th>&nbsp;</th>
                               <th>".$langs->trans('Secteur')."</th></tr></thead>";
            print '        </table>';
            print '      </div>';
            print '    </td>';
            print "</tr>";
            print '</table>';

            $requete = "SELECT ".MAIN_DB_PREFIX."societe.rowid,
                               ".MAIN_DB_PREFIX."societe.client,
                               ".MAIN_DB_PREFIX."societe.nom,
                               ".MAIN_DB_PREFIX."societe.ville,
                               ".MAIN_DB_PREFIX."societe.fk_effectif,
                               ".MAIN_DB_PREFIX."c_effectif.libelle as effectifStr,
                               ".MAIN_DB_PREFIX."societe.fk_departement,
                               CONCAT(".MAIN_DB_PREFIX."c_departements.code_departement,' ',".MAIN_DB_PREFIX."c_departements.nom)  as departmentStr,
                               ".MAIN_DB_PREFIX."societe.fk_secteur,
                               ".MAIN_DB_PREFIX."c_secteur.libelle AS secteurStr
                              FROM ".MAIN_DB_PREFIX."societe
                         LEFT JOIN ".MAIN_DB_PREFIX."c_country on ".MAIN_DB_PREFIX."c_country.rowid=".MAIN_DB_PREFIX."societe.fk_pays
                         LEFT JOIN ".MAIN_DB_PREFIX."c_typent on ".MAIN_DB_PREFIX."c_typent.id=".MAIN_DB_PREFIX."societe.fk_typent
                         LEFT JOIN ".MAIN_DB_PREFIX."c_forme_juridique on ".MAIN_DB_PREFIX."c_forme_juridique.rowid = ".MAIN_DB_PREFIX."societe.fk_forme_juridique
                         LEFT JOIN ".MAIN_DB_PREFIX."c_departements on ".MAIN_DB_PREFIX."c_departements.rowid = ".MAIN_DB_PREFIX."societe.fk_departement
                         LEFT JOIN ".MAIN_DB_PREFIX."c_effectif on ".MAIN_DB_PREFIX."c_effectif.id = ".MAIN_DB_PREFIX."societe.fk_effectif AND ".MAIN_DB_PREFIX."c_effectif.active = 1
                         LEFT JOIN ".MAIN_DB_PREFIX."c_prospectlevel on ".MAIN_DB_PREFIX."c_prospectlevel.sortorder = ".MAIN_DB_PREFIX."societe.fk_prospectlevel
                         LEFT JOIN ".MAIN_DB_PREFIX."c_stcomm on ".MAIN_DB_PREFIX."c_stcomm.id = ".MAIN_DB_PREFIX."societe.fk_stcomm
                         LEFT JOIN ".MAIN_DB_PREFIX."c_secteur on ".MAIN_DB_PREFIX."c_secteur.id = ".MAIN_DB_PREFIX."societe.fk_secteur  AND ".MAIN_DB_PREFIX."c_secteur.active = 1
                             WHERE client > 0
                               AND ".MAIN_DB_PREFIX."societe.rowid in (SELECT societe_refid FROM Babel_campagne_societe WHERE campagne_refid = ".$campagne_id.")
                          ORDER BY ".MAIN_DB_PREFIX."societe.nom ,
                                   ".MAIN_DB_PREFIX."societe.client,
                                   ".MAIN_DB_PREFIX."societe.ville,
                                   ".MAIN_DB_PREFIX."societe.fk_departement,
                                   ".MAIN_DB_PREFIX."societe.fk_effectif,
                                   ".MAIN_DB_PREFIX."societe.fk_secteur ";
            print "<script type='text/javascript'>";
            print 'var names = [';
            if ($resql = $db->query($requete))
            {
                $_SESSION['data_grid'][1]="Client";
                while ($res=$db->fetch_object($resql))
                {
                    $client = "Prospect";
                    if ($res->client==2)
                    {
                        $client = "Client";
                    }
                    print '    [ "'.$res->rowid.'", "'.$client.'", "'. htmlentities($res->nom).'","'. htmlentities($res->ville).'", "'. htmlentities($res->departmentStr).'", "'.$res->fk_effectif.'", "'.$res->effectifStr.'","'.$res->fk_secteur.'", "'.$res->secteurStr.'"],'."\n";
                }
            }
            print '];';

            print "</script>";
            //lignes boutons
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
        //si confirmation => place la prospection comme en cours
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
        //si confirmation => place la prospection comme en cours
        // appel trigger
    break;
    case 'stats':
        //Affiche la cartouche de presentation de la campagne
        llxHeader();
        cartouche_campagne($db,$id,'Statistiques de la campagne');

            print '<div class="tabs">';

            print '<a class="tabTitle">'.$langs->trans('Campagne Prospection').'</a>
                   <a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=listCamp&id='.$id.'">'.$langs->trans('Fiche').'</a>';
            print '<a id="active" class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=stats&id='.$id.'">'.$langs->trans('Statistiques').'</a>';
            print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/affichePropection.php?action=list&campagneId='.$id.'">Suivi</a>';
            print '</div>';
            print '<div class="tabBar">';
            require_once DOL_DOCUMENT_ROOT.'/../external-libs/Artichow/Artichow.cfg.php';

$WIDTH=500;
$HEIGHT=200;

/*
 *
 *
 */
require_once('campagnestats.class.php');
$stats = new CampagneStats($db,$id);

//todo => change n dernier jour
$data = $stats->getNbByMonth();
//var_dump($data);

dol_mkdir($conf->propal->dir_temp);

if (!$user->rights->societe->client->voir || $user->societe_id)
{
    $filename = $conf->propal->dir_temp.'/propale-'.$user->id.'-'.$year.'.png';
    $fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=propale-'.$user->id.'-'.$year.'.png';
}
else
{
    $filename = $conf->propal->dir_temp.'/propale'.$year.'.png';
    $fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=propale'.$year.'.png';
}

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
    $px->SetPrecisionY(0);
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
    $px->SetShading(3);
    $px->SetHorizTickIncrement(1);
    $px->SetPrecisionY(0);
    $px->draw($filename);
}
//todo here
$res = $stats->getAmountByMonth($year);

$data = array();

for ($i = 1 ; $i < 13 ; $i++)
{
  $data[$i-1] = array(ucfirst(substr(utf8_decode(strftime("%b",dol_mktime(12,12,12,$i,1,$year)),0,3))), $res[$i]);
}

if (!$user->rights->societe->client->voir || $user->societe_id)
{
    $filename_amount = $conf->propal->dir_temp.'/propaleamount-'.$user->id.'-'.$year.'.png';
    $fileurl_amount = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=propaleamount-'.$user->id.'-'.$year.'.png';
}
else
{
    $filename_amount = $conf->propal->dir_temp.'/propaleamount'.$year.'.png';
    $fileurl_amount = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=propaleamount'.$year.'.png';
}

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
    $px->SetPrecisionY(0);
    $px->SetYLabel($langs->trans("AmountTotal"));
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
    $px->SetShading(3);
    $px->SetHorizTickIncrement(1);
    $px->SetPrecisionY(0);
    $px->draw($filename_amount, $data, $year);
}
$res = $stats->getAverageByMonth($year);

$data = array();

for ($i = 1 ; $i < 13 ; $i++)
{
  $data[$i-1] = array(ucfirst(substr(utf8_decode(strftime("%b",dol_mktime(12,12,12,$i,1,$year))),0,3)), $res[$i]);
}

if (!$user->rights->societe->client->voir || $user->societe_id)
{
    $filename_avg = $conf->propal->dir_temp.'/propaleaverage-'.$user->id.'-'.$year.'.png';
    $fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=propaleaverage-'.$user->id.'-'.$year.'.png';
}
else
{
    $filename_avg = $conf->propal->dir_temp.'/propaleaverage'.$year.'.png';
    $fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=propaleaverage'.$year.'.png';
}

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
    $px->SetPrecisionY(0);
    $px->SetYLabel($langs->trans("AmountAverage"));
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
    $px->SetShading(3);
    $px->SetHorizTickIncrement(1);
    $px->SetPrecisionY(0);
    $px->draw($filename_avg);
}

print '<table class="border" width="100%">';
print '<tr><td align="center">'.$langs->trans("NumberOfProposalsByMonth").'</td>';
print '<td align="center">';
if ($mesg) { print $mesg; }
else { print '<img src="'.$fileurl.'">'; }
print '</td></tr>';
print '<tr><td align="center">'.$langs->trans("AmountTotal").'</td>';
print '<td align="center">';
if ($mesg) { print $mesg; }
else { print '<img src="'.$fileurl_amount.'">'; }
print '</td></tr>';
print '<tr><td align="center">'.$langs->trans("AmountAverage").'</td>';
print '<td align="center">';
if ($mesg) { print $mesg; }
else { print '<img src="'.$fileurl_avg.'">'; }
print '</td></tr></table>';


            print '</div>';

        //Affiche les stats d'appel par jour
        //Affiche les stats d'appel tot
        //Affiche la progression => barre à 3 niveau :> cloturer success, cloturer perdu, en cours, pas pris, restant

        // appel trigger
    break;
    case 'list':
    default:
    //list all

            $js = ' <script src="js/rico/rico.js" type="text/javascript"></script>';
            $js .= ' <script src="js/Babel_prospectList.js" type="text/javascript"></script>';
            llxHeader($js);

            print '<table width="100%" border="0" class="notopnoleftnoright"><tr><td class="notopnoleftnoright" valign="middle"><div class="titre">Espace Prospection</div></td></tr></table>';
            print $langs->trans("HelpProspection").'<br><br>';
            print '<div style="clear: both;">';
            print '<div class="tabs">';
            print '<a id="active" class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=list">'.$langs->trans('Campagne de prospection').'</a>';
//            print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=list">'.$langs->Trans('Index').'</a>';
            print '<a class="tab" href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=new">'.$langs->Trans('Cr&eacute;er une campagne').'</a>';
            print '</div>';
            print '<div class="tabBar">';


            print "<table style='width: 100%; clear: both; border: 1px Solid #000000;'>";
            print '<tr><td style="width: 100%;min-width: 100%;max-width: 100%;">';
            print '      <span id="data_grid2_bookmark"> </span>';
            print '    </td>';
            print "<tr><td style='width: 100%;min-width: 100%;max-width: 100%; vertical-align: top;'>";
            print "      <div style='max-width: 100%;width: 100%;'>";
            print "        <table  class='ricoLiveGrid'  id='data_grid2' style='max-width: 100%;width: 100%;'  cellspacing='0' cellpadding='0'>";
            print "          <thead><tr>
                               <th>&nbsb;</th>
                               <th>".$langs->trans('Reference')."   </th>
                               <th>".$langs->trans('DateDebut')."</th>
                               <th>".$langs->trans('DateFin')."</th>
                               <th>".$langs->trans('Statut')."</th>
                               <th>".$langs->trans('Nbr Tiers')."</th>
                               <th>&nbsb;</th>
                               <th>&nbsb;</th>
                            </tr></thead>";
            print '        </table>';
            print '      </div>';
            print '    </td>';
            print '</table>';
            $_SESSION['data_grid2'][0]=null;
            $_SESSION['data_grid2'][1]=null;
            $_SESSION['data_grid2'][2]="dateDebut";
            $_SESSION['data_grid2'][3]="dateFin";
            $_SESSION['data_grid2'][4]=null;
            $_SESSION['data_grid2'][5]=null;
            $_SESSION['data_grid2'][6]="dateDebut";
            $_SESSION['data_grid2'][7]="dateFin";

        $campagne = new Campagne($db);
        $campagne->fetch();
            print "<script type='text/javascript'>";
print <<<EOF

MyCustomColumn = Class.create();

MyCustomColumn.prototype = {

  initialize: function(col) {
    this._img=[];
    this._colnum=col;
    this._div=[];
  },

  _create: function(gridCell,windowRow) {

    this._img[windowRow]=RicoUtil.createFormField(gridCell,'img',null,this.liveGrid.tableId+'_img_'+this.index+'_'+windowRow);
    this._div[windowRow]=RicoUtil.createFormField(gridCell,'div',null,this.liveGrid.tableId+'_div_'+this.index+'_'+windowRow);
    this._clear(gridCell,windowRow);
  },

  _clear: function(gridCell,windowRow) {
    var img=this._img[windowRow];
    var div=this._div[windowRow];
    img.style.display='none';
    img.src='';
    div.innerHTML='';
    div.style.display='none';

  },

  _display: function(v,gridCell,windowRow) {

    var trad = new Array();
    trad[1]="CampagneStatusDraftShort";
    trad[2]="CampagneStatusOpenedShort";
    trad[3]="CampagneStatusSignedShort";
    trad[4]="CampagneStatusNotSignedShort";
    trad[5]="CampagneStatusBilledShort";
    var picto = new Array();
    picto[1]=0;
    picto[2]=1;
    picto[3]=3;
    picto[4]=4;
    picto[5]=6;

    var img=this._img[windowRow];

    var div=this._div[windowRow];
    var url;
    if (picto[v] == 5)
    {

EOF;
        print 'url = "'.DOL_URL_ROOT.'/theme/auguria/img/stcomm-1.png";';
        print '    } else {';
        print 'url = "'.DOL_URL_ROOT.'/theme/auguria/img/statut"+picto[v]+".png";';
print <<<EOF
    }

    this._img[windowRow].src=url;
    this._img[windowRow].align="absmiddle";
    this._img[windowRow].title = trad[v];
    //this._div[windowRow].style.display="inline";

    div.innerHTML = " "+trad[v] ;

//    this.liveGrid.buffer.setWindowValue(windowRow,this._colnum,trad[v] + this._img[windowRow].innerHTML);
    img.style.display='inline';
    div.style.display='inline';
  }
}

EOF;

        print "var nameList = [";
        foreach($campagne->campagneArray as $key=>$val)
        {
            $campagne->id = $val['id'];
            $campagne->stats();
            $stat =  $campagne->statCampagne['qty'];
            $dateDebutStr = $res->dateDebutmonth."-".$res->dateDebutyear;
            $dateFinStr =  $res->dateFinmonth."-".$res->dateFinyear;
            $statut = $val['statut'];

            print "['".$val['id'] . "','".htmlentities($val['nom']) . "', '".$val['dateDebut']. "','".$val['dateFin']."','".$statut."','".($stat?$stat:0)."','".$dateDebutStr."','".$dateFinStr."','".$statut."',],";
        }
        print "];";



        print "</script>";
    break;

}





//secteur
//            -> taille
//            -> Region geographique
//            -> les n derniers qui ont commande
//            -> les n derniers client qui n'ont pas commandes
//            -> les n derniers client qui ont  commandes
//            -> les n derniers prospects
//            -> les clients qui ont commande tel produits / services + idem avec factures + negate
//            -> les clients/prospect a qui ont a proposer tel produits / services + negate
//            -> les clients qui ont ete contacte a tel date + negate (table actioncom)
//            -> clients prospects sans activite depuis temps de temps / tel date
//            -> choix libre avec affiche alphabetique , par 100
//        -> faire une campagne avec suivie + action com
//        -> matrice pour enchainer et suivre les prospect
//        -> BI => resultat de la campagne
function cartouche_campagne($db,$id,$title='Nouvelle campagne de prospection')
{
    global $langs;
     ///**************************** Cartouche campagne **********************//
    $campagne = new Campagne($db);
    $campagne->fetch($id);
    print "<br>";
    print "<form action='?action=newAdd' method='POST'>";
    print '<table width="100%" class="notopnoleftnoright">';
    print "<tr  class='liste_titre' align='center' style='font-size: 120%; font-weight: 00;'><td>".$langs->trans($title);
    print '<tr><td valign="top" class="notopnoleft">';

    print '<table class="border" width="100%">';

    print '<tr><td width="15%">'.$langs->trans("D&eacute;signation de la campagne").'</td>';
    print "<td width='40%' colspan='1'>";
    print $campagne->nom;
    print '</td>';
    $requete = "SELECT Babel_campagne_people.isResponsable,
                       ".MAIN_DB_PREFIX."user.rowid as uid,
                       ".MAIN_DB_PREFIX."user.lastname as uname,
                       ".MAIN_DB_PREFIX."user.firstname as ufirstname
                  FROM ".MAIN_DB_PREFIX."user,
                       Babel_campagne_people
                 where ".MAIN_DB_PREFIX."user.rowid = Babel_campagne_people.user_refid
                   AND campagne_refid = ". $campagne->id."
               order by ".MAIN_DB_PREFIX."user.lastname, ".MAIN_DB_PREFIX."user.firstname";
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
                $strCommerciaux .= '<tr><td><a href="'.DOL_URL_ROOT.'/user/card.php?id='.$res->uid.'">'.img_object('',"user")." ".$nom."</a></td></tr>";
            }
        }
    }
    print "<td colspan=1 rowspan=5 width=45% style='background-color: rgb(253,245,163);'>";
    print '<div name="desc" width="70em" class="flat"  rows="9">'.$campagne->notePublic.'</div>';
    print "</td></tr>";
    print '<tr><td>'.$langs->trans('Responsable').'</td><td colspan="1">'.$strResponsable.'</td></tr>';
    print '<tr><td>'.$langs->trans('Commerciaux').'</td><td colspan="1"><table class="nobordernopadding" style="width:100%">'.$strCommerciaux.'</table></td></tr>';
    print '<tr><td>'.$langs->trans('Date d&eacute;but').'</td><td colspan="1">';
     if ('x'.$campagne->dateDebutEffective !='x')
     {
        print $campagne->dateDebutEffectiveday.'/'.$campagne->dateDebutEffectivemonth.'/'.$campagne->dateDebutEffectiveyear;
     } else {
        print $campagne->dateDebutday.'/'.$campagne->dateDebutmonth.'/'.$campagne->dateDebutyear;
     }
    print '</td></tr>';
    print '<tr><td>'.$langs->trans('Date fin').'</td><td colspan="1">';
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
?>
