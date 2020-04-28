<?php

class BDS_ProcessCron extends BimpObject
{

    public static $periods = array(
        'min'  => 'Minute',
        'hour' => 'Heure',
        'day'  => 'Jour',
        'week' => 'Semaine'
    );
    public static $frequency_units = array(
        'min'  => 60,
        'hour' => 3600,
        'day'  => 86400,
        'week' => 604800
    );

    // Getters array: 

    public function getOperationsArray($include_empty = false)
    {
        $process = $this->getParentInstance();

        if (BimpObject::objectLoaded($process)) {
            return $process->getChildrenListArray('operations', $include_empty);
        }

        return array();
    }

    // Affichages: 

    public function displayFrequency()
    {
        return $this->getData('freq_val') . ' ' . $this->displayData('freq_type') . '(s)';
    }
}
