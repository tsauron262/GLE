<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/
/*
  * GLE by Synopsis et DRSI
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
/*
 *
 * $Id: apercu.php,v 1.5 2008/01/29 19:03:36 eldy Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/synopsis_demandeinterv/apercu.php,v $
 */

/**
        \file        htdocs/synopsis_demandeinterv/apercu.php
        \ingroup    demandeInterv
        \brief        Page de l'onglet apercu d'une fiche d'intervention
        \version    $Revision: 1.5 $
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/demandeInterv.lib.php");

if (!$user->rights->synopsisdemandeinterv->lire)
    accessforbidden();

$langs->load('interventions');

require_once(DOL_DOCUMENT_ROOT.'/Synopsis_DemandeInterv/demandeInterv.class.php');

if ($conf->projet->enabled)
{
    require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
}


/*
 * Securite acces client
*/
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}

llxHeader();

$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */

if ($_GET["id"] > 0) {
    $demandeInterv = new demandeInterv($db);

    if ( $demandeInterv->fetch($_GET["id"], $user->societe_id) > 0)
        {
        $soc = new Societe($db, $demandeInterv->socid);
        $soc->fetch($demandeInterv->socid);


        $head = demandeInterv_prepare_head($demandeInterv);
    dol_fiche_head($head, 'preview', $langs->trans("DI"));


        /*
        *   demande intervention
        */
        $sql = 'SELECT s.nom, s.rowid, fi.fk_projet, fi.ref, fi.description, fi.fk_statut,';
        $sql.= ' fi.fk_user_author, fi.fk_user_valid, fi.datec, fi.date_valid';
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."Synopsis_demandeInterv as fi";
        $sql.= ' WHERE fi.fk_soc = s.rowid';
        $sql.= ' AND fi.rowid = '.$demandeInterv->id;
        if ($socid) $sql .= ' AND s.rowid = '.$socid;

        $result = $db->query($sql);

        if ($result)
        {
            if ($db->num_rows($result))
            {
                $obj = $db->fetch_object($result);

                $societe = new Societe($db);
                $societe->fetch($obj->rowid);

                print '<table class="border" width="100%">';

            // Ref
            print '<tr><td  class=\'ui-widget-header ui-state-default\' width="18%">'.$langs->trans("Ref")."</td>";
            print '<td colspan="2" class=\'ui-widget-content\'>'.$demandeInterv->ref.'</td>';

            $nbrow=4;
                print '<td rowspan="'.$nbrow.'" valign="top" width="50%">';

                /*
            * Documents
                */
                $demandeIntervref = sanitize_string($demandeInterv->ref);
                $dir_output = $conf->synopsisdemandeinterv->dir_output . "/";
                $filepath = $dir_output . $demandeIntervref . "/";
                $file = $filepath . $demandeIntervref . ".pdf";
                $filedetail = $filepath . $demandeIntervref . "-detail.pdf";
                $relativepath = "${demandeIntervref}/${demandeIntervref}.pdf";
                $relativepathdetail = "${demandeIntervref}/${demandeIntervref}-detail.pdf";

        // Chemin vers png apercus
                $relativepathimage = "${demandeIntervref}/${demandeIntervref}.pdf.png";
                $fileimage = $file.".png";          // Si PDF d'1 page
                $fileimagebis = $file.".png.0";     // Si PDF de plus d'1 page

                $var=true;

                // Si fichier PDF existe
                if (file_exists($file))
                {
                    $encfile = urlencode($file);
                    print_titre($langs->trans("Documents"));
                    print '<table class="border" width="100%">';

                    print "<tr $bc[$var]><td>".$langs->trans("DI")." PDF</td>";

                    print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=demandeInterv&file='.urlencode($relativepath).'">'.$demandeInterv->ref.'.pdf</a></td>';
                    print '<td align="right">'.filesize($file). ' bytes</td>';
                    print '<td align="right">'.dol_print_date(filemtime($file),'day').'</td>';
                    print '</tr>';

                    // Si fichier detail PDF existe
                    if (file_exists($filedetail)) { // demandeInterv detaillee supplementaire
                        print "<tr $bc[$var]><td>Demande d'intervention d&eacute;taill&eacute;e</td>";

                        print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=demandeInterv&file='.urlencode($relativepathdetail).'">'.$demandeInterv->ref.'-detail.pdf</a></td>';
                        print '<td align="right">'.filesize($filedetail). ' bytes</td>';
                        print '<td align="right">'.dol_print_date(filemtime($filedetail),'day').'</td>';
                        print '</tr>';
                    }
                    print "</table>\n";

                    // Conversion du PDF en image png si fichier png non existant
                    if (! file_exists($fileimage) && ! file_exists($fileimagebis))
                    {
                        /* Create the Imagick object */
                        $im = new Imagick();

                        /* Read the image file */
                        $im->readImage(  $file );

                        /* Thumbnail the image ( width 100, preserve dimensions ) */
                        $im->thumbnailImage( 400, null );

                        /* Write the thumbail to disk */
                         $im->writeImage( $file .".png" );

                         /* Free resources associated to the Imagick object */
                         $im->destroy();
//                        if (function_exists("imagick_readimage"))
//                        {
//                            $handle = imagick_readimage( $file ) ;
//                            if ( imagick_iserror( $handle ) )
//                            {
//                                $reason      = imagick_failedreason( $handle ) ;
//                                $description = imagick_faileddescription( $handle ) ;
//
//                                print "handle failed!<BR>\nReason: $reason<BR>\nDescription: $description<BR>\n";
//                            }
//                            imagick_convert( $handle, "PNG" ) ;
//                            if ( imagick_iserror( $handle ) )
//                            {
//                                $reason      = imagick_failedreason( $handle ) ;
//                                $description = imagick_faileddescription( $handle ) ;
//                                print "handle failed!<BR>\nReason: $reason<BR>\nDescription: $description<BR>\n";
//                            }
//                            imagick_writeimages( $handle, $file .".png");
//                        } else {
//                            $langs->load("other");
//                            print '<font class="error">'.$langs->trans("ErrorNoImagickReadimage").'</font>';
//                        }
                    }
                }

                print "</td></tr>";


                // Client
                print "<tr><td class='ui-widget-header ui-state-default'>".$langs->trans("Customer")."</td>";
                print '<td colspan="2" class="ui-widget-content">';
                print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$societe->id.'">'.$societe->nom.'</a>';
                print '</td>';
                print '</tr>';

                // Statut
                print '<tr><td class=\'ui-widget-header ui-state-default\'>'.$langs->trans("Status").'</td>';
                print "<td colspan=\"2\"  class='ui-widget-content'>".$demandeInterv->getLibStatut(4)."</td>\n";
                print '</tr>';

                // Date
                print '<tr><td class=\'ui-widget-header ui-state-default\'>'.$langs->trans("Date").'</td>';
                print "<td colspan=\"2\" class='ui-widget-content' >".dol_print_date($demandeInterv->date,"day")."</td>\n";
                print '</tr>';

                print '</table>';
            }
        } else {
            dol_print_error($db);
        }
    } else {
    // Intervention non trouvee
    print $langs->trans("ErrordemandeIntervNotFound",$_GET["id"]);
    }
}

// Si fichier png PDF d'1 page trouve
if (file_exists($fileimage))
    {
    print '<center><img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercudemandeInterv&file='.urlencode($relativepathimage).'"></center>';
    }
// Si fichier png PDF de plus d'1 page trouve
elseif (file_exists($fileimagebis))
    {
        $multiple = $relativepathimage . ".";

        for ($i = 0; $i < 20; $i++)
        {
            $preview = $multiple.$i;

            if (file_exists($dir_output.$preview))
      {
        print '<center><img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercudemandeInterv&file='.urlencode($preview).'"><p></center>';
      }
        }
    }


print '</div>';


// Juste pour eviter bug IE qui reorganise mal div precedents si celui-ci absent
print '<div class="tabsAction">';
print '</div>';


$db->close();

llxFooter('$Date: 2008/01/29 19:03:36 $ - $Revision: 1.5 $');
?>
