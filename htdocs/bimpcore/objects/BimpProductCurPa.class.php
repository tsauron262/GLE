<?php

class BimpProductCurPa extends BimpObject
{

    public static $origin_types = array(
        'commande_fourn' => 'Commande fournisseur',
        'facture_fourn'  => 'Facture fournisseur',
        'fourn_price'    => 'Prix d\'achat fournisseur',
        'pmp'            => 'Prix moyen pondéré'
    );

    // Getters Static

    public static function getProductCurPa($id_product, $date = '')
    {
        if ((int) $id_product) {
            $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX . 'bimp_product_cur_pa';
            $sql .= ' WHERE `id_product` = ' . (int) $id_product;
            $sql .= ' ORDER BY `date_from` DESC';

            if (!(string) $date) {
                $sql .= ' LIMIT 1';
            }

            $res = self::getBdb()->executeS($sql, 'array');

            $id_pa = 0;
            if (!(string) $date) {
                if (isset($res[0]['id'])) {
                    $id_pa = (int) $res[0]['id'];
                }
            } elseif (is_array($res)) {
                // On fait en sorte que si $date est inférieur au plus petit date_from trouvé, ce soit ce dernier qui soit retourné
                foreach ($res as $r) {
                    $id_pa = (int) $r['id'];
                    if ($r['date_from'] < $date && (!(string) $r['date_to'] || $r['date_to'] > $date)) {
                        break;
                    }
                }
            }

            if ($id_pa) {
                $pa = BimpCache::getBimpObjectInstance('bimpcore', 'BimpProductCurPa', $id_pa);
                if (BimpObject::objectLoaded($pa)) {
                    return $pa;
                }
            }
        }

        return null;
    }

    public static function getProductCurPaAmount($id_product, $date = '', $with_default = true)
    {
        $pa = null;

        if ((int) $id_product) {
            $sql = 'SELECT id, amount, date_from, date_to FROM ' . MAIN_DB_PREFIX . 'bimp_product_cur_pa';
            $sql .= ' WHERE `id_product` = ' . (int) $id_product;
            $sql .= ' ORDER BY `date_from` DESC';

            if (!(string) $date) {
                $sql .= ' LIMIT 1';
            }

            $res = self::getBdb()->executeS($sql, 'array');

            if (!(string) $date) {
                if (isset($res[0]['amount'])) {
                    $pa = (float) $res[0]['amount'];
                }
            } elseif (is_array($res)) {
                // On fait en sorte que si $date est inférieur au plus petit date_from trouvé, ce soit ce dernier qui soit retourné
                foreach ($res as $r) {
                    $pa = (float) $r['amount'];
                    if ($r['date_from'] < $date && (!(string) $r['date_to'] || $r['date_to']) > $date) {
                        break;
                    }
                }
            }

            if (is_null($pa) && $with_default) {
                $sql = 'SELECT rowid as id, price FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price';
                $sql .= ' WHERE fk_product = ' . (int) $id_product;
                $sql .= ' ORDER BY `datec` DESC';

                if (!$date) {
                    $sql .= ' LIMIT 1';
                }

                $res = self::getBdb()->executeS($sql);

                if (!(string) $date) {
                    if (isset($res[0]['price'])) {
                        $pa = (float) $res[0]['price'];
                    }
                } elseif (is_array($res)) {
                    foreach ($res as $r) {
                        $pa = (float) $r['price'];
                        if ($r['datec'] < $date) {
                            break;
                        }
                    }
                }

                if (is_null($pa)) {
                    $pa = (float) self::getBdb()->getValue('product', 'cur_pa_ht', 'rowid = ' . $id_product);

                    if (!$pa) {
                        $pa = (float) self::getBdb()->getValue('product', 'pmp', 'rowid = ' . $id_product);
                    }
                }
            }
        }

        return $pa;
    }

    // Affichage: 

    public function displayOrigine()
    {
        if ($this->isLoaded()) {
            if ($this->getData('origin'))
                switch ($this->getData('origin')) {
                    case 'commande_fourn':
                        $comm = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $this->getData('id_origin'));
                        if (BimpObject::objectLoaded($comm)) {
                            return 'Commande fournisseur: ' . $comm->getNomUrl(0, 1, 1, 'full');
                        } elseif ((int) $this->getData('id_origin')) {
                            return '<span class="warning">La commande fournisseur d\'ID ' . (int) $this->getData('id_origin') . ' n\'existe plus</span>';
                        } else {
                            return '<span class="danger">ID de la commande fournisseur absent</span>';
                        }
                        break;

                    case 'facture_fourn':
                        $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', (int) $this->getData('id_origin'));
                        if (BimpObject::objectLoaded($fac)) {
                            return 'Facture fournisseur: ' . $fac->getNomUrl(0, 1, 1, 'full');
                        } elseif ((int) $this->getData('id_origin')) {
                            return '<span class="warning">La facture fournisseur d\'ID ' . (int) $this->getData('id_origin') . ' n\'existe plus</span>';
                        } else {
                            return '<span class="danger">ID de la facture fournisseur absent</span>';
                        }
                        break;

                    case 'fourn_price':
                        $pfp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', (int) $this->getData('id_origin'));
                        if (BimpObject::objectLoaded($pfp)) {
                            return $pfp->getNomUrl(0, 0, 0, 'default');
                        } elseif ((int) $this->getData('id_origin')) {
                            return '<span class="warning">Le prix d\'achat fournisseur d\'ID ' . (int) $this->getData('id_origin') . ' n\'existe plus</span>';
                        } else {
                            return '<span class="danger">ID du prix d\'achat fournisseur absent</span>';
                        }
                        break;

                    case 'pmp':
                        return self::$origin_types['pmp'];
                }
        }

        return '';
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        if (!(int) $this->getData('id_product')) {
            return array('ID du produit absent');
        }

        $prevPa = null;
        if (!$this->getData('date_from')) {
            // Il s'agit d'un nouveau PA courant 
            $dateFrom = date('Y-m-d H:i:s');
            $this->set('date_from', $dateFrom);
            $prevPa = self::getProductCurPa((int) $this->getData('id_product'));
        } else {
            // Il s'agit d'une insertion personnalisée'
            $dateFrom = $this->getData('date_from');
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            if (BimpObject::objectLoaded($prevPa)) {
                $err = $prevPa->updateField('date_to', $dateFrom);
                if (count($err)) {
                    $warnings[] = BimpTools::getMsgFromArray($err, 'Echec de la mise à jour de la date de fin du Prix d\'achat précédant');
                }
                
                $this->db->update('product', array(
                    'cur_pa_ht' => (float) $this->getData('amount')
                        ), 'rowid = ' . (int) $this->getData('id_product'));
            }
        }

        return $errors;
    }
}
