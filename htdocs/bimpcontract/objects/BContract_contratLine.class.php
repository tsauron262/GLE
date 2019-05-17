<?php

class BContract_contratLine extends BimpObject {
    
     protected function createDolObject(&$errors) {
         global $db;
         $data = $this->getDataArray();
         $contrat = $this->getParentInstance();
         BimpTools::loadDolClass('product');
         BimpTools::loadDolClass('contrat');
         $produit = new Product($db);
         $produit->fetch($data['fk_product']);
         
         if(!BimpObject::objectLoaded($contrat)) {
             $errors[] = 'id du contrat ' . $contrat->id;
             return 0;
         }
         if($contrat->dol_object->addLine($data['description'], $produit->price, $data['qty'], $produit->tva_tx, 0, 0, $produit->id, 0 /* a changer */, date('Y-m-d'), date('Y-m-d')) <= 0) {
             $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($contrat));
             return 0;
         }
         
         return 1;
     }
    
}