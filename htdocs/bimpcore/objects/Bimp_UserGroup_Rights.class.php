<?php
require_once(DOL_DOCUMENT_ROOT."/bimpcore/objects/Bimp_Rights.class.php");
class Bimp_UserGroup_Rights extends Bimp_Rights
{
    
    public function getFilterPermsByGroup() {
        $id_group = $_REQUEST['id']; 
        $filters = array();
        $filters[] = [
            'name' => 'fk_usergroup',
            'filter' => $id_group 
        ];
        return $filters;
    }
    
    public function getModuleForRight() {
        
        $r = explode('->',$this->displayData('fk_id'));
        return ucfirst($r[0]);
    }
    
    public function getLibelleForRight() {
        return $this->db->getValue('rights_def', 'libelle', 'id = '.$this->getData('fk_id'));
    }

}
