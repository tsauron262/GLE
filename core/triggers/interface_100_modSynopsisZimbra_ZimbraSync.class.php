<?php

/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
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
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.synopsis-erp.com
 *
 */
/*
 *
 * $Id: interface_modZimbra_ZimbraSync.class.php,v 1.1 2008/01/06 20:33:49 hregis Exp $
 */

/**
  \file       htdocs/includes/triggers/interface_modZimbra_ZimbraSync.class.php
  \ingroup    Zimbra
  \brief      Fichier de gestion des triggers Zimbra
 */

/**
  \class      InterfaceZimbraSync
  \brief      Classe des fonctions triggers des actions Zimbra
 */
class InterfaceZimbraSync {

    public $db;
    public $error;
    public $date;
    public $duree;
    public $texte;
    public $desc;

    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'acces base
     */
    function InterfaceZimbraSync($DB) {
        $this->db = $DB;

        $this->name = "ZimbraSync";
        $this->family = "OldGleModule";
        $this->description = "Les triggers de ce composant permettent d'ins&eacute;rer un &eacute;v&egrave;nement dans le calendrier Zimbra pour chaque &eacute;v&egrave;nement Dolibarr.";
        $this->version = '0.1';                        // 'experimental' or 'dolibarr' or version
    }

    /**
     *   \brief      Renvoi nom du lot de triggers
     *   \return     string      Nom du lot de triggers
     */
    function getName() {
        return $this->name;
    }

    /**
     *   \brief      Renvoi descriptif du lot de triggers
     *   \return     string      Descriptif du lot de triggers
     */
    function getDesc() {
        return $this->description;
    }

    /**
     *   \brief      Renvoi version du lot de triggers
     *   \return     string      Version du lot de triggers
     */
    function getVersion() {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'experimental')
            return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr')
            return GLE_VERSION;
        elseif ($this->version)
            return $this->version;
        else
            return $langs->trans("Unknown");
    }

    /**
     *      \brief      Fonction appelee lors du declenchement d'un evenement Dolibarr.
     *                  D'autres fonctions run_trigger peuvent etre presentes dans includes/triggers
     *      \param      action      Code de l'evenement
     *      \param      object      Objet concerne
     *      \param      user        Objet user
     *      \param      lang        Objet lang
     *      \param      conf        Objet conf
     *      \return     int         <0 si ko, 0 si aucune action faite, >0 si ok
     */
    function run_trigger($action, $object, $user, $langs, $conf) {

        //init nécéssaire à l'activation et à la descativaton de zimbra
        //dans init, faire un populate du calendar
        // Mettre ici le code e executer en reaction de l'action

        require_once(DOL_DOCUMENT_ROOT . '/Synopsis_Zimbra/ZimbraSoap.class.php');
        $zimuser = $conf->global->ZIMBRA_ADMINUSER;
//        if ($conf->global->ZIMBRA_ZIMBRA_USE_LDAP=="true")
//        {
//            $zimuser=$user->login;
//        } else {
//            $user->getZimbraCred($user->id);
//            $zimuser=$user->ZimbraLogin;
//        }
        $zim = new Zimbra($zimuser);
//        $zim->debug=true;
        if (!preg_match('/^USER/i', $action)) {
            $zim->connect();
        }
        if (!$langs)
            global $langs;
        $langs->load("synopsisGene@Synopsis_Tools");
        // Actions
//        if ($action == 'COMPANY_CREATE')
//        {
//            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
//            $langs->load("synopsisGene@Synopsis_Tools");
//
////            file_put_contents("/tmp/somefile","created soc".$objet->nom);
//            //on cree le calendrier  on ajoute la date e création de la société
////            $zim->Babel
//
//
//        }
        $db = $this->db;
        $zim->db = $this->db;
//        var_dump($user);
        if ($zim->connected() || preg_match('/^USER/i', $action))
            switch ($action) {
                case 'ACTION_CREATE':
                    $this->ActionAction($object, $zim, $user);
                    break;
                case 'ACTION_DELETE':
                    //on efface l'action du cal
                    $this->deleteElement($object, 'llx_actioncomm', $zim);
                    break;
                case 'ACTION_UPDATE':
                    $this->deleteElement($object, 'llx_actioncomm', $zim);
                    $this->ActionAction($object, $zim, $user);
                    //on efface l'action du cal
                    break;
                case 'AFFAIRE_NEW':
                    $this->AffaireAction($object, $zim, $user);
                    break;
                case 'BILL_CANCEL':
                    $this->deleteElement($object, "llx_facture", $zim);
                    $this->BillActionCancel($object, $zim);
                    break;
                case 'BILL_UNPAYED':
                    //reouvert
                    $this->deleteElement($object, "llx_facture", $zim);
                    $this->BillAction($object, $zim);
                    break;
                case 'BILL_CREATE':
                    $this->BillAction($object, $zim);
                    break;
                case 'BILL_DELETE':
                    $this->deleteElement($object, "llx_facture", $zim);
                    break;
                case 'BILL_PAYED':
                    //ajout 'un ev => facture payé
                    //Partial ou total ?
                    $facid = $object->id;
                    require_once(DOL_DOCUMENT_ROOT . '/facture.class.php');
                    $factTmp = new Facture($this->db);
                    $factTmp->fetch($facid);
                    $this->deleteElement($factTmp, "llx_facture", $zim);
                    if ($factTmp->paye == 1) {
                        //payement total
                        $this->BillAction($factTmp, $zim, 2);
                    } else {
                        //payement partiel
                        $this->BillAction($factTmp, $zim, 1);
                    }

                    break;
                case 'BILL_SENTBYMAIL':
                    $this->facture_sendAction($object, $zim);
                    break;
                case 'BILL_MODIFY':
                case 'BILL_VALIDATE':
                    $this->deleteElement($object, "llx_facture", $zim);
                    $this->BillAction($object, $zim);
                    break;
                case 'BILL_SUPPLIER_VALIDATE':
                    $this->deleteElement($object, "llx_facture_fourn", $zim);
                    $this->BillSupAction($object, $zim);
                    break;
                case 'COMPANY_CREATE':
                    //1 create Vcard ok
                    //2 create Calendar et subcalendar ok
                    //3 ajout dans le calendrier des utilisateurs concernés
                    //4 ajoute dans les droits
                    $requete = "SELECT folder_uid, " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id as ttid
                                 FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='contact'
                                  AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                                  AND skeleton_part =1
                                  AND folder_name LIKE 'Soci%t%s'
                                  AND folder_parent = ( SELECT folder_uid FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                         WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='contact'
                                                          AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                                                          AND folder_name  = 'Contacts - GLE')";

                    if ($resql = $db->query($requete)) {
                        $resTmp = $db->fetch_object($resql);
                        $where = $resTmp->folder_uid;
                        $typeId = $resTmp->ttid;
                        $socArr = array();
                        $socArr['l'] = $where;
                        $confPrefCat = "work";
                        $confJabberPrefix = "other://";
                        $socArr['contactDet']["fileAs"] = 3;
                        if ("x" . $object->address != "x") {
                            $socArr['contactDet'][$confPrefCat . "Street"] = iconv("ISO-8859-1", "UTF-8", $object->addresss);
                        }
                        if ("x" . $object->cp != "x") {
                            $socArr['contactDet'][$confPrefCat . "PostalCode"] = iconv("ISO-8859-1", "UTF-8", $object->cp);
                        }
                        if ("x" . $object->ville != "x") {
                            $socArr['contactDet'][$confPrefCat . "City"] = iconv("ISO-8859-1", "UTF-8", $object->ville);
                        }
                        if ("x" . $object->pays != "x" && $object->pays_id > 0) {
                            $socArr['contactDet'][$confPrefCat . "Country"] = iconv("ISO-8859-1", "UTF-8", $object->pays);
                        }
                        if ("x" . $object->email != "x") {
                            $socArr['contactDet']["email"] = iconv("ISO-8859-1", "UTF-8", $object->email);
                        }
                        if ("x" . $object->note != "x") {
                            $socArr['contactDet']["notes"] = iconv("ISO-8859-1", "UTF-8", $object->note);
                        }
                        if ("x" . $object->phone_pro != "x") {
                            $socArr['contactDet']["workPhone"] = iconv("ISO-8859-1", "UTF-8", $object->tel);
                            $socArr['contactDet']['companyPhone'] = iconv("ISO-8859-1", "UTF-8", $object->tel);
                        }
                        if ("x" . $object->fax != "x") {
                            $socArr['contactDet'][$confPrefCat . "Fax"] = iconv("ISO-8859-1", "UTF-8", $object->fax);
                        }
                        if ("x" . $object->nom != "x") {
                            $socArr['contactDet']["company"] = iconv("ISO-8859-1", "UTF-8", $object->nom);
                            $socArr['contactDet']["fullName"] = iconv("ISO-8859-1", "UTF-8", $object->nom);
                            $socArr['contactDet']["lastName"] = iconv("ISO-8859-1", "UTF-8", $object->nom);
                        }
                        $ret = $zim->createContBabel($socArr);
                        $zimId = $ret["id"];
                        $arr = array();
                        $arr['l'] = $where;
                        $arr['cat'] = "llx_societe";
                        $arr['obj'] = $object;
                        $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);
                    }
                    //calendar
                    $arrAlpha = array(0 => 'abc', 1 => 'def', 2 => 'ghi', 3 => 'jkl', 4 => 'mno', 5 => 'pqrs', 6 => 'tuv', 7 => 'wxyz', 8 => 'autres');
                    $where = "";
                    $firstLetter = $object->nom;
                    $firstLetterA = $firstLetter[0];
                    $firstLetterIn = $arrAlpha[8];
                    for ($i = 0; $i < 8; $i++) {
                        if (preg_match("/" . $firstLetterA . "/i", $arrAlpha[$i])) {
                            $firstLetterIn = $arrAlpha[$i];
                        }
                    }
                    //Trouve le numéro de rep
                    $requete = "SELECT folder_uid
                             FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                            WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='appointment'
                              AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                              AND skeleton_part =1
                              AND folder_name = '" . $firstLetterIn . "'
                              AND folder_parent = ( SELECT folder_uid FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                     WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='appointment'
                                                      AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                                                      AND folder_name  LIKE 'Soci%t%s')";
                    $arr4ndFold = array();
                    if ($resql = $db->query($requete)) {
                        $where = $db->fetch_object($resql)->folder_uid;
                        $createArray = array('view' => 'appointment',
                            "name" => iconv("ISO-8859-1", "UTF-8", $object->nom) . "-" . $object->id,
                            "where" => $where);
                        //$zim->debug=1;
                        $ret = $zim->BabelCreateFolder($createArray);
                        $arr4ndFold["appointment"][] = $ret;
                        //fill SQL table
                        $zim->BabelInsertTriggerFolder($ret['id'], $ret['name'], $ret['parent'], "appointment", 2);
                        //create SubFolder =>
                        $zim->subFolderGLE = array();
                        $zim->Babel_createGLESubFolder($ret['id'], "soc");
                        foreach ($zim->subFolderGLE as $key => $val) {
                            $zim->BabelInsertTriggerFolder($val['id'], $val['name'], $val['parent'], "appointment", 2);
                        }
                        //exit(0);
                    }
                    //TODO Add création dans le cal de l'utilisateur

                    break;
                case 'COMPANY_DELETE':
                    //  On choope l'id du calendrier dans SQL => puis on efface. On efface la fiche de contact et la fiche des contacts e la société si any
                    $requete = "SELECT folder_uid FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder WHERE folder_name = '" . $res->socname . '-' . $res->socid . "' ";
                    if ($resql = $this->db->query($requete)) {
                        $pId = $this->db->fetch_object($resql)->folder_ui;
                        $zim->BabelDeleteFolder($pId);
                        //on cherche l'id du dossier et de ses sous dossiers pour effacer le contenu
                        $requete = "SELECT id FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder WHERE folder_parent =" . $res->folder_uid;
                        if ($resql1 = $this->db->query($requete)) {
                            while ($res1 = $this->db->fetch_object($resql1)) {
                                $requeteDel = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger WHERE event_folder = " . $res1->folder_uid;
                                $db->query($requeteDel);
                            }
                        }
                        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder WHERE  folder_parent =" . $res1->folder_uid . " OR folder_uid = " . $res->folder_uid;
                        $db->query($requete);
                    }
                    $this->deleteElement($object, 'llx_societe', $zim, "contact");
                    //TODO Add création dans le cal de l'utilisateur
                    break;
                case 'COMPANY_MODIFY':
                    //On choope l'id du calendrier dans SQL => puis on modify si nécéssaire (name qui change ??? ).
                    //on rename le folder de la société
                    $requete = "SELECT folder_uid
                              FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                             WHERE folder_name RLIKE '-" . $object->id . "$' ";
                    if ($resql1 = $this->db->query($requete)) {
                        $pId = $this->db->fetch_object($resql1)->folder_uid;
                        $zim->BabelRenameFolder($pId, $object->nom);
                        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder SET folder_name = '" . $object->nom . "' WHERE folder_uid = " . $pId;
                    }

                    //On efface la fiche de contact et la fiche des contacts e la société si any puis on les recrée
                    $this->deleteElement($object, 'llx_societe', $zim, "contact");
                    $requete = "SELECT folder_uid, " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id as ttid
                                 FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='contact'
                                  AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                                  AND skeleton_part =1
                                  AND folder_name LIKE 'Soci%t%s'
                                  AND folder_parent = ( SELECT folder_uid FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                         WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='contact'
                                                          AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                                                          AND folder_name  = 'Contacts - GLE')";
                    if ($resql = $db->query($requete)) {
                        $resTmp = $db->fetch_object($resql);
                        $where = $resTmp->folder_uid;
                        $typeId = $resTmp->ttid;
                        $socArr = array();
                        $socArr['l'] = $where;
                        $confPrefCat = "work";
                        $confJabberPrefix = "other://";
                        $socArr['contactDet']["fileAs"] = 3;
                        if ("x" . $object->address != "x") {
                            $socArr['contactDet'][$confPrefCat . "Street"] = iconv("ISO-8859-1", "UTF-8", $object->addresss);
                        }
                        if ("x" . $object->cp != "x") {
                            $socArr['contactDet'][$confPrefCat . "PostalCode"] = iconv("ISO-8859-1", "UTF-8", $object->cp);
                        }
                        if ("x" . $object->ville != "x") {
                            $socArr['contactDet'][$confPrefCat . "City"] = iconv("ISO-8859-1", "UTF-8", $object->ville);
                        }
                        if ("x" . $object->pays != "x" && $object->pays_id > 0) {
                            $socArr['contactDet'][$confPrefCat . "Country"] = iconv("ISO-8859-1", "UTF-8", $object->pays);
                        }
                        if ("x" . $object->email != "x") {
                            $socArr['contactDet']["email"] = iconv("ISO-8859-1", "UTF-8", $object->email);
                        }
                        if ("x" . $object->note != "x") {
                            $socArr['contactDet']["notes"] = iconv("ISO-8859-1", "UTF-8", $object->note);
                        }
                        if ("x" . $object->phone_pro != "x") {
                            $socArr['contactDet']["workPhone"] = iconv("ISO-8859-1", "UTF-8", $object->tel);
                            $socArr['contactDet']['companyPhone'] = iconv("ISO-8859-1", "UTF-8", $object->tel);
                        }
                        if ("x" . $object->fax != "x") {
                            $socArr['contactDet'][$confPrefCat . "Fax"] = iconv("ISO-8859-1", "UTF-8", $object->fax);
                        }
                        if ("x" . $object->nom != "x") {
                            $socArr['contactDet']["company"] = iconv("ISO-8859-1", "UTF-8", $object->nom);
                            $socArr['contactDet']["fullName"] = iconv("ISO-8859-1", "UTF-8", $object->nom);
                            $socArr['contactDet']["lastName"] = iconv("ISO-8859-1", "UTF-8", $object->nom);
                        }
                        $ret = $zim->createContBabel($socArr);
                        $zimId = $ret["id"];
                        $arr = array();
                        $arr['l'] = $where;
                        $arr['cat'] = "llx_societe";
                        $arr['obj'] = $object;
                        $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);
                    }
                    //TODO Add modification dans le cal de l'utilisateur a checker

                    break;
                case 'CONTACT_MODIFY':
                    //on efface dans zimbra et SQL et on recré
                    $this->deleteElement($object, 'llx_socpeople', $zim, "contact");
                //TODO del cont dans le cal de l'utilisateur a checker
                case 'CONTACT_CREATE':
                    //on ajoute ans zimbra et SQL
                    $where = "";
                    //Fiche contact:
                    $socArr = array();
                    //Trouve le numéro de rep
                    $requete = "SELECT folder_uid, " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id as ttid
                             FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                            WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='contact'
                              AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                              AND skeleton_part =1
                              AND folder_name = 'Personnes'
                              AND folder_parent = ( SELECT folder_uid FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                     WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='contact'
                                                      AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                                                      AND folder_name  LIKE 'Contacts - GLE')";
                    if ($resql = $db->query($requete)) {
                        $cont = new Contact($db);
                        $cont->fetch($object->id);
                        $resTmp = $db->fetch_object($resql);
                        $where = $resTmp->folder_uid;
                        $typeId = $resTmp->ttid;
                        $ret = $zim->connect();
                        //Get Cotact Folder
                        $contArr = array();
                        $contArr['l'] = $where;
                        $confPrefCat = "work";
                        $confJabberPrefix = "other://";
//                        var_dump($object);
                        if ("x" . $cont->nom != "x") {
                            $contArr['contactDet']["lastName"] = iconv("ISO-8859-1", "UTF-8", $cont->nom);
                        }
                        if ("x" . $cont->prenom != "x") {
                            $contArr['contactDet']["firstName"] = iconv("ISO-8859-1", "UTF-8", $cont->prenom);
                        }
                        if ("x" . $cont->address != "x") {
                            $contArr['contactDet'][$confPrefCat . "Street"] = iconv("ISO-8859-1", "UTF-8", $cont->address);
                        }
                        if ("x" . $cont->cp != "x") {
                            $contArr['contactDet'][$confPrefCat . "PostalCode"] = iconv("ISO-8859-1", "UTF-8", $cont->cp);
                        }
                        if ("x" . $cont->ville != "x") {
                            $contArr['contactDet'][$confPrefCat . "City"] = iconv("ISO-8859-1", "UTF-8", $cont->ville);
                        }
                        if ("x" . $cont->pays != "x" && $cont->fk_pays > 0) {
                            $contArr['contactDet'][$confPrefCat . "Country"] = iconv("ISO-8859-1", "UTF-8", $cont->pays);
                        }
                        if ("x" . $cont->poste != "x") {
                            $contArr['contactDet']["jobTitle"] = iconv("ISO-8859-1", "UTF-8", $cont->poste);
                        }
                        if ("x" . $cont->email != "x") {
                            $contArr['contactDet']["email"] = iconv("ISO-8859-1", "UTF-8", $cont->email);
                        }
                        if ("x" . $cont->birthday_mysql != "x") {
                            $contArr['contactDet']["birthday"] = iconv("ISO-8859-1", "UTF-8", $cont->birthday_mysql);
                        }
                        if ("x" . $cont->jabberid != "x") {
                            $contArr['contactDet']["imAddress1"] = iconv("ISO-8859-1", "UTF-8", $confJabberPrefix . $cont->jabberid);
                        }
                        if ("x" . $cont->note != "x") {
                            $contArr['contactDet']["notes"] = iconv("ISO-8859-1", "UTF-8", $cont->note);
                        }
                        if ("x" . $cont->phone_pro != "x") {
                            $contArr['contactDet']["workPhone"] = iconv("ISO-8859-1", "UTF-8", $cont->phone_pro);
                        }
                        if ("x" . $cont->fax != "x") {
                            $contArr['contactDet'][$confPrefCat . "Fax"] = iconv("ISO-8859-1", "UTF-8", $cont->fax);
                        }
                        if ("x" . $cont->phone_perso != "x") {
                            $contArr['contactDet']["homePhone"] = iconv("ISO-8859-1", "UTF-8", $cont->phone_perso);
                        }
                        if ("x" . $cont->phone_mobile != "x") {
                            $contArr['contactDet']["mobilePhone"] = iconv("ISO-8859-1", "UTF-8", $cont->phone_mobile);
                        }
                        if ("x" . $cont->socname != "x") {
                            $contArr['contactDet']["company"] = iconv("ISO-8859-1", "UTF-8", $cont->socname);
                        }
                        //get Company phone if exist
                        $requeteTel = "SELECT llx_societe.tel
                                      FROM llx_societe
                                     WHERE rowid = " . $cont->fk_soc;
                        if ($resqlTel = $db->query($requeteTel)) {
                            $resTel = $db->fetch_object($resqlTel);
                            if ($resTel->tel . "x" != "x") {
                                $contArr['contactDet']['companyPhone'] = $resTel->tel;
                            }
                        }
                        //$zim->debug=true;
                        $ret = $zim->createContBabel($contArr);
                        $zimId = $ret["id"];
                        $arr = array();
                        $arr['l'] = $where;
                        $arr['cat'] = "llx_socpeople";
                        $arr['obj'] = $cont;
                        $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);
                    }
