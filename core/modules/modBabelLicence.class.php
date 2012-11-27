<?php
/* Copyright (C) 2007      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id: modBabelLicence.class.php,v 1.5 2008/01/13 22:48:28 eldy Exp $
 */

/**
        \defgroup   Licences     Module Licence
        \brief      Module pour gerer une base de Licences
*/

/**
        \file       htdocs/core/modules/modBabelLicence.class.php
        \ingroup    adherent
        \brief      Fichier de description et activation du module Licence
*/

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


class modBabelLicence extends DolibarrModules
{

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function modBabelLicence($DB)
    {
        $this->db = $DB;

        $this->family = "Affaire";
        $this->name = "Licences";
        $this->description = "Gestion d'une base de licences";
        $this->version = '0.1';            // 'development' or 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_LICENCES';
        $this->special = 2;
        $this->picto='Licences';

        // Dir
        //----
        $this->dirs = array();

        // Config pages
        //-------------
        $this->config_page_url = array();

        // Dependances
        //------------
        $this->depends = array();
        $this->requiredby = array();
        $this->langfiles = array("licences");

        // Constantes
        //-----------
        $this->const = array();

        // Boites
        //-------
        $this->boxes = array();

        // Permissions
        //------------
        $this->rights = array();
        $this->rights_class = 'Licence';
        $r=0;

        $r++;
        $this->rights[$r][0] = 6301;
        $this->rights[$r][1] = 'Lire la base de licences';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'lire';

        $r++;
        $this->rights[$r][0] = 6302;
        $this->rights[$r][1] = 'Creer/modifier la base de licence';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'creer';

        $r++;
        $this->rights[$r][0] = 6303;
        $this->rights[$r][1] = 'Effacer des licences';
        $this->rights[$r][2] = 'd';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'effacer';

        // Exports
        //--------
        $r=0;

        // $this->export_code[$r]          Code unique identifiant l'export (tous modules confondus)
        // $this->export_label[$r]         Libelle par defaut si traduction de cle "ExportXXX" non trouvee (XXX = Code)
        // $this->export_permission[$r]    Liste des codes permissions requis pour faire l'export
        // $this->export_fields_sql[$r]    Liste des champs exportables en codif sql
        // $this->export_fields_name[$r]   Liste des champs exportables en codif traduction
        // $this->export_sql[$r]           Requete sql qui offre les donnees a l'export
    }


    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees a creer pour ce module.
     */
    function init()
    {
        global $conf;

        // Permissions
        $this->remove();

        $requete = "SELECT rowid FROM `".MAIN_DB_PREFIX."menu` WHERE level = -1 AND titre = 'Tools'";
        $sql = $this->db->query($requete);
        if ($sql)
        {
            $res = $this->db->fetch_object($sql);
            $mainmenuId = $res->rowid;
            $requete1 = "INSERT INTO `".MAIN_DB_PREFIX."menu`
                                     (`rowid`,`menu_handler`,`module`,`type`,`mainmenu`,`fk_menu`,`position`,`url`,`target`,`titre`,`langs`,`level`,`leftmenu`,`perms`,`user`)
                              VALUES
                                     (6301, 'auguria', 'Licence', 'left', 'tools', ".$mainmenuId.", 5, '/Babel_Licence/index.php?leftmenu=Licence', '', 'Licences', 'licences', 0, '1', '\$user->rights->Licence->lire', 2);";
            $res1 = $this->db->query($requete1);
        }

        $sql = array();
        return $this->_init($sql);
    }
    /**
     *    \brief      Fonction appelee lors de la desactivation d'un module.
     *                Supprime de la base les constantes, boites et permissions du module.
     */
    function remove()
    {
        $sql = array();
        $requete = 'DELETE FROM ".MAIN_DB_PREFIX."menu WHERE rowid = 6301';
        $res=$this->db->query($requete);

        return $this->_remove($sql);
    }

}
?>