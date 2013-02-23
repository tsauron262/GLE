<?php

/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.synopsis-erp.com
 *
 */
/*
 *
 * $Id: interface_modProcess_triggerForm.class.php,v 1.1 2008/01/06 20:33:49
 * hregis Exp $
 */

/**
  \file       htdocs/includes/triggers/interface_modProcess_triggerForm.class.php
  \ingroup    Babel
  \brief      Fichier de gestion des triggers Process
 */

/**
  \class      Interface
  \brief      Classe des fonctions triggers des actions Process
 */
class InterfaceLienOrangeHrm {

    public $db;
    public $error;
    public $date;
    public $duree;
    public $texte;
    public $desc;

    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'acces base
     */
    function InterfaceLienOrangeHrm($DB) {
        $this->db = $DB;

        $this->name = "Lien avec OrangeHrm";
        $this->family = "Synopsis";
        $this->description = "";
        $this->version = '1';                        // 'experimental' or 'dolibarr' or version
    }

    /**
     *   \brief      Renvoi nom du lot de triggers
     *   \return     string      Nom du lot de triggers
     */
    function getName() {
        return $this->name;
    }

    /**
     *   \brief      Renvoi descriptif du lot de triggers
     *   \return     string      Descriptif du lot de triggers
     */
    function getDesc() {
        return $this->description;
    }

    /**
     *   \brief      Renvoi version du lot de triggers
     *   \return     string      Version du lot de triggers
     */
    function getVersion() {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'experimental')
            return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr')
            return GLE_VERSION;
        elseif ($this->version)
            return $this->version;
        else
            return $langs->trans("Unknown");
    }

    /**
     *      \brief      Fonction appelee lors du declenchement d'un evenement Dolibarr.
     *                  D'autres fonctions run_trigger peuvent etre presentes dans includes/triggers
     *      \param      action      Code de l'evenement
     *      \param      object      Objet concerne
     *      \param      user        Objet user
     *      \param      lang        Objet lang
     *      \param      conf        Objet conf
     *      \return     int         <0 si ko, 0 si aucune action faite, >0 si ok
     */
    function run_trigger($action, $object, $user, $langs, $conf) {
        if ($action == 'USER_CREATE') {
            require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Hrm/hrm.class.php");
            $hrm = new hrm($db);
            $object->fetch($object->id);
            $hrm->create($object);
        }
        return 0;
    }

}