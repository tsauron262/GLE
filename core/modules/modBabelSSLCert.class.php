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
 * $Id: modBabelSSLCert.class.php,v 1.5 2008/01/13 22:48:28 eldy Exp $
 */

/**
        \defgroup   SSLCert     Module SSLCert
        \brief      Module pour gerer une base de noms de SSLCertes
*/

/**
        \file       htdocs/core/modules/modBabelSSLCert.class.php
        \ingroup    adherent
        \brief      Fichier de description et activation du module SSLCert
*/

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
        \class      modAdherent
        \brief      Classe de description et activation du module Adherent
*/

class modBabelSSLCert extends DolibarrModules
{

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function modBabelSSLCert($DB)
    {
        $this->db = $DB;
        $this->numero = 6200 ;

        $this->family = "Affaire";
        $this->name = "SSLCerts";
        $this->description = "Gestion d'une base de certificats num&eacute;riques";
        $this->version = '0.1';            // 'development' or 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SSLCERT';
        $this->special = 2;
        $this->picto='SSLCERT';

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
        $this->langfiles = array("SSLCerts");

        // Constantes
        //-----------
        $this->const = array('MAIN_DELAY_SSLCERT_WARNING'=>30);

        // Boites
        //-------
        $this->boxes = array();

        // Permissions
        //------------
        $this->rights = array();
        $this->rights_class = 'SSLCert';
        $r=0;

        $r++;
        $this->rights[$r][0] = 6201;
        $this->rights[$r][1] = 'Lire la base des certificats';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'lire';

        $r++;
        $this->rights[$r][0] = 6202;
        $this->rights[$r][1] = 'Creer/modifier la base des certificats';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'creer';

        $r++;
        $this->rights[$r][0] = 6203;
        $this->rights[$r][1] = 'Effacer des certificats';
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
                                     (6201, 'auguria', 'SSLCert', 'left', 'tools', ".$mainmenuId.", 5, '/Babel_SSLCert/index.php?leftmenu=SSLCert', '', 'SSLCerts', 'SSLCerts', 0, '', '\$user->rights->SSLCert->lire', 2);";
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
        $requete = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE rowid = 6201";
        $res = $this->db->query($requete);

        return $this->_remove($sql);
    }

}
?>