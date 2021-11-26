<?php

class Bimp_Client_Suivi_Recouvrement extends BimpObject
{

    public $mode_list = array(
        0 => "Courrier",
        1 => "Email",
        2 => "Sms",
        3 => "Téléphone",
        4 => "Interne"
    );
    
    public $sens_list = array(
        0 => "Entrant",
        1 => "Sortant",
        2 => "Interne"
    );

    public function canDelete()
    {
        global $user;

        return (int) $user->admin;
    }
    public function canEdit()
    {
        global $user;

        if($this->isLoaded())
            return (int) ($user->admin || $this->getData('user_create') == $user->id);
        return 1;
    }
}
