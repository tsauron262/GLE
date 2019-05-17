<?php

class BContract_contratLine extends BimpObject {
    
     protected function createDolObject(&$errors) {
         $contrat = $this->getParentInstance();
         $data = $this->getDataArray();
         if(BimpObject::objectLoaded($contrat)) {
             $errors[] = 'id du contrat ' . $contrat->id;
             return 0;
         }
         if($contrat->dol_object->addLine() <= 0) {
             $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($contrat->dol_object));
             return 0;
         }
         
         return 1;
     }
    
}