<?php
/* Copyright (C) 2005      Christophe
 * Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.*//*
  * GLE by DRSI & Synopsis
  *
  * Author: SAURON Tommy <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.synopsis-erp.com
  *
  *//*
 *
 * $Id: box_comptes.php,v 1.11 2008/07/31 16:07:41 eldy Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/core/boxes/box_comptes.php,v $
 */

/**
        \file       htdocs/core/boxes/box_comptes.php
        \ingroup    banque
        \brief      Module de generation de l'affichage de la box comptes
*/

include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");
include_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");


class box_comptes extends ModeleBoxes {

    var $boxcode="currentaccounts";
    var $boximg="object_bill";
    var $boxlabel;
    var $depends = array("banque");     // Box active si module banque actif

    var $db;
    var $param;

    var $info_box_head = array();
    var $info_box_contents = array();


    /**
    *      \brief      Constructeur de la classe
    */
    function box_comptes()
    {
        global $langs;
        $langs->load("boxes");

        $this->boxlabel=$langs->trans('BoxCurrentAccounts');
    }

    /**
    *      \brief      Charge les donnees en memoire pour affichage ulterieur
    *      \param      $max        Nombre maximum d'enregistrements a charger
    */
    function loadBox($max=5)
    {
        global $user, $langs, $db, $conf;

        $this->info_box_head = array('text' => $langs->trans("BoxTitleCurrentAccounts"));

        if ($user->rights->banque->lire)
        {
            $sql = "SELECT rowid, ref, label, bank, number, courant, clos, rappro, url,";
            $sql.= " code_banque, code_guichet, cle_rib, bic, iban_prefix,";
            $sql.= " domiciliation, proprio, adresse_proprio,";
            $sql.= " account_number, currency_code,";
            $sql.= " min_allowed, min_desired, comment";
            $sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
            $sql.= " WHERE clos = 0 AND courant = 1";
            $sql.= " ORDER BY label";
            $sql.= $db->plimit($max, 0);

            dol_syslog("Box_comptes::loadBox sql=".$sql);
            $result = $db->query($sql);
            if ($result)
            {
                $num = $db->num_rows($result);

                $i = 0;
                $solde_total = 0;

                $account_static = new Account($db);
                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);

                    $account_static->id = $objp->rowid;
                    $solde=$account_static->solde(0);

                    $solde_total += $solde;

                    $this->info_box_contents[$i][0] = array('align' => 'left',
                    'logo' => $this->boximg,
                    'text' => $objp->label,
                    'url' => DOL_URL_ROOT."/compta/bank/account.php?account=".$objp->rowid);

                    /*
                    $this->info_box_contents[$i][1] = array('align' => 'left',
                    'text' => $objp->bank
                    );
                    */

                    $this->info_box_contents[$i][1] = array('align' => 'left',
                    'text' => $objp->number
                    );

                    $this->info_box_contents[$i][2] = array('align' => 'right',
                    'text' => price($solde).' '.$langs->trans("Currency".$objp->currency_code)
                    );

                    $i++;
                }

                // Total
                $this->info_box_contents[$i][-1] = array('class' => 'liste_total');

                $this->info_box_contents[$i][0] = array('align' => 'right',
                //'width' => '75%',
                'colspan' => '3',
                'class' => 'liste_total',
                'text' => $langs->trans('Total')
                );

                $this->info_box_contents[$i][1] = array('align' => 'right',
                'class' => 'liste_total',
                'text' => price($solde_total).' '.$langs->trans("Currency".$conf->global->MAIN_MONNAIE)
                );

            }
            else {
                dolibarr_print_error($db);
            }
        }
        else {
            $this->info_box_contents[0][0] = array('align' => 'left',
            'text' => $langs->trans("ReadPermissionNotAllowed"));
        }

    }

    function showBox($head = null, $contents = null)
    {
        $html = parent::showBox($this->info_box_head, $this->info_box_contents);
        return ($html);
    }

}

?>
