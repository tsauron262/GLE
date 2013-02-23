<?php

/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * $Id: task.class.php,v 1.1 2005/08/21 12:39:46 rodolphe Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/task.class.php,v $
 *
 */
/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
  \file       htdocs/task.class.php
  \ingroup    projet
  \brief      Fichier de la classe de gestion des taches
  \version    $Revision: 1.1 $
 */
/**
  \class      Task
  \brief      Classe permettant la gestion des taches
 */
require_once(DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");

/**
  \class      Project
  \brief      Classe permettant la gestion des projets
 */
class Task extends CommonObject {

    var $id;
    var $db;

    /**
     *    \brief  Constructeur de la classe
     *    \param  DB          handler acc�s base de donn�es
     */
    function Task($DB) {
        $this->db = $DB;
    }

    function getNomUrl($withpicto = 0, $option = '', $maxlen = 0) {
        if(isset($this->projet))
            return $this->projet->getTaskNomUrl($this->id, $withpicto, $option, $maxlen, $this->title);
        else
            die("Projet non initialiser");
    }

    /*
     *    \brief      Charge objet projet depuis la base
     *    \param      rowid       id du projet � charger
     */

    function fetch($rowid) {

        $sql = "SELECT title, fk_projet, duration_effective, statut";
        $sql .= " FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task";
        $sql .= " WHERE rowid=" . $rowid;

        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $rowid;
                $this->title = $obj->title;
                $this->statut = $obj->statut;
                $this->projet_id = $obj->fk_projet;

                $this->db->free($resql);


                $this->projet = new Project($this->db);
                $this->projet->fetch($this->projet_id);
                $this->ref = $this->projet->ref."-".$this->id;

                return 1;
            } else {
                return -1;
            }
        } else {
            print $this->db->error();
            return -2;
        }
    }

}

?>
