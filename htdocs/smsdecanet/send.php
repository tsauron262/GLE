<?php

/* Copyright (C) 2012 Maxime MANGIN <maxime@tuxserv.fr>
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

/**
 *  \file       htdocs/contratabonnement/admin/contratabonnement_conf.php
 *  \ingroup    produit
 *  \brief      Page d'administration/configuration du module contrat d'abonnement
 */
require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/smsdecanet/core/modules/modSmsDecanet.class.php");

$langs->load("admin");
$langs->load("sms");
$langs->load("smsdecanet@smsdecanet");

// Security check
if (!$user->admin)
    accessforbidden();

$action = GETPOST('action');


if ($action == 'send' && !$_POST['cancel']) {
    $error = 0;

    $smsfrom = '';
    if (!empty($_POST["fromsms"]))
        $smsfrom = GETPOST("fromsms");
    if (empty($smsfrom))
        $smsfrom = GETPOST("fromname");
    $sendto = GETPOST("sendto");
    $body = GETPOST('message');
    $deliveryreceipt = GETPOST("deliveryreceipt");
    $deferred = GETPOST('deferred');
    $priority = GETPOST('priority');
    $class = GETPOST('class');
    $errors_to = GETPOST("errorstosms");

    // Create form object
    include_once(DOL_DOCUMENT_ROOT . '/core/class/html.formsms.class.php');
    $formsms = new FormSms($db);

    if (!empty($formsms->error)) {
        $message = '<div class="error">' . $formsms->error . '</div>';
        $action = 'test';
        $error++;
    }
    if (empty($body)) {
        $message = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->transnoentities("Message")) . '</div>';
        $action = 'test';
        $error++;
    }
    if (empty($smsfrom) || !str_replace('+', '', $smsfrom)) {
        $message = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->transnoentities("SmsFrom")) . '</div>';
        $action = 'test';
        $error++;
    }
    if (empty($sendto) || !str_replace('+', '', $sendto)) {
        $message = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->transnoentities("SmsTo")) . '</div>';
        $action = 'test';
        $error++;
    }
    if (!$error) {
        require_once(DOL_DOCUMENT_ROOT . "/core/class/CSMSFile.class.php");

        $smsfile = new CSMSFile($sendto, $smsfrom, $body, $deliveryreceipt, $deferred, $priority, $class);
        $result = $smsfile->sendfile();
        if ($result > 0) {
            $message = '<div class="ok">' . $langs->trans("SmsSuccessfulySent", $smsfrom, $sendto) . '</div>';
        } else {
            $message = '<div class="error">' . $langs->trans("ResultKo") . '<br>' . $smsfile->error . ' ' . $result . '</div>';
        }

        $action = '';
    }
}

llxHeader('', $langs->trans("SendSMS"));
dol_htmloutput_mesg($message);
$tabPrefPays[0] = "33";
$to = '+'.$tabPrefPays[0];
$socid = intval($_GET['id']);
if(isset($_REQUEST['to'])){
    $phone = $_REQUEST['to'];
    $idPays = 0;
}
else if ($socid > 0) {
    $soc = new Societe($db);
    $soc->fetch($socid);
    $soc->info($socid);
    $phone = $soc->phone;
    $idPays = $soc->state_id;
}
if (isset($phone) && $phone != "") {
    if (substr($phone, 0, 1) != '+')
        $toT = '+' . $tabPrefPays[$idPays] . '' . substr($phone, 1);
    else
        $toT = $phone;
    
    if(strlen($toT) == 12 && (stripos($toT, "+".$tabPrefPays[$idPays]."6") === 0 || stripos($toT, "+".$tabPrefPays[$idPays]."7") === 0))
            $to = $toT;
}

$fromPerso = (isset($_REQUEST['fromsms']) && $_REQUEST['fromsms'] != "");


$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans("SendSMS"), false, 'setup');
include_once(DOL_DOCUMENT_ROOT . "/core/class/html.formsms.class.php");
$formsms = new FormSms($db);
$formsms->fromtype = ($fromPerso ? 'perso' : 'user');
$formsms->fromid = $user->id;
$formsms->fromsms = ($fromPerso ? $_REQUEST['fromsms'] : ($conf->global->MAIN_MAIL_SMS_FROM ? $conf->global->MAIN_MAIL_SMS_FROM : $user->user_mobile));
$formsms->withfromreadonly = $fromPerso;
$formsms->withto = $to;
$formsms->withsubstit = 0;
$formsms->withfrom = 1;
$formsms->witherrorsto = 1;
$formsms->withfile = 2;
$formsms->withbody = (isset($_REQUEST['msg']) && $_REQUEST['msg'] != "" ? $_REQUEST['msg'] : $langs->trans("yourMessage"));
$formsms->withbodyreadonly = 0;
$formsms->withcancel = 0;
$formsms->withfckeditor = 0;
// Tableau des parametres complementaires du post
$formsms->param["action"] = "send";
$formsms->param["models"] = "body";
$formsms->param["smsid"] = 0;
$formsms->param["returnurl"] = $_SERVER['REQUEST_URI'];

$formsms->show_form();
$db->close();


llxFooter('$Date: 2010/03/10 15:00:00');
