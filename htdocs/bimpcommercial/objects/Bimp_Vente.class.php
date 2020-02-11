<?php

class Bimp_Vente extends BimpObject
{

    public static $facture_fields = array('date' => 'datef', 'id_client' => 'fk_soc', 'id_user' => 'fk_user_author');
    public static $facture_extrafields = array('id_entrepot' => 'entrepot', 'secteur' => 'type');

    // Getters booléens: 

    public function isCreatable($force_create = false)
    {
        return 0;
    }

    public function isEditable($force_edit = false)
    {
        return 0;
    }

    public function isDeletable($force_delete = false)
    {
        return 0;
    }

    // Overrides : 

    public function fetchExtraFields()
    {
        $fields = array(
            'date'               => '',
            'id_client'          => 0,
            'id_entrepot'        => 0,
            'id_user'            => 0,
            'secteur'            => '',
            'marque'             => 0,
            'gamme'              => 0,
            'product_categories' => array()
        );

        if ($this->isLoaded()) {
            $facture = $this->getParentInstance();
            if (BimpObject::objectLoaded($facture)) {
                $fields['date'] = $facture->getData('datef');
                $fields['id_client'] = $facture->getData('fk_soc');
                $fields['id_entrepot'] = $facture->getData('entrepot');
                $fields['id_user'] = $facture->getData('fk_user_author');
                $fields['secteur'] = $facture->getData('ef_type');
            }

            if ((int) $this->getData('fk_product')) {

                $categories = BimpCache::getProductCategoriesArray((int) $this->getData('fk_product'));
                foreach ($categories as $id_category => $label) {
                    $fields['product_categories'][] = (int) $id_category;
                }

                $marques_categories = BimpCache::getMarquesList();
                foreach ($fields['product_categories'] as $id_category) {
                    if (in_array((int) $id_category, $marques_categories)) {
                        $fields['marque'] = $id_category;
                        break;
                    }
                }

                $gammes_materiel_categories = BimpCache::getGammesMaterielList();
                foreach ($fields['product_categories'] as $id_category) {
                    if (in_array((int) $id_category, $gammes_materiel_categories)) {
                        $fields['gamme'] = $id_category;
                        break;
                    }
                }
            }
        }

        return $fields;
    }

    public function getExtraFieldSavedValue($field, $id_object)
    {
        $instance = self::getBimpObjectInstance($this->module, $this->object_name, (int) $id_object);

        if (BimpObject::objectLoaded($instance)) {
            if (array_key_exists($field, self::$facture_fields)) {
                if ((int) $instance->getData('fk_facture')) {
                    return $this->db->getValue('facture', self::$facture_fields[$field], '`rowid` = ' . (int) $instance->getData('fk_facture'));
                }
            } elseif (array_key_exists($field, self::$facture_extrafields)) {
                if ((int) $instance->getData('fk_facture')) {
                    return $this->db->getValue('facture_extrafields', self::$facture_fields[$field], '`fk_object` = ' . (int) $instance->getData('fk_facture'));
                }
            } elseif ($field === 'categories') {
                $id_product = (int) $instance->getData('fk_product');
                if ($id_product) {
                    if (isset(self::$cache['product_' . $id_product . '_categories_array'])) {
                        unset(self::$cache['product_' . $id_product . '_categories_array']);
                    }

                    $categories = array();
                    foreach (self::getProductCategoriesArray($id_product) as $id_category => $label) {
                        $categories[] = (int) $id_category;
                    }

                    return $categories;
                }

                return array();
            } elseif ($field === 'marque') {
                $id_product = (int) $instance->getData('fk_product');
                if ($id_product) {
                    $sql = 'SELECT cp.`fk_categorie` FROM ' . MAIN_DB_PREFIX . 'categorie_product cp';
                    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie c ON c.rowid = cp.fk_categorie';
                    $sql .= ' WHERE cp.fk_product = ' . $id_product . ' AND c.fk_parent IN (' . BimpCore::getConf('marques_parent_categories') . ')';
                    $sql .= ' LIMIT 1';

                    $result = $this->db->executeS($sql, 'array');
                    if (isset($result[0]['fk_categorie'])) {
                        return (int) $result[0]['fk_categorie'];
                    }
                }
                return 0;
            } elseif ($field === 'gamme') {
                $id_product = (int) $instance->getData('fk_product');
                if ($id_product) {
                    $cats = BimpCache::getGammesMaterielList();
                    $sql = 'SELECT `fk_categorie` FROM ' . MAIN_DB_PREFIX . 'categorie_product';
                    $sql .= ' WHERE `fk_product` = ' . $id_product . ' AND fk_categorie IN (' . implode(',', $cats) . ')';
                    $sql .= ' LIMIT 1';

                    $result = $this->db->executeS($sql, 'array');
                    if (isset($result[0]['fk_categorie'])) {
                        return (int) $result[0]['fk_categorie'];
                    }
                }
                return 0;
            }
        }

        return null;
    }

