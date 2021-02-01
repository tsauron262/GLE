<?php

class Bimp_Entrepot extends BimpObject
{

    public static $status_list = array(
        0 => array('label' => 'Désactivé', 'icon' => 'fas_times', 'classes' => array('danger')),
        1 => array('label' => 'Actif', 'icon' => 'fas_check', 'classes' => array('success')),
        2 => array('label' => 'Actif (en interne seulement)', 'icon' => 'fas_exclamation', 'classes' => array('warning'))
    );

    // Affichages: 
    
    public function displayFullAdress()
    {
        $html = '';

        if ($this->getData('address')) {
            $html .= $this->getData('address') . '<br/>';
        }

        if ($this->getData('zip')) {
            $html .= $this->getData('zip');

            if ($this->getData('town')) {
                $html .= ' ';
            }
        }
        
        $html .= $this->getData('town');

        return $html;
    }
}
