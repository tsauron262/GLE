<?php
/**
 *
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

set_include_path(get_include_path() . PATH_SEPARATOR . ROOT_PATH . '/lib/common');

require_once ROOT_PATH . '/lib/common/Zend/Mail.php';
require_once ROOT_PATH . '/lib/common/Zend/Mail/Transport/Smtp.php';
require_once ROOT_PATH . '/lib/common/Zend/Mail/Transport/Sendmail.php';

require_once ROOT_PATH . '/lib/confs/Conf.php';

class EmailConfiguration {

    const EMAILCONFIGURATION_FILE_CONFIG = '/lib/confs/mailConf.php';

    const EMAILCONFIGURATION_TYPE_MAIL = 'mail';
    const EMAILCONFIGURATION_TYPE_SENDMAIL = 'sendmail';
    const EMAILCONFIGURATION_TYPE_SMTP = 'smtp';

    const EMAILCONFIGURATION_SMTP_SECURITY_NONE = 'NONE';
    const EMAILCONFIGURATION_SMTP_SECURITY_TLS = 'TLS';
    const EMAILCONFIGURATION_SMTP_SECURITY_SSL = 'SSL';

    const EMAILCONFIGURATION_SMTP_AUTH_NONE = 'NONE';
    const EMAILCONFIGURATION_SMTP_AUTH_LOGIN = 'LOGIN';

    private $smtpHost;
    private $smtpUser;
    private $smtpPass;
    private $smtpPort;
    private $mailAddress;
    private $mailType;
    private $sendmailPath;
    private $smtpSecurity;
    private $smtpAuth;
    private $configurationFile;
    private $testEmail;
    private $testEmailType;

    public function getSmtpHost() {
        return $this->smtpHost;
    }

    public function setSmtpHost($smtphost) {
        $this->smtpHost = $smtphost;
    }

    public function getSmtpUser() {
        return $this->smtpUser;
    }

    public function setSmtpUser($smtpUser) {
        $this->smtpUser = $smtpUser;
    }

    public function getSmtpPass() {
        return $this->smtpPass;
    }

    public function setSmtpPass($smtpPass) {
        $this->smtpPass = $smtpPass;
    }

    public function getSmtpPort() {
        return $this->smtpPort;
    }

    public function setSmtpPort($smtpPort) {
        $this->smtpPort = $smtpPort;
    }

    public function getMailAddress() {
        return $this->mailAddress;
    }

    public function setMailAddress($mailAddress) {
        $this->mailAddress = $mailAddress;
    }

    public function getMailType() {
        return $this->mailType;
    }

    public function setMailType($mailType) {
        $this->mailType = $mailType;
    }

    public function getSendmailPath() {
        return $this->sendmailPath;
    }

    public function setSendmailPath($sendmailPath) {
        $this->sendmailPath = $sendmailPath;
    }

    public function setSmtpAuth($auth) {
        $this->smtpAuth = $auth;
    }

    public function getSmtpAuth() {
        return $this->smtpAuth;
    }

    public function setSmtpSecurity($security) {
        $this->smtpSecurity = $security;
    }

    public function getSmtpSecurity() {
        return('tls');//Zimbra
        //return $this->smtpSecurity;
    }

    public function setTestEmail($testEmail) {
        $this->testEmail = $testEmail;
    }

    public function getTestEmail() {
        return $this->testEmail;
    }

    public function setTestEmailType($testEmailType) {
        $this->testEmailType = $testEmailType;
    }

    public function getTestEmailType() {
        return $this->testEmailType;
    }

    public function __construct() {
        $confObj = new HrmConf();

        if (is_file(ROOT_PATH.self::EMAILCONFIGURATION_FILE_CONFIG)) {
            $this->configurationFile=ROOT_PATH.self::EMAILCONFIGURATION_FILE_CONFIG;
        }

        if (isset($confObj->emailConfiguration) && is_file($confObj->emailConfiguration)) {
            $this->configurationFile=$confObj->emailConfiguration;
        } else if (isset($confObj->emailConfiguration) && is_file(ROOT_PATH.$confObj->emailConfiguration)) {
            $this->configurationFile=ROOT_PATH.$confObj->emailConfiguration;
        }

        if ($this->configurationFile == null) {
            include ROOT_PATH.self::EMAILCONFIGURATION_FILE_CONFIG."-distribution";

            if (isset($confObj->emailConfiguration)) {
                $this->configurationFile=$confObj->emailConfiguration;
            }

            $this->reWriteConf();
        }

        include $this->configurationFile;
    }

    public function reWriteConf() {
        $content = '
<?php
    $this->smtpHost = \''.$this->getSmtpHost().'\';
    $this->smtpUser = \''.$this->getSmtpUser().'\';
    $this->smtpPass = \''.$this->getSmtpPass().'\';
    $this->smtpPort = \''.$this->getSmtpPort().'\';

    $this->mailType = \''.$this->getMailType().'\';
    $this->mailAddress = \''.$this->getMailAddress().'\';
    $this->smtpAuth = \''.$this->getSmtpAuth().'\';
    $this->smtpSecurity = \''.$this->getSmtpSecurity().'\';
?>';

        return file_put_contents($this->configurationFile, $content);
    }

    /**
    * Uses to test the SMTP details set in Email Configuration
    * @param string $testAddress Email address to send test email
    * @return bool Returns true if no error occurs during transport. False other wise.
    */

    public function sendTestEmail() {
        $subject="";
        $message  = "";
        $transport="";
        if ($this->getTestEmailType() == "smtp") {

            $config = array('auth' => 'login',
                            'username' => $this->getSmtpUser(),
                            'password' => $this->getSmtpPass(),
                            'ssl' => 'tls',
                            'port' => $this->getSmtpPort());
//ssl require for zimbra
            $transport = new Zend_Mail_Transport_Smtp($this->getSmtpHost(), $config);
            $subject = "SMTP Configuration Test Email";
            $message = utf8_decode("Si vous avez reçu cet email, c'est que la configuration de l'envoi de mail est correcte.");
            $logMessage = date('r')." Sending Test Email Using SMTP to {$this->getTestEmail()} ";

        } elseif ($this->getTestEmailType() == "sendmail") {

            $transport = new Zend_Mail_Transport_Sendmail();
            $subject = "BIMP-ERP - HRM - Email de test";
            $message = utf8_decode("Si vous avez reçu cet email, c'est que la configuration de l'envoi de mail est correcte.");
            $logMessage = date('r')." Sending Test Email Using SendMail to {$this->getTestEmail()} ";

        }

        $mail = new Zend_Mail();
        $mail->setFrom($this->getMailAddress(), "BIMP-ERP - HRM");
        $mail->addTo($this->getTestEmail());
        $mail->setSubject($subject);
        $mail->setBodyText($message);

        $logPath = ROOT_PATH.'/lib/logs/notification_mails.log';

        try {
            $toto = $mail->send($transport);
            //var_dump($toto);
            $logMessage .= "Succeeded \r\n";
            error_log($logMessage, 3, $logPath);
            return true;
        } catch (Exception $e) {
            $logMessage .= "Failed \r\n Reason: {$e->getMessage()} \r\n";
            error_log($logMessage, 3, $logPath);
            return false;
        }

    }

}
?>
