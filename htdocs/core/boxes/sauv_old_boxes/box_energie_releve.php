<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * $Id: box_energie_releve.php,v 1.2 2006/06/18 01:34:16 eldy Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/core/boxes/box_energie_releve.php,v $
 *
 */

include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");
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
class box_energie_releve extends ModeleBoxes {

    var $boxcode="energie";
    var $boximg="object_energie";
    var $boxlabel;
    var $depends = array("energie");

    var $db;
    var $param;

    var $info_box_head = array();
    var $info_box_contents = array();

    /**
     *      \brief      Constructeur de la classe
     */
    function box_energie_releve()
    {
        global $langs;
        $langs->load("boxes");

        $this->boxlabel=$langs->trans("Energie");
    }

    /**
     *      \brief      Charge les donnees en memoire pour affichage ulterieur
     *      \param      $max        Nombre maximum d'enregistrements a charger
     */
    function loadBox($max=5)
    {
      global $user, $langs, $db;
      $langs->load("boxes");

      $text = '<a href="energie/">'.$langs->trans("Energie").'</a>';

      $this->info_box_head = array('text' => $text,$max);


      $sql = "SELECT ec.libelle, ".$db->pdate("date_releve")." as date_releve, ecr.valeur, ec.rowid";
      $sql .= " FROM ".MAIN_DB_PREFIX."energie_compteur_releve as ecr";
      $sql .= " , ".MAIN_DB_PREFIX."energie_compteur as ec";
      $sql .= " WHERE ecr.fk_compteur = ec.rowid";
      $sql .= " ORDER BY ecr.date_releve DESC LIMIT 5";
      $resql = $db->query($sql);
      if ($resql)
    {
    $num = $db->num_rows($resql);
    $i = 0;
    $var=True;
    while ($i < $num)
        {
        $objp = $db->fetch_object($resql);

        $this->info_box_contents[$i][0] = array('align' => 'left',
                            'logo' => $this->boximg,
                            'text' => $objp->libelle,
                            'url' => DOL_URL_ROOT."/energie/compteur.php?id=".$objp->rowid);

        $this->info_box_contents[$i][1] = array('align' => 'left',
                            'text' => $objp->valeur);


        $i++;
        }
    }
    }

    function showBox($head = null, $contents = null)
    {
        $html = parent::showBox($this->info_box_head, $this->info_box_contents);
        return ($html);
    }
}

?>
