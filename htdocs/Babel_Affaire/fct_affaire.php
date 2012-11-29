<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 26 juil. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : fct_affaire.php
  * GLE-1.2
  */

function print_cartoucheAffaire($affaire,$index='element',$action=false)
{
    global $langs;
    $head=affaire_prepare_head($affaire);
    dol_fiche_head($head, $index, $langs->trans("Affaire"));


    print '<table class="border" width="100%" cellpadding=15>';
//TODO droit de modifier
    if ($action == 'edit')
    {
        print '<tr><th width=200 class="ui-widget-header ui-state-default">'.$langs->trans("Nom").'</th>
                   <td class="ui-widget-content"><input type="text" value="'.$affaire->nom.'" name="nom" id="nom"></td></tr>';
        print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Description").'</th>
                   <td class="ui-widget-content"><textarea name="description" id="description">'.$affaire->description.'</textarea></td></tr>';
        print '<tr><th width=200 class="ui-widget-header ui-state-default">'.$langs->trans("Ref").'</th>
                   <td class="ui-widget-content">'.$affaire->ref.'</td></tr>';
    } else {
        print '<tr><th width=200 class="ui-widget-header ui-state-default">'.$langs->trans("Nom").'</th>
                   <td class="ui-widget-content">'.$affaire->nom.'</td></tr>';
        print '<tr><th width=200 class="ui-widget-header ui-state-default">'.$langs->trans("Ref").'</th>
                   <td class="ui-widget-content">'.$affaire->ref.'</td></tr>';
        print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Description").'</th>
                   <td class="ui-widget-content">'.$affaire->description.'</td></tr>';

        print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Date cr&eacute;ation").'</th>
                   <td class="ui-widget-content">'.date('d/m/Y',$affaire->date_creation).'</td></tr>';
        print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Cr&eacute;ateur").'</th>
                   <td class="ui-widget-content">'.$affaire->user_author->getNomUrl(1).'</td></tr>';
        print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Statut").'</th>
                   <td class="ui-widget-content">'.$affaire->getLibStatut(4).'</td></tr>';
    }


    print '</table>';
}

function affaire_prepare_head($affaire)
{
    global $langs, $conf, $user,$db;
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/Babel_Affaire/fiche.php?id='.$affaire->id;
    $head[$h][1] = $langs->trans("Fiche");
    $head[$h][2] = 'index';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/Babel_Affaire/element.php?id='.$affaire->id;
    $head[$h][1] = $langs->trans("R&eacute;f&eacute;rents");
    $head[$h][2] = 'element';
    $h++;
    if ($affaire->statut < 2)
    {
        $head[$h][0] = DOL_URL_ROOT.'/Babel_Affaire/ajoutElements.php?id='.$affaire->id;
        $head[$h][1] = $langs->trans("Ajout");
        $head[$h][2] = 'Ajout';
        $h++;
    }

//    $head[$h][0] = DOL_URL_ROOT.'/Babel_Affaire/stats.php?id='.$affaire->id;
//    $head[$h][1] = $langs->trans("Stats");
//    $head[$h][2] = 'Stats';
//    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/Babel_Affaire/PN.php?id='.$affaire->id;
    $head[$h][1] = $langs->trans("Fiche PN");
    $head[$h][2] = 'PN';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/Babel_Affaire/contact.php?id='.$affaire->id;
    $head[$h][1] = $langs->trans("Contacts");
    $head[$h][2] = 'Contacts';
    $h++;


    $head[$h][0] = DOL_URL_ROOT.'/Babel_Affaire/categorie.php?typeid=3&id='.$affaire->id;
    $head[$h][1] = $langs->trans("Cat&eacute;gories");
    $head[$h][2] = 'Categorie';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/Babel_Affaire/relation.php?typeid=3&id='.$affaire->id;
    $head[$h][1] = $langs->trans("Relation");
    $head[$h][2] = 'Relation';
    $h++;


    $head[$h][0] = DOL_URL_ROOT.'/Babel_Calendar/calendar.php?type=Affaire&id='.$affaire->id;
    $head[$h][1] = $langs->trans("Calendrier");
    $head[$h][2] = 'Calendrier';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/Babel_Affaire/document.php?id='.$affaire->id;
    $head[$h][1] = $langs->trans("Documents");
    $head[$h][2] = 'document';
    $h++;

    if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS && $user->rights->process->lire &&  DoesElementhasProcess($db,'Affaire'))
    {
        $head[$h][0] = DOL_URL_ROOT.'/Synopsis_Process/listProcessForElement.php?type=Affaire&id='.$affaire->id;
        $head[$h][1] = $langs->trans("Process");
        $head[$h][2] = 'process';
        $head[$h][4] = 'ui-icon ui-icon-gear';
        $h++;
    }

    return($head);
}



?>
