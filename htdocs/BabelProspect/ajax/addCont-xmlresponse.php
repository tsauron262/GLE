<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 7-23-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : addCont-xmlresponse.php
  * GLE-1.1
  */
  require_once('../../main.inc.php');
  require_once(DOL_DOCUMENT_ROOT.'/contact.class.php');


  //prend les info,
  // cree le contact
  $socid = ($_REQUEST['socid']."x"=="x"?"":$_REQUEST['socid']);
  $civil = ($_REQUEST['Civil']."x"=="x"?"":$_REQUEST['Civil']);
  $Nom = ($_REQUEST['Nom']."x"=="x"?"":$_REQUEST['Nom']);
  $gsm = ($_REQUEST['GSM']."x"=="x"?"":$_REQUEST['GSM']);
  $tel = ($_REQUEST['TEL']."x"=="x"?"":$_REQUEST['TEL']);
  $prenom = ($_REQUEST['Prenom']."x"=="x"?"":$_REQUEST['Prenom']);
  $note = ($_REQUEST['note']."x"=="x"?"":$_REQUEST['note']);
  $userid = ($_REQUEST['userid']."x"=="x"?"":$_REQUEST['userid']);
  $email = ($_REQUEST['email']."x"=="x"?"":$_REQUEST['email']);

    if ($userid)
    {
        $user = new User($db);
        $user->fetch($userid);
        $contact = new Contact($db);
        $contact->socid = $socid;
        $contact->name = $Nom;
        $contact->phone_mobile = $gsm;
        $contact->phone_pro = $tel;
        $contact->email=$email;
        $contact->civility_id = $civil;
        $contact->firstname = $prenom;
        $contact->note = $note;
        $contact->create($user);

    }




?>
