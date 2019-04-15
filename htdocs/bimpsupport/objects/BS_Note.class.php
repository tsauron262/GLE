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
    
    public function canClientDelete() {
        return $this->canClientEdit();
    }
    
    public function canClientEdit() {
        global $userClient;
        if($this->canClientCreate() && $this->getData('id_user_client') == $userClient->id){
            $list_of_note_for_this_ticket = $this->getList(Array('id_ticket' => $_REQUEST['id']));
            $good_array = array('id' => 0, 'date' => '2000-01-01');
            foreach($list_of_note_for_this_ticket as $note) {
                if(strtotime($note['date_create']) > strtotime($good_array['date'])) {
                    $good_array = array('id' => $note['id'], 'date' => $note['date_create']);
                }
            }
            
            if($this->id == $good_array['id']) {
                return 1;
            }
            
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
    
    public function canDelete(){
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
    
    public function create(&$warnings = array(), $force_create = false) {
        parent::create($warnings, $force_create);
        if(BimpTools::getContext() == 'public') {
            global $userClient;
            $this->updateField('id_user_client', $userClient->id);
        }
    }
}
