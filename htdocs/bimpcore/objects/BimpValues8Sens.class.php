<?php

class BimpValues8Sens extends BimpObject
{

    public function getListExtraBtn()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $buttons[] = array(
                'label'   => 'Supprimer',
                'icon'    => 'fas_trash',
                'onclick' => $this->getJsActionOnclick('deleteFus', array(), array(
                                'form_name' => 'deleteFus'
                ))
            );
        }

        return $buttons;
    }
    
    
    public function getReplaceTags(){
        $values = BimpCache::getProductsTagsByTypeArray($this->getData('type'));
        $values2 = array();
        
        foreach($values as $id => $val){
            if($this->id != $id)
                $values2[$id] = $val;
        }
        
        return $values2;
    }
    
    public function actionDeleteFus($data, &$success){
        $success = 'Supprimer avec succés';
        
        $this->db->db->query("UPDATE ".MAIN_DB_PREFIX."product_extrafields SET ".$this->getData('type')." = ".$data['replace']." WHERE ".$this->getData('type')." = ".$this->id."");
        $this->delete();
    }
}
