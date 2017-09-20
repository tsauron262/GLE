<?php

/* Copyright (C) 2000-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * BIMP-ERP by DRSI & Synopsis
 *
 * Author: SAURON Tommy <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.synopsis-erp.com
 *
 */
/*
 * or see http://www.gnu.org/
 *
 * Lots of code inspired from Dan Potter's CMailFile class
 */

/**
 *       \file       htdocs/lib/CMailFile.class.php
 *       \brief      Fichier de la classe permettant d'envoyer des mail avec attachements
 *    \version    $Id: CMailFile.class.php,v 1.60 2008/08/20 19:21:16 eldy Exp $
 *       \author     Dan Potter.
 *       \author        Eric Seigne
 *       \author        Laurent Destailleur.
 */

/**
 *       \class      CMailFile
 *       \brief      Classe d'envoi de mails et pieces jointes. Encapsule mail() avec d'eventuels attachements.
 *       \remarks    Usage: $mailfile = new CMailFile($subject,$sendto,$replyto,$message,$filepath,$mimetype,$filename,$cc,$ccc,$deliveryreceipt,$msgishtml,$errors_to);
 *       \remarks           $mailfile->sendfile();
 */
class CMailFile {

    public $subject;
    public $addr_from;
    public $errors_to;
    public $addr_to;
    public $addr_cc;
    public $addr_bcc;
    public $mime_boundary;
    public $deliveryreceipt;
    public $eol;
    public $atleastonefile = 0;
    public $error = '';

    /**
      \brief     CMailFile
      \param     subject             sujet
      \param     to                  email destinataire (RFC 2822: "Nom prenom <email>[, ...]" ou "email[, ...]" ou "<email>[, ...]")
      \param     from                email emetteur     (RFC 2822: "Nom prenom <email>[, ...]" ou "email[, ...]" ou "<email>[, ...]")
      \param     msg                 message
      \param     filename_list       tableau de fichiers attaches
      \param     mimetype_list       tableau des types des fichiers attaches
      \param     mimefilename_list   tableau des noms des fichiers attaches
      \param     addr_cc             email cc
      \param     addr_bcc            email bcc
      \param     deliveryreceipt        demande accuse reception
      \param    msgishtml            1=String IS already html, 0=String IS NOT html, -1=Unknown need autodetection
     */
    function CMailFile($subject, $to, $from, $msg, $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array(), $addr_cc = "", $addr_bcc = "", $deliveryreceipt = 0, $msgishtml = 0, $errors_to = '') {
        global $conf;
        if (isset($conf->global->devMailTo))
            $to = $conf->global->devMailTo;
        dol_syslog("CMailFile::CMailfile: from=$from, to=$to, addr_cc=$addr_cc, addr_bcc=$addr_bcc, errors_to=$errors_to");
        dol_syslog("CMailFile::CMailfile: subject=$subject, deliveryreceipt=$deliveryreceipt, msgishtml=$msgishtml");
        // Define if there is at least one file
        foreach ($filename_list as $i => $val) {
            if ($filename_list[$i]) {
                $this->atleastonefile = 1;
                dol_syslog("CMailFile::CMailfile: filename_list[$i]=" . $filename_list[$i] . ", mimetype_list[$i]=" . $mimetype_list[$i] . " mimefilename_list[$i]=" . $mimefilename_list[$i]);
            }
        }

        // On defini mime_boundary
        $this->mime_boundary = md5(uniqid("dolibarr"));

        // On definit fin de ligne
        $this->eol = "\n";
        if (preg_match('/^win/i', PHP_OS))
            $this->eol = "\r\n";
        if (preg_match('/^mac/i', PHP_OS))
            $this->eol = "\r";

        // Detect if message is HTML (use fast method)
        if ($msgishtml == -1) {
            $this->msgishtml = 0;
            if (dol_textishtml($msg, 1))
                $this->msgishtml = 1;
        }
        else {
            $this->msgishtml = $msgishtml;
        }

        $smtp_headers = "";
        $mime_headers = "";
        $text_body = "";
        $text_encoded = "";

        // En-tete dans $smtp_headers
        $this->subject = $subject;
        $this->addr_from = $from;
        $this->errors_to = $errors_to;
        $this->addr_to = $to;
        $this->addr_cc = $addr_cc;
        $this->addr_bcc = $addr_bcc;
        $this->deliveryreceipt = $deliveryreceipt;
        $smtp_headers = $this->write_smtpheaders();

        // En-tete suite dans $mime_headers
        if ($this->atleastonefile) {
            $mime_headers = $this->write_mimeheaders($filename_list, $mimefilename_list);
        }

        // Corps message dans $text_body
        $text_body = $this->write_body($msg, $filename_list);

        // Corps message suite (fichiers attaches) dans $text_encoded
        if ($this->atleastonefile) {
            $text_encoded = $this->write_files($filename_list, $mimetype_list, $mimefilename_list);
        }

        // On defini $this->headers et $this->message
        $this->headers = $smtp_headers . $mime_headers;
        $this->message = $text_body . $text_encoded;

        // On nettoie le header pour qu'il ne se termine pas par un retour chariot.
        // Ceci evite aussi les lignes vides en fin qui peuvent etre interpretees
        // comme des injections mail par les serveurs de messagerie.
        $this->headers = preg_replace("/[\r\n]+$/", "", $this->headers);
    }

