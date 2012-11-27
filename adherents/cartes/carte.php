<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file 		htdocs/adherents/cartes/carte.php
 *	\ingroup    member
 *	\brief      Page to output members business cards
 */
require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/modules/member/cards/modules_cards.php");
require_once(DOL_DOCUMENT_ROOT."/core/modules/member/labels/modules_labels.php");

$langs->load("members");
$langs->load("errors");

// Choix de l'annee d'impression ou annee courante.
$now = dol_now();
$year=dol_print_date($now,'%Y');
$month=dol_print_date($now,'%m');
$day=dol_print_date($now,'%d');
$foruserid=GETPOST('foruserid');
$foruserlogin=GETPOST('foruserlogin');
$mode=GETPOST('mode');

$mesg='';


/*
 * View
 */

if ($mode == 'cardlogin' && empty($foruserlogin))
{
    $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Login"));
}

if ((! empty($foruserid) || ! empty($foruserlogin) || ! empty($mode)) && ! $mesg)
{
    $arrayofmembers=array();

    // requete en prenant que les adherents a jour de cotisation
    $sql = "SELECT d.rowid, d.prenom as firstname, d.nom as lastname, d.login, d.societe, d.datefin,";
    $sql.= " d.adresse, d.cp, d.ville, d.naiss, d.email, d.photo,";
    $sql.= " t.libelle as type,";
    $sql.= " p.libelle as pays";
    $sql.= " FROM ".MAIN_DB_PREFIX."adherent_type as t, ".MAIN_DB_PREFIX."adherent as d";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p ON d.pays = p.rowid";
    $sql.= " WHERE d.fk_adherent_type = t.rowid AND d.statut = 1";
    if (is_numeric($foruserid)) $sql.=" AND d.rowid=".$foruserid;
    if ($foruserlogin) $sql.=" AND d.login='".$db->escape($foruserlogin)."'";
    $sql.= " ORDER BY d.rowid ASC";

    $result = $db->query($sql);
    if ($result)
    {
    	$num = $db->num_rows($result);
    	$i = 0;
    	while ($i < $num)
    	{
    		$objp = $db->fetch_object($result);

    		if ($objp->pays == '-') $objp->pays='';

    		// List of values to scan for a replacement
            $substitutionarray = array (
            '%PRENOM%'=>$objp->firstname,
            '%NOM%'=>$objp->lastname,
            '%LOGIN%'=>$objp->login,
            '%SERVEUR%'=>"http://".$_SERVER["SERVER_NAME"]."/",
            '%SOCIETE%'=>$objp->societe,
            '%ADRESSE%'=>$objp->adresse,
            '%CP%'=>$objp->cp,
            '%VILLE%'=>$objp->ville,
            '%PAYS%'=>$objp->pays,
            '%EMAIL%'=>$objp->email,
            '%NAISS%'=>dol_print_date($objp->naiss,'day'),
            '%TYPE%'=>$objp->type,
            '%ID%'=>$objp->rowid,
            '%ANNEE%'=>$year,    // For backward compatibility
            '%YEAR%'=>$year,
            '%MONTH%'=>$month,
            '%DAY%'=>$day
            );
            complete_substitutions_array($substitutionarray, $langs);

            // For business cards
            if (empty($mode) || $mode=='card' || $mode=='cardlogin')
            {
                $textleft=make_substitutions($conf->global->ADHERENT_CARD_TEXT, $substitutionarray);
                $textheader=make_substitutions($conf->global->ADHERENT_CARD_HEADER_TEXT, $substitutionarray);
                $textfooter=make_substitutions($conf->global->ADHERENT_CARD_FOOTER_TEXT, $substitutionarray);
                $textright=make_substitutions($conf->global->ADHERENT_CARD_TEXT_RIGHT, $substitutionarray);

                if (is_numeric($foruserid) || $foruserlogin)
                {
                    for($j=0;$j<100;$j++)
                    {
                        $arrayofmembers[]=array('textleft'=>$textleft,
                                        'textheader'=>$textheader,
                                        'textfooter'=>$textfooter,
                                        'textright'=>$textright,
                                        'id'=>$objp->rowid,
                                        'photo'=>$objp->photo);
                    }
                }
                else
                {
                    $arrayofmembers[]=array('textleft'=>$textleft,
                                        'textheader'=>$textheader,
                                        'textfooter'=>$textfooter,
                                        'textright'=>$textright,
                                        'id'=>$objp->rowid,
                                        'photo'=>$objp->photo);
                }
            }

            // For labels
            if ($mode == 'label')
            {
                $conf->global->ADHERENT_ETIQUETTE_TEXT="%PRENOM% %NOM%\n%ADRESSE%\n%CP% %VILLE%\n%PAYS%";
                $textleft=make_substitutions($conf->global->ADHERENT_ETIQUETTE_TEXT, $substitutionarray);
                $textheader='';
                $textfooter='';
                $textright='';

                $arrayofmembers[]=array('textleft'=>$textleft,
                                        'textheader'=>$textheader,
                                        'textfooter'=>$textfooter,
                                        'textright'=>$textright,
                                        'id'=>$objp->rowid,
                                        'photo'=>$objp->photo);
            }

            $i++;
    	}

    	// Build and output PDF
        if (empty($mode) || $mode=='card' || $mode=='cardlogin')
        {
            if (! count($arrayofmembers))
            {
                $mesg=$langs->trans("ErrorRecordNotFound");
            }

            if (! $mesg) $result=members_card_pdf_create($db, $arrayofmembers, '', $outputlangs);

        }
        elseif ($mode == 'label')
        {
            $result=members_label_pdf_create($db, $arrayofmembers, '', $outputlangs);
        }

    	if ($result <= 0)
    	{
    		dol_print_error('',$result);
    	}
    }
    else
    {
    	dol_print_error($db);
    }

    if (! $mesg) exit;
}





llxHeader('',$langs->trans("MembersCards"));

print_fiche_titre($langs->trans("LinkToGeneratedPages"));
print '<br>';

print $langs->trans("LinkToGeneratedPagesDesc").'<br>';
print '<br>';

dol_htmloutput_errors($mesg);

print $langs->trans("DocForAllMembersCards",($conf->global->ADHERENT_CARD_TYPE?$conf->global->ADHERENT_CARD_TYPE:$langs->transnoentitiesnoconv("None"))).' ';
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="foruserid" value="all">';
print '<input type="hidden" name="mode" value="card">';
print ' <input class="button" type="submit" value="'.$langs->trans("BuildDoc").'">';
print '</form>';
print '<br>';

print $langs->trans("DocForOneMemberCards",($conf->global->ADHERENT_CARD_TYPE?$conf->global->ADHERENT_CARD_TYPE:$langs->transnoentitiesnoconv("None"))).' ';
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="mode" value="cardlogin">';
print $langs->trans("Login").': <input size="10" type="text" name="foruserlogin" value="'.GETPOST('foruserlogin').'">';
print ' <input class="button" type="submit" value="'.$langs->trans("BuildDoc").'">';
print '</form>';
print '<br>';

print $langs->trans("DocForLabels",$conf->global->ADHERENT_ETIQUETTE_TYPE).' ';
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="mode" value="label">';
print ' <input class="button" type="submit" value="'.$langs->trans("BuildDoc").'">';
print '</form>';
print '<br>';

llxFooter();

$db->close();
?>
