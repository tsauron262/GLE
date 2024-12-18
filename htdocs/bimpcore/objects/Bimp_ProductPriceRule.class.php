<?php

class Bimp_ProductPriceRule extends BimpObject
{

    public static $types_condition = array(
        'qty_min' => 'Quantité minimum'
    );
    public static $types_impact = array(
        'reduc_percent' => 'Pourcentage réduction',
        'reduc_amount'  => 'Montant réduction',
        'new_price'     => 'Nouveau prix'
    );

    // Getters données : 

    public function getPrice($base_pu_ht = null)
    {
        $type = $this->getData('type_impact');
        $val = (float) $this->getData('impact_value');

        if ($type === 'new_price') {
            return $val;
        }

        if (is_null($base_pu_ht)) {
            $prod = $this->getParentInstance();

            if (BimpObject::objectLoaded($prod)) {
                $base_pu_ht = $prod->getData('price');
            }
        }

        switch ($type) {
            case 'reduc_percent':
                return $base_pu_ht - ($base_pu_ht * ($val / 100));

            case 'reduc_amount':
                return $base_pu_ht - $val;
        }

        return $base_pu_ht;
    }

    // Getters static : 

    public static function getBestPriceForProduct($product, $params = array(), $active_only = true)
    {
        $qty = abs($qty);
        $base_pu_ht = $product->getData('price');
        $best_pu_ht = $base_pu_ht;

        $filters = array(
            'id_product' => $product->id
        );

        if ($active_only) {
            $filters['active'] = 1;
        }

        $rules = self::getBimpObjectObjects('bimpcore', 'Bimp_ProductPriceRule', $filters);

        foreach ($rules as $rule) {
            if (!$rule->checkCondition($params)) {
                continue;
            }
            
            $rule_price = $rule->getPrice($base_pu_ht, $params);
            if ($rule_price < $best_pu_ht) {
                $best_pu_ht = $rule_price;
            }
        }

        return $best_pu_ht;
    }

    // Affichages : 

    public function displayCondition()
    {
        $html = '';

        $type = $this->getData('type_condition');
        $val = $this->getData('cond_value');

        switch ($type) {
            case 'qty_min':
                $html .= 'Quantité minimum : ' . BimpTools::displayFloatValue($val, 6, ',', 0, 0, 1, 0, 1, 1);
                break;
        }

        return $html;
    }

    public function displayImpact()
    {
        $html = '';

        $type = $this->getData('type_impact');
        $val = $this->getData('impact_value');

        switch ($type) {
            case 'reduc_percent':
                $html .= 'Réduction de ' . BimpTools::displayFloatValue($val, 6, ',', 0, 0, 1, 0, 1, 1) . ' %';
                break;

            case 'reduc_amount':
                $html .= 'Réduction de ' . BimpTools::displayMoneyValue($val, 'EUR', 0, 0, 0, 2, 1, ',', 1, 8);
                break;

            case 'new_price':
                $html .= 'Nouveau prix unitaire HT : ' . BimpTools::displayMoneyValue($val, 'EUR', 0, 0, 0, 2, 1, ',', 1, 8);
                break;
        }

        return $html;
    }

    // Traitements : 

    public function checkCondition($params = array())
    {
        $val = (float) $this->getData('cond_value');
        
        switch ($this->getData('type_condition')) {
            case 'qty_min': 
                if (isset($params['qty']) && (float) $params['qty'] >= $val) {
                    return 1;
                }
                break;
        }
        
        return 0;
    }
}
