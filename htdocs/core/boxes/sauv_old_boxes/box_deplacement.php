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
 * $Id: box_deplacement.php,v 1.34 2008/05/30 07:06:37 ywarnier Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/core/boxes/box_deplacement.php,v $
 */

/**
    \file       htdocs/core/boxes/box_deplacement.php
    \ingroup    deplacement
    \brief      Module de generation de l'affichage de la box deplacement
*/

include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");


class box_deplacement extends ModeleBoxes {

    var $boxcode="lastdeplacement";
    var $boximg="object_ndf";
    var $boxlabel;
    var $depends = array("");

    var $db;
    var $param;

    var $info_box_head = array();
    var $info_box_contents = array();

    /**
     *      \brief      Constructeur de la classe
     */
    function box_deplacement()
    {
        global $langs;
        $langs->load("boxes");
        $langs->load("babel");

        $this->boxlabel=$langs->trans("BoxLastdeplacement");
    }

    /**
     *      \brief      Charge les donnees en memoire pour affichage ulterieur
     *      \param      $max        Nombre maximum d'enregistrements a charger
     */
    function loadBox($max=5)
    {
        global $user, $langs, $db;

        include_once(DOL_DOCUMENT_ROOT."/Babel_TechPeople/deplacements/deplacement.class.php");
        $deplacementtatic=new Ndf($db);

        $text = $langs->trans("BoxTitledeplacement",$max);
        $this->info_box_head = array(
                'text' => $text,
                'limit'=> strlen($text)
            );
//if ($user->rights->TechPeople->ndf->Affiche || $user->rights->TechPeople->ndf->Valid) || AfficheMien
        $sql = "";
        if ($user->rights->TechPeople->ndf->Valid || $user->rights->TechPeople->ndf->Affiche|| $user->rights->TechPeople->ndf->AfficheMien)
        {
            if ($user->rights->TechPeople->ndf->Valid || $user->rights->TechPeople->ndf->Affiche)
            {
                //on cherche les ndf de tous les monde qui sont en statut en cours
                $sql = "SELECT id FROM Babel_ndf WHERE statut = 2 order by periode desc ";
            } else if ($user->rights->TechPeople->ndf->AfficheMien) {
                //on cherche mes dernieres ndf
                $sql = "SELECT id FROM Babel_ndf WHERE fk_user_author=".$user->id." order by periode desc ";
            }
                $sql.= $db->plimit($max, 0);
//print $sql;
                if ($result = $db->query($sql))
                {
                    //$num = $db->num_rows();

                    $i = 0;
                    while ($objp = $db->fetch_object($result))
                    {
//print ('tt');
                        $deplacementtatic->fetch($objp->id);
                        $picto='trip';

//                        $this->info_box_contents[$i][0] = array('align' => 'left',
//                        'logo' => $picto,
//                        'text' => ($objp->ref?$objp->ref:"-"),
//                        'url' => DOL_URL_ROOT."/Babel_deplacement/deplacements/ficheNdf.php?id=".$deplacementtatic->id);

                        $tuser = new User($db);
                        $tuser->id=$deplacementtatic->fk_user_author;
                        $tuser->fetch();

                        $this->info_box_contents[$i][1] = array('align' => 'left',
                        'text' => $tuser->getNomUrl(1),
                        'maxlength'=>30,
                        'url' => DOL_URL_ROOT."/users/fiche.php?socid=".$deplacementtatic->fk_user_author);

                        $this->info_box_contents[$i][0] = array('align' => 'center', 'vertical-align' => 'center',
                        'text' => date('m/Y',$deplacementtatic->tsperiode),
                        'logo' => $picto,
                        'url' => DOL_URL_ROOT."/Babel_TechPeople/deplacements/ficheNdf.php?id=".$deplacementtatic->id
                        );

                        //print $deplacementtatic->statut;
                        $this->info_box_contents[$i][2] = array(
                        'align' => 'right',
                        'text' => $deplacementtatic->LibStatut($deplacementtatic->statut,5));

                        $i++;
                    }
$num=$i;
                    $i=$num;
                    while ($i < $max)
                    {
                        if ($num==0 && $i==$num)
                        {
                            $this->info_box_contents[$i][0] = array('align' => 'center','text'=>$langs->trans("Pas de ndf en cours"));
                            $this->info_box_contents[$i][1] = array('text'=>'&nbsp;');
                            $this->info_box_contents[$i][2] = array('text'=>'&nbsp;');
                            $this->info_box_contents[$i][3] = array('text'=>'&nbsp;');
                        } else {
                            $this->info_box_contents[$i][0] = array('text'=>'&nbsp;');
                            $this->info_box_contents[$i][1] = array('text'=>'&nbsp;');
                            $this->info_box_contents[$i][2] = array('text'=>'&nbsp;');
                            $this->info_box_contents[$i][3] = array('text'=>'&nbsp;');
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

    } else {
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
