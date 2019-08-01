<?php

class bimpRevalorisation extends BimpObject
{

    public static $status_list = array(
        0 => array('label' => 'En Attente', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        1 => array('label' => 'Acceptée', 'icon' => 'fas_check', 'classes' => array('success')),
        2 => array('label' => 'Refusée', 'icon' => 'fas_times', 'classes' => array('danger')),
    );
    public static $types = array(
        'crt' => 'Remise CRT',
        'oth' => 'Autre'
    );

    // Gestion des droits user: 

    public function canDelete()
    {
        global $user;

        return (int) ($user->admin ? 1 : 0);
    }

    public function canSetAction($action)
    {
        switch ($action) {
            case 'accept':
            case 'refuse':
                return 1;
        }

        return (int) parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'accept':
            case 'refuse':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }

                if (!(int) $this->getData('status') !== 0) {
                    $errors[] = 'Cette revalorisation n\'est plus en attente de validation';
                    return 0;
                }

                return 1;
        }

        return (int) parent::isActionAllowed($action, $errors);
    }

    // Getters params: 
    
    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            if ($this->isActionAllowed('accept') && $this->canSetAction('accept')) {
                $buttons[] = array(
                    'label' => 'Accepter',
                    'icon' => 'fas_check',
                    'onclick' => ''
                );
            }
            
            
        }

        return $buttons;
    }

    // Affichage: 

    public function displayDesc()
    {
        $html = '';

        if ($this->isLoaded()) {
            
        }

        return $html;
    }
}
