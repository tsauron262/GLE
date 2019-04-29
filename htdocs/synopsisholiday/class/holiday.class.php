<?php

/* Copyright (C) 2011	   Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2012-2014 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012	   Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
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

/*
 * Notes de mise à jour - Synopsis. 
 *      - Ajout de la valeur 6 pour la variable $statut : approbation finale par le DRH.
 *      - Ajout vars:
 *          - $type_conges pour différencier les congés payés ordinaires des rtt et absence exceptionnelle. 
 *          - $fk_user_drh_valid : id drh si validation. 
 *          - $date_drh_valid : date de validation par le DRH
 *          - $fk_actioncomm : id actioncomm pour événement agenda, si statut = 2, 3 ou 6.
 *          - $fk_substitute : id utilisateur remplaçant. 
 *      - Ajout config: 'drhUserId' et 'nbRTTDeducted'
 *      - Ajout méthodes:
 *          - getRTTforUser()
 *          - updateSoldeRTT()
 *          - getTypeLabel()
 *          - onStatusUpdate() // gestion des événements dans l'agenda lors de la màj du statut
 * 
 * Mise à jour 2 : Gestion des utilisateurs multiples pour une même instance Holiday. 
 *      - $this->fk_user peut être un array.
 *      - Ajout variable $fk_group
 */

/**
 *    \file       holiday.class.php
 *    \ingroup    holiday
 *    \brief      Class file of the module paid holiday.
 */
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/holiday/class/holiday.class.php';

/**
 * 	Class of the module paid holiday. Developed by Teclib ( http://www.teclib.com/ )
 */
class SynopsisHoliday extends Holiday {

    public $element = 'holiday';
    public $table_element = 'holiday';
    var $db;
    var $error;
    var $errors = array();
    var $rowid;
    var $ref;
    var $fk_user;
    var $fk_group = '';
    var $date_create = '';
    var $description;
    var $date_debut = '';   // Date start in PHP server TZ
    var $date_fin = '';   // Date end in PHP server TZ
    var $date_debut_gmt = '';  // Date start in GMT
    var $date_fin_gmt = '';  // Date end in GMT
    var $halfday = '';
    var $statut = '';   // 1=draft, 2=validated, 3=approved (par valideur), 6= validé par DRH. (4= annulé, 5= refusé)
    var $fk_validator;
    var $date_valid = '';
    var $date_drh_valid = '';
    var $fk_user_valid;
    var $fk_user_drh_valid;
    var $date_refuse = '';
    var $fk_user_refuse;
    var $date_cancel = '';
    var $fk_user_cancel;
    var $detail_refuse = '';
    var $holiday = array();
    var $events = array();
    var $logs = array();
    var $optName = '';
    var $optValue = '';
    var $optRowid = '';
    var $type_conges = ''; // 0: ordinaires, 1: exceptionnels, 2: rtt.
    var $fk_substitute = '';
    public static $typesConges = array(
        0 => 'Congés payés',
        1 => 'Absence exceptionnelle',
        2 => 'RTT'
    );
    var $fk_actioncomm = '';
    
    static $decalageHeure = 2;

    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function __construct($db) {
        $this->db = $db;
    }

    /**
     * updateSold. Update sold and check table of users for holidays is complete. If not complete.
     *
     * @return	int			Return 1
     */
    function updateSold() {
        // Mets à jour les congés payés et RTT en début de mois
        $this->updateSoldeCP();

        // Mise à jour annuelle si besoin:
        $this->annualUpdateSoldeCP();

        // Vérifie le nombre d'utilisateur et mets à jour si besoin
        $this->verifNbUsers($this->countActiveUsersWithoutCP(), $this->getConfCP('nbUser'));
        return 1;
    }