    /**
      \brief      Permet d'encoder un fichier
      \param      sourcefile
      \return     <0 si erreur, fichier encode si ok
     */
    function _encode_file($sourcefile) {
        if (is_readable($sourcefile)) {
            $fd = fopen($sourcefile, "r");
            $contents = fread($fd, filesize($sourcefile));
            $encoded = chunk_split(base64_encode($contents), 68, $this->eol);
            fclose($fd);
            return $encoded;
        } else {
            $this->error = "Error: Can't read file '$sourcefile'";
            dol_syslog("CMailFile::encode_file: " . $this->error);
            return -1;
        }
    }

    /**
      \brief     Envoi le mail
      \return    boolean     true si mail envoye, false sinon
     */
    function sendfile() {
        global $conf;

        dol_syslog("CMailFile::sendfile addr_to=" . $this->addr_to . ", subject=" . $this->subject);
        dol_syslog("CMailFile::sendfile header=\n" . $this->headers);
        //dol_syslog("CMailFile::sendfile message=\n".$message);
        //$this->dump_mail();

        $errorlevel = error_reporting();
        error_reporting($errorlevel ^ E_WARNING);   // Desactive warnings

        $res = false;

        if (!$conf->global->MAIN_DISABLE_ALL_MAILS) {
            // Si Windows, addr_from doit obligatoirement etre defini
            if (isset($_SERVER["WINDIR"])) {
                if (empty($this->addr_from))
                    $this->addr_from = 'robot@mydomain.com';
                @ini_set('sendmail_from', getValidAddress($this->addr_from, 2));
            }

            // Forcage parametres
            if (!empty($conf->global->MAIN_MAIL_SMTP_SERVER))
                ini_set('SMTP', $conf->global->MAIN_MAIL_SMTP_SERVER);
            if (!empty($conf->global->MAIN_MAIL_SMTP_PORT))
                ini_set('smtp_port', $conf->global->MAIN_MAIL_SMTP_PORT);

            $dest = getValidAddress($this->addr_to, 2);
            if (!$dest) {
                $this->error = "Failed to send mail to SMTP=" . ini_get('SMTP') . ", PORT=" . ini_get('smtp_port') . "<br>Recipient address '$dest' invalid";
                dol_syslog("CMailFile::sendfile: mail end error=" . $this->error);
            } else {
                dol_syslog("CMailFile::sendfile: mail start SMTP=" . ini_get('SMTP') . ", PORT=" . ini_get('smtp_port'));
                //dol_syslog("to=".getValidAddress($this->addr_to,2).", subject=".$this->subject.", message=".stripslashes($this->message).", header=".$this->headers);

                $bounce = '';
                if ($conf->global->MAIN_MAIL_ALLOW_SENDMAIL_F) {
                    // le return-path dans les header ne fonctionne pas avec tous les MTA
                    // Le passage par -f est donc possible si la constante MAIN_MAIL_ALLOW_SENDMAIL_F est definie.
                    // La variable definie pose des pb avec certains sendmail securisee (option -f refusee car dangereuse)
                    $bounce = $this->addr_from != '' ? "-f {$this->addr_from}" : "";
                }

                $res = mail($dest, $this->subject, stripslashes($this->message), $this->headers, $bounce);

                if (!$res) {
                    $this->error = "Failed to send mail to SMTP=" . ini_get('SMTP') . ", PORT=" . ini_get('smtp_port') . "<br>Check your server logs and your firewalls setup";
                    dol_syslog("CMailFile::sendfile: mail end error=" . $this->error);
                } else {
                    dol_syslog("CMailFile::sendfile: mail end success");
                }
            }

            if (isset($_SERVER["WINDIR"])) {
                @ini_restore('sendmail_from');
            }

            // Forcage parametres
            if (!empty($conf->global->MAIN_MAIL_SMTP_SERVER))
                ini_restore('SMTP');
            if (!empty($conf->global->MAIN_MAIL_SMTP_PORT))
                ini_restore('smtp_port');
        }
        else {
            $this->error = 'No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
            dol_syslog("CMailFile::sendfile: " . $this->error);
        }

        error_reporting($errorlevel);              // Reactive niveau erreur origine

        return $res;
    }

