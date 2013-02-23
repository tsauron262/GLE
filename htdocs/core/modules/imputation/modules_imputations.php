<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
        \file       htdocs/includes/modules/deplacement/modules_deplacement.php
        \ingroup    deplacement
        \brief      Fichier contenant la classe mere de generation des deplacements en PDF
                    et la classe mere de numerotation des deplacements
        \version    $Id: modules_deplacement.php,v 1.35 2008/07/11 16:14:59 eldy Exp $
*/

require_once(DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/commondocgenerator.class.php");


/**
        \class      ModelePDFImputations
        \brief      Classe mere des modeles de deplacement
*/
$conf->global->IMPUTATIONS_ADDON_PDF = "caracal";

class ModeleImputations extends CommonDocGenerator
{
    public $error='';

    /**
     *      \brief      Renvoi le dernier message d'erreur de creation de deplacement
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
        $type='imputations';
        $liste=array();
        $sql ="SELECT nom as id, ifnull(libelle,nom) as lib";
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
        } else {
            $this->error=$db->error();
            return -1;
        }
        return $liste;

    }

}
/**
        \class      ModeleNumRefPropales
        \brief      Classe mere des modeles de numerotation des references de deplacements
*/



/**
        \brief      Cree une deplacement sur disque en fonction du modele de DEPLACEMENT_ADDON_PDF
        \param        db              objet base de donnee
        \param        id                id de la deplacement e creer
        \param        modele            force le modele e utiliser ('' par defaut)
        \param        outputlangs        objet lang a utiliser pour traduction
        \return     int             0 si KO, 1 si OK
*/
function imputations_pdf_create($db, $id, $modele='', $outputlangs='')
{
    global $langs,$conf,$user;
    $langs->load("synopsisGene@Synopsis_Tools");
    $langs->load("projects");
    $dir = DOL_DOCUMENT_ROOT."/core/modules/imputation/";
    $modelisok=0;

    if($modele == '')
        $modele = 'caracal';
    // Positionne modele sur le nom du modele de deplacement e utiliser
    $file = "pdf_imputations_".$modele.".modules.php";
//die($dir.$file);
    if ($modele && file_exists($dir.$file)) $modelisok=1;
    // Si model pas encore bon
//    if (! $modelisok)
//    {
//        if ($conf->global->CONTRAT_ADDON_PDF) $modele = $conf->global->CONTRAT_ADDON_PDF;
//        $file = "pdf_cimputationst_".$modele.".modules.php";
//        if (file_exists($dir.$file)) $modelisok=1;
//    }
    // Si model pas encore bon
    if (! $modelisok)
    {
        $liste=array();
        $model=new ModeleImputations();
        $liste=$model->liste_modeles($db);
        $modele=key($liste);        // Renvoie premiere valeur de cle trouve dans le tableau
        $file = "pdf_imputations_".$modele.".modules.php";
        if (file_exists($dir.$file)) $modelisok=1;
    }

    // Charge le modele
    if ($modelisok)
    {
        $classname = "pdf_imputations_".$modele;
        require_once($dir.$file);



//        $requete = "UPDATE ".MAIN_DB_PREFIX."contrat SET modelPdf= '".$modele."' WHERE rowid=".$id;
//        $db->query($requete);
        $obj = new $classname($db);
        $fuser = $user;
        if($_REQUEST['userId'] > 0)
        {
            $fuser->fetch($_REQUEST['userId']);
        }
        if ($obj->write_file($id, $outputlangs,$fuser) > 0)
        {
            // on supprime l'image correspondant au preview
            imputations_delete_preview($db, $id);
            return 1;
        } else {
            dol_syslog("Erreur dans projet_pdf_create");
            dol_print_error($db,$obj->pdferror());
            return 0;
        }
    } else {
        if (! $conf->global->IMPUTATIONS_ADDON_PDF)
        {
            print $langs->trans("Error")." ".$langs->trans("Error_IMPUTATIONS_ADDON_PDF_NotDefined");
        } else {
            print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file);
        }
        return 0;
    }
}



/**
   \brief      Supprime l'image de previsualitation, pour le cas de regeneration de propal
   \param        db          objet base de donnee
   \param        propalid    id de la propal e effacer
   \param     propalref reference de la propal si besoin
*/
function imputations_delete_preview($db, $contratid, $contratref='')
{
        global $langs,$conf,$user,$conf;


        if ($conf->imputations->dir_output)
        {
            $contratref = sanitize_string('imputations'.$user->ud);
            $dir = $conf->imputations->dir_output . "/" . $contratref ;
            $file = $dir . "/" . $contratref . ".pdf.png";
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


/**
        \class      ModeleNumRefCommandes
            \brief      Classe mere des modeles de numerotation des references de commandes
*/
?>
