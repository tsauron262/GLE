<?php

/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011      Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Marcos García        <marcosgdf@gmail.com>
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

/**
 *  \file       htdocs/core/triggers/interface_50_modNotification_Notification.class.php
 *  \ingroup    notification
 *  \brief      File of class of triggers for notification module
 */

/**
 *  Class of triggers for notification module
 */
class InterfaceNotificationUser {

    var $db;
    var $listofmanagedevents = array(
        ''
    );

    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function __construct($db) {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "Synopsis";
        $this->description = "Triggers of this module send email notifications according to Notification module setup.";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
        $this->picto = 'email';
    }

    /**
     *   Return name of trigger file
     *
     *   @return     string      Name of trigger file
     */
    function getName() {
        return $this->name;
    }

    /**
     *   Return description of trigger file
     *
     *   @return     string      Description of trigger file
     */
    function getDesc() {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *
     *   @return     string      Version of trigger file
     */
    function getVersion() {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'experimental')
            return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr')
            return DOL_VERSION;
        elseif ($this->version)
            return $this->version;
        else
            return $langs->trans("Unknown");
    }

    function replaceTextMail($str, $object) {
        $str = str_replace("[ELEM]", $object->getNomUrl(1), $str);
        if (isset($object->name))
            $str = str_replace("[NAME]", $object->name, $str);
        if (isset($object->firstname) && isset($object->lastname))
            $str = str_replace("[NAME]", $object->firstname . " " . $object->lastname, $str);
        if (isset($object->ref))
            $str = str_replace("[REF]", $object->ref, $str);
        if (method_exists($object, "getLibStatut"))
            $str = str_replace("[STATUT]", $object->getLibStatut(1), $str);
        return $str;
    }

    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
     *
     *      @param	string		$action		Event action code
     *      @param  Object		$object     Object
     *      @param  User		$user       Object user
     *      @param  Translate	$langs      Object langs
     *      @param  conf		$conf       Object conf
     *      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
     */
    function run_trigger($action, $object, $user, $langs, $conf) {
        $sql = $this->db->query("SELECT nu.* FROM " . MAIN_DB_PREFIX . "synopsistools_notificationUser nu, " . MAIN_DB_PREFIX . "Synopsis_trigger t WHERE fk_trigger = t.id AND nu.active = 1 AND t.code LIKE '" . $action . "'");
        if ($this->db->num_rows($sql) > 0) {
            while ($result = $this->db->fetch_object($sql)) {
                $sujet = $this->replaceTextMail($result->sujet, $object);
                $message = $this->replaceTextMail($result->message, $object);
                $to = $result->mailTo;
                if ($to != '' && $result->fk_type_contact > 0)
                    $to .= ", ";
                if ($result->fk_type_contact > 0) {
                    $tabMail = array();
                    $fk_soc = 0;
                    if (isset($object->fk_soc) && $object->fk_soc > 0)
                        $fk_soc = $object->fk_soc;
                    elseif (get_class($object) == "Societe")
                        $fk_soc = $object->id;

                    if ($result->fk_type_contact == 1001 && $fk_soc)
                        $req = "SELECT u.email FROM " . MAIN_DB_PREFIX . "user u, " . MAIN_DB_PREFIX . "societe_commerciaux ec WHERE ec.fk_user = u.rowid AND fk_soc =" . $fk_soc;
                    elseif ($result->fk_type_contact == 1002 && $fk_soc)
                        $req = "SELECT u.email FROM " . MAIN_DB_PREFIX . "user u, " . MAIN_DB_PREFIX . "element_element ec WHERE ec.fk_target = u.rowid AND sourcetype = 'soc' AND targettype = 'userTech' AND fk_source =" . $fk_soc;
                    elseif ($result->fk_type_contact == 1003 && isset($object->fk_user_prisencharge) && $object->fk_user_prisencharge > 0)
                        $req = "SELECT u.email FROM " . MAIN_DB_PREFIX . "user u WHERE u.rowid =" . $object->fk_user_prisencharge;
                    elseif ($result->fk_type_contact == 1003 && isset($object->fk_user_author) && $object->fk_user_author > 0)
                        $req = "SELECT u.email FROM " . MAIN_DB_PREFIX . "user u WHERE u.rowid =" . $object->fk_user_author;
                    elseif ($result->fk_type_contact == 1004 && isset($object->id) && isset($object->model_refid))
                        $req = "SELECT u.email FROM " . MAIN_DB_PREFIX . "user u WHERE u.rowid IN (SELECT `technicien` FROM `llx_synopsischrono_chrono_105` WHERE `id = ".$object->id."";
                    else
                        $req = "SELECT u.email FROM " . MAIN_DB_PREFIX . "user u, " . MAIN_DB_PREFIX . "element_contact ec WHERE ec.fk_socpeople = u.rowid AND element_id =" . $object->id . " AND fk_c_type_contact =" . $result->fk_type_contact;
                    $sql2 = $this->db->query($req);
                    while ($result2 = $this->db->fetch_object($sql2))
                        $tabMail[] = $result2->email;
                    $to .= implode(',', $tabMail);
                }
                $from = $from = 'Application GLE ' . MAIN_INFO_SOCIETE_NOM . ' <gle@' . str_replace(" ", "", MAIN_INFO_SOCIETE_NOM) . '.fr>';
                mailSyn2($sujet, $to, $from, $message);
            }
        }
//		if (empty($conf->notification->enabled)) return 0;     // Module not active, we do nothing
//
//		require_once DOL_DOCUMENT_ROOT .'/core/class/notify.class.php';
//
//		if ($action == 'BILL_VALIDATE')
//		{
//            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
//
//            $ref = dol_sanitizeFileName($object->ref);
//            $filepdf = $conf->facture->dir_output . '/' . $ref . '/' . $ref . '.pdf';
//            if (! file_exists($filepdf)) $filepdf='';
//            $filepdf='';	// We can't add PDF as it is not generated yet.
//            $langs->load("other");
//			$mesg = $langs->transnoentitiesnoconv("EMailTextInvoiceValidated",$object->ref);
//
//            $notify = new Notify($this->db);
//            $notify->send($action, $object->socid, $mesg, 'facture', $object->id, $filepdf);
//		}
//
//		elseif ($action == 'ORDER_VALIDATE')
//		{
//            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
//
//            $ref = dol_sanitizeFileName($object->ref);
//            $filepdf = $conf->commande->dir_output . '/' . $ref . '/' . $ref . '.pdf';
//            if (! file_exists($filepdf)) $filepdf='';
//            $filepdf='';	// We can't add PDF as it is not generated yet.
//            $langs->load("other");
//			$mesg = $langs->transnoentitiesnoconv("EMailTextOrderValidated",$object->ref);
//
//            $notify = new Notify($this->db);
//            $notify->send($action, $object->socid, $mesg, 'order', $object->id, $filepdf);
//		}
//
//		elseif ($action == 'PROPAL_VALIDATE')
//		{
//            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
//
//            $ref = dol_sanitizeFileName($object->ref);
//            $filepdf = $conf->propal->dir_output . '/' . $ref . '/' . $ref . '.pdf';
//            if (! file_exists($filepdf)) $filepdf='';
//            $filepdf='';	// We can't add PDF as it is not generated yet.
//            $langs->load("other");
//			$mesg = $langs->transnoentitiesnoconv("EMailTextProposalValidated",$object->ref);
//
//            $notify = new Notify($this->db);
//            $notify->send($action, $object->socid, $mesg, 'propal', $object->id, $filepdf);
//		}
//
//		elseif ($action == 'FICHINTER_VALIDATE')
//		{
//            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
//
//            $ref = dol_sanitizeFileName($object->ref);
//            $filepdf = $conf->facture->dir_output . '/' . $ref . '/' . $ref . '.pdf';
//            if (! file_exists($filepdf)) $filepdf='';
//            $filepdf='';	// We can't add PDF as it is not generated yet.
//            $langs->load("other");
//			$mesg = $langs->transnoentitiesnoconv("EMailTextInterventionValidated",$object->ref);
//
//            $notify = new Notify($this->db);
//            $notify->send($action, $object->socid, $mesg, 'ficheinter', $object->id, $filepdf);
//		}
//
//		elseif ($action == 'ORDER_SUPPLIER_APPROVE')
//		{
//            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
//
//            $ref = dol_sanitizeFileName($object->ref);
//            $filepdf = $conf->fournisseur->dir_output . '/commande/' . $ref . '/' . $ref . '.pdf';
//            if (! file_exists($filepdf)) $filepdf='';
//            $mesg = $langs->transnoentitiesnoconv("Hello").",\n\n";
//			$mesg.= $langs->transnoentitiesnoconv("EMailTextOrderApprovedBy",$object->ref,$user->getFullName($langs));
//			$mesg.= "\n\n".$langs->transnoentitiesnoconv("Sincerely").".\n\n";
//
//            $notify = new Notify($this->db);
//            $notify->send($action, $object->socid, $mesg, 'order_supplier', $object->id, $filepdf);
//		}
//
//		elseif ($action == 'ORDER_SUPPLIER_REFUSE')
//		{
//            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
//
//            $ref = dol_sanitizeFileName($object->ref);
//            $filepdf = $conf->fournisseur->dir_output . '/commande/' . $ref . '/' . $ref . '.pdf';
//            if (! file_exists($filepdf)) $filepdf='';
//			$mesg = $langs->transnoentitiesnoconv("Hello").",\n\n";
//			$mesg.= $langs->transnoentitiesnoconv("EMailTextOrderRefusedBy",$object->ref,$user->getFullName($langs));
//			$mesg.= "\n\n".$langs->transnoentitiesnoconv("Sincerely").".\n\n";
//
//            $notify = new Notify($this->db);
//            $notify->send($action, $object->socid, $mesg, 'order_supplier', $object->id, $filepdf);
//		}
//        elseif ($action == 'SHIPPING_VALIDATE')
//        {
//            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
//
//            $ref = dol_sanitizeFileName($object->ref);
//            $filepdf = $conf->expedition->dir_output . '/sending/' . $ref . '/' . $ref . '.pdf';
//            if (! file_exists($filepdf)) $filepdf='';
//            $mesg = $langs->transnoentitiesnoconv("EMailTextExpeditionValidated",$object->ref);
//
//
//            $notify = new Notify($this->db);
//            $notify->send($action, $object->socid, $mesg, 'expedition', $object->id, $filepdf);
//        }
        // If not found
        /*
          else
          {
          dol_syslog("Trigger '".$this->name."' for action '$action' was ran by ".__FILE__." but no handler found for this action.");
          return -1;
          }
         */
        return 0;
    }

