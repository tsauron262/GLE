<?php

class Bimp_Vente extends BimpObject
{

    public static $facture_fields = array('date' => 'datef', 'id_client' => 'fk_soc', 'id_user' => 'fk_user_author');
    public static $facture_extrafields = array('id_entrepot' => 'entrepot', 'secteur' => 'type');

    // Getters booléens:

    public function isCreatable($force_create = false, &$errors = Array())
    {
        return 0;
    }

    public function isEditable($force_edit = false, &$errors = Array())
    {
        return 0;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        return 0;
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

    public function getLink($params = array())
    {
        if ($this->isLoaded()) {
            $fac = $this->getChildObject('facture');

            if (BimpObject::objectLoaded($fac)) {
                $params['label_extra'] = 'Ligne n°' . $this->getData('rang');
                return $fac->getLink($params);
            }

            $html = 'Ligne de facture #' . $this->id;

            $html .= '<span class="danger">';
            if ((int) $this->getData('fk_facture')) {
                $html .= ' (La facture #' . $this->getData('fk_facture') . ' n\'existe plus)';
            } else {
                $html .= ' (ID de la facture absent)';
            }
            $html .= '</span>';

            return $html;
        }

        return BimpRender::renderAlerts('ID ' . $this->getLabel('of_the') . ' absent');
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

    // Traitements:

    public function generateAppleCSV($types, $dateFrom, $dateTo, $distribute_ca = false, &$errors = array(), $include_part_soc = 1)
    {
        set_time_limit(600000);
        ignore_user_abort(0);

        $id_category = (int) BimpCore::getConf('id_categorie_apple');

        $id_category = 1;

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
                if ($data['stock'] < 0)
                    $data['stock'] = 0;
                if ($data['stock_showroom'] < 0)
                    $data['stock_showroom'] = 0;

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
                    'stock_showroom' => $data['stock_showroom'],
                    'factures'       => (isset($data['factures']) ? $data['factures'] : array())
                );

                $product_ca = (float) $data['ventes'] * (float) $pa_ht;
                $shiptos_data[$ship_to]['total_ca'] += $product_ca;
                $total_ca += $product_ca;
            }
        }

        // Distribution du CA: 
        $html = '';
        if ($distribute_ca) {
            $shiptos = explode(',', BimpCore::getConf('csv_apple_distribute_ca_shiptos'));
            $shiptos_data = $this->distributeCaForShiptos($total_ca, $shiptos_data, $shiptos, 80, $html);
        }

        // Génération du CSV Inventaire par shipTo: 
        if (isset($types['inventory']) && (int) $types['inventory']) {
            $file_str = '"Inventory Location ID

Mandatory Field
(23)";"Apple Marketing Part number

Mandatory Field
(30)";"UPC code

Preferred Field
(30)";"JAN Code

Preferred Field
(30)";"Inventory Free Quantity

Mandatory Field
(10)";"Inventory Demo Quantity

Preferred Field
(10)";"Inventory Internal In-transit Quantity

Preferred Field
(10)";"Inventory Non-Sellable Quantity

Preferred Field
(10)";"Inventory Reserved Quantity

Preferred Field
(10)";"Inventory Back Order Quantity

Preferred Field
(10)";"Inventory Received Quantity

Preferred Field
(10)";"Errors"' . "\n";

            foreach ($products_list as $p) {
                foreach ($shiptos_data as $shipTo => $shipToData) {
                    if (isset($shipToData['products'][(int) $p['rowid']])) {
                        $prod = $shipToData['products'][(int) $p['rowid']];
                        $prod_ref = preg_replace('/^APP\-(.*)$/', '$1', $prod['ref']);
                        if (preg_match('/^(Z[^\/]+)$/', $prod_ref, $matches)) {
                            $prod_ref = substr($matches[1], 0, 4);
                        }
                        if ((int) $prod['stock'] || (int) $prod['stock_showroom']) {
                            $file_str .= implode(';', array(
                                        $shipTo, // A
                                        substr($prod_ref, 0, 30), // B
                                        '',
                                        '',
                                        $prod['stock'], // E
                                        $prod['stock_showroom'], // F
                                        0,
                                        0,
                                        0,
                                        0,
                                        0,
                                        '',
                                    )) . "\n";
                        }
                    }
                }
            }

            $dir = DOL_DATA_ROOT . '/bimpcore/apple_csv/' . date('Y');
            $fileName = 'inventory_' . $dateFrom . '_' . $dateTo . '.csv';

            if (!file_exists(DOL_DATA_ROOT . '/bimpcore/apple_csv')) {
                mkdir(DOL_DATA_ROOT . '/bimpcore/apple_csv');
            }

            if (!file_exists($dir)) {
                mkdir($dir);
            }

            if (!file_put_contents($dir . '/' . $fileName, $file_str)) {
                $errors[] = 'Echec de la création du fichier CSV';
            } else {
                $files[] = $fileName;
            }
        }

