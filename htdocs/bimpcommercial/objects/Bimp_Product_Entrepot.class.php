<?php

class Bimp_Product_Entrepot extends BimpObject
{

    function getValue1()
    {
        return 56;
    }
    
    function actionPrintEtiquettes($data, &$success){
        $prod = BimpObject::getInstance('bimpcore', 'Bimp_Product');
        
        $newIds = array();
        foreach($data['id_objects'] as $id){
            $tmp = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Product_Entrepot', $id);
            $newIds[] = $tmp->getData('fk_product');
          
        }
        $data['id_objects'] = $newIds;
        return $prod->actionPrintEtiquettes($data, $success);
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

    public function fetchExtraFields()
    {
        $fields = array(
            'ventes_qty'    => 0,
            'ventes_ht'     => 0,
            'stockShowRoom' => 0
        );

//        $prod = $this->getChildObject('product');
        $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $this->getData("fk_product"));

        $tabVentes = $prod->getVentes(null, null, $this->getData('fk_entrepot'));
        if ($tabVentes['qty'] > 0)
            $fields['ventes_qty'] = $tabVentes['qty'];
        if ($tabVentes['total_ht'] > 0)
            $fields['ventes_ht'] = $tabVentes['total_ht'];
        $stockShowRoom = $prod->getStockShoowRoom($this->getData('fk_entrepot'));
        if ($stockShowRoom > 0)
            $fields['stockShowRoom'] = $stockShowRoom;

        return $fields;
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array())
    {
        switch ($field_name) {
            case 'categ1':
            case 'categ2':
            case 'categ3':
                $alias = 'cat_prod' . $field_name;
                $joins[$alias] = array(
                    'alias' => $alias,
                    'table' => 'categorie_product',
                    'on'    => $alias . '.fk_product = a.fk_product'
                );
                $filters[$alias . '.fk_categorie'] = array(
                    'in' => $values
                );
                return;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors);
    }
}
