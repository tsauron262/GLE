<?php

/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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

 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */
/*
 */
/*
 */

/**
  \file       htdocs/synopsisproject.class.php
  \ingroup    projet
  \brief      Fichier de la classe de gestion des projets
  \version    $Id: synopsisproject.class.php,v 1.27.2.1 2008/09/10 09:47:51 eldy Exp $
 */
require_once(DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

/**
  \class      Project
  \brief      Classe permettant la gestion des projets
 */
class SynopsisProject extends CommonObject {

    public $id;
    public $db;
    public $ref;
    public $title;
    public $socid;
    public $user_resp_id;
    public $cost = array();
    public $costByDay = array();
    public $CostRHByUser = array();
    public $CostRHByTaskByUser = array();
    public $CostRHByUserByTask = array();
    public $element = 'synopsisprojet';
    public $table_element = 'Synopsis_projet';  //!< Name of table without prefix where object is stored
    public $table_element_line = 'Synopsis_projet_task';
    public $fk_element = 'fk_projet';
    protected $ismultientitymanaged = 1;  // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe


    /**
     *    \brief  Constructeur de la classe
     *    \param  DB          handler acces base de donnees
     */
    function getProjectsAuthorizedForUser($user, $mode=0, $list=0, $socid=0)
    {
        $projects = array();
        $temp = array();

        $sql = "SELECT DISTINCT p.rowid, p.ref";
        $sql.= " FROM " . MAIN_DB_PREFIX . "projet as p";
        if ($mode == 0 || $mode == 1)
        {
            $sql.= ", " . MAIN_DB_PREFIX . "element_contact as ec";
            $sql.= ", " . MAIN_DB_PREFIX . "c_type_contact as ctc";
        }
        $sql.= " WHERE p.entity IN (".getEntity('project').")";
        // Internal users must see project he is contact to even if project linked to a third party he can't see.
        //if ($socid || ! $user->rights->societe->client->voir)	$sql.= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
        if ($socid > 0) $sql.= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = " . $socid . ")";

        if ($mode == 0)
        {
            $sql.= " AND ec.element_id = p.rowid";
            $sql.= " AND ( p.public = 1";
            //$sql.= " OR p.fk_user_creat = ".$user->id;
            $sql.= " OR ( ctc.rowid = ec.fk_c_type_contact";
            $sql.= " AND ctc.element = '" . $this->element . "'";
            $sql.= " AND ( (ctc.source = 'internal' AND ec.fk_socpeople = ".$user->id.")";
            //$sql.= " OR (ctc.source = 'external' AND ec.fk_socpeople = ".($user->contact_id?$user->contact_id:0).")"; // Permission are supported on users only. To have an external thirdparty contact to see a project, its user must allowed to contacts of projects.
            $sql.= " )";
            $sql.= " ))";
        }
        if ($mode == 1)
        {
            $sql.= " AND ec.element_id = p.rowid";
            $sql.= " AND ctc.rowid = ec.fk_c_type_contact";
            $sql.= " AND ctc.element = '" . $this->element . "'";
            $sql.= " AND ( (ctc.source = 'internal' AND ec.fk_socpeople = ".$user->id.")";
            //$sql.= " OR (ctc.source = 'external' AND ec.fk_socpeople = ".($user->contact_id?$user->contact_id:0).")"; // Permission are supported on users only. To have an external thirdparty contact to see a project, its user must allowed to contacts of projects.
            $sql.= " )";
        }
        if ($mode == 2)
        {
            // No filter. Use this if user has permission to see all project
        }
        //print $sql;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $row = $this->db->fetch_row($resql);
                $projects[$row[0]] = $row[1];
                $temp[] = $row[0];
                $i++;
            }

            $this->db->free($resql);

            if ($list)
            {
                if (empty($temp)) return 0;
                $result = implode(',', $temp);
                return $result;
            }
        }
        else
        {
            dol_print_error($this->db);
        }

        return $projects;
    }
    
    public function __construct($DB) {
        $this->db = $DB;
        $this->societe = new Societe($DB);
        global $langs;
        $langs->load("synopsisproject@synopsisprojet");
        $this->labelstatut[0] = $langs->trans("ProjectStatusDraft");
        $this->labelstatut[5] = $langs->trans("ProjectStatusPlanning");
        $this->labelstatut[10] = $langs->trans("ProjectStatusRunning");
        $this->labelstatut[50] = $langs->trans("ProjectStatusClose");
        $this->labelstatut[999] = $langs->trans("ProjectStatusWaitingValidation");
        $this->labelstatut_short[0] = $langs->trans("ProjectStatusDraftShort");
        $this->labelstatut_short[5] = $langs->trans("ProjectStatusPlanningShort");
        $this->labelstatut_short[10] = $langs->trans("ProjectStatusRunningShort");
        $this->labelstatut_short[50] = $langs->trans("ProjectStatusCloseShort");
        $this->labelstatut_short[999] = $langs->trans("ProjectStatusWaitingValidationShort");
    }

    /*
     *    \brief      Cree un projet en base
     *    \param      user        Id utilisateur qui cree
     *    \return     int         <0 si ko, id du projet cree si ok
     */

    public function create($user) {
        $error = 0;
        global $langs, $conf;
        // Check parameters
        if (!trim($this->ref)) {
            $this->error = 'ErrorFieldsRequired';
            dol_syslog("Project::Create error -1 ref null");
            return -1;
        }

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_projet (ref, title, fk_soc, fk_user_creat, fk_user_resp, dateo, fk_statut,fk_type_projet,date_create)";
        $sql.= " VALUES ('" . addslashes($this->ref) . "', '" . addslashes($this->title) . "',";
        $sql.= " " . ($this->socid > 0 ? $this->socid : "null") . ",";
        $sql.= " " . $user->id . ",";
        $sql.= " " . $this->user_resp_id . ", " . $this->db->idate(time()) . ", 0," . ($this->type_id ? $this->type_id : "NULL") . ",now())";

        dol_syslog("Project::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id("" . MAIN_DB_PREFIX . "Synopsis_projet");
            $result = $this->id;
            if ($result > 0) {
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $resulta = $interface->run_triggers('PROJECT_CREATE', $this, $user, $langs, $conf);
                if ($resulta < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $resultb = $interface->run_triggers('PROJECT_CREATE_PROJADMIN', $this, $user, $langs, $conf);
                if ($resultb < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers
            } else {
                dol_syslog("Project::Create error -2");
                $this->error = $this->db->error();
                $result = -2;
            }
        } else {
            dol_syslog("Project::Create error -2");
            $this->error = $this->db->error();
            $result = -2;
        }
        if ($error > 0) {
            return (-2);
        } else {
            return $result;
        }
    }

    public function update($user) {
        global $langs, $conf;
        if (strlen(trim($this->ref)) > 0) {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_projet";
            $sql.= " SET ref='" . $this->ref . "'";
            $sql.= ", title = '" . $this->title . "'";
            $sql.= ", fk_soc = " . ($this->socid > 0 ? $this->socid : "null");
            $sql.= ", fk_user_resp = " . $this->user_resp_id;
            $sql.= " WHERE rowid = " . $this->id;

            dol_syslog("Project::update sql=" . $sql, LOG_DEBUG);
            if ($this->db->query($sql)) {
                $result = 0;
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('PROJECT_UPDATE', $this, $user, $langs, $conf);
                if ($result < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('PROJECT_MOD_PROJADMIN', $this, $user, $langs, $conf);
                if ($result < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers
            } else {
                dol_syslog($this->db->error());
                $result = -2;
            }
        } else {
            dol_syslog("Project::Update ref null");
            $result = -1;
        }

        return $result;
    }

    /*
     *    \brief      Charge objet projet depuis la base
     *    \param      rowid       id du projet a charger
     */

    public $task = array();
    public $dateo = '';

    public function valid() {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_projet SET date_valid=now(), fk_statut = 5 WHERE rowid = " . $this->id;
        $sql = $this->db->query($requete);
        if ($sql)
            return(1);
        else {
            $this->error = $db->lastqueryerror . '<br/>' . $this->db->lasterror;
            return(-1);
        }
    }

    public function launch() {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_projet SET date_launch=now(), fk_statut = 10 WHERE rowid = " . $this->id;
        $sql = $this->db->query($requete);
        if ($sql)
            return(1);
        else {
            $this->error = $db->lastqueryerror . '<br/>' . $this->db->lasterror;
            return(-1);
        }
    }

    public function info() {
        $requete = "SELECT *  FROM " . MAIN_DB_PREFIX . "Synopsis_projet WHERE rowid = " . $this->id;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $this->dateo = $res->dateo;
        $this->date_create = $res->date_create;
        $this->date_valid = $res->date_valid;
        $this->date_launch = $res->date_launch;
        $this->date_cloture = $res->date_cloture;
        $this->fk_user_resp = $res->fk_user_resp;
        $this->fk_user_creat = $res->fk_user_creat;
        $tmpUser = new User($this->db);
        $tmpUser->fetch($this->fk_user_resp);
        $this->user_resp = $tmpUser;
        $tmpUser->fetch($this->fk_user_creat);
        $this->user_creat = $tmpUser;
    }

    public function cloture() {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_projet SET date_cloture=now(),  fk_statut = 50 WHERE rowid = " . $this->id;
        $sql = $this->db->query($requete);
        if ($sql)
            return(1);
        else {
            $this->error = $db->lastqueryerror . '<br/>' . $this->db->lasterror;
            return(-1);
        }
    }

    public function fetch($rowid) {

        $sql = "SELECT title,
                       ref,
                       fk_soc,
                       fk_user_creat,
                       fk_user_resp,
                       fk_statut,
                       note,
                       dateo,
                       fk_type_projet,
                       type,
                       refAddOn,
                       hasGantt,
                       hasCout,
                       hasRH,
                       hasRessources,
                       hasRisque,
                       hasImputation,
                       hasPointage,
                       hasAgenda,
                       hasDocuments,
                       hasStats,
                       hasReferent,
                       hasTache,
                       hasTacheLight
                  FROM " . MAIN_DB_PREFIX . "Synopsis_projet,
                       " . MAIN_DB_PREFIX . "Synopsis_projet_type";
        $sql.= " WHERE rowid=" . $rowid . "
                   AND " . MAIN_DB_PREFIX . "Synopsis_projet_type.id = " . MAIN_DB_PREFIX . "Synopsis_projet.fk_type_projet";
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $rowid;
                $this->ref = $obj->ref;
                $this->title = ($obj->title != '' ? $obj->title : $obj->ref);
                $this->titre = $obj->title;
                $this->note = $obj->note;
                $this->type_id = $obj->fk_type_projet;
                $this->type = $obj->type;
                $this->type_inRef = ($obj->refAddOn . "x" != "x" ? $obj->refAddOn : "");
                $this->socid = $obj->fk_soc;
                $tmpSoc = new Societe($this->db);
                $tmpSoc->fetch($obj->fk_soc);
                $this->societe = $tmpSoc;
                $this->user_author_id = $obj->fk_user_creat;
                $this->user_resp_id = $obj->fk_user_resp;
                $this->statut = $obj->fk_statut;
                $this->dateo = $obj->dateo;

                $this->hasTache = $obj->hasTache;
                $this->hasTacheLight = $obj->hasTacheLight;
                $this->hasGantt = $obj->hasGantt;
                $this->hasCout = $obj->hasCout;
                $this->hasRH = $obj->hasRH;
                $this->hasRessources = $obj->hasRessources;
                $this->hasRisque = $obj->hasRisque;
                $this->hasImputation = $obj->hasImputation;
                $this->hasPointage = $obj->hasPointage;
                $this->hasAgenda = $obj->hasAgenda;
                $this->hasDocuments = $obj->hasDocuments;
                $this->hasStats = $obj->hasStats;
                $this->hasReferents = (isset($obj->hasReferent) ? $obj->hasReferent : null);


                $this->tasks = array();

                $requete1 = "SELECT " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid as tid,
                                    task_date as dateDeb,
                                    " . MAIN_DB_PREFIX . "Synopsis_projet_task.statut as tstatut,
                                    " . MAIN_DB_PREFIX . "Synopsis_projet_task.title as title,
                                    date_add(task_date , INTERVAL task_duration second) as dateFin
                               FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task
                          LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_projet_task_time ON " . MAIN_DB_PREFIX . "Synopsis_projet_task_time.fk_task = " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid
                              WHERE fk_projet = " . $this->id . "
                           ORDER BY " . MAIN_DB_PREFIX . "Synopsis_projet_task_time.task_date DESC, title ASC";
                $resql1 = $this->db->query($requete1);
                if ($resql1) {
                    if ($this->db->num_rows($resql1)) {
                        $iter = 0;
                        while ($res1 = $this->db->fetch_object($resql1)) {
                            if ($iter == 0) {
                                $this->first_task = $res1->tid;
                                $this->first_task_begin = $res1->dateDeb;
                                $this->first_task_end = $res1->dateFin;
                            }
                            $this->tasks[$res1->tid]['id'] = $res1->tid;
                            $this->tasks[$res1->tid]['dateDeb'] = $res1->dateDeb;
                            $this->tasks[$res1->tid]['dateFin'] = $res1->dateFin;
                            $this->tasks[$res1->tid]['statut'] = $res1->tstatut;
                            $this->tasks[$res1->tid]['title'] = $res1->title;


                            $iter++;
                        }
                    }
                }
                //last Task
                $requete2 = "SELECT " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid as tid,
                                    task_date as dateDeb,
                                    " . MAIN_DB_PREFIX . "Synopsis_projet_task.statut as tstatut,
                                    date_add(task_date , INTERVAL task_duration second) as dateFin
                               FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task
                          LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_projet_task_time ON " . MAIN_DB_PREFIX . "Synopsis_projet_task_time.fk_task = " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid
                              WHERE fk_projet = " . $this->id . "
                           ORDER BY date_add(task_date , INTERVAL task_duration second) DESC LIMIT 1";
                ;
                $resql2 = $this->db->query($requete2);
                if ($resql2) {
                    if ($this->db->num_rows($resql2)) {
                        $res2 = $this->db->fetch_object($resql2);
                        $this->last_task = $res2->tid;
                        $this->last_task_begin = $res2->dateDeb;
                        $this->last_task_end = $res2->dateFin;
                    }
                }
                //Actors
                $requete3 = "SELECT " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid as tid,
                                    " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.fk_user as userid,
                                    role
                               FROM " . MAIN_DB_PREFIX . "user, " . MAIN_DB_PREFIX . "Synopsis_projet_task
                          LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors ON " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.fk_projet_task = " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid
                              WHERE fk_projet = " . $this->id . "
                                AND " . MAIN_DB_PREFIX . "user.rowid = " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.fk_user
                           ORDER BY role";
                $resql3 = $this->db->query($requete3);
                if ($resql3) {
                    if ($this->db->num_rows($resql3)) {
                        $iter = 0;
                        while ($res3 = $this->db->fetch_object($resql3)) {
                            $tmpUsr = new User($this->db);
                            $tmpUsr->fetch($res3->userid);

                            $this->tasks[$res3->tid]['acto'][$res3->role][$iter]['userid'] = $res3->userid;
                            $this->tasks[$res3->tid]['acto'][$res3->role][$iter]['userobj'] = $tmpUsr;
                            $iter++;
                        }
                    }
                }

                $this->db->free($resql);

                return true;
            } else {
                return -1;
            }
        } else {
            print $this->db->error();
            return -2;
        }
    }

    /*
     *
     *
     *
     */

    public function liste_array($id_societe = '') {
        $projets = array();

        $sql = "SELECT rowid, title FROM " . MAIN_DB_PREFIX . "Synopsis_projet";

        if (isset($id_societe)) {
            $sql .= " WHERE fk_soc = $id_societe";
        }

        if ($this->db->query($sql)) {
            $nump = $this->db->num_rows();

            if ($nump) {
                $i = 0;
                while ($i < $nump) {
                    $obj = $this->db->fetch_object();

                    $projets[$obj->rowid] = $obj->title;
                    $i++;
                }
            }
            return $projets;
        } else {
            print $this->db->error();
        }
    }

    /**
     *     \brief        Return list of elements for type linked to project
     *    \param        type        'propal','order','invoice','order_supplier','invoice_supplier'
     *    \return        array        List of orders linked to project, <0 if error
     */
    public function get_element_list($type) {
        $elements = array();

        $sql = '';
        if ($type == 'propal')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "propal WHERE fk_projet=" . $this->id;
        if ($type == 'order')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "commande WHERE fk_projet=" . $this->id;
        if ($type == 'invoice')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "facture WHERE fk_projet=" . $this->id;
        if ($type == 'order_supplier')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "commande_fournisseur WHERE fk_projet=" . $this->id;
        if ($type == 'invoice_supplier')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "facture_fourn WHERE fk_projet=" . $this->id;
        if ($type == 'synopsischrono')
            $sql = "SELECT id as rowid FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE projetId=" . $this->id;


        if (!$sql)
            return -1;

        dol_syslog("Project::get_element_list sql=" . $sql);
        $result = $this->db->query($sql);
        if ($result) {
            $nump = $this->db->num_rows($result);
//            if ($nump) {
                $i = 0;
                while ($i < $nump) {
                    $obj = $this->db->fetch_object($result);

                    $elements[$i] = $obj->rowid;

                    $i++;
                }
                $this->db->free($result);

                /* Return array */
                return $elements;
//            }
        } else {
            dol_print_error($this->db);
        }
    }

    /**
     *    \brief    Supprime le projet dans la base
     *    \param    Utilisateur
     */
    public function delete($user) {
        global $langs, $conf;
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_projet";
        $sql .= " WHERE rowid=" . $this->id;

        $resql = $this->db->query($sql);
        if ($resql) {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('PROJECT_DELETE', $this, $user, $langs, $conf);
            if ($result < 0) {
                $error++;
                $this->errors = $interface->errors;
            }
            // Fin appel triggers
            return 0;
        } else {
            return -1;
        }
    }

    /**
     *    \brief      Cree une tache dans le projet
     *    \param      user        Id utilisateur qui cree
     *    \param     title      titre de la tache
     *    \param      parent   tache parente
     */
    public function CreateTask($user, $title, $parent = 0) {
        global $langs, $conf;
        $result = 0;
        if (trim($title)) {
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_projet_task (fk_projet, title, fk_user_creat, fk_task_parent, duration_effective)";
            $sql.= " VALUES (" . $this->id . ",'$title', " . $user->id . "," . $parent . ", 0)";

            dol_syslog("Project::CreateTask sql=" . $sql, LOG_DEBUG);
            if ($this->db->query($sql)) {
                $task_id = $this->db->last_insert_id("" . MAIN_DB_PREFIX . "Synopsis_projet_task");
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('PROJECT_CREATE_TASK', $this, $user, $langs, $conf);
                if ($result < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                $result = 0;
            } else {
                $this->error = $this->db->error();
                dol_syslog("Project::CreateTask error -2 " . $this->error, LOG_ERR);
                $result = -2;
            }

            if ($result == 0) {
                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors (fk_projet_task, fk_user)";
                $sql.= " VALUES (" . $task_id . "," . $user->id . ")";

                dol_syslog("Project::CreateTask sql=" . $sql, LOG_DEBUG);
                if ($this->db->query($sql)) {
                    // Appel des triggers
                    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                    $interface = new Interfaces($this->db);
                    $result = $interface->run_triggers('PROJECT_CREATE_TASK_ACTORS', $this, $user, $langs, $conf);
                    if ($result < 0) {
                        $error++;
                        $this->errors = $interface->errors;
                    }
                    // Fin appel triggers
                    $result = 0;
                } else {
                    $this->error = $this->db->error();
                    dol_syslog("Project::CreateTask error -3 " . $this->error, LOG_ERR);
                    $result = -2;
                }
            }
        } else {
            dol_syslog("Project::CreateTask error -1 ref null");
            $result = -1;
        }

        return $result;
    }

    /**
     *    \brief      Cree une tache dans le projet
     *    \param      user        Id utilisateur qui cree
     *    \param     title      titre de la tache
     *    \param      parent   tache parente
     */
    public function TaskAddTime($user, $task, $time, $date) {
        $result = 0;
        global $langs, $conf;

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_projet_task_time (fk_task, task_date, task_duration, fk_user)";
        $sql .= " VALUES (" . $task . ",'" . $this->db->idate($date) . "'," . $time . ", " . $user->id . ") ;";

        if ($this->db->query($sql)) {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('PROJECT_CREATE_TASK_TIME', $this, $user, $langs, $conf);
            if ($result < 0) {
                $error++;
                $this->errors = $interface->errors;
            }
            // Fin appel triggers
            $task_id = $this->db->last_insert_id("" . MAIN_DB_PREFIX . "Synopsis_projet_task");
            $result = 0;
        } else {
            dol_syslog("Project::TaskAddTime error -2", LOG_ERR);
            $this->error = $this->db->error();
            $result = -2;
        }

        if ($result == 0) {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_projet_task";
            $sql .= " SET duration_effective = duration_effective + '" . preg_replace("/,/", ".", $time) . "'";
            $sql .= " WHERE rowid = '" . $task . "';";

            if ($this->db->query($sql)) {
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('PROJECT_UPDATE_TASK', $this, $user, $langs, $conf);
                if ($result < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers
                $result = 0;
            } else {
                dol_syslog("Project::TaskAddTime error -3", LOG_ERR);
                $this->error = $this->db->error();
                $result = -2;
            }
        }

        return $result;
    }
    
    
    /**
     * 	Check if user has permission on current project
     *
     * 	@param	User	$user		Object user to evaluate
     * 	@param  string	$mode		Type of permission we want to know: 'read', 'write'
     * 	@return	int					>0 if user has permission, <0 if user has no permission
     */
    function restrictedProjectArea($user, $mode='read')
    {
        // To verify role of users
        $userAccess = 0;
        if (($mode == 'read' && ! empty($user->rights->projet->all->lire)) || ($mode == 'write' && ! empty($user->rights->projet->all->creer)) || ($mode == 'delete' && ! empty($user->rights->projet->all->supprimer)))
        {
            $userAccess = 1;
        }
        else if ($this->public && (($mode == 'read' && ! empty($user->rights->projet->lire)) || ($mode == 'write' && ! empty($user->rights->projet->creer)) || ($mode == 'delete' && ! empty($user->rights->projet->supprimer))))
        {
            $userAccess = 1;
        }
        else
		{
            foreach (array('internal', 'external') as $source)
            {
                $userRole = $this->liste_contact(4, $source);
                $num = count($userRole);

                $nblinks = 0;
                while ($nblinks < $num)
                {
                    if ($source == 'internal' && preg_match('/^PROJECT/', $userRole[$nblinks]['code']) && $user->id == $userRole[$nblinks]['id'])
                    {
                        if ($mode == 'read'   && $user->rights->projet->lire)      $userAccess++;
                        if ($mode == 'write'  && $user->rights->projet->creer)     $userAccess++;
                        if ($mode == 'delete' && $user->rights->projet->supprimer) $userAccess++;
                    }
                    // Permission are supported on users only. To have an external thirdparty contact to see a project, its user must allowed to contacts of projects.
                    /*if ($source == 'external' && preg_match('/PROJECT/', $userRole[$nblinks]['code']) && $user->contact_id == $userRole[$nblinks]['id'])
                    {
                        if ($mode == 'read'   && $user->rights->projet->lire)      $userAccess++;
                        if ($mode == 'write'  && $user->rights->projet->creer)     $userAccess++;
                        if ($mode == 'delete' && $user->rights->projet->supprimer) $userAccess++;
                    }*/
                    $nblinks++;
                }
            }
            //if (empty($nblinks))	// If nobody has permission, we grant creator
            //{
            //	if ((!empty($this->user_author_id) && $this->user_author_id == $user->id))
            //	{
            //		$userAccess = 1;
            //	}
            //}
        }

        return ($userAccess?$userAccess:-1);
    }

    public function getTasksRoleForUser($user) {
        $tasksrole = array();

        /* Liste des taches et role sur la tache du user courant dans $tasksrole */
        $sql = "SELECT a.fk_projet_task, a.role";
        $sql .= " FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors as a";
        $sql .= " WHERE a.fk_user = " . $user->id;

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $row = $this->db->fetch_row($resql);
                $tasksrole[$row[0]] = $row[1];
                $i++;
            }
            $this->db->free();
        } else {
            dol_print_error($this->db);
        }

        return $tasksrole;
    }

    public function getTasksArray() {
        $tasks = array();

        /* Liste des taches dans $tasks */

        $sql = "SELECT t.rowid, t.title, t.fk_task_parent, t.duration_effective";
        $sql .= " FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task as t";
        $sql .= " WHERE t.fk_projet =" . $this->id;
        $sql .= " ORDER BY t.fk_task_parent";

        $var = true;
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $tasks[$i]->id = $obj->rowid;
                $tasks[$i]->title = $obj->title;
                $tasks[$i]->fk_parent = $obj->fk_task_parent;
                $tasks[$i]->duration = $obj->duration_effective;
                $i++;
            }
            $this->db->free();
        } else {
            dol_print_error($this->db);
        }

        return $tasks;
    }

    public $workedPercentTime = 0;

    public function getAllStats() {
        $this->aggregateProgressProject();
        $this->getStatsDuration();
        $this->getStatsDurationEffective();
        if ($this->totDuration > 0) {
            if (100 * $this->workedDuration / $this->totDuration < 5) {
                $this->workedPercentTime = preg_replace('/,/', '.', round(100 * $this->workedDuration / $this->totDuration, 2));
            } else {
                $this->workedPercentTime = round(100 * $this->workedDuration / $this->totDuration);
            }
        }
    }

    public $workedPercentQual = 0;
    public $statAvgProgByGroup = array();

    public function aggregateProgressProject() {
        //chope tous les groupes
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task WHERE fk_task_type=3 AND fk_projet = " . $this->id . " ORDER BY level DESC";
        $sql = $this->db->query($requete);
        $arr = array();
        $arrParent = array();
        while ($res = $this->db->fetch_object($sql)) {
            $requete1 = "SELECT avg(progress) as prog FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task WHERE fk_projet =" . $this->id . "  AND fk_task_parent = " . $res->rowid;
            $sql1 = $this->db->query($requete1);
            $res1 = $this->db->fetch_object($sql1);
            $avg = $res1->prog;
            $this->statAvgProgByGroup[$res->rowid] = $avg;
            $requete2 = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_projet_task SET progress = $avg WHERE rowid = " . $res->rowid;
            $this->db->query($requete2);
        }
        $requeteAvg = "SELECT avg(progress) as AVGGlobal FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task WHERE fk_projet = " . $this->id . " AND (fk_task_parent = 0 OR fk_task_parent IS NULL)";
        $sqlAvg = $this->db->query($requeteAvg);
        $resAvg = $this->db->fetch_object($sqlAvg);
        if ($resAvg->AVGGlobal < 5) {
            $this->statAvgProgByGroup[0] = preg_replace('/,/', '.', round($resAvg->AVGGlobal, 2));
        } else {
            $this->statAvgProgByGroup[0] = preg_replace('/,/', '.', round($resAvg->AVGGlobal, 0));
        }

        return ($this->statAvgProgByGroup[0]);


        //faire le calculs de progression du group et stocker dans la db
        //au niveau 0, calcul global, retour de la valeur
    }

    public $totDuration = 0;

    public function getStatsDuration() {
        //chope tous les temps pour toutes les taches par type (milestone / tache) en secondes
        $requete = "SELECT SUM(task_duration) as duree_prevue
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task
                     WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_task.fk_projet = " . $this->id . "
                       AND " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid = " . MAIN_DB_PREFIX . "Synopsis_projet_task_time.fk_task
                       AND " . MAIN_DB_PREFIX . "Synopsis_projet_task.fk_task_type <> 3";
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $this->totDuration = $res->duree_prevue;
        return ( $this->totDuration);
    }

    public $workedDuration = 0;

    public function getStatsDurationEffective() {
        //chope tous les temps pour toutes les taches par type (milestone / tache) en secondes
        $requete = "SELECT SUM(task_duration_effective) as duree_conso
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task
                     WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_task.fk_projet = " . $this->id . "
                       AND " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid = " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective.fk_task
                       AND " . MAIN_DB_PREFIX . "Synopsis_projet_task.fk_task_type <> 3";
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $this->workedDuration = $res->duree_conso;
        return ( $this->workedDuration);
    }

    /**
     *        \brief      Renvoie nom clicable (avec eventuellement le picto)
     *        \param        withpicto        Inclut le picto dans le lien
     *        \param        option            Sur quoi pointe le lien
     *        \param        maxlen            Longueur max libelle
     *        \return        string            Chaine avec URL
     */
    function getNomUrl($withpicto = 0, $option = '', $maxlen = 0) {
        global $langs;
        if($maxlen == 1)//Incoherent
            $maxlen = 0;

        $result = '';
        $lien = "";
        if ($this->hasGantt == 1)
            $lien = '<a href="' . DOL_URL_ROOT . '/synopsisprojet/gantt/gantt.php?id=' . $this->id . '">';
        else if ($this->hasTacheLight == 1)
            $lien = '<a href="' . DOL_URL_ROOT . '/synopsisprojet/tasks/card.php?mode=Light&id=' . $this->id . '">';
        else
            $lien = '<a href="' . DOL_URL_ROOT . '/synopsisprojet/card.php?id=' . $this->id . '">';
        $lienfin = '</a>';

        $label = $this->ref ."-".$this->title;
        if ($withpicto)
            $result.=($lien . img_object($langs->trans("ShowProject") . ': ' . $label, 'project') . $lienfin . ' ');
        $result.=$lien . ($maxlen ? dol_trunc($label, $maxlen) : $label) . $lienfin;
        return $result;
    }

    function getTaskNomUrl($id, $withpicto = 0, $option = '', $maxlen = 0, $titreT = '') {
        global $langs;

        $result = '';

        $lien = '<a href="' . DOL_URL_ROOT . '/synopsisprojet/tasks/task.php?id=' . $id . '">';
        $lienfin = '</a>';

        $titre = $this->title . ($titreT != '' ? ' - ' . $titreT : '');
        if ($withpicto)
            $result.=($lien . img_object($langs->trans("ShowTask") . ': ' . $titre, 'task') . $lienfin . ' ');
        $result.=$lien . ($maxlen ? dol_trunc($titre, $maxlen) : $titre) . $lienfin;
        return $result;
    }

    public function showTreeTask($js, $width = 150, $height = 300, $opacity = '1', $display = 'block') {
        $requete = "SELECT " . MAIN_DB_PREFIX . "Synopsis_projet_task.title, " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task
                     WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_task.fk_projet = " . MAIN_DB_PREFIX . "Synopsis_projet.rowid
                       AND fk_task_parent is null
                       AND " . MAIN_DB_PREFIX . "Synopsis_projet.rowid = " . $this->id;
        $sql = $this->db->query($requete);

        print '<div id="projetTreeDiv" style="width: ' . $width . 'px;  z-index: 2; float: left; opacity:' . $opacity . ';display:' . $display . '; overflow: auto; max-height: ' . $height . 'px; height: ' . $height . 'px;">';
        print '<ul id="projetTree" class="projecttree treeview">';
        print '  <li  class="lastCollapsable"><div class="hitarea lastCollapsable-hitarea"></div><span class="project">' . $this->title . '</span>';
        $i = 0;
        $count2 = $this->db->num_rows($sql);
        while ($res = $this->db->fetch_object($sql)) {
            $i++;
            print '      <ul>';
            $requete1 = "SELECT count(*) as cnt
                             FROM " . MAIN_DB_PREFIX . "Synopsis_projet,
                                  " . MAIN_DB_PREFIX . "Synopsis_projet_task
                            WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_task.fk_projet = " . MAIN_DB_PREFIX . "Synopsis_projet.rowid
                              AND fk_task_parent = $res->rowid";
            $sql1 = $this->db->query($requete1);
            $res1 = $this->db->fetch_object($sql1);
            if ($res1->cnt > 0) {
                if ($i == $res1->cnt) {
                    print '          <li class="lastCollapsable"><div class="hitarea lastCollapsable-hitarea"></div><span class="task"><a href="#" onClick="' . $js . '(' . $res->rowid . ',this);">' . $res->title . '</a></span>';
                    $this->recursTask($res->rowid, $js);
                    print '  </li>';
                } else {
                    print '          <li class="collapsable"><div class="hitarea collapsable-hitarea"></div><span class="task"><a href="#" onClick="' . $js . '(' . $res->rowid . ',this);">' . $res->title . '</a></span>';
                    $this->recursTask($res->rowid, $js);
                    print '  </li>';
                }
            } else {
                if ($count2 == $i) {
                    print '          <li class="last"><div class="last"></div><span class="task"><a href="#" onClick="' . $js . '(' . $res->rowid . ',this);">' . $res->title . '</a></span>';
                    print '  </li>';
                } else {
                    print '          <li class=""><div class=""></div><span class="task"><a href="#" onClick="' . $js . '(' . $res->rowid . ',this);">' . $res->title . '</a></span>';
                    print '  </li>';
                }
            }

            print '</ul>';
        }

        print '  </li>';
        print '</ul>';


        print '</div>';
    }

    private function recursTask($parentId, $js) {
        $requete = "SELECT " . MAIN_DB_PREFIX . "Synopsis_projet_task.title, " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task
                     WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_task.fk_projet = " . MAIN_DB_PREFIX . "Synopsis_projet.rowid
                       AND fk_task_parent = " . $parentId;
        $sql = $this->db->query($requete);
        $i = 0;
        $count2 = $this->db->num_rows($sql);
        while ($res = $this->db->fetch_object($sql)) {
            $i++;
            print '      <ul>';
            $requete1 = "SELECT count(*) as cnt FROM " . MAIN_DB_PREFIX . "Synopsis_projet,
                              " . MAIN_DB_PREFIX . "Synopsis_projet_task
                        WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_task.fk_projet = " . MAIN_DB_PREFIX . "Synopsis_projet.rowid
                          AND fk_task_parent = $res->rowid";
            $sql1 = $this->db->query($requete1);
            $res1 = $this->db->fetch_object($sql1);
            if ($res1->cnt > 0) {
                if ($i == $res1->cnt) {
                    print '          <li class="lastCollapsable"><div class="hitarea lastCollapsable-hitarea"></div><span class="task"><a href="#" onClick="' . $js . '(' . $res->rowid . ',this);">' . $res->title . '</a></span>';
                    $this->recursTask($res->rowid, $js);
                    print '  </li>';
                } else {
                    print '          <li class="collapsable"><div class="hitarea collapsable-hitarea"></div><span class="task"><a href="#" onClick="' . $js . '(' . $res->rowid . ',this);">' . $res->title . '</a></span>';
                    $this->recursTask($res->rowid, $js);
                    print '  </li>';
                }
            } else {
                if ($count2 == $i) {
                    print '          <li class="last"><div class="last"></div><span class="task"><a href="#" onClick="' . $js . '(' . $res->rowid . ',this);">' . $res->title . '</a></span>';
                    print '  </li>';
                } else {
                    print '          <li class=""><div class=""></div><span class="task"><a href="#" onClick="' . $js . '(' . $res->rowid . ',this);">' . $res->title . '</a></span>';
                    print '  </li>';
                }
            }
            print '      </ul>';
        }
    }

    public function getEndDateProject() {
        // Effectif C'est la date de fin de la derniere tache
        // Prevu C'est la date de fin du projet Prévu lors de la config
    }

    private $arrCoutHoraire = array();

    public function getCoutHoraireRH($type = 'user', $userId) {

        if (isset($conf->global->MAIN_MODULE_SYNOPSISHRM)) {
            require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Hrm/hrm.class.php");
            $hrm = new Hrm($this->db);
        }
        if ($type == "user") {
            $coutHoraire = "0";
            if ($this->arrCoutHoraire[$type][$userId] != 0) {
                $coutHoraire = $this->arrCoutHoraire[$type][$userId];
            } else {
                $requete = "SELECT couthoraire
                              FROM " . MAIN_DB_PREFIX . "Synopsis_hrm_user
                             WHERE user_id = " . $userId . "
                          ORDER BY startDate DESC
                             LIMIT 1";
                $sql2 = $this->db->query($requete);
                if ($sql2) {
                    $res2 = $this->db->fetch_object($sql2);
                    $coutHoraire = $res2->couthoraire;
                }
                if ($coutHoraire == "0" && isset($conf->global->MAIN_MODULE_SYNOPSISHRM)) {
                    //on prend le cout de l'equipe
                    //get Team Id
                    $teamId = $hrm->getTeam($res->fk_user);
                    $requete = "SELECT couthoraire FROM Babel_hrm_team WHERE teamId = " . $teamId;
                    $sql_team = $this->db->query($requete);
                    if ($sql_team) {
                        $res3 = $this->db->fetch_object($sql_team);
                        $coutHoraire = $res3->couthoraire;
                    }
                }
                $this->arrCoutHoraire[$type][$userId] = $coutHoraire;
            }
            return($coutHoraire);
        } else if ($type == 'group') {
            $coutHoraire = 0;
            if ($this->arrCoutHoraire[$type][$userId] != 0) {
                $coutHoraire = $this->arrCoutHoraire[$type][$userId];
            } else if (isset($conf->global->MAIN_MODULE_SYNOPSISHRM)) {

                $teamId = $hrm->getTeam($res->fk_user);
                $requete = "SELECT couthoraire FROM Babel_hrm_team WHERE teamId = " . $userId;
                $sql_team = $this->db->query($requete);
                if ($sql_team) {
                    $res3 = $this->db->fetch_object($sql_team);
                    $coutHoraire = $res3->couthoraire;
                }
                $this->arrCoutHoraire[$type][$userId] = $coutHoraire;
            }
            return($coutHoraire);
        }
    }

    public function costTaskRH($taskId) {
        global $conf;
//TODO 2 cases : effectif / prévu
//        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Hrm/hrm.class.php");
//        $hrm = new hrm($this->db);
//        $arrFerie = $hrm->jourFerie();

        $db = $this->db;

        $arr = array();
        $arr['taskCostForUser'] = 0;
        $arr['totalDur'] = 0;
        $arrayByDay = array();
        //$hrm->listRessources();
//TODO optimizer
//require_once('Var_Dump.php');
//Var_Dump::Display($hrm->allRessource);
//        foreach($hrm->allRessource as $key=>$val) //Humm
//        {
//            if ("x".$val['GLEId'] != "x")
//            {
        $requete = "SELECT " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.fk_user,
                                   " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid as tid,
                                   " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.percent as occupation,
                                   " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.type as userType,
                                   " . MAIN_DB_PREFIX . "Synopsis_projet_task.title as title
                              FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors,
                                   " . MAIN_DB_PREFIX . "Synopsis_projet_task
                             WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid = " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.fk_projet_task
                               AND " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid = " . $taskId;
        $sql = $db->query($requete);
        $iter = 0;
        while ($res = $db->fetch_object($sql)) {
            if ($res->fk_user . "x" != "x") {
                //coutHoraire
                $coutHoraire = $this->getCoutHoraireRH($res->userType, $res->fk_user);
//TODO ajouter user/group
                $requete = "SELECT task_duration as Dur,
                                           unix_timestamp(task_date) as task_dateF,
                                           fk_user
                                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time
                                     WHERE fk_task = " . $res->tid . "
                                       AND fk_user=" . $res->fk_user;
                $sql1 = $db->query($requete);
                //par tache , paruser, par jour
                while ($res1 = $db->fetch_object($sql1)) {
                    //minuit le premier et le dernier jour
                    $beginTask = mktime(0, 0, 0, date("m", $res1->task_dateF), date("d", $res1->task_dateF), date("Y", $res1->task_dateF));
                    $endTask = mktime(23, 59, 59, date("m", $res1->task_dateF + $res1->Dur), date("d", $res1->task_dateF + $res1->Dur), date("Y", $res1->task_dateF + $res1->Dur));

                    if ($res1->Dur < 86400) {
                        $costMajore = 0;
                        $costMajore1 = 0;
                        $costMajore2 = 0;
                        if (date("N", $res1->task_dateF) != date('N', $res1->task_dateF + $res1->Dur)) {
                            //temp => minuit
                            $day1Dur = mktime(23, 59, 59, date('m', $res1->task_dateF), date('d', $res1->task_dateF), date('Y', $res1->task_dateF)) - $res1->task_dateF;
                            $date2 = $res1->task_dateF + $res1->Dur;
                            $day2Debut = mktime(0, 0, 0, date('m', $date2), date('d', $date2), date('Y', $date2));


                            $costMajore1 = $this->getCostRHForAday($res1->task_dateF, $day1Dur, $res->occupation, $coutHoraire);
                            $costMajore2 = $this->getCostRHForAday($day2Debut, $res1->Dur - $day1Dur, $res->occupation, $coutHoraire);

                            $this->CostRHByDay[$beginTask]['duree'] += $day1Dur * ($res->occupation / 100);
                            $this->CostRHByDay[$day2Debut]['duree'] += ($res1->Dur - $day1Dur) * ($res->occupation / 100);

                            $this->CostRHByDay[$beginTask]['cost'] += $costMajore1;
                            $this->CostRHByDay[$day2Debut]['cost'] += $costMajore2;
                            $this->costByDay['task'][$taskId]['HRessource']['duree'][$beginTask] += $day1Dur * ($res->occupation / 100);
                            $this->costByDay['task'][$taskId]['HRessource']['cost'][$beginTask] += $costMajore1;
                            $this->costByDay['task'][$taskId]['HRessource']['duree'][$day2Debut] += ($res1->Dur - $day1Dur) * ($res->occupation / 100);
                            $this->costByDay['task'][$taskId]['HRessource']['cost'][$day2Debut] += $costMajore2;
                            $costMajore = $costMajore1 + $costMajore2;
                        } else {
                            $costMajore = $this->getCostRHForAday($res1->task_dateF, $res1->Dur, $res->occupation, $coutHoraire);
                            $this->costByDay['task'][$taskId]['HRessource']['duree'][$beginTask] += $res1->Dur * ($res->occupation / 100);
                            $this->costByDay['task'][$taskId]['HRessource']['cost'][$beginTask] += $costMajore;
                            $this->CostRHByDay[$beginTask]['duree'] += $res1->Dur * ($res->occupation / 100);
                            $this->CostRHByDay[$beginTask]['cost'] += $costMajore;
                        }
                        $this->CostRHByTaskByUser[$taskId]['task'][$res->fk_user]['id'] = $taskId;
                        $this->CostRHByTaskByUser[$taskId]['task'][$res->fk_user]['title'] = $res->title;
                        $this->CostRHByTaskByUser[$taskId]['task'][$res->fk_user]['duration']+=$res1->Dur * ($res->occupation / 100);
                        $this->CostRHByTaskByUser[$taskId]['task'][$res->fk_user]['taskCostForUser']+= $costMajore;

                        $this->CostRHByUserByTask[$res->fk_user]['task'][$taskId]['id'] = $taskId;
                        $this->CostRHByUserByTask[$res->fk_user]['task'][$taskId]['title'] = $res->title;
                        $this->CostRHByUserByTask[$res->fk_user]['task'][$taskId]['duration']+=$res1->Dur * ($res->occupation / 100);
                        $this->CostRHByUserByTask[$res->fk_user]['task'][$taskId]['taskCostForUser']+= $costMajore;

                        $this->CostRHByUser[$res->fk_user]['totalDur'] += $res1->Dur * ($res->occupation / 100);
                        $this->CostRHByUser[$res->fk_user]['totalCostForUser'] += $costMajore;

                        $this->cost["task"][$taskId]['coutRH'] += $costMajore;
                        $this->cost["task"][$taskId]['durRH'] += $res1->Dur * ($res->occupation / 100);
                    } else {
                        //Cas du premier et du dernier jour => jour entier
                        for ($i = $beginTask; $i < $endTask; $i+= 86400) {
                            if (date('N', $i) == 6 || date('N', $i) == 7) {
                                continue;
                            }
                            //Jour ferié
                            $pasFerie = $this->isFerie($i);

                            if ($pasFerie) {

                                //Calcul du nombre d'heure dans la journee % config
                                $dur = $conf->global->PROJECT_HOUR_PER_DAY * 3600 * ($res->occupation / 100);
                                $cout = ($dur / 3600 * $coutHoraire);

                                $this->costByDay['task'][$taskId]['HRessource']['duree'][$i] += $dur;
                                $this->costByDay['task'][$taskId]['HRessource']['cost'][$i] += $cout;
                                $this->CostRHByTaskByUser[$taskId]['task'][$res->fk_user]['id'] = $taskId;
                                $this->CostRHByTaskByUser[$taskId]['task'][$res->fk_user]['title'] = $res->title;
                                $this->CostRHByTaskByUser[$taskId]['task'][$res->fk_user]['duration']+=$dur;
                                $this->CostRHByTaskByUser[$taskId]['task'][$res->fk_user]['taskCostForUser']+= $cout;

                                $this->CostRHByUserByTask[$res->fk_user]['task'][$taskId]['id'] = $taskId;
                                $this->CostRHByUserByTask[$res->fk_user]['task'][$taskId]['title'] = $res->title;
                                $this->CostRHByUserByTask[$res->fk_user]['task'][$taskId]['duration']+=$dur;
                                $this->CostRHByUserByTask[$res->fk_user]['task'][$taskId]['taskCostForUser']+= $cout;

                                $this->CostRHByUser[$res->fk_user]['totalDur'] += $dur;
                                $this->CostRHByUser[$res->fk_user]['totalCostForUser'] += $cout;

                                $this->cost["task"][$taskId]['coutRH'] += $cout;
                                $this->cost["task"][$taskId]['durRH'] += $dur;
                            }
                        }
                        //Horaire speciaux
                        $requete = "SELECT *
                                              FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_special,
                                                   " . MAIN_DB_PREFIX . "Synopsis_projet_trancheHoraire
                                             WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_trancheHoraire.id = " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_special.fk_tranche
                                               AND fk_task = " . $taskId . "
                                               AND fk_user=" . $res->fk_user . '
                                               AND type="' . $res->userType . '"';
                        $sql2 = $this->db->query($requete);
                        while ($res2 = $this->db->fetch_object($sql2)) {
                            $factor = $res2->facteur;
                            $dur = $res2->qte * 3600 * ($res->occupation / 100);
                            $cout = ($dur / 3600) * $coutHoraire * ($factor / 100);

                            $this->costByDay['task'][$taskId]['HRessource']['duree'][-1] += $dur;
                            $this->costByDay['task'][$taskId]['HRessource']['cost'][-1] += $cout;
//                                    $this->CostRHByDay[-1]['duree'] += $dur;
//                                    $this->CostRHByDay[-1]['cost'] += $cout;
                            $this->CostRHByTaskByUser[$taskId]['task'][$res->fk_user]['id'] = $taskId;
                            $this->CostRHByTaskByUser[$taskId]['task'][$res->fk_user]['title'] = $res->title;
                            $this->CostRHByTaskByUser[$taskId]['task'][$res->fk_user]['duration']+=$dur;
                            $this->CostRHByTaskByUser[$taskId]['task'][$res->fk_user]['taskCostForUser']+= $cout;

                            $this->CostRHByUserByTask[$res->fk_user]['task'][$taskId]['id'] = $taskId;
                            $this->CostRHByUserByTask[$res->fk_user]['task'][$taskId]['title'] = $res->title;
                            $this->CostRHByUserByTask[$res->fk_user]['task'][$taskId]['duration']+=$dur;
                            $this->CostRHByUserByTask[$res->fk_user]['task'][$taskId]['taskCostForUser']+= $cout;

                            $this->CostRHByUser[$res->fk_user]['totalDur'] += $dur;
                            $this->CostRHByUser[$res->fk_user]['totalCostForUser'] += $cout;

                            $this->cost["task"][$taskId]['coutRH'] += $cout;
                            $this->cost["task"][$taskId]['durRH'] += $dur;
                        }
                    }
                }
            }
            $iter++;
//                }
//            }
        }
        return ($this->cost["task"][$taskId]['coutRH']);
    }

    public function costTaskRHEffective($taskId) {
        global $conf;
//TODO 2 cases : effectif / prévu
//        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Hrm/hrm.class.php");
//        $hrm = new hrm($this->db);
//        $arrFerie = $hrm->jourFerie();

        $db = $this->db;

        $arr = array();
        $arr['taskCostForUser'] = 0;
        $arr['totalDur'] = 0;
        $arrayByDay = array();
        $requete = "SELECT " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.fk_user,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid as tid,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.percent as occupation,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.type as userType,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.title as title
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task
                     WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid = " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.fk_projet_task
                       AND " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid = " . $taskId;
        $sql = $db->query($requete);
        $iter = 0;
        while ($res = $db->fetch_object($sql)) {
            if ($res->fk_user . "x" != "x") {
                //coutHoraire
                $coutHoraire = $this->getCoutHoraireRH($res->userType, $res->fk_user);
//TODO ajouter user/group
                $requete = "SELECT task_duration_effective as Dur,
                                   unix_timestamp(task_date_effective) as task_dateF,
                                   fk_user
                              FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective
                             WHERE fk_task = " . $res->tid . "
                               AND fk_user=" . $res->fk_user;
                $sql1 = $db->query($requete);
                //par tache , paruser, par jour
                while ($res1 = $db->fetch_object($sql1)) {
                    //minuit le premier et le dernier jour
                    $beginTask = mktime(0, 0, 0, date("m", $res1->task_dateF), date("d", $res1->task_dateF), date("Y", $res1->task_dateF));
                    $endTask = mktime(23, 59, 59, date("m", $res1->task_dateF + $res1->Dur), date("d", $res1->task_dateF + $res1->Dur), date("Y", $res1->task_dateF + $res1->Dur));

                    if ($res1->Dur < 86400) {
                        $costMajore = 0;
                        $costMajore1 = 0;
                        $costMajore2 = 0;
                        if (date("N", $res1->task_dateF) != date('N', $res1->task_dateF + $res1->Dur)) {
                            //temp => minuit
                            $day1Dur = mktime(23, 59, 59, date('m', $res1->task_dateF), date('d', $res1->task_dateF), date('Y', $res1->task_dateF)) - $res1->task_dateF;
                            $date2 = $res1->task_dateF + $res1->Dur;
                            $day2Debut = mktime(0, 0, 0, date('m', $date2), date('d', $date2), date('Y', $date2));


                            $costMajore1 = $this->getCostRHForAdayEffective($res1->task_dateF, $day1Dur, $res->occupation, $coutHoraire);
                            $costMajore2 = $this->getCostRHForAdayEffective($day2Debut, $res1->Dur - $day1Dur, $res->occupation, $coutHoraire);

                            $this->CostRHByDayEffective[$beginTask]['duree'] += $day1Dur * ($res->occupation / 100);
                            $this->CostRHByDayEffective[$day2Debut]['duree'] += ($res1->Dur - $day1Dur) * ($res->occupation / 100);

                            $this->CostRHByDayEffective[$beginTask]['cost'] += $costMajore1;
                            $this->CostRHByDayEffective[$day2Debut]['cost'] += $costMajore2;
                            $this->costByDayEffective['task'][$taskId]['HRessource']['duree'][$beginTask] += $day1Dur * ($res->occupation / 100);
                            $this->costByDayEffective['task'][$taskId]['HRessource']['cost'][$beginTask] += $costMajore1;
                            $this->costByDayEffective['task'][$taskId]['HRessource']['duree'][$day2Debut] += ($res1->Dur - $day1Dur) * ($res->occupation / 100);
                            $this->costByDayEffective['task'][$taskId]['HRessource']['cost'][$day2Debut] += $costMajore2;
                            $costMajore = $costMajore1 + $costMajore2;
                        } else {
                            $costMajore = $this->getCostRHForAday($res1->task_dateF, $res1->Dur, $res->occupation, $coutHoraire);
                            $this->costByDayEffective['task'][$taskId]['HRessource']['duree'][$beginTask] += $res1->Dur * ($res->occupation / 100);
                            $this->costByDayEffective['task'][$taskId]['HRessource']['cost'][$beginTask] += $costMajore;
                            $this->CostRHByDayEffective[$beginTask]['duree'] += $res1->Dur * ($res->occupation / 100);
                            $this->CostRHByDayEffective[$beginTask]['cost'] += $costMajore;
                        }
                        $this->CostRHByTaskByUserEffective[$taskId]['task'][$res->fk_user]['id'] = $taskId;
                        $this->CostRHByTaskByUserEffective[$taskId]['task'][$res->fk_user]['title'] = $res->title;
                        $this->CostRHByTaskByUserEffective[$taskId]['task'][$res->fk_user]['duration']+=$res1->Dur * ($res->occupation / 100);
                        $this->CostRHByTaskByUserEffective[$taskId]['task'][$res->fk_user]['taskCostForUser']+= $costMajore;

                        $this->CostRHByUserByTaskEffective[$res->fk_user]['task'][$taskId]['id'] = $taskId;
                        $this->CostRHByUserByTaskEffective[$res->fk_user]['task'][$taskId]['title'] = $res->title;
                        $this->CostRHByUserByTaskEffective[$res->fk_user]['task'][$taskId]['duration']+=$res1->Dur * ($res->occupation / 100);
                        $this->CostRHByUserByTaskEffective[$res->fk_user]['task'][$taskId]['taskCostForUser']+= $costMajore;

                        $this->CostRHByUserEffective[$res->fk_user]['totalDur'] += $res1->Dur * ($res->occupation / 100);
                        $this->CostRHByUserEffective[$res->fk_user]['totalCostForUser'] += $costMajore;

                        $this->costEffective["task"][$taskId]['coutRH'] += $costMajore;
                        $this->costEffective["task"][$taskId]['durRH'] += $res1->Dur * ($res->occupation / 100);
                    } else {
                        //Cas du premier et du dernier jour => jour entier
                        for ($i = $beginTask; $i < $endTask; $i+= 86400) {
                            if (date('N', $i) == 6 || date('N', $i) == 7) {
                                continue;
                            }
                            //Jour ferié
                            $pasFerie = $this->isFerie($i);

                            if ($pasFerie) {

                                //Calcul du nombre d'heure dans la journee % config
                                $dur = $conf->global->PROJECT_HOUR_PER_DAY * 3600 * ($res->occupation / 100);
                                $cout = ($dur / 3600 * $coutHoraire);

                                $this->costByDayEffective['task'][$taskId]['HRessource']['duree'][$i] += $dur;
                                $this->costByDayEffective['task'][$taskId]['HRessource']['cost'][$i] += $cout;
                                $this->CostRHByTaskByUserEffective[$taskId]['task'][$res->fk_user]['id'] = $taskId;
                                $this->CostRHByTaskByUserEffective[$taskId]['task'][$res->fk_user]['title'] = $res->title;
                                $this->CostRHByTaskByUserEffective[$taskId]['task'][$res->fk_user]['duration']+=$dur;
                                $this->CostRHByTaskByUserEffective[$taskId]['task'][$res->fk_user]['taskCostForUser']+= $cout;

                                $this->CostRHByUserByTaskEffective[$res->fk_user]['task'][$taskId]['id'] = $taskId;
                                $this->CostRHByUserByTaskEffective[$res->fk_user]['task'][$taskId]['title'] = $res->title;
                                $this->CostRHByUserByTaskEffective[$res->fk_user]['task'][$taskId]['duration']+=$dur;
                                $this->CostRHByUserByTaskEffective[$res->fk_user]['task'][$taskId]['taskCostForUser']+= $cout;

                                $this->CostRHByUserEffective[$res->fk_user]['totalDur'] += $dur;
                                $this->CostRHByUserEffective[$res->fk_user]['totalCostForUser'] += $cout;

                                $this->costEffective["task"][$taskId]['coutRH'] += $cout;
                                $this->costEffective["task"][$taskId]['durRH'] += $dur;
                            }
                        }
//Prob recupere la quantite d'horaire special Effective
//                        //Horaire speciaux
//                        $requete = "SELECT *
//                                      FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time_special,
//                                           ".MAIN_DB_PREFIX."Synopsis_projet_trancheHoraire
//                                     WHERE ".MAIN_DB_PREFIX."Synopsis_projet_trancheHoraire.id = ".MAIN_DB_PREFIX."Synopsis_projet_task_time_special.fk_tranche
//                                       AND fk_task = ".$taskId."
//                                       AND fk_user=".$res->fk_user . '
//                                       AND type="'.$res->userType.'"';
//                        $sql2 = $this->db->query($requete);
//                        while ($res2 = $this->db->fetch_object($sql2))
//                        {
//                            $factor = $res2->facteur;
//                            $dur = $res2->qte * 3600 * ($res->occupation / 100);
//                            $cout = ($dur / 3600) * $coutHoraire  * ($factor /100);
//
//                            $this->costByDay['task'][$taskId]['HRessource']['duree'][-1] += $dur;
//                            $this->costByDay['task'][$taskId]['HRessource']['cost'][-1] += $cout;
//                            $this->CostRHByTaskByUser[$taskId]['task'][$res->fk_user]['id']=$taskId;
//                            $this->CostRHByTaskByUser[$taskId]['task'][$res->fk_user]['title']=$res->title;
//                            $this->CostRHByTaskByUser[$taskId]['task'][$res->fk_user]['duration']+=$dur;
//                            $this->CostRHByTaskByUser[$taskId]['task'][$res->fk_user]['taskCostForUser']+= $cout;
//
//                            $this->CostRHByUserByTask[$res->fk_user]['task'][$taskId]['id']=$taskId;
//                            $this->CostRHByUserByTask[$res->fk_user]['task'][$taskId]['title']=$res->title;
//                            $this->CostRHByUserByTask[$res->fk_user]['task'][$taskId]['duration']+=$dur;
//                            $this->CostRHByUserByTask[$res->fk_user]['task'][$taskId]['taskCostForUser']+= $cout;
//
//                            $this->CostRHByUser[$res->fk_user]['totalDur'] += $dur;
//                            $this->CostRHByUser[$res->fk_user]['totalCostForUser'] += $cout;
//
//                            $this->cost["task"][$taskId]['coutRH'] += $cout;
//                            $this->cost["task"][$taskId]['durRH'] += $dur;
//
//                        }
                    }
                }
            }
            $iter++;
        }
        return ($this->costEffective["task"][$taskId]['coutRH']);
    }

    public function getCostRHForAday($debut, $Dur, $occupation, $coutHoraire) {
        $pasFerie = $this->isFerie($debut);
        $cout = 0;
        switch (true) {
            case (!$pasFerie): {
                    $arrRes = $this->getHourPerTranche(8, $debut, $Dur);
                    foreach ($arrRes as $factor => $qte) {
                        $cout += ($occupation / 100 ) * ($factor / 100) * ($qte / 3600) * $coutHoraire;
                    }
                }
                break;
            case (date('N', $debut) == 6): {
                    $arrRes = $this->getHourPerTranche(6, $debut, $Dur);
                    foreach ($arrRes as $factor => $qte) {
                        $cout += ($occupation / 100 ) * ($factor / 100) * ($qte / 3600) * $coutHoraire;
                    }
                }
                break;
            case (date('N', $debut) == 7): {
                    $arrRes = $this->getHourPerTranche(7, $debut, $Dur);
                    foreach ($arrRes as $factor => $qte) {
                        $cout += ($occupation / 100 ) * ($factor / 100) * ($qte / 3600) * $coutHoraire;
                    }
                }
                break;
            default: {
                    $arrRes = $this->getHourPerTranche(1, $debut, $Dur);
                    foreach ($arrRes as $factor => $qte) {
                        $cout += ($occupation / 100 ) * ($factor / 100) * ($qte / 3600) * $coutHoraire;
                    }
                }
                break;
        }
        return($cout);
    }

    private $ArrFerie = false;

    public function isFerie($day, $forceRefresh = false) {
        $this->ArrFerie = array();
        if (isset($conf->global->MAIN_MODULE_SYNOPSISHRM)) {
            require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Hrm/hrm.class.php");
            $hrm = new hrm($this->db);
            if ($forceRefresh || !$this->ArrFerie) {
                $this->ArrFerie = $hrm->jourFerie();
            }
        }

        $pasFerie = true;
        foreach ($this->ArrFerie as $key) {
            //print $key . " " . $j ."\n";
            if ($key >= $day && $key <= $day + 24 * 3600) {
                $pasFerie = false;
                break;
            }
        }
        return($pasFerie);
    }

    public function getHourPerTranche($trancheId, $debut, $dur) {
        $ret = array();

        $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_trancheHoraire
                     WHERE day " . ($trancheId == 1 ? 'is null ' : ' = ' . $trancheId . " ");
        $sql = $this->db->query($requete);
        $arrTranche = array();
        while ($res = $this->db->fetch_object($sql)) {
            $debutTranche = $this->hourToSec($res->debut);
            $finTranche = $this->hourToSec($res->fin);
            $facteurTranche = $res->facteur;
            $arrTranche[$res->id]['debut'] = $debutTranche;
            $arrTranche[$res->id]['fin'] = $finTranche;
            $arrTranche[$res->id]['facteur'] = $facteurTranche;
        }
        $hourByFactor = array();
        $hourByFactor[100] = 0;
        $tmpDebut = $debut - mktime(0, 0, 0, date('m', $debut), date('d', $debut), date('Y', $debut));
        for ($i = $tmpDebut; $i < $tmpDebut + $dur; $i+=60) { // par minute
            $found = false;
            foreach ($arrTranche as $key => $val) {
                if ($i >= $val['debut'] && $i <= $val['fin']) {
                    $found = $val['facteur'];
                    break;
                }
            }
            if ($found) {
                $hourByFactor[$found]+=60;
            } else {
                $hourByFactor[100]+=60;
            }
        }
        return($hourByFactor);
    }

    public function hourToSec($hour) {
        if (preg_match("/([0-9]{2}):([0-9]{2})/", $hour, $arr)) {
            $return = (intval($arr[1]) * 3600) + (intval($arr[2]) * 60);
            return($return);
        } else if (preg_match("/([0-9]{2}):([0-9]{2}):([0-9]{2})/", $hour, $arr)) {
            $return = (intval($arr[1]) * 3600) + (intval($arr[2]) * 60) + (intval($arr[3]));
            return($return);
        } else {
            return(false);
        }
    }

    public function SecToHour($hour) {
        return(date('H:i', $hour));
    }

    public $arrCostRessourcePerDayPerTask = array();
    public $CostRessourceByUser = array();
    public $CostRessourceByTask = array();
    public $CostByRessource = array();

    public function costTaskRessource($taskId) {
        global $conf;
//        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Hrm/hrm.class.php");
//        $hrm = new hrm($this->db);
//        $arrFerie = $hrm->jourFerie();
        $db = $this->db;
        $requete = "SELECT unix_timestamp(" . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa.datedeb) as datedebF,
                           unix_timestamp(" . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa.datefin) as datefinF,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources.cout,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa.fk_user_imputation,
                           ifnull(" . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa.fk_projet_task,-1) as taskId,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources.nom,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources.fk_resa_type,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources.id,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.title as ttitle
                      FROM " . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task
                     WHERE " . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa.fk_ressource = " . MAIN_DB_PREFIX . "Synopsis_global_ressources.id
                       AND fk_projet_task = " . $taskId . "
                       AND fk_projet_task = " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid";
        $sql = $db->query($requete);
        $arrayByDay = array();
        $totalDur = 0;
        $totalCnt = 0;

        if ($conf->global->ALLDAY == 0)
            die("Horraire de travaille non configurée <a href='config.php'>Config</a>");

        while ($res = $db->fetch_object($sql)) {
            $array = array();
            $array['totalCostForRessources'] = 0;
            $array['totalDur'] = 0;
            for ($j = $res->datedebF; $j <= $res->datefinF; $j += 3600 * 24) {
                $currentDay = mktime(0, 0, 0, date('m', $j), date('d', $j), date('Y', $j));
                if (date('N', $j) < 6) { //pas un week end
                    //ajouter si pas un jour ferie
                    $pasFerie = $this->isFerie($j);
                    if ($pasFerie) {
                        $nextDate = 0;
                        if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/", date("d/m/Y", $j), $arr)) {
                            $nextDate = mktime(0, 0, 0, $arr[2], $arr[1], $arr[3]) + 24 * 3600;
                        }
                        if ($nextDate > $res->datefinF)
                            $nextDate = $res->datefinF;
                        $durationPerDay = $nextDate - $j;
                        //                            print $nextDate."\n".$res1->datefinF."\n".$durationPerDay / 3600 ."\n";
                        //Prob timezone
                        if ($durationPerDay > $conf->global->ALLDAY * 3600)//On fait la journée compléte
                            $dureeEnHeure = $conf->global->ALLDAY * 3600;
                        else
                            $dureeEnHeure = $durationPerDay;
                        switch ($res->fk_resa_type) {
                            case 1://Prix par heure
                                $tmpCout = $res->cout * $dureeEnHeure / 3600; // le cout est un cout horaire
                                break;
                            case 2://Prix par demi journée
                                if ($dureeEnHeure > $conf->global->HALFDAY * 3600) {//2 demi journée
                                    $tmpCout = $res->cout * 2;
                                } else {//1Demi j
                                    $tmpCout = $res->cout;
                                }
                                break;
                            case 3:
                            default://Prix par jour
                                $tmpCout = $res->cout;
                                break;
                        }
                        $tmpCount = $dureeEnHeure;
                        $array['totalDur'] +=$tmpCount;
                        $array['totalCostForRessources'] += $tmpCout; // le cout est un cout horaire
                        $this->costByDay["task"][$taskId]['Ressource'][$currentDay]['totalDur'] += $tmpCount;
                        $this->costByDay["task"][$taskId]['Ressource'][$currentDay]['totalCostForRessources'] += $tmpCout;
                        $this->arrCostRessourcePerDayPerTask[$currentDay]['count'] += $tmpCount;
                        $this->arrCostRessourcePerDayPerTask[$currentDay]['cout'] += $tmpCout;
                        $this->arrCostAllRessourcePerDay[$currentDay]['count'] += $tmpCount;
                        $this->arrCostAllRessourcePerDay[$currentDay]['cout'] += $tmpCout;
                        /* if ($durationPerDay > $conf->global->ALLDAY * 3600) {
                          switch ($res->fk_resa_type) {
                          case 1:
                          $array['totalDur'] +=$conf->global->ALLDAY * 3600;
                          $array['totalCostForRessources'] += $res->cout * $conf->global->ALLDAY; // le cout est un cout horaire
                          $this->costByDay["task"][$taskId]['Ressource'][$currentDay]['totalDur'] += $conf->global->ALLDAY * 3600;
                          $this->costByDay["task"][$taskId]['Ressource'][$currentDay]['totalCostForRessources'] += $res->cout * $conf->global->ALLDAY; // le cout est un cout horaire
                          $this->arrCostRessourcePerDayPerTask[$currentDay]['count'] += $conf->global->ALLDAY * 3600;
                          $this->arrCostRessourcePerDayPerTask[$currentDay]['cout'] += $res->cout * $conf->global->ALLDAY;
                          $this->arrCostAllRessourcePerDay[$currentDay]['count'] += $conf->global->ALLDAY * 3600;
                          $this->arrCostAllRessourcePerDay[$currentDay]['cout'] += $res->cout * $conf->global->ALLDAY;
                          break;
                          case 2:
                          $array['totalDur'] += $conf->global->HALFDAY * 2 * 3600; // le cout est un cout par 1/2j
                          $array['totalCostForRessources'] += $res->cout * 2;
                          $this->costByDay["task"][$taskId]['Ressource'][$currentDay]['totalDur'] += $conf->global->HALFDAY * 2 * 3600;
                          $this->costByDay["task"][$taskId]['Ressource'][$currentDay]['totalCostForRessources'] += $res->cout * 2; // le cout est un cout horaire
                          $this->arrCostRessourcePerDayPerTask[$currentDay]['count'] += $conf->global->HALFDAY * 2 * 3600;
                          $this->arrCostRessourcePerDayPerTask[$currentDay]['cout'] += $res->cout * 2;
                          $this->arrCostAllRessourcePerDay[$currentDay]['count'] += $conf->global->HALFDAY * 2 * 3600;
                          $this->arrCostAllRessourcePerDay[$currentDay]['cout'] += $res->cout * 2;

                          break;
                          case 3:
                          default:
                          $array['totalDur'] += $conf->global->ALLDAY * 3600; // le cout est un cout parj
                          $array['totalCostForRessources'] += $res->cout;
                          $this->costByDay["task"][$taskId]['Ressource'][$currentDay]['totalDur'] += $conf->global->ALLDAY * 3600;
                          $this->costByDay["task"][$taskId]['Ressource'][$currentDay]['totalCostForRessources'] += $res->cout; // le cout est un cout horaire
                          $this->arrCostRessourcePerDayPerTask[$currentDay]['count'] += $conf->global->ALLDAY;
                          $this->arrCostRessourcePerDayPerTask[$currentDay]['cout'] += $res->cout;
                          $this->arrCostAllRessourcePerDay[$currentDay]['count'] += $conf->global->ALLDAY;
                          $this->arrCostAllRessourcePerDay[$currentDay]['cout'] += $res->cout;
                          break;
                          }
                          } else {
                          switch ($res->fk_resa_type) {
                          case 1:
                          $array['totalDur'] += $durationPerDay;
                          $array['totalCostForRessources'] += $res->cout * $durationPerDay / 3600;
                          $this->costByDay["task"][$taskId]['Ressource'][$currentDay]['totalDur'] += $durationPerDay;
                          $this->costByDay["task"][$taskId]['Ressource'][$currentDay]['totalCostForRessources'] += $res->cout * $durationPerDay / 3600; // le cout est un cout horaire
                          $this->arrCostRessourcePerDayPerTask[$currentDay]['count'] += $durationPerDay;
                          $this->arrCostRessourcePerDayPerTask[$currentDay]['cout'] += $res->cout * $durationPerDay / 3600;
                          $this->arrCostAllRessourcePerDay[$currentDay]['count'] += $durationPerDay;
                          $this->arrCostAllRessourcePerDay[$currentDay]['cout'] += $res->cout * $durationPerDay / 3600;

                          break;
                          case 2:
                          $array['totalDur'] += $conf->global->HALFDAY * 2 * 3600;
                          $array['totalCostForRessources'] += $res->cout * 2;
                          $this->costByDay["task"][$taskId]['Ressource'][$currentDay]['totalDur'] += $conf->global->HALFDAY * 2 * 3600;
                          $this->costByDay["task"][$taskId]['Ressource'][$currentDay]['totalCostForRessources'] += $res->cout * 2;
                          $this->arrCostRessourcePerDayPerTask[$currentDay]['count'] += $conf->global->HALFDAY * 2 * 3600;
                          $this->arrCostRessourcePerDayPerTask[$currentDay]['cout'] += $res->cout * 2;
                          $this->arrCostAllRessourcePerDay[$currentDay]['count'] += $conf->global->HALFDAY * 2 * 3600;
                          $this->arrCostAllRessourcePerDay[$currentDay]['cout'] += $res->cout * 2;
                          break;
                          case 3:
                          default:
                          $array['totalDur'] += $conf->global->ALLDAY * 3600;
                          $array['totalCostForRessources'] += $res->cout;
                          $this->costByDay["task"][$taskId]['Ressource'][$currentDay]['totalDur'] += $conf->global->ALLDAY * 3600;
                          $this->costByDay["task"][$taskId]['Ressource'][$currentDay]['totalCostForRessources'] += $res->cout;
                          $this->arrCostRessourcePerDayPerTask[$currentDay]['count'] += $conf->global->ALLDAY * 3600;
                          $this->arrCostRessourcePerDayPerTask[$currentDay]['cout'] += $res->cout;
                          $this->arrCostAllRessourcePerDay[$currentDay]['count'] += $conf->global->ALLDAY * 3600;
                          $this->arrCostAllRessourcePerDay[$currentDay]['cout'] += $res->cout;
                          break;
                          }
                          } */
                    }
                }
            }//fin de pour chaque jour // TODO mettre dans la requete
            $title = $res->ttitle;

            $this->CostRessourceByTask[$res->taskId][$res->fk_user_imputation]['title'] = $title;
            $this->CostRessourceByTask[$res->taskId][$res->fk_user_imputation]['duration'] += $array['totalDur'];
            $this->CostRessourceByTask[$res->taskId][$res->fk_user_imputation]['totalCostForRessources'] += $array['totalCostForRessources'];
            $this->CostRessourceByUser[$res->fk_user_imputation][$res->taskId]['title'] = $title;
            $this->CostRessourceByUser[$res->fk_user_imputation][$res->taskId]['duration'] += $array['totalDur'];
            $this->CostRessourceByUser[$res->fk_user_imputation][$res->taskId]['totalCostForRessources'] += $array['totalCostForRessources'];
            $this->CostByRessource[$res->id]['duration'] += $array['totalDur'];
            $this->CostByRessource[$res->id]['totalCostForRessources'] += $array['totalCostForRessources'];
            $this->CostByRessource[$res->id]['nom'] = $res->nom;
            $totalCnt += $array['totalCostForRessources'];
            $totalDur += $array['totalDur'];
        }//fin de pour chaque ressource
        $this->cost["task"][$taskId]['coutRessource'] = $totalCnt;
        $this->cost["task"][$taskId]['durRessource'] = $totalDur;
        return ($totalCnt);
    }

    public $arrCostRessourcePerDayPerTaskEffective = array();
    public $CostRessourceByUserEffective = array();
    public $CostRessourceByTaskEffective = array();
    public $CostByRessourceEffectiv = array();

    public function costTaskRessourceEffective($taskId) {
        global $conf;
//        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Hrm/hrm.class.php");
//        $hrm = new hrm($this->db);
//        $arrFerie = $hrm->jourFerie();
        $db = $this->db;
        $requete = "SELECT unix_timestamp(" . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa.datedeb) as datedebF,
                           unix_timestamp(" . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa.datefin) as datefinF,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources.cout,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa.fk_user_imputation,
                           ifnull(" . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa.fk_projet_task,-1) as taskId,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources.nom,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources.fk_resa_type,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources.id,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.title as ttitle
                      FROM " . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task
                     WHERE " . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa.fk_ressource = " . MAIN_DB_PREFIX . "Synopsis_global_ressources.id
                       AND fk_projet_task = " . $taskId . "
                       AND fk_projet_task = " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid
                       AND " . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa.datefin< now()";
        $sql = $db->query($requete);
        $array = array();
        $array['totalCostForRessources'] = 0;
        $array['totalDur'] = 0;
        $arrayByDay = array();
        $totalDur = 0;
        $totalCnt = 0;

        //print $requete;
        $iter = 0;
        $count = 0;
        $cout = 0;
        while ($res = $db->fetch_object($sql)) {
            for ($j = $res->datedebF; $j <= $res->datefinF; $j += 3600 * 24) {
                $currentDay = mktime(0, 0, 0, date('m', $j), date('d', $j), date('Y', $j));
                if (date('N', $j) < 6) { //pas un week end
                    //ajouter si pas un jour ferie
                    $pasFerie = $this->isFerie($j);
                    if ($pasFerie) {
                        $nextDate = 0;
                        if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/", date("d/m/Y", $j), $arr)) {
                            $nextDate = mktime(0, 0, 0, $arr[2], $arr[1], $arr[3]) + 24 * 3600;
                        }
                        if ($nextDate > $res->datefinF)
                            $nextDate = $res->datefinF;
                        $durationPerDay = $nextDate - $j;
                        //                            print $nextDate."\n".$res1->datefinF."\n".$durationPerDay / 3600 ."\n";
                        //Prob timezone
                        if ($durationPerDay > $conf->global->ALLDAY * 3600) {
                            switch ($res->fk_resa_type) {
                                case 1:
                                    $array['totalDur'] +=$conf->global->ALLDAY * 3600;
                                    $array['totalCostForRessources'] += $res->cout * $conf->global->ALLDAY; // le cout est un cout horaire
                                    $this->costByDayEffective["task"][$taskId]['Ressource'][$currentDay]['totalDur'] += $conf->global->ALLDAY * 3600;
                                    $this->costByDayEffective["task"][$taskId]['Ressource'][$currentDay]['totalCostForRessources'] += $res->cout * $conf->global->ALLDAY; // le cout est un cout horaire
                                    $this->arrCostRessourcePerDayPerTaskEffective[$currentDay]['count'] += $conf->global->ALLDAY * 3600;
                                    $this->arrCostRessourcePerDayPerTaskEffective[$currentDay]['cout'] += $res->cout * $conf->global->ALLDAY;
                                    $this->arrCostAllRessourcePerDayEffective[$currentDay]['count'] += $conf->global->ALLDAY * 3600;
                                    $this->arrCostAllRessourcePerDayEffective[$currentDay]['cout'] += $res->cout * $conf->global->ALLDAY;
                                    break;
                                case 2:
                                    $array['totalDur'] += $conf->global->HALFDAY * 2 * 3600; // le cout est un cout par 1/2j
                                    $array['totalCostForRessources'] += $res->cout * 2;
                                    $this->costByDayEffective["task"][$taskId]['Ressource'][$currentDay]['totalDur'] += $conf->global->HALFDAY * 2 * 3600;
                                    $this->costByDayEffective["task"][$taskId]['Ressource'][$currentDay]['totalCostForRessources'] += $res->cout * 2; // le cout est un cout horaire
                                    $this->arrCostRessourcePerDayPerTaskEffective[$currentDay]['count'] += $conf->global->HALFDAY * 2 * 3600;
                                    $this->arrCostRessourcePerDayPerTaskEffective[$currentDay]['cout'] += $res->cout * 2;
                                    $this->arrCostAllRessourcePerDayEffective[$currentDay]['count'] += $conf->global->HALFDAY * 2 * 3600;
                                    $this->arrCostAllRessourcePerDayEffective[$currentDay]['cout'] += $res->cout * 2;

                                    break;
                                case 3:
                                default:
                                    $array['totalDur'] += $conf->global->ALLDAY * 3600; // le cout est un cout parj
                                    $array['totalCostForRessources'] += $res->cout;
                                    $this->costByDayEffective["task"][$taskId]['Ressource'][$currentDay]['totalDur'] += $conf->global->ALLDAY * 3600;
                                    $this->costByDayEffective["task"][$taskId]['Ressource'][$currentDay]['totalCostForRessources'] += $res->cout; // le cout est un cout horaire
                                    $this->arrCostRessourcePerDayPerTaskEffective[$currentDay]['count'] += $conf->global->ALLDAY;
                                    $this->arrCostRessourcePerDayPerTaskEffective[$currentDay]['cout'] += $res->cout;
                                    $this->arrCostAllRessourcePerDayEffective[$currentDay]['count'] += $conf->global->ALLDAY;
                                    $this->arrCostAllRessourcePerDayEffective[$currentDay]['cout'] += $res->cout;
                                    break;
                            }
                        } else {
                            switch ($res->fk_resa_type) {
                                case 1:
                                    $array['totalDur'] += $durationPerDay;
                                    $array['totalCostForRessources'] += $res->cout * $durationPerDay / 3600;
                                    $this->costByDayEffective["task"][$taskId]['Ressource'][$currentDay]['totalDur'] += $durationPerDay;
                                    $this->costByDayEffective["task"][$taskId]['Ressource'][$currentDay]['totalCostForRessources'] += $res->cout * $durationPerDay / 3600; // le cout est un cout horaire
                                    $this->arrCostRessourcePerDayPerTaskEffective[$currentDay]['count'] += $durationPerDay;
                                    $this->arrCostRessourcePerDayPerTaskEffective[$currentDay]['cout'] += $res->cout * $durationPerDay / 3600;
                                    $this->arrCostAllRessourcePerDayEffective[$currentDay]['count'] += $durationPerDay;
                                    $this->arrCostAllRessourcePerDayEffective[$currentDay]['cout'] += $res->cout * $durationPerDay / 3600;

                                    break;
                                case 2:
                                    $array['totalDur'] += $conf->global->HALFDAY * 2 * 3600;
                                    $array['totalCostForRessources'] += $res->cout * 2;
                                    $this->costByDayEffective["task"][$taskId]['Ressource'][$currentDay]['totalDur'] += $conf->global->HALFDAY * 2 * 3600;
                                    $this->costByDayEffective["task"][$taskId]['Ressource'][$currentDay]['totalCostForRessources'] += $res->cout * 2;
                                    $this->arrCostRessourcePerDayPerTaskEffective[$currentDay]['count'] += $conf->global->HALFDAY * 2 * 3600;
                                    $this->arrCostRessourcePerDayPerTaskEffective[$currentDay]['cout'] += $res->cout * 2;
                                    $this->arrCostAllRessourcePerDayEffective[$currentDay]['count'] += $conf->global->HALFDAY * 2 * 3600;
                                    $this->arrCostAllRessourcePerDayEffective[$currentDay]['cout'] += $res->cout * 2;
                                    break;
                                case 3:
                                default:
                                    $array['totalDur'] += $conf->global->ALLDAY * 3600;
                                    $array['totalCostForRessources'] += $res->cout;
                                    $this->costByDayEffective["task"][$taskId]['Ressource'][$currentDay]['totalDur'] += $conf->global->ALLDAY * 3600;
                                    $this->costByDayEffective["task"][$taskId]['Ressource'][$currentDay]['totalCostForRessources'] += $res->cout;
                                    $this->arrCostRessourcePerDayPerTaskEffective[$currentDay]['count'] += $conf->global->ALLDAY * 3600;
                                    $this->arrCostRessourcePerDayPerTaskEffective[$currentDay]['cout'] += $res->cout;
                                    $this->arrCostAllRessourcePerDayEffective[$currentDay]['count'] += $conf->global->ALLDAY * 3600;
                                    $this->arrCostAllRessourcePerDayEffective[$currentDay]['cout'] += $res->cout;
                                    break;
                            }
                        }
                    }
                }
            }//fin de pour chaque jour // TODO mettre dans la requete
            $title = $res->ttitle;

            $this->CostRessourceByTaskEffective[$res->taskId][$res->fk_user_imputation]['title'] = $title;
            $this->CostRessourceByTaskEffective[$res->taskId][$res->fk_user_imputation]['duration'] += $array['totalDur'];
            $this->CostRessourceByTaskEffective[$res->taskId][$res->fk_user_imputation]['totalCostForRessources'] += $array['totalCostForRessources'];
            $this->CostRessourceByUserEffective[$res->fk_user_imputation][$res->taskId]['title'] = $title;
            $this->CostRessourceByUserEffective[$res->fk_user_imputation][$res->taskId]['duration'] += $array['totalDur'];
            $this->CostRessourceByUserEffective[$res->fk_user_imputation][$res->taskId]['totalCostForRessources'] += $array['totalCostForRessources'];
            $this->CostByRessourceEffective[$res->id]['duration'] += $array['totalDur'];
            $this->CostByRessourceEffective[$res->id]['totalCostForRessources'] += $array['totalCostForRessources'];
            $this->CostByRessourceEffective[$res->id]['nom'] = $res->nom;
            $totalCnt += $array['totalCostForRessources'];
            $totalDur += $array['totalDur'];
        }//fin de pour chaque ressource
        $this->costEffective["task"][$taskId]['coutRessource'] = $totalCnt;
        $this->costEffective["task"][$taskId]['durRessource'] = $totalDur;
        return ($totalCnt);
    }

    public function costTaskFraisProjet($taskId) {
        $somme = 0;
        $requete = "SELECT SUM(montantHT) as somme
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_frais
                     WHERE fk_task =" . $taskId;
        $sql = $this->db->query($requete);
        if ($sql) {
            while ($res = $this->db->fetch_object($sql)) {
                $somme = $res->somme;
            }
        }
        $this->cost["task"][$taskId]['FraisProjet'] = $somme;

        $requete = "SELECT SUM(montantHT) as somme, unix_timestamp(dateAchat) as FdateAchat
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_frais
                     WHERE fk_task =" . $taskId . "
                  GROUP BY day(dateAchat), week(dateAchat), year(dateAchat)";
        $sql = $this->db->query($requete);
        //print $requete ;
        $arrayByDay = array();
        if ($sql) {
            while ($res = $this->db->fetch_object($sql)) {
                $arrayByDay[$res->FdateAchat] = $res->somme;
            }
        }
        $this->costByDay["task"][$taskId]['FraisProjet'] = $arrayByDay;

        return ($somme);
    }

    public function costTaskFraisProjetEffective($taskId) {
        $somme = 0;
        $requete = "SELECT SUM(montantHT) as somme
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_frais
                     WHERE fk_task =" . $taskId . " AND dateAchat < now()";
        $sql = $this->db->query($requete);
        if ($sql) {
            while ($res = $this->db->fetch_object($sql)) {
                $somme = $res->somme;
            }
        }
        $this->costEffective["task"][$taskId]['FraisProjet'] = $somme;

        $requete = "SELECT SUM(montantHT) as somme, unix_timestamp(dateAchat) as FdateAchat
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_frais
                     WHERE fk_task =" . $taskId . " and dateAchat < now()
                  GROUP BY day(dateAchat), week(dateAchat), year(dateAchat)";
        $sql = $this->db->query($requete);
        //print $requete ;
        $arrayByDay = array();
        if ($sql) {
            while ($res = $this->db->fetch_object($sql)) {
                $arrayByDay[$res->FdateAchat] = $res->somme;
            }
        }
        $this->costByDayEffective["task"][$taskId]['FraisProjet'] = $arrayByDay;

        return ($somme);
    }

    public function costTask($taskId) {
        $somme = 0;
        $somme1 = 0;
        $somme += $this->costTaskRessource($taskId);
        $somme += $this->costTaskFraisProjet($taskId);
        $somme += $this->costTaskRH($taskId);
        $somme1 += $this->costTaskRessourceEffective($taskId);
        $somme1 += $this->costTaskFraisProjetEffective($taskId);
        $somme1 += $this->costTaskRHEffective($taskId);
        $this->cost["task"][$taskId]['total'] = $somme;
        $this->costEffective["task"][$taskId]['total'] = $somme1;
        return ($somme);
    }

    public $arrCostRessourcePerDay = array();
    public $arrCostAllRessourcePerDay = array();

    public function costProjetRessource($conf) {
//        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Hrm/hrm.class.php");
//        $hrm = new hrm($this->db);
//        $arrFerie = $hrm->jourFerie();
        $db = $this->db;
        $requete = "SELECT unix_timestamp(" . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa.datedeb) as datedebF,
                           unix_timestamp(" . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa.datefin) as datefinF,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources.cout,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa.fk_user_imputation,
                           ifnull(" . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa.fk_projet_task,-1) as taskId,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources.nom,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources.fk_resa_type,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources.id
                      FROM " . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa,
                           " . MAIN_DB_PREFIX . "Synopsis_global_ressources
                     WHERE " . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa.fk_ressource = " . MAIN_DB_PREFIX . "Synopsis_global_ressources.id
                       AND fk_projet = " . $this->id . " AND fk_projet_task is null";
        $sql = $db->query($requete);
        $totalDur = 0;
        $totalCnt = 0;
        $title = "Global";
        while ($res = $db->fetch_object($sql)) {
            for ($j = $res->datedebF; $j <= $res->datefinF; $j += 3600 * 24) {
                if (date('N', $j) < 6) { //pas un week end
                    //ajouter si pas un jour ferie
                    $pasFerie = $this->isFerie($j);
                    if ($pasFerie) {
                        $nextDate = 0;
                        $curdate = mktime(0, 0, 0, date('m', $j), date('d', $j), date('Y', $j));
                        if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/", date("d/m/Y", $j), $arr)) {
                            $nextDate = mktime(0, 0, 0, $arr[2], $arr[1], $arr[3]) + 24 * 3600;
                        }
                        if ($nextDate > $res->datefinF)//Il reste - de 24 heures
                            $nextDate = $res->datefinF;
                        $durationPerDay = $nextDate - $j;
                        //                            print $nextDate."\n".$res1->datefinF."\n".$durationPerDay / 3600 ."\n";
                        //Prob timezone
                        if ($durationPerDay > $conf->global->ALLDAY * 3600)//On fait la journée compléte
                            $dureeEnHeure = $conf->global->ALLDAY * 3600;
                        else
                            $dureeEnHeure = $durationPerDay;
                        switch ($res->fk_resa_type) {
                            case 1://Prix par heure
                                $tmpCout = $res->cout * $dureeEnHeure / 3600; // le cout est un cout horaire
                                break;
                            case 2://Prix par demi journée
                                if ($dureeEnHeure > $conf->global->HALFDAY * 3600) {//2 demi journée
                                    $tmpCout = $res->cout * 2;
                                } else {//1Demi j
                                    $tmpCout = $res->cout;
                                }
                                break;
                            case 3:
                            default://Prix par jour
                                $tmpCout = $res->cout;
                                break;
                        }
                        $tmpCount = $dureeEnHeure;

                        $this->updateArrCoutRessource($curdate, $tmpCout, $tmpCount, $title, $res);
                        $totalCnt += $tmpCount;
                        $totalDur += $tmpCout;
                    }
                }
            }//fin de pour chaque jour
            //calcul la duree
            //$array['totalDur'] += $count;
            //calcul du cout de la ressource
            //$array['totalCostForRessources'] += $cout;
        }//fin de pour chaque ressource
        $this->cost["projet"]['coutRessource'] = $totalDur;
        $this->cost["projet"]['durRessource'] = $totalCnt;



        return ($arr['totalCostForRessources']);
    }

    private function updateArrCoutRessource($curdate, $tmpCout, $tmpCount, $title, $res) {
        $this->arrCostRessourcePerDay[$curdate]['count'] += $tmpCount;
        $this->arrCostRessourcePerDay[$curdate]['cout'] += $tmpCout;
        $this->arrCostAllRessourcePerDay[$curdate]['count'] += $tmpCount;
        $this->arrCostAllRessourcePerDay[$curdate]['cout'] += $tmpCout;
        $this->costByDay["projet"][$this->id]['Ressource'][$curdate]['totalDur']+=$tmpCount;
        $this->costByDay["projet"][$this->id]['Ressource'][$curdate]['totalCostForRessources']+=$tmpCout;
        $this->CostRessourceByTask[-1][$res->fk_user_imputation]['title'] = $title;
        $this->CostRessourceByTask[-1][$res->fk_user_imputation]['duration'] += $tmpCount;
        $this->CostRessourceByTask[-1][$res->fk_user_imputation]['totalCostForRessources'] += $tmpCout;
        $this->CostRessourceByUser[$res->fk_user_imputation][-1]['title'] = $title;
        $this->CostRessourceByUser[$res->fk_user_imputation][-1]['duration'] += $tmpCount;
        $this->CostRessourceByUser[$res->fk_user_imputation][-1]['totalCostForRessources'] += $tmpCout;
        $this->CostByRessource[$res->id]['duration']+=$tmpCount;
        $this->CostByRessource[$res->id]['totalCostForRessources']+=$tmpCout;
        $this->CostByRessource[$res->id]['nom'] = $res->nom;
    }

    public function costProject() {
        //c'est le cout de toutes les taches + le cout du projet
        //tache
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task WHERE fk_projet = " . $this->id;
        $sql = $this->db->query($requete);
        $somme = 0;
        if ($sql) {
            while ($res = $this->db->fetch_object($sql)) {
                $somme += $this->costTask($res->rowid);
            }
        }
        $this->cost["projet"]['totalTask'] = $somme;
        //Projet = frais de projet + frais de resource
        //frais de projet
        $requete = "SELECT sum(montant_ht) as somme
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_frais
                     WHERE fk_task is null
                       AND fk_projet = " . $this->id;
        $sql = $this->db->query($requete);
        $this->cost["projet"]['fraisProjet'] = 0;
        if ($sql) {
            $somme += $res->somme;
            $this->cost["projet"]['fraisProjet'] = $res->somme;
        }
        //Ressource
        $sommeRessource = $this->costProjetRessource();
        $this->cost["projet"]['ressourceGlobale'] = $sommeRessource;
        $this->cost["projet"]['total'] = $somme + $sommeRessource;
    }

    public function getLibStatut($mode = 0) {
        return $this->LibStatut($this->statut, $mode);
    }

    /**
     *        \brief      Renvoi le libelle d'un statut donne
     *        \param      statut        id statut
     *        \param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *        \return     string        Libelle
     */
    private function LibStatut($statut = 0, $mode = 1) {
        global $langs;
        $langs->load("synopsisproject@synopsisprojet");
        if ($mode == 0) {
            return $this->labelstatut[$statut];
        }
        if ($mode == 1) {
            return $this->labelstatut_short[$statut];
        }
        if ($mode == 2) {
            if ($statut == 0)
                return img_picto($langs->trans('ProjectStatusDraftShort'), 'statut0') . ' ' . $this->labelstatut_short[$statut];
            if ($statut == 5)
                return img_picto($langs->trans('ProjectStatusPlanningShort'), 'statut1') . ' ' . $this->labelstatut_short[$statut];
            if ($statut == 10)
                return img_picto($langs->trans('ProjectStatusRunningShort'), 'statut3') . ' ' . $this->labelstatut_short[$statut];
            if ($statut == 50)
                return img_picto($langs->trans('ProjectStatusCloseShort'), 'statut5') . ' ' . $this->labelstatut_short[$statut];
            if ($statut == 999)
                return img_picto($langs->trans('ProjectStatusWaitingValidShort'), 'statut8', 'style="vertical-align:middle;"') . ' ' . $this->labelstatut_short[$statut];
        }
        if ($mode == 3) {
            if ($statut == 0)
                return img_picto($langs->trans('ProjectStatusDraftShort'), 'statut0');
            if ($statut == 5)
                return img_picto($langs->trans('ProjectStatusPlanningShort'), 'statut1');
            if ($statut == 10)
                return img_picto($langs->trans('ProjectStatusRunningShort'), 'statut3');
            if ($statut == 50)
                return img_picto($langs->trans('ProjectStatusCloseShort'), 'statut5');
            if ($statut == 999)
                return img_picto($langs->trans('ProjectStatusWaitingValidShort'), 'statut8', 'style="vertical-align:middle;"');
        }
        if ($mode == 4) {
            if ($statut == 0)
                return img_picto($langs->trans('ProjectStatusDraft'), 'statut0') . ' ' . $this->labelstatut[$statut];
            if ($statut == 5)
                return img_picto($langs->trans('ProjectStatusPlanning'), 'statut1') . ' ' . $this->labelstatut[$statut];
            if ($statut == 10)
                return img_picto($langs->trans('ProjectStatusRunning'), 'statut3') . ' ' . $this->labelstatut[$statut];
            if ($statut == 50)
                return img_picto($langs->trans('ProjectStatusClose'), 'statut5') . ' ' . $this->labelstatut[$statut];
            if ($statut == 999)
                return img_picto($langs->trans('ProjectStatusWaitingValid'), 'statut8', 'style="vertical-align:middle;"') . ' ' . $this->labelstatut[$statut];
        }
        if ($mode == 5) {
            if ($statut == 0)
                return $this->labelstatut_short[$statut] . ' ' . img_picto($langs->trans('ProjectStatusDraftShort'), 'statut0');
            if ($statut == 5)
                return $this->labelstatut_short[$statut] . ' ' . img_picto($langs->trans('ProjectStatusPlanningShort'), 'statut1');
            if ($statut == 10)
                return $this->labelstatut_short[$statut] . ' ' . img_picto($langs->trans('ProjectStatusRunningShort'), 'statut3');
            if ($statut == 50)
                return $this->labelstatut_short[$statut] . ' ' . img_picto($langs->trans('ProjectStatusCloseShort'), 'statut5');
            if ($statut == 999)
                return $this->labelstatut_short[$statut] . ' ' . img_picto($langs->trans('ProjectStatusWaitingValidShort'), 'statut8', 'style="vertical-align:middle;"');
        }
        if ($mode == 6) {
            if ($statut == 0)
                return $this->labelstatut[$statut] . ' ' . img_picto($langs->trans('ProjectStatusDraft'), 'statut0');
            if ($statut == 5)
                return $this->labelstatut[$statut] . ' ' . img_picto($langs->trans('ProjectStatusPlanning'), 'statut1');
            if ($statut == 10)
                return $this->labelstatut[$statut] . ' ' . img_picto($langs->trans('ProjectStatusRunning'), 'statut3');
            if ($statut == 50)
                return $this->labelstatut[$statut] . ' ' . img_picto($langs->trans('ProjectStatusClose'), 'statut5');
            if ($statut == 999)
                return $this->labelstatut[$statut] . ' ' . img_picto($langs->trans('ProjectStatusWaitingValid'), 'statut8', 'style="vertical-align:middle;"');
        }
    }

    public function initAsSpecimen() {
        
    }

    public function getNextNumRef($soc) {
        global $db, $langs, $conf;
        $langs->load("synopsisproject@synopsisprojet");

        $dir = DOL_DOCUMENT_ROOT . "/core/modules/synopsis_projet";

        if (!empty($conf->global->PROJET_ADDON)) {
            $file = $conf->global->PROJET_ADDON . ".php";

            // Chargement de la classe de numerotation
            $classname = $conf->global->PROJET_ADDON;
            $result = include_once($dir . '/' . $file);
            if ($result) {
                $obj = new $classname();
                $numref = "";
                $numref = $obj->getNextValue($soc, $this);

                if ($numref != "") {
                    return $numref;
                } else {
                    dol_print_error($db, "Projet::getNextNumRef " . $obj->error);
                    return "";
                }
            } else {
                print $langs->trans("Error") . " " . $langs->trans("Error_PROJET_ADDON_NotDefined");
                return "";
            }
        } else {
            print $langs->trans("Error") . " " . $langs->trans("Error_PROJET_ADDON_NotDefined");
            return "";
        }
    }

    public function sec2time($sec) {
        if (!is_numeric($sec)) {
            $sec = 0;
        }
        $returnstring = " ";
        $days = intval($sec / 86400);
        $hours = intval(($sec / 3600) - ($days * 24));
        $minutes = intval(($sec - (($days * 86400) + ($hours * 3600))) / 60);
        $seconds = $sec - ( ($days * 86400) + ($hours * 3600) + ($minutes * 60));

        $returnstring .= ($days) ? (($days == 1) ? "1 j" : $days . "j") : "";
        $returnstring .= ($days && $hours && !$minutes && !$seconds) ? "" : "";
        $returnstring .= ($hours) ? ( ($hours == 1) ? " 1h" : " " . $hours . "h") : "";
        $returnstring .= (($days || $hours) && ($minutes && !$seconds)) ? "  " : " ";
        $returnstring .= ($minutes) ? ( ($minutes == 1) ? " 1 min" : " " . $minutes . "min") : "";
        //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
        //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
        return ($returnstring);
    }

    public function sec2hour($sec) {
        if (!is_numeric($sec)) {
            $sec = 0;
        }
        $days = false;
        $returnstring = " ";
        $hours = intval(($sec / 3600));
        $minutes = intval(($sec - ( ($hours * 3600))) / 60);
        $seconds = $sec - ( ($hours * 3600) + ($minutes * 60));

        $returnstring .= ($days) ? (($days == 1) ? "1 j" : $days . "j") : "";
        $returnstring .= ($days && $hours && !$minutes && !$seconds) ? "" : "";
        $returnstring .= ($hours) ? ( ($hours == 1) ? " 1h" : " " . $hours . "h") : "";
        $returnstring .= (($days || $hours) && ($minutes && !$seconds)) ? "  " : " ";
        $returnstring .= ($minutes) ? ( ($minutes == 1) ? " 1 min" : " " . $minutes . "min") : "";
        //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
        //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
        return ($returnstring);
    }

}

