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
        }

        return '';
    }

    public function updateExtraField($field_name, $value, $id_object)
    {
        return array();
    }

    // Rendus HTML: 

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

    // Traitements : 

    public function generateAppleCSV($dateFrom, $dateTo, &$errors = array())
    {
        set_time_limit(0);

//        $id_category = (int) BimpCore::getConf('id_categorie_apple');
//
//        if (!$id_category) {
//            $errors[] = 'ID de la catgorie "APPLE" non configurée';
//            return '';
//        }
//
//        $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');
//        $products_list = $product->getList(array(
//            'cp.fk_categorie' => (int) $id_category
//                ), null, null, 'id', 'asc', 'array', array('rowid', 'ref'), array(
//            'cp' => array(
//                'alias' => 'cp',
//                'table' => 'categorie_product',
//                'on'    => 'a.rowid = cp.fk_product'
//            )
//        ));
        
        
        
        $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');
        $products_list = $product->getList(array(
            'ref' => array(
                'part_type' => 'beginning',
                'part'      => 'APP-'
            )
                ), null, null, 'id', 'asc', 'array', array('rowid', 'ref'));


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
(2)";Erreurs
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

";Erreurs de validation de base' . "\n";

        $entrepots = BimpCache::getEntrepotsShipTos();

        foreach ($products_list as $p) {
            $entrepots_data = $product->getAppleCsvData($dateFrom, $dateTo, $entrepots, $p['rowid']);

            foreach ($entrepots_data as $ship_to => $data) {
                if ($data['stock'] < 0)
                    $data['stock'] = 0;
                if ($data['stock_showroom'] < 0)
                    $data['stock_showroom'] = 0;
                if ((int) $data['ventes'] || (int) $data['stock'] || (int) $data['stock_showroom']) {
                    $file_str .= implode(';', array(
                                $ship_to,
                                preg_replace('/^APP\-(.*)$/', '$1', $p['ref']),
                                $data['ventes'],
                                0,
                                $data['stock'],
                                $data['stock_showroom'],
                                0,
                                0,
                                0,
                                0,
                                0,
                                '',
                                ''
                            )) . "\n";
//                    break 2;
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
            return '';
        }

        return $fileName;
    }

    // Actions : 

    public function actionGenerateAppleCSV($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        $date_from = isset($data['date_from']) ? $data['date_from'] : date('Y-m-d');
        $date_to = isset($data['date_to']) ? $data['date_to'] : '';

        if (!$date_to) {
            $dt = new DateTime($date_from);
            $dt->sub(new DateInterval('P7D'));
            $date_to = $dt->format('Y-m-d');
        }

        $file_name = $this->generateAppleCSV($date_from, $date_to, $errors);

        if ($file_name && file_exists(DOL_DATA_ROOT . '/bimpcore/apple_csv/' . date('Y') . '/' . $file_name)) {
            $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('apple_csv/' . date('Y') . '/' . $file_name);

            $success_callback = 'window.open(\'' . $url . '\')';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }
    
    public function displayShortRef(){
        if ($this->isLoaded()){
            $prod = BimpCache::getBimpObjectInstance('bimpcore', "Bimp_Product", $this->getData('fk_product'));
            $ref = $prod->getData('ref');
            if(substr($ref, 3, 1) === "-")
                    $ref = substr($ref, 4);
            return $ref;
        }
    }
    public function displayCountry(){
        if ($this->isLoaded()){
            $cli = BimpCache::getBimpObjectInstance('bimpcore', "Bimp_Client", $this->getData('id_client'));
            return $cli->displayCountry();
        }
    }
    
    
}