//debug
//                    exit();
                    //TODO add cont dans le cal de l'utilisateur a checker
                    break;
                case 'CONTACT_DELETE':
                    //on efface dans zimbra et SQL
                    $this->deleteElement($object, 'llx_socpeople', $zim, "contact");
                    //TODO del cont dans le cal de l'utilisateur a checker
                    break;
                case 'CONTRAT_LIGNE_MODIFY':
                case 'CONTRACT_SERVICE_ACTIVATE':
                    $this->deleteElement($object, 'llx_contratdet', $zim);
                    $this->deleteElement($object, 'llx_contrat', $zim);
                    $this->ContratAction($object, $zim);
                    break;
                case 'CONTRACT_SERVICE_CLOSE':
                    $this->deleteElement($object, 'llx_contratdet', $zim);
                    $this->deleteElement($object, 'llx_contrat', $zim);
                    $this->ContratAction($object, $zim);
                    break;
                case 'CONTRACT_CANCEL':
                    $this->deleteElement($object, 'llx_contrat', $zim);
                    $this->deleteElement($object, 'llx_contratdet', $zim);
                    $this->ContratAction($object, $zim);
                    break;
                case 'CONTRACT_CLOSE':
                    $this->deleteElement($object, 'llx_contrat', $zim);
                    $this->deleteElement($object, 'llx_contratdet', $zim);
                    $this->ContratAction($object, $zim);
                    break;
                case 'CONTRACT_CREATE':
                    $this->ContratAction($object, $zim);
                    break;
                case 'CONTRACT_DELETE':
                    $this->deleteElement($object, 'llx_contrat', $zim);
                    $this->deleteElement($object, 'llx_contratdet', $zim);
                    $this->ContratAction($object, $zim);
                    break;
                case 'CONTRACT_VALIDATE':
                    $this->deleteElement($object, 'llx_contrat', $zim);
                    $this->deleteElement($object, 'llx_contratdet', $zim);
                    $this->ContratAction($object, $zim);
                    break;

                case 'ORDER_CREATE':
                    $this->OrderAction($object, $zim);
                    break;
                case 'ORDER_DELETE':
                    //on efface dans le bon calendrier
                    $this->deleteElement($object, 'llx_commande', $zim);
                    break;
                case 'ORDER_SENTBYMAIL':
                    // on ajoute une action com dans le calendrier
                    $this->commande_sendAction($object, $zim);
                    break;
                case 'ORDER_VALIDATE':
                    // on ajoute une validation de commande dans le calendrier
                    //=> faire requete delete appointment by id
                    $this->deleteElement($object, 'llx_commande', $zim);
                    $this->OrderAction($object, $zim);
                    break;
                case 'ORDER_SUPPLIER_CREATE':
                    // on ajoute une commande fournisseur dans le calendrier
                    $this->deleteElement($object, 'llx_commande_fournisseur', $zim);
                    $this->OrderSupAction($object, $zim);
                    break;
                case 'ORDER_SUPPLIER_VALIDATE':
                    $this->deleteElement($object, 'llx_commande_fournisseur', $zim);
                    $this->OrderSupAction($object, $zim);
                    // on ajoute une validation de commande fournisseur dans le calendrier
                    break;

                case 'PAYMENT_CUSTOMER_CREATE':
                    // on ajoute unun payement dans le calendrier facture
                    $this->PaymentAction($object, $zim);

                    break;
                case 'PAYMENT_SUPPLIER_CREATE':
                    // on ajoute unun payement dans le calendrier facture fournisseur
                    $this->PaymentSupAction($object, $zim);
                    break;
                case 'PRODUCT_CREATE':
                    break;
                case 'PRODUCT_DELETE':
                    break;
                case 'PRODUCT_MODIFY':
                    break;
                case 'PROPAL_CLOSE_REFUSED':
                    // on ajoute unun evenement dans le calendrier propal
                    if ($zim->connected()) {
                        $this->deleteElement($object, 'llx_propal', $zim);
                        $this->PropalAction($object, $zim);
                    }
                    break;
                case 'PROPAL_CLOSE_SIGNED':
                    // on ajoute unun evenement dans le calendrier propal
                    if ($zim->connected()) {
                        $this->deleteElement($object, 'llx_propal', $zim);
                        $this->PropalAction($object, $zim);
                    }
                    break;
                case 'PROPAL_DELETE':
                    if ($zim->connected())
                        $this->deleteElement($object, 'llx_propal', $zim);
                    break;
                case 'PROPAL_CREATE':
                    // on ajoute unun evenement dans le calendrier propal
                    if ($zim->connected())
                        $this->PropalAction($object, $zim);
                    break;
                case 'PROPAL_MODIFY':
                    // on modifie l'evenement dans le calendrier si nécéssaire
                    if ($zim->connected()) {
                        $this->deleteElement($object, 'llx_propal', $zim);
                        $this->PropalAction($object, $zim);
                    }
                    break;
                case 'PROPAL_SENTBYMAIL':
                    if ($zim->connected())
                        $this->propal_sendAction($object, $zim);
                    break;
                case 'PROPAL_VALIDATE':
                    // on ajoute l'evenement dans le calendrier propal
                    // on modifie l'evenement dans le calendrier si nécéssaire
                    if ($zim->connected()) {
                        $this->deleteElement($object, 'llx_propal', $zim);
                        $this->PropalAction($object, $zim);
                    }
                    break;
//nouveau trigger
                case 'EXPEDITION_CREATE':
                    if ($zim->connected())
                        $this->ExpedAction($object, $zim);
                    break;
                case 'EXPEDITION_DELETE':
                    if ($zim->connected())
                        $this->deleteElement($object, 'llx_expedition', $zim);
                    break;
                case 'EXPEDITION_VALIDATE':
                    if ($zim->connected()) {
                        $this->deleteElement($object, 'llx_expedition', $zim);
                        $this->ExpedAction($object, $zim);
                    }
                    break;
                case 'LIVRAISON_CREATE':
                    if ($zim->connected()) {
                        $this->LivraisonAction($object, $zim);
                    }
                    break;
                case 'EXPEDITION_VALID_FROM_DELIVERY':
                case 'EXPEDITION_CREATE_FROM_DELIVERY':
                    if ($zim->connected()) {
                        $this->deleteElement($object, 'llx_livraison', $zim);
                        $this->LivraisonAction($object, $zim);
                    }
                    break;
                case 'LIVRAISON_VALID':
                    if ($zim->connected()) {
                        $this->deleteElement($object, 'llx_livraison', $zim);
                        $this->LivraisonAction($object, $zim);
                    }
                    break;
                case 'LIVRAISON_DELETE':
                    if ($zim->connected()) {
                        $this->deleteElement($object, 'llx_livraison', $zim);
                    }
                    break;
                case 'PROJECT_CREATE':
                    //Ajoute un calendrier par référence
                    //Ajoute l'ev dans le cal de la societe
                    break;
                case 'PROJECT_UPDATE':
                    //Modifie le calendrier dans soc et dans projet
                    break;
                case 'PROJECT_DELETE':
                    //efface le calendrier dans soc et dans projet
                    break;
                case 'PROJECT_CREATE_TASK_ACTORS':
                    //ajoute dan le cal de l'utilisateur
                    break;
                case 'PROJECT_CREATE_TASK':
                    //ajoute dan le cal de l'utilisateur et du projet
                    break;
                case 'PROJECT_CREATE_TASK_TIME':
                    //ajoute dan le cal de l'utilisateur et du projet et de la societe
                    break;
//new
                case 'PROJECT_CREATE_TASK_TIME_EFFECTIVE':
                    //ajoute dan le cal de l'utilisateur et du projet et de la societe
                    break;
                case 'PROJECT_UPDATE_TASK':
                    //Maj dans le cal de l'utilisateur et du projet et de la societe
                    break;
