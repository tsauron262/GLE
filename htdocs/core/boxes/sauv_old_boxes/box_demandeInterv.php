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
  */
/*
 *
 * $Id: box_demandeInterv.php,v 1.34 2008/05/30 07:06:37 ywarnier Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/core/boxes/box_demandeInterv.php,v $
 */

/**
    \file       htdocs/core/boxes/box_demandeInterv.php
    \ingroup    demandeInterv
    \brief      Module de generation de l'affichage de la box demandeInterv
*/

include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");


class box_demandeInterv extends ModeleBoxes {

    public $boxcode="lastdemandeInterv";
    public $boximg="object_intervention";
    public $boxlabel;
    public $depends = array("");

    public $db;
    public $param;

    public $info_box_head = array();
    public $info_box_contents = array();

    /**
     *      \brief      Constructeur de la classe
     */
    function box_demandeInterv()
    {
        global $langs;
        $langs->load("boxes");

        $this->boxlabel=$langs->trans("BoxLastDemandeInterv");
    }

    /**
     *      \brief      Charge les donnees en memoire pour affichage ulterieur
     *      \param      $max        Nombre maximum d'enregistrements a charger
     */
    function loadBox($max=5)
    {
        global $user, $langs, $db;

        include_once(DOL_DOCUMENT_ROOT."/Babel_demandeInterv/demandeInterv.class.php");
        $demandeIntervtatic=new demandeInterv($db);

        $text = html_entity_decode($langs->trans("BoxTitleDemandeInterv",$max));
        $this->info_box_head = array(
                'text' => $text,
                'limit'=> strlen($text)
            );

        if ($user->rights->demandeInterv->lire)
        {
            $sql = "SELECT Babel_demandeInterv.rowid as diid,
                           Babel_demandeInterv.ref,
                           ".MAIN_DB_PREFIX."societe.nom,
                           ".MAIN_DB_PREFIX."societe.rowid as socid,
                           Babel_demandeInterv.datec,
                           Babel_demandeInterv.fk_statut
                      FROM Babel_demandeInterv
                 LEFT JOIN ".MAIN_DB_PREFIX."societe ON Babel_demandeInterv.fk_soc = ".MAIN_DB_PREFIX."societe.rowid
                     WHERE (Babel_demandeInterv.fk_statut < 3
                           AND (Babel_demandeInterv.fk_user_author = ".$user->id." OR Babel_demandeInterv.fk_user_prisencharge = ".$user->id."  OR Babel_demandeInterv.fk_user_cloture = ".$user->id."  OR Babel_demandeInterv.fk_user_valid = ".$user->id." OR Babel_demandeInterv.fk_user_target = ".$user->id." )
                           )
                    ORDER BY datei desc";
            $sql.= $db->plimit($max, 0);

            $result = $db->query($sql);

            if ($result)
            {
                $num = $db->num_rows();

                $i = 0;
                $l_due_date = $langs->trans('Late').' ('.strtolower($langs->trans('DateEcheance')).': %s)';

                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);

                    $picto='intervention';

                    $this->info_box_contents[$i][0] = array('align' => 'left',
                    'logo' => $picto,
                    'text' => ($objp->ref?$objp->ref:"-"),
                    'url' => DOL_URL_ROOT."/Babel_demandeInterv/fiche.php?id=".$objp->diid);

                    $this->info_box_contents[$i][1] = array('align' => 'left',
                    'text' => $objp->nom,
                    'maxlength'=>40,
                    'url' => DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->socid);

                    $this->info_box_contents[$i][2] = array('align' => 'right',
                    'text' => dol_print_date($objp->datec,'day'),
                    );

                    $this->info_box_contents[$i][3] = array(
                    'align' => 'right',
                    'width' => 18,
                    'text' => $demandeIntervtatic->LibStatut($objp->fk_statut,3));

                    $i++;
                }

                $i=$num;
                while ($i < $max)
                {
                    if ($num==0 && $i==$num)
                    {
                        $this->info_box_contents[$i][0] = array('align' => 'center','text'=>$langs->trans("Pas de demande en cours"));
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
            else
            {
                $this->info_box_contents[0][0] = array(    'align' => 'left',
                                                        'maxlength'=>500,
                                                        'text' => ($db->error().' sql='.$sql));
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