    /**
     *   Créer un congés payés dans la base de données
     *
     *   @param		User	$user        	User that create
     *   @param     int		$notrigger	    0=launch triggers after, 1=disable triggers
     *   @return    int			         	<0 if KO, Id of created object if OK
     */
    function create($user, $notrigger = 0) {
        global $conf, $langs;
        $error = 0;

        $now = dol_now();

        // Check parameters
        if (is_array($this->fk_user)) {
            if (!count($this->fk_user)) {
                $this->error = "ErrorBadParameter";
                return -1;
            }
        } else if (empty($this->fk_user) || !is_numeric($this->fk_user) || $this->fk_user < 0) {
            $this->error = "ErrorBadParameter";
            return -1;
        }
        if (empty($this->fk_validator) || !is_numeric($this->fk_validator) || $this->fk_validator < 0) {
            $this->error = "ErrorBadParameter";
            return -1;
        }
        if (!array_key_exists($this->type_conges, self::$typesConges)) {
            $this->error = "ErrorBadParameter ";
            return -1;
        }

        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "holiday(";
        $sql.= "fk_user,";
        $sql.= "fk_group,";
        $sql.= "date_create,";
        $sql.= "description,";
        $sql.= "date_debut,";
        $sql.= "date_fin,";
        $sql.= "halfday,";
        $sql.= "statut,";
        $sql.= "fk_validator,";
        $sql.= "type_conges,";
        $sql.= "fk_substitute";
        $sql.= ") VALUES (";

        // User
        if (is_array($this->fk_user))
            $sql.= "NULL,";
        else
            $sql.= "'" . $this->fk_user . "',";
        $sql.= "'" . (!empty($this->fk_group) ? $this->fk_group : 'NULL') . "',";
        $sql.= " '" . $this->db->idate($now) . "',";
        $sql.= " '" . $this->db->escape($this->description) . "',";
        $sql.= " '" . $this->db->idate($this->date_debut) . "',";
        $sql.= " '" . $this->db->idate($this->date_fin) . "',";
        $sql.= " " . $this->halfday . ",";
        $sql.= " '1',";
        $sql.= " '" . $this->fk_validator . "',";
        $sql.= " '" . $this->type_conges . "',";
        $sql.= (isset($this->fk_substitute) && !empty($this->fk_substitute)) ? "'" . (int) $this->fk_substitute . "'" : 'NULL';
        $sql.= ")";

        $this->db->begin();

        dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error) {
            $this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX . "holiday");
            $this->db->commit();
            if (is_array($this->fk_user)) {
                foreach ($this->fk_user as $user_id) {
                    if (is_numeric($user_id) && $user_id >= 0) {
                        $sql2 = 'INSERT INTO ' . MAIN_DB_PREFIX . 'holiday_group(fk_holiday, fk_user) VALUES (' . $this->rowid . ', ' . $user_id . ')';
                        $result = $this->db->query($sql2);
                        if (!$result) {
                            $error++;
                            $this->errors[] = "Erreur: " . $this->db->lasterror();
                        }
                    }
                }
            }
        } else
            $this->db->rollback();

        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
                $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
            }
            return -1 * $error;
        } else
            return $this->rowid;
    }

    /**
     * 	Ajout Synopsis : Charge la liste des utilisateurs pour un congé collecif
     *
     *  @param	int		$holiday_id         Id Holiday
     *  @return int         		 array - Liste des user_id 
     */
    function fetchGroupUsers($holiday_id) {
        $sql = 'SELECT `fk_user` FROM ' . MAIN_DB_PREFIX . 'holiday_group WHERE `fk_holiday`= ' . $holiday_id;
        $result = $this->db->query($sql);
        if ($result) {
            $users = array();
            $num = $this->db->num_rows($result);
            $i = 0;
            if ($num) {
                while ($i < $num) {
                    $obj = $this->db->fetch_object($result);
                    $users[] = $obj->fk_user;
                    $i++;
                }
            }
            return $users;
        } else {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetchGroupUsers " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }
    }

    /**
     * 	Load object in memory from database
     *
     *  @param	int		$id         Id object
     *  @return int         		<0 if KO, >0 if OK
     */
    function fetch($id) {
        global $langs;

        $sql = "SELECT";
        $sql.= " cp.rowid,";
        $sql.= " cp.fk_user,";
        $sql.= " cp.fk_group,";
        $sql.= " cp.date_create,";
        $sql.= " cp.description,";
        $sql.= " cp.date_debut,";
        $sql.= " cp.date_fin,";
        $sql.= " cp.halfday,";
        $sql.= " cp.statut,";
        $sql.= " cp.fk_validator,";
        $sql.= " cp.date_valid,";
        $sql.= " cp.date_drh_valid,";
        $sql.= " cp.fk_user_valid,";
        $sql.= " cp.fk_user_drh_valid,";
        $sql.= " cp.date_refuse,";
        $sql.= " cp.fk_user_refuse,";
        $sql.= " cp.date_cancel,";
        $sql.= " cp.fk_user_cancel,";
        $sql.= " cp.detail_refuse,";
        $sql.= " cp.note_private,";
        $sql.= " cp.note_public,";
        $sql.= " cp.type_conges,";
        $sql.= " cp.fk_actioncomm,";
        $sql.= " cp.fk_substitute";
        $sql.= " FROM " . MAIN_DB_PREFIX . "holiday as cp";
        $sql.= " WHERE cp.rowid = " . $id;

        dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->rowid = $obj->rowid; // deprecated
                $this->ref = $obj->rowid;
                $this->fk_user = $obj->fk_user;
                $this->fk_group = $obj->fk_group;
                $this->date_create = $this->db->jdate($obj->date_create);
                $this->description = $obj->description;
                $this->date_debut = $this->db->jdate($obj->date_debut);
                $this->date_fin = $this->db->jdate($obj->date_fin);
                $this->date_debut_gmt = $this->db->jdate($obj->date_debut, 1);
                $this->date_fin_gmt = $this->db->jdate($obj->date_fin, 1);
                $this->halfday = $obj->halfday;
                $this->statut = $obj->statut;
                $this->fk_validator = $obj->fk_validator;
                $this->date_valid = $this->db->jdate($obj->date_valid);
                $this->date_drh_valid = $this->db->jdate($obj->date_drh_valid);
                $this->fk_user_valid = $obj->fk_user_valid;
                $this->fk_user_drh_valid = $obj->fk_user_drh_valid;
                $this->date_refuse = $this->db->jdate($obj->date_refuse);
                $this->fk_user_refuse = $obj->fk_user_refuse;
                $this->date_cancel = $this->db->jdate($obj->date_cancel);
                $this->fk_user_cancel = $obj->fk_user_cancel;
                $this->detail_refuse = $obj->detail_refuse;
                $this->note_private = $obj->note_private;
                $this->note_public = $obj->note_public;
                $this->type_conges = $obj->type_conges;
                $this->fk_actioncomm = $obj->fk_actioncomm;
                $this->fk_substitute = $obj->fk_substitute;
                if (empty($this->fk_user))
                    $this->fk_user = $this->fetchGroupUsers($this->id);
            }
            $this->db->free($resql);

            return 1;
        } else {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     * 	List holidays for a particular user
     *
     *  @param		int		$user_id    ID of user to list
     *  @param      string	$order      Sort order
     *  @param      string	$filter     SQL Filter
     *  @return     int      			-1 if KO, 1 if OK, 2 if no result
     */
    function fetchByUser($user_id = '', $order = '', $filter = '', $substitute_id = '') {
        global $langs, $conf;

        $sql = "SELECT";
        $sql.= " cp.rowid,";

        $sql.= " cp.fk_user,";
        $sql.= " cp.fk_group,";
        $sql.= " cp.date_create,";
        $sql.= " cp.description,";
        $sql.= " cp.date_debut,";
        $sql.= " cp.date_fin,";
        $sql.= " cp.halfday,";
        $sql.= " cp.statut,";
        $sql.= " cp.fk_validator,";
        $sql.= " cp.date_valid,";
        $sql.= " cp.date_drh_valid,";
        $sql.= " cp.fk_user_valid,";
        $sql.= " cp.fk_user_drh_valid,";
        $sql.= " cp.date_refuse,";
        $sql.= " cp.fk_user_refuse,";
        $sql.= " cp.date_cancel,";
        $sql.= " cp.fk_user_cancel,";
        $sql.= " cp.detail_refuse,";
        $sql.= " cp.type_conges,";
        $sql.= " cp.fk_actioncomm,";
        $sql.= " cp.fk_substitute,";

        $sql.= " uu.lastname as user_lastname,";
        $sql.= " uu.firstname as user_firstname,";

        $sql.= " ua.lastname as validator_lastname,";
        $sql.= " ua.firstname as validator_firstname";

        $sql.= " FROM " . MAIN_DB_PREFIX . "user as uu, " . MAIN_DB_PREFIX . "user as ua, " . MAIN_DB_PREFIX . "holiday as cp";
        if (!empty($user_id))
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . "holiday_group as hg ON cp.rowid = hg.fk_holiday";
        $sql.= " WHERE uu.rowid = " . $user_id . " AND cp.fk_validator = ua.rowid AND (";
        if (!empty($user_id)) {
            $sql.= "hg.fk_user = " . $user_id . " OR ";
            $sql.= "cp.fk_user = " . $user_id;
            if (!empty($substitute_id))
                $sql .= " OR ";
        }
        if (!empty($substitute_id))
            $sql.= "cp.fk_substitute = " . $substitute_id;

        $sql .= ') ';
        // Filtre de séléction
        if (!empty($filter)) {
            $sql.= $filter;
        }

        // Ordre d'affichage du résultat
        if (!empty($order)) {
            $sql.= $order;
        }

        dol_syslog(get_class($this) . "::fetchByUser sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);

        // Si pas d'erreur SQL
        if ($resql) {
            $i = 0;
            $tab_result = $this->holiday;
            $num = $this->db->num_rows($resql);

            // Si pas d'enregistrement
            if (!$num) {
                return 2;
            }

            // Liste les enregistrements et les ajoutent au tableau
            while ($i < $num) {

                $obj = $this->db->fetch_object($resql);

                $tab_result[$i]['rowid'] = $obj->rowid;
                $tab_result[$i]['ref'] = $obj->rowid;
                $tab_result[$i]['fk_user'] = !empty($obj->fk_user) ? $obj->fk_user : $user_id;
                $tab_result[$i]['fk_group'] = $obj->fk_group;
                $tab_result[$i]['date_create'] = $this->db->jdate($obj->date_create);
                $tab_result[$i]['description'] = $obj->description;
                $tab_result[$i]['date_debut'] = $this->db->jdate($obj->date_debut);
                $tab_result[$i]['date_fin'] = $this->db->jdate($obj->date_fin);
                $tab_result[$i]['date_debut_gmt'] = $this->db->jdate($obj->date_debut, 1);
                $tab_result[$i]['date_fin_gmt'] = $this->db->jdate($obj->date_fin, 1);
                $tab_result[$i]['halfday'] = $obj->halfday;
                $tab_result[$i]['statut'] = $obj->statut;
                $tab_result[$i]['fk_validator'] = $obj->fk_validator;
                $tab_result[$i]['date_valid'] = $this->db->jdate($obj->date_valid);
                $tab_result[$i]['date_drh_valid'] = $this->db->jdate($obj->date_drh_valid);
                $tab_result[$i]['fk_user_valid'] = $obj->fk_user_valid;
                $tab_result[$i]['fk_user_drh_valid'] = $obj->fk_user_drh_valid;
                $tab_result[$i]['date_refuse'] = $this->db->jdate($obj->date_refuse);
                $tab_result[$i]['fk_user_refuse'] = $obj->fk_user_refuse;
                $tab_result[$i]['date_cancel'] = $this->db->jdate($obj->date_cancel);
                $tab_result[$i]['fk_user_cancel'] = $obj->fk_user_cancel;
                $tab_result[$i]['detail_refuse'] = $obj->detail_refuse;
                $tab_result[$i]['type_conges'] = $obj->type_conges;
                $tab_result[$i]['fk_actioncomm'] = $obj->fk_actioncomm;
                $tab_result[$i]['fk_substitute'] = $obj->fk_substitute;

                $tab_result[$i]['user_firstname'] = $obj->user_firstname;
                $tab_result[$i]['user_lastname'] = $obj->user_lastname;

                $tab_result[$i]['validator_firstname'] = $obj->validator_firstname;
                $tab_result[$i]['validator_lastname'] = $obj->validator_lastname;

                $i++;
            }

            // Retourne 1 avec le tableau rempli
            $this->holiday = $tab_result;
            return 1;
        } else {
            // Erreur SQL
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetchByUser " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     * 	List all holidays of all users
     *
     *  @param      string	$order      Sort order
     *  @param      string	$filter     SQL Filter
     *  @return     int      			-1 if KO, 1 if OK, 2 if no result
     */
    function fetchAll($order, $filter, $group_cp = false) {
        global $langs;

        $sql = "SELECT";
        $sql.= " cp.rowid,";

        $sql.= $group_cp ? " cp.fk_group," : " cp.fk_user,";
        $sql.= " cp.date_create,";
        $sql.= " cp.description,";
        $sql.= " cp.date_debut,";
        $sql.= " cp.date_fin,";
        $sql.= " cp.halfday,";
        $sql.= " cp.statut,";
        $sql.= " cp.fk_validator,";
        $sql.= " cp.date_valid,";
        $sql.= " cp.date_drh_valid,";
        $sql.= " cp.fk_user_valid,";
        $sql.= " cp.fk_user_drh_valid,";
        $sql.= " cp.date_refuse,";
        $sql.= " cp.fk_user_refuse,";
        $sql.= " cp.date_cancel,";
        $sql.= " cp.fk_user_cancel,";
        $sql.= " cp.detail_refuse,";
        $sql.= " cp.type_conges,";
        $sql.= " cp.fk_actioncomm,";
        $sql.= " cp.fk_substitute,";

        if (!$group_cp) {
            $sql.= " uu.lastname as user_lastname,";
            $sql.= " uu.firstname as user_firstname,";

            $sql.= " ua.lastname as validator_lastname,";
            $sql.= " ua.firstname as validator_firstname";

            $sql.= " FROM " . MAIN_DB_PREFIX . "holiday as cp, " . MAIN_DB_PREFIX . "user as uu, " . MAIN_DB_PREFIX . "user as ua";
            $sql.= " WHERE cp.fk_user = uu.rowid AND cp.fk_validator = ua.rowid "; // Hack pour la recherche sur le tableau
        } else {
            $sql.= " ua.lastname as validator_lastname,";
            $sql.= " ua.firstname as validator_firstname";

            $sql.= " FROM " . MAIN_DB_PREFIX . "holiday as cp, " . MAIN_DB_PREFIX . "user as ua";
            $sql.= " WHERE cp.fk_user IS NULL AND cp.fk_validator = ua.rowid ";
        }


        //
        // Filtrage de séléction
        if (!empty($filter)) {
            $sql.= $filter;
        }
        // Ordre d'affichage
        if (!empty($order)) {
            $sql.= $order;
        }

        dol_syslog(get_class($this) . "::fetchAll sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);

        $tab_result = $this->holiday;
        // Si pas d'erreur SQL
        if ($resql) {
            $i = 0;
            $num = $this->db->num_rows($resql);

            // On liste les résultats et on les ajoute dans le tableau
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);

                $tab_result[$i]['rowid'] = $obj->rowid;
                $tab_result[$i]['ref'] = $obj->rowid;

                if ($group_cp)
                    $tab_result[$i]['fk_group'] = $obj->fk_group;
                else
                    $tab_result[$i]['fk_user'] = $obj->fk_user;

                $tab_result[$i]['date_create'] = $this->db->jdate($obj->date_create);
                $tab_result[$i]['description'] = $obj->description;
                $tab_result[$i]['date_debut'] = $this->db->jdate($obj->date_debut);
                $tab_result[$i]['date_fin'] = $this->db->jdate($obj->date_fin);
                $tab_result[$i]['date_debut_gmt'] = $this->db->jdate($obj->date_debut, 1);
                $tab_result[$i]['date_fin_gmt'] = $this->db->jdate($obj->date_fin, 1);
                $tab_result[$i]['halfday'] = $obj->halfday;
                $tab_result[$i]['statut'] = $obj->statut;
                $tab_result[$i]['fk_validator'] = $obj->fk_validator;
                $tab_result[$i]['date_valid'] = $this->db->jdate($obj->date_valid);
                $tab_result[$i]['date_drh_valid'] = $this->db->jdate($obj->date_drh_valid);
                $tab_result[$i]['fk_user_valid'] = $obj->fk_user_valid;
                $tab_result[$i]['fk_user_drh_valid'] = $obj->fk_user_drh_valid;
                $tab_result[$i]['date_refuse'] = $obj->date_refuse;
                $tab_result[$i]['fk_user_refuse'] = $obj->fk_user_refuse;
                $tab_result[$i]['date_cancel'] = $obj->date_cancel;
                $tab_result[$i]['fk_user_cancel'] = $obj->fk_user_cancel;
                $tab_result[$i]['detail_refuse'] = $obj->detail_refuse;
                $tab_result[$i]['type_conges'] = $obj->type_conges;
                $tab_result[$i]['fk_actioncomm'] = $obj->fk_actioncomm;
                $tab_result[$i]['fk_substitute'] = $obj->fk_substitute;

                if (!$group_cp) {
                    $tab_result[$i]['user_firstname'] = $obj->user_firstname;
                    $tab_result[$i]['user_lastname'] = $obj->user_lastname;
                } else {
                    $tab_result[$i]['user_firstname'] = '';
                    $tab_result[$i]['user_lastname'] = '';
                }

                $tab_result[$i]['validator_firstname'] = $obj->validator_firstname;
                $tab_result[$i]['validator_lastname'] = $obj->validator_lastname;
                $i++;
            }
            if (!count($tab_result))
                return 2;

            // Retourne 1 et ajoute le tableau à la variable
            $this->holiday = $tab_result;
            return 1;
        } else {
            // Erreur SQL
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetchAll " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     * 	Update database
     *
     *  @param	User	$user        	User that modify
     *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
     *  @return int         			<0 if KO, >0 if OK
     */
    function update($user = 0, $notrigger = 0) {
        global $conf, $langs;
        $error = 0;

        // Update request
        $sql = "UPDATE " . MAIN_DB_PREFIX . "holiday SET";

        $sql.= " description= '" . $this->db->escape($this->description) . "',";

        if (!empty($this->date_debut)) {
            $sql.= " date_debut = '" . $this->db->idate($this->date_debut) . "',";
        } else {
            $error++;
        }
        if (!empty($this->date_fin)) {
            $sql.= " date_fin = '" . $this->db->idate($this->date_fin) . "',";
        } else {
            $error++;
        }
        $sql.= " halfday = " . $this->halfday . ",";
        if (!empty($this->statut) && is_numeric($this->statut)) {
            $sql.= " statut = '" . $this->statut . "',";
        } else {
            $error++;
        }
        if (!empty($this->fk_validator)) {
            $sql.= " fk_validator = '" . $this->fk_validator . "',";
        } else {
            $error++;
        }
        if (!empty($this->date_valid)) {
            $sql.= " date_valid = '" . $this->db->idate($this->date_valid) . "',";
        } else {
            $sql.= " date_valid = NULL,";
        }
        if (!empty($this->date_drh_valid)) {
            $sql.= " date_drh_valid = '" . $this->db->idate($this->date_drh_valid) . "',";
        } else {
            $sql.= " date_drh_valid = NULL,";
        }
        if (!empty($this->fk_user_valid)) {
            $sql.= " fk_user_valid = '" . $this->fk_user_valid . "',";
        } else {
            $sql.= " fk_user_valid = NULL,";
        }
        if (!empty($this->fk_user_drh_valid)) {
            $sql.= " fk_user_drh_valid = '" . $this->fk_user_drh_valid . "',";
        } else {
            $sql.= " fk_user_drh_valid = NULL,";
        }
        if (!empty($this->date_refuse)) {
            $sql.= " date_refuse = '" . $this->db->idate($this->date_refuse) . "',";
        } else {
            $sql.= " date_refuse = NULL,";
        }
        if (!empty($this->fk_user_refuse)) {
            $sql.= " fk_user_refuse = '" . $this->fk_user_refuse . "',";
        } else {
            $sql.= " fk_user_refuse = NULL,";
        }
        if (!empty($this->date_cancel)) {
            $sql.= " date_cancel = '" . $this->db->idate($this->date_cancel) . "',";
        } else {
            $sql.= " date_cancel = NULL,";
        }
        if (!empty($this->fk_user_cancel)) {
            $sql.= " fk_user_cancel = '" . $this->fk_user_cancel . "',";
        } else {
            $sql.= " fk_user_cancel = NULL,";
        }
        if (!empty($this->detail_refuse)) {
            $sql.= " detail_refuse = '" . $this->db->escape($this->detail_refuse) . "',";
        } else {
            $sql.= " detail_refuse = NULL,";
        }
        if (isset($this->type_conges)) {
            $sql .= " type_conges = '" . $this->type_conges . "',";
        } else {
            $error++;
        }
        if (isset($this->fk_actioncomm) && !empty($this->fk_actioncomm)) {
            $sql.= " fk_actioncomm = '" . $this->fk_actioncomm . "',";
        } else {
            $sql.= " fk_actioncomm = NULL,";
        }
        if (isset($this->fk_substitute) && !empty($this->fk_substitute)) {
            $sql.= " fk_substitute = '" . $this->fk_substitute . "'";
        } else {
            $sql.= " fk_substitute = NULL";
        }

        $sql.= " WHERE rowid= '" . $this->rowid . "'";

        $this->db->begin();

        dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
            $this->db->rollback();
        } else {
            $this->db->commit();
            if (is_array($this->fk_user)) {
                $result = $this->db->query('DELETE FROM ' . MAIN_DB_PREFIX . 'holiday_group WHERE `fk_holiday`= ' . $this->rowid);
                if (!$result) {
                    $this->errors[] = 'Echec de la mise à jour de la table "holiday_group" (req. DELETE)';
                    $error++;
                } else {
                    foreach ($this->fk_user as $user_id) {
                        $result = $this->db->query('INSERT INTO ' . MAIN_DB_PREFIX . 'holiday_group(fk_holiday, fk_user) VALUES(' . $this->rowid . ', ' . $user_id . ')');
                        if (!$result) {
                            $this->errors[] = 'Echec de la mise à jour de la table "holiday_group" (req. INSERT INTO userId: ' . $user_id . ')';
                            $error++;
                        }
                    }
                }
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
                $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
            }

            return -1 * $error;
        } else {

            return 1;
        }
    }

    /**
     *   Delete object in database
     *
     * 	 @param		User	$user        	User that delete
     *   @param     int		$notrigger	    0=launch triggers after, 1=disable triggers
     * 	 @return	int						<0 if KO, >0 if OK
     */
    function delete($user, $notrigger = 0) {
        global $conf, $langs;
        $error = 0;

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "holiday";
        $sql.= " WHERE `rowid`= " . $this->rowid;

        $this->db->begin();
        dol_syslog(get_class($this) . "::delete sql=" . $sql);
        $resql = $this->db->query($sql);

        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
            $this->db->rollback();
        } else {
            $this->db->commit();
            if (is_array($this->fk_user)) {
                $this->db->begin();
                $result = $this->db->query('DELETE FROM ' . MAIN_DB_PREFIX . 'holiday_group WHERE `fk_holiday`= ' . $this->rowid);
                if (!$result) {
                    $error++;
                    $this->errors[] = "Error " . $this->db->lasterror();
                    $this->db->rollback();
                } else
                    $this->db->commit();
            }
            if ($this->fk_actioncomm) {
                $ac = new ActionComm($this->db);
                $ac->fetch($this->fk_actioncomm);
                $ac->delete();
            }
        }

        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
                $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
            }
            return -1 * $error;
        }
        return 1;
    }

    /**
     * 	verifDateHolidayCP
     *
     * 	@param 	int		$fk_user		Id user
     * 	@param 	date	$dateDebut		Start date
     * 	@param 	date	$dateFin		End date
     *  @param	int		$halfday		Tag to define half day when holiday start and end
     * 	@return boolean
     */
    function verifDateHolidayCP($fk_user, $dateDebut, $dateFin, $halfday = 0) {
        $this->fetchByUser($fk_user, '', '', $fk_user); // On vérifie également les congés pour lesquels $fk_user est remplaçant
        // Vérifications des conflits avec les congés existants pour cet utilisateur:
        foreach ($this->holiday as $infos_CP) {
            if (!empty($this->rowid)) // en cas de verif lors d'une update
                if ($this->rowid == $infos_CP['rowid'])
                    continue;
            if ($infos_CP['statut'] == 4)
                continue;  // ignore not validated holidays
            if ($infos_CP['statut'] == 5)
                continue;  // ignore not validated holidays















                
//            if ($dateDebut >= $infos_CP['date_debut'] && $dateDebut <= $infos_CP['date_fin'] || // Test invalide : ne prend pas en compte début1 < début2 ET fin1 > fin2
//                    $dateFin <= $infos_CP['date_fin'] && $dateFin >= $infos_CP['date_debut']) {
            if ($dateDebut <= $infos_CP['date_fin'] && $dateFin >= $infos_CP['date_debut']) {
                // Mise à jour Synopsis - prise en compte des demies-journées
                if ($halfday && $infos_CP['halfday']) {
                    if ($dateDebut == $infos_CP['date_fin']) {
                        if (($halfday < 0 || $halfday == 2) && $infos_CP['halfday'] > 0) {
                            continue;
                        }
                    }
                    if ($dateFin == $infos_CP['date_debut']) {
                        if ($halfday > 0 && ($infos_CP['halfday'] < 0 || $infos_CP['halfday'] == 2)) {
                            continue;
                        }
                    }
                }
                if ($infos_CP['fk_substitute'] == $fk_user)
                    return -1;
                return 0;
            }
        }
        return 1;
    }

    /**
     * 	verifDateHolidayForGroup
     *
     * 	@param 	int		$fk_group		Id group
     * 	@param 	date	$dateDebut		Start date
     * 	@param 	date	$dateFin		End date
     *  @param	int		$halfday		Tag to define half day when holiday start and end
     * 	@return int                             -1: erreur, 0: conflits existants, 1: pas de conflits
     */
    function verifDateHolidayForGroup($fk_group, $dateDebut, $dateFin, $halfday = 0) {
        if ($this->fetchAll('', '', true) > 0) {
            foreach ($this->holiday as $cp) {
                if (!empty($this->rowid)) // en cas de verif lors d'une update
                    if ($this->rowid == $cp['rowid'])
                        continue;

                if (($cp['statut'] == 4) || ($cp['statut'] == 5))
                    continue;

                if (isset($cp['fk_group']) && !empty($cp['fk_group'])) {
                    if ($cp['fk_group'] == $fk_group) {
                        if ($dateDebut <= $cp['date_fin'] && $dateFin >= $cp['date_debut']) {
                            if ($halfday && $cp['halfday']) {
                                if ($dateDebut == $cp['date_fin']) {
                                    if (($halfday < 0 || $halfday == 2) && $cp['halfday'] > 0) {
                                        continue;
                                    }
                                }
                                if ($dateFin == $cp['date_debut']) {
                                    if ($halfday > 0 && ($cp['halfday'] < 0 || $cp['halfday'] == 2)) {
                                        continue;
                                    }
                                }
                            }
                            return 0;
                        }
                    }
                }
            }
            return 1;
        }
        return -1;
    }

    /**
     * 	Ajout Synopsis - verifUserConflictsForGroupHoliday
     * 
     * Vérifie l'existance de conflits entre le congé instancié et les congés existants pour l'utilisateur d'id fk_user. 
     * Modifie les dates des congés existants ou les annule pour supprimer tout conflt. 
     * Restitue les soldes correspondants. 
     *
     * 	@param 	User		$user		Objet user à l'origine de l'action
     * 	@param 	int	$fk_user		id user 
     * 	@return array                           Tableau d'infos sur les congés affectés
     */
    function verifUserConflictsForGroupHoliday($user, $fk_user) {
        $this->holiday = array();
        $this->fetchByUser($fk_user);
        $results = array();
        $DateTime = new DateTime();
        foreach ($this->holiday as $cp) {
            if ($cp['rowid'] == $this->rowid)
                continue;

            if ($cp['statut'] == 4 || $cp['statut'] == 5)
                continue;

            // Vérif de l'existance d'un conflit
            if ($this->date_debut <= $cp['date_fin'] && $this->date_fin >= $cp['date_debut']) {
                if ($this->halfday && $cp['halfday']) {
                    if ($this->date_debut == $cp['date_fin']) {
                        if (($this->halfday < 0 || $this->halfday == 2) && $cp['halfday'] > 0) {
                            continue;
                        }
                    }
                    if ($this->date_fin == $cp['date_debut']) {
                        if ($this->halfday > 0 && ($cp['halfday'] < 0 || $cp['halfday'] == 2)) {
                            continue;
                        }
                    }
                }

                // $left: Indique la position du début du nouveau congé par rapport au début du congé existant 
                // 0: égal, 1: demie-journée, 2: journée entière, >0: supérieur, <0: inférieur
                $left = 0;
                if ($this->date_debut < $cp['date_debut'])
                    $left = -2;
                else if ($this->date_debut > $cp['date_debut'])
                    $left = 2;
                else {
                    if ($this->halfday < 0 || $this->halfday == 2) {
                        if ($cp['halfday'] < 0 || $cp['halfday'] == 2)
                            $left = 0;
                        else
                            $left = 1;
                    } else if ($cp['halfday'] < 0 || $cp['halfday'] == 2) {
                        $left = -1;
                    } else
                        $left = 0;
                }

                // $right: Indique la position de la fin du nouveau congé par rapport à la fin du congé existant 
                // 0: égal, 1: demie-journée, 2: journée entière, >0: supérieur, <0: inférieur
                $right = 0;
                if ($this->date_fin < $cp['date_fin'])
                    $right = -2;
                else if ($this->date_fin > $cp['date_fin'])
                    $right = 2;
                else {
                    if ($this->halfday > 0 || $this->halfday == 2) {
                        if ($cp['halfday'] > 0 || $cp['halfday'] == 2)
                            $right = 0;
                        else
                            $right = -1;
                    } else if ($cp['halfday'] > 0 || $cp['halfday'] == 2) {
                        $right = 1;
                    } else
                        $right = 0;
                }

                $nbDays = null;
                $upCp = new SynopsisHoliday($this->db);
                if ($upCp->fetch($cp['rowid']) > 0) {
                    $datas = array(
                        'initial_date_begin' => $cp['date_debut'],
                        'initial_date_end' => $cp['date_fin'],
                        'operation' => 'none',
                        'errors' => array()
                    );
                    // Annulation                
                    if ($left <= 0 && $right >= 0) {
                        $upCp->statut = 4;
                        if ($upCp->update($user)) {
                            dol_syslog('Annulation du congé ' . $cp['rowid'] . ' de l\utilisateur ' . $fk_user);
                            $upCp->onStatusUpdate($user);
                            $datas['operation'] = 'cancel';
                            $datas['id'] = $cp['rowid'];
                            if ($cp['statut'] == 6)
                                $nbDays = $this->getCPforUser($fk_user, $cp['date_debut'], $cp['date_fin'], $cp['halfday'], true);
                        } else {
                            dol_syslog('Erreur lors de l\'annulation du congé ' . $cp['rowid'], LOG_ERR);
                            $datas['errors'][] = 'Echec de l\'annulation du congé ' . $cp['rowid'];
                        }
                    }

                    // Màj de la date de fin: 
                    else if ($left > 0) {
                        $newDateFin = $this->date_debut;
                        $newHd = $cp['halfday'];
                        if ($this->halfday < 0 || $this->halfday == 2) {
                            if ($newHd < 0) {
                                $newHd = 2;
                            } else if ($newHd != 2)
                                $newHd = 1;
                        } else {
                            if ($newHd == 2)
                                $newHd = -1;
                            else if ($newHd == 1)
                                $newHd = 0;
                            $newDateFin -= 24 * 60 * 60;
                        }

                        if ($cp['date_debut'] == $newDateFin) {
                            if ($newHd == 2)
                                $newHd = -1;
                        }

                        $upCp->date_fin = $newDateFin;
                        $upCp->halfday = $newHd;
                        if ($upCp->update($user)) {
                            $DateTime->setTimestamp($datas['initial_date_end']);
                            dol_syslog('Mise à jour de la date de fin du congé ' . $cp['rowid'] . '. Ancienne date: ' . $DateTime->format('d / m / Y'));

                            $upCp->onStatusUpdate($user);
                            if ($right < 0) {
                                // Création d'un nouveau congé pour conserver la fin du congé existant: 
                                $upCp->rowid = null;
                                $upCp->date_fin = $cp['date_fin'];
                                $upCp->date_debut = $this->date_fin;
                                $newHd = $cp['halfday'];
                                if ($this->halfday > 0 || $this->halfday == 2) {
                                    if ($newHd > 0)
                                        $newHd = 2;
                                    else if ($newHd != 2)
                                        $newHd = -1;
                                } else {
                                    if ($newHd == 2)
                                        $newHd = 1;
                                    else if ($newHd == -1)
                                        $newHd = 0;
                                    $upCp->date_debut += 24 * 60 * 60;
                                }

                                if ($upCp->date_debut == $upCp->date_fin) {
                                    if ($newHd == 2)
                                        $newHd = 1;
                                }
                                $upCp->halfday = $newHd;
                                $upCp->fk_actioncomm = '';
                                if ($upCp->create($user) > 0) {
                                    if ($upCp->update($user)) {
                                        $upCp->onStatusUpdate($user);
                                        // On restitue le nombre de jours ouvrés correspondant exactement aux dates du nouveau congé: 
                                        if ($cp['statut'] == 6)
                                            $nbDays = $this->getCPforUser($fk_user, $this->date_debut, $this->date_fin, $this->halfday, true);
                                        $datas['operation'] = 'create';
                                        $datas['id'] = $cp['rowid'];
                                        $datas['new_id'] = $upCp->rowid;
                                    }
                                }
                            } else {
                                if ($cp['statut'] == 6) {
                                    $hd = 0;
                                    if ($this->halfday < 0 || $this->halfday == 2)
                                        $hd = -1;
                                    if ($cp['halfday'] > 0 || $cp['halfday'] == 2) {
                                        if ($hd < 0)
                                            $hd = 2;
                                        else
                                            $hd = 1;
                                    }
                                    $nbDays = $this->getCPforUser($fk_user, $this->date_debut, $cp['date_fin'], $hd, true);
                                }
                                $datas['operation'] = 'end_update';
                                $datas['id'] = $cp['rowid'];
                            }
                        } else {
                            dol_syslog('Echec de la mise à jour de la date de fin du congé ' . $cp['rowid'], LOG_ERR);
                            $datas['errors'][] = 'Echec de la mise à jour de la date de fin du congé ' . $cp['rowid'];
                        }
                    } else if ($right < 0) {
                        // màj du début:
                        $newDateDebut = $this->date_fin;
                        $newHd = $cp['halfday'];
                        if ($this->halfday > 0 || $this->halfday == 2) {
                            if ($newHd > 0)
                                $newHd = 2;
                            else if ($newHd != 2)
                                $newHd = -1;
                        } else {
                            if ($newHd == 2)
                                $newHd = 1;
                            else if ($newHd == -1)
                                $newHd = 0;
                            $newDateDebut += 24 * 60 * 60;
                        }

                        if ($newDateDebut == $cp['date_fin']) {
                            if ($newHd == 2)
                                $newHd = 1;
                        }
                        $upCp->date_debut = $newDateDebut;
                        $upCp->halfday = $newHd;
                        if ($upCp->update($user)) {
                            $DateTime->setTimestamp($datas['initial_date_begin']);
                            dol_syslog('Mise à jour de la date de début du congé ' . $cp['rowid'] . '. Ancienne date: ' . $DateTime->format('d / m / Y'));
                            $upCp->onStatusUpdate($user);
                            if ($cp['statut'] == 6) {
                                $hd = 0;
                                if ($this->halfday > 0 || $this->halfday == 2)
                                    $hd = 1;
                                if ($cp['halfday'] < 0 || $cp['halfday'] == 2)
                                    if ($hd > 0)
                                        $hd = 2;
                                    else
                                        $hd = -1;
                                $nbDays = $this->getCPforUser($fk_user, $cp['date_debut'], $this->date_fin, $hd, true);
                            }

                            $datas['operation'] = 'begin_update';
                            $datas['id'] = $cp['rowid'];
                        } else {
                            dol_syslog('Echec de la mise à jour de la date de début du congé ' . $cp['rowid'], LOG_ERR);
                            $datas['errors'][] = 'Echec de la mise à jour de la date de début du congé ' . $cp['rowid'];
                        }
                    }
                }
                // Restitution des soldes:
                if (isset($nbDays) && $cp['statut'] == 6) {
                    if ($cp['type_conges'] == 0) {
                        if (isset($nbDays['error'])) {
                            $datas['errors'][] = $nbDays['error'];
                        } else {
                            $nbHolidayDeducted = $this->getConfCP('nbHolidayDeducted');
                            // solde année en cours:
                            if ($nbDays['nbOpenDayCurrent'] > 0) {
                                $newSolde = $nbDays['nb_holiday_current'] + ($nbDays['nbOpenDayCurrent'] * $nbHolidayDeducted);
                                $result1 = $this->addLogCP($user->id, $fk_user, 'Restitution suite à création d\'un congé collectif en conflit (cp - année n)', $newSolde, false, true);
                                $result2 = $this->updateSoldeCP($fk_user, $newSolde, true);
                            } else {
                                $result1 = 1;
                                $result2 = 1;
                            }
                            // solde année n+1
                            if ($nbDays['nbOpenDayNext'] > 0) {
                                $newSolde = $nbDays['nb_holiday_next'] + ($nbDays['nbOpenDayNext'] * $nbHolidayDeducted);
                                $result3 = $this->addLogCP($user->id, $fk_user, 'Restitution suite à création d\'un congé collectif en conflit (cp - année n+1)', $newSolde, false, false);
                                $result4 = $this->updateSoldeCP($fk_user, $newSolde, false);
                            } else {
                                $result3 = 1;
                                $result4 = 1;
                            }

                            if ($result1 < 0 || $result2 < 0 || $result3 < 0 || $result4 < 0) {
                                $datas['errors'][] = 'Une erreur est survenue lors de la restitution du solde';
                            }
                        }
                    } else if ($cp['type_conges'] == 2) {
                        $soldeActuel = $this->getRTTforUser($fk_user);
                        $newSolde = $soldeActuel + ($nbDays['nbOpenDayTotal'] * $this->getConfCP('nbRTTDeducted'));
                        $result1 = $this->addLogCP($user->id, $fk_user, 'Restitution suite à création d\'un congé collectif en conflit (rtt)', $newSolde, true);
                        $result2 = $this->updateSoldeRTT($fk_user, $newSolde);
                        if ($result1 < 0 || $result2 < 0) {
                            $error = 'Une erreur est survenue lors de la restitution du solde';
                        }
                    }
                    unset($nbDays);
                }
                if ($datas['operation'] !== 'none') {
                    $results[] = $datas;
                }
                unset($upCp);
            }
        }
        unset($this->holiday);
        $this->holiday = array();
        return $results;
    }

    /**
     * 	Return clicable name (with picto eventually)
     *
     * 	@param		int			$withpicto		0=_No picto, 1=Includes the picto in the linkn, 2=Picto only
     * 	@return		string						String with URL
     */
    function getNomUrl($withpicto = 0) {
        global $langs;

        $result = '';

        $lien = '<a href="' . DOL_URL_ROOT . '/synopsisholiday/card.php?id=' . $this->id . '">';
        $lienfin = '</a>';

        $picto = 'holiday';

        $label = $langs->trans("Show") . ': ' . $this->ref;

        $userStr = "";

        $usersList = '';
        if (!empty($this->fk_user)) {
            if (is_numeric($this->fk_user)) {
                $userT = new User($this->db);
                $userT->fetch($this->fk_user);
                $userStr = " - " . $userT->getFullName($langs);
            } else if (is_array($this->fk_user)) {
                if (isset($this->fk_group) && !empty($this->fk_group)) {
                    require_once(DOL_DOCUMENT_ROOT . "/user/class/usergroup.class.php");
                    $userGroup = new UserGroup($this->db);
                    if ($userGroup->fetch($this->fk_group) > 0)
                        $userStr = ' - Groupe: <b>' . $userGroup->name . '</b>';
                }
                if (empty($userStr))
                    $userStr = ' - Congés collectifs';
            }
        }

        if ($withpicto)
            $result.=($lien . img_object($label, $picto) . $lienfin);
        if ($withpicto && $withpicto != 2)
            $result.=' ';
        if ($withpicto != 2)
            $result.=$lien . $this->ref . $userStr . $lienfin;
        return $result;
    }

    /**
     * 	Returns the label status
     *
     * 	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
     * 	@return     string      		Label
     */
    function getLibStatut($mode = 0) {
        return $this->LibStatut($this->statut, $mode, $this->date_debut);
    }

    /**
     * 	Returns the label of a statut
     *
     * 	@param      int		$statut     id statut
     * 	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
     *  @param		date	$startdate	Date holiday should start
     * 	@return     string      		Label
     */
    function LibStatut($statut, $mode = 0, $startdate = '') {
        global $langs;

        if ($mode == 0) {
            if ($statut == 1)
                return $langs->trans('DraftCP');
            if ($statut == 2)
                return $langs->trans('ToReviewCP');
            if ($statut == 3)
                return $langs->trans('ApprovedCP');
            if ($statut == 4)
                return $langs->trans('CancelCP');
            if ($statut == 5)
                return $langs->trans('RefuseCP');
            if ($statut == 6)
                return $langs->trans('FinalApprovedCP');
        }
        if ($mode == 2) {
            $pictoapproved = 'statut6';
            if (!empty($startdate) && $startdate > dol_now())
                $pictoapproved = 'statut4';
            if ($statut == 1)
                return img_picto($langs->trans('DraftCP'), 'statut0') . ' ' . $langs->trans('DraftCP');    // Draft
            if ($statut == 2)
                return img_picto($langs->trans('ToReviewCP'), 'statut1') . ' ' . $langs->trans('ToReviewCP');  // Waiting approval
            if ($statut == 3)
                return img_picto($langs->trans('ApprovedCP'), $pictoapproved) . ' ' . $langs->trans('ApprovedCP');
            if ($statut == 4)
                return img_picto($langs->trans('CancelCP'), 'statut5') . ' ' . $langs->trans('CancelCP');
            if ($statut == 5)
                return img_picto($langs->trans('RefuseCP'), 'statut5') . ' ' . $langs->trans('RefuseCP');
            if ($statut == 6)
                return img_picto($langs->trans('FinalApprovedCP'), $pictoapproved) . ' ' . $langs->trans('FinalApprovedCP');
        }
        if ($mode == 5) {
            $pictoapproved = 'statut6';
            if (!empty($startdate) && $startdate > dol_now())
                $pictoapproved = 'statut4';
            if ($statut == 1)
                return $langs->trans('DraftCP') . ' ' . img_picto($langs->trans('DraftCP'), 'statut0');    // Draft
            if ($statut == 2)
                return $langs->trans('ToReviewCP') . ' ' . img_picto($langs->trans('ToReviewCP'), 'statut1');  // Waiting approval
            if ($statut == 3)
                return $langs->trans('ApprovedCP') . ' ' . img_picto($langs->trans('ApprovedCP'), $pictoapproved);
            if ($statut == 4)
                return $langs->trans('CancelCP') . ' ' . img_picto($langs->trans('CancelCP'), 'statut5');
            if ($statut == 5)
                return $langs->trans('RefuseCP') . ' ' . img_picto($langs->trans('RefuseCP'), 'statut5');
            if ($statut == 6)
                return $langs->trans('FinalApprovedCP') . ' ' . img_picto($langs->trans('FinalApprovedCP'), $pictoapproved);
        }

        die($statut);
        return $statut;
    }

    /**
     *   Affiche un select HTML des statuts de congés payés
     *
     *   @param 	int		$selected   int du statut séléctionné par défaut
     *   @return    string				affiche le select des statuts
     */
    function selectStatutCP($selected = '') {

        global $langs;

        // Liste des statuts
        $name = array('DraftCP', 'ToReviewCP', 'ApprovedCP', 'CancelCP', 'RefuseCP', 'FinalApprovedCP');
        $nb = count($name) + 1;

        // Select HTML
        $statut = '<select name="select_statut" class="flat">' . "\n";
        $statut.= '<option value="-1">&nbsp;</option>' . "\n";

        // Boucle des statuts
        for ($i = 1; $i < $nb; $i++) {
            if ($i == $selected) {
                $statut.= '<option value="' . $i . '" selected="selected">' . $langs->trans($name[$i - 1]) . '</option>' . "\n";
            } else {
                $statut.= '<option value="' . $i . '">' . $langs->trans($name[$i - 1]) . '</option>' . "\n";
            }
        }

        $statut.= '</select>' . "\n";
        print $statut;
    }

    /**
     * 	Retourne un select HTML des groupes d'utilisateurs
     *
     *  @param	string	$prefix     nom du champ dans le formulaire
     *  @return string				retourne le select des groupes
     */
    function selectUserGroup($prefix) {
        // On récupère le groupe déjà configuré
        $group.= "SELECT value";
        $group.= " FROM " . MAIN_DB_PREFIX . "holiday_config";
        $group.= " WHERE name = 'userGroup'";

        $resultat = $this->db->query($group);
        $objet = $this->db->fetch_object($resultat);
        $groupe = $objet->value;

        // On liste les groupes de Dolibarr
        $sql = "SELECT u.rowid, u.nom";
        $sql.= " FROM " . MAIN_DB_PREFIX . "usergroup as u";
        $sql.= " ORDER BY u.rowid";

        dol_syslog(get_class($this) . "::selectUserGroup sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);

        // Si pas d'erreur SQL
        if ($result) {
            // On créer le select HTML
            $selectGroup = '<select name="' . $prefix . '" class="flat">' . "\n";
            $selectGroup.= '<option value="-1">&nbsp;</option>' . "\n";

            // On liste les utilisateurs
            while ($obj = $this->db->fetch_object($result)) {
                if ($groupe == $obj->rowid) {
                    $selectGroup.= '<option value="' . $obj->rowid . '" selected="selected">' . $obj->nom . '</option>' . "\n";
                } else {
                    $selectGroup.= '<option value="' . $obj->rowid . '">' . $obj->nom . '</option>' . "\n";
                }
            }
            $selectGroup.= '</select>' . "\n";
            $this->db->free($result);
        } else {
            // Erreur SQL
            $this->error = $this->db->lasterror();
            dol_syslog(get_class($this) . "::selectUserGroup " . $this->error, LOG_ERR);
            return -1;
        }

        // Retourne le select HTML
        return $selectGroup;
    }

    /**
     *  Met à jour une option du module Holiday Payés
     *
     *  @param	string	$name       nom du paramètre de configuration
     *  @param	string	$value      vrai si mise à jour OK sinon faux
     *  @return boolean				ok or ko
     */
    function updateConfCP($name, $value) {

        $sql = "UPDATE " . MAIN_DB_PREFIX . "holiday_config SET";
        $sql.= " value = '" . $value . "'";
        $sql.= " WHERE name = '" . $name . "'";

        dol_syslog(get_class($this) . '::updateConfCP name=' . $name . ' sql=' . $sql);
        $result = $this->db->query($sql);
        if ($result) {
            return true;
        }

        return false;
    }

    /**
     *  Retourne la valeur d'un paramètre de configuration
     *
     *  @param	string	$name       nom du paramètre de configuration
     *  @return string      		retourne la valeur du paramètre
     */
    function getConfCP($name) {
        $sql = "SELECT value";
        $sql.= " FROM " . MAIN_DB_PREFIX . "holiday_config";
        $sql.= " WHERE name = '" . $name . "'";

        dol_syslog(get_class($this) . '::getConfCP name=' . $name . ' sql=' . $sql);
        $result = $this->db->query($sql);

        // Si pas d'erreur
        if ($result) {

            $objet = $this->db->fetch_object($result);
            // Retourne la valeur
            return $objet->value;
        } else {

            // Erreur SQL
            $this->error = $this->db->lasterror();
            dol_syslog(get_class($this) . "::getConfCP " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *  Ajout Synopsis - Mise à jour annuelle des soldes CP si besoin.
     *  @return void
     */
    function annualUpdateSoldeCP() {
        $now = new DateTime();
        $nowYear = (int) $now->format('Y');
        $lastUpdateYear = (int) $this->getConfCP('lastAnnualUpdate');

        if (empty($lastUpdateYear)) {
            $lastUpdateYear = $nowYear - 1;
            $this->updateConfCP('lastAnnualUpdate', $lastUpdateYear);
        }

        if ($lastUpdateYear == $nowYear)
            return;

        $d = $this->getConfCP('cpNewYearDay');
        if ((int) $d < 10)
            $d = '0' . $d;

        $m = $this->getConfCP('cpNewYearMonth');
        if ((int) $m < 10)
            $m = '0' . $m;

        $y = $lastUpdateYear + 1;

        if ((int) $now->format('Ymd') >= (int) ($y . $m . $d)) {
            global $user, $langs;
            $this->updateConfCP('lastAnnualUpdate', $nowYear);

            $users = $this->fetchUsers(false, false);
            $nbUser = count($users);
            $drhUserID = $this->getConfCP('drhUserId');
            $i = 0;
            while ($i < $nbUser) {
                $now_holiday_current = $this->getCurrentYearCPforUser($users[$i]['rowid']);
                $now_holiday_next = $this->getNextYearCPforUser($users[$i]['rowid']);

                $this->addLogCP($drhUserID, $users[$i]['rowid'], 'Mise à jour annuelle du solde des congés payés pour l\'année en cours', $now_holiday_next, true);
                $this->addLogCP($drhUserID, $users[$i]['rowid'], 'Mise à jour annuelle du solde des congés payés pour l\'année suivante', 0);
                $i++;
            }

            $sql = "UPDATE " . MAIN_DB_PREFIX . "holiday_users SET";
            $sql.= " nb_holiday = nb_holiday_next";
            $sql.= ", nb_holiday_next = 0";

            dol_syslog(get_class($this) . '::annualUpdateSoldeCP sql=' . $sql);
            $result = $this->db->query($sql);
            return $result;
        }
    }

    /**
     * 	Met à jour le timestamp de la dernière mise à jour du solde des CP
     *
     * 	@param		int		$userID		Id of user
     * 	@param		int		$nbHoliday	Nb of days
     *  @return     int					0=Nothing done, 1=OK, -1=KO
     */
    function updateSoldeCP($userID = '', $nbHoliday = '', $currentYearSolde = false) {
        global $user, $langs;
        if (empty($userID) && empty($nbHoliday)) {
            // Si mise à jour pour tout le monde en début de mois (congés payés + rtt)
            $now = dol_now();

            // Mois actuel
            $month = date('m', $now);
            $lastUpdate = $this->getConfCP('lastUpdate');
            $monthLastUpdate = $lastUpdate[4] . $lastUpdate[5];
            //print 'month: '.$month.' '.$lastUpdate.' '.$monthLastUpdate;exit;
            // Si la date du mois n'est pas la même que celle sauvegardée, on met à jour le timestamp
            if ($month != $monthLastUpdate) {
                $sql = "UPDATE " . MAIN_DB_PREFIX . "holiday_config SET";
                $sql.= " value = '" . dol_print_date($now, '%Y%m%d%H%M%S') . "'";
                $sql.= " WHERE name = 'lastUpdate'";

                dol_syslog(get_class($this) . '::updateSoldeCP sql=' . $sql);
                $result = $this->db->query($sql);

                // On ajoute x jours à chaque utilisateurs
                $nb_holiday = $this->getConfCP('nbHolidayEveryMonth');
                if (empty($nb_holiday))
                    $nb_holiday = 0;

                $nb_rtt = $this->getConfCP('nbRTTEveryMonth');
                if (empty($nb_rtt))
                    $nb_rtt = 0;

                $users = $this->fetchUsers(false, false);
                $nbUser = count($users);

                $i = 0;

                while ($i < $nbUser) {
                    $now_holiday = $this->getNextYearCPforUser($users[$i]['rowid']);
                    $new_solde = $now_holiday + $nb_holiday;

                    $now_rtt = $this->getRTTforUser($users[$i]['rowid']);
                    $new_solde_rtt = $now_rtt + $nb_rtt;

                    // On ajoute la modification dans le LOG
                    $this->addLogCP($user->id, $users[$i]['rowid'], $langs->trans('HolidaysMonthlyUpdate'), $new_solde);
                    $i++;
                }

                $sql2 = "UPDATE " . MAIN_DB_PREFIX . "holiday_users SET";
                $sql2.= " nb_holiday_next = nb_holiday_next + " . $nb_holiday;
                $sql2.= ", nb_rtt = nb_rtt + " . $nb_rtt;

                dol_syslog(get_class($this) . '::updateSoldeCP sql=' . $sql2);
                $result = $this->db->query($sql2);

                if ($result)
                    return 1;
                else
                    return -1;
            }

            return 0;
        }
        else {
            // Mise à jour pour un utilisateur
            $nbHoliday = price2num($nbHoliday, 2);

            $sql = "UPDATE " . MAIN_DB_PREFIX . "holiday_users SET";
            if ($currentYearSolde)
                $sql.= " nb_holiday = ";
            else
                $sql.= " nb_holiday_next = ";
            $sql.= $nbHoliday;
            $sql.= " WHERE fk_user = '" . $userID . "'";

            dol_syslog(get_class($this) . '::updateSoldeCP sql=' . $sql);
            $result = $this->db->query($sql);
            if ($result)
                return 1;
            else
                return -1;
        }
    }

    /**
     *  - Ajout Synopisis - 
     * 	Met à jour le solde des RTT pour un utilisateur déterminé.
     *
     * 	@param		int		$userID		Id of user
     * 	@param		int		$nbRtt	Nb of days
     *  @return     int					0=Nothing done, 1=OK, -1=KO
     */
    function updateSoldeRTT($userID, $nbRtt) {
        if (!isset($userID) || !isset($nbRtt))
            return 0;

        $nbRtt = price2num($nbRtt, 2);

        // Mise à jour pour un utilisateur
        $sql = "UPDATE " . MAIN_DB_PREFIX . "holiday_users SET";
        $sql.= " nb_rtt = " . $nbRtt;
        $sql.= " WHERE fk_user = '" . $userID . "'";

        dol_syslog(get_class($this) . '::updateSoldeRTT sql=' . $sql);
        $result = $this->db->query($sql);

        if ($result)
            return 1;
        else
            return -1;
    }

    /**
     * 	Retourne un checked si vrai
     *
     *  @param	string	$name       nom du paramètre de configuration
     *  @return string      		retourne checked si > 0
     */
    function getCheckOption($name) {

        $sql = "SELECT value";
        $sql.= " FROM " . MAIN_DB_PREFIX . "holiday_config";
        $sql.= " WHERE name = '" . $name . "'";

        $result = $this->db->query($sql);

        if ($result) {
            $obj = $this->db->fetch_object($result);

            // Si la valeur est 1 on retourne checked
            if ($obj->value) {
                return 'checked="checked"';
            }
        }
    }

    /**
     *  Créer les entrées pour chaque utilisateur au moment de la configuration
     *
     *  @param	boolean		$single		Single
     *  @param	int			$userid		Id user
     *  @return void
     */
    function createCPusers($single = false, $userid = '') {
        // Si c'est l'ensemble des utilisateurs à ajouter
        if (!$single) {
            dol_syslog(get_class($this) . '::createCPusers');
            $arrayofusers = $this->fetchUsers(false, true);

            foreach ($arrayofusers as $users) {
                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "holiday_users";
                $sql.= " (fk_user, nb_holiday_next, nb_holiday, nb_rtt)";
                $sql.= " VALUES ('" . $userid . "','0', '0', 0')";

                $resql = $this->db->query($sql);
                if (!$resql)
                    dol_print_error($this->db);
            }
        }
        else {
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "holiday_users";
            $sql.= " (fk_user, nb_holiday_next, nb_holiday, nb_rtt)";
            $sql.= " VALUES ('" . $userid . "','0','0','0')";

            $resql = $this->db->query($sql);
            if (!$resql)
                dol_print_error($this->db);
        }
    }

    /**
     *  Supprime un utilisateur du module Congés Payés
     *
     *  @param	int		$user_id        ID de l'utilisateur à supprimer
     *  @return boolean      			Vrai si pas d'erreur, faut si Erreur
     */
    function deleteCPuser($user_id) {

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "holiday_users";
        $sql.= " WHERE fk_user = '" . $user_id . "'";

        $this->db->query($sql);
    }

    /**
     *  Ajout Synopsis - Retourne la date de début du décompte du solde CP à n+1)
     *  @return string      Date début année n+1 (AAAA-MM-JJ)
     */
    function getCPNextYearDate($text = false, $yearAfter = false) {
        $m = $this->getConfCP('cpNewYearMonth');
        if ($m < 10)
            $m = '0' . $m;
        $d = $this->getConfCP('cpNewYearDay');
        if ($d < 10)
            $d = '0' . $d;
        $y = (int) $this->getConfCP('lastAnnualUpdate');
        $y++;
        if ($yearAfter)
            $y++;

        if ($text)
            return $d . ' / ' . $m . ' / ' . $y;
        else
            return $y . '-' . $m . '-' . $d;
    }

    /**
     *  Modifs Synopsis - Retourne le détail des soldes des congés payés
     *  Si une période est fournie : indique le nombre de jours ouvrés total et celui correspondant aux années n, n +1 et > à n+1
     *
     *  @param	int		$user_id    ID de l'utilisateur
     *  @param date             $beginDate  Début période
     *  @param date             $endDate    Fin période
     *  @return        		Retourne le détail des soldes de congés payés
     */
    function getCPforUser($user_id, $beginDate = '', $endDate = '', $halfday = 0, $timestamp = false) {
        global $userHoliday;
        $userHoliday = $user_id;
        $solde = array(
            'nb_holiday_next' => $this->getNextYearCPforUser($user_id),
            'nb_holiday_current' => $this->getcurrentYearCPforUser($user_id)
        );

        if (empty($beginDate) || empty($endDate))
            return ($solde['nb_holiday_next'] + $solde['nb_holiday_current']);

        if ($beginDate > $endDate) {
            $solde['error'] = 'La date de début de période indiquée est supérieure à la date de fin.';
            return $solde;
        }

        if ($timestamp) {
            $DT = new DateTime();
            $DT->setTimestamp($beginDate);
            $beginDate = $DT->format('Y-m-d');
            $DT->setTimestamp($endDate);
            $endDate = $DT->format('Y-m-d');
        }

        $nextYearDate = $this->getCPNextYearDate();
        $nextYearDateAfter = $this->getCPNextYearDate(false, true);

        // formatage des dates pour le calcul des jours ouvrés:
        $beginDateGMT = '';
        $matches = array();
        if (preg_match('/^(\d{4})\-(\d{2})\-(\d{2})$/', $beginDate, $matches))
            $beginDateGMT = dol_mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);
        $endDateGMT = '';
        if (preg_match('/^(\d{4})\-(\d{2})\-(\d{2})$/', $endDate, $matches))
            $endDateGMT = dol_mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);
        $nextYearDateGMT = '';
        if (preg_match('/^(\d{4})\-(\d{2})\-(\d{2})$/', $nextYearDate, $matches))
            $nextYearDateGMT = dol_mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);
        $nextYearDateAfterGMT = '';
        if (preg_match('/^(\d{4})\-(\d{2})\-(\d{2})$/', $nextYearDateAfter, $matches))
            $nextYearDateAfterGMT = dol_mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);

        if (empty($beginDateGMT) || empty($endDateGMT) || empty($nextYearDateGMT) || empty($nextYearDateAfterGMT)) {
            $solde['error'] = 'Les dates indiquées ne sont pas au bon format (attendu : "AAAA-MM-JJ").';
            return $solde;
        }


        $solde['nbOpenDayTotal'] = 0; // Nombre total de jours ouvrés de la période.
        $solde['nbOpenDayCurrent'] = 0; // Nombre de jours ouvrés de la période inclus dans l'année n.
        $solde['nbOpenDayNext'] = 0; // Nombre de jours ouvrés de la période inclus dans l'année n+1.
        $solde['nbOpenDayNextAfter'] = 0; // Nombre de jours ouvrés de la période inclus après l'année n+1.

        $hd_begin = 0;
        $hd_end = 0;
        if ($halfday == 2 || $halfday < 0)
            $hd_begin = -1;
        if ($halfday == 2 || $halfday > 0)
            $hd_end = 1;

        // Jours ouvrés total: 
        $solde['nbOpenDayTotal'] = num_open_dayUser($user_id, $beginDateGMT, $endDateGMT, 0, 1, $halfday);

        // Jours ouvrés de la période sur l'année en cours:
        if ($beginDate < $nextYearDate) {
            if ($endDate < $nextYearDate)
                $solde['nbOpenDayCurrent'] = $solde['nbOpenDayTotal'];
            else {
                $solde['nbOpenDayCurrent'] = num_open_dayUser($user_id, $beginDateGMT, $nextYearDateGMT, 0, 0, $hd_begin);
            }
        }

        // Jours ouvrés de la période sur l'année n+1:
        if ($beginDate < $nextYearDate) {
            if ($endDate < $nextYearDateAfter)
                $solde['nbOpenDayNext'] = num_open_dayUser($user_id, $nextYearDateGMT, $endDateGMT, 0, 1, $hd_end);
            else
                $solde['nbOpenDayNext'] = num_open_dayUser($user_id, $nextYearDateGMT, $nextYearDateAfterGMT, 0, 0, 0);
        } else if ($beginDate < $nextYearDateAfter) {
            if ($endDate < $nextYearDateAfter)
                $solde['nbOpenDayNext'] = $solde['nbOpenDayTotal'];
            else
                $solde['nbOpenDayNext'] = num_open_dayUser($user_id, $beginDateGMT, $nextYearDateAfterGMT, 0, 0, $hd_begin);
        }

        // Jours ouvrés de la période > à l'année n+1:
        if ($endDate >= $nextYearDateAfter) {
            if ($beginDate >= $nextYearDateAfter) {
                $solde['nbOpenDayNextAfter'] = $solde['nbOpenDayTotal'];
            } else {
                $solde['nbOpenDayNextAfter'] = num_open_dayUser($user_id, $nextYearDateAfterGMT, $endDateGMT, 0, 1, $hd_end);
            }
        }
        return $solde;
    }

    /**
     *  Ajouts Synopsis - Retourne le solde de congés payés à l'année n+1 pour un utilisateur
     *
     *  @param	int		$user_id    ID de l'utilisateur
     *  @return float        		Retourne le solde de congés payés de l'utilisateur à n+1
     */
    function getNextYearCPforUser($user_id) {

        $sql = "SELECT nb_holiday_next";
        $sql.= " FROM " . MAIN_DB_PREFIX . "holiday_users";
        $sql.= " WHERE fk_user = '" . $user_id . "'";

        dol_syslog(get_class($this) . '::getNextYearCPforUser sql=' . $sql);
        $result = $this->db->query($sql);
        if ($result) {
            $obj = $this->db->fetch_object($result);
            return number_format($obj->nb_holiday_next, 2);
        } else {
            return '0';
        }
    }

    /**
     *  Ajouts Synopsis - Retourne le solde de congés payés de l'année en cours pour un utilisateur
     *
     *  @param	int		$user_id    ID de l'utilisateur
     *  @return float        		Retourne le solde de congés payés de l'utilisateur de l'année en cours
     */
    function getCurrentYearCPforUser($user_id) {

        $sql = "SELECT nb_holiday";
        $sql.= " FROM " . MAIN_DB_PREFIX . "holiday_users";
        $sql.= " WHERE fk_user = '" . $user_id . "'";

        dol_syslog(get_class($this) . '::getCurrentYearCPforUser sql=' . $sql);
        $result = $this->db->query($sql);
        if ($result) {
            $obj = $this->db->fetch_object($result);
            return number_format($obj->nb_holiday, 2);
        } else {
            return '0';
        }
    }

    /**
     *  Ajouts Synopsis - Retourne le solde de RTT pour un utilisateur
     *
     *  @param	int		$user_id    ID de l'utilisateur
     *  @return float        	Retourne le solde de RTT de l'utilisateur
     */
    function getRTTforUser($user_id) {

        $sql = "SELECT nb_rtt";
        $sql.= " FROM " . MAIN_DB_PREFIX . "holiday_users";
        $sql.= " WHERE fk_user = '" . $user_id . "'";

        dol_syslog(get_class($this) . '::getRTTforUser sql=' . $sql);
        $result = $this->db->query($sql);
        if ($result) {
            $obj = $this->db->fetch_object($result);
            return number_format($obj->nb_rtt, 2);
        } else {
            return '0';
        }
    }

    /**
     *    Liste la liste des utilisateurs du module congés
     *    uniquement pour vérifier si il existe de nouveau utilisateur
     *
     *    @param      boolean	$liste	    si vrai retourne une liste, si faux retourne un array
     *    @param      boolean   $type		si vrai retourne pour Dolibarr, si faux retourne pour CP
     *    @return     string      			retourne un tableau de tout les utilisateurs actifs
     */
    function fetchUsers($liste = true, $type = true) {
        // Si vrai donc pour user Dolibarr
        if ($liste) {

            if ($type) {
                // Si utilisateur de Dolibarr

                $sql = "SELECT u.rowid";
                $sql.= " FROM " . MAIN_DB_PREFIX . "user as u";
                $sql.= " WHERE statut > 0";

                dol_syslog(get_class($this) . "::fetchUsers sql=" . $sql, LOG_DEBUG);
                $resql = $this->db->query($sql);

                // Si pas d'erreur SQL
                if ($resql) {

                    $i = 0;
                    $num = $this->db->num_rows($resql);
                    $liste = '';

                    // Boucles du listage des utilisateurs
                    while ($i < $num) {

                        $obj = $this->db->fetch_object($resql);

                        if ($i == 0) {
                            $liste.= $obj->rowid;
                        } else {
                            $liste.= ', ' . $obj->rowid;
                        }

                        $i++;
                    }
                    // Retoune le tableau des utilisateurs
                    return $liste;
                } else {
                    // Erreur SQL
                    $this->error = "Error " . $this->db->lasterror();
                    dol_syslog(get_class($this) . "::fetchUsers " . $this->error, LOG_ERR);
                    return -1;
                }
            } else { // Si utilisateur du module Congés Payés
                $sql = "SELECT u.fk_user";
                $sql.= " FROM " . MAIN_DB_PREFIX . "holiday_users as u";

                dol_syslog(get_class($this) . "::fetchUsers sql=" . $sql, LOG_DEBUG);
                $resql = $this->db->query($sql);

                // Si pas d'erreur SQL
                if ($resql) {

                    $i = 0;
                    $num = $this->db->num_rows($resql);
                    $liste = '';

                    // Boucles du listage des utilisateurs
                    while ($i < $num) {

                        $obj = $this->db->fetch_object($resql);

                        if ($i == 0) {
                            $liste.= $obj->fk_user;
                        } else {
                            $liste.= ', ' . $obj->fk_user;
                        }

                        $i++;
                    }
                    // Retoune le tableau des utilisateurs
                    return $liste;
                } else {
                    // Erreur SQL
                    $this->error = "Error " . $this->db->lasterror();
                    dol_syslog(get_class($this) . "::fetchUsers " . $this->error, LOG_ERR);
                    return -1;
                }
            }
        } else { // Si faux donc user Congés Payés
            // Si c'est pour les utilisateurs de Dolibarr
            if ($type) {

                $sql = "SELECT u.rowid, u.lastname, u.firstname";
                $sql.= " FROM " . MAIN_DB_PREFIX . "user as u";
                $sql.= " WHERE statut > 0";

                dol_syslog(get_class($this) . "::fetchUsers sql=" . $sql, LOG_DEBUG);
                $resql = $this->db->query($sql);

                // Si pas d'erreur SQL
                if ($resql) {

                    $i = 0;
                    $tab_result = $this->holiday;
                    $num = $this->db->num_rows($resql);

                    // Boucles du listage des utilisateurs
                    while ($i < $num) {

                        $obj = $this->db->fetch_object($resql);

                        $tab_result[$i]['rowid'] = $obj->rowid;
                        $tab_result[$i]['name'] = $obj->lastname;
                        $tab_result[$i]['firstname'] = $obj->firstname;

                        $i++;
                    }
                    // Retoune le tableau des utilisateurs
                    return $tab_result;
                } else {
                    // Erreur SQL
                    $this->error = "Error " . $this->db->lasterror();
                    dol_syslog(get_class($this) . "::fetchUsers " . $this->error, LOG_ERR);
                    return -1;
                }

                // Si c'est pour les utilisateurs du module Congés Payés
            } else {

                $sql = "SELECT cpu.fk_user, u.lastname, u.firstname";
                $sql.= " FROM " . MAIN_DB_PREFIX . "holiday_users as cpu,";
                $sql.= " " . MAIN_DB_PREFIX . "user as u";
                $sql.= " WHERE cpu.fk_user = u.rowid";

                dol_syslog(get_class($this) . "::fetchUsers sql=" . $sql, LOG_DEBUG);
                $resql = $this->db->query($sql);

                // Si pas d'erreur SQL
                if ($resql) {

                    $i = 0;
                    $tab_result = $this->holiday;
                    $num = $this->db->num_rows($resql);

                    // Boucles du listage des utilisateurs
                    while ($i < $num) {

                        $obj = $this->db->fetch_object($resql);

                        $tab_result[$i]['rowid'] = $obj->fk_user;
                        $tab_result[$i]['name'] = $obj->lastname;
                        $tab_result[$i]['firstname'] = $obj->firstname;

                        $i++;
                    }
                    // Retoune le tableau des utilisateurs
                    return $tab_result;
                } else {
                    // Erreur SQL
                    $this->error = "Error " . $this->db->lasterror();
                    dol_syslog(get_class($this) . "::fetchUsers " . $this->error, LOG_ERR);
                    return -1;
                }
            }
        }
    }

    /**
     * 	Compte le nombre d'utilisateur actifs dans Dolibarr
     *
     *  @return     int      retourne le nombre d'utilisateur
     */
    function countActiveUsers() {

        $sql = "SELECT count(u.rowid) as compteur";
        $sql.= " FROM " . MAIN_DB_PREFIX . "user as u";
        $sql.= " WHERE u.statut > 0";

        $result = $this->db->query($sql);
        $objet = $this->db->fetch_object($result);
        return $objet->compteur;
    }

    /**
     * 	Compte le nombre d'utilisateur actifs dans Dolibarr sans CP
     *
     *  @return     int      retourne le nombre d'utilisateur
     */
    function countActiveUsersWithoutCP() {

        $sql = "SELECT count(u.rowid) as compteur";
        $sql.= " FROM " . MAIN_DB_PREFIX . "user as u LEFT OUTER JOIN " . MAIN_DB_PREFIX . "holiday_users hu ON (hu.fk_user=u.rowid)";
        $sql.= " WHERE u.statut > 0 AND hu.fk_user IS NULL ";

        $result = $this->db->query($sql);
        $objet = $this->db->fetch_object($result);
        return $objet->compteur;
    }

    /**
     *  Compare le nombre d'utilisateur actif de Dolibarr à celui des utilisateurs des congés payés
     *
     *  @param    int	$userDolibarrWithoutCP	Number of active users in Dolibarr without holidays
     *  @param    int	$userCP    				Number of active users into table of holidays
     *  @return   void
     */
    function verifNbUsers($userDolibarrWithoutCP, $userCP) {

        if (empty($userCP))
            $userCP = 0;
        dol_syslog(get_class($this) . '::verifNbUsers userDolibarr=' . $userDolibarrWithoutCP . ' userCP=' . $userCP);

        // On vérifie les users Dolibarr sans CP
        if ($userDolibarrWithoutCP > 0) {
            $this->db->begin();

            $this->updateConfCP('nbUser', $userDolibarrWithoutCP);

            $listUsersCP = $this->fetchUsers(true, false);

            // On séléctionne les utilisateurs qui ne sont pas déjà dans le module
            $sql = "SELECT u.rowid, u.lastname, u.firstname";
            $sql.= " FROM " . MAIN_DB_PREFIX . "user as u";
            if ($listUsersCP != '')
                $sql.= " WHERE u.rowid NOT IN(" . $listUsersCP . ")";

            $resql = $this->db->query($sql);
            if ($resql) {
                $i = 0;
                $num = $this->db->num_rows($result);

                while ($i < $num) {
                    $obj = $this->db->fetch_object($resql);
                    $uid = $obj->rowid;

                    // On ajoute l'utilisateur
                    //print "Add user rowid = ".$uid." into database holiday";

                    $result = $this->createCPusers(true, $uid);

                    $i++;
                }

                $this->db->commit();
            } else {
                // Erreur SQL
                $this->error = "Error " . $this->db->lasterror();
                dol_syslog(get_class($this) . "::verifNbUsers " . $this->error, LOG_ERR);
                $this->db->rollback();
                return -1;
            }
        } else {
            $this->db->begin();

            // Si il y a moins d'utilisateur Dolibarr que dans le module CP

            $this->updateConfCP('nbUser', $userDolibarrWithoutCP);

            $listUsersDolibarr = $this->fetchUsers(true, true);

            // On séléctionne les utilisateurs qui ne sont pas déjà dans le module
            $sql = "SELECT u.fk_user";
            $sql.= " FROM " . MAIN_DB_PREFIX . "holiday_users as u";
            $sql.= " WHERE u.fk_user NOT IN (" . $listUsersDolibarr . ")";

            $resql = $this->db->query($sql);

            // Si pas d'erreur SQL
            if ($resql) {

                $i = 0;
                $num = $this->db->num_rows($resql);

                while ($i < $num) {

                    $obj = $this->db->fetch_object($resql);

                    // On supprime l'utilisateur
                    $this->deleteCPuser($obj->fk_user);

                    $i++;
                }

                $this->db->commit();
            } else {
                // Erreur SQL
                $this->error = "Error " . $this->db->lasterror();
                dol_syslog(get_class($this) . "::verifNbUsers " . $this->error, LOG_ERR);
                $this->db->rollback();
                return -1;
            }
        }
    }

    /**
     *  Liste les évènements de congés payés enregistré
     *
     *  @return     int         -1 si erreur, 1 si OK et 2 si pas de résultat
     */
    function fetchEventsCP() {
        global $langs;

        $sql = "SELECT";
        $sql.= " cpe.rowid,";
        $sql.= " cpe.name,";
        $sql.= " cpe.value";

        $sql.= " FROM " . MAIN_DB_PREFIX . "holiday_events as cpe";

        dol_syslog(get_class($this) . "::fetchEventsCP sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);

        // Si pas d'erreur SQL
        if ($resql) {

            $i = 0;
            $tab_result = $this->events;
            $num = $this->db->num_rows($resql);

            // Si pas d'enregistrement
            if (!$num) {
                return 2;
            }

            // On liste les résultats et on les ajoutent dans le tableau
            while ($i < $num) {

                $obj = $this->db->fetch_object($resql);

                $tab_result[$i]['rowid'] = $obj->rowid;
                $tab_result[$i]['name'] = $obj->name;
                $tab_result[$i]['value'] = $obj->value;

                $i++;
            }
            // Retourne 1 et ajoute le tableau à la variable
            $this->events = $tab_result;
            return 1;
        } else {
            // Erreur SQL
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetchEventsCP " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *  Créer un évènement de congés payés
     *
     * 	@param	User	$user			User
     * 	@param	int		$notrigger		No trigger
     *  @return int         			-1 si erreur, id si OK
     */
    function createEventCP($user, $notrigger = 0) {
        global $conf, $langs;
        $error = 0;

        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "holiday_events (";

        $sql.= "name,";
        $sql.= "value";

        $sql.= ") VALUES (";

        $sql.= " '" . $this->db->escape($this->optName) . "',";
        $sql.= " '" . $this->optValue . "'";
        $sql.= ")";

        $this->db->begin();

        dol_syslog(get_class($this) . "::createEventCP sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error) {
            $this->optRowid = $this->db->last_insert_id(MAIN_DB_PREFIX . "holiday_events");
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::createEventCP " . $errmsg, LOG_ERR);
                $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return $this->optRowid;
        }
    }

    /**
     *  Met à jour les évènements de congés payés
     *
     * 	@param	int		$rowid		Row id
     * 	@param	string	$name		Name
     * 	@param	value	$value		Value
     *  @return int         		-1 si erreur, id si OK
     */
    function updateEventCP($rowid, $name, $value) {

        $sql = "UPDATE " . MAIN_DB_PREFIX . "holiday_events SET";
        $sql.= " name = '" . $this->db->escape($name) . "', value = '" . $value . "'";
        $sql.= " WHERE rowid = '" . $rowid . "'";

        $result = $this->db->query($sql);

        if ($result) {
            return true;
        }

        return false;
    }

    /**
     * Select event
     *
     * @return string|boolean		Select Html to select type of holiday
     */
    function selectEventCP() {

        $sql = "SELECT rowid, name, value";
        $sql.= " FROM " . MAIN_DB_PREFIX . "holiday_events";

        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);
            $i = 0;

            $out = '<select name="list_event" class="flat" >';
            $out.= '<option value="-1">&nbsp;</option>';
            while ($i < $num) {
                $obj = $this->db->fetch_object($result);

                $out.= '<option value="' . $obj->rowid . '">' . $obj->name . ' (' . $obj->value . ')</option>';
                $i++;
            }
            $out.= '</select>';

            return $out;
        } else {
            return false;
        }
    }

    /**
     * deleteEvent
     *
     * @param 	int		$rowid		Row id
     * @return 	boolean				Success or not
     */
    function deleteEventCP($rowid) {

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "holiday_events";
        $sql.= " WHERE rowid = '" . $rowid . "'";

        $result = $this->db->query($sql);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * getValueEventCp
     *
     * @param 	int		$rowid		Row id
     * @return string|boolean
     */
    function getValueEventCp($rowid) {

        $sql = "SELECT value";
        $sql.= " FROM " . MAIN_DB_PREFIX . "holiday_events";
        $sql.= " WHERE rowid = '" . $rowid . "'";

        $result = $this->db->query($sql);

        if ($result) {
            $obj = $this->db->fetch_array($result);
            return number_format($obj['value'], 2);
        } else {
            return false;
        }
    }

    /**
     * getNameEventCp
     *
     * @param 	int		$rowid		Row id
     * @return unknown|boolean
     */
    function getNameEventCp($rowid) {

        $sql = "SELECT name";
        $sql.= " FROM " . MAIN_DB_PREFIX . "holiday_events";
        $sql.= " WHERE rowid = '" . $rowid . "'";

        $result = $this->db->query($sql);

        if ($result) {
            $obj = $this->db->fetch_array($result);
            return $obj['name'];
        } else {
            return false;
        }
    }

    /**
     * addLogCP
     *
     * @param 	int		$fk_user_action		Id user creation
     * @param 	int		$fk_user_update		Id user update
     * @param 	int		$type				Type
     * @param 	int		$new_solde			New value
     * @return number|string
     */
    function addLogCP($fk_user_action, $fk_user_update, $type, $new_solde, $is_rtt = false, $currentYear = false) {

        global $conf, $langs;

        $error = 0;
        $prev_solde = '';
        if ($is_rtt)
            $prev_solde = $this->getRTTforUser($fk_user_update);
        else if ($currentYear)
            $prev_solde = $this->getCurrentYearCPforUser($fk_user_update);
        else
            $prev_solde = $this->getNextYearCPforUser($fk_user_update);

        $new_solde = number_format($new_solde, 2, '.', '');

        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "holiday_logs (";

        $sql.= "date_action,";
        $sql.= "fk_user_action,";
        $sql.= "fk_user_update,";
        $sql.= "type_action,";
        $sql.= "prev_solde,";
        $sql.= "new_solde";

        $sql.= ") VALUES (";

        $sql.= " NOW(), ";
        $sql.= " '" . $fk_user_action . "',";
        $sql.= " '" . $fk_user_update . "',";
        $sql.= " '" . $this->db->escape($type) . "',";
        $sql.= " '" . $prev_solde . "',";
        $sql.= " '" . $new_solde . "'";
        $sql.= ")";

        $this->db->begin();

        dol_syslog(get_class($this) . "::addLogCP sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error) {
            $this->optRowid = $this->db->last_insert_id(MAIN_DB_PREFIX . "holiday_logs");
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::addLogCP " . $errmsg, LOG_ERR);
                $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return $this->optRowid;
        }
    }

    /**
     *  Liste le log des congés payés
     *
     *  @param	string	$order      Filtrage par ordre
     *  @param  string	$filter     Filtre de séléction
     *  @return int         		-1 si erreur, 1 si OK et 2 si pas de résultat
     */
    function fetchLog($order, $filter) {
        global $langs;

        $sql = "SELECT";
        $sql.= " cpl.rowid,";
        $sql.= " cpl.date_action,";
        $sql.= " cpl.fk_user_action,";
        $sql.= " cpl.fk_user_update,";
        $sql.= " cpl.type_action,";
        $sql.= " cpl.prev_solde,";
        $sql.= " cpl.new_solde";

        $sql.= " FROM " . MAIN_DB_PREFIX . "holiday_logs as cpl";
        $sql.= " WHERE cpl.rowid > 0"; // To avoid error with other search and criteria
        // Filtrage de séléction
        if (!empty($filter)) {
            $sql.= " " . $filter;
        }

        // Ordre d'affichage
        if (!empty($order)) {
            $sql.= " " . $order;
        }

        dol_syslog(get_class($this) . "::fetchLog sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);

        // Si pas d'erreur SQL
        if ($resql) {

            $i = 0;
            $tab_result = $this->logs;
            $num = $this->db->num_rows($resql);

            // Si pas d'enregistrement
            if (!$num) {
                return 2;
            }

            // On liste les résultats et on les ajoutent dans le tableau
            while ($i < $num) {

                $obj = $this->db->fetch_object($resql);

                $tab_result[$i]['rowid'] = $obj->rowid;
                $tab_result[$i]['date_action'] = $obj->date_action;
                $tab_result[$i]['fk_user_action'] = $obj->fk_user_action;
                $tab_result[$i]['fk_user_update'] = $obj->fk_user_update;
                $tab_result[$i]['type_action'] = $obj->type_action;
                $tab_result[$i]['prev_solde'] = $obj->prev_solde;
                $tab_result[$i]['new_solde'] = $obj->new_solde;

                $i++;
            }
            // Retourne 1 et ajoute le tableau à la variable
            $this->logs = $tab_result;
            return 1;
        } else {
            // Erreur SQL
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetchLog " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     * 	id must be 0 if object instance is a specimen.
     *
     *  @return	void
     */
    function initAsSpecimen() {
        global $user, $langs;

        // Initialise parameters
        $this->id = 0;
        $this->specimen = 1;

        $this->fk_user = 1;
        $this->description = 'SPECIMEN description';
        $this->date_debut = dol_now();
        $this->date_fin = dol_now() + (24 * 3600);
        $this->fk_validator = 1;
        $this->halfday = 0;
    }

    /**
     *  Retourne l'intitulé du type de congé
     *
     *  @return	string
     */
    function getTypeLabel($toLowerCase = false) {
        if (!isset($this->type_conges) || !key_exists($this->type_conges, self::$typesConges))
            $this->type_conges = 0;

        if ($toLowerCase && ($this->type_conges != 2))
            return strtolower(self::$typesConges[$this->type_conges]);

        return self::$typesConges[$this->type_conges];
    }

    /**
     *  Ajout Synopsis - Gestion agenda
     *
     *  @return	bool            ok ou non
     */
    function onStatusUpdate($updateUser) {
        if (!isset($this->id) || empty($this->id))
            return false;

        $ac = new ActionComm($this->db);
        $dateBegin = new DateTime($this->db->idate($this->date_debut));
        $dateEnd = new DateTime($this->db->idate($this->date_fin));
        $check = true;

        $substitute = null;
        if (isset($this->fk_substitute) && $this->fk_substitute > 0) {
            $substitute = new User($this->db);
            $substitute->fetch($this->fk_substitute);
        }

        $fk_action = 0;
        $tabDebLib = explode(" ", $this->getTypeLabel());
        if (isset($tabDebLib[0])) {
            $sql = $this->db->query("SELECT id 
                FROM  `" . MAIN_DB_PREFIX . "c_actioncomm` 
                WHERE  `libelle` LIKE  '%" . $tabDebLib[0] . "%'");
            if ($this->db->num_rows($sql) > 0) {
                $ligne = $this->db->fetch_object($sql);
                $fk_action = $ligne->id;
            }
        }


        if (isset($this->fk_actioncomm) && !empty($this->fk_actioncomm)) {
            $ac->fetch($this->fk_actioncomm);
        }
        if ($ac->id > 0) {
            if ($this->statut == 2 || $this->statut == 3 || $this->statut == 6) {
                // Mise à jour
                // On envisage la possibilité que les dates aient pu être modifiées
                if ($this->statut == 6)
                    $ac->percentage = -1;

                if ($fk_action)
                    $ac->fk_action = $fk_action;

                if (!$this->halfday) {
                    $dateBegin->setTime(8-self::$decalageHeure, 0, 0);
                    $dateEnd->setTime(19-self::$decalageHeure, 59, 59);
                } else if ($this->halfday == 2) {
                    $dateBegin->setTime(14-self::$decalageHeure, 0, 0);
                    $dateEnd->setTime(13-self::$decalageHeure, 59, 59);
                } else if ($this->halfday > 0) {
                    $dateBegin->setTime(8-self::$decalageHeure, 0, 0);
                    $dateEnd->setTime(13-self::$decalageHeure, 59, 59);
                } else if ($this->halfday < 0) {
                    $dateBegin->setTime(14-self::$decalageHeure, 0, 0);
                    $dateEnd->setTime(19-self::$decalageHeure, 59, 59);
                }

                // Idem pour la liste des users: 
                if (is_array($this->fk_user)) {
                    $userAssigned = array();
                    foreach ($this->fk_user as $user_id)
                        $userAssigned[] = array('id' => $user_id, 'transparency' => 1);
                    $ac->userassigned = $userAssigned;
                }

                date_default_timezone_set("GMT");

                $ac->datep = $dateBegin->format('Y-m-d H:i:s');
                $ac->datef = $dateEnd->format('Y-m-d H:i:s');
                $ac->note = $this->LibStatut($this->statut) . ' - ' . (isset($substitute) ? 'Remplaçant: ' . dolGetFirstLastname($substitute->firstname, $substitute->lastname . '.') : 'Aucun rempaçant désigné.');

                $result = $ac->update($updateUser);

                date_default_timezone_set("Europe/Paris");

                if ($result == 0) {
                    // Forçage de la màj (si $updateUser n'a pas les droits):
                    $sql = "UPDATE " . MAIN_DB_PREFIX . "actioncomm ";
                    $sql .= "SET note = '" . $this->db->escape($ac->note) . "'";
                    $sql .= ", datep = '" . $dateBegin->format('Y-m-d H:i:s') . "'";
                    $sql .= ", datep2 = '" . $dateEnd->format('Y-m-d H:i:s') . "'";
                    if ($this->statut == 6)
                        $sql .= ", percent = -1";
                    $sql.= " WHERE id=" . $ac->id;
                    dol_syslog("SynopsisHoliday::onStatusUpdate sql=" . $sql);
                    if ($this->db->query($sql)) {
                        $this->db->commit();
                    } else {
                        $this->db->rollback();
                        dol_syslog("SynopsisHoliday::onStatusUpdate sqlError: " . $this->db->lasterror(), LOG_ERR);
                        $check = false;
                    }
                } else if ($result < 0) {
                    $check = false;
                }
//                else {
//                    // Hack:
//                    $sql = "UPDATE " . MAIN_DB_PREFIX . "actioncomm ";
//                    $sql .= "SET datep = '" . $ac->datep . "'";
//                    $sql .= ", datep2 = '" . $ac->datef . "'";
//                    $sql.= " WHERE id=" . $ac->id;
//                    if ($this->db->query($sql)) {
//                        $this->db->commit();
//                    } else {
//                        $this->db->rollback();
//                        dol_syslog("SynopsisHoliday::onStatusUpdate sqlError: " . $this->db->lasterror(), LOG_ERR);
//                    }
//                }
            } else {
                // Suppression:
                $result = $ac->delete();
                if ($result < 0) {
                    dol_syslog("SynopsisHoliday::annulation suppression de l'event fail id action : " . $this->fk_actioncomm, LOG_ERR);
                    $check = false;
                }
            }
        } else if ($this->statut == 2 || $this->statut == 3 || $this->statut == 6) {
            // Création
            if ($this->statut == 6)
                $ac->percentage = -1;
            $ac->type_id = 50;
            $ac->label = $this->getTypeLabel();
            $ac->transparency = 1;
            $ac->fk_element = $this->id;
            $ac->elementtype = $this->element;

            if (is_array($this->fk_user)) {
                $usersAssigned = array();
                foreach ($this->fk_user as $user_id)
                    $userAssigned[] = array('id' => $user_id, 'transparency' => 1);
                $ac->userassigned = $userAssigned;
            } else {
                $ac->userownerid = $this->fk_user;
                $ac->userassigned[] = array('id' => $this->fk_user, 'transparency' => 1);
            }

            if ($fk_action)
                $ac->type_id = $fk_action;


            if (!$this->halfday) {
                $dateBegin->setTime(8-self::$decalageHeure, 0, 0);
                $dateEnd->setTime(19-self::$decalageHeure, 59, 59);
            } else if ($this->halfday == 2) {
                $dateBegin->setTime(14-self::$decalageHeure, 0, 0);
                $dateEnd->setTime(13-self::$decalageHeure, 59, 59);
            } else if ($this->halfday > 0) {
                $dateBegin->setTime(8-self::$decalageHeure, 0, 0);
                $dateEnd->setTime(13-self::$decalageHeure, 59, 59);
            } else if ($this->halfday < 0) {
                $dateBegin->setTime(14-self::$decalageHeure, 0, 0);
                $dateEnd->setTime(19-self::$decalageHeure, 59, 59);
            }
            $ac->datep = $dateBegin->format('Y-m-d H:i:s');
            $ac->datef = $dateEnd->format('Y-m-d H:i:s');
            $ac->note = $this->LibStatut($this->statut) . ' - ' . (isset($substitute) ? 'Remplaçant: ' . $substitute->firstname . ' ' . $substitute->lastname : 'Aucun rempaçant désigné.');
            $result = $ac->add($updateUser);
            if ($result < 0)
                $check = false;
            else
                $this->fk_actioncomm = $result;
//            {
//                // Hack:
//                $sql = "UPDATE " . MAIN_DB_PREFIX . "actioncomm ";
//                $sql .= "SET datep = '" . $ac->datep . "'";
//                $sql .= ", datep2 = '" . $ac->datef . "'";
//                $sql.= " WHERE id=" . $ac->id;
//                if ($this->db->query($sql)) {
//                    $this->db->commit();
//                } else {
//                    $this->db->rollback();
//                    dol_syslog("SynopsisHoliday::onStatusUpdate sqlError: " . $this->db->lasterror(), LOG_ERR);
//                }
//            }
        } else if ($this->statut == 1 || $this->statut == 4 || $this->statut == 5) {
            return true;
        }

        return $check;
    }

    /**
     *  Ajout Synopsis - Liste des RDV à transmettre au remplaçant
     *
     *  @return	array            Liste des rowid actioncomm - 0 si erreur. 
     */
    function fetchRDV($fk_user = -1) {
        if (empty($this->id) || empty($this->fk_user) || empty($this->date_debut) || empty($this->date_fin))
            return 0;

        if (is_array($this->fk_user) || ($fk_user < 0 && empty($this->fk_user)))
            return 0;

        $dateBegin = new DateTime();
        $dateBegin->setTimestamp($this->date_debut);
        $dateEnd = new DateTime();
        $dateEnd->setTimestamp($this->date_fin);

        if (!$this->halfday) {
            $dateBegin->setTime(8-self::$decalageHeure, 0, 0);
            $dateEnd->setTime(19-self::$decalageHeure, 59, 59);
        } else if ($this->halfday == 2) {
            $dateBegin->setTime(14-self::$decalageHeure, 0, 0);
            $dateEnd->setTime(13-self::$decalageHeure, 59, 59);
        } else if ($this->halfday > 0) {
            $dateBegin->setTime(8-self::$decalageHeure, 0, 0);
            $dateEnd->setTime(13-self::$decalageHeure, 59, 59);
        } else if ($this->halfday < 0) {
            $dateBegin->setTime(14-self::$decalageHeure, 0, 0);
            $dateEnd->setTime(19-self::$decalageHeure, 59, 59);
        }


        $sql = "SELECT `id` FROM " . MAIN_DB_PREFIX . "actioncomm WHERE `fk_user_action` = " . (($fk_user >= 0) ? $fk_user : $this->fk_user);
        $sql .= " AND `datep` <= ";
        $sql .= "'" . $dateEnd->format('Y-m-d H:i:s') . "' AND fk_action != 40 AND `datep2` >= '" . $dateBegin->format('Y-m-d H:i:s') . "';";

        dol_syslog(get_class($this) . "::fetchRDV sql=" . $sql, LOG_DEBUG);
        date_default_timezone_set("GMT");
        $resql = $this->db->query($sql);
        date_default_timezone_set("Europe/Paris");

        if ($resql) {
            $i = 0;
            $num = $this->db->num_rows($resql);
            if (!$num)
                return array();

            $list = array();
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                if (isset($obj)) {
                    if (empty($this->fk_actioncomm) || (!empty($this->fk_actioncomm) && $this->fk_actioncomm != $obj->id))
                        $list[] = $obj->id;
                }
                $i++;
            }
            return $list;
        }
        return 0;
    }

    function recrediteSold() {
        global $langs;
        if (!is_array($this->fk_user)) {
            $tabUser = array($this->fk_user);
        } else {
            $tabUser = $this->fk_user;
        }
        $nbopenedday = num_open_dayUser($this->fk_user, $this->date_debut_gmt, $this->date_fin_gmt, 0, 1, $this->halfday);
        foreach ($tabUser as $fk_user) {
            global $userHoliday;
        $userHoliday = $fk_user;
            if ($this->type_conges == 0) {
                $soldes = $this->getCpforUser($fk_user, $this->date_debut, $this->date_fin, $this->halfday, true);
                if (isset($solde['error'])) {
                    $error = $solde['error'];
                } else {
                    $nbHolidayDeducted = $this->getConfCP('nbHolidayDeducted');
                    // solde année en cours:
                    if ($soldes['nbOpenDayCurrent'] > 0) {
                        $newSolde = $soldes['nb_holiday_current'] + ($soldes['nbOpenDayCurrent'] * $nbHolidayDeducted);
                        $result1 = $this->addLogCP($user->id, $fk_user, $langs->transnoentitiesnoconv("HolidaysCancelation") . ' (année en cours)', $newSolde, false, true);
                        $result2 = $this->updateSoldeCP($fk_user, $newSolde, true);
                    } else {
                        $result1 = 1;
                        $result2 = 1;
                    }
                    // solde année n+1
                    if ($soldes['nbOpenDayNext'] > 0) {
                        $newSolde = $soldes['nb_holiday_next'] + ($soldes['nbOpenDayNext'] * $nbHolidayDeducted);
                        $result3 = $this->addLogCP($user->id, $fk_user, $langs->transnoentitiesnoconv("HolidaysCancelation") . ' (année suivante)', $newSolde, false, false);
                        $result4 = $this->updateSoldeCP($fk_user, $newSolde, false);
                    } else {
                        $result3 = 1;
                        $result4 = 1;
                    }

                    if ($result1 < 0 || $result2 < 0 || $result3 < 0 || $result4 < 0) {
                        $error = $langs->trans('ErrorCantDeleteCP');
                    }
                }
            } else if ($this->type_conges == 2) {
                $soldeActuel = $this->getRTTforUser($fk_user);
                $newSolde = $soldeActuel + ($nbopenedday * $this->getConfCP('nbRTTDeducted'));
                $result1 = $this->addLogCP($user->id, $fk_user, $langs->transnoentitiesnoconv("RTTCancelation"), $newSolde, true);
                $result2 = $this->updateSoldeRTT($fk_user, $newSolde);
                if ($result1 < 0 || $result2 < 0) {
                    $error = $langs->trans('ErrorCantDeleteCP');
                }
            }
        }
    }

}