    /**
     *    \brief  Ecrit le mail dans un fichier. Utilisation pour le debuggage.
     */
    function dump_mail() {
        global $dol_main_data_root;

        if (@is_writeable($dol_main_data_root)) {    // Avoid fatal error on fopen with open_basedir
            $fp = fopen($dol_main_data_root . "/dol_mail", "w");
            fputs($fp, $this->headers);
            fputs($fp, $this->eol);            // This eol is added by the mail function, so we add it in log
            fputs($fp, $this->message);
            fclose($fp);
        }
    }

    /**
      \brief        Creation des headers smtp
     */
    function write_smtpheaders() {
        global $conf;
        $out = "";

        // Sender
        //$out .= "X-Sender: ".getValidAddress($this->addr_from,2).$this->eol;
        $out .= "From: " . getValidAddress($this->addr_from, 0) . $this->eol;
        $out .= "Return-Path: " . getValidAddress($this->addr_from, 0) . $this->eol;
        if (isset($this->reply_to) && $this->reply_to)
            $out .= "Reply-To: " . getValidAddress($this->reply_to, 2) . $this->eol;
        if (isset($this->errors_to) && $this->errors_to)
            $out .= "Errors-To: " . getValidAddress($this->errors_to, 2) . $this->eol;

        // Receiver
        if (isset($this->addr_cc) && $this->addr_cc)
            $out .= "Cc: " . getValidAddress($this->addr_cc, 2) . $this->eol;
        if (isset($this->addr_bcc) && $this->addr_bcc)
            $out .= "Bcc: " . getValidAddress($this->addr_bcc, 2) . $this->eol;

        // Accuse reception
        if (isset($this->deliveryreceipt) && $this->deliveryreceipt == 1)
            $out .= "Disposition-Notification-To: " . getValidAddress($this->addr_from, 2) . $this->eol;

        //$out .= "X-Priority: 3".$this->eol;
        $out .= "X-Mailer: Dolibarr version " . DOL_VERSION . $this->eol;
        $out .= "MIME-Version: 1.0" . $this->eol;

        if ($this->msgishtml) {
            if (!$this->atleastonefile)
                $out.= "Content-Type: text/html; charset=" . $conf->character_set_client . $this->eol;
            $out.= "Content-Transfer-Encoding: 8bit" . $this->eol;
        }
        else {
            if (!$this->atleastonefile)
                $out.= "Content-Type: text/plain; charset=" . $conf->character_set_client . $this->eol;
            $out.= "Content-Transfer-Encoding: 8bit" . $this->eol;
        }

        dol_syslog("CMailFile::write_smtpheaders smtp_header=\n" . $out);
        return $out;
    }

    /**
      \brief         Creation header MIME
      \param         filename_list
      \param         mimefilename_list
     */
    function write_mimeheaders($filename_list, $mimefilename_list) {
        $mimedone = 0;
        $out = "";
        for ($i = 0; $i < count($filename_list); $i++) {
            if ($filename_list[$i]) {
                if (!$mimedone) {
                    $out.= "Content-Type: multipart/mixed; boundary=\"" . $this->mime_boundary . "\"" . $this->eol;
                    $mimedone = 1;
                }
                if ($mimefilename_list[$i])
                    $filename_list[$i] = $mimefilename_list[$i];
                $out.= "X-attachments: $filename_list[$i]" . $this->eol;
            }
        }
        //$out.= $this->eol;
        dol_syslog("CMailFile::write_mimeheaders mime_header=\n" . $out);
        return $out;
    }

    /**
      \brief         Permet d'ecrire le corps du message
      \param         msgtext
      \param         filename_list
     */
    function write_body($msgtext, $filename_list) {
        global $conf;

        $out = '';

        if ($this->atleastonefile) {
            $out.= "--" . $this->mime_boundary . $this->eol;
            if ($this->msgishtml) {
                $out.= "Content-Type: text/html; charset=" . $conf->character_set_client . $this->eol;
            } else {
                $out.= "Content-Type: text/plain; charset=" . $conf->character_set_client . $this->eol;
            }
            $out.= $this->eol;
        }
        if ($this->msgishtml) {
            // Check if html header already in message
            $htmlalreadyinmsg = 0;
            if (preg_match('/^[ \t]*<html/i', $msgtext))
                $htmlalreadyinmsg = 1;

            if (!$htmlalreadyinmsg)
                $out .= "<html><head><title></title></head><body>";
            $out.= $msgtext;
            if (!$htmlalreadyinmsg)
                $out .= "</body></html>";
        }
        else {
            $out.= $msgtext;
        }
        $out.= $this->eol;
        return $out;
    }

