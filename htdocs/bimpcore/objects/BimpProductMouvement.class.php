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
    
    
    public function getInfosOrigine(){
            $objet = '';
            $module = '';
            $label = '';
            switch($this->getData('origintype')) {
                
                case 'facture':
                    $objet = 'Bimp_Facture';
                    $module = 'bimpcommercial';
                    $label = 'Vente';
                    break;
                
                case 'commande':
                    $objet = 'Bimp_Commande';
                    $module = 'bimpcommercial';
                    $label = 'Vente';
                    break;
                
                case 'order_supplier':
                    $objet = 'Bimp_CommandeFourn';
                    $module = 'bimpcommercial';
                    $label = 'Achat';
                    break;
                
                case 'user':
                    $objet = 'Bimp_User';
                    $module = 'bimpcore';
                    $label = 'Immo';
                    break;  
                
                case 'societe':
                    $objet = 'Bimp_Societe';
                    $module = 'bimpcore';
                    $label = 'Vente';
                    break;
                
                case 'transfert':
                    $objet = 'Transfert';
                    $module = 'bimptransfer';
                    $label = 'Transfert';
                    break;
                
            }
            
            return array($objet, $module, $label);
    }
    
    /**
     * function displayOriginMvt
     * Rôle: Afficher l'origine du mouvement
     * @return string
     */
    public function displayOriginMvt() {
        
        // Si fk_origin > 0
        if($this->getData('fk_origin') > 0) {
            $infos = $this->getInfosOrigine();
            
            
            if($infos[0] != "" && $infos[1] != ""){
                // OPn load l'instance en fonction de l'élément 
                $instance = $this->getInstance($infos[1], $infos[0], $this->getData('fk_origin'));

                if($instance->isLoaded()) // Si l'instance est bien loader
                    return $instance->getNomUrl(); // On affiche le nom de l'éléméent 
                else
                    return "L'objet n'existe plus : ".$infos[1]."/".$infos[0]; // Autrement on dit que l'objet est inexistant
            }
            return 'Object inconnnue'; // Si fk_origin = 0 alors on met aucun
        }
        
        return 'Aucun'; // Si fk_origin = 0 alors on met aucun
        
    }
    
    public function displayReasonMvt() {
        $infos = $this->getInfosOrigine();
        $reason = $infos[2];
        if($reason == ''){
            $reason = 'Inconnue';
            
            if(stripos($this->getData("label"), "Transfert de stock") === 0
                    || stripos($this->getData("label"), "TR-") === 0)
                  $reason = 'Transfert';
            elseif(stripos($this->getData("inventorycode"),'inventory-id-') === 0)
                $reason = 'Inventaire';
        }
        return $reason;
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