    public function getExtraFieldFilterKey($field, &$joins, $main_alias = '')
    {
        if (array_key_exists($field, self::$facture_fields)) {
            $join_alias = ($main_alias ? $main_alias . '_' : '') . 'facture';
            $joins[$join_alias] = array(
                'table' => 'facture',
                'alias' => $join_alias,
                'on'    => $join_alias . '.rowid = ' . ($main_alias ? $main_alias : 'a') . '.fk_facture'
            );

            return $join_alias . '.' . self::$facture_fields[$field];
        } elseif (array_key_exists($field, self::$facture_extrafields)) {
            $join_alias = ($main_alias ? $main_alias . '_' : '') . 'factureef';
            $joins[$join_alias] = array(
                'table' => 'facture_extrafields',
                'alias' => $join_alias,
                'on'    => $join_alias . '.fk_object = ' . ($main_alias ? $main_alias : 'a') . '.fk_facture'
            );

            return $join_alias . '.' . self::$facture_extrafields[$field];
        } elseif ($field === 'categories') {
            // todo...
        } elseif ($field === 'marque' || $field === 'gamme') {
            $join_alias = ($main_alias ? $main_alias . '_' : '') . 'prodcat';
            $joins[$join_alias] = array(
                'table' => 'categorie_product',
                'alias' => $join_alias,
                'on'    => $join_alias . '.fk_product = ' . ($main_alias ? $main_alias : 'a') . '.fk_product'
            );
            return $join_alias . '.fk_categorie';
        }

        return '';
    }

    public function updateExtraField($field_name, $value, $id_object)
    {
        return array();
    }

