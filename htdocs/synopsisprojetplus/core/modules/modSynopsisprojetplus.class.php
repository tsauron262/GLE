<?php

/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCmodHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
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
 * $Id: modProjet.class.php,v 1.23 2008/01/13 22:48:26 eldy Exp $
 */

/**  \defgroup   projet     Module projet
  \brief      Module pour inclure le detail par projets dans les autres modules
 */
/** \file       htdocs/core/modules/modProjet.class.php
  \ingroup    projet
  \brief      Fichier de description et activation du module Projet
 */
include_once(DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php");

/**
  \class      modProjet
  \brief      Classe de description et activation du module Projet
 */
class modSynopsisprojetplus extends DolibarrModules {

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function __construct($DB) {
        $this->db = $DB;
        $this->numero = 95002;

        $this->family = "Synopsis";
        $this->name = "Projet +++";
        $this->nameI = "synopsisprojetplus";
        $this->description = "Gestion des projets avancé ++";
        $this->version = 2;
        $this->const_name = 'MAIN_MODULE_SYNOPSISPROJETPLUS';
        $this->special = 0;
        $this->picto = 'project';
        $this->langfiles = array("synopsisprojetplus@synopsisprojetplus");

        // Dependances
        $this->depends = array("modSociete");
        $this->requiredby = array();
        $this->config_page_url = preg_replace('/^mod/i', '', get_class($this)).".php";


        // Constants
        $this->const = array();
        $r = 0;
        
        $this->rights_class = 'synopsisprojet';
        $this->rights[1][0] = 46; // id de la permission
        $this->rights[1][1] = 'Voir / Modifier les imputations des autres'; // libelle de la permission
        $this->rights[1][2] = 'c'; // type de la permission (deprecie a ce jour)
        $this->rights[1][3] = 0; // La permission est-elle une permission par defaut
        $this->rights[1][4] = 'voirImputations';

        $this->rights[6][0] = 47; // id de la permission
        $this->rights[6][1] = 'Attribution/modification de budgets d’heures associées aux tâches et attribués aux utilisateurs'; // libelle de la permission
        $this->rights[6][2] = 'c'; // type de la permission (deprecie a ce jour)
        $this->rights[6][3] = 0; // La permission est-elle une permission par defaut
        $this->rights[6][4] = 'attribution';

        $this->rights[7][0] = 48; // id de la permission
        $this->rights[7][1] = 'Voir les CA dans les imputations'; // libelle de la permission
        $this->rights[7][2] = 'c'; // type de la permission (deprecie a ce jour)
        $this->rights[7][3] = 0; // La permission est-elle une permission par defaut
        $this->rights[7][4] = 'caImput';
  
        
        
        $this->tabs = array('task:+attribution:Attribution:@monmodule:$user->rights->projet->lire:/synopsisprojetplus/task/timeP.php?&withproject=1&id=__ID__',
            'project:+imputations:Imputations:@monmodule:$user->rights->' . $this->rights_class . '->attribution:/synopsisprojetplus/histo_imputations.php?id=__ID__');
    }

    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees a creer pour ce module.
     */
    function init() {
        // Permissions
        $this->remove();

        return $this->_init($sql);
    }

    /**
     *    \brief      Fonction appelee lors de la desactivation d'un module.
     *                Supprime de la base les constantes, boites et permissions du module.
     */
    function remove($option = '') {

        return $this->_remove($sql, $option);
    }

}

?>
