<?php

require_once DOL_DOCUMENT_ROOT.'/bimpfichinter/objects/extraFI.class.php';

class Bimp_Fichinterdet extends extraFI
{
    

    public static $typeinter_list = array();
    
    public function __construct($module, $object_name) {
        parent::__construct($module, $object_name);
        
        if(count(self::$typeinter_list) < 1){
            $sql = $this->db->db->query("SELECT * FROM `".MAIN_DB_PREFIX."synopsisfichinter_c_typeInterv` ORDER BY rang ASC");
            while($ln = $this->db->db->fetch_object($sql))
                    self::$typeinter_list[$ln->id] = array('label'=>$ln->label);
        }
    }
    
    
    public function getInstanceName()
    {
        if ($this->isLoaded()) {
            $parent = $this->getParentInstance();
            if($parent->isLoaded())
                return $parent->getData('ref')." ligne ".dol_trunc ($this->getData("description"));
        }

        return ' ';
    }
    
    public function canEdit() {
        $parent = $this->getParentInstance();
        if($parent->isLoaded())
            return $parent->canEdit();
        return 0;
    }
    
    
    public function create(&$warnings = array(), $force_create = false) {
        parent::create($warnings, $force_create);
        
        $this->update($warnings, $force_update);
    }
    

}

