<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 *
 * $Id: box_propales.php,v 1.34 2008/03/01 01:26:49 eldy Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/core/boxes/box_propales.php,v $
 */

/**
        \file       htdocs/core/boxes/box_propales.php
        \ingroup    propales
        \brief      Module de generation de l'affichage de la box propales
*/

include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");


class box_propales extends ModeleBoxes {

    var $boxcode="lastpropals";
    var $boximg="object_propal";
    var $boxlabel;
    var $depends = array("propale");

    var $db;
    var $param;

    var $info_box_head = array();
    var $info_box_contents = array();


    /**
     *      \brief      Constructeur de la classe
     */
    function box_propales()
    {
        global $langs;
        $langs->load("boxes");

        $this->boxlabel=$langs->trans("BoxLastProposals");
    }

    /**
     *      \brief      Charge les donnees en memoire pour affichage ulterieur
     *      \param      $max        Nombre maximum d'enregistrements a charger
     */
    function loadBox($max=5)
    {

        global $user, $langs, $db;

        include_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
        $propalstatic=new Propal($db);

        $this->info_box_head = array('text' => $langs->trans("BoxTitleLastPropals",$max));

        if ($user->rights->propale->lire)
        {

            $sql = "SELECT s.nom, s.rowid as socid,";
            $sql.= " p.rowid, p.ref, p.fk_statut, ".$db->pdate("p.datep")." as dp, p.datec";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
            $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."propal as p";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql .= " WHERE p.fk_soc = s.rowid";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
            if($user->societe_id)
            {
                $sql .= " AND s.rowid = ".$user->societe_id;
            }
            $sql .= " ORDER BY p.datep DESC, p.ref DESC ";
            $sql .= $db->plimit($max, 0);

            $result = $db->query($sql);

            if ($result)
            {
                $num = $db->num_rows();

                $i = 0;

                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);

                    $this->info_box_contents[$i][0] = array('align' => 'left',
                    'logo' => $this->boximg,
                    'text' => $objp->ref,
                    'url' => DOL_URL_ROOT."/comm/propal.php?propalid=".$objp->rowid);

                    $this->info_box_contents[$i][1] = array('align' => 'left',
                    'text' => dol_trunc($objp->nom,40),
                    'url' => DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->socid);

                    $this->info_box_contents[$i][2] = array(
                    'align' => 'right',
                    'text' => dolibarr_print_date($objp->datec,'day'));

                    $this->info_box_contents[$i][3] = array(
                    'align' => 'right',
                    'width' => 18,
                    'text' => $propalstatic->LibStatut($objp->fk_statut,3));

                    $i++;
                }

                $i=$num;
          while ($i < $max)
          {
            if ($num==0 && $i==$num)
            {
                $this->info_box_contents[$i][0] = array('align' => 'center','text'=>$langs->trans("NoRecordedProposals"));
                $this->info_box_contents[$i][1] = array('text'=>'&nbsp;');
                $this->info_box_contents[$i][2] = array('text'=>'&nbsp;');
                $this->info_box_contents[$i][3] = array('text'=>'&nbsp;');
                $this->info_box_contents[$i][4] = array('text'=>'&nbsp;');
            } else {
                $this->info_box_contents[$i][0] = array('text'=>'&nbsp;');
                $this->info_box_contents[$i][1] = array('text'=>'&nbsp;');
                $this->info_box_contents[$i][2] = array('text'=>'&nbsp;');
                $this->info_box_contents[$i][3] = array('text'=>'&nbsp;');
                $this->info_box_contents[$i][4] = array('text'=>'&nbsp;');
            }
            $i++;
          }
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
