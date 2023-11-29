<?php

class Bimp_Entrepot extends BimpObject
{
    public $redirectMode = 5; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old

    public static $status_list = array(
        0 => array('label' => 'Désactivé', 'icon' => 'fas_times', 'classes' => array('danger')),
        1 => array('label' => 'Actif', 'icon' => 'fas_check', 'classes' => array('success')),
        2 => array('label' => 'Actif (en interne seulement)', 'icon' => 'fas_exclamation', 'classes' => array('warning'))
    );

    // Droits users: 

    public function canCreate()
    {
        global $user;
        return (isset($user->rights->stock->creer) && $user->rights->stock->creer);
    }

    public function canDelete()
    {
        global $user;
        return (isset($user->rights->stock->supprimer) && $user->rights->stock->supprimer);
    }

//    public function iAmAdminRedirect()
//    {
//        return $this->canEdit();
//    }

    // Getters: 

    public function getNameProperties()
    {
        return array('lieu');
    }

    public function getMail()
    {
        if(BimpCore::getExtendsEntity() == 'bimp'){
            $domaine = 'bimp.fr';
            $nbCaracdeps = array(3, 2);
            foreach ($nbCaracdeps as $nbCaracdep) {
                $dep = substr($this->getData('zip'), 0, $nbCaracdep);
                $name = 'boutique' . $dep;
                $sql = $this->db->db->query('SELECT mail FROM `llx_usergroup` u, llx_usergroup_extrafields ue WHERE ue.fk_object = u.rowid AND u.nom LIKE "' . $name . '"');
                while ($ln = $this->db->db->fetch_object($sql)) {
                    if ($ln->mail && $ln->mail != '' && stripos($ln->mail, "@") !== false)
                        return $ln->mail;
                    else {
                        require_once(DOL_DOCUMENT_ROOT . "/synopsistools/SynDiversFunction.php");
                        return str_replace(",", "", traiteCarac($name) . "@" . $domaine);
                    }
                }
            }
        }
    }

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

    // Overrides: 

    public function getDolObjectUpdateParams()
    {
        global $user;

        return array(
            ($this->isLoaded() ? (int) $this->id : 0),
            $user
        );
    }
}
