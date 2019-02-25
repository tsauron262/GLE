<?php


require_once DOL_DOCUMENT_ROOT.'/bimpcore/objects/BimpDolObject.class.php';

abstract class extraFI extends BimpDolObject{
    
    // gestion des extra
    
    public function getExtra($field){
        $field = str_replace("extra", "", $field);
        if ($this->isLoaded()){
            if(!$this->extraFetch){
                $this->dol_object->fetch_extra();
                $this->extraFetch = true;
            }
            return $this->dol_object->extraArr[$field];
        }
    }
    
    
     public function insertExtraFields()
    {
         if(!is_object($this->dol_object)){
             $this->dol_object = new Synopsisfichinter($this->db->db);
             $this->dol_object->id = $this->id;
         }
         $this->updateExtraFields();

        return array();
    }

    public function updateExtraFields()
    {
        $list = $this->getExtraFields();
        foreach($list as $extra)
            if($this->getData($extra) != $this->getInitData($extra))
                $this->updateExtraField ($extra, $this->getData($extra),0);

        return array();
    }

    public function updateExtraField($field_name, $value, $id_object)
    {
        if($id_object == $this->dol_object->id || $id_object == 0){
            $field = str_replace("extra", "", $field_name);
            $this->dol_object->setExtra($field, $value);
        }

        return array();
    }

    public function fetchExtraFields()
    {
        $return = array();
        $list = $this->getExtraFields();
        foreach($list as $extra)
            $return[$extra] = $this->getExtra ($extra);

        return $return;
    }

    public function deleteExtraFields()
    {
        // Supprimer les extrafields
        // Retourner un tableau d'erreurs

        if (count($this->getExtraFields())) {
            return array('Fonction de suppression des champs supplémentaires non implémentée');
        }

        return array();
    }

    public function getExtraFieldSavedValue($field, $id_object)
    {
        return $this->getInitData($field);
    }

    public function getExtraFieldFilterKey($field, &$joins, $main_alias = '')
    {
        // Retourner la clé de filtre SQl sous la forme alias_table.nom_champ_db 
        // Implémenter la jointure dans $joins en utilisant l'alias comme clé du tableau (pour éviter que la même jointure soit ajouté plusieurs fois à $joins). 
        // Si $main_alias est défini, l'utiliser comme préfixe de alias_table. Ex: $main_alias .'_'.$alias_table (Bien utiliser l'underscore).  
        // ET: utiliser $main_alias à la place de "a" dans la clause ON. 
//        Ex: 
//        $join_alias = ($main_alias ? $main_alias . '_' : '') . 'xxx';
//        $joins[$join_alias] = array(
//            'alias' => $join_alias,
//            'table' => 'nom_table',
//            'on'    => $join_alias . '.xxx = ' . ($main_alias ? $main_alias : 'a') . '.xxx'
//        );
//        
//        return $join_alias.'.nom_champ_db';

        return '';
    }
}