<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';

class GSX_Diagnostic extends BimpObject
{

    protected static $status = null;

    public function getSerial()
    {
        $sav = $this->getParentInstance();
        if (BimpObject::objectLoaded($sav)) {
            return $sav->getSerial();
        }

        return '';
    }

    public function displayStatus()
    {
        $html = '';

        $complete = (int) $this->getData('complete');
        if (!$complete) {
            $gsx = GSX_v2::getInstance();
            $suite = null;

            if ($gsx->logged) {
                $data = $gsx->diagnosticStatus($this->getSerial());

                if (isset($data['diagnosticSuite']) && (int) $data['diagnosticSuite']['id'] == $this->getData('suite_id')) {
                    $suite = $data['diagnosticSuite'];

                    if ($suite['percentComplete'] === '100%') {
                        $complete = 1;
                        $this->updateField('complete', 1);
                    }
                }
            }

            if (!$complete) {
                if (is_null($suite)) {
                    $html .= 'En cours<br/>';
                } else {
                    $html .= $suite['suiteStatus'] . '<br/>';
                    $html .= $suite['statusDescription'] . '<br/>';
                    $html .= 'Progession: <strong>' . $suite['percentComplete'] . '</strong><br/>';
                }
                $html .= 'Durée estimée: <strong>de ' . $this->getData('time_min') . ' à ' . $this->getData('time_max') . ' minute(s)</strong>';
            }
        }

        if ($complete) {
            $html .= '<span class="success">' . BimpRender::renderIcon('fas_check') . 'Terminé</span>';
        }

        return $html;
    }
    
    public function getStatus()
    {
        if (is_null(self::$status)) {
            self::$status = array();
            
            
        }
    }
}
