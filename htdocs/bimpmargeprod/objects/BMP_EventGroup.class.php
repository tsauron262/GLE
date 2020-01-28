<?php

class BMP_EventGroup extends BimpObject {
     public static $ranks = array(
         1 => 'Groupe principal',
         2 => 'Première partie',
         3 => 'Deuxième partie',
         4 => 'Autre'
     );
     
     public function isEventEditable()
     {
         $event = $this->getParentInstance();
         if (!is_null($event) && $event->isLoaded()) {
             return (int) $event->isInEditableStatus();
         }
         
         return 0;
     }
}
