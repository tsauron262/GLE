<?php

require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

class BimpMailCore
{

    public $cmail = null;
    public $model = 'default';
    public $primary = '';
    public $subject;
    public $msg = '';
    public $from = '';
    public $to = '';
    public $cc = '';
    public $bcc = '';
    public $reply_to = '';
    public $errors_to = '';
    public $deliveryreceipt = 0;
    public $send_context = 'standard';
    public $title = '';
    public $subtitle = '';
    public $from_name = '';
    public $url = '';
    public $files = array();
    public $parent;
    public static $defaultType = '';

    function __construct($parent, $subject, $to, $from, $msg = '', $reply_to = '', $addr_cc = '', $addr_bcc = '', $deliveryreceipt = 0, $errors_to = '')
    {
        global $dolibarr_main_url_root, $conf, $user;

        if (is_object($parent)) {
            $this->parent = $parent;
        } elseif ($parent !== 'none') {
            BimpCore::addlog('Pas d\'objet parent pour BimpMail');

            if (!is_null($parent)) {
                $paramtres = array('parent', 'subject', 'to', 'from', 'msg', 'reply_to', 'addr_cc', 'addr_bcc', 'deliveryreceipt', 'errors_to');
                $oldParams = null;
                foreach ($paramtres as $newParams) {
                    if ($oldParams) {
                        eval('$' . $newParams . 'tmp=$' . $oldParams . ';');
                    }

                    $oldParams = $newParams;
                }
                foreach ($paramtres as $newParams) {
                    eval('$' . $newParams . '=$' . $newParams . 'tmp;');
                }
            }
        }

        $subject = str_replace(array($dolibarr_main_url_root, $_SERVER['SERVER_NAME'] . DOL_URL_ROOT), DOL_URL_ROOT, $subject);
        $subject = str_replace(DOL_URL_ROOT, $dolibarr_main_url_root, $subject);
        $msg = str_replace(array($dolibarr_main_url_root, $_SERVER['SERVER_NAME'] . DOL_URL_ROOT), DOL_URL_ROOT, $msg);
        $msg = str_replace(DOL_URL_ROOT, $dolibarr_main_url_root, $msg);

        if (BimpTools::cleanEmailsStr($to) == '') {
            BimpCore::addlog('Echec envoi email sans destinatiare ', Bimp_Log::BIMP_LOG_ALERTE, 'email', NULL, array(
                'Destinataire' => $to,
                'From'         => $from,
                'Reply_to'     => $reply_to,
                'Sujet'        => $subject,
                'Message'      => $msg
            ));
        }

        if ($from == '') {
            $from = '';

            if (isset($conf->global->MAIN_MAIL_EMAIL_FROM)) {
                $from .= $conf->global->MAIN_MAIL_EMAIL_FROM;
            } elseif (isset($conf->global->MAIN_INFO_SOCIETE_MAIL) && $conf->global->MAIN_INFO_SOCIETE_MAIL != '') {
                $from .= $conf->global->MAIN_INFO_SOCIETE_MAIL;
            } else {
                $from .= 'no-reply@' . strtolower(str_replace(" ", "", $conf->global->MAIN_INFO_SOCIETE_NOM)) . '.fr';
                if (!$reply_to) {
                    $reply_to = 'none';
                }
            }

            if ($reply_to === 'none') {
                $reply_to = '';
            } elseif (!$reply_to) {
                if (BimpCore::isContextPublic()) {
                    $reply_to = BimpCore::getConf('public_default_reply_to_email');
                } else {
                    $reply_to = $user->email;
                }
            }
        } else {
            $this->from_name = '';
        }

        if (defined('MOD_DEV_SYN_MAIL')) {
            $msg = "OrigineTo = " . htmlentities(BimpTools::cleanEmailsStr($to)) . "\n\n" . $msg;
            if ($addr_cc) {
                $msg = "OrigineCc = " . htmlentities(BimpTools::cleanEmailsStr($addr_cc)) . "\n\n" . $msg;
            }
            if ($addr_bcc) {
                $msg = "OrigineBcc = " . htmlentities(BimpTools::cleanEmailsStr($addr_bcc)) . "\n\n" . $msg;
            }
            $addr_cc = '';
            $addr_bcc = '';
            $reply_to = MOD_DEV_SYN_MAIL;
            $to = MOD_DEV_SYN_MAIL;
        }

        $this->subject = $subject;
        $this->msg = $msg;
        $this->from = $from;
        $this->to = $to;
        $this->cc = $addr_cc;
        $this->bcc = $addr_bcc;
        $this->reply_to = $reply_to;
        $this->errors_to = $errors_to;
        $this->deliveryreceipt = $deliveryreceipt;
        $this->primary = BimpCore::getParam('public_email/primary', '807F7F');

        if (static::$defaultType != '')
            $this->setFromType(static::$defaultType);
    }

