<?php

class BDS_ImportsTechDataProcess extends BDSImportProcess
{

    public function initFtpTest(&$data, &$errors = array())
    {
        $host = BimpTools::getArrayValueFromPath($this->params, 'host', '');
        $login = BimpTools::getArrayValueFromPath($this->params, 'login', '');
        $pword = BimpTools::getArrayValueFromPath($this->params, 'pwd', '');

        $ftp = $this->ftpConnect($host, $login, $pword, true, $errors);

        if ($ftp !== false) {
            $data['result_html'] = BimpRender::renderAlerts('Connection FTP réussie', 'success');
        }
    }
    
    
}
