<?php

/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * BIMP-ERP by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.synopsis-erp.com
 *
 */
/*
 */

/**        \file       htdocs/synopsisfichinter/class/fichinter.class.php
  \ingroup    ficheinter
  \brief      Fichier de la classe des gestion des fiches interventions
  \version    $Id: fichinter.class.php,v 1.58 2008/07/12 12:45:30 eldy Exp $
 */
require_once(DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT . "/fichinter/class/fichinter.class.php");

/**
 *     \class      Ficheinter
 *    \brief      Classe des gestion des fiches interventions
 */
class Synopsisfichinter extends Fichinter {

    public $db;
    public $element = 'synopsisfichinter';
    public $table_element = 'synopsis_fichinter';
    public $fk_element = 'fk_fichinter';
    public $table_element_line = 'synopsis_fichinterdet';
    public $id;
    public $socid;        // Id client
    public $client;        // Objet societe client (a charger par fetch_client)
    public $author;
    public $fk_user_author;
    public $ref;
    public $date;
    public $fk_di;
    public $date_delivery;
    public $duree;
    public $description;
    public $note_private;
    public $fk_commande;
    public $fk_contrat;
    public $note_public;
    public $projet_id;
    public $modelpdf;
    public $natureInter = 0;
    public $fk_fi = "";
    public $lignes = array();

    /**
     *    \brief      Constructeur de la classe
     *    \param      DB            Handler acces base de donnees
     *    \param      socid            Id societe
     */
    function Synopsisfichinter($DB, $socid = "") {
        global $langs;

        $this->db = $DB;
        $this->socid = $socid;
        $this->products = array();
        $this->projet_id = 0;

        // Statut 0=brouillon, 1=valide
        $this->statuts[0] = $langs->trans("Draft");
        $this->statuts[1] = $langs->trans("Validated");
        $this->statuts_short[0] = $langs->trans("Draft");
        $this->statuts_short[1] = $langs->trans("Validated");
    }

    /*
     *        \brief      Cree une fiche intervention en base
     *        \return        int        <0 if KO, >0 if OK
     */

    function create($user, $notrigger = 0) {
        global $user, $langs, $conf;
        dol_syslog("Fichinter.class::create ref=" . $this->ref);

        if (!is_numeric($this->duree)) {
            $this->duree = 0;
        }
        if ($this->socid <= 0) {
            $this->error = 'ErrorBadParameterForFunc no CLIENT';
            dol_syslog("Fichinter::create " . $this->error, LOG_ERR);
            return -1;
        }

        $this->db->begin();

        // on verifie si la ref n'est pas utilisee
        $soc = new Societe($this->db);
        $result = $soc->fetch($this->socid);
        $this->verifyNumRef($soc);
        
        if($this->author == "")
            $this->author = $this->fk_user_author;
        
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "fichinter (fk_soc, datec, ref, fk_user_author, description, model_pdf";
        if ($this->projet_id)
            $sql.= ", fk_projet";
//        if ($this->fk_user_prisencharge > 0)
//            $sql.= ", fk_user_prisencharge";
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
        if ($this->fk_contrat > 0)
            $sql .= ", " . $this->fk_contrat;
        if ($this->date > 0)
            $sql.= ", '" . $this->db->idate($this->date) . "'";
        $sql.= ")";
        $sqlok = 0;
        dol_syslog("Fichinter::create sql=" . $sql);
        $result = $this->db->query($sql);
        if ($result) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "fichinter");

            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "synopsisfichinter (rowid";
            if ($this->fk_commande > 0)
                $sql.= ", fk_commande";
            $sql.= ") ";
            $sql.= " VALUES (" . $this->id;
            if ($this->fk_commande > 0)
                $sql .= ", " . $this->fk_commande;
            $sql.= ")";
            $result = $this->db->query($sql);

            $this->db->commit();
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('FICHEINTER_CREATE', $this, $user, $langs, $conf);
            if ($result < 0) {
                $error++;
                $this->errors = $interface->errors;
            }
            // Fin appel triggers
            if ($this->fk_di . "x" != "x") {
//                    $this->db->begin();
//                    $requete = "INSERT INTO Babel_li_interv (fi_refid,di_refid) VALUES (".$this->id.",".$this->fk_di.")";
//                    $result=$this->db->query($requete);
//                    $this->db->commit();
                $this->addDI($this->fk_di);
            }

