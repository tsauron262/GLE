<?php

class HrmConf {

    var $smtphost;
    var $dbhost;
    var $dbport;
    var $dbname;
    var $dbuser;
    var $version;

    function HrmConf() {

        $this->dbhost = 'localhost';
        $this->dbport = '3306';
        $this->dbname = 'hr_mysql111';
        $this->dbuser = 'root';
        $this->dbpass = 'freeparty';
        $this->version = '2.4.1';

        $this->emailConfiguration = dirname(__FILE__) . '/mailConf.php';
        $this->errorLog = realpath(dirname(__FILE__) . '/../logs/') . '/';
    }

}

?>