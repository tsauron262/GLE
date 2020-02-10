<?php

class BimpProductMouvement extends BimpObject {
     
    public static $originetypes = array('' => 'Aucun', 'facture' => 'Facture', 'commande' => 'Commande', 'societe' => 'Vente en caisse ou SAV', 'order_supplier' => 'Commande fournisseur', 'user' => 'Utilisateur');
    
    public function displayOriginMvt() {
        
        
        if($this->getData('fk_origin') > 0) {
            
            switch($this->getData('origintype')) {
                
                case 'facture':
                    $objet = 'Bimp_Facture';
                    $module = 'bimpcommercial';
                    break;
                
                case 'commande':
                    $objet = 'Bimp_Commande';
                    $module = 'bimpcommercial';
                    break;
                
                case 'order_supplier':
                    $objet = 'Bimp_CommandeFourn';
                    $module = 'bimpcommercial';
                    break;
                
                case 'user':
                    $objet = 'Bimp_User';
                    $module = 'bimpcore';
                    break;  
                
                case 'societe':
                    $objet = 'Bimp_Societe';
                    $module = 'bimpcore';
                    break;
                
            }
            
            
            $instance = $this->getInstance($module, $objet, $this->getData('fk_origin'));
            
            if($instance->isLoaded()) 
                return $instance->getNomUrl();
            else
                return "L'objet n'existe plus";
        }
        
        return 'Aucun';
        
    }
    
    public function displayProduct()
    {
        $product = $this->getChildObject('product');

        if (BimpObject::objectLoaded($product)) {
            $html = $product->dol_object->getNomUrl(1);
            $html .= BimpRender::renderObjectIcons($product, 1, 'default');
            $html .= '<br/>';
            $html .= $product->getData('label');
            return $html;
        }

        return (int) $this->getData('fk_product');
    }
    
    public function displayId() {
        
        return "<b>#" . $this->id . "</b>";
        
    }
    
    
}