class SynopsisProjectTask extends commonObject {

    public $db;
    public $id;
    public $fk_projet;
    public $fk_task_parent;
    public $title;
    public $duration_effective;
    public $user_creat;
    public $statut;
    public $note;
    public $progress;
    public $description;
    public $task_type;
    public $color;
    public $url;
    public $fk_task_type;
    public $shortDesc;
    public $level;
    public $dateDeb;
    public $dateDebFR;
    public $dateDebFRFull;
    public $projet_id;
    public $labelstatut = array();
    public $labelstatut_short = array();

    public function __construct($db) {
        $this->db = $db;
        global $langs;
        $langs->load("synopsisproject@synopsisprojet");
        $this->labelstatut['open'] = $langs->trans("Open");
        $this->labelstatut["closed"] = $langs->trans("Closed");
        $this->labelstatut_short["open"] = $langs->trans("Open");
        $this->labelstatut_short["closed"] = $langs->trans("Closed");
    }

    public function fetch($id) {
        $this->id = $id;
        $requete = "SELECT " . MAIN_DB_PREFIX . "Synopsis_projet_task.fk_projet,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.fk_task_parent,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.title,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.duration_effective,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.duration,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.fk_user_creat,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.statut,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.note,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.progress,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.description,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.color,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.url,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.fk_task_type,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.shortDesc,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.level,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.dateDeb,
                           " . MAIN_DB_PREFIX . "Synopsis_task_type.label
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task,
                           " . MAIN_DB_PREFIX . "Synopsis_task_type
                     WHERE rowid = " . $id . "
                       AND " . MAIN_DB_PREFIX . "Synopsis_task_type.id = " . MAIN_DB_PREFIX . "Synopsis_projet_task.fk_task_type";
        $sql = $this->db->query($requete);
        if ($sql) {
            $res = $this->db->fetch_object($sql);

            $this->fk_projet = $res->fk_projet;
            $this->projet_id = $res->fk_projet;
            $tmpProj = new SynopsisProject($this->db);
            $tmpProj->fetch($res->fk_projet);
            $res->project = $tmpProj;
            $this->dateDeb = strtotime($res->dateDeb);
            $this->dateDebFR = date('d/m/Y', strtotime($res->dateDeb));
            $this->dateDebFRFull = date('d/m/Y H:i', strtotime($res->dateDeb));
            $this->fk_task_parent = $res->fk_task_parent;
            $this->title = $res->title;
            $this->duration_effective = $res->duration_effective;
            $this->duration = $res->duration;
            $this->fk_user_creat = $res->fk_user_creat;
            $tmpUser = new User($this->db);
            $tmpUser->fetch($res->fk_user_creat);
            $this->user_creat = $tmpUser;
            $this->statut = $res->statut;
            $this->note = $res->note;
            $this->progress = $res->progress;
            $this->description = $res->description;
            $this->color = $res->color;
            $this->url = $res->url;
            $this->fk_task_type = $res->fk_task_type;
            $this->task_type = $res->label;
            $this->shortDesc = $res->shortDesc;
            $this->level = $res->level;
            return($this->id);
        } else {
            return -1;
        }
    }

