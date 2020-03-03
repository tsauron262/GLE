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
    
    
    public function canView() {
        global $user;
        return $user->rights->facture->paiement;
    }
}

