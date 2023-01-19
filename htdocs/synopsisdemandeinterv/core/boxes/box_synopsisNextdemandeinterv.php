<?php

/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * BIMP-ERP by DRSI & Synopsis
 *
 * Author: SAURON Tommy <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.synopsis-erp.com
 *
 */
/*
 *
 * $Id: box_ficheInterv.php,v 1.34 2008/05/30 07:06:37 ywarnier Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/core/boxes/box_ficheInterv.php,v $
 */

/**
  \file       htdocs/core/boxes/box_ficheInterv.php
  \ingroup    ficheInterv
  \brief      Module de generation de l'affichage de la box ficheInterv
 */
include_once(DOL_DOCUMENT_ROOT . "/core/boxes/modules_boxes.php");

class box_synopsisNextdemandeinterv extends ModeleBoxes {

    public $boxcode = "lastficheInterv";
    public $boximg = "object_fichinter";
    public $boxlabel = "BoxNextsynopsisdemandeinterv";
    public $depends = array("synopsisdemandeinterv");
    public $db;
    public $param;
    public $info_box_head = array();
    public $info_box_contents = array();

    /**
     *      \brief      Constructeur de la classe
     */
    function box_synopsisNextdemandeinterv() {
        global $langs;
        $langs->load("boxes");

        $this->boxlabel = $langs->trans("BoxNextsynopsisdemandeinterv");
    }

    /**
     *      \brief      Charge les donnees en memoire pour affichage ulterieur
     *      \param      $max        Nombre maximum d'enregistrements a charger
     */
    function loadBox($max = 5) {
        global $user, $langs, $db;

        include_once(DOL_DOCUMENT_ROOT . "/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php");
        $DIStatic = new Synopsisdemandeinterv($db);

        $text = $langs->trans("BoxNextsynopsisdemandeinterv", $max);
        $this->info_box_head = array(
            'text' => "" . $text,
            'limit' => strlen($text)
        );

        if ($user->rights->ficheinter->lire) {
            $sql = "SELECT DI.rowid as diid,
                           DI.ref,
                           DI.fk_commande,
                           c.ref as cref,
                           concat(tech.firstname, concat(' ', tech.lastname)) as nomTech,
                           " . MAIN_DB_PREFIX . "societe.nom,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid,
                           DI.datei,
                           DI.fk_statut
                      FROM " . MAIN_DB_PREFIX . "synopsisdemandeinterv DI
                 LEFT JOIN " . MAIN_DB_PREFIX . "societe ON DI.fk_soc = " . MAIN_DB_PREFIX . "societe.rowid
                 LEFT JOIN " . MAIN_DB_PREFIX . "commande c ON DI.fk_commande = c.rowid
                 LEFT JOIN " . MAIN_DB_PREFIX . "user tech ON DI.fk_user_prisencharge = tech.rowid
                     WHERE DI.fk_statut < 3
                     AND (DI.fk_user_author = " . $user->id . "
                        OR DI.fk_user_valid = " . $user->id . "
                        OR DI.fk_user_prisencharge = " . $user->id . " OR 1)
                            AND DI.rowid not in (SELECT fk_source FROM " . MAIN_DB_PREFIX . "element_element DIFI WHERE DIFI.sourcetype = 'DI' AND DIFI.targettype='FI')";
            if (isset($_REQUEST['lien_commande']))
                $sql .= " AND DI.fk_commande IS NOT NULL ";
            $sql .= "ORDER BY datei asc";
            $sql.= $db->plimit($max, 0);

            $result = $db->query($sql);

            if ($result) {
                $num = $db->num_rows($result);

                $i = 0;
                $l_due_date = $langs->trans('Late') . ' (' . strtolower($langs->trans('DateEcheance')) . ': %s)';

                while ($i < $num) {
                    $objp = $db->fetch_object($result);

                    $picto = 'synopsisdemandeinterv@synopsisdemandeinterv';

                    $this->info_box_contents[$i][0] = array('align' => 'left',
                        'logo' => $picto,
                        'text' => ($objp->ref ? $objp->ref : "-"),
                        'url' => DOL_URL_ROOT . "/synopsisdemandeinterv/card.php?id=" . $objp->diid);

                    $this->info_box_contents[$i][1] = array('align' => 'left',
                        'text' => $objp->nom,
                        'maxlength' => 30,
                        'url' => DOL_URL_ROOT . "/comm/card.php?socid=" . $objp->socid);

                    $this->info_box_contents[$i][2] = array('align' => 'right',
                        'text' => dol_print_date(strtotime($objp->datei), 'day'),
                    );

                    $this->info_box_contents[$i][3] = array(
                        'align' => 'right',
                        'width' => 10,
                        'text' => $DIStatic->LibStatut($objp->fk_statut, 3));


                    $this->info_box_contents[$i][4] = array(
                        'logo' => 'user',
                        'align' => 'right',
                        'width' => 18,
                        'text' => $objp->nomTech,
                        'url' => DOL_URL_ROOT . "/user/card.php?id=" . $objp->fk_user_prisencharge);


                    if (isset($_REQUEST['lien_commande'])) {
                        $this->info_box_contents[$i][5] = array(
                            'logo' => 'contract',
                            'align' => 'right',
                            'width' => 18,
                            'text' => $objp->cref,
                            'url' => DOL_URL_ROOT . "/commande/card.php?id=" . $objp->fk_commande);
                    }

                    $i++;
                }

                $i = $num;
                while ($i < $max) {
                    if ($num == 0 && $i == $num) {
                        $this->info_box_contents[$i][0] = array('align' => 'center', 'text' => $langs->trans("Pas de fiche en cours"));
                        $this->info_box_contents[$i][1] = array('text' => '&nbsp;');
                        $this->info_box_contents[$i][2] = array('text' => '&nbsp;');
                        $this->info_box_contents[$i][3] = array('text' => '&nbsp;');
                        $this->info_box_contents[$i][4] = array('text' => '&nbsp;');
                        $this->info_box_contents[$i][5] = array('text' => '&nbsp;');
                    } else {
                        $this->info_box_contents[$i][0] = array('text' => '&nbsp;');
                        $this->info_box_contents[$i][1] = array('text' => '&nbsp;');
                        $this->info_box_contents[$i][2] = array('text' => '&nbsp;');
                        $this->info_box_contents[$i][3] = array('text' => '&nbsp;');
                        $this->info_box_contents[$i][4] = array('text' => '&nbsp;');
                        $this->info_box_contents[$i][5] = array('text' => '&nbsp;');
                        if (isset($_REQUEST['lien_commande']))
                            $this->info_box_contents[$i][6] = array('text' => '&nbsp;');
                    }
                    $i++;
                }
            } else {
                $this->info_box_contents[0][0] = array('align' => 'left',
                    'maxlength' => 500,
                    'text' => ($db->error() . ' sql=' . $sql));
            }
        } else {
            $this->info_box_contents[0][0] = array('align' => 'left',
                'text' => $langs->trans("ReadPermissionNotAllowed"));
        }
    }

    function showBox($head = null, $contents = null, $nooutput = 0) {
        $html = parent::showBox($this->info_box_head, $this->info_box_contents);
        return ($html);
    }

}

?>