            return $this->id;
        } else {
            $this->error = $this->db->error();
            dol_syslog("Fichinter::create " . $this->error, LOG_ERR);
            $this->db->rollback();
            return -1;
        }
    }

    /*
     *    \brief        Met a jour une intervention
     *    \return        int        <0 si ko, >0 si ok
     */

    function update($user, $noTrigger = 0) {
        global $user, $langs, $conf;
        if (!is_numeric($this->duree)) {
            $this->duree = 0;
        }
        if (!strlen($this->projet_id)) {
            $this->projet_id = 0;
        }

        /*
         *  Insertion dans la base
         */
        $sql = "UPDATE " . MAIN_DB_PREFIX . "fichinter SET ";
        $sql .= " datei = '" . $this->db->idate($this->date) . "'";
        $sql .= ", description  = '" . addslashes($this->description) . "'";
        $sql .= ", duree = " . $this->duree;
        $sql .= ", fk_projet = " . $this->projet_id;
        $sql .= ", fk_statut = " . $this->statut;
        $sql .= ", note_public = '" . $this->note_public."'";
        $sql .= ", note_private = '" . $this->note_private."'";
        $sql .= ", fk_contrat = '" . $this->fk_contrat . "'";
        $sql.= ",model_pdf='" . $this->modelpdf . "'";
        $sql .= " WHERE rowid = " . $this->id;


        $sql2 = "UPDATE " . MAIN_DB_PREFIX . "synopsisfichinter SET ";
        $sql2 .= " natureInter = '" . $this->natureInter . "'";
        $sql2 .= ", fk_commande = '" . $this->fk_commande . "'";
        $sql2 .= " WHERE rowid = " . $this->id;

        dol_syslog("Fichinter::update sql=" . $sql);
        if (!$this->db->query($sql) || !$this->db->query($sql2)) {
            $this->error = $this->db->error();
            dol_syslog("Fichinter::update error " . $this->error, LOG_ERR);
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('FICHEINTER_UPDATE', $this, $user, $langs, $conf);
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
    function fetch($rowid, $ref = '') {
        $result = $this->db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."synopsisfichinter WHERE rowid = ".$rowid);
        if($this->db->num_rows($result) == 0)
            $this->db->query("INSERT INTO ".MAIN_DB_PREFIX."synopsisfichinter (rowid) VALUES (".$rowid.")");
        
        $sql = "SELECT ref, fk_user_valid, description, fk_soc, fk_user_author, fk_statut, fk_contrat, fk_commande, total_ht, total_tva, total_ttc,";
        $sql.= " datei as di, duree, fk_projet, note_public, note_private, model_pdf, natureInter";
        $sql.= " FROM " . MAIN_DB_PREFIX . "synopsis_fichinter";
        $sql.= " WHERE rowid=" . $rowid;

//        dol_syslog("Fichinter::fetch sql=".$sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->fk_user_valid = $obj->fk_user_valid;
                $this->id = $rowid;
                $this->ref = $obj->ref;
                $this->description = $obj->description;
                $this->socid = $obj->fk_soc;
                $this->fk_soc = $obj->fk_soc;
                $this->fk_contrat = $obj->fk_contrat;
                $this->fk_user_author = $obj->fk_user_author;
                $this->fk_commande = $obj->fk_commande;
                $tmpSoc = new Societe($this->db);
                $tmpSoc->fetch($obj->fk_soc);
                $this->societe = $tmpSoc;
                $this->statut = $obj->fk_statut;
                $this->date = $this->db->jdate($obj->di);
                $this->di = $obj->di;
                $this->duree = $obj->duree;
                $this->projetidp = $obj->fk_projet;
                $this->note_public = $obj->note_public;
                $this->note_private = $obj->note_private;
                $this->modelpdf = $obj->model_pdf;
                $this->total_ht = $obj->total_ht;
                $this->total_tva = $obj->total_tva;
                $this->total_ttc = $obj->total_ttc;
                $this->natureInter = $obj->natureInter;

                $tabDi = $this->getDI();
                $this->fk_ref = ((isset($tabDi[0])) ? $tabDi[0] : 0);

                if ($this->statut == 0)
                    $this->brouillon = 1;

                $this->db->free($resql);
                return 1;
            }
        }
        else {
            $this->error = $this->db->error();
            dol_syslog("Fichinter::update error " . $this->error, LOG_ERR);
            return -1;
        }
    }