    public function getHeader()
    {
        return '';
    }

    public function getFooter()
    {
        return '';
    }

    public function addFiles($files)
    {
        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            $this->files[] = $file;
        }
    }

    public function addFile($file)
    {
        if (empty($file)) {
            return;
        }

        $this->files[] = $file;
    }

    public function setFromType($type)
    {
        
    }

    public function send(&$errors = array(), &$warnings = array())
    {
        if (BimpCore::isModeDev()) {
            sleep(1);
        }

        if (!$this->to) {
            $errors[] = 'Destinataire absent';
            return false;
        }

        $filename_list = array();
        $mimetype_list = array();
        $mimefilename_list = array();

        foreach ($this->files as $i => $file) {
            if (!isset($file[0]) || !$file[0]) {
                $warnings[] = 'Fichier n°' . ($i + 1) . ': chemin absent';
                continue;
            }
            if (!isset($file[1]) || !$file[1]) {
                $warnings[] = 'Fichier n°' . ($i + 1) . ': type mime';
                continue;
            }
            if (!isset($file[2]) || !$file[2]) {
                $warnings[] = 'Fichier n°' . ($i + 1) . ': nom du fichier absent';
                continue;
            }

            $filename_list[] = $file[0];
            $mimetype_list[] = $file[1];
            $mimefilename_list[] = $file[2];
        }

        $html = $this->getHeader();
        $html .= $this->msg;
        $html .= $this->getFooter();

        $html = str_replace("\n", "<br/>", $html);

        $to = BimpTools::cleanEmailsStr($this->to);
        $from = BimpTools::cleanEmailsStr($this->from, $this->from_name);
        $cc = BimpTools::cleanEmailsStr($this->cc);
        $bcc = BimpTools::cleanEmailsStr($this->bcc);
        $replyTo = BimpTools::cleanEmailsStr($this->reply_to);

        $cmail = new CMailFile($this->subject, $to, $from, $html, $filename_list, $mimetype_list, $mimefilename_list, $cc, $bcc, $this->deliveryreceipt, 1, $this->errors_to, '', '', '', $this->send_context, $replyTo);
        $result = $cmail->sendfile();

        if (!$result) {
            $errors[] = 'Echec envoi de l\'e-mail : ' . $cmail->error;
            $ip = BimpCache::getIpFromDns($cmail->smtps->getHost());
            BimpCore::addlog('Echec envoi email ' . $cmail->error, Bimp_Log::BIMP_LOG_ALERTE, 'email', NULL, array(
                'IP'           => $ip,
                'Destinataire' => $to,
                'Sujet'        => $this->subject,
                'Message'      => $this->msg
            ));

            $lastMailFailedTms = (int) BimpCore::getConf('bimpcore_last_mail_failed_tms', 0);
            $tms = (int) time();

            if ($tms - $lastMailFailedTms > 7200) {
                $nMailsFailed = 0;
            } else {
                $nMailsFailed = BimpCore::getConf('bimpcore_nb_mails_failed', 0);
            }

            $nMailsFailed++;

            if ($nMailsFailed == 10) {
                if (!BimpCore::isModeDev()) {
                    global $conf;
                    if (empty($conf->global->MAIN_DISABLE_ALL_SMS)) {
                        if (!class_exists('CSMSFile')) {
                            require_once DOL_DOCUMENT_ROOT . '/core/class/CSMSFile.class.php';
                        }
                        $smsfile = new CSMSFile('0686691814', 'ADMIN BIMP', '10 ECHECS ENVOI EMAIL EN 2H SUR ' . DOL_URL_ROOT);
                        $smsfile->sendfile();
                        $smsfile = new CSMSFile('0628335081', 'ADMIN BIMP', '10 ECHECS ENVOI EMAIL EN 2H SUR ' . DOL_URL_ROOT);
                        $smsfile->sendfile();
                    }
                }
            }

            BimpCore::setConf('bimpcore_nb_mails_failed', $nMailsFailed);
            BimpCore::setConf('bimpcore_last_mail_failed_tms', $tms);
        } else {
            $instance = BimpObject::getInstance('bimpcore', 'BimpMailLog');

            $data = array(
                'mail_to'      => $this->to,
                'mail_from'    => $this->from,
                'mail_cc'      => $this->cc,
                'msg'          => $this->msg,
                'mail_subject' => $this->subject,
                'obj_type'     => 'bimp_object',
                'obj_module'   => $this->parent->module,
                'obj_name'     => $this->parent->object_name,
                'id_obj'       => $this->parent->id,
            );

            $errors = $instance->validateArray($data);

            if (!count($errors)) {
                $errors = $instance->create($warnings, true);
            }
        }

        return $result;
    }
}
