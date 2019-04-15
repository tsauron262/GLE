<?php

class BS_Note extends BimpObject
{
    
    public static $visibilities = array(
        1 => 'Membres Bimp et client',
        2 => 'Membres BIMP',
        3 => 'Auteur seulement'
    );

    public function getInterventionsArray()
    {
        $array = array(
            0 => '-'
        );

        $id_parent = $this->getData('id_ticket');

        if (!is_null($id_parent) && $id_parent) {
            $rows = $this->db->getValues('bs_inter', 'id', '`id_ticket` = ' . (int) $id_parent);
            if (!is_null($rows)) {
                foreach ($rows as $id_inter) {
                    $array[(int) $id_inter] = 'Intervention n°' . $id_inter;
                }
            }
        }

        return $array;
    }
    
    public function canClientView() {
        return 1;
    }
    
    public function canClientEdit() {
        global $userClient;
        if($this->canClientCreate() && $this->getData('id_user_client') == $userClient->id){
            // Vérifier que c'est la dernière
            return 1;
        } elseif(!$this->isLoaded()) {
            return 1;
        }
        return 0;
        
        
    }
    
    public function canClientCreate() {
        $parent = $this->getParentInstance();
        if($parent->getData('status') == 20 || $parent->getData('status') == 2) {
            return 1;
        }
        return 0;
    }
    
    public function canCreate() {
        return 1;
    }
    
    public function canEdit(){
        return 1;
    }
    
    public function getListFilterNotesInterface() {
        $parent = $this->getParentInstance();
        return Array(
            Array(
                'name' => 'id_ticket',
                'filter' => $parent->getData('id')
            ),
            Array(
                'name' => 'visibility',
                'filter' => 1 // A changer après avoir fait le créate
            )
        );
    }
}
