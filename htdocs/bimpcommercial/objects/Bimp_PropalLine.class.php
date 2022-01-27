<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/ObjectLine.class.php';

class Bimp_PropalLine extends ObjectLine
{

    public static $parent_comm_type = 'propal';
    public static $dol_line_table = 'propaldet';
    public static $dol_line_parent_field = 'fk_propal';
    
    // Getters - ovrrides ObjectLine
    public function showMarginsInForms()
    {
        return 1;
    }
    
    
    public function isDeletable($force_delete = false, &$errors = array()): int {
        if($this->getData('linked_object_name') == 'discount')
            return 1;
        return parent::isDeletable($force_delete, $errors);
    }
    
    public function delete(&$warnings = array(), $force_delete = false) {
        if($this->getData('linked_object_name') == 'discount'){
            $parent = $this->getParentInstance();
            $parent->dol_object->statut = 0;
            $return = parent::delete($warnings, $force_delete);
            $parent->dol_object->statut = $parent->getInitData('statut');
        }
        else
            $return = parent::delete($warnings, $force_delete);
        return $return;
    }
}
