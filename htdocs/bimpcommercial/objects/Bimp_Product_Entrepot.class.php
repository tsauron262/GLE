<?php



class Bimp_Product_Entrepot extends BimpObject
{

    function getValue1(){
        return 56;
    }
    
    
    public function fetchExtraFields()
    {
        $fields = array(
            'ventes_qty'        => 0,
            'ventes_ht'         => 0,
            'stockShowRoom'     => 0
        );
        
//        $prod = $this->getChildObject('product');
        $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $this->getData("fk_product"));
        
        $tabVentes = $prod->getVentes(null,null,$this->getData('fk_entrepot'));
        if($tabVentes['qty'] > 0)
            $fields['ventes_qty'] = $tabVentes['qty'];
        if($tabVentes['total_ht'] > 0)
            $fields['ventes_ht'] = $tabVentes['total_ht'];
        $stockShowRoom = $prod->getStockShoowRoom($this->getData('fk_entrepot'));
        if($stockShowRoom > 0)
            $fields['stockShowRoom'] = $stockShowRoom;

        return $fields;
    }
    
        public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array())
    {
        switch ($field_name) {
            case 'categ1':
            case 'categ2':
            case 'categ3':
                $alias = 'cat_prod'.$field_name;
                $joins[$alias] = array(
                    'alias' => $alias,
                    'table' => 'categorie_product',
                    'on'    => $alias . '.fk_product = a.fk_product'
                );
                $filters[$alias.'.fk_categorie'] = array(
                    'in' => $values
                );
                return;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors);
    }
}
