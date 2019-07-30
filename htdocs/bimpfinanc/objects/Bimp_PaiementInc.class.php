<?php


class Bimp_PaiementInc  extends BimpObject
{
    public function canEdit() {
        global $user;
        return ($this->getData("user_create") == $user->id);
    }
    
    public function canDelete() {
        return $this->canEdit();
    }
}

