<?php

class BH_Inter extends BimpObject
{

    public static $priorities = array(
        1 => array('label' => 'Urgent', 'classes' => array('danger')),
        2 => array('label' => 'non urgent', 'classes' => array('info')),
    );
    public static $status = array(
        1 => array('label' => 'Ouvert', 'classes' => array('success')),
        2 => array('label' => 'Fermé', 'classes' => array('danger'))
    );

    public function renderTimer()
    {
        if (!isset($this->id) || !$this->id) {
            return BimpRender::renderAlerts('intervention non enregistrée');
        }

        $html = BimpRender::renderBimpTimer($this, 'timer');

        return $html;
    }
}