    public $childArray = array();

    public function getChildsTree() {
        $requete = "SELECT " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task
                     WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_task.fk_task_parent = " . $this->id;
        $sql = $this->db->query($requete);
        $count = $this->db->num_rows($sql);
        while ($res = $this->db->fetch_object($sql)) {
            array_push($this->childArray, $res->rowid);
            $this->recursTask($res->rowid);
        }
    }

    public $directChildArray = array();

    public function getDirectChildsTree() {
        $requete = "SELECT " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task
                     WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_task.fk_task_parent = " . $this->id;
        $sql = $this->db->query($requete);
        $count = $this->db->num_rows($sql);
        while ($res = $this->db->fetch_object($sql)) {
            array_push($this->directChildArray, $res->rowid);
        }
    }

    private function recursTask($parentId, $js) {
        $requete = "SELECT " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task
                     WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_task.fk_task_parent = " . $parentId;
        $sql = $this->db->query($requete);
        while ($res = $this->db->fetch_object($sql)) {
            array_push($this->childArray, $res->rowid);
            $this->recursTask($res->rowid);
        }
    }

    /**
     *        \brief      Renvoie nom clicable (avec eventuellement le picto)
     *        \param        withpicto        Inclut le picto dans le lien
     *        \param        option            Sur quoi pointe le lien
     *        \param        maxlen            Longueur max libelle
     *        \return        string            Chaine avec URL
     */
    public function getNomUrl($withpicto = 0, $option = '', $maxlen = 0) {
        global $langs;

        $result = '';
        $lien = "";
        $lien = '<a href="' . DOL_URL_ROOT . '/synopsisprojet/tasks/task.php?id=' . $this->id . '">';
        $lienfin = '</a>';

        if ($withpicto)
            $result.=($lien . img_object($langs->trans("ShowProject") . ': ' . $this->title, 'task') . $lienfin . ' ');
        $result.=$lien . ($maxlen ? dol_trunc($this->title, $maxlen) : $this->title) . $lienfin;
        return $result;
    }