    // Getters: 

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'categ1':
            case 'categ2':
            case 'categ3':
                $alias = 'cat_prod';
                $joins[$alias] = array(
                    'alias' => $alias,
                    'table' => 'categorie_product',
                    'on'    => $alias . '.fk_product = a.fk_product'
                );
                $filters['cat_prod.fk_categorie'] = array(
                    ($excluded ? 'not_' : '') . 'in' => $values
                );
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors, $excluded);
    }

    public function getListHeaderButtons()
    {
        $buttons = array();

        $dt = new DateTime();
        $dow = (int) $dt->format('w');
        if ($dow > 0) {
            $dt->sub(new DateInterval('P' . $dow . 'D')); // Premier dimanche précédent. 
        }
        $date_to = $dt->format('Y-m-d');

        $dt->sub(new DateInterval('P7D'));
        $date_from = $dt->format('Y-m-d');

        $buttons[] = array(
            'classes'     => array('btn', 'btn-default'),
            'label'       => 'Générer rapport Apple',
            'icon_before' => 'fas_file-excel',
            'attr'        => array(
                'type'    => 'button',
                'onclick' => $this->getJsActionOnclick('generateAppleCSV', array(
                    'date_from' => $date_from,
                    'date_to'   => $date_to
                        ), array(
                    'form_name' => 'generate_apple_cvs'
                ))
            )
        );

        return $buttons;
    }

    // Affichage: 

    public function displayShortRef()
    {
        if ($this->isLoaded()) {
            $prod = BimpCache::getBimpObjectInstance('bimpcore', "Bimp_Product", $this->getData('fk_product'));
            $ref = $prod->getData('ref');
            if (substr($ref, 3, 1) === "-")
                $ref = substr($ref, 4);
            return $ref;
        }
    }

    public function displayCountry()
    {
        if ($this->isLoaded()) {
            $cli = BimpCache::getBimpObjectInstance('bimpcore', "Bimp_Client", $this->getData('id_client'));
            return $cli->displayCountry();
        }
    }

    public function displayTypeMateriel()
    {
        if ($this->isLoaded()) {
            $cats = $this->getData('product_categories');
            if (!is_array($cats)) {
                $cats = array();
            }

            if (in_array((int) BimpCore::getConf('desktop_id_categorie'), $cats)) {
                return 'desktop';
            } elseif (in_array((int) BimpCore::getConf('notebook_id_categorie'), $cats)) {
                return 'notebook';
            }
        }

        return '';
    }

    // Traitements : 

    public function generateAppleCSV($dateFrom, $dateTo, $distribute_ca = false, &$errors = array())
    {
        set_time_limit(600000);

        $id_category = (int) BimpCore::getConf('id_categorie_apple');

        if (!$id_category) {
            $errors[] = 'ID de la catégorie "APPLE" non configurée';
            return array(
                'filename' => '',
                'html'     => ''
            );
        }

        $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');

        $products_list = $product->getList(array(
            'ef.collection' => (int) $id_category
                ), null, null, 'rowid', 'asc', 'array', array('rowid', 'ref', 'price', 'no_fixe_prices', 'pmp', 'cur_pa_ht'), array(
            'ef' => array(
                'alias' => 'ef',
                'table' => 'product_extrafields',
                'on'    => 'a.rowid = ef.fk_object'
            )
        ));

//        $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');
//        $products_list = $product->getList(array(
//            'ref' => array(
//                'part_type' => 'beginning',
//                'part'      => 'APP-'
//            )
//                ), null, null, 'id', 'asc', 'array', array('rowid', 'ref'));
//        $file_str = '';
//
//        $file_str .= implode(';', array(
//            'ID d’emplacement pour le(s) entrepôt(s), le(s) magasin(s) et tout autre point de vente (peut être un ID attribué par le client ou par Apple)',
//            'Référence commerciale du produit (MPN) / Code JAN',
//            'Unités vendues et expédiées depuis les entrepôts ou les points de vente au client final (quantité brute en cas de « Quantité vendue renvoyée », sinon quantité nette).',
//            'Unités retournées par le client final.',
//            'Unités en stock prêtes à la vente dans les entrepôts et les points de vente (sans paiement ni dépôt du client) ',
//            'Unités de démonstration faisant partie des stocks dans les points de vente et les entrepôts',
//            'Unités en transit : entre les entrepôts et les points de vente ou inversement',
//            'Stocks invendables (par exemple, unités endommagées, hors d’usage à l’arrivée ou ouvertes avant d’être renvoyées)',
//            'Unités (avec paiement/versement d’arrhes du client) en attente d’expédition dans les entrepôts et les points de vente)',
//            'Unités commandées (avec paiement/versement d’arrhes du client) non expédiées pour cause de stocks insuffisants.',
//            'Stocks envoyés par Apple ou ses distributeurs et réservés dans les entrepôts ou les points de vente',
//            '"1R - Université, Établissement d’enseignement supérieur ou école
//21 - Petite entreprise
//2L - Entreprise(ventes à une personne morale)
//BB - Partenaire commercial
//CQ - Siège social(achats destinés à la revente)
//E4 - Autre personne ou entité associée à l’étudiant
//EN - Utilisateur final
//HS - Établissement d’enseignement secondaire
//M8 - Établissement d’enseignement
//VO - École élémentaire
//VQ - Collège
// QW - Gouvernement"',
//            'Erreurs de validation de base'
//        )) . "\n";
        $file_str = '"ID d’emplacement

Champ obligatoire
(23)";"Référence commerciale du produit Apple (MPN) /  Code JAN (si le code JAN indiqué est approuvé par Apple)

Champ obligatoire
(30)";"Quantité vendue

Champ obligatoire
(10)";"Quantité vendue renvoyée

 Champ recommandé
(10)";"Quantité disponible en stock

Champ obligatoire
(10)";"Quantité de stocks en démonstration

Champ recommandé
(10)";"Quantité de stocks en transit interne

Champ recommandé
(10)";"Quantité de stocks invendable

Champ recommandé
(10)";"Quantité de stocks réservée

Champ recommandé
(10)";"Quantité de stocks dont la commande est en souffrance

Champ recommandé
(10)";"Quantité de stocks reçue

Champ recommandé
(10)";"Type du client final

Champ recommandé
(2)";Erreurs;Prix d\'achat actuel
ID d’emplacement pour le(s) entrepôt(s), le(s) magasin(s) et tout autre point de vente (peut être un ID attribué par le client ou par Apple);Référence commerciale du produit (MPN) / Code JAN;Unités vendues et expédiées depuis les entrepôts ou les points de vente au client final (quantité brute en cas de « Quantité vendue renvoyée », sinon quantité nette).;Unités retournées par le client final.;Unités en stock prêtes à la vente dans les entrepôts et les points de vente (sans paiement ni dépôt du client) ;Unités de démonstration faisant partie des stocks dans les points de vente et les entrepôts;Unités en transit : entre les entrepôts et les points de vente ou inversement ;Stocks invendables (par exemple, unités endommagées, hors d’usage à l’arrivée ou ouvertes avant d’être renvoyées);Unités (avec paiement/versement d’arrhes du client) en attente d’expédition dans les entrepôts et les points de vente) ;Unités commandées (avec paiement/versement d’arrhes du client) non expédiées pour cause de stocks insuffisants.;Stocks envoyés par Apple ou ses distributeurs et réservés dans les entrepôts ou les points de vente ;"1R - Université, Établissement d’enseignement supérieur ou école
21 - Petite entreprise
2L - Entreprise(ventes à une personne morale)
BB - Partenaire commercial
CQ - Siège social(achats destinés à la revente)
E4 - Autre personne ou entité associée à l’étudiant
EN - Utilisateur final
HS - Établissement d’enseignement secondaire
M8 - Établissement d’enseignement
VO - École élémentaire
VQ - Collège
 QW - Gouvernement

