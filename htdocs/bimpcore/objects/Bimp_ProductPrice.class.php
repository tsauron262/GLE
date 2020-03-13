<?php

class Bimp_ProductPrice extends BimpObject
{

    public static $bases_types = array(
        'HT'  => 'HT',
        'TTC' => 'TTC'
    );

    public function isEditable($force_edit = false, &$errors = array())
    {
        return 0;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        return 0;
    }

    public static function getCurrentProductPrice($id_product, $date = '')
    {
        if ((int) $id_product) {
            $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX . 'product_price';
            $sql .= ' WHERE `id_product` = ' . (int) $id_product;
            $sql .= ' ORDER BY `rowid` DESC';

            if (!(string) $date) {
                $sql .= ' LIMIT 1';
            }

            $res = self::getBdb()->executeS($sql, 'array');

            $id_pp = 0;
            if (!(string) $date) {
                if (isset($res[0]['rowid'])) {
                    $id_pp = (int) $res[0]['rowid'];
                }
            } elseif (is_array($res)) {
                if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $date)) {
                    $date .= ' 00:00:00';
                }

                // On fait en sorte que si $date est inférieur au plus petit date_price trouvé, ce soit ce dernier qui soit retourné
                foreach ($res as $r) {
                    $id_pp = (int) $r['rowid'];
                    if ($date > $r['date_price']) {
                        break;
                    }
                }
            }

            if ($id_pp) {
                $pp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductPrice', $id_pp);
                if (BimpObject::objectLoaded($pp)) {
                    return $pp;
                }
            }
        }

        return null;
    }

    public function getProductListHeaderButtons()
    {
        $buttons = array();

        $product = $this->getParentInstance();

        if (BimpObject::objectLoaded($product)) {
            if ($product->isActionAllowed('updatePrice') && $product->canSetAction('updatePrice')) {
                $data = array(
                    'price_base'      => (float) $product->getData('price'),
                    'price_base_type' => 'HT',
                    'tva_tx'          => (float) $product->getData('tva_tx')
                );

                $buttons[] = array(
                    'classes'     => array('btn', 'btn-default'),
                    'label'       => 'Nouveau prix de vente',
                    'icon_before' => 'fas_plus-circle',
                    'attr'        => array(
                        'type'    => 'button',
                        'onclick' => $product->getJsActionOnclick('updatePrice', $data, array(
                            'form_name' => 'price'
                        ))
                    )
                );
            }
        }

        return $buttons;
    }
}