    /**
      \brief         Permet d'attacher un fichier
      \param         filename_list        Tableau
      \param         mimetype_list        Tableau
      \param         mimefilename_list    Tableau
      \return        out                    Chaine fichiers encodes
     */
    function write_files($filename_list, $mimetype_list, $mimefilename_list) {
        $out = '';

        for ($i = 0; $i < count($filename_list); $i++) {
            if ($filename_list[$i]) {
                dol_syslog("CMailFile::write_files: i=$i");
                $encoded = $this->_encode_file($filename_list[$i]);
                if ($encoded >= 0) {
                    if ($mimefilename_list[$i])
                        $filename_list[$i] = $mimefilename_list[$i];
                    if (!$mimetype_list[$i]) {
                        $mimetype_list[$i] = "application/octet-stream";
                    }

                    $out = $out . "--" . $this->mime_boundary . $this->eol;
                    $out.= "Content-Type: " . $mimetype_list[$i] . "; name=\"" . $filename_list[$i] . "\"" . $this->eol;
                    $out.= "Content-Transfer-Encoding: base64" . $this->eol;
                    $out.= "Content-Disposition: attachment; filename=\"" . $filename_list[$i] . "\"" . $this->eol;
                    $out.= $this->eol;
                    $out.= $encoded;
                    $out.= $this->eol;
//                    $out.= $this->eol;
                } else {
                    return $encoded;
                }
            }
        }

        // Fin de tous les attachements
        $out = $out . "--" . $this->mime_boundary . "--" . $this->eol;
        return $out;
    }

    function check_server_port($host, $port) {
        $_retVal = 0;

        if (function_exists('fsockopen')) {
            //See if we can connect to the SMTP server
            if ($socket = @fsockopen($host, // Host to 'hit', IP or domain
                            $port, // which Port number to use
                            $errno, // actual system level error
                            $errstr, // and any text that goes with the error
                            5)) {  // timeout for reading/writing data over the socket
                // Windows still does not have support for this timeout function
                if (function_exists('socket_set_timeout'))
                    socket_set_timeout($socket, 5, 0);

                // Check response from Server
                if ($_retVal = $this->server_parse($socket, "220"))
                    $_retVal = $socket;
            }
            else {
                $this->error = 'Error ' . $errno . ' - ' . $errstr;
            }
        }
        return $_retVal;
    }

    // This function has been modified as provided
    // by SirSir to allow multiline responses when
    // using SMTP Extensions
    //
    function server_parse($socket, $response) {
        /**
         * Default return value
         *
         * Returns constructed SELECT Object string or boolean upon failure
         * Default value is set at TRUE
         *
         * @var mixed $_retVal Indicates if Object was created or not
         * @access private
         * @static
         */
        $_retVal = true;
        $server_response = '';

        while (substr($server_response, 3, 1) != ' ') {
            if (!( $server_response = fgets($socket, 256) )) {
                $this->error = "Couldn't get mail server response codes";
                $_retVal = false;
            }
        }

        if (!( substr($server_response, 0, 3) == $response )) {
            $this->error = "Ran into problems sending Mail.\r\nResponse: $server_response";
            $_retVal = false;
        }

        return $_retVal;
    }

}

/**
  \brief      Renvoie une adresse acceptee par le serveur SMTP
  \param      adresses        Exemple: 'John Doe <john@doe.com>' ou 'john@doe.com'
  \param        format            0=Auto, 1=emails avec <>, 2=emails sans <>
  \return        string            Renvoi: Si format 1: '<john@doe.com>' ou 'John Doe <john@doe.com>'
  Si format 2: 'john@doe.com'
 */
function getValidAddress($adresses, $format) {
    global $conf;

    $ret = '';

    $arrayaddress = preg_split('/,/', $adresses);

    // Boucle sur chaque composant de l'adresse
    foreach ($arrayaddress as $val) {
        if (preg_match('/^(.*)<(.*)>$/i', trim($val), $regs)) {
            $name = trim($regs[1]);
            $email = trim($regs[2]);
        } else {
            $name = '';
            $email = trim($val);
        }

        if ($email) {
            $newemail = '';
            if ($format == 2) {
                $newemail = $email;
            }
            if ($format == 1) {
                $neweamil = '<' . $email . '>';
            }
            if ($format == 0) {
                if ($conf->global->MAIN_MAIL_NO_FULL_EMAIL)
                    $newemail = '<' . $email . '>';
                elseif (!$name)
                    $newemail = '<' . $email . '>';
                else
                    $newemail = $name . ' <' . $email . '>';
            }

            $ret = ($ret ? $ret . ',' : '') . $newemail;
        }
    }

    return $ret;
}

?>
