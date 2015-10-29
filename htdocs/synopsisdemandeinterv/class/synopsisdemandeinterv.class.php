<?php

/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
/*
 */

/**        \file       htdocs/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php
  \ingroup    synopsisdemandeinterv
  \brief      Fichier de la classe des gestion des fiches interventions
  \version    $Id: synopsisdemandeinterv.class.php,v 1.58 2008/07/12 12:45:30 eldy Exp $
 */
require_once(DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

/**
 *     \class      synopsisdemandeinterv
 *    \brief      Classe des gestion des fiches interventions
 */
class Synopsisdemandeinterv extends CommonObject {

    public $db;
    public $element = 'synopsisdemandeinterv';
    public $table_element = 'synopsisdemandeinterv';
    public $fk_element = 'fk_synopsisdemandeinterv';
    public $table_element_line = 'synopsisdemandeintervdet';
    public $id;
    public $socid;        // Id client
    public $client;        // Objet societe client (a charger par fetch_client)
    public $author;
    public $ref;
    public $date;
    public $date_delivery;
    public $duree;
    public $description;
    public $note_private;
    public $fk_commande;
    public $fk_contrat;
    public $note_public;
    public $projet_id;
    public $modelpdf;
    public $fk_di = "";
    public $lignes = array();

    /**
     *    \brief      Constructeur de la classe
     *    \param      DB            Handler acces base de donnees
     *    \param      socid            Id societe
     */
    function Synopsisdemandeinterv($DB, $socid = "") {
        global $langs;

        $this->db = $DB;
        $this->socid = $socid;
        $this->products = array();
        $this->projet_id = 0;

        // Statut 0=brouillon, 1=valide
        $this->statuts[0] = $langs->trans("Draft");
        $this->statuts[1] = $langs->trans("Validated");
        $this->statuts[2] = $langs->trans("En cours");
        $this->statuts[3] = $langs->trans("Cl&ocirc;turé");
        $this->statuts_short[0] = $langs->trans("Draft");
        $this->statuts_short[1] = $langs->trans("Validated");
        $this->statuts_short[2] = $langs->trans("En cours");
        $this->statuts_short[3] = $langs->trans("Cl&ocirc;turé");
    }

    /*
     *        \brief      Cree une fiche intervention en base
     *        \return        int        <0 if KO, >0 if OK
     */

    function getFI() {
        $return = array();
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "element_element WHERE sourcetype='DI' AND targettype='FI' AND fk_source = " . $this->id;
        $res = $this->db->query($requete);
        while ($result = $this->db->fetch_object($res)) {
            $return[] = $result->fk_target;
        }
        return $return;
    }

    function fetch_client() {
        $this->client = new Societe($this->db);
        $this->client->fetch($this->socid);
    }

    function create() {
        global $user, $langs, $conf;
        dol_syslog("synopsisdemandeinterv.class::create ref=" . $this->ref);
        if (!is_numeric($this->duree)) {
            $this->duree = 0;
        }
        if ($this->socid <= 0) {
            $this->error = 'ErrorBadParameterForFunc';
            dol_syslog("synopsisdemandeinterv::create " . $this->error, LOG_ERR);
            return -1;
        }
//    $synopsisdemandeinterv->fk_user_prisencharge = $_POST["userid"];
//    $synopsisdemandeinterv->fk_commande = $_POST["comLigneId"];

        $this->db->begin();

        // on verifie si la ref n'est pas utilisee
        $soc = new Societe($this->db);
        $result = $soc->fetch($this->socid);
        $this->soc = $soc;
        $this->verifyNumRef();
        $this->statut = 0;

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "synopsisdemandeinterv (fk_soc, datec, ref, fk_user_author, description, model_pdf";
        if ($this->projet_id)
            $sql.= ", fk_projet";
        if ($this->fk_user_prisencharge > 0)
            $sql.= ", fk_user_prisencharge";
        if ($this->fk_commande > 0)
            $sql.= ", fk_commande";
        if ($this->fk_contrat > 0)
            $sql .= ", fk_contrat";
        if ($this->date > 0)
            $sql.= ", datei";
        $sql.= ") ";
        $sql.= " VALUES (" . $this->socid . ",";
        $sql.= " now(), '" . $this->ref . "', " . $this->author;
        $sql.= ", '" . addslashes($this->description) . "', '" . $this->modelpdf . "'";
        if ($this->projet_id)
            $sql .= ", " . $this->projet_id;
        if ($this->fk_user_prisencharge > 0)
            $sql .= ", " . $this->fk_user_prisencharge;
        if ($this->fk_commande > 0)
            $sql .= ", " . $this->fk_commande;
        if ($this->fk_contrat > 0)
            $sql .= ", " . $this->fk_contrat;
        if ($this->date > 0)
            $sql.= ", '" . $this->db->idate($this->date) . "'";
        $sql.= ")";
        $sqlok = 0;
        dol_syslog("synopsisdemandeinterv::create sql=" . $sql);
        $result = $this->db->query($sql);
        if ($result) {
            $this->id = $this->db->last_insert_id("" . MAIN_DB_PREFIX . "synopsisdemandeinterv");
            $this->db->commit();
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('SYNOPSISDEMANDEINTERV_CREATE', $this, $user, $langs, $conf);
            if ($result < 0) {
                $error++;
                $this->errors = $interface->errors;
            }
            // Fin appel triggers
            return $this->id;
        } else {
            $this->error = $this->db->error();
            dol_syslog("synopsisdemandeinterv::create " . $this->error, LOG_ERR);
            $this->db->rollback();
            return -1;
        }
    }

    /*
     *    \brief        Met a jour une intervention
     *    \return        int        <0 si ko, >0 si ok
     */

    function update($id = null) {
        global $user, $langs, $conf;
        if($id == null)
            $id = $this->id;
        if (!is_numeric($this->duree)) {
            $this->duree = 0;
        }
        if (!strlen($this->projet_id)) {
            $this->projet_id = 0;
        }

        /*
         *  Insertion dans la base
         */
        $sql = "UPDATE " . MAIN_DB_PREFIX . "synopsisdemandeinterv SET ";
        $sql .= " datei = " . $this->db->idate($this->date);
        $sql .= ", description  = '" . addslashes($this->description) . "'";
        $sql .= ", duree = " . $this->duree;
        $sql .= ", fk_projet = " . $this->projet_id;
        $sql .= " WHERE rowid = " . $id;

        dol_syslog("synopsisdemandeinterv::update sql=" . $sql);
        if (!$this->db->query($sql)) {
            $this->error = $this->db->error();
            dol_syslog("synopsisdemandeinterv::update error " . $this->error, LOG_ERR);
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('SYNOPSISDEMANDEINTERV_UPDATE', $this, $user, $langs, $conf);
            if ($result < 0) {
                $error++;
                $this->errors = $interface->errors;
            }
            // Fin appel triggers
            return -1;
        }

        return 1;
    }

    /**
     *        \brief        Charge en memoire la fiche intervention
     *        \param        rowid        Id de la fiche a charger
     *        \return        int            <0 si ko, >0 si ok
     */
    function fetch($rowid) {
        $sql = "SELECT rowid, ref, description, fk_soc, fk_statut,fk_user_prisencharge, fk_user_author, fk_contrat, fk_commande, total_ht, total_tva, total_ttc, ";
        $sql.= " datei as di, duree, fk_projet, note_public, note_private, model_pdf";
        $sql.= " FROM " . MAIN_DB_PREFIX . "synopsisdemandeinterv";
        $sql.= " WHERE rowid=" . $rowid;

        dol_syslog("synopsisdemandeinterv::fetch sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql) > 0) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->ref = $obj->ref;
                $this->description = $obj->description;
                $this->socid = $obj->fk_soc;
                $this->fk_soc = $obj->fk_soc;
                $this->fk_contrat = $obj->fk_contrat;
                $this->fk_commande = $obj->fk_commande;
                if ($obj->fk_soc > 0) {
                    $tmpSoc = new Societe($this->db);
                    $tmpSoc->fetch($obj->fk_soc);
                    $this->societe = $tmpSoc;
                }
                $this->statut = $obj->fk_statut;
                $this->date = $this->db->jdate($obj->di);
                $this->duree = $obj->duree;
                $this->projetidp = $obj->fk_projet;
                $this->note_public = $obj->note_public;
                $this->note_private = $obj->note_private;
                $this->modelpdf = $obj->model_pdf;
                $this->total_ht = $obj->total_ht;
                $this->total_tva = $obj->total_tva;
                $this->total_ttc = $obj->total_ttc;
                $this->author = $obj->fk_user_author;
                $this->fk_user_prisencharge = $obj->fk_user_prisencharge;
                $this->user_prisencharge = new User($this->db);
                if ($this->fk_user_prisencharge)
                    $this->user_prisencharge->fetch($this->fk_user_prisencharge);
                $this->user_author_id = $obj->fk_user_author;

                if ($this->statut == 0)
                    $this->brouillon = 1;

                $this->db->free($resql);
                return $this->id;
//            } else {
//                $this->error = $this->db->error();
//                dol_syslog("synopsisdemandeinterv::fetch error " . $this->error, LOG_ERR);
//                return -2;
            }
        } else {
            $this->error = $this->db->error();
            dol_syslog("synopsisdemandeinterv::fetch error " . $this->error, LOG_ERR);
            return -1;
        }
    }

    private function sendMail($subject, $to, $from, $msg, $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array(), $addr_cc = '', $addr_bcc = '', $deliveryreceipt = 0, $msgishtml = 1, $errors_to = '') {
        global $mysoc;
        global $langs;
        require_once(DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php');
        $mail = new CMailFile($subject, $to, $from, $msg, $filename_list, $mimetype_list, $mimefilename_list, $addr_cc, $addr_bcc, $deliveryreceipt, $msgishtml, $errors_to);
        $res = $mail->sendfile();
        if ($res) {
            return (1);
        } else {
            return -1;
        }
    }

    /**
     *        \brief        Valide une fiche intervention
     *        \param        user        User qui valide
     *        \return        int            <0 si ko, >0 si ok
     */
    function valid($user, $outputdir = "") {
        global $langs, $conf;

        $this->db->begin();

        $sql = "UPDATE " . MAIN_DB_PREFIX . "synopsisdemandeinterv";
        $sql.= " SET fk_statut = 1, date_valid=now(), fk_user_valid=" . $user->id;
        $sql.= " WHERE rowid = " . $this->id . " AND fk_statut = 0";
//die($sql);
        dol_syslog("synopsisdemandeinterv::valid sql=" . $sql);
        $resql = $this->db->query($sql);

        if ($resql) {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('SYNOPSISDEMANDEINTERV_VALIDATE', $this, $user, $langs, $conf);
            if ($result < 0) {
                $error++;
                $this->errors = $interface->errors;
            }
            // Fin appel triggers

            if ($resql) {

                $this->db->commit();


                $this->synchroAction();
                //notification
                if (isset($conf->global->BIMP_MAIL_GESTPROD)) {
                    $this->info($this->id);
                    $subject = utf8_encode("[Nouvelle Intervention] Nouvelle intervention chez " . $this->societe->nom);
                    $to = $this->user_prisencharge->email;

                    $msg = "Bonjour " . $this->user_prisencharge->fullname . ",<br/><br/>";
                    $msg .= "Une nouvelle intervention est pr&eacute;vue chez " . $this->societe->getNomUrl(1, 6) . ".";
                    $msg .= "<br/>Le " . dol_print_date($this->date);
                    $msg .= "<br/>";
                    $msg .= "<br/>";
                    $msg .= "Ref Demande Intervertion : " . $this->getNomUrl(1);

                    $msg .= "<br/><br/>Cordialement,<br/>\nGLE\n";
                    $from = $conf->global->BIMP_MAIL_FROM;
                    $addr_cc = $conf->global->BIMP_MAIL_GESTPROD;

                    mailSyn2($subject, $to, $from, $msg, array(), array(), array(), $addr_cc, '', 0, $msgishtml = 1, $from);
                }


                return 1;
            } else {
                $this->db->rollback();
                $this->error = join(',', $this->errors);
                dol_syslog("synopsisdemandeinterv::update " . $this->error, LOG_ERR);
                return -1;
            }
        } else {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            dol_syslog("synopsisdemandeinterv::update " . $this->error, LOG_ERR);
            return -1;
        }
    }

    function invalid($user, $outputdir = "") {
        global $langs, $conf;

        $this->db->begin();

        $sql = "UPDATE " . MAIN_DB_PREFIX . "synopsisdemandeinterv";
        $sql.= " SET fk_statut = 0, date_valid=now(), fk_user_valid=" . $user->id;
        $sql.= " WHERE rowid = " . $this->id . " ";

        dol_syslog("synopsisdemandeinterv::valid sql=" . $sql);
        $resql = $this->db->query($sql);

        if ($resql) {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('SYNOPSISDEMANDEINTERV_INVALIDATE', $this, $user, $langs, $conf);
            if ($result < 0) {
                $error++;
                $this->errors = $interface->errors;
            }
            // Fin appel triggers

            if ($resql) {

                $this->db->commit();
                //notification
                return 1;
            } else {
                $this->db->rollback();
                $this->error = join(',', $this->errors);
                dol_syslog("synopsisdemandeinterv::update " . $this->error, LOG_ERR);
                return -1;
            }
        } else {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            dol_syslog("synopsisdemandeinterv::update " . $this->error, LOG_ERR);
            return -1;
        }
    }

    function SelectTarget($user) {
        global $langs, $conf;

        $this->db->begin();

        $sql = "UPDATE " . MAIN_DB_PREFIX . "synopsisdemandeinterv";
        $sql.= " SET fk_user_target=" . $user->id;
        $sql.= " WHERE rowid = " . $this->id . " ";

        dol_syslog("synopsisdemandeinterv::valid sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('SYNOPSISDEMANDEINTERV_ATTRIB', $this, $user, $langs, $conf);
            if ($result < 0) {
                $error++;
                $this->errors = $interface->errors;
            }
            // Fin appel triggers

            if (!$error) {
                $this->db->commit();
                return 1;
            } else {
                $this->db->rollback();
                $this->error = join(',', $this->errors);
                dol_syslog("synopsisdemandeinterv::update " . $this->error, LOG_ERR);
                return -1;
            }
        } else {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            dol_syslog("synopsisdemandeinterv::update " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**


     *
     *
     *     *  \brief Pris en charge  une fiche intervention
     *  \param  user User qui prend en charge
     *  \return int
     * <0 si ko, >0 si ok
     */
    function prisencharge($user, $outputdir = "") {
        global $langs, $conf;

        $this->db->begin();

        $sql = "UPDATE " . MAIN_DB_PREFIX . "synopsisdemandeinterv";
        $sql.= " SET fk_statut = 2, date_prisencharge=now(), fk_user_prisencharge=" . $user->id;
        $sql.= " WHERE rowid = " . $this->id . " AND fk_statut = 1";

        dol_syslog("synopsisdemandeinterv::valid sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('SYNOPSISDEMANDEINTERV_PRISENCHARGE', $this, $user, $langs, $conf);
            if ($result < 0) {
                $error++;
                $this->errors = $interface->errors;
            }
            // Fin appel triggers

            if (!$error) {
                $this->db->commit();
                //notification
                if ($conf->global->BIMP_MAIL_GESTPROD) {
                    $this->info($this->id);
                    $subject = "[Prise en charge intervention] Prise en charge de la DI " . $this->ref . " chez " . $this->societe->nom;
                    $to = $conf->global->BIMP_MAIL_GESTPROD;

                    $msg = "Bonjour, <br/><br/>";
                    $msg .= "" . $this->user_prisencharge->fullname . " a bien pris en charge la DI " . $this->ref . " chez " . $this->societe->getNomUrl(1, 6) . ".";
                    $msg .= "<br/>Le " . dol_print_date($this->date);
                    $msg .= "<br/>";
                    $msg .= "<br/>";
                    $msg .= "<br/>";
                    $msg .= "<br/>";
                    $msg .= "Ref Demande Intervertion : " . $this->getNomUrl(1);

                    $msg .= "<br/><br/>Cordialement,<br/>\nGLE\n";
                    $from = $conf->global->BIMP_MAIL_FROM;
                    $addr_cc = $this->user_prisencharge->email;

                    mailSyn2($subject, $to, $from, $msg, array(), array(), array(), $addr_cc, '', 0, 1, $from);
                }

                return 1;
            } else {
                $this->db->rollback();
                $this->error = join(',', $this->errors);
                dol_syslog("synopsisdemandeinterv::update " . $this->error, LOG_ERR);
                return -1;
            }
        } else {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            dol_syslog("synopsisdemandeinterv::update " . $this->error, LOG_ERR);
            return -1;
        }
    }

    function getExtra() {
        $this->tabExtra = array();
        $requete = "SELECT k.label,
                       k.type,
                       k.id,
                       v.extra_value,
                       k.fullLine
                  FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_key as k
             LEFT JOIN " . MAIN_DB_PREFIX . "synopsisfichinter_extra_value as v ON v.extra_key_refid = k.id AND v.interv_refid = " . $this->id . " AND typeI = 'DI'
                 WHERE (isQuality<>1 OR isQuality is null) AND isInMainPanel = 1 AND k.active = 1 AND inDi = 1 ORDER BY rang, label";
        $sql = $this->db->query($requete);
        $modulo = false;
        while ($res = $this->db->fetch_object($sql)) {
            $this->tabExtra[$res->id] = $res;
            $this->tabExtraV[$res->id] = $res->extra_value;
        }
        return $this->tabExtra;
    }

    function synchroAction($createIfNot = true) {
        global $user;
        $this->fetch($this->id);
        require_once(DOL_DOCUMENT_ROOT . "/comm/action/class/actioncomm.class.php");

        $tabAct = ActionComm::getActions($this->db, 0, $this->id, 'synopsisdemandeinterv');
        $update = (count($tabAct) > 0);

        if ($update)
            $action = $tabAct[0];
        else
            $action = new ActionComm($this->db);

        $this->getExtra();
        
        if ($this->tabExtraV[24] != "00:00" && $this->tabExtraV[24] != "") {
            $heureArr = $this->tabExtraV[24];
            if ($this->tabExtraV[27] != "00:00" && $this->tabExtraV[27] != "") {
                $heureDep = $this->tabExtraV[27];
            } else {
                $heureDep = $this->tabExtraV[26];
            }
        } else {
            $heureArr = $this->tabExtraV[25];
            $heureDep = $this->tabExtraV[27];
        }
        if ($heureArr == '' || $heureArr == '00:00')
            $heureArr = "08:00";
        if ($heureDep == '' || $heureDep == '00:00')
            $heureDep = "18:00"; //else die($heureDep);

        if ($this->date > 0) {
            $action->fk_action = $this->tabExtraV[37];
            $action->type_id = $action->fk_action;
            $action->datep = $this->db->jdate(date("Y-m-d", $this->date) . " " . $heureArr . ":00");
            $action->datef = $this->db->jdate(date("Y-m-d", $this->date) . " " . $heureDep . ":00");
            $action->elementtype = "synopsisdemandeinterv";
            $action->fk_element = $this->id;
            $action->percentage = -1;
            $soc = new Societe($this->db);
            $soc->fetch($this->socid);
            $action->societe = $soc;
            $action->socid = $this->socid;
            $action->label = $this->description . " DI : " . $this->ref;
            $action->note = $this->description;
            $action->userownerid = $this->user_prisencharge->id;
            $action->userassigned = array('id' => $this->user_prisencharge->id);

            if ($update)
                $action->update($user);
            elseif ($createIfNot)
                $action->add($user);
        }
    }

    function preparePrisencharge($userT, $outputdir = "") {
        global $langs, $conf, $user;

        $this->db->begin();

        $this->user_prisencharge = $userT;
        $sql = "UPDATE " . MAIN_DB_PREFIX . "synopsisdemandeinterv";
        $sql.= " SET fk_user_prisencharge=" . $userT->id;
        $sql.= " WHERE rowid = " . $this->id . " AND fk_statut < 2";

        dol_syslog("synopsisdemandeinterv::valid sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {

//            die("ok");
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('SYNOPSISDEMANDEINTERV_PRISENCHARGE', $this, $userT, $langs, $conf);
            if ($result < 0) {
                $error++;
                $this->errors = $interface->errors;
            }
            // Fin appel triggers

            if (!$error) {
                $this->db->commit();
                return 1;
            } else {
                $this->db->rollback();
                $this->error = join(',', $this->errors);
                dol_syslog("synopsisdemandeinterv::update " . $this->error, LOG_ERR);
                return -1;
            }
        } else {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            dol_syslog("synopsisdemandeinterv::update " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *  \brief Prise en charge  une fiche intervention
     *  \param  user User qui prend en charge
     *  \return int
     * <0 si ko, >0 si ok
     */
    function cloture($user, $outputdir = "") {
        global $langs, $conf;

        $this->db->begin();

        $sql = "UPDATE " . MAIN_DB_PREFIX . "synopsisdemandeinterv";
        $sql.= " SET fk_statut = 3, date_cloture=now(), fk_user_cloture=" . $user->id;
        $sql.= " WHERE rowid = " . $this->id . " AND fk_statut = 2";

        dol_syslog("synopsisdemandeinterv::valid sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('SYNOPSISDEMANDEINTERV_CLOTURE', $this, $user, $langs, $conf);
            if ($result < 0) {
                $error++;
                $this->errors = $interface->errors;
            }
            // Fin appel triggers

            if (!$error) {
                $this->db->commit();
                return 1;
            } else {
                $this->db->rollback();
                $this->error = join(',', $this->errors);
                dol_syslog("synopsisdemandeinterv::update " . $this->error, LOG_ERR);
                return -1;
            }
        } else {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            dol_syslog("synopsisdemandeinterv::update " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *    \brief      Retourne le libelle du statut de l'intervention
     *    \return     string      Libelle
     */
    function getLibStatut($mode = 0) {
        return $this->LibStatut($this->statut, $mode);
    }

    /**
     *    \brief      Renvoi le libelle d'un statut donne
     *    \param      statut      id statut
     *    \return     string      Libelle
     */
    function LibStatut($statut, $mode = 0) {
        if ($mode == 0) {
            return $this->statuts[$statut];
        }
        if ($mode == 1) {
            return $this->statuts_short[$statut];
        }
        if ($mode == 2) {
            if ($statut == 0)
                return img_picto($this->statuts_short[$statut], 'statut0') . ' ' . $this->statuts_short[$statut];
            if ($statut == 1)
                return img_picto($this->statuts_short[$statut], 'statut1') . ' ' . $this->statuts_short[$statut];
            if ($statut == 2)
                return img_picto($this->statuts_short[$statut], 'statut3') . ' ' . $this->statuts_short[$statut];
            if ($statut == 3)
                return img_picto($this->statuts_short[$statut], 'statut6') . ' ' . $this->statuts_short[$statut];
        }
        if ($mode == 3) {
            if ($statut == 0)
                return img_picto($this->statuts_short[$statut], 'statut0');
            if ($statut == 1)
                return img_picto($this->statuts_short[$statut], 'statut1');
            if ($statut == 2)
                return img_picto($this->statuts_short[$statut], 'statut3');
            if ($statut == 3)
                return img_picto($this->statuts_short[$statut], 'statut6');
        }
        if ($mode == 4) {
            if ($statut == 0)
                return img_picto($this->statuts_short[$statut], 'statut0') . ' ' . $this->statuts[$statut];
            if ($statut == 1)
                return img_picto($this->statuts_short[$statut], 'statut1') . ' ' . $this->statuts[$statut];
            if ($statut == 2)
                return img_picto($this->statuts_short[$statut], 'statut3') . ' ' . $this->statuts[$statut];
            if ($statut == 3)
                return img_picto($this->statuts_short[$statut], 'statut6') . ' ' . $this->statuts[$statut];
        }
        if ($mode == 5) {
            if ($statut == 0)
                return $this->statuts_short[$statut] . ' ' . img_picto($this->statuts_short[$statut], 'statut0');
            if ($statut == 1)
                return $this->statuts_short[$statut] . ' ' . img_picto($this->statuts_short[$statut], 'statut1');
            if ($statut == 2)
                return $this->statuts_short[$statut] . ' ' . img_picto($this->statuts_short[$statut], 'statut3');
            if ($statut == 3)
                return $this->statuts_short[$statut] . ' ' . img_picto($this->statuts_short[$statut], 'statut6');
        }
    }

    /**
     *      \brief      Verifie si la ref n'est pas deja utilisee
     *      \param        soc                      objet societe
     */
    function verifyNumRef() {
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "synopsisdemandeinterv";
        $sql.= " WHERE ref = '" . $this->ref . "'";

        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);
            if ($num > 0 || $this->ref == "") {
                $this->ref = $this->getNextNumRef($this->soc);
            }
        }
    }

    /**
     *      \brief      Renvoie la reference de fiche intervention suivante non utilisee en fonction du module
     *                  de numerotation actif defini dans SYNOPSISDEMANDEINTERV_ADDON
     *      \param        soc                      objet societe
     *      \return     string              reference libre pour la fiche intervention
     */
    function getNextNumRef($soc) {
        global $db, $langs, $conf;
        $langs->load("interventions");
        $dir = DOL_DOCUMENT_ROOT . "/core/modules/synopsisdemandeinterv/";
        if ($conf->global->SYNOPSISDEMANDEINTERV_ADDON . "x" != "x") {
            $file = "mod_" . $conf->global->SYNOPSISDEMANDEINTERV_ADDON . ".php";

            // Chargement de la classe de numerotation
            $classname = "mod_" . $conf->global->SYNOPSISDEMANDEINTERV_ADDON;
            require_once($dir . $file);

            $obj = new $classname();

            $numref = "";
            $numref = $obj->getNumRef($soc, $this);

            if ($numref != "") {
                return $numref;
            } else {
                dol_print_error($db, "synopsisdemandeinterv::getNextNumRef " . $obj->error);
                return "";
            }
        } else {
            print $langs->trans("Error") . " " . $langs->trans("Error_SYNOPSISDEMANDEINTERV_ADDON_NotDefined");
            return "";
        }
    }

    /**
     *      \brief      Information sur l'objet fiche intervention
     *      \param      id      id de la fiche d'intervention
     */
    function info($id) {
        $sql = "SELECT f.rowid, ";
        $sql.= "f.datec as datec, f.date_valid as datev";
        $sql.= ", f.fk_user_author, f.fk_user_valid";
        $sql.= ", f.fk_user_target, f.fk_user_prisencharge";
        $sql.= ", f.fk_user_cloture ";
        $sql.= " FROM " . MAIN_DB_PREFIX . "synopsisdemandeinterv as f";
        $sql.= " WHERE f.rowid = " . $id;

        $result = $this->db->query($sql);

        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);

                $this->id = $obj->rowid;

                $this->date_creation = $this->db->jdate($obj->datec);
                $this->date_validation = $this->db->jdate($obj->datev);

                $cuser = new User($this->db);
                $cuser->fetch($obj->fk_user_author);
                $this->user_creation = $cuser;

                if ($obj->fk_user_valid) {
                    $vuser = new User($this->db);
                    $vuser->fetch($obj->fk_user_valid);
                    $this->user_validation = $vuser;
                }

                if ($obj->fk_user_target) {
                    $vuser = new User($this->db);
                    $vuser->fetch($obj->fk_user_target);
                    $this->user_target = $vuser;
                }
                if ($obj->fk_user_prisencharge) {
                    $vuser = new User($this->db);
                    $vuser->fetch($obj->fk_user_prisencharge);
                    $this->user_prisencharge = $vuser;
                }
                if ($obj->fk_user_cloture) {
                    $vuser = new User($this->db);
                    $vuser->fetch($obj->fk_user_cloture);
                    $this->user_cloture = $vuser;
                }
            }
            $this->db->free($result);
        } else {
            dol_print_error($this->db);
        }
    }

    /**
     *      \brief     Classe la fiche d'intervention dans un projet
     *      \param     project_id       Id du projet dans lequel classer la facture
     */
    function set_project($user, $project_id) {
        if ($user->rights->synopsisdemandeinterv->creer) {
            //verif que le projet et la societe concordent
            $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX . '_Syn_projet as p WHERE p.fk_soc =' . $this->socid . ' AND p.rowid=' . $project_id;
            $sqlres = $this->db->query($sql);
            if ($sqlres) {
                $numprojet = $this->db->num_rows($sqlres);
                if ($numprojet > 0) {
                    $this->projetidp = $project_id;
                    $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'synopsisdemandeinterv SET fk_projet = ' . $project_id;
                    $sql .= ' WHERE rowid = ' . $this->id . ' AND fk_statut = 0 ;';
                    $this->db->query($sql);
                }
            } else {

                dol_syslog("synopsisdemandeinterv::set_project Erreur SQL");
            }
        }
    }

    /**
     *    \brief      Efface fiche intervention
     *    \param      user        Objet du user qui efface
     */
    function delete($user) {
        global $conf, $user, $langs;
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        $this->db->begin();

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "synopsisdemandeintervdet WHERE fk_synopsisdemandeinterv = " . $this->id;
        dol_syslog("synopsisdemandeinterv::delete sql=" . $sql);
        if ($this->db->query($sql)) {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "synopsisdemandeinterv WHERE rowid = " . $this->id;
            dol_syslog("synopsisdemandeinterv::delete sql=" . $sql);
            if ($this->db->query($sql)) {

                // Remove directory with files
                $synopsisdemandeintervref = sanitize_string($this->ref);
                if ($conf->synopsisdemandeinterv->dir_output) {
                    $dir = $conf->synopsisdemandeinterv->dir_output . "/" . $synopsisdemandeintervref;
                    $file = $conf->synopsisdemandeinterv->dir_output . "/" . $synopsisdemandeintervref . "/" . $synopsisdemandeintervref . ".pdf";
                    if (file_exists($file)) {
                        synopsisdemandeinterv_delete_preview($this->db, $this->id, $this->ref);

                        if (!dol_delete_file($file)) {
                            $this->error = $langs->trans("ErrorCanNotDeleteFile", $file);
                            return 0;
                        }
                    }
                    if (file_exists($dir)) {
                        if (!dol_delete_dir($dir)) {
                            $this->error = $langs->trans("ErrorCanNotDeleteDir", $dir);
                            return 0;
                        }
                    }
                }

                $this->db->commit();
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('SYNOPSISDEMANDEINTERV_DELETE', $this, $user, $langs, $conf);
                if ($result < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers
                return 1;
            } else {
                $this->error = $this->db->lasterror();
                $this->db->rollback();
                return -2;
            }
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *      \brief      Definit une date de livraison du bon d'intervention
     *      \param      user                Objet utilisateur qui modifie
     *      \param      date_creation   date de livraison
     *      \return     int                 <0 si ko, >0 si ok
     */
    function set_date_delivery($user, $date_delivery, $no_trigger = false) {
        global $langs, $conf;

        if (1 || (isset($user->rights) && isset($user->rights->synopsisdemandeinterv) && $user->rights->synopsisdemandeinterv->creer/* && $this->statut == 0*/)) {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "synopsisdemandeinterv ";
            $sql.= " SET datei = " . ($date_delivery > 0 ? "'" . $this->db->idate($date_delivery) . "'" : "null");
            $sql.= " WHERE rowid = " . $this->id . " AND fk_statut = 0";

            if ($this->db->query($sql)) {
                $this->date_delivery = $date_delivery;


                $sql = "UPDATE " . MAIN_DB_PREFIX . "synopsisdemandeintervdet ";
                $sql.= " SET date = " . ($date_delivery > 0 ? "'" . $this->db->idate($date_delivery) . "'" : "null");
                $sql.= " WHERE fk_synopsisdemandeinterv = " . $this->id . "";
                $this->db->query($sql);
                
                
                if(!$no_trigger){
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('SYNOPSISDEMANDEINTERV_SETDELIVERY', $this, $user, $langs, $conf);
                if ($result < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                $this->synchroAction(false);
                }
                $this->date = $date_delivery;
                
                // Fin appel triggers
                return 1;
            } else {
                $this->error = $this->db->error();
                dol_syslog("synopsisdemandeinterv::set_date_delivery Erreur SQL");
                return -1;
            }
        }
        else
            dol_syslog("Modification date DI interdite : ".$this->id,3);
    }
    
    
    public function setExtra($idExtraKey, $val){
        
                $requete = "INSERT INTO " . MAIN_DB_PREFIX . "synopsisfichinter_extra_value
                                         (interv_refid,extra_key_refid,extra_value,typeI)
                                  VALUES (" .$this->id . "," . $idExtraKey . ",'" . addslashes($val) . "','DI')";
                $sql = $this->db->query($requete);
    }

    /**
     *      \brief      Definit le label de l'intervention
     *      \param      user                Objet utilisateur qui modifie
     *      \param      description     description
     *      \return     int                 <0 si ko, >0 si ok
     */
    function set_description($user, $description) {
        global $langs, $conf;
        if ($user->rights->synopsisdemandeinterv->creer) {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "synopsisdemandeinterv ";
            $sql.= " SET description = '" . addslashes($description) . "'";
            $sql.= " WHERE rowid = " . $this->id . " AND fk_statut = 0";

            if ($this->db->query($sql)) {
                $this->description = $description;
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('SYNOPSISDEMANDEINTERV_SETDESCRIPTION', $this, $user, $langs, $conf);
                if ($result < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers
                return 1;
            } else {
                $this->error = $this->db->error();
                dol_syslog("synopsisdemandeinterv::set_description Erreur SQL");
                return -1;
            }
        }
    }

    /**
     *      \brief         Ajout d'une ligne d'intervention, en base
     *         \param        synopsisdemandeintervid            Id de la fiche d'intervention
     *         \param        desc                  Description de la ligne
     *    \param      date_intervention   Date de l'intervention
     *    \param      duration            Duree de l'intervention
     *        \return        int                 >0 si ok, <0 si ko
     */
    function addline($synopsisdemandeintervid, $desc, $date_intervention, $duration, $typeinterv, $qte = 1, $pu_ht = 0, $isForfait = 0, $fk_commandedet = false, $fk_contratdet = false) {
        dol_syslog("synopsisdemandeinterv::Addline $synopsisdemandeintervid, $desc, $date_intervention, $duration");

        if ($this->statut == 0) {
            $this->db->begin();

            // Insertion ligne
            $ligne = new SynopsisdemandeintervLigne($this->db);

            $ligne->fk_synopsisdemandeinterv = $synopsisdemandeintervid;
            $ligne->desc = $desc;
            $ligne->datei = $date_intervention;
            $ligne->duration = $duration;
            $ligne->fk_typeinterv = $typeinterv;
            $ligne->fk_commandedet = $fk_commandedet;
            $ligne->fk_contratdet = $fk_contratdet;

            $ligne->qte = $qte;
            $ligne->pu_ht = $pu_ht;
            $ligne->isForfait = $isForfait;
            if ($isForfait == 1) {
                $ligne->total_ht = floatval($ligne->qte) * floatval($pu_ht);
            } else {
                $ligne->total_ht = floatval(($ligne->qte . "x" == "x" ? 1 : $ligne->qte)) * floatval($ligne->duration) * floatval($pu_ht) / 3600;
            }


            $result = $ligne->insert();
            if ($result > 0) {
                $this->db->commit();
                return 1;
            } else {
                $this->error = $this->db->error();
                dol_syslog("Error sql=, error=" . $this->error);
                $this->db->rollback();
                return -1;
            }
        }
    }

    /**
     *        \brief        Initialise la fiche intervention avec valeurs fictives aleatoire
     *                    Sert a generer une fiche intervention pour l'apercu des modeles ou demo
     */
    function initAsSpecimen() {
        global $user, $langs;

        // Charge tableau des id de societe socids
        $socids = array();
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "societe WHERE client=1 LIMIT 10";
        $resql = $this->db->query($sql);
        if ($resql) {
            $num_socs = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num_socs) {
                $i++;

                $row = $this->db->fetch_row($resql);
                $socids[$i] = $row[0];
            }
        }

        // Charge tableau des produits prodids
        $prodids = array();
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "product  WHERE tobuy=1";
        $resql = $this->db->query($sql);
        if ($resql) {
            $num_prods = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num_prods) {
                $i++;
                $row = $this->db->fetch_row($resql);
                $prodids[$i] = $row[0];
            }
        }

        // Initialise parametres
        $this->id = 0;
        $this->ref = 'SPECIMEN';
        $this->specimen = 1;
        $socid = rand(1, $num_socs);
        $this->socid = $socids[$socid];
        $this->date = time();
        $this->date_lim_reglement = $this->date + 3600 * 24 * 30;
        $this->cond_reglement_code = 'RECEP';
        $this->mode_reglement_code = 'CHQ';
        $this->note_public = 'SPECIMEN';
        $nbp = 5;
        $xnbp = 0;
        while ($xnbp < $nbp) {
            $ligne = new SynopsisdemandeintervLigne($this->db);
            $ligne->desc = $langs->trans("Description") . " " . $xnbp;
            $ligne->qty = 1;
            $ligne->subprice = 100;
            $ligne->price = 100;
            $ligne->tva_tx = 19.6;
            $prodid = rand(1, $num_prods);
            $ligne->produit_id = $prodids[$prodid];
            $this->lignes[$xnbp] = $ligne;
            $xnbp++;
        }

        $this->amount_ht = $xnbp * 100;
        $this->total_ht = $xnbp * 100;
        $this->total_tva = $xnbp * 19.6;
        $this->total_ttc = $xnbp * 119.6;
    }

    /**
     *        \brief        Initialise la fiche intervention avec valeurs fictives aleatoire
     *                    Sert a generer une fiche intervention pour l'aperu des modeles ou demo
     *         \return        int        <0 OK,    >0 KO
     */
    function fetch_lines() {
        $sql = 'SELECT rowid';
        $sql.= '  FROM ' . MAIN_DB_PREFIX . 'synopsisdemandeintervdet';
        $sql.= ' WHERE fk_synopsisdemandeinterv = ' . $this->id;

        dol_syslog("synopsisdemandeinterv::fetch_lines sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $objp = $this->db->fetch_object($resql);

                $synopsisdemandeintervligne = new SynopsisdemandeintervLigne($this->db);
                $synopsisdemandeintervligne->id = $objp->rowid;
                $synopsisdemandeintervligne->fetch($objp->rowid);
                //               var_dump($synopsisdemandeintervligne);
                $this->lignes[$i] = $synopsisdemandeintervligne;

                $i++;
            }
            $this->db->free($resql);
            return 1;
        } else {
            $this->error = $this->db->error();
            return -1;
        }
    }

    function getNomUrl($withpicto = 0, $option = 0) {
        global $langs;

        $result = '';
        $urlOption = '';

        $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/synopsisdemandeinterv/card.php?id=' . $this->id . '">';
        if ($option == 6)
            $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/synopsisdemandeinterv/card.php?id=' . $this->id . '">';
        $lienfin = '</a>';

        $picto = 'synopsisdemandeinterv@synopsisdemandeinterv';
        $label = $langs->trans("DI") . ': ' . $this->ref;

        if ($withpicto)
            $result.=($lien . img_object($label, $picto) . $lienfin);
        if ($withpicto && $option == 6)
            $result.=($lien . img_object($label, $picto, false, false, false, true) . $lienfin);
        if ($withpicto && $withpicto != 2)
            $result.=' ';
        $result.=$lien . $this->ref . $lienfin;
        return $result;
    }

}

/**
  \class      synopsisdemandeintervLigne
  \brief      Classe permettant la gestion des lignes d'intervention
 */
class synopsisdemandeintervLigne {

    public $db;
    public $error;
    // From ".MAIN_DB_PREFIX."synopsisdemandeintervdet
    public $rowid;
    public $fk_synopsisdemandeinterv;
    public $desc;              // Description ligne
    public $datei;           // Date intervention
    public $duration;        // Duree de l'intervention
    public $rang = 0;
    public $typeInterv;
    public $fk_typeinterv;
    public $dateiunformated;

    /**
     *      \brief     Constructeur d'objets ligne d'intervention
     *      \param     DB      handler d'acces base de donnee
     */
    function synopsisdemandeintervLigne($DB) {
        $this->db = $DB;
    }

    /**
     *      \brief     Recupere l'objet ligne d'intervention
     *      \param     rowid           id de la ligne
     */
    function fetch($rowid) {
        $sql = '     SELECT ft.rowid,
                            ft.fk_typeinterv,
                            t.label as typeInterv,
                            t.isDeplacement,
                            ft.fk_synopsisdemandeinterv,
                            ft.description,
                            ft.duree,
                            ft.rang,
                            ft.qte,
                            ft.fk_commandedet as comLigneId,
                            ft.total_ht,
                            ft.total_ttc,
                            ft.total_tva,
                            ft.isForfait,
                            ft.fk_commandedet,
                            ft.fk_contratdet,
                            ft.pu_ht,
                            ft.tx_tva,
                            ft.date as dateiunformated,
                            c.fk_product as fk_product1,';
        $sql.= '            ft.date as datei ';
        $sql.= '       FROM ' . MAIN_DB_PREFIX . 'synopsisdemandeintervdet as ft';
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv as t ON ft.fk_typeinterv=t.id";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commandedet as c ON c.rowid = ft.fk_commandedet";
        $sql.= '      WHERE ft.rowid = ' . $rowid;
        dol_syslog("synopsisdemandeintervLigne::fetch sql=" . $sql);
        $result = $this->db->query($sql);
        if ($result) {
            $objp = $this->db->fetch_object($result);
            $this->rowid = $objp->rowid;
            $this->fk_synopsisdemandeinterv = $objp->fk_synopsisdemandeinterv;
            $this->datei = $objp->datei;
            $this->fk_typeinterv = $objp->fk_typeinterv;
            $this->isDeplacement = $objp->isDeplacement;
            $this->typeInterv = $objp->typeInterv;
            $this->dateiunformated = $objp->dateiunformated;
            $this->desc = $objp->description;
            $this->duration = $objp->duree;
            $this->rang = $objp->rang;
            $this->qte = $objp->qte;
            $this->total_ht = $objp->total_ht;
            $this->total_ttc = $objp->total_ttc;
            $this->total_tva = $objp->total_tva;
            $this->isForfait = $objp->isForfait;
            $this->fk_commandedet = $objp->fk_commandedet;
            $this->fk_contratdet = $objp->fk_contratdet;
            $this->pu_ht = $objp->pu_ht;
            $this->tx_tva = $objp->tx_tva;
            $this->comLigneId = $objp->comLigneId;

            if ($objp->fk_product1 > 0)
                $this->fk_prod = $objp->fk_product1;


            $this->db->free($result);
            return 1;
        }
        else {
            $this->error = $this->db->error() . ' sql=' . $sql;
            dol_print_error($this->db, $this->error);
            return -1;
        }
    }

    /**
     *      \brief         Insere l'objet ligne d'intervention en base
     *        \return        int        <0 si ko, >0 si ok
     */
    function insert() {
        global $user, $langs, $conf;
        dol_syslog("synopsisdemandeintervLigne::insert rang=" . $this->rang);
        $this->db->begin();

        $rangToUse = $this->rang;
        if (!$rangToUse > 0) {
            // Recupere rang max de la ligne d'intervention dans $rangmax
            $sql = 'SELECT max(rowid) as max FROM ' . MAIN_DB_PREFIX . 'synopsisdemandeintervdet';
            $sql.= ' WHERE fk_synopsisdemandeinterv =' . $this->fk_synopsisdemandeinterv;
            $resql = $this->db->query($sql);
            if ($resql) {
                $rangToUse = 0;
                if ($this->db->num_rows($resql)) {
                    $obj = $this->db->fetch_object($resql);
                    $rangToUse = $obj->max + 1;
                }
            } else {
                dol_print_error($this->db);
                $this->db->rollback();
                return -1;
            }
        }

//            $ligne->qte   = $qte;
//            $ligne->pu_ht = $pu_ht;
//            $ligne->isForfait = $isForfait;
//            if ($isForfait==1)
//            {
//                $ligne->total_ht = floatval($ligne->qte) * floatval($pu_ht);
//            } else {
//                $ligne->total_ht = floatval($ligne->duration) * floatval($pu_ht)/3600;
//            }
//Compute total tva / total_TTC
        $total_ttc = 1.2 * $this->total_ht;
        $total_tva = 0.2 * $this->total_ht;

        // Insertion dans base de la ligne
        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'synopsisdemandeintervdet';
        $sql.= ' (fk_synopsisdemandeinterv, description, date, duree, rang,fk_typeinterv, isForfait ';
        if ($this->qte > 0)
            $sql .= ',qte ';
        if ($this->pu_ht != 0 && $this->pu_ht != '')
            $sql .= ',pu_ht, total_ht, total_tva, total_ttc ';
        if ($this->fk_commandedet > 0)
            $sql .= ',fk_commandedet ';
        if ($this->fk_contratdet > 0)
            $sql .= ',fk_contratdet ';
        $sql.= ')';
        $sql.= " VALUES (" . $this->fk_synopsisdemandeinterv . ",";
        $sql.= " '" . addslashes($this->desc) . "',";
        $sql.= " " . $this->db->idate($this->datei) . ",";
        $sql.= " " . $this->duration . ",";
        $sql.= ' ' . $rangToUse . ",";
        $sql.= ' ' . ($this->fk_typeinterv > 0 ? $this->fk_typeinterv : 'NULL') . ",";
        $sql.= ' ' . ($this->isForfait > 0 ? 1 : 0);
        if ($this->qte > 0)
            $sql .= ' ,' . preg_replace('/,/', '.', $this->qte);
        if ($this->pu_ht != 0 && $this->pu_ht != '')
            $sql .= "," . preg_replace('/,/', '.', $this->pu_ht) . "," . preg_replace('/,/', '.', $this->total_ht) . "," . preg_replace('/,/', '.', $total_tva) . "," . preg_replace('/,/', '.', $total_ttc);
        if ($this->fk_commandedet > 0)
            $sql .= ', ' . $this->fk_commandedet;
        if ($this->fk_contratdet > 0)
            $sql .= ', ' . $this->fk_contratdet;

        $sql.= ')';
        dol_syslog("synopsisdemandeintervLigne::insert sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            $result = $this->update_total();
            if ($result > 0) {
                $this->rang = $rangToUse;
                $this->db->commit();
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('SYNOPSISDEMANDEINTERV_INSERTLINE', $this, $user, $langs, $conf);
                if ($result < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers
                return $result;
            } else {
                $this->db->rollback();
                return -1;
            }
        } else {
            $this->error = $this->db->error() . " sql=" . $sql;
            dol_syslog("synopsisdemandeintervLigne::insert Error " . $this->error);
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *      \brief         Mise a jour de l'objet ligne d'intervention en base
     *        \return        int        <0 si ko, >0 si ok
     */
    function update() {
        $this->db->begin();
        global $user, $langs, $conf;

        if ($this->isForfait == 1 && ($this->pu_ht . "x" != "x")) {
            $this->total_ht = floatval($this->qte) * floatval($this->pu_ht);
        } else if ($this->pu_ht . "x" != "x") {
            $this->total_ht = floatval(($this->qte . "x" == "x" ? 1 : $this->qte)) * floatval($this->duration) * floatval($this->pu_ht) / 3600;
        }

//print "toto".$this->total_ht;
        $total_ttc = 1.2 * $this->total_ht;
        $total_tva = 0.2 * $this->total_ht;


        // Mise a jour ligne en base
        $sql = "UPDATE " . MAIN_DB_PREFIX . "synopsisdemandeintervdet SET";
        $sql.= " description='" . addslashes($this->desc) . "'";
        $sql.= ",date='" . $this->db->idate($this->datei) . "'";
        $sql.= ",duree=" . $this->duration;
        $sql.= ",fk_typeinterv=" . ($this->fk_typeinterv > 0 ? $this->fk_typeinterv : 'NULL');
        if ($this->qte . "x" != "x")
            $sql .= ",qte = " . $this->qte;
        if ($this->pu_ht . "x" != "x")
            $sql .= ",pu_ht = " . preg_replace('/,/', '.', $this->pu_ht);
        if ($this->pu_ht . "x" != "x")
            $sql .= ",total_ht = " . preg_replace('/,/', '.', $this->total_ht);
        if ($this->pu_ht . "x" != "x")
            $sql .= ",total_tva = " . preg_replace('/,/', '.', $total_tva);
        if ($this->pu_ht . "x" != "x")
            $sql .= ",total_ttc = " . preg_replace('/,/', '.', $total_ttc);
        if ($this->isForfait . "x" != "x")
            $sql .= ",isForfait = " . $this->isForfait;
        if ($this->comLigneId > 0)
            $sql .= ",fk_commandedet =  " . $this->comLigneId;
        else
            $sql .= ",fk_commandedet =  NULL";

        if ($this->fk_contratdet > 0)
            $sql .= ",fk_contratdet =  " . $this->fk_contratdet;


        $sql.= ",rang='" . $this->rang . "'";
        $sql.= " WHERE rowid = " . $this->rowid;

        dol_syslog("synopsisdemandeintervLigne::update sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            $result = $this->update_total();
            if ($result > 0) {
                $this->db->commit();
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('SYNOPSISDEMANDEINTERV_UPDATELINE', $this, $user, $langs, $conf);
                if ($result < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers
                return $result;
            } else {
                $this->db->rollback();
                return -1;
            }
        } else {
            $this->error = $this->db->error();
            dol_syslog("synopsisdemandeintervLigne::update Error " . $this->error);
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *      \brief                   Mise a jour duree total dans table ".MAIN_DB_PREFIX."synopsisdemandeinterv
     *      \return        int        <0 si ko, >0 si ok
     */
    function update_total() {
        global $user, $langs, $conf;
//        $sql = "SELECT SUM(duree) as total_duration";
//        $sql.= " FROM ".MAIN_DB_PREFIX."synopsisdemandeintervdet";
//        $sql.= " WHERE fk_synopsisdemandeinterv=".$this->fk_synopsisdemandeinterv;
        $result = 1;
        $requete = "SELECT sum(total_ht) as sht, sum(total_tva) as stva, sum(total_ttc) as sttc, sum(duree) as sdur FROM " . MAIN_DB_PREFIX . "synopsisdemandeintervdet WHERE fk_synopsisdemandeinterv = " . $this->fk_synopsisdemandeinterv;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $requete = "UPDATE " . MAIN_DB_PREFIX . "synopsisdemandeinterv SET total_ht = " . (($res->sht > 0) ? $res->sht : 0) . ", total_tva = " . (($res->stva > 0) ? $res->stva : 0) . " , total_ttc = " . (($res->sttc > 0) ? $res->sttc : 0) . ", duree = " . (($res->sdur > 0) ? $res->sdur : 0) . " WHERE rowid = " . $this->fk_synopsisdemandeinterv;
        $sql = $this->db->query($requete);
        if ($sql) {
            $result = 1;
        }
        $this->db->commit();

        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface = new Interfaces($this->db);
        $result = $interface->run_triggers('SYNOPSISDEMANDEINTERV_UPDATETOTAL', $this, $user, $langs, $conf);
        if ($result < 0) {
            $error++;
            $this->errors = $interface->errors;
        } else {
            $result = 1;
        }
        // Fin appel triggers
        return($result);
//        dol_syslog("synopsisdemandeintervLigne::update_total sql=".$sql);
//        $resql=$this->db->query($sql);
//        if ($resql)
//        {
//            $obj=$this->db->fetch_object($resql);
//            $total_duration=0;
//            if ($obj) $total_duration = $obj->total_duration;
//
//            $sql = "UPDATE ".MAIN_DB_PREFIX."synopsisdemandeinterv";
//            $sql.= " SET duree = ".$total_duration;
//            $sql.= " WHERE rowid = ".$this->fk_synopsisdemandeinterv;
//
//            dol_syslog("synopsisdemandeintervLigne::update_total sql=".$sql);
//            $resql=$this->db->query($sql);
//            if ($resql)
//            {
//                // Appel des triggers
//                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
//                $interface=new Interfaces($this->db);
//                $result=$interface->run_triggers('SYNOPSISDEMANDEINTERV_UPDATETOTAL',$this,$user,$langs,$conf);
//                if ($result < 0) { $error++; $this->errors=$interface->errors; }
//                // Fin appel triggers
//                $this->db->commit();
//                return 1;
//            }
//            else
//            {
//                $this->error=$this->db->error();
//                dol_syslog("synopsisdemandeintervLigne::update_total Error ".$this->error);
//                $this->db->rollback();
//                return -2;
//            }
//        }
//        else
//        {
//            $this->error=$this->db->error();
//            dol_syslog("synopsisdemandeintervLigne::update Error ".$this->error);
//            $this->db->rollback();
//            return -1;
//        }
    }

    /**
     *      \brief      Supprime une ligne d'intervention
     *      \return     int         >0 si ok, <0 si ko
     */
    function delete_line() {
        global $user, $langs, $conf;
        if ($this->statut == 0) {
            dol_syslog("synopsisdemandeintervLigne::delete_line lineid=" . $this->rowid);
            $this->db->begin();

            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "synopsisdemandeintervdet WHERE rowid = " . $this->rowid;
            $resql = $this->db->query($sql);
            dol_syslog("synopsisdemandeintervLigne::delete_line sql=" . $sql);

            if ($resql) {
                $result = $this->update_total();
                if ($result > 0) {
                    $this->db->commit();
                    //APpel Trigger
                    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                    $interface = new Interfaces($this->db);
                    $result = $interface->run_triggers('SYNOPSISDEMANDEINTERV_DELETELINE', $this, $user, $langs, $conf);
                    if ($result < 0) {
                        $error++;
                        $this->errors = $interface->errors;
                    }
                    // Fin appel triggers
                    $this->db->commit();
                    return $result;
                } else {
                    $this->db->rollback();
                    return -1;
                }
            } else {
                $this->error = $this->db->error() . " sql=" . $sql;
                dol_syslog("synopsisdemandeintervLigne::delete_line Error " . $this->error);
                $this->db->rollback();
                return -1;
            }
        } else {
            return -2;
        }
    }

}

?>