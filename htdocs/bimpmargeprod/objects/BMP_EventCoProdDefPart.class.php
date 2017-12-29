<?php

class BMP_EventCoProdDefPart extends BimpObject {
     public function create()
     {
         $id_event = $this->getData('id_event');
         $id_cat = $this->getData('id_category_montant');
         $id_coprod = $this->getData('id_event_coprod');
         
         if (!is_null($id_event) && !is_null($id_cat) && !is_null($id_coprod)) {
             $result = $this->getList(array(
                 'id_event' => (int) $id_event,
                 'id_category_montant' => (int) $id_cat,
                 'id_event_coprod' => (int) $id_coprod
             ));
             
             if (!is_null($result) && count($result)) {
                 return array('La part par défaut de ce co-producteur est déjà définie pour cette catégorie');
             }
         }
         return parent::create();
     }
}