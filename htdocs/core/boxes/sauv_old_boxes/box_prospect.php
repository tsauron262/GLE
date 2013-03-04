<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.*//*
  * GLE by Babel-Services
  *
  * Author: Jean-Marc LE FEVRE <jm.lefevre@babel-services.com>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.babel-services.com
  *
  *//*
 */

/**
    \file       htdocs/core/boxes/box_prospect.php
    \ingroup    societe
    \brief      Module to generate the last prospects box.
    \version    $Id: box_prospect.php,v 1.25 2008/04/04 10:42:31 eldy Exp $
*/


include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");
include_once(DOL_DOCUMENT_ROOT."/prospect.class.php");


class box_prospect extends ModeleBoxes {

    var $boxcode="lastprospects";
    var $boximg="object_company";
    var $boxlabel;
    var $depends = array("commercial");

    var $db;
    var $param;

    var $info_box_head = array();
    var $info_box_contents = array();

    /**
     *      \brief      Constructeur de la classe
     */
    function box_prospect()
    {
        global $langs;
        $langs->load("boxes");

        $this->boxlabel=$langs->trans("BoxLastProspects");
    }

    /**
     *      \brief      Charge les donnees en memoire pour affichage ulterieur
     *      \param      $max        Nombre maximum d'enregistrements a charger
     */
    function loadBox($max=5)
    {
        global $user, $langs, $db;

        $this->info_box_head = array('text' => $langs->trans("BoxTitleLastProspects",$max));

        if ($user->rights->societe->lire)
        {
            $sql = "SELECT s.nom, s.rowid as socid, s.fk_stcomm, ".$db->pdate("s.datec")." as dc";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
            $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql .= " WHERE s.client = 2";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
            if ($user->societe_id > 0)
            {
                $sql .= " AND s.rowid = ".$user->societe_id;
            }
            $sql .= " ORDER BY s.datec DESC";
            $sql .= $db->plimit($max, 0);

            dol_syslog("box_prospect::loadBox sql=".$sql,LOG_DEBUG);
            $resql = $db->query($sql);
            if ($resql)
            {
                $num = $db->num_rows($resql);

                $i = 0;
                $prospectstatic=new Prospect($db);
                while ($i < $num)
                {
                    $objp = $db->fetch_object($resql);

                    $this->info_box_contents[$i][0] = array('align' => 'left',
                    'logo' => $this->boximg,
                    'text' => stripslashes($objp->nom),
                    'url' => DOL_URL_ROOT."/comm/prospect/fiche.php?socid=".$objp->socid);

                    $this->info_box_contents[$i][1] = array('align' => 'right',
                    'text' => dol_print_date($objp->dc, "day"));

                    $this->info_box_contents[$i][2] = array('align' => 'right',
                    'width' => 18,
                    'text' => preg_replace('/img /i','img height="14px" ',$prospectstatic->LibStatut($objp->fk_stcomm,3)));

                    $i++;
                }

                $i=$num;
                while ($i < $max)
                {
                    if ($num==0 && $i==$num)
                    {
                        $this->info_box_contents[$i][0] = array('align' => 'center','text'=>$langs->trans("NoRecordedProspects"));
                        $this->info_box_contents[$i][1] = array('text'=>'&nbsp;');
                        $this->info_box_contents[$i][2] = array('text'=>'&nbsp;');
                        $this->info_box_contents[$i][3] = array('text'=>'&nbsp;');
                    } else {
                        //$this->info_box_contents[$i][0] = array('text'=>'&nbsp;');
                        //$this->info_box_contents[$i][1] = array('text'=>'&nbsp;');
                        //$this->info_box_contents[$i][2] = array('text'=>'&nbsp;');
                        //$this->info_box_contents[$i][3] = array('text'=>'&nbsp;');
                    }
                    $i++;
                }
            }
            else
            {
                dolibarr_print_error($db);
            }
        }
        else {
            dol_syslog("box_prospect::loadBox not allowed de read this box content",LOG_ERR);
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