        // Génération du CSV Ventes par shipto: 

        if (isset($types['sales']) && (int) $types['sales']) {
//            $socsTypes = array();
//            $types_matches = array(
//                'TE_UNKNOWN'   => '21',
//                'TE_STARTUP'   => '21',
//                'TE_GROUP'     => '2L',
//                'TE_MEDIUM'    => '21',
//                'TE_SMALL'     => '21',
//                'TE_ADMIN'     => 'HS',
//                'TE_WHOLE'     => 'BB',
//                'TE_RETAIL'    => 'BB',
//                'TE_PRIVATE'   => 'EN',
//                'TE_OTHER'     => '21',
//                'TE_ASSO'      => '21',
//                'TE_PART'      => 'BB',
//                'TE_RETAIL_CL' => 'BB',
//                'TE_RETAIL_EX' => 'BB'
//            );
//            $typesSoc = array();
//
//            $te_rows = $this->db->getRows('c_typent', '1', null, 'array');
//
//            foreach ($te_rows as $r) {
//                $typesSoc[(int) $r['id']] = $r['code'];
//            }

            $id_soc_type_particulier = (int) $this->db->getValue('c_typent', 'id', 'code = \'TE_PRIVATE\'');

            $file_str = '"Sales Location ID

Mandatory Field
(23)";"Apple Marketing Part number

Mandatory Field
(30)";"UPC Code

Preferred Field
(30)";"JAN Code

Preferred Field
(30)";"Sell Through Sold Quantity

Mandatory Field
(10)";"Sell Through Returned Quantity

Mandatory Field
(10)";"Serial Numbers

Preferred Field
(31)";"Unit Selling Price

Preferred Field
(9)";"Invoice No

Preferred Field
(30)";"Invoice Line Item No

Preferred Field
(10)";"Invoice Date

Preferred Field
(8)";"End Customer ID

Preferred Field
(17)";"End Customer Name

Preferred Field
(35)";"End Customer Address

Preferred Field
(35)";"End Customer City

Preferred Field
(19)";"End Customer Province/State

Preferred Field
(4)";"End Customer Postal/Zip code

Preferred Field
(9)";"End Customer Country

Preferred Field
(2)";"End Customer Type 

Preferred Field
(2)";"Error"' . "\n";

            $countries = BimpCache::getCountriesCodesArray();

            foreach ($products_list as $p) {
                foreach ($shiptos_data as $shipTo => $shipToData) {
                    if (isset($shipToData['products'][(int) $p['rowid']])) {
                        $prod = $shipToData['products'][(int) $p['rowid']];
                        $prod_ref = preg_replace('/^APP\-(.*)$/', '$1', $prod['ref']);
                        if (preg_match('/^(Z[^\/]+)$/', $prod_ref, $matches)) {
                            $prod_ref = substr($matches[1], 0, 4);
                        }
                        if (isset($prod['factures']) && !empty($prod['factures'])) {
                            foreach ($prod['factures'] as $id_fac => $fac_lines) {
                                $secteur = (string) $this->db->getValue('facture_extrafields', 'type', 'fk_object = ' . (int) $id_fac);
                                $fac_data = $this->db->getRow('facture', 'rowid = ' . (int) $id_fac, array('fk_soc', 'facnumber', 'datef'), 'array');

                                if (is_null($fac_data)) {
                                    continue;
                                }

                                $soc_data = $this->db->getRow('societe', 'rowid = ' . (int) $fac_data['fk_soc'], array('fk_typent', 'fk_pays'), 'array');

                                if ($secteur == 'E') {
                                    $customer_code = '1R';
//                                } elseif ($secteur == 'BP') {
//                                    $customer_code = 'BB';
                                } else {
                                    $id_soc_type = isset($soc_data['fk_typent']) ? (int) $soc_data['fk_typent'] : 0;
                                    if (!$id_soc_type) {
                                        $customer_code = 'EN';
                                    } else {
                                        $customer_code = ($id_soc_type === $id_soc_type_particulier ? 'EN' : '21');
                                    }
                                }

                                if (isset($soc_data['fk_pays']) && array_key_exists((int) $soc_data['fk_pays'], $countries)) {
                                    $country_code = $countries[(int) $soc_data['fk_pays']];
                                } else {
                                    $country_code = 'FR';
                                }

                                if (!$include_part_soc && $customer_code == 'EN') {
                                    continue;
                                }

                                $dt_fac = new DateTime($fac_data['datef']);

                                foreach ($fac_lines as $id_line => $line_data) {
                                    $file_str .= implode(';', array(
                                                $shipTo, // A
                                                substr($prod_ref, 0, 30), // B
                                                '',
                                                '',
                                                ($line_data['qty'] >= 0 ? $line_data['qty'] : 0), // E
                                                ($line_data['qty'] < 0 ? abs($line_data['qty']) : 0), // F
                                                '',
                                                '',
                                                $id_fac, // I
                                                $line_data['position'], // J
                                                $dt_fac->format('Ymd'),
                                                '',
                                                ($customer_code != 'EN' ? 'XXX' : ''), // M
                                                ($customer_code != 'EN' ? 'XXX' : ''), // N
                                                ($customer_code != 'EN' ? 'XXX' : ''), // O
                                                '',
                                                '',
                                                $country_code, // R
                                                $customer_code // S
                                            )) . "\n";
                                }
                            }
                        }
                    }
                }
            }

            $dir = DOL_DATA_ROOT . '/bimpcore/apple_csv/' . date('Y');
            $fileName = 'sales_' . $dateFrom . '_' . $dateTo . '.csv';

            if (!file_exists(DOL_DATA_ROOT . '/bimpcore/apple_csv')) {
                mkdir(DOL_DATA_ROOT . '/bimpcore/apple_csv');
            }

            if (!file_exists($dir)) {
                mkdir($dir);
            }

            if (!file_put_contents($dir . '/' . $fileName, $file_str)) {
                $errors[] = 'Echec de la création du fichier CSV';
            } else {
                $files[] = $fileName;
            }
        }