    /**
     * Return list of events managed by notification module
     *
     * @return      array       Array of events managed by notification module
     */
    function getListOfManagedEvents() {
        global $conf, $langs;

        $ret = array();

        $sql = "SELECT rowid, code, label, description, elementtype";
        $sql.= " FROM " . MAIN_DB_PREFIX . "c_action_trigger";
        $sql.= $this->db->order("elementtype, code");
        dol_syslog("Get list of notifications sql=" . $sql);
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);

                $qualified = 0;
                // Check is this event is supported by notification module
                if (in_array($obj->code, $this->listofmanagedevents))
                    $qualified = 1;
                // Check if module for this event is active
                if ($qualified) {
                    //print 'xx'.$obj->code;
                    $element = $obj->elementtype;
                    if ($element == 'order_supplier' && empty($conf->fournisseur->enabled))
                        $qualified = 0;
                    elseif ($element == 'invoice_supplier' && empty($conf->fournisseur->enabled))
                        $qualified = 0;
                    elseif ($element == 'withdraw' && empty($conf->prelevement->enabled))
                        $qualified = 0;
                    elseif ($element == 'shipping' && empty($conf->expedition->enabled))
                        $qualified = 0;
                    elseif ($element == 'member' && empty($conf->adherent->enabled))
                        $qualified = 0;
                    elseif (!in_array($element, array('order_supplier', 'invoice_supplier', 'withdraw', 'shipping', 'member')) && empty($conf->$element->enabled))
                        $qualified = 0;
                }

                if ($qualified) {
                    $ret[] = array('rowid' => $obj->rowid, 'code' => $obj->code, 'label' => $obj->label, 'description' => $obj->description, 'elementtype' => $obj->elementtype);
                }

                $i++;
            }
        } else
            dol_print_error($this->db);

        return $ret;
    }

}

?>