//new
                case 'PROJECT_DEL_TASK':
                    //Maj dans le cal de l'utilisateur et du projet et de la societe
                    break;
                case 'FICHEINTER_VALIDATE':
                    $this->deleteElement($object, 'llx_fichinter', $zim);
                    $this->IntervAction($object, $zim);
                    break;
                case 'FICHEINTER_CREATE':
                    $this->IntervAction($object, $zim);
                    break;
                case 'FICHEINTER_UPDATE':
                    $this->deleteElement($object, 'llx_fichinter', $zim);
                    $this->IntervAction($object, $zim);
                    break;
                case 'FICHEINTER_DELETE':
                    $this->deleteElement($object, 'llx_fichinter', $zim);
                    break;
                case 'DEMANDEINTERV_CREATE':
                    $this->DIAction($object, $zim);
                    break;
                case 'DEMANDEINTERV_UPDATE':
                    $this->deleteElement($object, 'llx_Synopsis_demandeInterv', $zim);
                    $this->DIAction($object, $zim);
                    break;
                case 'DEMANDEINTERV_VALIDATE':
                    $this->deleteElement($object, 'llx_Synopsis_demandeInterv', $zim);
                    $this->DIAction($object, $zim);
                    break;
                case 'DEMANDEINTERV_PRISENCHARGE':
                    $this->deleteElement($object, 'llx_Synopsis_demandeInterv', $zim);
                    $this->DIAction($object, $zim);
                    break;
                case 'DEMANDEINTERV_CLOTURE':
                    $this->deleteElement($object, 'llx_Synopsis_demandeInterv', $zim);
                    $this->DIAction($object, $zim);
                    break;
                case 'DEMANDEINTERV_DELETE':
                    $this->deleteElement($object, 'llx_Synopsis_demandeInterv', $zim);
                    break;
                case 'DEMANDEINTERV_SETDELIVERY':
                    $this->deleteElement($object, 'llx_Synopsis_demandeInterv', $zim);
                    $this->DIAction($object, $zim);
                    break;
                //new
                case 'CAMPAGNEPROSPECT_CREATE':
                    //ajoute un cal dans le repertoire campagne de zimbra
                    //ajoute un cal dans le cal des utilisateurs
                    break;
                case 'CAMPAGNEPROSPECT_UPDATE':
                    //modifie le cal dans le repertoire campagne de zimbra
                    //modifie le cal dans le cal des utilisateurs
                    break;
                case 'CAMPAGNEPROSPECT_VALIDATE':
                    //modifie le cal dans le repertoire campagne de zimbra
                    //modifie le cal dans le cal des utilisateurs
                    break;
                case 'CAMPAGNEPROSPECT_LANCER':
                    //modifie le cal dans le repertoire campagne de zimbra
                    //modifie le cal dans le cal des utilisateurs
                    break;
                case 'CAMPAGNEPROSPECT_CLOTURE':
                    //modifie le cal dans le repertoire campagne de zimbra
                    //modifie le cal dans le cal des utilisateurs
                    break;
                case 'CAMPAGNEPROSPECT_NEWACTION': //V2
                    //ajoute ev dans le cal action de la campagne
                    //ajoute ev dans le cal de la société
                    //ajoute ev dans dans le cal des utilisateurs
                    break;
                case 'CAMPAGNEPROSPECT_CLOSE':
                    //ajoute ev dans le cal action de la campagne
                    //ajoute ev dans le cal de la société
                    //ajoute ev dans dans le cal des utilisateurs
                    break;
                case 'CAMPAGNEPROSPECT_NEWPRISECHARGE':
                    //ajoute ev dans le cal action de la campagne
                    //ajoute ev dans le cal de la société
                    //ajoute ev dans dans le cal des utilisateurs
                    break;

                /*                 * *** Admin ****** */
                case 'USER_CREATE':

                    $zimuser = "";
                    $zimpass = "";
                    $zimuser1 = "";
                    $zimpass1 = "";
                    $object->id = $object->id;
                    $object->fetch();
                    $userAdminZim = $conf->global->ZIMBRA_ADMINUSER;
                    $passAdminZim = $conf->global->ZIMBRA_ADMINPASS;
                    $zim1 = new Zimbra($userAdminZim);
                    $zim1->db = $db;
                    $ret = $zim1->connectAdmin($userAdminZim, $passAdminZim);
                    # cn            - full name, common name
                    # co            - country friendly name
                    # company       - company (company name)
                    # displayName   - name to display inressou admin tool, outlook uses as well
                    #                 (cn is multi-valued)
                    # gn            - first name (given name)
                    # initials      - middle initial
                    # l             - city (locality)
                    # ou            - organizational unit
                    # physicalDeliveryOfficeName - office
                    # street        - street address
                    # postalCode    - zip code
                    # sn            - last name (sir name)
                    # st            - state
                    # telephoneNumber - phone
                    $newAccountDet['cn'] = utf8_encode($object->fullname);
                    $newAccountDet['co'] = utf8_encode($conf->global->MAIN_INFO_SOCIETE_PAYS);
                    $newAccountDet['company'] = utf8_encode($conf->global->MAIN_INFO_SOCIETE_VILLE);
                    $newAccountDet['displayName'] = utf8_encode($object->fullname);
                    $newAccountDet['gn'] = utf8_encode($object->prenom);
                    $newAccountDet['l'] = utf8_encode($conf->global->MAIN_INFO_SOCIETE_VILLE);
                    //$newAccountDet['ou']="Organisation";
                    //$newAccountDet['physicalDeliveryOfficeName']="Office Test";
                    $newAccountDet['street'] = utf8_encode($conf->global->MAIN_INFO_SOCIETE_ADRESSE);
                    $newAccountDet['postalCode'] = $conf->global->MAIN_INFO_SOCIETE_CP;
                    $newAccountDet['sn'] = utf8_encode($object->nom);
                    $newAccountDet['st'] = "PACA";
                    $newAccountDet['telephoneNumber'] = $object->office_phone;
                    $username = $object->login;
                    $password = $object->pass;
                    $ret = $zim1->BabelCreateAccount($username, $password, $newAccountDet);

                    // on ajoute son calendrier dans zimbra et SQL
                    // crée l'utilisateur dans Zimbra
                    //            require_once('Var_Dump.php');
                    //            Var_Dump::displayInit(array('display_mode' => 'HTML4_Text'), array('mode' => 'normal','offset' => 4));
                    //            Var_Dump::Display($object);
                    /*
                     *     error                          => NULL
                      element                    => string(4) user
                      table_element              => string(4) user
                      id                         => int 8
                      ldap_sid                   => NULL
                      search_sid                 => NULL
                      fullname                   => string(21) testGLEPrenom testGLE
                      nom                        => string(7) testGLE
                      prenom                     => string(13) testGLEPrenom
                      note                       => string(10) test&nbsp;
                      email                      => string(26) testGLE@synopsis-erp.com
                      office_phone               => string(9) 123123123
                      office_fax                 => string(9) 123123789
                      user_mobile                => string(9) 123123456
                      admin                      => int 0
                      login                      => string(12) testGLELogin
                      pass                       => string(0)
                      pass_indatabase            => NULL
                      pass_indatabase_crypted    => NULL
                      datec                      => NULL
                      datem                      => NULL
                      societe_id                 => NULL
                      fk_member                  => NULL
                      webcal_login               => string(0)
                      phenix_login               => string(0)
                      phenix_pass                => NULL
                      phenix_pass_crypted        => NULL
                      datelastlogin              => NULL
                      datepreviouslogin          => NULL
                      statut                     => NULL
                      lang                       => NULL
                      userpref_limite_liste      => NULL
                      entrepots                  => NULL
                      rights                     => NULL
                      all_permissions_are_loaded => int 0
                      tab_loaded                 => array(0)
                      liste_limit                => int 0
                      clicktodial_enabled        => int 0
                     */
                    //insert into Babel_Zimbra_user => zimbra_id, user_refid
                    $id = $ret['account'][0]['id'];
                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Zimbra_li_User " .
                            "                (ZimbraLogin,ZimbraPass,ZimbraId,User_refid) " .
                            "         VALUES ('" . $username . "','" . $password . "','" . $id . "','" . $object->id . "')";
                    $db->query($requete);

                    //TODO : ajoute les repertoires utilisateurs :> Le calendrier + fiche contacte
                    //createCalendar utilisateur (avec son nom)
                    // recupere l'id du rep Utilisateurs/(alpha)
                    // cree le
                    $arrAlpha = array(0 => 'abc', 1 => 'def', 2 => 'ghi', 3 => 'jkl', 4 => 'mno', 5 => 'pqrs', 6 => 'tuv', 7 => 'wxyz', 8 => 'autres');
                    $where = "";
                    $firstLetter = $object->nom;
                    $firstLetterA = $firstLetter[0];
                    $firstLetterIn = $arrAlpha[8];
                    for ($i = 0; $i < 8; $i++) {
                        if (preg_match("/" . $firstLetterA . "/i", $arrAlpha[$i])) {
                            $firstLetterIn = $arrAlpha[$i];
                        }
                    }
                    //Trouve le numéro de rep
                    $requete = "SELECT folder_uid
                             FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                            WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='appointment'
                              AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                              AND skeleton_part =1
                              AND folder_name = '" . $firstLetterIn . "'
                              AND folder_parent = ( SELECT folder_uid FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                     WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='appointment'
                                                      AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                                                      AND folder_name  LIKE 'Utilisateurs')";
                    if ($resql = $db->query($requete)) {
                        $where = $db->fetch_object($resql)->folder_uid;
                        $createArray = array('view' => 'appointment',
                            "name" => iconv("ISO-8859-1", "UTF-8", trim($object->prenom . ' ' . $object->nom)),
                            "where" => $where);
                        //$zim->debug=1;
                        $userCalid = $zim1->BabelCreateFolder($createArray);
                        $zim1->BabelInsertTriggerFolder($userCalid['id'], $object->prenom . ' ' . $object->nom, $where, "appointment", 1);
                        $requeteUpdtUser = "UPDATE llx_Synopsis_Zimbra_li_User " .
                                "               SET calFolderZimId ='" . $userCalid['id'] . "' " .
                                "     WHERE ZimbraId = '" . $id . "'";
                        $db->query($requeteUpdtUser);
                        $zim1->Babel_createGLESubFolder($userCalid['id'], "user");
                        foreach ($zim1->subFolderGLE as $key => $val) {
                            $zim1->BabelInsertTriggerFolder($val['id'], $val['name'], $val['parent'], "appointment", 1);
                        }
                        //Create vcard utilisateur V1.1
                    }
                    break;

                case 'USER_DELETE':
                    // place l'utilisateur en mode desactived dans zimbra
                    //faire requete => chope l'id dans la base
                    $requete = "SELECT * FROM llx_Synopsis_Zimbra_li_User WHERE User_refid=" . $object->id;
                    $db->begin();
                    if ($resql = $db->query($requete)) {
                        $res = $db->fetch_object($resql);
                        $zimId = $res->ZimbraId;
                        $requete = "DELETE FROM llx_Synopsis_Zimbra_li_User WHERE User_refid=" . $object->id;
                        $delOk = $db->query($requete);
                        $userAdminZim = $conf->global->ZIMBRA_ADMINUSER;
                        $passAdminZim = $conf->global->ZIMBRA_ADMINPASS;

                        $zim1 = new Zimbra($userAdminZim);
                        $ret = $zim1->connectAdmin($userAdminZim, $passAdminZim);
                        $delOkZim = $zim1->BabelDeleteAccount($zimId);
                        if ($delOk && $delOkZim) {
                            $db->commit();
                        }
                        // on efface pas le calendrier de l'utilisateur pour conservation
                    }
                    //requete soap pour effacer selon l'id

                    break;
                case 'USER_DISABLE':
                    // place l'utilisateur en mode desactived dans zimbra
                    $requete = "SELECT * FROM llx_Synopsis_Zimbra_li_User WHERE User_refid=" . $object->id;
                    if ($resql = $db->query($requete)) {
                        if ($object->statut >= 0) {
                            $res = $db->fetch_object($resql);
                            $zimId = $res->ZimbraId;
//                                $zim=new Zimbra($object->login);
//                                $zim->connectAdmin('gle','root66');
                            $userAdminZim = $conf->global->ZIMBRA_ADMINUSER;
                            $passAdminZim = $conf->global->ZIMBRA_ADMINPASS;

                            $zim1 = new Zimbra($userAdminZim);
                            $ret = $zim1->connectAdmin($userAdminZim, $passAdminZim);

                            $newAccountDet['zimbraAccountStatus'] = "locked";
                            $ret1 = $zim1->BabelUpdateAccount($zimId, $newAccountDet);
                        }
                    }

                    break;
                case 'USER_ENABLE':
                    // place l'utilisateur en mode active dans zimbra
                    $requete = "SELECT * FROM llx_Synopsis_Zimbra_li_User WHERE User_refid=" . $object->id;
                    if ($resql = $db->query($requete)) {
                        if ($object->statut >= 0) {
                            $res = $db->fetch_object($resql);
                            $zimId = $res->ZimbraId;
                            $userAdminZim = $conf->global->ZIMBRA_ADMINUSER;
                            $passAdminZim = $conf->global->ZIMBRA_ADMINPASS;

                            $zim1 = new Zimbra($userAdminZim);
                            $ret = $zim1->connectAdmin($userAdminZim, $passAdminZim);

                            $newAccountDet['zimbraAccountStatus'] = "active";
                            $ret1 = $zim1->BabelUpdateAccount($zimId, $newAccountDet);
                        }
                    }

                    break;
                case 'USER_MODIFY':
                    // on modifie l'evenement dans le calendrier si nécéssaire et dans contact + fiche user
                    $requete = "SELECT * FROM llx_Synopsis_Zimbra_li_User WHERE User_refid=" . $object->id;
                    if ($resql = $db->query($requete)) {
                        $res = $db->fetch_object($resql);
                        $zimId = $res->ZimbraId;
                        $userAdminZim = $conf->global->ZIMBRA_ADMINUSER;
                        $passAdminZim = $conf->global->ZIMBRA_ADMINPASS;

                        $zim1 = new Zimbra($userAdminZim);
                        $ret = $zim1->connectAdmin($userAdminZim, $passAdminZim);

                        $zim1->BabelRenameAccount($zimId, $object->login);
                        $newAccountDet['cn'] = $object->fullname;
                        $newAccountDet['co'] = $conf->global->MAIN_INFO_SOCIETE_PAYS;
                        $newAccountDet['company'] = $conf->global->MAIN_INFO_SOCIETE_VILLE;
                        $newAccountDet['displayName'] = $object->fullname;
                        $newAccountDet['gn'] = $object->prenom;
                        $newAccountDet['l'] = $conf->global->MAIN_INFO_SOCIETE_VILLE;
                        $newAccountDet['street'] = $conf->global->MAIN_INFO_SOCIETE_ADRESSE;
                        $newAccountDet['postalCode'] = $conf->global->MAIN_INFO_SOCIETE_CP;
                        $newAccountDet['sn'] = $object->nom;
                        $newAccountDet['st'] = "PACA";
                        $newAccountDet['telephoneNumber'] = $object->office_phone;
                        if ($object->admin > 0) {
                            $newAccountDet['zimbraIsAdminAccount'] = "TRUE";
                            $newAccountDet['zimbraIsDomainAdminAccount'] = "TRUE";
                        }
                        $ret1 = $zim1->BabelUpdateAccount($zimId, $newAccountDet);
                        $zim1->BabelRenameFolder($res->calFolderZimId, $object->prenom . ' ' . $object->nom);
                    }

                    break;
                case 'USER_ENABLEDISABLE':
                    // si utilisateur enable => enbale sinon disabled
                    $requete = "SELECT * FROM llx_Synopsis_Zimbra_li_User WHERE User_refid=" . $object->id;
                    if ($resql = $db->query($requete)) {
                        if ($object->statut >= 0) {
                            $res = $db->fetch_object($resql);
                            $zimId = $res->ZimbraId;
                            $userAdminZim = $conf->global->ZIMBRA_ADMINUSER;
                            $passAdminZim = $conf->global->ZIMBRA_ADMINPASS;

                            $zim1 = new Zimbra($userAdminZim);
                            $ret = $zim1->connectAdmin($userAdminZim, $passAdminZim);

                            $newAccountDet['zimbraAccountStatus'] = "locked";
                            $ret1 = $zim1->BabelUpdateAccount($zimId, $newAccountDet);
                        } else {
                            $res = $db->fetch_object($resql);
                            $zimId = $res->ZimbraId;
                            $userAdminZim = $conf->global->ZIMBRA_ADMINUSER;
                            $passAdminZim = $conf->global->ZIMBRA_ADMINPASS;

                            $zim1 = new Zimbra($userAdminZim);
                            $ret = $zim1->connectAdmin($userAdminZim, $passAdminZim);

                            $newAccountDet['zimbraAccountStatus'] = "active";
                            $ret1 = $zim1->BabelUpdateAccount($zimId, $newAccountDet);
                        }
                    }
                    break;
                case 'USER_LOGIN':
                    break;
                case 'USER_LOGIN_FAILED':
                    break;
                case 'USER_CHANGERIGHT':
                    //GLE V2
                    //si droit sur propal change
                    //si droit sur commande
                    //si droit sur facture
                    //             livraison/exped
                    //             contrat
                    //             facture / commande fourn
                    //             interventions
                    //             action co
                    //             zimbra
                    //            var_dump($object);
                    break;
                case 'USER_NEW_PASSWORD':
                    // on modifie le password zimbra
                    $requete = "SELECT * FROM llx_Synopsis_Zimbra_li_User WHERE User_refid=" . $object->id;
                    if ($resql = $db->query($requete)) {
                        $res = $db->fetch_object($resql);
                        $zimId = $res->ZimbraId;
                        $userAdminZim = $conf->global->ZIMBRA_ADMINUSER;
                        $passAdminZim = $conf->global->ZIMBRA_ADMINPASS;

                        $zim1 = new Zimbra($userAdminZim);
                        $ret = $zim1->connectAdmin($userAdminZim, $passAdminZim);

                        $zim1->BabelChangePass($zimId, $object->pass);
                    }
                    break;
            }

        return 0;
    }

    function deleteElement($object, $tableSql, $zim, $type = 'appointment') {
        if ($type == 'appointment') {
            $requetePre = "SELECT *
                             FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger
                            WHERE event_table_link = '" . $tableSql . "'
                              AND event_table_id = $object->id
                              AND type_event_refid = (SELECT id
                                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                       WHERE val='appointment')";
//print $requetePre . "<br>";
            $db = $this->db;
            if ($resqlPre = $db->query($requetePre)) {
                while ($res = $db->fetch_object($resqlPre)) {
                    $deleteId = $res->event_uid;
                    //Efface dans zimbra
                    $zim->Babel_DelZimCal($deleteId);
                    //Efface dans sql
                    $requeteDel = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger
                                         WHERE event_uid = '" . $deleteId . "'";
                    $db->query($requeteDel);
                }
            }
        } else {
            $requetePre = "SELECT *
                             FROM " . MAIN_DB_PREFIX . "Synopsis__Zimbra_trigger
                            WHERE event_table_link = '" . $tableSql . "'
                              AND event_table_id = $object->id
                              AND type_event_refid = (SELECT id
                                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                       WHERE val='contact')";
            $db = $this->db;
            if ($resqlPre = $db->query($requetePre)) {
                while ($res = $db->fetch_object($resqlPre)) {
                    $deleteId = $res->event_uid;
                    //Efface dans zimbra
                    $zim->BabelDeleteContact($deleteId);
                    //Efface dans sql
                    $requeteDel = "Delete FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger WHERE event_uid = '" . $deleteId . "'";
                    $db->query($requeteDel);
                }
            }
        }
    }

    function IntervAction($object, $zim) {
        $db = $this->db;
        $requete = "SELECT llx_fichinter.rowid,
                           llx_fichinter.ref,
                           llx_fichinter.datec,
                           llx_fichinter.date_valid,
                           llx_fichinter.datei,
                           llx_fichinter.fk_user_author,
                           llx_fichinter.fk_user_valid,
                           llx_fichinter.fk_statut,
                           llx_societe.nom as socname,
                           llx_societe.rowid as socid,
                           llx_fichinter.note_public,
                           llx_fichinter.description as note
                      FROM llx_fichinter, llx_societe
                     WHERE llx_societe.rowid = llx_fichinter.fk_soc
                       AND llx_fichinter.rowid = " . $object->id;
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $zim->dolibarr_main_url_root . "/fichinter/fiche.php?id=" . $res->rowid;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Interventions'
                                         AND folder_parent =( SELECT folder_uid
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;

                        if ($res->datec) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    $res->datec, "Cr&eacute;ation de la FI " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la fiche d'intervention " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_fichinter", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                        if ($res->date_valid) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    $res->date_valid, "Validation de FI " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validation de fiche d'intervention " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_fichinter", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                        if ($res->datei) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    $res->datei, "FI " . "" . $res->ref . "" . " (" . $res->socname . ")", "Fiche d'intervention " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_fichinter", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                    }
                }
            }
            while (count($zim->ApptArray) > 0) {
                $arr = array_pop($zim->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $zim->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $zim->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
            $this->SyncUserCal($object, $zim, 'FI');
        }
    }

    function PaymentAction($object, $zim) {
        $db = $this->db;
        $paymentid = $object->id;
        $requetePre = " SELECT llx_paiement_facture.fk_facture,
                               llx_paiement.datep
                          FROM llx_paiement,
                               llx_paiement_facture
                         WHERE llx_paiement_facture.fk_paiement = llx_paiement.rowid
                           AND llx_paiement_facture.fk_paiement=  " . $paymentid;
        if ($resqlPre = $this->db->query($requetePre)) {
            $objPai = $db->fetch_object($resqlPre);
            $requete = "SELECT llx_facture.rowid,
                               llx_facture.facnumber as ref,
                               llx_facture.datec,
                               llx_facture.paye,
                               llx_facture.datef,
                               llx_facture.date_lim_reglement,
                               llx_facture.date_valid,
                               llx_facture.fk_user_author,
                               llx_facture.fk_user_valid,
                               llx_facture.fk_statut,
                               llx_societe.nom as socname,
                               llx_societe.rowid as socid,
                               llx_facture.note,
                               llx_facture.note_public
                          FROM llx_facture, llx_societe
                         WHERE llx_societe.rowid = llx_facture.fk_soc
                           AND llx_facture.rowid = " . $objPai->fk_facture;
            $resql = $db->query($requete);
            $id = 0;
            $typeId = false;
            if ($resql) {
                $res = $db->fetch_object($resql);
                $url = $zim->dolibarr_main_url_root . "/compta/paiement/fiche.php?id=" . $paymentid;
                //get Loc Zimbra
                $requeteLocZim = "SELECT folder_type_refid as ftid,
                                         folder_uid as fid
                                    FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                   WHERE folder_name='Factures'
                                     AND folder_parent =( SELECT folder_uid
                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                           WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                             AND folder_type_refid = (SELECT id
                                                                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                       WHERE val='appointment'))";
                if ($resqlLocZim = $this->db->query($requeteLocZim)) {
                    $zimRes = $db->fetch_object($resqlLocZim);
                    $zimLoc = $zimRes->fid;
                    $typeId = $zimRes->ftid;
                    $arrRes = $zim->Babel_pushDateArr(
                            $objPai->datep, "Regl de " . "" . $res->ref . "" . " (" . $res->socname . ")", "R&egrave;glement de la facture " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_paiement", 1, //all day
                            "", //loc géo
                            1, //is org
                            $zimLoc, //loc zimbra
                            $url, $soc->id, $res);
                    $id++;
                }

                while (count($zim->ApptArray) > 0) {
                    $arr = array_pop($zim->ApptArray);
                    $arr1 = $arr;
                    //extract socid
                    //Store to Db, Store to Zimbra
                    $ret = $zim->createApptBabel($arr);
                    // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
                    //                $parent = $arr['l'];
                    $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                    $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                    //faut aussi placer l'event dans le calendrier de la société
                    $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                    $arr1['l'] = $parentId;
                    $ret1 = $zim->createApptBabel($arr1);
                    $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                    $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
                }
            }
        }
    }

    function PaymentSupAction($object, $zim) {
        $db = $this->db;
        $paymentid = $object->id;
        $requetePre = " SELECT llx_paiementfourn_facturefourn.fk_facturefourn,
                               llx_paiementfourn.datep
                          FROM llx_paiementfourn,
                               llx_paiementfourn_facturefourn
                         WHERE llx_paiementfourn_facturefourn.fk_paiementfourn = llx_paiementfourn.rowid
                           AND llx_paiementfourn_facturefourn.fk_paiementfourn=  " . $paymentid;
        if ($resqlPre = $this->db->query($requetePre)) {
            $objPai = $db->fetch_object($resqlPre);
            $requete = "SELECT llx_facture_fourn.rowid,
                               llx_facture_fourn.facnumber as ref,
                               llx_facture_fourn.datec,
                               llx_facture_fourn.paye,
                               llx_facture_fourn.datef,
                               llx_facture_fourn.date_lim_reglement,
                               llx_facture_fourn.date_valid,
                               llx_facture_fourn.fk_user_author,
                               llx_facture_fourn.fk_user_valid,
                               llx_facture_fourn.fk_statut,
                               llx_societe.nom as socname,
                               llx_societe.rowid as socid,
                               llx_facture_fourn.note,
                               llx_facture_fourn.note_public
                          FROM llx_facture_fourn, llx_societe
                         WHERE llx_societe.rowid = llx_facture_fourn.fk_soc
                           AND llx_facture_fourn.rowid = " . $objPai->fk_facturefourn;
            $resql = $db->query($requete);
            $id = 0;
            $typeId = false;
            if ($resql) {
                $res = $db->fetch_object($resql);
                $url = $zim->dolibarr_main_url_root . "/fourn/paiement/fiche.php?id=" . $res->rowid;
                //get Loc Zimbra
                $requeteLocZim = "SELECT folder_type_refid as ftid,
                                         folder_uid as fid
                                    FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                   WHERE folder_name='Factures fournisseur'
                                     AND folder_parent =( SELECT folder_uid
                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                           WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                             AND folder_type_refid = (SELECT id
                                                                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                       WHERE val='appointment'))";
                if ($resqlLocZim = $this->db->query($requeteLocZim)) {
                    $zimRes = $db->fetch_object($resqlLocZim);
                    $zimLoc = $zimRes->fid;
                    $typeId = $zimRes->ftid;
                    $arrRes = $zim->Babel_pushDateArr(
                            $objPai->datep, "Regl de " . $res->ref . "" . " (" . $res->socname . ")", "R&egrave;glement de la facture fournisseur " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_paiementfourn", 1, //all day
                            "", //loc géo
                            1, //is org
                            $zimLoc, //loc zimbra
                            $url, $soc->id, $res);
                    $id++;
                }

                while (count($zim->ApptArray) > 0) {
                    $arr = array_pop($zim->ApptArray);
                    $arr1 = $arr;
                    //extract socid
                    //Store to Db, Store to Zimbra
                    $ret = $zim->createApptBabel($arr);
                    // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
                    //                $parent = $arr['l'];
                    $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                    $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                    //faut aussi placer l'event dans le calendrier de la société
                    $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                    $arr1['l'] = $parentId;
                    $ret1 = $zim->createApptBabel($arr1);
                    $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                    $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
                }
            }
        }
    }

    function BillAction($object, $zim, $payed = 0) {
        $db = $this->db;
        $requete = "SELECT llx_facture.rowid,
                           llx_facture.facnumber as ref,
                           llx_facture.datec,
                           llx_facture.paye,
                           llx_facture.datef,
                           llx_facture.date_lim_reglement,
                           llx_facture.date_valid,
                           llx_facture.fk_user_author,
                           llx_facture.fk_user_valid,
                           llx_facture.fk_statut,
                           llx_societe.nom as socname,
                           llx_societe.rowid as socid,
                           llx_facture.note,
                           llx_facture.note_public
                      FROM llx_facture, llx_societe
                     WHERE llx_societe.rowid = llx_facture.fk_soc
                       AND llx_facture.rowid = " . $object->id;
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $zim->dolibarr_main_url_root . "/compta/facture.php?facid=" . $res->rowid;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Factures'
                                         AND folder_parent =( SELECT folder_uid
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";
                    //author et valid
                    $requeteLocZim1 = false;
                    if ($res->fk_user_author != $user->id) {
                        $tmpUser = new User($db);
                        $tmpUser->id = $res->fk_user_author;
                        $tmpUser->fetch();
                        $requeteLocZim1 = "SELECT folder_type_refid as ftid,
                                                 folder_uid as fid
                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                           WHERE folder_name='Factures'
                                             AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                                    WHERE folder_name ='" . trim($tmpUser->prenom . ' ' . $tmpUser->nom) . "'
                                                                      AND folder_type_refid = (SELECT id
                                                                                                 FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                                WHERE val='appointment'))";
                    }
                    $requeteLocZim2 = false;
                    if ($res->fk_user_valid != $user->id) {
                        $tmpUser = new User($db);
                        $tmpUser->id = $res->fk_user_valid;
                        $tmpUser->fetch();
                        $requeteLocZim2 = "SELECT folder_type_refid as ftid,
                                                 folder_uid as fid
                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                           WHERE folder_name='Factures'
                                             AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                                    WHERE folder_name ='" . trim($tmpUser->prenom . ' ' . $tmpUser->nom) . "'
                                                                      AND folder_type_refid = (SELECT id
                                                                                                 FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                                WHERE val='appointment'))";
                    }
                    $zimLoc = false;
                    $zimLoc1 = false;
                    $zimLoc2 = false;


                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                    }
                    if ($requeteLocZim1 && $resqlLocZim1 = $db->query($requeteLocZim1)) {
                        $zimRes1 = $db->fetch_object($requeteLocZim1);
                        $zimLoc1 = $zimRes1->fid;
                    }
                    if ($requeteLocZim2 && $resqlLocZim2 = $db->query($requeteLocZim2)) {
                        $zimRes2 = $db->fetch_object($requeteLocZim2);
                        $zimLoc2 = $zimRes2->fid;
                    }


                    if ($zimLoc . "x" != "x") {
                        $infoPaie = "";
                        if ($payed == 1) {
                            $infoPaie = " Payer partiellement <br>";
                        } else if ($payed == 2) {
                            $infoPaie = " Payer totalement <br>";
                        }

                        if ($res->date_lim_reglement && $res->datef) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    array('debut' => $res->datef, "fin" => $res->date_lim_reglement), "Facture " . "" . $res->ref . "" . " (" . $res->socname . ")", "R&eacute;glement de la facture " . $res->ref . "<BR>" . $infoPaie . "<P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                            if ($zimLoc1) {
                                $arrRes = $zim->Babel_pushDateArr(
                                        array('debut' => $res->datef, "fin" => $res->date_lim_reglement), "Facture " . "" . $res->ref . "" . " (" . $res->socname . ")", "R&eacute;glement de la facture " . $res->ref . "<BR>" . $infoPaie . "<P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture", 1, //all day
                                        "", //loc géo
                                        1, //is org
                                        $zimLoc1, //loc zimbra
                                        $url, $soc->id, $res);
                                $id++;
                            }
                            if ($zimLoc2) {
                                $arrRes = $zim->Babel_pushDateArr(
                                        array('debut' => $res->datef, "fin" => $res->date_lim_reglement), "Facture " . "" . $res->ref . "" . " (" . $res->socname . ")", "R&eacute;glement de la facture " . $res->ref . "<BR>" . $infoPaie . "<P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture", 1, //all day
                                        "", //loc géo
                                        1, //is org
                                        $zimLoc2, //loc zimbra
                                        $url, $soc->id, $res);
                                $id++;
                            }
                        } else if ($res->datef) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    $res->datef, "Valid de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validit&eacute; de la facture " . $res->ref . "<BR>" . $infoPaie . "<P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                            if ($zimLoc1) {
                                $arrRes = $zim->Babel_pushDateArr(
                                        $res->datef, "Valid de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validit&eacute; de la facture " . $res->ref . "<BR>" . $infoPaie . "<P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture", 1, //all day
                                        "", //loc géo
                                        1, //is org
                                        $zimLoc1, //loc zimbra
                                        $url, $soc->id, $res);
                                $id++;
                            }
                            if ($zimLoc2) {
                                $arrRes = $zim->Babel_pushDateArr(
                                        $res->datef, "Valid de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validit&eacute; de la facture " . $res->ref . "<BR>" . $infoPaie . "<P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture", 1, //all day
                                        "", //loc géo
                                        1, //is org
                                        $zimLoc2, //loc zimbra
                                        $url, $soc->id, $res);
                                $id++;
                            }
                        }
                        if ($res->date_valid) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    $res->date_valid, "Validation de Facture " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validation de la facture " . $res->ref . "<BR>" . $infoPaie . "<P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                            if ($zimLoc1) {
                                $arrRes = $zim->Babel_pushDateArr(
                                        $res->date_valid, "Validation de Facture " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validation de la facture " . $res->ref . "<BR>" . $infoPaie . "<P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture", 1, //all day
                                        "", //loc géo
                                        1, //is org
                                        $zimLoc1, //loc zimbra
                                        $url, $soc->id, $res);
                                $id++;
                            }
                            if ($zimLoc2) {
                                $arrRes = $zim->Babel_pushDateArr(
                                        $res->date_valid, "Validation de Facture " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validation de la facture " . $res->ref . "<BR>" . $infoPaie . "<P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture", 1, //all day
                                        "", //loc géo
                                        1, //is org
                                        $zimLoc2, //loc zimbra
                                        $url, $soc->id, $res);
                                $id++;
                            }
                        }
                    }
                }
            }
            while (count($zim->ApptArray) > 0) {
                $arr = array_pop($zim->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $zim->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $zim->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

    function BillActionCancel($object, $zim) {
        //on ajoute cancel sur tout les ev + on ajoute l'annulation de la commande
        $db = $this->db;
        $requete = "SELECT llx_facture.rowid,
                           llx_facture.facnumber as ref,
                           llx_facture.datec,
                           llx_facture.paye,
                           llx_facture.datef,
                           llx_facture.date_lim_reglement,
                           llx_facture.date_valid,
                           llx_facture.fk_user_author,
                           llx_facture.fk_user_valid,
                           llx_facture.fk_statut,
                           llx_societe.nom as socname,
                           llx_societe.rowid as socid,
                           llx_facture.note,
                           llx_facture.note_public
                      FROM llx_facture, llx_societe
                     WHERE llx_societe.rowid = llx_facture.fk_soc
                       AND llx_facture.rowid = " . $object->id;
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        $mainloc = "";
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $zim->dolibarr_main_url_root . "/compta/facture.php?facid=" . $res->rowid;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Factures'
                                         AND folder_parent =( SELECT folder_uid
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";
                    //author et valid
                    $requeteLocZim1 = false;
                    if ($res->fk_user_author != $user->id) {
                        $tmpUser = new User($db);
                        $tmpUser->id = $res->fk_user_author;
                        $tmpUser->fetch();
                        $requeteLocZim1 = "SELECT folder_type_refid as ftid,
                                                 folder_uid as fid
                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                           WHERE folder_name='Factures'
                                             AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                                    WHERE folder_name ='" . trim($tmpUser->prenom . ' ' . $tmpUser->nom) . "'
                                                                      AND folder_type_refid = (SELECT id
                                                                                                 FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                                WHERE val='appointment'))";
                    }
                    $requeteLocZim2 = false;
                    if ($res->fk_user_valid != $user->id) {
                        $tmpUser = new User($db);
                        $tmpUser->id = $res->fk_user_valid;
                        $tmpUser->fetch();
                        $requeteLocZim2 = "SELECT folder_type_refid as ftid,
                                                 folder_uid as fid
                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                           WHERE folder_name='Factures'
                                             AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                                    WHERE folder_name ='" . trim($tmpUser->prenom . ' ' . $tmpUser->nom) . "'
                                                                      AND folder_type_refid = (SELECT id
                                                                                                 FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                                WHERE val='appointment'))";
                    }
                    $zimLoc = false;
                    $zimLoc1 = false;
                    $zimLoc2 = false;

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                    }
                    if ($requeteLocZim1 && $resqlLocZim1 = $db->query($requeteLocZim1)) {
                        $zimRes1 = $db->fetch_object($requeteLocZim1);
                        $zimLoc1 = $zimRes1->fid;
                    }
                    if ($requeteLocZim2 && $resqlLocZim2 = $db->query($requeteLocZim2)) {
                        $zimRes2 = $db->fetch_object($requeteLocZim2);
                        $zimLoc2 = $zimRes2->fid;
                    }

                    if ($zimLoc) {

                        if ($res->date_lim_reglement && $res->datef) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    array('debut' => $res->datef, "fin" => $res->date_lim_reglement), "Facture annul&eacute;e" . "" . $res->ref . "" . " (" . htmlentities($res->socname) . ")", "Facture (annul&eacute;e)" . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }

                        if ($zimLoc1) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    array('debut' => $res->datef, "fin" => $res->date_lim_reglement), "Facture annul&eacute;e" . "" . $res->ref . "" . " (" . htmlentities($res->socname) . ")", "Facture (annul&eacute;e)" . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc1, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                        if ($zimLoc2) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    array('debut' => $res->datef, "fin" => $res->date_lim_reglement), "Facture annul&eacute;e" . "" . $res->ref . "" . " (" . htmlentities($res->socname) . ")", "Facture (annul&eacute;e)" . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc2, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                        $arrRes = $zim->Babel_pushDateArr(
                                $date = date('Y') . "-" . date('m') . "-" . date('d') . " " . date('G') . ":" . date('i') . ":00", "Ann. facture " . "" . $res->ref . "" . " (" . htmlentities($res->socname) . ")", "Annulation de la facture " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                        if ($zimLoc1) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    $date = date('Y') . "-" . date('m') . "-" . date('d') . " " . date('G') . ":" . date('i') . ":00", "Ann. facture " . "" . $res->ref . "" . " (" . htmlentities($res->socname) . ")", "Annulation de la facture " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc1, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                        if ($zimLoc2) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    $date = date('Y') . "-" . date('m') . "-" . date('d') . " " . date('G') . ":" . date('i') . ":00", "Ann. facture " . "" . $res->ref . "" . " (" . htmlentities($res->socname) . ")", "Annulation de la facture " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc2, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                    }
                }
            }
            while (count($zim->ApptArray) > 0) {
                $arr = array_pop($zim->ApptArray);
                if ($arr['cat'] == "llx_facture") {
                    $arr1 = $arr;
                    //extract socid
                    //Store to Db, Store to Zimbra
                    $ret = $zim->createApptBabel($arr);
                    // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
                    //                $parent = $arr['l'];
                    $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                    $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                    //faut aussi placer l'event dans le calendrier de la société
                    $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                    $arr1['l'] = $parentId;
                    $ret1 = $zim->createApptBabel($arr1);
                    $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                    $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
                } else if ($arr['cat'] == "llx_actioncom_rep") {

                    $requeteRepAct = "SELECT folder_type_refid as ftid,
                                         folder_uid as fid
                                    FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                   WHERE folder_name='Actions'
                                     AND folder_parent =" . $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                    if ($resqlRepAct = $this->db->query($requeteRepAct)) {
                        $foldId = $this->db->fetch_object($resqlRepAct)->fid;
                        $arr2 = $arr;
                        $arr2['cat'] == "llx_facture";
                        $arr2['l'] = $foldId;
                        $ret1 = $zim->createApptBabel($arr2);
                        $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                        $zim->Babel_AddEventFromTrigger($typeId, $arr2, $zimId1);
                    }
                }
            }
            //Ajoute l'ev dans action Com
        }
    }

    function BillSupAction($object, $zim) {
        $db = $this->db;
        $requete = "SELECT llx_facture_fourn.rowid,
                           llx_facture_fourn.facnumber as ref,
                           llx_facture_fourn.datec,
                           llx_facture_fourn.paye,
                           llx_facture_fourn.datef,
                           llx_facture_fourn.date_lim_reglement,
                           llx_facture_fourn.date_valid,
                           llx_facture_fourn.fk_user_author,
                           llx_facture_fourn.fk_user_valid,
                           llx_facture_fourn.fk_statut,
                           llx_societe.nom as socname,
                           llx_societe.rowid as socid,
                           llx_facture_fourn.note,
                           llx_facture_fourn.note_public
                      FROM llx_facture_fourn, llx_societe
                     WHERE llx_societe.rowid = llx_facture_fourn.fk_soc
                       AND llx_facture_fourn.rowid = " . $object->id;
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $zim->dolibarr_main_url_root . "/fourn/facture/fiche.php?facid=" . $res->rowid;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Factures fournisseur'
                                         AND folder_parent =( SELECT folder_uid
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;

                        if ($res->date_lim_reglement && $res->datef) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    array('debut' => $res->datef, "fin" => $res->date_lim_reglement), "Facture " . "" . $res->ref . "" . " (" . $res->socname . ")", "R&eacute;glement de la facture fournisseur " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture_fourn", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        } else if ($res->datef) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    $res->datef, "Valid de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validit&eacute; de la facture fournisseur " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture_fourn", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                        if ($res->date_valid) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    $res->date_valid, "Validation de Facture  fournisseur" . "" . $res->ref . "" . " (" . $res->socname . ")", "Validation de la facture  fournisseur" . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_facture_fourn", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                    }
                }
            }
            while (count($zim->ApptArray) > 0) {
                $arr = array_pop($zim->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $zim->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $zim->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

    function OrderAction($object, $zim) {
        //on ajoute dans le bon calendrier
        $db = $this->db;
        $requete = "SELECT llx_commande.rowid,
                           llx_commande.ref,
                           llx_commande.date_creation,
                           llx_commande.date_commande,
                           llx_commande.date_valid,
                           llx_commande.date_cloture,
                           llx_commande.fk_user_author,
                           llx_commande.fk_user_valid,
                           llx_commande.fk_user_cloture,
                           llx_commande.fk_statut,
                           llx_societe.nom as socname,
                           llx_societe.rowid as socid,
                           llx_commande.note,
                           llx_commande.note_public,
                           llx_commande.date_livraison
                      FROM llx_commande, llx_societe
                     WHERE llx_societe.rowid = llx_commande.fk_soc AND llx_commande.rowid = " . $object->id;
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/commande/fiche.php?id=" . $res->rowid;
                if ($res->date_creation) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Commandes'
                                         AND folder_parent =( SELECT folder_uid
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";
                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->date_creation, "Créat. de la com " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la commande " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_commande", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_commande) {
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->datec, "Date commande " . "" . $res->ref . "" . " (" . $res->socname . ")", "Commande  " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_commande", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_valid && $res->date_cloture . "x" == "x") {
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->date_valid, "Valid de la com " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validit&eacute; de la commande " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_commande", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    } else if ($res->date_cloture) {
                        $arrRes = $zim->Babel_pushDateArr(
                                array('debut' => $res->date_valid, 'fin' => $res->date_cloture), "Clot de le com " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cloture de la commande " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_commande", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($zim->ApptArray) > 0) {
                $arr = array_pop($zim->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $zim->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);
                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $zim->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
            $this->SyncUserCal($object, $zim, 'order');
        }
    }

    function OrderSupAction($object, $zim) {
        //on ajoute dans le bon calendrier
        $db = $this->db;
        $requete = "SELECT llx_commande_fournisseur.rowid,
                           llx_commande_fournisseur.ref,
                           llx_commande_fournisseur.date_creation,
                           llx_commande_fournisseur.date_commande,
                           llx_commande_fournisseur.date_valid,
                           llx_commande_fournisseur.date_cloture,
                           llx_commande_fournisseur.fk_user_author,
                           llx_commande_fournisseur.fk_user_valid,
                           llx_commande_fournisseur.fk_user_cloture,
                           llx_commande_fournisseur.fk_statut,
                           llx_societe.nom as socname,
                           llx_societe.rowid as socid,
                           llx_commande_fournisseur.note,
                           llx_commande_fournisseur.note_public
                      FROM llx_commande_fournisseur, llx_societe
                     WHERE llx_societe.rowid = llx_commande_fournisseur.fk_soc AND llx_commande_fournisseur.rowid = " . $object->id;
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/fourn/commande/fiche.php?id=" . $res->rowid;
                if ($res->date_creation) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Commandes fournisseur'
                                         AND folder_parent =( SELECT folder_uid
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";
                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->date_creation, "Créat. de la com fourn " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la commande fournisseur " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_commande_fournisseur", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_commande) {
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->datec, "Date commande fourniseur" . "" . $res->ref . "" . " (" . $res->socname . ")", "Commande  fournisseur" . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_commande_fournisseur", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_valid && $res->date_cloture . "x" == "x") {
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->date_valid, "Valid de la com fourn " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validit&eacute; de la commande fournisseur " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_commande_fournisseur", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    } else if ($res->date_cloture) {
                        $arrRes = $zim->Babel_pushDateArr(
                                array('debut' => $res->date_valid, 'fin' => $res->date_cloture), "Clot de la com fourn " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cloture de la commande fourn " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_commande_fournisseur", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($zim->ApptArray) > 0) {
                $arr = array_pop($zim->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $zim->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);
                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $zim->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

    function SyncUserCal($object, $zim, $type) {
        global $user;
        switch ($type) {
            case 'order': {
                    $whichUser = array();
                    array_push($whichUser, $user->id);
                    $object->info($object->id);
                    if ($object->user_creation->id && $object->user_creation->id != $user->id) {
                        array_push($whichUser, $object->user_creation->id);
                    }
                    if ($object->user_validation->id && $object->user_validation->id != $user->id) {
                        array_push($whichUser, $object->user_validation->id);
                    }
                    if ($object->user_cloture->id && $object->user_cloture->id != $user->id) {
                        array_push($whichUser, $object->user_cloture->id);
                    }
                    foreach ($whichUser as $key => $val) {
                        $zim->Synopsis_Zimbra_GetCommandeUserById($val, $object->id);
                    }
                }
                break;
            case 'propal': {
                    $whichUser = array();
                    array_push($whichUser, $user->id);
                    $object->info($object->id);
                    if ($object->user_creation->id && $object->user_creation->id != $user->id) {
                        array_push($whichUser, $object->user_creation->id);
                    }
                    if ($object->user_validation->id && $object->user_validation->id != $user->id) {
                        array_push($whichUser, $object->user_validation->id);
                    }
                    if ($object->user_cloture->id && $object->user_cloture->id != $user->id) {
                        array_push($whichUser, $object->user_cloture->id);
                    }
                    foreach ($whichUser as $key => $val) {
                        $zim->Synopsis_Zimbra_GetPropalUserById($val, $object->id);
                    }
                }
                break;
            case 'expedition': {
                    $whichUser = array();
                    array_push($whichUser, $user->id);
                    $object->info($object->id);
                    if ($object->user_creation->id && $object->user_creation->id != $user->id) {
                        array_push($whichUser, $object->user_creation->id);
                    }
                    if ($object->user_validation->id && $object->user_validation->id != $user->id) {
                        array_push($whichUser, $object->user_validation->id);
                    }
                    foreach ($whichUser as $key => $val) {
                        $zim->Synopsis_Zimbra_GetExpeditionUserById($val, $object->id);
                    }
                }
            case 'livraison': {
                    $whichUser = array();
                    array_push($whichUser, $user->id);
                    $object->info($object->id);
                    if ($object->user_creation->id && $object->user_creation->id != $user->id) {
                        array_push($whichUser, $object->user_creation->id);
                    }
                    if ($object->user_validation->id && $object->user_validation->id != $user->id) {
                        array_push($whichUser, $object->user_validation->id);
                    }
                    foreach ($whichUser as $key => $val) {
                        $zim->Synopsis_Zimbra_GetLivraisonUserById($val, $object->id);
                    }
                }
                break;
            case 'FI': {
                    $whichUser = array();
                    array_push($whichUser, $user->id);
                    $object->info($object->id);
                    if ($object->user_creation->id && $object->user_creation->id != $user->id) {
                        array_push($whichUser, $object->user_creation->id);
                    }
                    if ($object->user_validation->id && $object->user_validation->id != $user->id) {
                        array_push($whichUser, $object->user_validation->id);
                    }
                    foreach ($whichUser as $key => $val) {
                        $zim->Synopsis_Zimbra_GetInterventionUserById($val, $object->id);
                    }
                }
                break;
            case 'DI': {
                    $whichUser = array();
                    array_push($whichUser, $user->id);
                    $object->info($object->id);
                    if ($object->user_creation->id && $object->user_creation->id != $user->id) {
                        array_push($whichUser, $object->user_creation->id);
                    }
                    if ($object->user_validation->id && $object->user_validation->id != $user->id) {
                        array_push($whichUser, $object->user_validation->id);
                    }
                    if ($object->user_target->id && $object->user_target->id != $user->id) {
                        array_push($whichUser, $object->user_target->id);
                    }
                    if ($object->user_prisencharge->id && $object->user_prisencharge->id != $user->id) {
                        array_push($whichUser, $object->user_prisencharge->id);
                    }
                    if ($object->user_cloture->id && $object->user_cloture->id != $user->id) {
                        array_push($whichUser, $object->user_cloture->id);
                    }
                    foreach ($whichUser as $key => $val) {
                        $zim->Synopsis_Zimbra_GetDemandeInterventionUserById($val, $object->id);
                    }
                }
                break;
            case 'contratDet': {
                    $whichUser = array();
                    array_push($whichUser, $user->id);
                    $object->info_contratdet($object->id);
                    foreach ($object->user_creation as $key => $val) {
                        if ($val->id && $val->id != $user->id) {
                            array_push($whichUser, $val->id);
                        }
                    }
                    foreach ($object->user_ouverture as $key => $val) {
                        if ($val->id && $val->id != $user->id) {
                            array_push($whichUser, $val->id);
                        }
                    }
                    foreach ($object->user_cloture as $key => $val) {
                        if ($val->id && $val->id != $user->id) {
                            array_push($whichUser, $val->id);
                        }
                    }

                    foreach ($whichUser as $key => $val) {
                        $zim->Synopsis_Zimbra_GetContratDetUserById($val, $object->id);
                    }
                }
                break;
            case 'Contrat': {
                    $whichUser = array();
                    array_push($whichUser, $user->id);
                    $object->info($object->id);
                    if ($object->user_creation->id && $object->user_creation->id != $user->id) {
                        array_push($whichUser, $object->user_creation->id);
                    }
                    if ($object->user_cloture->id && $object->user_cloture->id != $user->id) {
                        array_push($whichUser, $object->user_cloture->id);
                    }
                    if ($object->commercial_signature->id && $object->commercial_signature->id != $user->id) {
                        array_push($whichUser, $object->commercial_signature->id);
                    }
                    if ($object->commercial_suivi->id && $object->commercial_suivi->id != $user->id) {
                        array_push($whichUser, $object->commercial_suivi->id);
                    }
                    if ($object->user_mise_en_service->id && $object->user_mise_en_service->id != $user->id) {
                        array_push($whichUser, $object->user_mise_en_service->id);
                    }
                    foreach ($whichUser as $key => $val) {
                        $zim->Synopsis_Zimbra_GetContratUserById($val, $object->id);
                    }
                }
                break;
        }
    }

    function PropalAction($object, $zim) {
        $db = $this->db;
        $requete = "SELECT llx_propal.rowid,
                           llx_propal.ref,
                           llx_propal.datec,
                           llx_propal.datep,
                           llx_propal.fin_validite,
                           llx_propal.date_valid,
                           llx_propal.date_cloture,
                           llx_propal.fk_user_author,
                           llx_propal.fk_user_valid,
                           llx_propal.fk_user_cloture,
                           llx_propal.fk_statut,
                           llx_societe.nom as socname,
                           llx_societe.rowid as socid,
                           llx_propal.note,
                           llx_propal.note_public,
                           llx_propal.date_livraison
                      FROM llx_propal, llx_societe
                     WHERE llx_societe.rowid = llx_propal.fk_soc
                       AND llx_propal.rowid = " . $object->id;
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;

        while ($res = $db->fetch_object($resql)) {
            $url = $zim->dolibarr_main_url_root . "/comm/propal.php?propalid=" . $res->rowid;
            if ($res->datec) {
                //get Loc Zimbra
                $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Propales'
                                         AND folder_parent =( SELECT folder_uid
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";
                if ($resqlLocZim = $db->query($requeteLocZim)) {
                    $zimRes = $db->fetch_object($resqlLocZim);
                    $zimLoc = $zimRes->fid;
                    $typeId = $zimRes->ftid;
                    $arrRes = $zim->Babel_pushDateArr(
                            $res->datec, "Créat. de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la proposition commerciale " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_propal", 1, //all day
                            "", 1, //loc géo
                            $zimLoc, //loc zimbra
                            $url, $soc->id, $res);
                    $id++;
                }
                if ($res->datep) {
                    $arrRes = $zim->Babel_pushDateArr(
                            $res->datep, "Date Prop " . "" . $res->ref . "" . " (" . $res->socname . ")", "Proposition commerciale " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_propal", 1, //all day
                            "", //loc géo
                            1, //is org
                            $zimLoc, //loc zimbra
                            $url, $soc->id, $res);
                    $id++;
                }
                if ($res->fin_validite && $res->datep) {
                    $arrRes = $zim->Babel_pushDateArr(
                            array('debut' => $res->datep, "fin" => $res->fin_validite), "Valid de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validit&eacute; de la proposition commerciale " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_propal", 1, //all day
                            "", //loc géo
                            1, //is org
                            $zimLoc, //loc zimbra
                            $url, $soc->id, $res);
                    $id++;
                }
                if ($res->date_cloture) {
                    $arrRes = $zim->Babel_pushDateArr(
                            $res->date_cloture, "Clot de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cloture Proposition commerciale " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_propal", 1, //all day
                            "", //loc géo
                            1, //is org
                            $zimLoc, //loc zimbra
                            $url, $soc->id, $res);
                    $id++;
                }
            }
            while (count($zim->ApptArray) > 0) {
                $arr = array_pop($zim->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $zim->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);
                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;

                $ret1 = $zim->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
            $this->SyncUserCal($object, $zim, 'propal');
        }
    }

    function ActionAction($object, $zim, $user) {
        $db = $zim->db;
        $requete = "SELECT  llx_actioncomm.datec,
                            llx_actioncomm.datep,
                            llx_actioncomm.datep2,
                            llx_actioncomm.label,
                            llx_actioncomm.fk_user_action,
                            llx_actioncomm.fk_user_done,
                            llx_actioncomm.fk_user_author,
                            llx_actioncomm.fk_user_mod,
                            llx_actioncomm.id,
                            llx_c_actioncomm.libelle,
                            llx_projet.title,
                            llx_projet.ref,
                            llx_actioncomm.durationp,
                            llx_actioncomm.note,
                            llx_societe.nom as socname,
                            llx_societe.rowid as socid
                      FROM  llx_societe, llx_actioncomm llx_actioncomm
                 LEFT JOIN llx_projet on llx_actioncomm.fk_projet = llx_projet.rowid
                 LEFT JOIN llx_c_actioncomm on llx_c_actioncomm.id = llx_actioncomm.fk_action
                 LEFT JOIN llx_socpeople on llx_socpeople.rowid = llx_actioncomm.fk_contact
                    WHERE llx_societe.rowid = llx_actioncomm.fk_soc AND llx_actioncomm.id =" . $object->id;
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $zim->dolibarr_main_url_root . "/comm/action/fiche.php?id=" . $res->id;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Actions'
                                         AND folder_parent =( SELECT max(folder_uid) as folder_uid
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type

                                                                                           WHERE val='appointment'))";
                    $requeteLocZim1 = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Actions'
                                         AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                                 FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                                WHERE folder_name ='" . trim($user->prenom . ' ' . $user->nom) . "'
                                                                  AND folder_type_refid = (SELECT id
                                                                                             FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                            WHERE val='appointment'))";
                    $requeteLocZim2 = false;
                    if ("x" . $res->fk_user_author != "x" && $res->fk_user_author != $user->id) {
                        $tmpUser = new User($db);
                        $tmpUser->id = $res->fk_user_author;
                        $tmpUser->fetch();
                        $requeteLocZim2 = "SELECT folder_type_refid as ftid,
                                                 folder_uid as fid
                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                           WHERE folder_name='Actions'
                                             AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                                    WHERE folder_name ='" . $tmpUser->fullname . "'
                                                                      AND folder_type_refid = (SELECT id
                                                                                                 FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                                WHERE val='appointment'))";
                    }
                    $requeteLocZim3 = false;
                    if ("x" . $res->fk_user_action != "x" && $res->fk_user_action != $user->id) {
                        $tmpUser = new User($db);
                        $tmpUser->id = $res->fk_user_action;
                        $tmpUser->fetch();
                        $requeteLocZim3 = "SELECT folder_type_refid as ftid,
                                                 folder_uid as fid
                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                           WHERE folder_name='Actions'
                                             AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                                    WHERE folder_name ='" . $tmpUser->fullname . "'
                                                                      AND folder_type_refid = (SELECT id
                                                                                                 FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                                WHERE val='appointment'))";
                    }
                    $requeteLocZim4 = false;
                    if ("x" . $res->fk_user_done != "x" && $res->fk_user_done != $user->id) {
                        $tmpUser = new User($db);
                        $tmpUser->id = $res->fk_user_done;
                        $tmpUser->fetch();
                        $requeteLocZim4 = "SELECT folder_type_refid as ftid,
                                                 folder_uid as fid
                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                           WHERE folder_name='Actions'
                                             AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                                    WHERE folder_name ='" . $tmpUser->fullname . "'
                                                                      AND folder_type_refid = (SELECT id
                                                                                                 FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                                WHERE val='appointment'))";
                    }
                    $requeteLocZim5 = false;
                    if ("x" . $res->fk_user_mod != "x" && $res->fk_user_mod != $user->id) {
                        $tmpUser = new User($db);
                        $tmpUser->id = $res->fk_user_mod;
                        $tmpUser->fetch();
                        $requeteLocZim5 = "SELECT folder_type_refid as ftid,
                                                 folder_uid as fid
                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                           WHERE folder_name='Actions'
                                             AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                                    WHERE folder_name ='" . $tmpUser->fullname . "'
                                                                      AND folder_type_refid = (SELECT id
                                                                                                 FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                                WHERE val='appointment'))";
                    }

                    $zimLoc = false;
                    $zimLoc1 = false;
                    $zimLoc2 = false;
                    $zimLoc3 = false;
                    $zimLoc4 = false;
                    $zimLoc5 = false;


                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                    }

                    if ($resqlLocZim1 = $db->query($requeteLocZim1)) {
                        $zimRes1 = $db->fetch_object($resqlLocZim1);
                        $zimLoc1 = $zimRes1->fid;
                    }
                    if ($resqlLocZim2 = $db->query($requeteLocZim2)) {
                        $zimRes2 = $db->fetch_object($resqlLocZim2);
                        $zimLoc2 = $zimRes2->fid;
                    }
                    if ($resqlLocZim3 = $db->query($requeteLocZim3)) {
                        $zimRes3 = $db->fetch_object($resqlLocZim3);
                        $zimLoc1 = $zimRes3->fid;
                    }
                    if ($resqlLocZim4 = $db->query($requeteLocZim4)) {
                        $zimRes4 = $db->fetch_object($resqlLocZim4);
                        $zimLoc4 = $zimRes4->fid;
                    }
                    if ($resqlLocZim5 = $db->query($requeteLocZim5)) {
                        $zimRes5 = $db->fetch_object($resqlLocZim5);
                        $zimLoc5 = $zimRes5->fid;
                    }
                    if ("x" . $zimLoc != "x" && "x" . $zimLoc1 != "x") {
                        //si durationp => debut + fin =  si datep2 != datep => debut + fin
                        $allDay = 0;
                        if ($res->datep) {
                            $date = $res->datep;
                            if ($res->datep2 && $res->datep && $res->durationp != 0) {
                                $allDay = 1;
                                $date = array('debut' => $res->datep, 'fin' => $res->datep2);
                            }
                            $arrRes = $zim->Babel_pushDateArr(
                                    $date, htmlentities($res->libelle . " (" . $res->socname . ")"), "Action commerciale " . htmlentities($res->libelle) . "<HR><P>" . htmlentities($res->label) . "<BR>Ref :" . htmlentities($res->ref) . "<HR><P>" . $res->note . "<BR><P>", $res->ref, $id, "llx_actioncomm", $allDay, //all day
                                    "", 1, //loc géo
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                            $arrRes = $zim->Babel_pushDateArr(
                                    $date, htmlentities($res->libelle . " (" . $res->socname . ")"), "Action commerciale " . htmlentities($res->libelle) . "<HR><P>" . htmlentities($res->label) . "<BR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $res->ref, $id, "llx_actioncomm", $allDay, //all day
                                    "", 1, //loc géo
                                    $zimLoc1, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                            if ($zimLoc2) {
                                $arrRes = $zim->Babel_pushDateArr(
                                        $date, htmlentities($res->libelle . " (" . $res->socname . ")"), "Action commerciale " . htmlentities($res->libelle) . "<HR><P>" . htmlentities($res->label) . "<BR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $res->ref, $id, "llx_actioncomm", $allDay, //all day
                                        "", 1, //loc géo
                                        $zimLoc2, //loc zimbra
                                        $url, $soc->id, $res);
                                $id++;
                            }
                            if ($zimLoc3) {
                                $arrRes = $zim->Babel_pushDateArr(
                                        $date, htmlentities($res->libelle . " (" . $res->socname . ")"), "Action commerciale " . htmlentities($res->libelle) . "<HR><P>" . htmlentities($res->label) . "<BR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $res->ref, $id, "llx_actioncomm", $allDay, //all day
                                        "", 1, //loc géo
                                        $zimLoc3, //loc zimbra
                                        $url, $soc->id, $res);
                                $id++;
                            }
                            if ($zimLoc4) {
                                $arrRes = $zim->Babel_pushDateArr(
                                        $date, htmlentities($res->libelle . " (" . $res->socname . ")"), "Action commerciale " . htmlentities($res->libelle) . "<HR><P>" . htmlentities($res->label) . "<BR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $res->ref, $id, "llx_actioncomm", $allDay, //all day
                                        "", 1, //loc géo
                                        $zimLoc4, //loc zimbra
                                        $url, $soc->id, $res);
                                $id++;
                            }
                            if ($zimLoc5) {
                                $arrRes = $zim->Babel_pushDateArr(
                                        $date, htmlentities($res->libelle . " (" . $res->socname . ")"), "Action commerciale " . htmlentities($res->libelle) . "<HR><P>" . htmlentities($res->label) . "<BR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $res->ref, $id, "llx_actioncomm", $allDay, //all day
                                        "", 1, //loc géo
                                        $zimLoc5, //loc zimbra
                                        $url, $soc->id, $res);
                                $id++;
                            }
                        }
                    }
                }
            }
            while (count($zim->ApptArray) > 0) {
                $arr = array_pop($zim->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $zim->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);
                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $zim->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

    function facture_sendAction($object, $zim) {
        global $user;
        //pas une action com
        $db = $zim->db;
        $object->info($object->id);
        $id = 0;
        $url = $zim->dolibarr_main_url_root . "/compta/facture.php?facid=" . $object->id;
        $typeId = false;
        $requeteLocZim = "SELECT folder_type_refid as ftid,
                                 folder_uid as fid
                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                           WHERE folder_name='Actions'
                             AND folder_parent =( SELECT folder_uid
                                                    FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                   WHERE folder_name ='" . $object->client->nom . '-' . $object->client->id . "'
                                                     AND folder_type_refid = (SELECT id
                                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                               WHERE val='appointment'))";

        $requeteLocZim1 = false;
        if ($object->user_creation->id != $user->id) {
            $requeteLocZim1 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Actions'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($object->user_creation->prenom . ' ' . $object->user_creation->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }

        $requeteLocZim2 = false;
        if ($res->user_validation->id . "x" != "x" && $object->user_validation->id != $user->id) {
            $requeteLocZim2 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Actions'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($object->user_validation->prenom . ' ' . $object->user_validation->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim3 = false;
        if ($res->user_validation->id . "x" != "x" && $res->user_validation->id != $user->id) {
            $tmpUser = new User($db);
            $tmpUser->id = $res->fk_user_valid;
            $tmpUser->fetch();
            $requeteLocZim3 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Factures'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($object->user_validation->prenom . ' ' . $object->user_validation->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim4 = false;
        if ($object->user_creation->id != $user->id) {
            $requeteLocZim4 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Factures'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($object->user_creation->prenom . ' ' . $object->user_creation->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }

        $requeteLocZim5 = false;
        if ($user->id) {
            $requeteLocZim5 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Actions'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($user->prenom . ' ' . $user->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }

        $requeteLocZim6 = false;
        if ($user->id) {
            $requeteLocZim6 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Factures'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($user->prenom . ' ' . $user->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim7 = false;
        if ($user->id) {
            $requeteLocZim7 = "SELECT folder_type_refid as ftid,
                             folder_uid as fid
                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                       WHERE folder_name='Factures'
                         AND folder_parent =( SELECT folder_uid
                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                               WHERE folder_name ='" . $object->client->nom . '-' . $object->client->id . "'
                                                 AND folder_type_refid = (SELECT id
                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                           WHERE val='appointment'))";
        }

        $zimLoc = false;
        $zimLoc1 = false;
        $zimLoc2 = false;
        $zimLoc3 = false;
        $zimLoc4 = false;
        $zimLoc5 = false;
        $zimLoc6 = false;
        $zimLoc7 = false;

        if ($resqlLocZim = $db->query($requeteLocZim)) {
            $zimRes = $db->fetch_object($resqlLocZim);
            $zimLoc = $zimRes->fid;
            $typeId = $zimRes->ftid;
        }
        if ($resqlLocZim1 = $db->query($requeteLocZim1)) {
            $zimRes1 = $db->fetch_object($resqlLocZim1);
            $zimLoc1 = $zimRes1->fid;
        }
        if ($resqlLocZim2 = $db->query($requeteLocZim2)) {
            $zimRes2 = $db->fetch_object($resqlLocZim2);
            $zimLoc2 = $zimRes2->fid;
        }
        if ($resqlLocZim3 = $db->query($requeteLocZim3)) {
            $zimRes3 = $db->fetch_object($resqlLocZim3);
            $zimLoc3 = $zimRes3->fid;
        }
        if ($resqlLocZim4 = $db->query($requeteLocZim4)) {
            $zimRes4 = $db->fetch_object($resqlLocZim4);
            $zimLoc4 = $zimRes4->fid;
        }
        if ($resqlLocZim5 = $db->query($requeteLocZim5)) {
            $zimRes5 = $db->fetch_object($resqlLocZim5);
            $zimLoc5 = $zimRes5->fid;
        }
        if ($resqlLocZim6 = $db->query($requeteLocZim6)) {
            $zimRes6 = $db->fetch_object($resqlLocZim6);
            $zimLoc6 = $zimRes6->fid;
        }
        if ($resqlLocZim7 = $db->query($requeteLocZim7)) {
            $zimRes7 = $db->fetch_object($resqlLocZim7);
            $zimLoc7 = $zimRes7->fid;
        }
        if ($zimLoc . "x" != "x") {
            //si durationp => debut + fin =  si datep2 != datep => debut + fin
            $allDay = 0;
            $res = $object;
            $date = date('Y') . "-" . date('m') . "-" . date('d') . " " . date('G') . ":" . date('i') . ":00"; //2007-08-31 12:01:01
            //replace $date par now()
            $arrRes = $zim->Babel_pushDateArr(
                    $date, "Env fact " . $object->ref . " (" . $object->client->nom . ")", "Envoie de facture par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                    "", 1, //loc géo
                    $zimLoc, //loc zimbra
                    $url, $soc->id, $res);
            $id++;
            if ($zimLoc1 && $zimLoc1 . "x" != "x") {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env fact " . $object->ref . " (" . $object->client->nom . ")", "Envoie de facture par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc1, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc2 && $zimLoc2 . "x" != "x") {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env fact " . $object->ref . " (" . $object->client->nom . ")", "Envoie de facture par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc2, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc3 && $zimLoc3 . "x" != "x") {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env fact " . $object->ref . " (" . $object->client->nom . ")", "Envoie de facture par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc3, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc4 && $zimLoc4 . "x" != "x") {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env fact " . $object->ref . " (" . $object->client->nom . ")", "Envoie de facture par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc4, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc5 && $zimLoc5 . "x" != "x") {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env fact " . $object->ref . " (" . $object->client->nom . ")", "Envoie de facture par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc5, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc6 && $zimLoc6 . "x" != "x") {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env fact " . $object->ref . " (" . $object->client->nom . ")", "Envoie de facture par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc6, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc7 && $zimLoc7 . "x" != "x") {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env fact " . $object->ref . " (" . $object->client->nom . ")", "Envoie de facture par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc7, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
        }
        $remArray = array();
        while (count($zim->ApptArray) > 0) {
            $arr = array_pop($zim->ApptArray);
            $arr1 = $arr;
            //Store to Db, Store to Zimbra
            $ret = $zim->createApptBabel($arr);
            // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
            $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
            $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);
            //faut aussi placer l'event dans le calendrier de la société
            $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
            $arr1['l'] = $parentId;

            $id = $arr['obj']->rowid;
            if ("x" . $id == "x") {
                $id = $arr['obj']->id;
            }
            if ($remArray[$parentId][$id]) {
                continue;
            } else {
                $remArray[$parentId][$id] = true;
                $ret1 = $zim->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

    function commande_sendAction($object, $zim) {
        $id = 0;
        //pas une action com
        $db = $zim->db;
        global $user;
        $typeId = false;
        $object->info($object->id);
        $url = $zim->dolibarr_main_url_root . "/commande/fiche.php?id=" . $res->id;
        //get Loc Zimbra
        $requeteLocZim = "SELECT folder_type_refid as ftid,
                                 folder_uid as fid
                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                           WHERE folder_name='Actions'
                             AND folder_parent =( SELECT folder_uid
                                                    FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                   WHERE folder_name ='" . $object->client->nom . '-' . $object->client->id . "'
                                                     AND folder_type_refid = (SELECT id
                                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                               WHERE val='appointment'))";
        $requeteLocZim1 = false;
        if ($objet->user_creation->id . "x" != "x" && $object->user_creation->id != $user->id) {
            $requeteLocZim1 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Commandes'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($object->user_creation->prenom . ' ' . $object->user_creation->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim2 = false;
        if ($objet->user_validation->id . "x" != "x" && $object->user_validation->id != $user->id) {
            $requeteLocZim2 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Commandes'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($object->user_validation->prenom . ' ' . $object->user_validation->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim3 = false;
        if ($objet->user_cloture->id . "x" != "x" && $object->user_cloture->id != $user->id) {
            $requeteLocZim3 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Commandes'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($object->user_cloture->prenom . ' ' . $object->user_cloture->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim4 = false;
        if ($user->id) {
            $requeteLocZim4 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Commandes'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($user->prenom . ' ' . $user->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }

        $requeteLocZim5 = false;
        if ($objet->user_creation->id . "x" != "x" && $object->user_creation->id != $user->id) {
            $requeteLocZim5 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Actions'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($object->user_creation->prenom . ' ' . $object->user_creation->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim6 = false;
        if ($objet->user_validation->id . "x" != "x" && $object->user_validation->id != $user->id) {
            $requeteLocZim6 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Actions'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($object->user_validation->prenom . ' ' . $object->user_validation->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim7 = false;
        if ($objet->user_cloture->id . "x" != "x" && $object->user_cloture->id != $user->id) {
            $requeteLocZim7 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Actions'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($object->user_cloture->prenom . ' ' . $object->user_cloture->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim8 = false;
        if ($user->id) {
            $requeteLocZim8 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Actions'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($user->prenom . ' ' . $user->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim9 = false;
        if ($user->id) {
            $requeteLocZim9 = "SELECT folder_type_refid as ftid,
                                 folder_uid as fid
                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                           WHERE folder_name='Commandes'
                             AND folder_parent =( SELECT folder_uid
                                                    FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                   WHERE folder_name ='" . $object->client->nom . '-' . $object->client->id . "'
                                                     AND folder_type_refid = (SELECT id
                                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                               WHERE val='appointment'))";
        }



        $zimLoc = false;
        $zimLoc1 = false;
        $zimLoc2 = false;
        $zimLoc3 = false;
        $zimLoc4 = false;
        $zimLoc5 = false;
        $zimLoc6 = false;
        $zimLoc7 = false;
        $zimLoc8 = false;
        $zimLoc9 = false;

        if ($resqlLocZim = $db->query($requeteLocZim)) {
            $zimRes = $db->fetch_object($resqlLocZim);
            $zimLoc = $zimRes->fid;
            $typeId = $zimRes->ftid;
        }
        if ($resqlLocZim1 = $db->query($requeteLocZim1)) {
            $zimRes1 = $db->fetch_object($resqlLocZim1);
            $zimLoc1 = $zimRes1->fid;
        }
        if ($resqlLocZim2 = $db->query($requeteLocZim2)) {
            $zimRes2 = $db->fetch_object($resqlLocZim2);
            $zimLoc2 = $zimRes2->fid;
        }
        if ($resqlLocZim3 = $db->query($requeteLocZim3)) {
            $zimRes3 = $db->fetch_object($resqlLocZim3);
            $zimLoc3 = $zimRes3->fid;
        }
        if ($resqlLocZim4 = $db->query($requeteLocZim4)) {
            $zimRes4 = $db->fetch_object($resqlLocZim4);
            $zimLoc4 = $zimRes4->fid;
        }
        if ($resqlLocZim5 = $db->query($requeteLocZim5)) {
            $zimRes5 = $db->fetch_object($resqlLocZim5);
            $zimLoc5 = $zimRes5->fid;
        }
        if ($resqlLocZim6 = $db->query($requeteLocZim6)) {
            $zimRes6 = $db->fetch_object($resqlLocZim6);
            $zimLoc6 = $zimRes6->fid;
        }
        if ($resqlLocZim7 = $db->query($requeteLocZim7)) {
            $zimRes7 = $db->fetch_object($resqlLocZim7);
            $zimLoc7 = $zimRes7->fid;
        }
        if ($resqlLocZim8 = $db->query($requeteLocZim8)) {
            $zimRes8 = $db->fetch_object($resqlLocZim8);
            $zimLoc8 = $zimRes8->fid;
        }
        if ($resqlLocZim9 = $db->query($requeteLocZim9)) {
            $zimRes9 = $db->fetch_object($resqlLocZim9);
            $zimLoc9 = $zimRes9->fid;
        }
        if ($zimLoc) {
            //si durationp => debut + fin =  si datep2 != datep => debut + fin
            $allDay = 0;
            $res = $object;
            $date = date('Y') . "-" . date('m') . "-" . date('d') . " " . date('G') . ":" . date('i') . ":00"; //2007-08-31 12:01:01
            //replace $date par now()
            $arrRes = $zim->Babel_pushDateArr(
                    $date, "Env commande " . $object->ref . " (" . $object->client->nom . ")", "Envoie de commande par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                    "", 1, //loc géo
                    $zimLoc, //loc zimbra
                    $url, $soc->id, $res);
            $id++;
            if ($zimLoc1) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env commande " . $object->ref . " (" . $object->client->nom . ")", "Envoie de commande par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc1, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc2) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env commande " . $object->ref . " (" . $object->client->nom . ")", "Envoie de commande par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc2, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc3) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env commande " . $object->ref . " (" . $object->client->nom . ")", "Envoie de commande par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc3, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc4) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env commande " . $object->ref . " (" . $object->client->nom . ")", "Envoie de commande par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc4, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc5) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env commande " . $object->ref . " (" . $object->client->nom . ")", "Envoie de commande par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc5, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc6) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env commande " . $object->ref . " (" . $object->client->nom . ")", "Envoie de commande par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc6, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc7) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env commande " . $object->ref . " (" . $object->client->nom . ")", "Envoie de commande par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc7, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc8) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env commande " . $object->ref . " (" . $object->client->nom . ")", "Envoie de commande par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc8, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc9) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env commande " . $object->ref . " (" . $object->client->nom . ")", "Envoie de commande par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc9, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
        }
        //ajoute ds action et ds factures
        $remArray = array();
        while (count($zim->ApptArray) > 0) {
            $arr = array_pop($zim->ApptArray);
            $arr1 = $arr;
            //extract socid
            //Store to Db, Store to Zimbra
            $ret = $zim->createApptBabel($arr);
            // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
            $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
            $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);
            //faut aussi placer l'event dans le calendrier de la société
            $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);

            $arr1['l'] = $parentId;

            $id = $arr['obj']->rowid;
            if ("x" . $id == "x") {
                $id = $arr['obj']->id;
            }
            if ($remArray[$parentId][$id]) {
                continue;
            } else {
                $remArray[$parentId][$id] = true;
                $ret1 = $zim->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

    function propal_sendAction($object, $zim) {
        $id = 0;
        global $user;

        // on ajoute l'evenement dans le calendrier action
        // on ajoute une action com dans le calendrier
        //pas une action com
        $db = $zim->db;
        $object->info($object->id);
        $url = $zim->dolibarr_main_url_root . "/comm/propal.php?propalid=" . $res->id;
        //get Loc Zimbra
        //
        //               Var_Dump::Display($object);
        $requeteLocZim = "SELECT folder_type_refid as ftid,
                                 folder_uid as fid
                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                           WHERE folder_name='Actions'
                             AND folder_parent =( SELECT folder_uid
                                                    FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                   WHERE folder_name ='" . $object->client->nom . '-' . $object->client->id . "'
                                                     AND folder_type_refid = (SELECT id
                                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                               WHERE val='appointment'))";


        $requeteLocZim1 = false;
        if ($user->id) {
            $requeteLocZim1 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Actions'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($user->prenom . ' ' . $user->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim2 = false;
        if ($user->id) {
            $requeteLocZim2 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Propales'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($user->prenom . ' ' . $user->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim3 = false;
        if ($object->user_creation->id . "x" != "x" && $object->user_creation->id != $user->id) {
            $requeteLocZim3 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Propales'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($object->user_creation->prenom . ' ' . $object->user_creation->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim4 = false;
        if ($object->user_creation->id . "x" != "x" && $object->user_creation->id != $user->id) {
            $requeteLocZim4 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Actions'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($object->user_creation->prenom . ' ' . $object->user_creation->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim5 = false;
        if ($object->user_validation->id . "x" != "x" && $object->user_validation->id != $user->id) {
            $requeteLocZim5 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Propales'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($object->user_validation->prenom . ' ' . $object->user_validation->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim6 = false;
        if ($object->user_validation->id . "x" != "x" && $object->user_validation->id != $user->id) {
            $requeteLocZim6 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Actions'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($object->user_validation->prenom . ' ' . $object->user_validation->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim7 = false;
        if ($object->user_cloture->id . "x" != "x" && $object->user_cloture->id != $user->id) {
            $requeteLocZim7 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Propales'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($object->user_cloture->prenom . ' ' . $object->user_cloture->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim8 = false;
        if ($object->user_cloture->id . "x" != "x" && $object->user_cloture->id != $user->id) {
            $requeteLocZim8 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Actions'
                                 AND folder_parent = ( SELECT max(folder_uid) as folder_uid
                                                         FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                        WHERE folder_name ='" . trim($object->user_cloture->prenom . ' ' . $object->user_cloture->nom) . "'
                                                          AND folder_type_refid = (SELECT id
                                                                                     FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                    WHERE val='appointment'))";
        }
        $requeteLocZim9 = false;
        if ($user->id) {

            $requeteLocZim9 = "SELECT folder_type_refid as ftid,
                                     folder_uid as fid
                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                               WHERE folder_name='Propales'
                                 AND folder_parent =( SELECT folder_uid
                                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                       WHERE folder_name ='" . $object->client->nom . '-' . $object->client->id . "'
                                                         AND folder_type_refid = (SELECT id
                                                                                    FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                   WHERE val='appointment'))";
        }



        $zimLoc = false;
        $zimLoc1 = false;
        $zimLoc2 = false;
        $zimLoc3 = false;
        $zimLoc4 = false;
        $zimLoc5 = false;
        $zimLoc6 = false;
        $zimLoc7 = false;
        $zimLoc8 = false;
        $zimLoc9 = false;

        if ($resqlLocZim = $db->query($requeteLocZim)) {
            $zimRes = $db->fetch_object($resqlLocZim);
            $zimLoc = $zimRes->fid;
            $typeId = $zimRes->ftid;
        }
        if ($resqlLocZim1 = $db->query($requeteLocZim1)) {
            $zimRes1 = $db->fetch_object($resqlLocZim1);
            $zimLoc1 = $zimRes1->fid;
        }
        if ($resqlLocZim2 = $db->query($requeteLocZim2)) {
            $zimRes2 = $db->fetch_object($resqlLocZim2);
            $zimLoc2 = $zimRes2->fid;
        }
        if ($resqlLocZim3 = $db->query($requeteLocZim3)) {
            $zimRes3 = $db->fetch_object($resqlLocZim3);
            $zimLoc3 = $zimRes3->fid;
        }
        if ($resqlLocZim4 = $db->query($requeteLocZim4)) {
            $zimRes4 = $db->fetch_object($resqlLocZim4);
            $zimLoc4 = $zimRes4->fid;
        }
        if ($resqlLocZim5 = $db->query($requeteLocZim5)) {
            $zimRes5 = $db->fetch_object($resqlLocZim5);
            $zimLoc5 = $zimRes5->fid;
        }
        if ($resqlLocZim6 = $db->query($requeteLocZim6)) {
            $zimRes6 = $db->fetch_object($resqlLocZim6);
            $zimLoc6 = $zimRes6->fid;
        }
        if ($resqlLocZim7 = $db->query($requeteLocZim7)) {
            $zimRes7 = $db->fetch_object($resqlLocZim7);
            $zimLoc7 = $zimRes7->fid;
        }
        if ($resqlLocZim8 = $db->query($requeteLocZim8)) {
            $zimRes8 = $db->fetch_object($resqlLocZim8);
            $zimLoc8 = $zimRes8->fid;
        }
        if ($resqlLocZim9 = $db->query($requeteLocZim9)) {
            $zimRes9 = $db->fetch_object($resqlLocZim9);
            $zimLoc9 = $zimRes9->fid;
        }
        if ($zimLoc) {
            //si durationp => debut + fin =  si datep2 != datep => debut + fin
            $allDay = 0;
            $res = $object;
            $date = date('Y') . "-" . date('m') . "-" . date('d') . " " . date('G') . ":" . date('i') . ":00"; //2007-08-31 12:01:01
            //replace $date par now()
            $arrRes = $zim->Babel_pushDateArr(
                    $date, "Env. propale " . $object->ref . " (" . $object->client->nom . ")", "Envoie de proposition commerciale par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                    "", 1, //loc géo
                    $zimLoc, //loc zimbra
                    $url, $soc->id, $res);
            $id++;
            if ($zimLoc1) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env. propale " . $object->ref . " (" . $object->client->nom . ")", "Envoie de proposition commerciale par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc1, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc2) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env. propale " . $object->ref . " (" . $object->client->nom . ")", "Envoie de proposition commerciale par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc2, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc3) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env. propale " . $object->ref . " (" . $object->client->nom . ")", "Envoie de proposition commerciale par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc3, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc4) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env. propale " . $object->ref . " (" . $object->client->nom . ")", "Envoie de proposition commerciale par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc4, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc5) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env. propale " . $object->ref . " (" . $object->client->nom . ")", "Envoie de proposition commerciale par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc5, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc6) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env. propale " . $object->ref . " (" . $object->client->nom . ")", "Envoie de proposition commerciale par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc6, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc7) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env. propale " . $object->ref . " (" . $object->client->nom . ")", "Envoie de proposition commerciale par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc7, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc8) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env. propale " . $object->ref . " (" . $object->client->nom . ")", "Envoie de proposition commerciale par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc8, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
            if ($zimLoc9) {
                $arrRes = $zim->Babel_pushDateArr(
                        $date, "Env. propale " . $object->ref . " (" . $object->client->nom . ")", "Envoie de proposition commerciale par mail " . $object->ref . "<HR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $object->ref, $id, "llx_actioncomm_fake", $allDay, //all day
                        "", 1, //loc géo
                        $zimLoc9, //loc zimbra
                        $url, $soc->id, $res);
                $id++;
            }
        }
        //ajoute ds action et ds factures
        $remArray = array();
        while (count($zim->ApptArray) > 0) {
            $arr = array_pop($zim->ApptArray);
            $arr1 = $arr;
            //extract socid
            //Store to Db, Store to Zimbra
            $ret = $zim->createApptBabel($arr);
            // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
            $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
            $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);
            //faut aussi placer l'event dans le calendrier de la société
            $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
            $arr1['l'] = $parentId;

            $id = $arr['obj']->rowid;
            if ("x" . $id == "x") {
                $id = $arr['obj']->id;
            }
            if ($remArray[$parentId][$id]) {
                continue;
            } else {
                $remArray[$parentId][$id] = true;
                $ret1 = $zim->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

    function ExpedAction($object, $zim) {


        $db = $this->db;
        $requete = "SELECT  llx_expedition.rowid as id,
                            llx_expedition.ref,
                            llx_expedition.fk_soc,
                            llx_expedition.date_creation,
                            llx_expedition.date_valid,
                            llx_expedition.date_expedition,
                            llx_expedition.fk_user_author,
                            llx_expedition.fk_user_valid,
                            llx_expedition.fk_expedition_methode,
                            llx_expedition.fk_statut,
                            llx_expedition.note,
                            llx_expedition_methode.rowid,
                            llx_expedition_methode.code,
                            llx_expedition_methode.libelle,
                            llx_expedition_methode.description,
                            llx_societe.nom as socname,
                            llx_societe.rowid as socid,
                            llx_expedition_methode.statut
                      FROM  llx_societe, llx_expedition
                 LEFT JOIN  llx_expedition_methode llx_expedition_methode
                        ON  llx_expedition.fk_expedition_methode = llx_expedition_methode.rowid
                    WHERE llx_societe.rowid = llx_expedition.fk_soc AND llx_expedition.rowid = $object->id";

        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/expedition/fiche.php?id=" . $res->rowid;
                if ($res->date_creation) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Expeditions'
                                         AND folder_parent = ( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->date_creation, "Prise en compte de l'expedition " . "" . $res->ref . "" . " (" . $res->socname . ")", "Prise en compte de l'expedition " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_expedition", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_expedition) {
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->date_expedition, "Expedition " . "" . $res->ref . "" . " (" . $res->socname . ")", "Expedition de " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_expedition", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_valid) {
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->date_valid, "Validation de l'expedition " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Validation de l'expedition " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_expedition", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($zim->ApptArray) > 0) {
                $arr = array_pop($zim->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $zim->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
//                Var_Dump::Display($arr);
                $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $zim->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
            $this->SyncUserCal($object, $zim, 'expedition');
        }
    }

    function DIAction($object, $zim) {
        $db = $this->db;
        $requete = "SELECT llx_Synopsis_demandeInterv.rowid,
                           llx_Synopsis_demandeInterv.fk_soc,
                           llx_Synopsis_demandeInterv.fk_contrat,
                           llx_Synopsis_demandeInterv.datec,
                           llx_Synopsis_demandeInterv.date_valid,
                           llx_Synopsis_demandeInterv.datei,
                           llx_Synopsis_demandeInterv.fk_user_author,
                           llx_Synopsis_demandeInterv.fk_user_valid,
                           llx_Synopsis_demandeInterv.fk_statut,
                           llx_Synopsis_demandeInterv.description,
                           llx_Synopsis_demandeInterv.note_private,
                           llx_Synopsis_demandeInterv.note_public,
                           llx_societe.nom as socname,
                           llx_societe.rowid as socid
                      FROM llx_Synopsis_demandeInterv, llx_societe
                     WHERE llx_societe.rowid = llx_Synopsis_demandeInterv.fk_soc
                       AND llx_Synopsis_demandeInterv.rowid = " . $object->id;
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/Synopsis_DemandeInterv/fiche.php?id=" . $res->rowid;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Interventions'
                                         AND folder_parent = ( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";
                    //get Loc Zimbra
                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->datec, "Créat. la DI  " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la demande d'intervention " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "llx_Synopsis_demandeInterv", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->datei) {
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->datei, "DI " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Demande d'intervention " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "llx_Synopsis_demandeInterv", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_valid) {
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->date_valid, "Valid. de la DI " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Validation de la demande intervention " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "llx_Synopsis_demandeInterv", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($zim->ApptArray) > 0) {
                $arr = array_pop($zim->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
//    var_dump($zim->connected());
                if ($zim->connected()) {
                    $ret = $zim->createApptBabel($arr);
                    // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
                    //                $parent = $arr['l'];
                    $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                    $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                    //faut aussi placer l'event dans le calendrier de la société
                    $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                    $arr1['l'] = $parentId;
                    $ret1 = $zim->createApptBabel($arr1);
                    $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                    $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
                }
            }
            if ($zim->connected())
                $this->SyncUserCal($object, $zim, 'DI');
            //var_dump("toto");
        }
    }

    function LivraisonAction($object, $zim) {
        $db = $this->db;
        $requete = "SELECT  llx_livraison.rowid as id,
                            llx_livraison.ref,
                            llx_livraison.fk_soc,
                            llx_livraison.date_creation,
                            llx_livraison.date_valid,
                            llx_livraison.date_livraison,
                            llx_livraison.fk_user_author,
                            llx_livraison.fk_user_valid,
                            llx_livraison.fk_statut,
                            llx_livraison.note,
                            llx_societe.nom as socname,
                            llx_societe.rowid as socid
                      FROM  llx_societe, llx_livraison
                     WHERE llx_societe.rowid = llx_livraison.fk_soc AND llx_livraison.rowid = $object->id";

        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/livraison/fiche.php?id=" . $res->rowid;
                if ($res->date_creation) {
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Expeditions'
                                         AND folder_parent = ( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->date_creation, "Prise en compte de la livraison " . "" . $res->ref . "" . " (" . $res->socname . ")", "Prise en compte de la livraison " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_livraison", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_livraison) {
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->date_livraison, "livraison " . "" . $res->ref . "" . " (" . $res->socname . ")", "livraison de " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_livraison", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_valid) {
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->date_valid, "Validation de la livraison " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Validation de la livraison " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "llx_livraison", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($zim->ApptArray) > 0) {
                $arr = array_pop($zim->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $zim->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $zim->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
            $this->SyncUserCal($object, $zim, 'livraison');
        }
    }

    function ContratAction($object, $zim) {
        $db = $this->db;

        $requete = "SELECT llx_contrat.rowid,
                           llx_contrat.fk_soc,
                           llx_contrat.ref,
                           llx_contrat.datec,
                           llx_contrat.date_contrat,
                           llx_contrat.date_valid,
                           llx_contrat.mise_en_service,
                           llx_contrat.fin_validite,
                           llx_contrat.date_cloture,
                           llx_societe.nom as socname,
                           llx_societe.rowid as socid
                      FROM llx_contrat,
                           llx_societe
                     WHERE llx_societe.rowid = llx_contrat.fk_soc AND llx_contrat.rowid = " . $object->id;
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/contrat/fiche.php?id=" . $res->rowid;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Contrats'
                                         AND folder_parent = ( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";
                    //get Loc Zimbra
                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->datec, "Créat. du contrat " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cr&eacute;ation du contrat " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "llx_contrat", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_contrat) {
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->date_contrat, "Contrat " . "" . $res->ref . "" . " (" . $res->socname . ")", "Contrat " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "llx_contrat", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->mise_en_service) {
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->mise_en_service, "Mise en serv. du contrat " . "" . $res->ref . "" . " (" . $res->socname . ")", "Mise en service du contrat " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "llx_contrat", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->fin_validite) {
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->fin_validite, "Fin de val. du contrat " . "" . $res->ref . "" . " (" . $res->socname . ")", "Fin de validit&eacute; du contrat  " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "llx_contrat", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_valid) {
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->date_valid, "Valid. du contrat " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validitation du contrat  " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "llx_contrat", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_cloture) {
                        $arrRes = $zim->Babel_pushDateArr(
                                $res->date_cloture, "Cl&ocirc;ture contrat " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cl&ocirc;ture du contrat " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "llx_contrat", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }

                //Contrat det
                $this->ContratDetAction($object, $zim);
            }
            while (count($zim->ApptArray) > 0) {
                $arr = array_pop($zim->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $zim->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $zim->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
            $this->SyncUserCal($object, $zim, 'Contrat');
        }
    }

    function ContratDetAction($object, $zim) { //obj is contrat obj
        $db = $this->db;

        $requete = "SELECT llx_contrat.rowid,
                           llx_contratdet.date_commande,
                           llx_contratdet.label as ref,
                           llx_contratdet.date_ouverture_prevue,
                           llx_contratdet.date_ouverture,
                           llx_contratdet.date_fin_validite as date_detfinvalid,
                           llx_contratdet.date_cloture as date_detcloture
                      FROM llx_contrat,
                           llx_contratdet,
                           llx_societe
                     WHERE llx_contratdet.fk_contrat = llx_contrat.rowid
                       AND llx_societe.rowid = llx_contrat.fk_soc
                       AND llx_contrat.rowid = " . $object->id;
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/contrat/fiche.php?id=" . $res->rowid;
                if ($res->date_ouverture_prevue || $res->date_ouverture) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Contrats'
                                         AND folder_parent = ( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";
                    //get Loc Zimbra
                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;

                        if ($res->date_ouverture) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    $res->date_ouverture, "Ouv du serv " . "" . $res->ref . "" . " (" . $res->socname . ")", "Ouverture du service " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "llx_contratdet", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        } else {
                            $arrRes = $zim->Babel_pushDateArr(
                                    $res->date_ouverture_prevue, "Ouv du serv. prev" . "" . $res->ref . "" . " (" . $res->socname . ")", "Ouverture pr&eacute;visonnelle du service" . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "llx_contratdet", 1, //all day
                                    "", 1, //loc géo
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                        if ($res->date_commande) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    $res->date_commande, "Com. du serv " . "" . $res->ref . "" . " (" . $res->socname . ")", "Commande du service " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "llx_contratdet", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }

                        if ($res->date_detfinvalid) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    $res->date_detfinvalid, "Fin de val. du serv. " . "" . $res->ref . "" . " (" . $res->socname . ")", "Fin de validat&eacute; du service " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "llx_contratdet", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                        if ($res->date_detcloture) {
                            $arrRes = $zim->Babel_pushDateArr(
                                    $res->date_detcloture, "Cl&ocirc;t. du  serv. " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cl&ocirc;ture du service " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "llx_contratdet", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                    }
                }
            }
            while (count($zim->ApptArray) > 0) {
                $arr = array_pop($zim->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $zim->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $zim->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $zim->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $zim->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
            $this->SyncUserCal($object, $zim, 'contratDet');
        }
    }

    function AffaireAction($object, $zim, $user) {
        //Si y'a pas le calendrier "Affaire"
        //Create Calendar "Affaire"
        //Create Calendar de l'affaire
    }

    function AffaireSubAction($object, $zim, $user) {
        //Trouve le parent folder
        //Add Event Calendar
    }

}

?>
