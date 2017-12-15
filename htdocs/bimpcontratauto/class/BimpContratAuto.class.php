<?php

/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2013 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file       htdocs/bimpcontratauto/class/BimpcontratAuto.class.php
 * 	\ingroup    bimpcontratauto
 * 	\brief      Chose 
 */
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobjectline.class.php';


class BimpContratAuto extends CommonObject {

    /**
     * 	Constructor
     *
     *  @param		DoliDB		$db     Database handler
     */
    function __construct($db) {
        $this->db = $db;
    }

    function create() {
        dol_syslog(get_class($this) . '::create', LOG_DEBUG);
    }
}



/*
Nom : bimpcontratauto

But :
Dans un client un new onglet qui affichera le résumé des contrat en cours + possibilité de créer un contrat a la volé.

Resumé du ou des contrat en cours.

Total facturé
Total payé (peu être différent)
Total restant
Total Contrat

Création du contrat :
Un peut comme dans le module précédent plusieurs choix possible par service :

•CTR-ASSITANCE
- Non
- 12 moi
- 24 moi
- 36 moi

CTR-PNEUMATIQUE
- Non
- 12 moi
- 24 moi
- 36 moi

CTR-MAINTENANCE

CTR_EXTENSION

Blyyd Connect

Puis un formulaire avec date de début
Et un boutons créer.
$tabProd = array(
                    array("id"=>234, "name"=> "Maintenance", "Values"=>array("Non", 12,24)),
                    array("id"=>235, "name"=> "Extenssion", "Values"=>array("Non", 12,24)));
 */