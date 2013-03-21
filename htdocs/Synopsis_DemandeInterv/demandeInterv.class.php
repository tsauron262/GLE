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

/**        \file       htdocs/synopsis_demandeinterv/demandeInterv.class.php
  \ingroup    demandeInterv
  \brief      Fichier de la classe des gestion des fiches interventions
  \version    $Id: demandeInterv.class.php,v 1.58 2008/07/12 12:45:30 eldy Exp $
 */
require_once(DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

/**
 *     \class      demandeInterv
 *    \brief      Classe des gestion des fiches interventions
 */
class demandeInterv extends CommonObject {

    public $db;
    public $element = 'demandeInterv';
    public $table_element = 'Synopsis_demandeInterv';
    public $fk_element = 'fk_demandeInterv';
    public $table_element_line = 'Synopsis_demandeIntervdet';
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
    function demandeInterv($DB, $socid = "") {
        global $langs;

        $this->db = $DB;
        $this->socid = $socid;
        $this->products = array();
        $this->projet_id = 0;

        // Statut 0=brouillon, 1=valide
        $this->statuts[0] = $langs->trans("Draft");
        $this->statuts[1] = $langs->trans("Validated");
        $this->statuts[2] = $langs->trans("En cours");
        $this->statuts[3] = $langs->trans("Cl&ocirc;turer");
        $this->statuts_short[0] = $langs->trans("Draft");
        $this->statuts_short[1] = $langs->trans("Validated");
        $this->statuts_short[2] = $langs->trans("En cours");
        $this->statuts_short[3] = $langs->trans("Cl&ocirc;turer");
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
        dol_syslog("demandeInterv.class::create ref=" . $this->ref);
        if (!is_numeric($this->duree)) {
            $this->duree = 0;
        }
        if ($this->socid <= 0) {
            $this->error = 'ErrorBadParameterForFunc';
            dol_syslog("demandeInterv::create " . $this->error, LOG_ERR);
            return -1;
        }
//    $demandeInterv->fk_user_prisencharge = $_POST["userid"];
//    $demandeInterv->fk_commande = $_POST["comLigneId"];

        $this->db->begin();

        // on verifie si la ref n'est pas utilisee
        $soc = new Societe($this->db);
        $result = $soc->fetch($this->socid);
        $this->soc = $soc;
        $this->verifyNumRef();
        $this->statut = 0;

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_demandeInterv (fk_soc, datec, ref, fk_user_author, description, model_pdf";
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
            $sql.= ", '" . date('Y-m-d', $this->date) . "'";
        $sql.= ")";
        $sqlok = 0;
        dol_syslog("demandeInterv::create sql=" . $sql);
        $result = $this->db->query($sql);
        if ($result) {
            $this->id = $this->db->last_insert_id("" . MAIN_DB_PREFIX . "Synopsis_demandeInterv");
            $this->db->commit();
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('DEMANDEINTERV_CREATE', $this, $user, $langs, $conf);
            if ($result < 0) {
                $error++;
                $this->errors = $interface->errors;
            }
            // Fin appel triggers
            return $this->id;
        } else {
            $this->error = $this->db->error();
            dol_syslog("demandeInterv::create " . $this->error, LOG_ERR);
            $this->db->rollback();
            return -1;
        }
    }

    /*
     *    \brief        Met a jour une intervention
     *    \return        int        <0 si ko, >0 si ok
     */

    function update($id) {
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
        $sql = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_demandeInterv SET ";
        $sql .= " datei = " . $this->db->idate($this->date);
        $sql .= ", description  = '" . addslashes($this->description) . "'";
        $sql .= ", duree = " . $this->duree;
        $sql .= ", fk_projet = " . $this->projet_id;
        $sql .= " WHERE rowid = " . $id;

        dol_syslog("demandeInterv::update sql=" . $sql);
        if (!$this->db->query($sql)) {
            $this->error = $this->db->error();
            dol_syslog("demandeInterv::update error " . $this->error, LOG_ERR);
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('DEMANDEINTERV_UPDATE', $this, $user, $langs, $conf);
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
        $sql = "SELECT ref, description, fk_soc, fk_statut,fk_user_prisencharge, fk_user_author, fk_contrat, fk_commande, total_ht, total_tva, total_ttc, ";
        $sql.= " datei as di, duree, fk_projet, note_public, note_private, model_pdf";
        $sql.= " FROM " . MAIN_DB_PREFIX . "Synopsis_demandeInterv";
        $sql.= " WHERE rowid=" . $rowid;

        dol_syslog("demandeInterv::fetch sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql) > 0) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $rowid;
                $this->ref = $obj->ref;
                $this->description = $obj->description;
                $this->socid = $obj->fk_soc;
                $this->fk_soc = $obj->fk_soc;
                $this->fk_contrat = $obj->fk_contrat;
                $this->fk_commande = $obj->fk_commande;
                $tmpSoc = new Societe($this->db);
                $tmpSoc->fetch($obj->fk_soc);
                $this->societe = $tmpSoc;
                $this->statut = $obj->fk_statut;
                $this->date = $obj->di;
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
                $this->user_author_id = $obj->fk_user_author;

                if ($this->statut == 0)
                    $this->brouillon = 1;

                $this->db->free($resql);
                return $this->id;
            } else {
                $this->error = $this->db->error();
                dol_syslog("demandeInterv::fetch error " . $this->error, LOG_ERR);
                return -2;
            }
        } else {
            $this->error = $this->db->error();
            dol_syslog("demandeInterv::fetch error " . $this->error, LOG_ERR);
            return -1;
        }
    }

    private function sendMail($subject, $to, $from, $msg, $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array(), $addr_cc = '', $addr_bcc = '', $deliveryreceipt = 0, $msgishtml = 1, $errors_to = '') {
        global $mysoc;
        global $langs;
        require_once(DOL_DOCUMENT_ROOT . '/Synopsis_Tools/class/CMailFile.class.php');
        $mail = new CMailFile($subject, $to, $from, $msg,
                        $filename_list, $mimetype_list, $mimefilename_list,
                        $addr_cc, $addr_bcc, $deliveryreceipt, $msgishtml, $errors_to);
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
    function valid($user, $outputdir) {
        global $langs, $conf;

        $this->db->begin();

        $sql = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_demandeInterv";
        $sql.= " SET fk_statut = 1, date_valid=now(), fk_user_valid=" . $user->id;
        $sql.= " WHERE rowid = " . $this->id . " AND fk_statut = 0";
//die($sql);
        dol_syslog("demandeInterv::valid sql=" . $sql);
        $resql = $this->db->query($sql);

        if ($resql) {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('DEMANDEINTERV_VALIDATE', $this, $user, $langs, $conf);
            if ($result < 0) {
                $error++;
                $this->errors = $interface->errors;
            }
            // Fin appel triggers

            if ($resql) {

                $this->db->commit();
                //notification
                if (isset($conf->global->BIMP_MAIL_GESTPROD)) {
                    $this->info($this->id);
                    $subject = utf8_encode("[Nouvelle Intervention] Nouvelle intervention chez " . $this->societe->nom);
                    $to = $this->user_prisencharge->email;

                    $msg = "Bonjour " . $this->user_prisencharge->fullname . ",<br/><br/>";
                    $msg .= "Une nouvelle intervention est pr&eacute;vue chez " . $this->societe->getNomUrl(1, 6) . ".";
                    $msg .= "<br/>";
                    $msg .= "<br/>";
                    $msg .= "Ref Demande Intervertion : " . $this->getNomUrl(1, 6);

                    $msg .= "<br/><br/>Cordialement,<br/>\nGLE\n";
                    $from = $conf->global->BIMP_MAIL_FROM;
                    $addr_cc = $conf->global->BIMP_MAIL_GESTPROD;

                    $this->sendMail($subject, $to, $from, utf8_encode($msg), array(), array(), array(), $addr_cc, '', 0, $msgishtml = 1, $from);
                }


                return 1;
            } else {
                $this->db->rollback();
                $this->error = join(',', $this->errors);
                dol_syslog("demandeInterv::update " . $this->error, LOG_ERR);
                return -1;
            }
        } else {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            dol_syslog("demandeInterv::update " . $this->error, LOG_ERR);
            return -1;
        }
    }

    function invalid($user, $outputdir) {
        global $langs, $conf;

        $this->db->begin();

        $sql = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_demandeInterv";
        $sql.= " SET fk_statut = 0, date_valid=now(), fk_user_valid=" . $user->id;
        $sql.= " WHERE rowid = " . $this->id . " ";

        dol_syslog("demandeInterv::valid sql=" . $sql);
        $resql = $this->db->query($sql);

        if ($resql) {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('DEMANDEINTERV_INVALIDATE', $this, $user, $langs, $conf);
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
                dol_syslog("demandeInterv::update " . $this->error, LOG_ERR);
                return -1;
            }
        } else {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            dol_syslog("demandeInterv::update " . $this->error, LOG_ERR);
            return -1;
        }
    }

    function SelectTarget($user) {
        global $langs, $conf;

        $this->db->begin();

        $sql = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_demandeInterv";
        $sql.= " SET fk_user_target=" . $user->id;
        $sql.= " WHERE rowid = " . $this->id . " ";

        dol_syslog("demandeInterv::valid sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('DEMANDEINTERV_ATTRIB', $this, $user, $langs, $conf);
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
                dol_syslog("demandeInterv::update " . $this->error, LOG_ERR);
                return -1;
            }
        } else {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            dol_syslog("demandeInterv::update " . $this->error, LOG_ERR);
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

        $sql = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_demandeInterv";
        $sql.= " SET fk_statut = 2, date_prisencharge=now(), fk_user_prisencharge=" . $user->id;
        $sql.= " WHERE rowid = " . $this->id . " AND fk_statut = 1";

        dol_syslog("demandeInterv::valid sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('DEMANDEINTERV_PRISENCHARGE', $this, $user, $langs, $conf);
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
                    $msg .= "<br/>";
                    $msg .= "<br/>";
                    $msg .= "Ref Demande Intervertion : " . $this->getNomUrl(1, 6);

                    $msg .= "<br/><br/>Cordialement,<br/>\nGLE\n";
                    $from = $conf->global->BIMP_MAIL_FROM;
                    $addr_cc = $this->user_prisencharge->email;

                    $this->sendMail($subject, $to, $from, $msg, array(), array(), array(), $addr_cc, '', 0, 1, $from);
                }

                return 1;
            } else {
                $this->db->rollback();
                $this->error = join(',', $this->errors);
                dol_syslog("demandeInterv::update " . $this->error, LOG_ERR);
                return -1;
            }
        } else {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            dol_syslog("demandeInterv::update " . $this->error, LOG_ERR);
            return -1;
        }
    }

    function preparePrisencharge($user, $outputdir = "") {
        global $langs, $conf;

        $this->db->begin();

        $sql = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_demandeInterv";
        $sql.= " SET fk_user_prisencharge=" . $user->id;
        $sql.= " WHERE rowid = " . $this->id . " AND fk_statut < 2";

        dol_syslog("demandeInterv::valid sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('DEMANDEINTERV_PRISENCHARGE', $this, $user, $langs, $conf);
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
                dol_syslog("demandeInterv::update " . $this->error, LOG_ERR);
                return -1;
            }
        } else {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            dol_syslog("demandeInterv::update " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *  \brief Prise en charge  une fiche intervention
     *  \param  user User qui prend en charge
     *  \return int
     * <0 si ko, >0 si ok
     */
    function cloture($user, $outputdir) {
        global $langs, $conf;

        $this->db->begin();

        $sql = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_demandeInterv";
        $sql.= " SET fk_statut = 3, date_cloture=now(), fk_user_cloture=" . $user->id;
        $sql.= " WHERE rowid = " . $this->id . " AND fk_statut = 2";

        dol_syslog("demandeInterv::valid sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('DEMANDEINTERV_CLOTURE', $this, $user, $langs, $conf);
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
                dol_syslog("demandeInterv::update " . $this->error, LOG_ERR);
                return -1;
            }
        } else {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            dol_syslog("demandeInterv::update " . $this->error, LOG_ERR);
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
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "Synopsis_demandeInterv";
        $sql.= " WHERE ref = '" . $this->ref . "'";

        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);
            if ($num > 0) {
                $this->ref = $this->getNextNumRef($this->soc);
            }
        }
    }

    /**
     *      \brief      Renvoie la reference de fiche intervention suivante non utilisee en fonction du module
     *                  de numerotation actif defini dans demandeInterv_ADDON
     *      \param        soc                      objet societe
     *      \return     string              reference libre pour la fiche intervention
     */
    function getNextNumRef($soc) {
        global $db, $langs, $conf;
        $langs->load("interventions");
        $dir = DOL_DOCUMENT_ROOT . "/core/modules/synopsisdemandeinterv/";
        if ($conf->global->DEMANDEINTERV_ADDON . "x" != "x") {
            $file = "mod_" . $conf->global->DEMANDEINTERV_ADDON . ".php";

            // Chargement de la classe de numerotation
            $classname = "mod_" . $conf->global->DEMANDEINTERV_ADDON;
            require_once($dir . $file);

            $obj = new $classname();

            $numref = "";
            $numref = $obj->getNumRef($soc, $this);

            if ($numref != "") {
                return $numref;
            } else {
                dol_print_error($db, "demandeInterv::getNextNumRef " . $obj->error);
                return "";
            }
        } else {
            print $langs->trans("Error") . " " . $langs->trans("Error_DEMANDEINTERV_ADDON_NotDefined");
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
        $sql.= " FROM " . MAIN_DB_PREFIX . "Synopsis_demandeInterv as f";
        $sql.= " WHERE f.rowid = " . $id;

        $result = $this->db->query($sql);

        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);

                $this->id = $obj->rowid;

                $this->date_creation = $obj->datec;
                $this->date_validation = $obj->datev;

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
                    $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'Synopsis_demandeInterv SET fk_projet = ' . $project_id;
                    $sql .= ' WHERE rowid = ' . $this->id . ' AND fk_statut = 0 ;';
                    $this->db->query($sql);
                }
            } else {

                dol_syslog("demandeInterv::set_project Erreur SQL");
            }
        }
    }

    /**
     *    \brief      Efface fiche intervention
     *    \param      user        Objet du user qui efface
     */
    function delete($user) {
        global $conf, $user, $langs;

        $this->db->begin();

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_demandeIntervdet WHERE fk_demandeInterv = " . $this->id;
        dol_syslog("demandeInterv::delete sql=" . $sql);
        if ($this->db->query($sql)) {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_demandeInterv WHERE rowid = " . $this->id;
            dol_syslog("demandeInterv::delete sql=" . $sql);
            if ($this->db->query($sql)) {

                // Remove directory with files
                $demandeIntervref = sanitize_string($this->ref);
                if ($conf->synopsisdemandeinterv->dir_output) {
                    $dir = $conf->synopsisdemandeinterv->dir_output . "/" . $demandeIntervref;
                    $file = $conf->synopsisdemandeinterv->dir_output . "/" . $demandeIntervref . "/" . $demandeIntervref . ".pdf";
                    if (file_exists($file)) {
                        demandeInterv_delete_preview($this->db, $this->id, $this->ref);

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
                $result = $interface->run_triggers('DEMANDEINTERV_DELETE', $this, $user, $langs, $conf);
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
    function set_date_delivery($user, $date_delivery) {
        global $langs, $conf;
        if ($user->rights->synopsisdemandeinterv->creer) {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_demandeInterv ";
            $sql.= " SET datei = " . $this->db->idate($date_delivery);
            $sql.= " WHERE rowid = " . $this->id . " AND fk_statut = 0";

            if ($this->db->query($sql)) {
                $this->date_delivery = $date_delivery;
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('DEMANDEINTERV_SETDELIVERY', $this, $user, $langs, $conf);
                if ($result < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers
                return 1;
            } else {
                $this->error = $this->db->error();
                dol_syslog("demandeInterv::set_date_delivery Erreur SQL");
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
    function set_description($user, $description) {
        global $langs, $conf;
        if ($user->rights->synopsisdemandeinterv->creer) {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_demandeInterv ";
            $sql.= " SET description = '" . addslashes($description) . "'";
            $sql.= " WHERE rowid = " . $this->id . " AND fk_statut = 0";

            if ($this->db->query($sql)) {
                $this->description = $description;
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('DEMANDEINTERV_SETDESCRIPTION', $this, $user, $langs, $conf);
                if ($result < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers
                return 1;
            } else {
                $this->error = $this->db->error();
                dol_syslog("demandeInterv::set_description Erreur SQL");
                return -1;
            }
        }
    }

    /**
     *      \brief         Ajout d'une ligne d'intervention, en base
     *         \param        demandeIntervid            Id de la fiche d'intervention
     *         \param        desc                  Description de la ligne
     *    \param      date_intervention   Date de l'intervention
     *    \param      duration            Duree de l'intervention
     *        \return        int                 >0 si ok, <0 si ko
     */
    function addline($demandeIntervid, $desc, $date_intervention, $duration, $typeinterv, $qte = 1, $pu_ht = 0, $isForfait = 0, $fk_commandedet = false, $fk_contratdet = false) {
        dol_syslog("demandeInterv::Addline $demandeIntervid, $desc, $date_intervention, $duration");

        if ($this->statut == 0) {
            $this->db->begin();

            // Insertion ligne
            $ligne = new demandeIntervLigne($this->db);

            $ligne->fk_demandeInterv = $demandeIntervid;
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
            $ligne = new demandeIntervLigne($this->db);
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
        $sql.= '  FROM ' . MAIN_DB_PREFIX . 'Synopsis_demandeIntervdet';
        $sql.= ' WHERE fk_demandeInterv = ' . $this->id;

        dol_syslog("demandeInterv::fetch_lines sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $objp = $this->db->fetch_object($resql);

                $demandeIntervligne = new demandeIntervLigne($this->db);
                $demandeIntervligne->id = $objp->rowid;
                $demandeIntervligne->fetch($objp->rowid);
                //               var_dump($demandeIntervligne);
                $this->lignes[$i] = $demandeIntervligne;

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

        $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/Synopsis_DemandeInterv/fiche.php?id=' . $this->id . '">';
        if ($option == 6)
            $lien = '<a href="' . DOL_URL_ROOT . $urlOption . '/Synopsis_DemandeInterv/fiche.php?id=' . $this->id . '">';
        $lienfin = '</a>';

        $picto = 'demandeInterv@Synopsis_DemandeInterv';
        $label = $langs->trans("DIs") . ': ' . $this->ref;

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
  \class      demandeIntervLigne
  \brief      Classe permettant la gestion des lignes d'intervention
 */
class demandeIntervLigne {

    public $db;
    public $error;
    // From ".MAIN_DB_PREFIX."Synopsis_demandeIntervdet
    public $rowid;
    public $fk_demandeInterv;
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
    function demandeIntervLigne($DB) {
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
                            ft.fk_demandeInterv,
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
        $sql.= '       FROM ' . MAIN_DB_PREFIX . 'Synopsis_demandeIntervdet as ft';
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_fichinter_c_typeInterv as t ON ft.fk_typeinterv=t.id";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commandedet as c ON c.rowid = ft.fk_commandedet";
        $sql.= '      WHERE ft.rowid = ' . $rowid;
        dol_syslog("demandeIntervLigne::fetch sql=" . $sql);
        $result = $this->db->query($sql);
        if ($result) {
            $objp = $this->db->fetch_object($result);
            $this->rowid = $objp->rowid;
            $this->fk_demandeInterv = $objp->fk_demandeInterv;
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
        dol_syslog("demandeIntervLigne::insert rang=" . $this->rang);
        $this->db->begin();

        $rangToUse = $this->rang;
        if (!$rangToUse > 0) {
            // Recupere rang max de la ligne d'intervention dans $rangmax
            $sql = 'SELECT max(rowid) as max FROM ' . MAIN_DB_PREFIX . 'Synopsis_demandeIntervdet';
            $sql.= ' WHERE fk_demandeInterv =' . $this->fk_demandeInterv;
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
        $total_ttc = 1.196 * $this->total_ht;
        $total_tva = 0.196 * $this->total_ht;

        // Insertion dans base de la ligne
        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'Synopsis_demandeIntervdet';
        $sql.= ' (fk_demandeInterv, description, date, duree, rang,fk_typeinterv, isForfait ';
        if ($this->qte > 0)
            $sql .= ',qte ';
        if ($this->pu_ht != 0 && $this->pu_ht != '')
            $sql .= ',pu_ht, total_ht, total_tva, total_ttc ';
        if ($this->fk_commandedet > 0)
            $sql .= ',fk_commandedet ';
        if ($this->fk_contratdet > 0)
            $sql .= ',fk_contratdet ';
        $sql.= ')';
        $sql.= " VALUES (" . $this->fk_demandeInterv . ",";
        $sql.= " '" . addslashes($this->desc) . "',";
        $sql.= " " . $this->datei . ",";
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
        dol_syslog("demandeIntervLigne::insert sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            $result = $this->update_total();
            if ($result > 0) {
                $this->rang = $rangToUse;
                $this->db->commit();
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('DEMANDEINTERV_INSERTLINE', $this, $user, $langs, $conf);
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
            dol_syslog("demandeIntervLigne::insert Error " . $this->error);
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
        $total_ttc = 1.196 * $this->total_ht;
        $total_tva = 0.196 * $this->total_ht;


        // Mise a jour ligne en base
        $sql = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_demandeIntervdet SET";
        $sql.= " description='" . addslashes($this->desc) . "'";
        $sql.= ",date=" . $this->db->idate($this->datei);
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

        dol_syslog("demandeIntervLigne::update sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            $result = $this->update_total();
            if ($result > 0) {
                $this->db->commit();
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('DEMANDEINTERV_UPDATELINE', $this, $user, $langs, $conf);
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
            dol_syslog("demandeIntervLigne::update Error " . $this->error);
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *      \brief                   Mise a jour duree total dans table ".MAIN_DB_PREFIX."demandeInterv
     *      \return        int        <0 si ko, >0 si ok
     */
    function update_total() {
        global $user, $langs, $conf;
//        $sql = "SELECT SUM(duree) as total_duration";
//        $sql.= " FROM ".MAIN_DB_PREFIX."Synopsis_demandeIntervdet";
//        $sql.= " WHERE fk_demandeInterv=".$this->fk_demandeInterv;
        $result = 1;
        $requete = "SELECT sum(total_ht) as sht, sum(total_tva) as stva, sum(total_ttc) as sttc, sum(duree) as sdur FROM " . MAIN_DB_PREFIX . "Synopsis_demandeIntervdet WHERE fk_demandeInterv = " . $this->fk_demandeInterv;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_demandeInterv SET total_ht = " . $res->sht . ", total_tva = " . $res->stva . " , total_ttc = " . $res->sttc . ", duree = " . $res->sdur . " WHERE rowid = " . $this->fk_demandeInterv;
        $sql = $this->db->query($requete);
        if ($sql) {
            $result = 1;
        }
        $this->db->commit();

        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface = new Interfaces($this->db);
        $result = $interface->run_triggers('DEMANDEINTERV_UPDATETOTAL', $this, $user, $langs, $conf);
        if ($result < 0) {
            $error++;
            $this->errors = $interface->errors;
        } else {
            $result = 1;
        }
        // Fin appel triggers
        return($result);
//        dol_syslog("demandeIntervLigne::update_total sql=".$sql);
//        $resql=$this->db->query($sql);
//        if ($resql)
//        {
//            $obj=$this->db->fetch_object($resql);
//            $total_duration=0;
//            if ($obj) $total_duration = $obj->total_duration;
//
//            $sql = "UPDATE ".MAIN_DB_PREFIX."Synopsis_demandeInterv";
//            $sql.= " SET duree = ".$total_duration;
//            $sql.= " WHERE rowid = ".$this->fk_demandeInterv;
//
//            dol_syslog("demandeIntervLigne::update_total sql=".$sql);
//            $resql=$this->db->query($sql);
//            if ($resql)
//            {
//                // Appel des triggers
//                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
//                $interface=new Interfaces($this->db);
//                $result=$interface->run_triggers('DEMANDEINTERV_UPDATETOTAL',$this,$user,$langs,$conf);
//                if ($result < 0) { $error++; $this->errors=$interface->errors; }
//                // Fin appel triggers
//                $this->db->commit();
//                return 1;
//            }
//            else
//            {
//                $this->error=$this->db->error();
//                dol_syslog("demandeIntervLigne::update_total Error ".$this->error);
//                $this->db->rollback();
//                return -2;
//            }
//        }
//        else
//        {
//            $this->error=$this->db->error();
//            dol_syslog("demandeIntervLigne::update Error ".$this->error);
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
            dol_syslog("demandeIntervLigne::delete_line lineid=" . $this->rowid);
            $this->db->begin();

            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_demandeIntervdet WHERE rowid = " . $this->rowid;
            $resql = $this->db->query($sql);
            dol_syslog("demandeIntervLigne::delete_line sql=" . $sql);

            if ($resql) {
                $result = $this->update_total();
                if ($result > 0) {
                    $this->db->commit();
                    //APpel Trigger
                    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                    $interface = new Interfaces($this->db);
                    $result = $interface->run_triggers('DEMANDEINTERV_DELETELINE', $this, $user, $langs, $conf);
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
                dol_syslog("demandeIntervLigne::delete_line Error " . $this->error);
                $this->db->rollback();
                return -1;
            }
        } else {
            return -2;
        }
    }

}

?>