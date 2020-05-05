<?php

class BimpReportLine extends BimpObject
{

    public static $types = array(
        'success' => array('label' => 'Succès', 'icon' => 'fas_check', 'classes' => array('success')),
        'danger'  => array('label' => 'Erreur', 'icon' => 'fas_exclamation-circle', 'classes' => array('danger')),
        'warning' => array('label' => 'Alerte', 'icon' => 'fas_exclamation-triangle', 'classes' => array('warning')),
        'info'    => array('label' => 'Info', 'icon' => 'fas_info-circle', 'classes' => array('info'))
    );

    public function displayMsgAlert()
    {
        $msg = (string) $this->getData('msg');

        if ($msg) {
            return BimpRender::renderAlerts($msg, $this->getData('type'));
        }
    }
}