//    private function sendMail($subject, $to, $from, $msg, $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array(), $addr_cc = '', $addr_bcc = '', $deliveryreceipt = 0, $msgishtml = 1, $errors_to = '') {
//        global $mysoc;
//        global $langs;
//        require_once(DOL_DOCUMENT_ROOT . '/lib/CMailFile.class.php');
//        $mail = new CMailFile($subject, $to, $from, $msg,
//                        $filename_list, $mimetype_list, $mimefilename_list,
//                        $addr_cc, $addr_bcc, $deliveryreceipt, $msgishtml, $errors_to);
//        $res = $mail->sendfile();
//        if ($res) {
//            return (1);
//        } else {
//            return -1;
//        }
//    }

    function devalid($user, $outputdir) {
        $this->setDraft($user);
//        global $langs, $conf;
//
//        $this->db->begin();
//
//        $sql = "UPDATE " . MAIN_DB_PREFIX . "fichinter";
//        $sql.= " SET fk_statut = 0, date_valid=now(), fk_user_valid=" . $user->id;
//        $sql.= " WHERE rowid = " . $this->id . " AND fk_statut = 1";
//
//        dol_syslog("Fichinter::devalid sql=" . $sql);
//        $resql = $this->db->query($sql);
//        if ($resql) {
//            $this->db->commit();
//            return 1;
//        } else {
//            $this->db->rollback();
//            $this->error = join(',', $this->errors);
//            dol_syslog("Fichinter::update " . $this->error, LOG_ERR);
//            return -1;
//        }
    }

    /**
     *        \brief        Valide une fiche intervention
     *        \param        user        User qui valide
     *        \return        int            <0 si ko, >0 si ok
     */
    function valid($user, $outputdir) {
        $this->setValid($user);
        
        
//        if($this->total_ttc > 0)
//        mailSyn2("FI Validé", "m.gallet@bimp.fr", null, "Bonjour, la FI ".str_replace("card.php", "document.php", $this->getNomUrl(1))." a été validé pour facturation (".$this->total_ttc." €)");
//        global $langs, $conf;
//        $this->db->begin();
//
//        $sql = "UPDATE " . MAIN_DB_PREFIX . "fichinter";
//        $sql.= " SET fk_statut = 1, date_valid=now(), fk_user_valid=" . $user->id;
//        $sql.= " WHERE rowid = " . $this->id . " AND fk_statut = 0";
//
//        dol_syslog("Fichinter::valid sql=" . $sql);
//        $resql = $this->db->query($sql);
//        if ($resql) {
//            // Appel des triggers
//            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
//
//            $interface = new Interfaces($this->db);
//            $result = $interface->run_triggers('FICHEINTER_VALIDATE', $this, $user, $langs, $conf);
//            if ($result < 0) {
//                $error++;
//                $this->errors = $interface->errors;
//            }
//            // Fin appel triggers
//
//            if ($resql) {
//                $this->db->commit();
//                return 1;
//            } else {
//                $this->db->rollback();
//                $this->error = join(',', $this->errors);
//                dol_syslog("Fichinter::update " . $this->error, LOG_ERR);
//                return -1;
//            }
//        } else {
//            $this->db->rollback();
//            $this->error = $this->db->lasterror();
//            dol_syslog("Fichinter::update " . $this->error, LOG_ERR);
//            return -1;
//        }
    }

    /**
     *    \brief      Retourne le libelle du statut de l'intervantion
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
                return img_picto($this->statuts_short[$statut], 'statut6') . ' ' . $this->statuts_short[$statut];
        }
        if ($mode == 3) {
            if ($statut == 0)
                return img_picto($this->statuts_short[$statut], 'statut0');
            if ($statut == 1)
                return img_picto($this->statuts_short[$statut], 'statut6');
        }
        if ($mode == 4) {
            if ($statut == 0)
                return img_picto($this->statuts_short[$statut], 'statut0') . ' ' . $this->statuts[$statut];
            if ($statut == 1)
                return img_picto($this->statuts_short[$statut], 'statut6') . ' ' . $this->statuts[$statut];
        }
        if ($mode == 5) {
            if ($statut == 0)
                return $this->statuts_short[$statut] . ' ' . img_picto($this->statuts_short[$statut], 'statut0');
            if ($statut == 1)
                return $this->statuts_short[$statut] . ' ' . img_picto($this->statuts_short[$statut], 'statut6');
        }
    }

    /**
     *      \brief      Verifie si la ref n'est pas deja utilisee
     *      \param        soc                      objet societe
     */
      function verifyNumRef($soc)
      {
      $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."synopsis_fichinter";
      $sql.= " WHERE ref = '".$this->ref."'";

      $result = $this->db->query($sql);
      if ($result)
      {
      $num = $this->db->num_rows($result);
      if ($num > 0)
      {
      $this->ref = $this->getNextNumRef($soc);
      }
      }
      } 



    function getDI() {
        $return = array();
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "element_element WHERE sourcetype='DI' AND targettype='FI' AND fk_target = " . $this->id;
        $res = $this->db->query($requete);
        while ($result = $this->db->fetch_object($res)) {
            $return[] = $result->fk_source;
        }
        return $return;
    }

    function setDI($idDI) {
        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "element_element WHERE fk_target = " . $this->id;
        $res = $this->db->query($requete);
        if($idDI > 0)
        $this->addDI($idDI);
    }

    function addDI($idDI) {
        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "element_element (sourcetype, fk_source, targettype, fk_target) VALUES ('DI', " . $idDI . ", 'FI', " . $this->id . ")";
        $res = $this->db->query($requete);
        $this->majPrixDi();
    }

    /**
     *      \brief      Renvoie la reference de fiche intervention suivante non utilisee en fonction du module
     *                  de numerotation actif defini dans FICHEINTER_ADDON
     *      \param        soc                      objet societe
     *      \return     string              reference libre pour la fiche intervention
     */
//    function getNextNumRef($soc) {
//        global $db, $langs, $conf;
//        $langs->load("interventions");
//
//        $dir = DOL_DOCUMENT_ROOT . "/core/modules/fichinter/";
//
//        if (defined($conf->global->FICHEINTER_ADDON) && $conf->global->FICHEINTER_ADDON) {
//            $file = $conf->global->FICHEINTER_ADDON . ".php";
//
//            // Chargement de la classe de numerotation
//            $classname = "mod" . $conf->global->FICHEINTER_ADDON;
//            require_once($dir . $file);
//
//            $obj = new $classname();
//
//            $numref = "";
//            $numref = $obj->getNumRef($soc, $this);
//
//            if ($numref != "") {
//                return $numref;
//            } else {
//                dol_print_error($db, "Fichinter::getNextNumRef " . $obj->error);
//                return "";
//            }
//        } else {
//            print $langs->trans("Error") . " " . $langs->trans("Error_FICHEINTER_ADDON_NotDefined");
//            return "";
//        }
//    }

    /**
     *      \brief      Information sur l'objet fiche intervention
     *      \param      id      id de la fiche d'intervention
     */
