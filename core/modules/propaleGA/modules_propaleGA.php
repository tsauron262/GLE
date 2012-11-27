<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.*//*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.synopsis-erp.com
  *
  *//*
 * or see http://www.gnu.org/
 */

/**
        \file       htdocs/includes/modules/propale/modules_propale.php
        \ingroup    propaleGA
        \brief      Fichier contenant la classe mere de generation des propalesGA en PDF
                    et la classe mere de numerotation des propales
        \version    $Id: modules_propale.php,v 1.35 2008/07/11 16:14:59 eldy Exp $
*/

require_once(DOL_DOCUMENT_ROOT.'/lib/functions.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');
require_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");   // Requis car utilise dans les classes qui heritent


/**
        \class      ModelePDFPropales
        \brief      Classe mere des modeles de propale
*/

class ModelePDFPropalesGA extends FPDF
{
    var $error='';

    /**
     *      \brief      Renvoi le dernier message d'erreur de creation de propale
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
        $type='propalGA';
        $liste=array();
        $sql ="SELECT nom as id, ifnull(libelle,nom) as lib";
        $sql.=" FROM llx_document_model";
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
        } else {
            $this->error=$db->error();
            return -1;
        }
        return $liste;

    }

}
/**
        \class      ModeleNumRefPropales
        \brief      Classe mere des modeles de numerotation des references de propales
*/

class ModeleNumRefPropalesGA
{
    var $error='';

    /**     \brief      Renvoi la description par defaut du modele de numerotation
     *      \return     string      Texte descripif
     */
    function info()
    {
        global $langs;
        $langs->load("propale");
        return $langs->trans("NoDescription");
    }

    /**     \brief      Renvoi un exemple de numerotation
     *      \return     string      Example
     */
    function getExample()
    {
        global $langs;
        $langs->load("propale");
        return $langs->trans("NoExample");
    }

    /**     \brief      Test si les numeros deja en vigueur dans la base ne provoquent pas de
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
        \brief      Cree une propale sur disque en fonction du modele de PROPALE_ADDON_PDF
        \param        db              objet base de donnee
        \param        id                id de la propale a creer
        \param        modele            force le modele a utiliser ('' par defaut)
        \param        outputlangs        objet lang a utiliser pour traduction
        \return     int             0 si KO, 1 si OK
*/
//if (function_exists('propale_pdf_create'))
//{
//
//} else {
//    function propale_pdf_create($db, $id, $modele='', $outputlangs='')
//    {
//        return (propaleGA_pdf_create($db, $id,$modele,$outputlangs));
//    }
//}

function propaleGA_pdf_create($db, $id, $modele='', $outputlangs='')
{
    global $langs;
    $langs->load("propale");

    $dir = DOL_DOCUMENT_ROOT."/includes/modules/propaleGA/";
    $modelisok=0;

    // Positionne modele sur le nom du modele de propale a utiliser
    $file = "pdf_propaleGA_".$modele.".modules.php";
    if ($modele && file_exists($dir.$file)) $modelisok=1;

    // Si model pas encore bon
    if (! $modelisok)
    {
        if ($conf->global->PROPALEGA_ADDON_PDF) $modele = $conf->global->PROPALEGA_ADDON_PDF;
        $file = "pdf_propaleGA_".$modele.".modules.php";
        if (file_exists($dir.$file)) $modelisok=1;
    }
    // Si model pas encore bon
    if (! $modelisok)
    {
        $liste=array();
        $model=new ModelePDFPropalesGA();
        $liste=$model->liste_modeles($db);
        $modele=key($liste);        // Renvoie premiere valeur de cle trouvee dans le tableau
        $file = "pdf_propaleGA_".$modele.".modules.php";
        if (file_exists($dir.$file)) $modelisok=1;
    }


    // Charge le modele
    if ($modelisok)
    {
        $classname = "pdf_propaleGA_".$modele;
        require_once($dir.$file);
        $obj = new $classname($db);
        if ($obj->write_file($id, $outputlangs) > 0)
        {
            // on supprime l'image correspondant au preview
            propaleGA_delete_preview($db, $id);
            return 1;
        }
        else
        {
            dolibarr_syslog("Erreur dans propale_pdf_create");
            dolibarr_print_error($db,$obj->pdferror());
            return 0;
        }
    }
    else
    {
        if (! $conf->global->PROPALEGA_ADDON_PDF)
        {
            print $langs->trans("Error")." ".$langs->trans("Error_PROPALEGA_ADDON_PDF_NotDefined");
        }
        else
        {
            print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file);
        }
        return 0;
    }
}



/**
   \brief      Supprime l'image de previsualitation, pour le cas de regeneration de propal
   \param        db          objet base de donnee
   \param        propalid    id de la propal a effacer
   \param     propalref reference de la propal si besoin
*/
//if (function_exists('propale_delete_preview'))
//{
//
//} else {
//    function propale_delete_preview($db, $propalid, $propalref='')
//    {
//        return (propaleGA_delete_preview($db, $propalid, $propalref));
//    }
//}
function propaleGA_delete_preview($db, $propalid, $propalref='')
{
        global $langs,$conf;

        if (!$propalref)
        {
            $propal = new Propal($db,"",$propalid);
            $propal->fetch($propalid);
            $propalref = $propal->ref;
        }

        if ($conf->propal->dir_output)
        {
            $propalref = sanitize_string($propalref);
            $dir = $conf->propal->dir_output . "/" . $propalref ;
            $file = $dir . "/" . $propalref . ".pdf.png";
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