";' . "\n";

        BimpObject::loadClass('bimpcore', 'BimpProductCurPa');
        $entrepots = BimpCache::getEntrepotsShipTos();
        $entrepots[-9999] = "1683245";
        $shiptos_data = array();

        $total_ca = 0;
        foreach ($products_list as $p) {
            $entrepots_data = $product->getAppleCsvData($dateFrom, $dateTo, $entrepots, $p['rowid']);

            if ((int) $p['no_fixe_prices']) {
                $pa_ht = 0;
            } else {
                $pa_ht = (float) $p['cur_pa_ht'];

                if (is_null($pa_ht)) {
                    if (!$pa_ht) {
                        $sql = 'SELECT price FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price WHERE';
                        $sql .= ' fk_product = ' . (int) $p['rowid'];
                        $sql .= ' ORDER BY tms DESC LIMIT 1';

                        $result = $this->db->executeS($sql);
                        if (isset($result[0]->price)) {
                            $pa_ht = (float) $result[0]->price;
                        } elseif (isset($p['pmp'])) {
                            $pa_ht = (float) $p['pmp'];
                        } else {
                            $pa_ht = 0;
                        }
                    }
                }
            }

            foreach ($entrepots_data as $ship_to => $data) {
                if ($data['ventes'] < 0)
                    $data['ventes'] = 0;
                if ($data['stock'] < 0)
                    $data['stock'] = 0;
                if ($data['stock_showroom'] < 0)
                    $data['stock_showroom'] = 0;

                if ((int) $data['ventes'] || (int) $data['stock'] || (int) $data['stock_showroom']) {
                    if (!isset($shiptos_data[$ship_to])) {
                        $shiptos_data[$ship_to] = array(
                            'total_ca' => 0,
                            'products' => array()
                        );
                    }

                    $shiptos_data[$ship_to]['products'][(int) $p['rowid']] = array(
                        'ref'            => $p['ref'],
                        'pu_ht'          => $p['price'],
                        'pa_ht'          => $pa_ht,
                        'ventes'         => $data['ventes'],
                        'stock'          => $data['stock'],
                        'stock_showroom' => $data['stock_showroom']
                    );

                    $product_ca = (float) $data['ventes'] * (float) $pa_ht;
                    $shiptos_data[$ship_to]['total_ca'] += $product_ca;
                    $total_ca += $product_ca;
                }
            }
        }
        
        $html = '';
        if ($distribute_ca) {
            $shiptos = explode(',', BimpCore::getConf('csv_apple_distribute_ca_shiptos'));
            $shiptos_data = $this->distributeCaForShiptos($total_ca, $shiptos_data, $shiptos, 80, $html);
        }

        foreach ($products_list as $p) {
            foreach ($shiptos_data as $shipTo => $shipToData) {
                if (isset($shipToData['products'][(int) $p['rowid']])) {
                    $prod = $shipToData['products'][(int) $p['rowid']];
                    $file_str .= implode(';', array(
                                $shipTo,
                                preg_replace('/^APP\-(.*)$/', '$1', $prod['ref']),
                                $prod['ventes'],
                                0,
                                $prod['stock'],
                                $prod['stock_showroom'],
                                0,
                                0,
                                0,
                                0,
                                0,
                                '',
                                '',
                                $prod['pa_ht']
                            )) . "\n";
                }
            }
        }

        $dir = DOL_DATA_ROOT . '/bimpcore/apple_csv/' . date('Y');
        $fileName = $dateFrom . '_' . $dateTo . '.csv';

        if (!file_exists(DOL_DATA_ROOT . '/bimpcore/apple_csv')) {
            mkdir(DOL_DATA_ROOT . '/bimpcore/apple_csv');
        }

        if (!file_exists($dir)) {
            mkdir($dir);
        }

        if (!file_put_contents($dir . '/' . $fileName, $file_str)) {
            $errors[] = 'Echec de la création du fichier CSV';
        }

        return array(
            'filename' => $fileName,
            'html'     => $html
        );
    }

    public function distributeCaForShiptos($total_ca, $shipTosData, $shiptos, $percent, &$html = '')
    {
        if (!(float) $total_ca) {
            $html .= BimpRender::renderAlerts('CA total: 0,00 €');
            return $shipTosData;
        }

        $total_ca_v1 = 0;
        $total_ca_v2 = 0;

        $v1_shipTos = array();
        $v2_shipTos = array();

        foreach ($shipTosData as $shipTo => $shipToData) {
            $data = array(
                'shipTo'   => $shipTo,
                'products' => array()
            );
            foreach ($shipToData['products'] as $id_p => $p) {
                $data['products'][] = $id_p;
            }
            $data['nProds'] = (count($data['products']) - 1);

            if (in_array($shipTo, $shiptos)) {
                $total_ca_v2 += (float) $shipToData['total_ca'];
                $v2_shipTos[] = $data;
            } else {
                $total_ca_v1 += (float) $shipToData['total_ca'];
                $v1_shipTos[] = $data;
            }

            if ((float) $shipToData['total_ca']) {
                $shipTosData[$shipTo]['ca_tx'] = $shipToData['total_ca'] / $total_ca;
            } else {
                $shipTosData[$shipTo]['ca_tx'] = 0;
            }
        }

        $rate = 80;
        $v2 = ($total_ca_v2 / $total_ca) * 100;

        // Ajustement aléatoire du % v2: 
        $adjust = (rand(0, 500) / 100);
        if ((int) rand(0, 1)) {
            $adjust *= -1;
        }
        $rate += $adjust;

        $html .= '<h3>Distribution du CA</h3>';

        $html .= '<br/><span class="bold">CA total: </span>' . BimpTools::displayMoneyValue($total_ca) . '<br/>';
        $html .= '<span class="bold">Pourcentage du CA pour la distribution dans v2: </span>' . BimpTools::displayFloatValue($rate) . '<br/>';
        $html .= '<span class="small">(Avec ajustement aléatoire entre +5% et -5%)</span><br/>';

        $html .= '<h4>Valeurs initiales</h4>';
        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>ShipTo</th>';
        $html .= '<th>CA</th>';
        $html .= '<th>%</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        foreach ($shipTosData as $shipTo => $shipToData) {
            $html .= '<tr>';
            $html .= '<td>' . $shipTo . '</td>';
            $html .= '<td>' . BimpTools::displayMoneyValue($shipToData['total_ca']) . '</td>';
            $html .= '<td>' . BimpTools::displayFloatValue((float) $shipToData['ca_tx'] * 100, 2) . '%</td>';
            $html .= '</tr>';
        }
        $html .= '<tr>';
        $html .= '<td>Total V1</td>';
        $html .= '<td>' . BimpTools::displayMoneyValue($total_ca_v1) . '</td>';
        $html .= '<td>' . BimpTools::displayFloatValue(($total_ca_v1 / $total_ca) * 100, 2) . '%</td>';
        $html .= '</tr>';
        $html .= '<td>Total V2</td>';
        $html .= '<td>' . BimpTools::displayMoneyValue($total_ca_v2) . '</td>';
        $html .= '<td>' . BimpTools::displayFloatValue(($total_ca_v2 / $total_ca) * 100, 2) . '%</td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '<br/>';

        $nV1 = count($v1_shipTos) - 1;
        $nV2 = count($v2_shipTos) - 1;

        $floor = (float) (BimpCore::getConf('csv_apple_sueil_v1', 2) / 100);
        $ceil = (float) (BimpCore::getConf('csv_apple_plafond_v2', 10) / 100);

        $n = 0;
        while ($v2 < $rate) {
            $n++;
            if ($n > 50000) {
                break; // protection boucle infinie
            }

            // ShipTos aléatoires dans v1 et v2;
            $v1_idx = rand(0, $nV1);
            $v2_idx = rand(0, $nV2);
            if (isset($v1_shipTos[$v1_idx]) && isset($v2_shipTos[$v2_idx])) {
                $v1_shipTo = $v1_shipTos[$v1_idx]['shipTo'];
                $v2_shipTo = $v2_shipTos[$v2_idx]['shipTo'];

                if (!empty($v1_shipTos[$v1_idx]['products'])) {
                    // Produit de V1 aléatoire: 
                    $prod_idx = rand(0, $v1_shipTos[$v1_idx]['nProds']);
                    $id_product = (isset($v1_shipTos[$v1_idx]['products'][$prod_idx]) ? (int) $v1_shipTos[$v1_idx]['products'][$prod_idx] : 0);
                    if ($id_product &&
                            isset($shipTosData[$v1_shipTo]['products'][$id_product]) &&
                            isset($shipTosData[$v2_shipTo]['products'][$id_product])) {
                        $v1_prod = $shipTosData[$v1_shipTo]['products'][$id_product];
                        $v2_prod = $shipTosData[$v2_shipTo]['products'][$id_product];

                        if ((int) $v1_prod['ventes'] > 0) {
                            // Qty aléatoire à transférer (max: 10). 
                            $max = ((int) $v1_prod['ventes'] > 10 ? 10 : (int) $v1_prod['ventes']);
                            $qty = rand(1, $max);
                            $v1_diff = (float) $v1_prod['pa_ht'] * $qty;
                            $v2_diff = (float) $v2_prod['pa_ht'] * $qty;

                            $shipTosData[$v1_shipTo]['products'][$id_product]['ventes'] -= $qty;
                            $shipTosData[$v2_shipTo]['products'][$id_product]['ventes'] += $qty;

                            $shipTosData[$v1_shipTo]['total_ca'] -= $v1_diff;
                            $shipTosData[$v2_shipTo]['total_ca'] += $v2_diff;

                            $total_ca_v1 -= $v1_diff;
                            $total_ca_v2 += $v2_diff;

                            $v2 = ($total_ca_v2 / $total_ca) * 100;

                            if ((float) ($shipTosData[$v1_shipTo]['total_ca'] / $total_ca) <= $floor) {
                                unset($v1_shipTos[$v1_idx]);
                                sort($v1_shipTos);
                                $nV1--;
                            }

                            if ((float) ($shipTosData[$v2_shipTo]['total_ca'] / $total_ca) >= $ceil) {
                                unset($v2_shipTos[$v2_idx]);
                                sort($v2_shipTos);
                                $nV2--;
                            }
                        }
                    }
                }
            }
        }

        $html .= '<h4>Valeurs après distribution</h4>';
        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>ShipTo</th>';
        $html .= '<th>CA</th>';
        $html .= '<th>%</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        foreach ($shipTosData as $shipTo => $shipToData) {
            $shipToData['ca_tx'] = $shipToData['total_ca'] / $total_ca;
            $html .= '<tr>';
            $html .= '<td>' . $shipTo . '</td>';
            $html .= '<td>' . BimpTools::displayMoneyValue($shipToData['total_ca']) . '</td>';
            $html .= '<td>' . BimpTools::displayFloatValue((float) $shipToData['ca_tx'] * 100, 2) . '%</td>';
            $html .= '</tr>';
        }
        $html .= '<tr>';
        $html .= '<td>Total V1</td>';
        $html .= '<td>' . BimpTools::displayMoneyValue($total_ca_v1) . '</td>';
        $html .= '<td>' . BimpTools::displayFloatValue(($total_ca_v1 / $total_ca) * 100, 2) . '%</td>';
        $html .= '</tr>';
        $html .= '<td>Total V2</td>';
        $html .= '<td>' . BimpTools::displayMoneyValue($total_ca_v2) . '</td>';
        $html .= '<td>' . BimpTools::displayFloatValue(($total_ca_v2 / $total_ca) * 100, 2) . '%</td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '<br/>';

        return $shipTosData;
    }

    // Actions :

    public function actionGenerateAppleCSV($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        global $user;
//        if ($user->id != 1) {
//            $errors[] = 'Debug en cours';
//        } else {
            $date_from = isset($data['date_from']) ? $data['date_from'] : date('Y-m-d');
            $date_to = isset($data['date_to']) ? $data['date_to'] : '';
            $distribute_ca = isset($data['distribute_ca']) ? $data['distribute_ca'] : 0;

            if (!$date_to) {
                $dt = new DateTime($date_from);
                $dt->sub(new DateInterval('P7D'));
                $date_to = $dt->format('Y-m-d');
            }

            $result = $this->generateAppleCSV($date_from, $date_to, $distribute_ca, $errors);

            if (isset($result['filename']) && $result['filename']) {
                $file_name = $result['filename'];
                if (file_exists(DOL_DATA_ROOT . '/bimpcore/apple_csv/' . date('Y') . '/' . $file_name)) {
                    $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('apple_csv/' . date('Y') . '/' . $file_name);
                    $success_callback = 'window.open(\'' . $url . '\');';
                }
            }

            $html = (isset($result['html']) ? $result['html'] : '');

            if ($html) {
                $success_callback .= 'setTimeout(function() {bimpModal.newContent(\'Distribution\', \'' . $html . '\', false, \'\', $());}, 1000);';
            }
//        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }
}