//    function info($id) {
//        $sql = "SELECT f.rowid, ";
//        $sql.= "f.datec as datec, f.date_valid as datev";
//        $sql.= ", f.fk_user_author, f.fk_user_valid";
//        $sql.= " FROM " . MAIN_DB_PREFIX . "fichinter as f";
//        $sql.= " WHERE f.rowid = " . $id;
//
//        $result = $this->db->query($sql);
//
//        if ($result) {
//            if ($this->db->num_rows($result)) {
//                $obj = $this->db->fetch_object($result);
//
//                $this->id = $obj->rowid;
//
//                $this->date_creation = $this->db->jdate($obj->datec);
//                $this->date_validation = $this->db->jdate($obj->datev);
//
//                $cuser = new User($this->db);
//                if ($obj->fk_user_author > 0)
//                    $cuser->fetch($obj->fk_user_author);
//                $this->user_creation = $cuser;
//
//                $vuser = new User($this->db);
//                if ($obj->fk_user_valid > 0)
//                    $vuser->fetch($obj->fk_user_valid);
//                $this->user_validation = $vuser;
//            }
//            $this->db->free($result);
//        } else {
//            dol_print_error($this->db);
//        }
//    }

    /**
     *      \brief     Classe la fiche d'intervention dans un projet
     *      \param     project_id       Id du projet dans lequel classer la facture
     */
    function set_project($user, $project_id) {
        if ($user->rights->synopsisficheinter->creer) {
            //verif que le projet et la societe concordent
            $sql = 'SELECT p.rowid, p.title FROM ' . MAIN_DB_PREFIX . 'projet as p WHERE p.fk_soc =' . $this->socid . ' AND p.rowid=' . $project_id;
            $sqlres = $this->db->query($sql);
            if ($sqlres) {
                $numprojet = $this->db->num_rows($sqlres);
                if ($numprojet > 0) {
                    $this->projetidp = $project_id;
                    $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'fichinter SET fk_projet = ' . $project_id;
                    $sql .= ' WHERE rowid = ' . $this->id . ' AND fk_statut = 0 ;';
                    $this->db->query($sql);
                }
            } else {

                dol_syslog("Fichinter::set_project Erreur SQL");
            }
        }
    }

    function majPrixDi() {
        global $user;
        $this->fetch_lines();
        foreach ($this->lignes as $lignes) {
            if ($lignes->fk_commandedet) {
                $tabDi = getElementElement("DI", 'FI', null, $this->id);
                if(isset($tabDi[0]['s'])){
                    $di = $tabDi[0]['s'];
                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisdemandeintervdet WHERE fk_commandedet =" . $lignes->fk_commandedet. " AND fk_synopsisdemandeinterv = ".$di;
                    $resql = $this->db->query($requete);
                    while ($res = $this->db->fetch_object($resql)) {
                        if ($lignes->pu_ht != $res->pu_ht || $lignes->isForfait != $res->isForfait) {
                            $lignes->pu_ht = $res->pu_ht;
                            $lignes->isForfait = $res->isForfait;
                            $lignes->update($user);
                        }
                    }
                }
            }
        }
    }

    /**
     *    \brief      Efface fiche intervention
     *    \param      user        Objet du user qui efface
     */
    function delete($user, $noTrigger = 0) {
        global $conf, $user, $langs;

        $this->db->begin();

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "synopsisfichinterdet WHERE rowid IN (SELECT f.rowid FROM " . MAIN_DB_PREFIX . "fichinterdet f WHERE fk_fichinter = " . $this->id . ")";
        $sql2 = "DELETE FROM " . MAIN_DB_PREFIX . "fichinterdet WHERE fk_fichinter = " . $this->id;
        dol_syslog("Fichinter::delete sql=" . $sql);
        if ($this->db->query($sql) && $this->db->query($sql2)) {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "synopsisfichinter WHERE rowid = " . $this->id;
            $sql2 = "DELETE FROM " . MAIN_DB_PREFIX . "fichinter WHERE rowid = " . $this->id;
            dol_syslog("Fichinter::delete sql=" . $sql);
            if ($this->db->query($sql) && $this->db->query($sql2)) {

                // Remove directory with files
                $fichinterref = sanitize_string($this->ref);
                if ($conf->fichinter->dir_output) {
                    $dir = $conf->fichinter->dir_output . "/" . $fichinterref;
                    $file = $conf->fichinter->dir_output . "/" . $fichinterref . "/" . $fichinterref . ".pdf";
                    if (file_exists($file)) {
                        synopsisfichinter_delete_preview($this->db, $this->id, $this->ref);

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
                $result = $interface->run_triggers('FICHEINTER_DELETE', $this, $user, $langs, $conf);
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
    public function fetch_client() {
        $this->client = new Societe($this->db);
        $this->client->fetch($this->socid);
    }
    
    public function setExtra($idExtraKey, $val){
        
                $requete = "DELETE FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_value WHERE
                                  interv_refid = " .$this->id . " AND extra_key_refid = '" . $idExtraKey . "' AND typeI = 'FI'";
                $sql = $this->db->query($requete);
        
                $requete = "INSERT INTO " . MAIN_DB_PREFIX . "synopsisfichinter_extra_value
                                         (interv_refid,extra_key_refid,extra_value,typeI)
                                  VALUES (" .$this->id . "," . $idExtraKey . ",'" . addslashes($val) . "','FI')";
                $sql = $this->db->query($requete);
    }

    public function fetch_extra() {
        $requete = "SELECT ifnull(c.label,e.extra_value) as val,
                          extra_key_refid
                     FROM " . MAIN_DB_PREFIX . "synopsisfichinter_extra_value as e
                LEFT JOIN " . MAIN_DB_PREFIX . "synopsisfichinter_extra_values_choice as c ON e.extra_key_refid = c .key_refid AND c.value = e.extra_value
                    WHERE e.typeI = 'FI'
                      AND e.interv_refid = " . $this->id;
        $sql = $this->db->query($requete);
        $arr = array();
//        file_put_contents("/tmp/titi", $requete);
        while ($res = $this->db->fetch_object($sql)) {
            $arr[$res->extra_key_refid] = $res->val;
        }
        $this->extraArr = $arr;
        return $arr;
    }

    function set_date_delivery($user, $date_delivery) {
        global $langs, $conf;
        if ($user->rights->synopsisficheinter->creer) {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "fichinter ";
            $sql.= " SET datei = '" . $this->db->idate($date_delivery)."'";
            $sql.= " WHERE rowid = " . $this->id . " AND fk_statut = 0";

            if ($this->db->query($sql)) {
                $this->date_delivery = $date_delivery;
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('FICHEINTER_SETDELIVERY', $this, $user, $langs, $conf);
                if ($result < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers
                return 1;
            } else {
                $this->error = $this->db->error();
                dol_syslog("Fichinter::set_date_delivery Erreur SQL");
                return -1;
            }
        }
    }

    /**
     *      \brief      Definit le label de l'intervention
     *      \param      user                Objet utilisateur qui modifie
     *      \param      description     description
     *      \return     int                 <0 si ko, >0 si ok
     */
//    function set_description($user, $description) {
//        global $langs, $conf;
//        if ($user->rights->synopsisficheinter->creer) {
//            $sql = "UPDATE " . MAIN_DB_PREFIX . "fichinter ";
//            $sql.= " SET description = '" . addslashes($description) . "'";
//            $sql.= " WHERE rowid = " . $this->id . " AND fk_statut = 0";
//
//            if ($this->db->query($sql)) {
//                $this->description = $description;
//                // Appel des triggers
//                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
//                $interface = new Interfaces($this->db);
//                $result = $interface->run_triggers('FICHEINTER_SETDESCRIPTION', $this, $user, $langs, $conf);
//                if ($result < 0) {
//                    $error++;
//                    $this->errors = $interface->errors;
//                }
//                // Fin appel triggers
//                return 1;
//            } else {
//                $this->error = $this->db->error();
//                dol_syslog("Fichinter::set_description Erreur SQL");
//                return -1;
//            }
//        }
//    }

    function set_note_private($user, $description) {
        global $langs, $conf;
        $this->update_note($description, "_private");
//        if ($user->rights->synopsisficheinter->creer) {
//            $sql = "UPDATE " . MAIN_DB_PREFIX . "synopsis_fichinter ";
//            $sql.= " SET note_private = '" . addslashes($description) . "'";
//            $sql.= " WHERE rowid = " . $this->id . " AND fk_statut = 0";
//
//            if ($this->db->query($sql)) {
//                $this->note_private = $description;
//                // Appel des triggers
//                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
//                $interface = new Interfaces($this->db);
//                $result = $interface->run_triggers('FICHEINTER_SETDESCRIPTION2', $this, $user, $langs, $conf);
//                if ($result < 0) {
//                    $error++;
//                    $this->errors = $interface->errors;
//                }
//                // Fin appel triggers
//                return 1;
//            } else {
//                $this->error = $this->db->error();
//                dol_syslog("Fichinter::set_description Erreur SQL");
//                return -1;
//            }
//        }
    }

    /**
     *      \brief         Ajout d'une ligne d'intervention, en base
     *         \param        fichinterid            Id de la fiche d'intervention
     *         \param        desc                  Description de la ligne
     *    \param      date_intervention   Date de l'intervention
     *    \param      duration            Duree de l'intervention
     *        \return        int                 >0 si ok, <0 si ko
     */
    function addlineSyn($fichinterid, $desc, $date_intervention, $duration, $typeinterv, $qte = 1, $pu_ht = 0, $isForfait = 0, $fk_commandedet = false, $fk_contratdet = false, $typeIntervProd) {
        global $user;
        dol_syslog("Fichinter::Addline $fichinterid, $desc, $date_intervention, $duration");

        if ($this->statut == 0) {
            $this->db->begin();

            // Insertion ligne
            $ligne = new SynopsisfichinterLigne($this->db);

            $ligne->fk_fichinter = $fichinterid;
            $ligne->desc = $desc;
            $ligne->datei = $date_intervention;
            $ligne->duration = $duration;
            $ligne->fk_typeinterv = $typeinterv;
            $ligne->fk_commandedet = $fk_commandedet;
            $ligne->fk_contratdet = $fk_contratdet;
            $ligne->typeIntervProd = $typeIntervProd;

            $this->getTauxHUser($typeinterv, $user);

            $ligne->qte = $qte;
            $ligne->pu_ht = $pu_ht;
            $ligne->isForfait = $isForfait;
            if ($ligne->isForfait == 1) {
                $ligne->total_ht = floatval($ligne->qte) * floatval($pu_ht);
            } else {
                $ligne->total_ht = floatval(($ligne->qte . "x" == "x" ? 1 : $ligne->qte)) * floatval($ligne->duration) * floatval($pu_ht) / 3600;
            }


            $result = $ligne->insert($user);
            if ($result > 0) {
                $this->db->commit();
                return 1;
            } else {
                $this->error = $this->db->error();
                dol_syslog("Error sql=$sql, error=" . $this->error);
                $this->db->rollback();
                return -1;
            }
        }
    }

    function getTauxHUser($typeInter, $user) {
        
    }

    /**
     *        \brief        Initialise la fiche intervention avec valeurs fictives aleatoire
     *                    Sert a generer une fiche intervention pour l'aperu des modeles ou demo
     */
//    function initAsSpecimen() {
//        global $user, $langs;
//
//        // Charge tableau des id de societe socids
//        $socids = array();
//        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "societe WHERE client=1 LIMIT 10";
//        $resql = $this->db->query($sql);
//        if ($resql) {
//            $num_socs = $this->db->num_rows($resql);
//            $i = 0;
//            while ($i < $num_socs) {
//                $i++;
//
//                $row = $this->db->fetch_row($resql);
//                $socids[$i] = $row[0];
//            }
//        }
//
//        // Charge tableau des produits prodids
//        $prodids = array();
//        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "product WHERE tobuy=1";
//        $resql = $this->db->query($sql);
//        if ($resql) {
//            $num_prods = $this->db->num_rows($resql);
//            $i = 0;
//            while ($i < $num_prods) {
//                $i++;
//                $row = $this->db->fetch_row($resql);
//                $prodids[$i] = $row[0];
//            }
//        }
//
//        // Initialise parametres
//        $this->id = 0;
//        $this->ref = 'SPECIMEN';
//        $this->specimen = 1;
//        $socid = rand(1, $num_socs);
//        $this->socid = $socids[$socid];
//        $this->date = time();
//        $this->date_lim_reglement = $this->date + 3600 * 24 * 30;
//        $this->cond_reglement_code = 'RECEP';
//        $this->mode_reglement_code = 'CHQ';
//        $this->note_public = 'SPECIMEN';
//        $nbp = 5;
//        $xnbp = 0;
//        while ($xnbp < $nbp) {
//            $ligne = new SynopsisfichinterLigne($this->db);
//            $ligne->desc = $langs->trans("Description") . " " . $xnbp;
//            $ligne->qty = 1;
//            $ligne->subprice = 100;
//            $ligne->price = 100;
//            $ligne->tva_tx = 19.6;
//            $prodid = rand(1, $num_prods);
//            $ligne->produit_id = $prodids[$prodid];
//            $this->lignes[$xnbp] = $ligne;
//            $xnbp++;
//        }
//
//        $this->amount_ht = $xnbp * 100;
//        $this->total_ht = $xnbp * 100;
//        $this->total_tva = $xnbp * 19.6;
//        $this->total_ttc = $xnbp * 119.6;
//    }

    /**
     *        \brief        Initialise la fiche intervention avec valeurs fictives aleatoire
     *                    Sert a generer une fiche intervention pour l'aperu des modeles ou demo
     *         \return        int        <0 OK,    >0 KO
     */
    function fetch_lines() {
        $sql = 'SELECT rowid';
        $sql.= " FROM " . MAIN_DB_PREFIX . "fichinterdet";
        $sql.= ' where fk_fichinter = ' . $this->id;

        dol_syslog("Fichinter::fetch_lines sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($sql);
            if ($num > 0) {
                while ($objp = $this->db->fetch_object($resql)) {
                    //var_dump($objp->rowid);
                    if ($objp->rowid > 0) {
                        $fichinterligne = new SynopsisfichinterLigne($this->db);
                        $fichinterligne->id = $objp->rowid;
                        $fichinterligne->fetch($objp->rowid);
                        $this->lignes[$objp->rowid] = $fichinterligne;
                    }
                }
            }
            $this->db->free($resql);
            return 1;
        } else {
            $this->error = $this->db->error();
            return -1;
        }
    }

    /**
      \brief      Renvoie nom clicable (avec eventuellement le picto)
      \param        withpicto        0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
      \param        option            Sur quoi pointe le lien: 0=fiche commande,3=fiche compta commande,4=fiche expedition commande
      \return        string            Chaine avec URL
     */
    function getNomUrl($withpicto = 0, $option = 0) {
        global $langs;

        $result = '';
        $urlOption = '';

        $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/synopsisfichinter/ficheFast.php?id=' . $this->id . '">';
        if ($option == 6)
            $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/synopsisfichinter/ficheFast.php?id=' . $this->id . '">';
        $lienfin = '</a>';

        $picto = 'intervention';
        $label = $langs->trans("InterventionCard") . ': ' . $this->ref;

        if ($withpicto)
            $result.=($lien . img_object($label, $picto) . $lienfin);
        if ($withpicto && $option == 6)
            $result.=($lien . img_object($label, $picto, 16, 16, false, true) . $lienfin);
        if ($withpicto && $withpicto != 2)
            $result.=' ';
        $result.=$lien . $this->ref . $lienfin;
        return $result;
    }

}

/**
  \class      FichinterLigne
  \brief      Classe permettant la gestion des lignes d'intervention
 */
class SynopsisfichinterLigne extends FichinterLigne{

    public $db;
    public $error;
    // From ".MAIN_DB_PREFIX."synopsis_fichinterdet
    public $rowid;
    public $fk_fichinter;
    public $desc;              // Description ligne
    public $datei;           // Date intervention
    public $duration;        // Duree de l'intervention
    public $rang = 0;
    public $typeIntervProd;
    public $extraArr = array();

    /**
     *      \brief     Constructeur d'objets ligne d'intervention
     *      \param     DB      handler d'acces base de donnee
     */
    function SynopsisfichinterLigne($DB) {
        $this->db = $DB;
    }

    /**
     *      \brief     Recupere l'objet ligne d'intervention
     *      \param     rowid           id de la ligne
     */
    function fetch($rowid, $ref='') {
        if ($ref) $where = "ref='".$this->db->escape($ref)."'";
	else $where = "rowid=".$rowid;
        
        $result = $this->db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."synopsisfichinterdet WHERE ".$where);
        if($this->db->num_rows($result) == 0)
            $this->db->query("INSERT INTO ".MAIN_DB_PREFIX."synopsisfichinterdet (rowid) VALUES (".$rowid.")");
        
        $sql = 'SELECT ft.rowid, ft.fk_fichinter, ft.description, ft.duree, ft.rang, ft.fk_typeinterv, f.label as typeinterv ';
        $sql .= ',`tx_tva`,`pu_ht` ,`qte`,`total_ht`,`total_tva`,`total_ttc`,`fk_contratdet`,`fk_commandedet`,`isForfait`';
        $sql.= ' ,ft.date as datei,fk_depProduct,f.isDeplacement';
        $sql.= " FROM " . MAIN_DB_PREFIX . "synopsis_fichinterdet as ft";
        $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv as f ON f.id = ft.fk_typeinterv";

        
        
        $sql.= ' WHERE ft.'.$where;
        dol_syslog("FichinterLigne::fetch sql=" . $sql);
        $result = $this->db->query($sql);
        if ($result) {
            $objp = $this->db->fetch_object($result);
            $this->rowid = $objp->rowid;
            $this->id = $objp->rowid;
            $this->fk_fichinter = $objp->fk_fichinter;
            $this->datei = $objp->datei;
            $this->desc = $objp->description;
            $this->duration = $objp->duree;
            $this->fk_typeinterv = $objp->fk_typeinterv;
            $this->typeinterv = $objp->typeinterv;
            $this->rang = $objp->rang;
            $this->tx_tva = $objp->tx_tva;
            $this->pu_ht = $objp->pu_ht;
            $this->qte = $objp->qte;
            $this->total_ht = $objp->total_ht;
            $this->total_tva = $objp->total_tva;
            $this->total_ttc = $objp->total_ttc;
            $this->fk_contratdet = $objp->fk_contratdet;
            $this->fk_commandedet = $objp->fk_commandedet;
            $this->isForfait = $objp->isForfait;
            $this->typeIntervProd = $objp->fk_depProduct;
            $this->isDeplacement = $objp->isDeplacement;

            $this->db->free($result);
            return 1;
        } else {
            $this->error = $this->db->error() . ' sql=' . $sql;
            dol_print_error($this->db, $this->error);
            return -1;
        }
    }
    
    
    
    function create($user, $noTrigger = 0){
        return $this->insert($user, $noTrigger);
    }
    /**
     *      \brief         Insere l'objet ligne d'intervention en base
     *        \return        int        <0 si ko, >0 si ok
     */
    function insert($user, $noTrigger = 0) {
        global $user, $langs, $conf;
        dol_syslog("FichinterLigne::insert rang=" . $this->rang);
        $this->db->begin();

        $rangToUse = $this->rang;
        if ($rangToUse == -1) {
            // Recupere rang max de la ligne d'intervention dans $rangmax
            $sql = 'SELECT max(rang) as max FROM ' . MAIN_DB_PREFIX . 'fichinterdet';
            $sql.= ' WHERE fk_fichinter =' . $this->fk_fichinter;
            $resql = $this->db->query($sql);
            if ($resql) {
                $obj = $this->db->fetch_object($resql);
                $rangToUse = $obj->max + 1;
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
        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'fichinterdet';
        $sql.= ' (fk_fichinter, description, date, duree, rang';
        $sql.= ')';
        $sql.= " VALUES (" . $this->fk_fichinter . ",";
        $sql.= " '" . addslashes($this->desc) . "',";
        $sql.= " '" . $this->db->idate($this->datei) . "',";
        $sql.= " " . $this->duration . ",";
        $sql.= ' ' . $rangToUse;
        $sql.= ')';


        dol_syslog("FichinterLigne::insert sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "fichinterdet");
            $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'synopsisfichinterdet';
            $sql.= ' (rowid, fk_typeinterv, isForfait ';
            if ($this->qte > 0)
                $sql .= ',qte ';
            if ($this->pu_ht > 0)
                $sql .= ',pu_ht, total_ht, total_tva, total_ttc ';
            if ($this->fk_commandedet > 0)
                $sql .= ',fk_commandedet ';
            if ($this->fk_contratdet > 0)
                $sql .= ',fk_contratdet ';
            if ($this->typeIntervProd > 0)
                $sql .= ", fk_depProduct ";
            $sql.= ')';
            $sql.= " VALUES (" . $this->id. ",";
            $sql.= " " . ($this->fk_typeinterv > 0 ? $this->fk_typeinterv : "NULL");
            $sql.= ' ,' . ($this->isForfait > 0 ? 1 : 0);
            if ($this->qte > 0)
                $sql .= ' ,' . preg_replace('/,/', '.', $this->qte);
            if ($this->pu_ht > 0)
                $sql .= "," . preg_replace('/,/', '.', $this->pu_ht) . "," . preg_replace('/,/', '.', $this->total_ht) . "," . preg_replace('/,/', '.', $total_tva) . "," . preg_replace('/,/', '.', $total_ttc);
            if ($this->fk_commandedet > 0)
                $sql .= ', ' . $this->fk_commandedet;
            if ($this->fk_contratdet > 0)
                $sql .= ', ' . $this->fk_contratdet;
            if ($this->typeIntervProd > 0)
                $sql .= ", " . $this->typeIntervProd;
            $sql.= ')';
        $resql = $this->db->query($sql);



            $result = $this->update_total();
            if ($result > 0) {
                $this->rang = $rangToUse;
                $this->db->commit();
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('FICHEINTER_INSERTLINE', $this, $user, $langs, $conf);
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
            dol_syslog("FichinterLigne::insert Error " . $this->error);
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *      \brief         Mise a jour de l'objet ligne d'intervention en base
     *        \return        int        <0 si ko, >0 si ok
     */
    function update($user, $noTrigger = 0) {
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

        $isDep = false;
        
        if($this->comLigneId == "")
            $this->comLigneId = $this->fk_commandedet;
        
        if($this->fk_typeinterv){
            $requete = "SELECT isDeplacement FROM " . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv WHERE id =" . $this->fk_typeinterv;
            $sql = $this->db->query($requete);
            $res = $this->db->fetch_object($sql);
                if ($res->isDeplacement == 1 || 1)
                    $isDep = true;
        }

        // Mise a jour ligne en base
        $sql = "UPDATE " . MAIN_DB_PREFIX . "fichinterdet SET";
        $sql.= " description='" . addslashes($this->desc) . "'";
        $sql.= ",date='" . $this->db->idate($this->datei)."'";
        $sql.= ",duree=" . $this->duration;
        $sql.= ",rang='" . $this->rang . "'";
        $sql.= " WHERE rowid = " . $this->rowid;

        dol_syslog("FichinterLigne::update sql=" . $sql);

        $resql = $this->db->query($sql);
        
        $sql = "UPDATE " . MAIN_DB_PREFIX . "synopsisfichinterdet SET";
        $sql.= " fk_typeinterv=" . ($this->fk_typeinterv > 0 ? $this->fk_typeinterv : 'NULL');
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
        else
            $sql .= ",isForfait = 0";
        if ($this->comLigneId > 0)
            $sql .= ",fk_commandedet =  " . $this->comLigneId;
        //else $sql .= ",fk_commandedet =  NULL ";
        if ($this->fk_contratdet > 0)
            $sql .= ",fk_contratdet =  " . $this->fk_contratdet;
        if ($this->fk_contratdet == 0)
            $sql .= ",fk_contratdet =  0";
        if ($this->typeIntervProd > 0 && $isDep)
            $sql .= ",fk_depProduct = " . $this->typeIntervProd;
        //else if (!$isDep) $sql .= ",fk_depProduct = NULL";
        $sql.= " WHERE rowid = " . $this->rowid;
        
        $resql2 = $this->db->query($sql);        
        
        if ($resql && $resql2) {
            $result = $this->update_total();
            if ($result > 0) {
                $this->db->commit();
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result1 = $interface->run_triggers('FICHEINTER_UPDATELINE', $this, $user, $langs, $conf);
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
            dol_syslog("FichinterLigne::update Error " . $this->error);
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *      \brief         Mise a jour duree total dans table ".MAIN_DB_PREFIX."synopsis_fichinter
     *        \return        int        <0 si ko, >0 si ok
     */
    function update_total() {
        global $user, $langs, $conf;
        /*        $sql = "SELECT SUM(duree) as total_duration";
          $sql.= " FROM ".MAIN_DB_PREFIX."synopsis_fichinterdet";
          $sql.= " WHERE fk_fichinter=".$this->fk_fichinter;

          dol_syslog("FichinterLigne::update_total sql=".$sql);
          $resql=$this->db->query($sql);
          if ($resql)
          {
          $obj=$this->db->fetch_object($resql);
          $total_duration=0;
          if ($obj) $total_duration = $obj->total_duration;

          $sql = "UPDATE ".MAIN_DB_PREFIX."synopsis_fichinter";
          $sql.= " SET duree = ".$total_duration;
          $sql.= " WHERE rowid = ".$this->fk_fichinter;

          dol_syslog("FichinterLigne::update_total sql=".$sql);
          $resql=$this->db->query($sql);
          if ($resql)
          {
          // Appel des triggers
          include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
          $interface=new Interfaces($this->db);
          $result=$interface->run_triggers('FICHEINTER_UPDATETOTAL',$this,$user,$langs,$conf);
          if ($result < 0) { $error++; $this->errors=$interface->errors; }
          // Fin appel triggers
          $this->db->commit();
          return 1;
          }
          else
          {
          $this->error=$this->db->error();
          dol_syslog("FichinterLigne::update_total Error ".$this->error);
          $this->db->rollback();
          return -2;
          }
          }
          else
          {
          $this->error=$this->db->error();
          dol_syslog("FichinterLigne::update Error ".$this->error);
          $this->db->rollback();
          return -1;
          }
         */
        $result = 1;
        $requete = "SELECT sum(total_ht) as sht,
                                   sum(total_tva) as stva,
                                   sum(total_ttc) as sttc,
                                   sum(duree) as sdur
                              FROM " . MAIN_DB_PREFIX . "synopsis_fichinterdet
                             WHERE fk_fichinter = " . $this->fk_fichinter;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $requete = "UPDATE " . MAIN_DB_PREFIX . "fichinter
                               SET duree = '" . $res->sdur . "'
                             WHERE rowid = " . $this->fk_fichinter;
        $requete2 = "UPDATE " . MAIN_DB_PREFIX . "synopsisfichinter
                               SET total_ht = '" . $res->sht . "',
                                   total_tva = '" . $res->stva . "' ,
                                   total_ttc = '" . $res->sttc . "'
                             WHERE rowid = " . $this->fk_fichinter;
        $sql = $this->db->query($requete);
        $sql2 = $this->db->query($requete2);
        if ($sql && $sql2) {
            $result = 1;
        }
        $this->db->commit();

        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface = new Interfaces($this->db);
        $result = $interface->run_triggers('FICHINTER_UPDATETOTAL', $this, $user, $langs, $conf);
        if ($result < 0) {
            $error++;
            $this->errors = $interface->errors;
        } else {
            $result = 1;
        }
        // Fin appel triggers
        return($result);
    }

    /**
     *      \brief      Supprime une ligne d'intervention
     *      \return     int         >0 si ok, <0 si ko
     */
    function delete(){
        return $this->delete_line();
    }
    
    
    function delete_line() {
        global $user, $langs, $conf;
        if ($this->statut == 0) {
            dol_syslog("FichinterLigne::delete_line lineid=" . $this->rowid);
            $this->db->begin();

            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "synopsisfichinterdet WHERE rowid = " . $this->rowid;
            $sql2 = "DELETE FROM " . MAIN_DB_PREFIX . "fichinterdet WHERE rowid = " . $this->rowid;
            $resql = $this->db->query($sql);
            $resql2 = $this->db->query($sql2);
            dol_syslog("FichinterLigne::delete_line sql=" . $sql);

            if ($resql && $resql2) {
                $result = $this->update_total();
                if ($result > 0) {
                    $this->db->commit();
                    //APpel Trigger
                    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                    $interface = new Interfaces($this->db);
                    $result = $interface->run_triggers('FICHEINTER_DELETELINE', $this, $user, $langs, $conf);
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
                dol_syslog("FichinterLigne::delete_line Error " . $this->error);
                $this->db->rollback();
                return -1;
            }
        } else {
            return -2;
        }
    }

}

?>
