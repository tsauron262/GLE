<?php

class BimpCommission extends BimpObject
{

    public static $status_list = array(
        0 => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1 => array('label' => 'Validée', 'icon' => 'fas_check', 'classes' => array('success'))
    );

    // Gestion des droits user: 

    public function canDelete()
    {
        global $user;

        return (int) ($user->admin ? 1 : 0);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            
        }

        return (int) parent::isActionAllowed($action, $errors);
    }

    // Getters params: 

    public function getListExtraBtn()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $buttons[] = array(
                'label'   => 'Détail',
                'icon'    => 'fas_bars',
                'onclick' => $this->getJsLoadModalView('details', 'Détail de la commission #' . $this->id)
            );
        }

        return $buttons;
    }

    // Méthodes statiques:

    public static function CreateCommissions()
    {
        
    }
}
