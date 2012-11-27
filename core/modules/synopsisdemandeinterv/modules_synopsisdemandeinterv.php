<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
        \file       htdocs/core/modules/synopsis_demandeinterv/modules_synopsisdemandeinterv.php
        \ingroup    demandeInterv
        \brief      Fichier contenant la classe mere de generation des fiches interventions en PDF
                    et la classe mere de numerotation des fiches interventions
        \version    $Id: modules_demandeInterv.php,v 1.24 2008/07/12 12:45:31 eldy Exp $
*/

require_once(DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php');


/**
        \class      ModeleSynopsis_demandeinterv
        \brief      Classe mere des modeles de fiche intervention
*/
class ModeleSynopsisdemandeinterv extends CommonDocGenerator
{
    public $error='';

    /**
        \brief      Constructeur
     */
    function ModeleSynopsisdemandeinterv()
    {

    }

    /**
        \brief      Renvoi le dernier message d'erreur de creation de fiche intervention
     */
    function pdferror()
    {
        return $this->error;
    }

    /**
     *      \brief      Renvoi la liste des modeles actifs
     */
    function liste_modeles($db)
    {
        $type='demandeInterv';
        $liste=array();
        $sql ="SELECT nom as id, nom as lib";
        $sql.=" FROM ".MAIN_DB_PREFIX."document_model";
        $sql.=" WHERE type = '".$type."'";

        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $row = $db->fetch_row($resql);
                $liste[$row[0]]=$row[1];
                $i++;
            }
        }
        else
        {
            return -1;
        }
        return $liste;
    }

}


/**
        \class      ModeleNumRefdemandeInterv
        \brief      Classe mere des modeles de numerotation des references de fiches d'intervention
*/

class ModeleNumRefdemandeInterv
{
    var $error='';

    /**     \brief      Renvoi la description par defaut du modele de numerotation
     *      \return     string      Texte descripif
     */
    function info()
    {
        global $langs;
        $langs->load("demandeInterv");
        return $langs->trans("NoDescription");
    }

    /**     \brief      Renvoi un exemple de numerotation
     *      \return     string      Example
     */
    function getExample()
    {
        global $langs;
        $langs->load("demandeInterv");
        return $langs->trans("NoExample");
    }

    /**     \brief      Test si les numeros deje en vigueur dans la base ne provoquent pas de
     *                  de conflits qui empechera cette numerotation de fonctionner.
     *      \return     boolean     false si conflit, true si ok
     */
    function canBeActivated()
    {
        return true;
    }

    /**     \brief      Renvoi prochaine valeur attribuee
     *      \return     string      Valeur
     */
    function getNextValue()
    {
        global $langs;
        return $langs->trans("NotAvailable");
    }

    /**     \brief      Renvoi version du module numerotation
    *          \return     string      Valeur
    */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("VersionDevelopment");
        if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
        if ($this->version == 'dolibarr') return GLE_VERSION;
        return $langs->trans("NotAvailable");
    }
}


/**
        \brief      Cree une fiche intervention sur disque en fonction du modele de demandeInterv_ADDON_PDF
        \param        db              objet base de donnee
        \param        object            Object demandeInterv
        \param        modele            force le modele e utiliser ('' par defaut)
        \param        outputlangs        objet lang a utiliser pour traduction
        \return     int             0 si KO, 1 si OK
*/
function demandeInterv_create($db, $object, $modele='', $outputlangs='')
{
    global $conf,$langs;
    $langs->load("demandeInterv");

    $dir = DOL_DOCUMENT_ROOT."/core/modules/synopsisdemandeinterv/";

    // Positionne modele sur le nom du modele de facture e utiliser
    if (! strlen($modele))
    {
        if ($conf->global->DEMANDEINTERV_ADDON_PDF)
        {
            $modele = $conf->global->DEMANDEINTERV_ADDON_PDF;
        }
        else
        {
            dol_syslog("Error ".$langs->trans("Error_DEMANDEINTERV_ADDON_PDF_NotDefined"), LOG_ERR);
            print "Error ".$langs->trans("Error_DEMANDEINTERV_ADDON_PDF_NotDefined");
            return 0;
        }
    }

    // Charge le modele
    $file = "pdf_".$modele.".modules.php";
    if (file_exists($dir.$file))
    {
        $classname = "pdf_".$modele;
        require_once($dir.$file);

        $obj = new $classname($db);

        dol_syslog("demandeInterv_create build PDF", LOG_DEBUG);
        if ($obj->write_file($object,$outputlangs) > 0)
        {
            return 1;
        }
        else
        {
            dol_print_error($db,$obj->pdferror());
            return 0;
        }
    }
    else
    {
        print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file);
        return 0;
    }
}

/**
   \brief     Supprime l'image de previsualitation, pour le cas de regeneration de propal
   \param        db          objet base de donnee
   \param        propalid    id de la propal e effacer
   \param     propalref reference de la propal si besoin
*/
function demandeInterv_delete_preview($db, $demandeIntervid, $demandeIntervref='')
{
    global $langs,$conf;

    if (!$demandeIntervref)
  {
    $demandeInterv = new demandeInterv($db,"",$demandeIntervid);
    $demandeInterv->fetch($demandeIntervid);
    $demandeIntervref = $demandeInterv->ref;
   }

   if ($conf->synopsisdemandeinterv->dir_output)
   {
    $demandeIntervref = sanitize_string($demandeIntervref);
    $dir = $conf->synopsisdemandeinterv->dir_output . "/" . $demandeIntervref ;
    $file = $dir . "/" . $demandeIntervref . ".pdf.png";
    $multiple = $file . ".";

    if ( file_exists( $file ) && is_writable( $file ) )
    {
        if ( ! unlink($file) )
        {
            $this->error=$langs->trans("ErrorFailedToOpenFile",$file);
            return 0;
        }
    }
    else
    {
        for ($i = 0; $i < 20; $i++)
        {
            $preview = $multiple.$i;
            if ( file_exists( $preview ) && is_writable( $preview ) )
            {
                if ( ! unlink($preview) )
                {
                    $this->error=$langs->trans("ErrorFailedToOpenFile",$preview);
                    return 0;
                }
            }
        }
    }
  }
}

?>