    public function getLibStatut($mode = 0) {
        return $this->LibStatut($this->statut, $mode);
    }

    /**
     *        \brief      Renvoi le libelle d'un statut donne
     *        \param      statut        id statut
     *        \param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *        \return     string        Libelle
     */
    private function LibStatut($statut = 0, $mode = 1) {
        global $langs;
        $langs->load("synopsisproject@synopsisprojet");
        if ($mode == 0) {
            return $this->labelstatut[$statut];
        }
        if ($mode == 1) {
            return $this->labelstatut_short[$statut];
        }
        if ($mode == 2) {
            if ($statut == 'open')
                return img_picto($langs->trans('Open'), 'statut4') . ' ' . $this->labelstatut_short[$statut];
            if ($statut == 'closed')
                return img_picto($langs->trans('Close'), 'statut5') . ' ' . $this->labelstatut_short[$statut];
        }
        if ($mode == 3) {
            if ($statut == 'open')
                return img_picto($langs->trans('Open'), 'statut4');
            if ($statut == 'closed')
                return img_picto($langs->trans('Close'), 'statut5');
        }
        if ($mode == 4) {
            if ($statut == 'open')
                return img_picto($langs->trans('Open'), 'statut4') . ' ' . $this->labelstatut[$statut];
            if ($statut == 'closed')
                return img_picto($langs->trans('Close'), 'statut5') . ' ' . $this->labelstatut[$statut];
        }
        if ($mode == 5) {
            if ($statut == 'open')
                return $this->labelstatut_short[$statut] . ' ' . img_picto($langs->trans('Open'), 'statut4');
            if ($statut == 'closed')
                return $this->labelstatut_short[$statut] . ' ' . img_picto($langs->trans('Close'), 'statut5');
        }
    }

}

class Projet extends SynopsisProject {
    
}
?>