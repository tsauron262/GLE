<?php

class BMP_Tarif extends BimpObject {
    public function isEventEditable()
     {
         $event = $this->getParentInstance();
         if (!is_null($event) && $event->isLoaded()) {
             return $event->isEditable();
         }
         
         return 0;
     }
     
     public function getCreateForm()
     {
         if ($this->isEventEditable()) {
             return 'default';
         }
         
         return '';
     }
}