        return array(
            'files' => $files,
            'html'  => $html
        );
    }

    public function distributeCaForShiptos($total_ca, $shipTosData, $shiptos, $percent, &$html = '')
    {
        // Actuellement la distribution se fait pour chaque vente totale d'un produit dans une facture d'un shipTo à un autre. 
        // Attention: on ne transfert pas l'intégralité d'une facture pour l'instant: donc les vente d'une même facture peuvent se retrouver réparties sur plusieurs shipTos. 

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
                'factures' => array(),
                'fac_ids'  => array()
            );

            foreach ($shipToData['products'] as $id_p => $p_data) {
                $data['products'][] = $id_p;

                if (isset($p_data['factures']) && !empty($p_data['factures'])) {
                    foreach ($p_data['factures'] as $id_fac => $fac_lines) {
                        if (!isset($data['factures'][$id_fac])) {
                            $data['fac_ids'][] = $id_fac;
                            $data['factures'][$id_fac] = array(
                                'products'  => array(),
                                'total_fac' => 0
                            );
                        }

                        if (!isset($data['factures'][$id_fac]['products'][$id_p])) {
                            $data['factures'][$id_fac]['products'][$id_p] = 0;
                        }

                        foreach ($fac_lines as $id_line => $line_data) {
                            $data['factures'][$id_fac]['total_fac'] += ($line_data['qty'] * (float) $p_data['pa_ht']);
                            $data['factures'][$id_fac]['products'][$id_p] += $line_data['qty'];
                        }
                    }
                }
            }

            $data['nfacs'] = (count($data['fac_ids']) - 1);

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

        $rate = $percent;
        $v1 = ($total_ca_v1 / $total_ca) * 100;
        $v2 = ($total_ca_v2 / $total_ca) * 100;
        $tolerance = 1; // Tolérance de dépassemement du taux attendu: 1%
        $max_transf_amount = 100; // Montant max transférable d'un shipTo à l'autre. 
        // Ajustement aléatoire du % v2: 
        $adjust = (rand(0, 500) / 100);
        if ((int) rand(0, 1)) {
            $adjust *= -1;
        }
        $rate += $adjust;

        $html .= '<h3>Distribution du CA</h3>';
        $html .= '<h4>(Nouvelle méthode)</h4>';

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
        $nFails = 0;
        $nDone = 0;
        $total_transf = 0;
        $maxIterations = 100000;
        $maxIterationsMax = 500000;
        $maxTolerance = 5;

        while ($v2 < $rate) {
            $n++;
            if ($n > $maxIterations) {
                break; // protection boucle infinie
            }

            // Si on ne parvient pas à trouver des montants transférables, on ajuste les paramètres: 
            if ($nFails >= 500) {
                $nFails = 0;
                $tolerance += 0.1; // Ajout de 0,1%.
                $max_transf_amount += 100; // Augmentation du montant max transférable de 100 euros. 
                $maxIterations += 500;

                if ($tolerance > $maxTolerance) {
                    $tolerance = $maxTolerance;
                }

                if ($maxIterations > $maxIterationsMax) {
                    $maxIterations = $maxIterationsMax;
                }
            }

            // ShipTos aléatoires dans v1 et v2;
            $v1_idx = rand(0, $nV1);
            $v2_idx = rand(0, $nV2);
            if (isset($v1_shipTos[$v1_idx]) && isset($v2_shipTos[$v2_idx])) {
                $v1_shipTo = $v1_shipTos[$v1_idx]['shipTo'];
                $v2_shipTo = $v2_shipTos[$v2_idx]['shipTo'];

                if (!empty($v1_shipTos[$v1_idx]['fac_ids'])) {
                    // Facture de V1 aléatoire: 
                    $id_fac_idx = rand(0, $v1_shipTos[$v1_idx]['nFacs']);
                    $id_fac = (isset($v1_shipTos[$v1_idx]['fac_ids'][$id_fac_idx]) ? (int) $v1_shipTos[$v1_idx]['fac_ids'][$id_fac_idx] : 0);
                    if ($id_fac && isset($v1_shipTos[$v1_idx]['factures'][$id_fac])) {
                        $total_fac = $v1_shipTos[$v1_idx]['factures'][$id_fac]['total_fac'];
                        if ($total_fac > 0 && $total_fac <= $max_transf_amount) {
                            $new_total_ca_v1 = $total_ca_v1 - $total_fac;
                            $new_total_ca_v2 = $total_ca_v2 + $total_fac;

                            $new_v1 = ($new_total_ca_v1 / $total_ca) * 100;
                            $new_v2 = ($new_total_ca_v2 / $total_ca) * 100;

                            $min_v1 = ((100 - $rate) - $tolerance);
                            $max_v2 = ($rate + $tolerance);

                            if ($new_v1 >= $min_v1 && $new_v2 <= $max_v2) {
                                // C'est ok, on effectue le transfert: 

                                $nDone++;
                                $total_transf += $total_fac;

                                // On transfert tous les produits de la facture:
                                foreach ($v1_shipTos[$v1_idx]['factures'][$id_fac]['products'] as $id_prod => $prod_qty) {
                                    if (!isset($shipTosData[$v1_shipTo]['products'][$id_prod])) {
                                        continue;
                                    }

                                    if (!isset($shipTosData[$v2_shipTo]['products'][$id_prod])) {
                                        $shipTosData[$v2_shipTo]['products'][$id_prod] = $shipTosData[$v1_shipTo]['products'][$id_prod];
                                        $shipTosData[$v2_shipTo]['products'][$id_prod]['ventes'] = 0;
                                        $shipTosData[$v2_shipTo]['products'][$id_prod]['stock'] = 0;
                                        $shipTosData[$v2_shipTo]['products'][$id_prod]['stock_showroom'] = 0;
                                        $shipTosData[$v2_shipTo]['products'][$id_prod]['factures'] = array();
                                    }

                                    $shipTosData[$v1_shipTo]['products'][$id_prod]['ventes'] -= $prod_qty;
                                    $shipTosData[$v2_shipTo]['products'][$id_prod]['ventes'] += $prod_qty;

                                    $shipTosData[$v2_shipTo]['products'][$id_prod]['factures'][$id_fac] = $shipTosData[$v1_shipTo]['products'][$id_prod]['factures'][$id_fac];
                                    unset($shipTosData[$v1_shipTo]['products'][$id_prod]['factures'][$id_fac]);
                                }

                                unset($v1_shipTos[$v1_idx]['fac_ids'][$id_fac_idx]);
                                sort($v1_shipTos[$v1_idx]['fac_ids']);
                                $v1_shipTos[$v1_idx]['nFacs'] --;

                                $shipTosData[$v1_shipTo]['total_ca'] -= $total_fac;
                                $shipTosData[$v2_shipTo]['total_ca'] += $total_fac;

                                $total_ca_v1 = $new_total_ca_v1;
                                $total_ca_v2 = $new_total_ca_v2;

                                $v1 = ($total_ca_v1 / $total_ca) * 100;
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

                                continue;
                            }
                        }
                    }
                }
            }

            $nFails++;
        }

        $html .= '<h4>Infos distribution: </h4>';
        $html .= '<strong>Nombre itérations: </strong>' . $n . '<br/>';
        $html .= '<strong>Tolérance finale: </strong>' . BimpTools::displayFloatValue($tolerance, 2) . '%<br/>';
        $html .= '<span class="small">Représente l\'écart toléré avec les taux de répartition attendus.<br/>Il est augmenté progressivement si on ne parvient pas à obtenir des montants répartissables.</span><br/>';
        $html .= '<strong>Montant tranférable max final: </strong>' . BimpTools::displayMoneyValue($max_transf_amount) . '<br/>';
        $html .= '<strong>Montant total transféré: </strong>' . BimpTools::displayMoneyValue($total_transf) . '<br/>';
        $html .= '<strong>Nombre total de tranferts: </strong>' . $nDone . '<br/>';
        $html .= '<span class="small">1 transfert = Tous les produits Apple d\'une facture d\'un shipTo vers un autre</span><br/>';
        $html .= '<br/>';

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

    public function distributeCaForShiptos_oldVersion($total_ca, $shipTosData, $shiptos, $percent, &$html = '')
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

        $rate = $percent;
        $v2 = ($total_ca_v2 / $total_ca) * 100;

        // Ajustement aléatoire du % v2: 
        $adjust = (rand(0, 500) / 100);
        if ((int) rand(0, 1)) {
            $adjust *= -1;
        }
        $rate += $adjust;

        $html .= '<h3>Distribution du CA</h3>';
        $html .= '<h4>(Ancienne méthode)</h4>';

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

    // Actions:

    public function actionGenerateAppleCSV($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        $date_from = isset($data['date_from']) ? $data['date_from'] : date('Y-m-d');
        $date_to = isset($data['date_to']) ? $data['date_to'] : '';
        $distribute_ca = isset($data['distribute_ca']) ? $data['distribute_ca'] : 0;
        $include_part_soc = isset($data['include_part_soc']) ? $data['include_part_soc'] : 1;

        $csv_types = array(
            'inventory' => (isset($data['include_inventory']) ? (int) $data['include_inventory'] : 0),
            'sales'     => (isset($data['include_sales']) ? (int) $data['include_sales'] : 0),
        );

        if (!$csv_types['inventory'] && !$csv_types['sales']) {
            $errors[] = 'Veuillez sélectionner au moins un type de rapport à générer';
        }

        if (!$date_to) {
            $dt = new DateTime($date_from);
            $dt->sub(new DateInterval('P7D'));
            $date_to = $dt->format('Y-m-d');
        }

        if (!count($errors)) {
            $result = $this->generateAppleCSV($csv_types, $date_from, $date_to, $distribute_ca, $errors, $include_part_soc);

            if (isset($result['files']) && !empty($result['files'])) {
                foreach ($result['files'] as $file_name) {
                    if (file_exists(DOL_DATA_ROOT . '/bimpcore/apple_csv/' . date('Y') . '/' . $file_name)) {
                        $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('apple_csv/' . date('Y') . '/' . $file_name);
                        $success_callback .= 'window.open(\'' . $url . '\');';
                    }
                }
            }

            $html = (isset($result['html']) ? $result['html'] : '');

            if ($html) {
                $success_callback .= 'setTimeout(function() {bimpModal.newContent(\'Distribution\', \'' . str_replace("'", "\'", $html) . '\', false, \'\', $());}, 1000);';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    // Overrides:

    public function fetchExtraFields()
    {
        $fields = array(
            'date'               => '',
            'id_client'          => 0,
            'id_entrepot'        => 0,
            'id_user'            => 0,
            'id_commercial'      => 0,
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
                $fields['id_commercial'] = (int) $facture->getIdCommercial();
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
            } elseif ($field === 'id_commercial') {
//                $id_facture = (int) $instance->getData('fk_facture');
                // todo...
            }
        }

        return null;
    }

    public function getExtraFieldFilterKey($field, &$joins, $main_alias = '', &$filters = array())
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
        } elseif ($field === 'id_commercial') {
            $join_alias = ($main_alias ? $main_alias . '_' : '') . 'facture_contact';
            $joins[$join_alias] = array(
                'table' => 'element_contact',
                'alias' => $join_alias,
                'on'    => $join_alias . '.element_id = ' . ($main_alias ? $main_alias : 'a') . '.fk_facture'
            );
            $alias = ($main_alias ? $main_alias . '_' : '') . 'commercial_type_contact';
            $joins[$alias] = array(
                'alias' => $alias,
                'table' => 'c_type_contact',
                'on'    => $alias . '.rowid = ' . $join_alias . '.fk_c_type_contact'
            );

            $filters[$alias . '.source'] = 'internal';
            $filters[$alias . '.element'] = 'facture';
            $filters[$alias . '.code'] = 'SALESREPFOLL';

            return $join_alias . '.fk_socpeople';
        }

        return '';
    }

    public function updateExtraField($field_name, $value, $id_object)
    {
        return array();
    }
}
