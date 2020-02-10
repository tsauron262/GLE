<?php

class BimpProductMouvement extends BimpObject {

    // Définition des constantes de la class
    CONST STOCK_IN = 0;
    CONST STOCK_OUT = 1;
    
    // Définition des tableau static de la class
    public static $type_mouvement = [
        self::STOCK_IN => ['label' => 'Entrée de stock', 'classes' => ['success'], 'icon' => 'arrow-left'],
        self::STOCK_OUT => ['label' => 'Sortie de stock', 'classes' => ['danger'], 'icon' => 'arrow-right'],
    ];
    
    public static $originetypes = array('' => 'Aucun', 'facture' => 'Facture', 'commande' => 'Commande', 'societe' => 'Vente en caisse ou SAV', 'order_supplier' => 'Commande fournisseur', 'user' => 'Utilisateur');
    
    /**
     * function displayOriginMvt
     * Rôle: Afficher l'origine du mouvement
     * @return string
     */
    public function displayOriginMvt() {
        
        // Si fk_origin > 0
        if($this->getData('fk_origin') > 0) {
            
            // On parcours origintype
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
            
            // OPn load l'instance en fonction de l'élément 
            $instance = $this->getInstance($module, $objet, $this->getData('fk_origin'));
            
            if($instance->isLoaded()) // Si l'instance est bien loader
                return $instance->getNomUrl(); // On affiche le nom de l'éléméent 
            else
                return "L'objet n'existe plus"; // Autrement on dit que l'objet est inexistant
        }
        
        return 'Aucun'; // Si fk_origin = 0 alors on met aucun
        
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