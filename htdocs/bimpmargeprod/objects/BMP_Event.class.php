<?php

require_once DOL_DOCUMENT_ROOT . "/bimpmargeprod/objects/Abstract_margeprod.class.php";

class BMP_Event extends Abstract_margeprod
{

    // Recettes: 
    public static $id_billets_2_1_type_montant = 21;
    public static $id_billets_5_5_type_montant = 52;
    public static $id_bar20_type_montant = 22;
    public static $id_bar55_type_montant = 23;
    public static $id_dl_prod_montant = 57;
    // Frais: 
    public static $id_achats_bar_montant = 24;
    public static $id_frais_billets_materiels = 41;
    // Taxes SACEM / CNV:
    public static $id_sacem_bar_montant = 3;
    public static $id_sacem_billets_montant = 26;
    public static $id_sacem_autre_montant = 30;
    public static $id_sacem_groupe = 62;
    public static $id_cnv_montant = 5;
    public static $id_sacem_secu_montant = 29;
    // Calculs:
    public static $id_calc_sacem_billets = 2;
    public static $id_calc_cnv_billets = 1;
    public static $id_calc_frais_bar = 3;
    public static $id_calc_sacem_bar = 7;
    public static $id_calc_sacem_secu = 9;
    // Catégories:
    public static $id_taxes_category = 1;
    public static $id_bar_category = 15;
    public static $id_billets_category = 14;
    public static $id_coprods_category = 12;
    // Montants divers: 
    public static $montant_frais_billet_materiel = 0.2;
    public static $sacem_billets_min = 43.57;
    public static $types = array(
            1 => 'Production',
            2 => 'Co-production',
            3 => 'MAD',
            4 => 'Location',
            5 => 'Privat'
    );
    public static $places = array(
        1 => 'Club',
        2 => 'Grande salle coupée',
        3 => 'Grande salle',
        4 => 'Double salle',
        5 => 'Hors les murs',
        6 => 'studios'
    );
    public static $status = array(
        1 => array('label' => 'Edition prévisionnel', 'classes' => array('warning')),
        2 => array('label' => 'Edition montants réels', 'classes' => array('info')),
        3 => array('label' => 'Validé', 'classes' => array('success')),
        4 => array('label' => 'Archivé', 'classes' => array('important'))
    );
    public static $tarifs = array(
        'TARIF NORMAL', 'TARIF REDUIT', 'FILGOOD', 'TARIF SPECIAL', 'TARIF SPECIAL LE FIL', 'TARIF CE', 'INVITATIONS', 'GUICHET NORMAL', 'GUICHET REDUIT', 'GUICHET FILGOOD'
    );
    public static $vendeurs = array();
    public static $analytics = array(
        ''     => '',
        'PRO'  => 'PRO',
        'LOC'  => 'LOC',
        'STU'  => 'STU',
        'RES'  => 'RES',
        'JEU'  => 'JEU',
        'AUTR' => 'AUTR',
        'INT'  => 'INT',
        'NUM'  => 'NUM',
        'MER'  => 'MER'
    );
    protected $montants = array();
    protected $calc_montants = array();

    // Getters booléens:

    public function isInEditableStatus()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('status') >= 3) {
                return 0;
            }
        }

        return 1;
    }

    public function isInPrevisionnelMode()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('status') === 1) {
                return 1;
            }
        }

        return 0;
    }

    public function isInReelMode()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('status') > 1) {
                return 1;
            }
        }

        return 0;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if ($this->isLoaded()) {
            if (in_array($field, array('type', 'tva_billets', 'ca_moyen_bar', 'frais_billet', 'default_dl_dist', 'default_dl_prod'))) {
                return (int) $this->isInEditableStatus();
            }
        }

        return 1;
    }

    public function showCoprods()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('type') === 1) {
                return 0;
            }
            return 1;
        }

        return 0;
    }

    // Getters Array:

    public function getGroupsArray($include_empty = 0)
    {
        return $this->getChildrenListArray('groups', $include_empty);
    }

    public static function getPredefTarifsArray()
    {
        if (!isset(self::$cache['bmp_predef_tarifs_array'])) {
            foreach (self::$tarifs as $label) {
                self::$cache['bmp_predef_tarifs_array'][$label] = $label;
            }
        }

        return self::$cache['bmp_predef_tarifs_array'];
    }

    public function getTarifsArray()
    {
        if ($this->isLoaded()) {
            $cache_key = 'bmp_event_' . $this->id . '_tarifs_array';
            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();

                $list = $this->getChildrenObjects('tarifs');
                if (!is_null($list) && count($list)) {
                    foreach ($list as $item) {
                        self::$cache[$cache_key][$item->id] = $item->getData('name');
                    }
                }
            }
            return self::$cache[$cache_key];
        }

        return array();
    }

    public function getCoProds($include_empty = false)
    {
        if (!$this->isLoaded()) {
            return array();
        }

        $cache_key = 'bmp_event_' . $this->id . '_coprods_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            foreach ($this->getChildrenObjects('coprods') as $coprod) {
                $soc = $coprod->getChildObject('societe');
                self::$cache[$cache_key][(int) $coprod->id] = BimpObject::getInstanceNom($soc);
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    // Getters Montants / calcMontant:

    public function getMontant($id_type_montant, $id_coprod = 0)
    {
        if ($this->isLoaded()) {
            if (!isset($this->montants[(int) $id_type_montant])) {
                $this->montants[$id_type_montant] = array();
            }

            if (!isset($this->montants[(int) $id_type_montant][(int) $id_coprod])) {
                $eventMontant = BimpCache::findBimpObjectInstance($this->module, 'BMP_EventMontant', array(
                            'id_event'   => (int) $this->id,
                            'id_coprod'  => (int) $id_coprod,
                            'id_montant' => (int) $id_type_montant
                ));

                if (BimpObject::objectLoaded($eventMontant)) {
                    $this->montants[(int) $id_type_montant][(int) $id_coprod] = $eventMontant;
                } else {
                    return null;
                }
            }

            return $this->montants[(int) $id_type_montant][(int) $id_coprod];
        }

        return null;
    }

    public function getMontantAmount($id_type_montant, $id_coprod = 0, $montant_status = null)
    {
        $amount = 0;

        if (is_null($id_coprod)) {
            $coprods = $this->getCoProds(true);
        } else {
            $coprods = array($id_coprod => '');
        }

        foreach ($coprods as $id_cp => $cp_name) {
            $montant = $this->getMontant($id_type_montant, $id_cp);

            if (BimpObject::objectLoaded($montant)) {
                if (is_null($montant_status) || (int) $montant_status === (int) $montant->getData('status')) {
                    $amount += (float) $montant->getData('amount');
                }
            }
        }

        return $amount;
    }

    public function getMontantTvaTx($id_type_montant, $id_coprod = 0)
    {
        $montant = $this->getMontant($id_type_montant, $id_coprod);

        if (BimpObject::objectLoaded($montant)) {
            return (float) $montant->getTvaTx();
        }

        return 0;
    }

    public function getBilletsIdTypeMontant()
    {
        if (!$this->isLoaded()) {
            return 0;
        }
        if ($this->getData('tva_billets') === 1) {
            return self::$id_billets_2_1_type_montant;
        }

        return self::$id_billets_5_5_type_montant;
    }

    public function getBilletsTvaTx($id_coprod = 0)
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        $montant = $this->getMontant((int) $this->getBilletsIdTypeMontant(), $id_coprod);
        if (BimpObject::objectLoaded($montant)) {
            return (float) $montant->getTvaTx();
        }

        return 0;
    }

    public function getBilletsTaxEx($billets_ttc)
    {
        return BimpTools::calculatePriceTaxEx($billets_ttc, $this->getBilletsTvaTx());
    }

    public function getCalcMontant($id_calc_montant)
    {
        if ($this->isLoaded()) {
            if (!isset($this->calc_montants[(int) $id_calc_montant])) {
                $cm = BimpCache::findBimpObjectInstance($this->module, 'BMP_EventCalcMontant', array(
                            'id_event'        => (int) $this->id,
                            'id_calc_montant' => (int) $id_calc_montant
                ));

                if (BimpObject::objectLoaded($cm)) {
                    $this->calc_montants[(int) $id_calc_montant] = $cm;
                } else {
                    return null;
                }
            }

            return $this->calc_montants[(int) $id_calc_montant];
        }

        return null;
    }

    public function getCalcMontantRate($id_calc_montant)
    {
        if ($this->isLoaded()) {
            $cm = $this->getCalcMontant($id_calc_montant);
            if (BimpObject::objectLoaded($cm)) {
                return (float) $cm->getData('percent');
            }
        }

        $calc_instance = BimpObject::getInstance($this->module, 'BMP_CalcMontant');
        return (float) $calc_instance->getSavedData('percent', (int) $id_calc_montant);
    }

    public function getCalcMontantsTargets($active_only)
    {
        if ($active_only) {
            if (!$this->isLoaded()) {
                return array();
            }
            $cache_key = 'event_' . $this->id . '_calc_montants_targets_active_only';
            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();

                $calc_montant = BimpObject::getInstance('bimpmargeprod', 'BMP_CalcMontant');

                $rows = $calc_montant->getList(array(
                    'ecm.id_event' => (int) $this->id,
                    'ecm.active'   => 1
                        ), null, null, 'id', 'asc', 'array', array('id', 'id_target'), array(
                    'ecm' => array(
                        'table' => 'bmp_event_calc_montant',
                        'on'    => 'ecm.id_calc_montant = a.id',
                        'alias' => 'ecm'
                    )
                ));

                if (!is_null($rows)) {
                    foreach ($rows as $r) {
                        self::$cache[$cache_key][(int) $r['id']] = $r['id_target'];
                    }
                }
            }
        } else {
            $cache_key = 'calc_montants_targets';

            $calc_montant = BimpObject::getInstance('bimpmargeprod', 'BMP_CalcMontant');

            $rows = $calc_montant->getList(array(), null, null, 'id', 'asc', 'array', array('id', 'id_target'));

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['id']] = $r['id_target'];
                }
            }
        }

        return self::$cache[$cache_key];
    }

    // Getters divers: 

    public function getDefaultTarifsValue()
    {
        return implode(',', self::$tarifs);
    }

    public function getTotalBilletsTitle()
    {
        $title = 'Total Billeterie';

        if ($this->isLoaded()) {
            if ((int) $this->getData('status') === 1) {
                $title .= ' (Prévisionnel)';
            }
        }

        return $title;
    }

    // Calculs: 

    public function getPrevisionnels()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        $montants = array(
            'total_billets_ttc'        => 0,
            'total_billets_ht'         => 0,
            'total_dl_dist_ttc'        => 0,
            'total_dl_dist_ht'         => 0,
            'total_dl_prod_ttc'        => 0,
            'total_dl_prod_ht'         => 0,
            'bar_ttc'                  => 0,
            'bat_ht'                   => 0,
            'total_frais_materiel_ttc' => 0,
            'total_frais_materiel_ht'  => 0,
            'total_nb_billets'         => 0
        );

        $event_type = $this->getData('type');
        $dl_dist = (float) $this->getData('default_dl_dist');
        $dl_prod = (float) $this->getData('default_dl_prod');

        $children = $this->getChildrenObjects('tarifs');

        foreach ($children as $child) {
            if (!is_null($child) && is_a($child, 'BimpObject')) {
                $amount = (float) $child->getData('amount');
                $qty = (int) $child->getData('previsionnel');
                $montants['total_billets_ttc'] += ($qty * $amount);
                $montants['total_billets_dl_dist_ttc'] += ($qty * $dl_dist);
                $montants['total_billets_dl_prod_ttc'] += ($qty * $dl_prod);
                $montants['total_nb_billets'] += $qty;
                if (in_array($event_type, array(1, 2)) || $amount > 0) {
                    $montants['total_frais_materiel_ht'] += (float) $this->getData('frais_billet') * $qty;
                }
            }
        }

        $montants['bar_ttc'] = (int) $montants['total_nb_billets'] * (float) $this->getData('ca_moyen_bar');

        $billets_tva_tx = 1 + ((float) $this->getBilletsTvaTx() / 100);

        $montants['total_billets_ht'] = $montants['total_billets_ttc'] / $billets_tva_tx;
        $montants['total_billets_dl_dist_ht'] = $montants['total_billets_dl_dist_ttc'] / $billets_tva_tx;
        $montants['total_billets_dl_prod_ht'] = $montants['total_billets_dl_prod_ttc'] / $billets_tva_tx;
        $montants['bar_ht'] = BimpTools::calculatePriceTaxEx($montants['bar_ttc'], (float) $this->getMontantTvaTx(self::$id_bar20_type_montant));
        $montants['total_frais_materiel_ttc'] = BimpTools::calculatePriceTaxIn($montants['total_frais_materiel_ht'], (float) $this->getMontantTvaTx(self::$id_frais_billets_materiels));

        return $montants;
    }

    public function getMontantsRecap($type_montant = 0)
    {
        $return = array(
            'total_ht'   => 0,
            'total_tva'  => 0,
            'total_ttc'  => 0,
            'categories' => array()
        );

        if ($this->isLoaded()) {
            switch ($type_montant) {
                case 1:
                    $object_name = 'frais';
                    break;
                case 2:
                    $object_name = 'recettes';
                    break;
                default:
                    $object_name = 'montants';
                    break;
            }
            $montants = $this->getChildrenObjects($object_name);
            $showCoprods = (int) $this->showCoprods();
            if ($showCoprods) {
                $coprods = $this->getCoProds(true);
                foreach ($coprods as $id_coprod => $cp_label) {
                    $return['coprods'][(int) $id_coprod] = array(
                        'total_part_ht'  => 0,
                        'total_part_tva' => 0,
                        'total_part_ttc' => 0,
                        'total_paid_ht'  => 0,
                        'total_paid_tva' => 0,
                        'total_paid_ttc' => 0,
                    );
                }
            }

            foreach ($montants as $montant) {
                $montant_ht = (float) $montant->getData('amount');
                $montant_ttc = (float) BimpTools::calculatePriceTaxIn($montant_ht, (float) $montant->getData('tva_tx'));
                $montant_tva = (float) ($montant_ttc - $montant_ht);

                $id_cat = (int) $montant->getData('id_category_montant');
                $id_type_montant = (int) $montant->getData('id_montant');
                $type = (int) $montant->getData('type');

                if ($type_montant === 0 && $type === 1) {
                    $montant_ht *= -1;
                    $montant_tva *= -1;
                    $montant_ttc *= -1;
                }

                if ($showCoprods) {
                    $coprods_montants = array();

                    $id_coprod_paid = (int) $montant->getData('id_coprod');
                    $paiements = $montant->getPaiements();
                    if (!is_array($paiements) || empty($paiements)) {
                        $paiements = array(
                            $id_coprod_paid = $montant_ttc
                        );
                    }

                    foreach ($coprods as $id_coprod => $cp_label) {
                        $part_tx = (float) $montant->getCoProdPart((int) $id_coprod) / 100;
                        if (isset($paiements[(int) $id_coprod])) {
                            $paid_ttc = (float) $paiements[(int) $id_coprod];
                        } else {
                            $paid_ttc = 0;
                        }
                        if ($type_montant === 0 && $type === 1) {
                            $paid_ttc *= -1;
                        }

                        $paid_ht = (float) BimpTools::calculatePriceTaxEx($paid_ttc, $montant->getTvaTx());
                        $coprods_montants[(int) $id_coprod] = array(
                            'total_part_ht'  => $montant_ht * $part_tx,
                            'total_part_tva' => $montant_tva * $part_tx,
                            'total_part_ttc' => $montant_ttc * $part_tx,
                            'total_paid_ht'  => $paid_ht,
//                            'total_paid_tva' => $montant_tva * $paiement_tx,
                            'total_paid_ttc' => $paid_ttc,
                        );
                    }
                }

                $return['total_ht'] += $montant_ht;
                $return['total_tva'] += $montant_tva;
                $return['total_ttc'] += $montant_ttc;

                if ($showCoprods) {
                    foreach ($coprods as $id_coprod => $cp_label) {
                        $return['coprods'][(int) $id_coprod]['total_part_ht'] += $coprods_montants[(int) $id_coprod]['total_part_ht'];
                        $return['coprods'][(int) $id_coprod]['total_part_tva'] += $coprods_montants[(int) $id_coprod]['total_part_tva'];
                        $return['coprods'][(int) $id_coprod]['total_part_ttc'] += $coprods_montants[(int) $id_coprod]['total_part_ttc'];
                        $return['coprods'][(int) $id_coprod]['total_paid_ht'] += $coprods_montants[(int) $id_coprod]['total_paid_ht'];
                        $return['coprods'][(int) $id_coprod]['total_paid_tva'] += $coprods_montants[(int) $id_coprod]['total_paid_tva'];
                        $return['coprods'][(int) $id_coprod]['total_paid_ttc'] += $coprods_montants[(int) $id_coprod]['total_paid_ttc'];
                    }
                }

                if (!array_key_exists($id_cat, $return['categories'])) {
                    $return['categories'][$id_cat] = array(
                        'total_ht'  => 0,
                        'total_tva' => 0,
                        'total_ttc' => 0,
                        'montants'  => array()
                    );
                    if ($showCoprods) {
                        $return['categories'][$id_cat]['coprods'] = array();
                        foreach ($coprods as $id_coprod) {
                            $return['categories'][$id_cat]['coprods'][(int) $id_coprod] = array(
                                'total_part_ht'  => 0,
                                'total_part_tva' => 0,
                                'total_part_ttc' => 0,
                                'total_paid_ht'  => 0,
                                'total_paid_tva' => 0,
                                'total_paid_ttc' => 0,
                            );
                        }
                    }
                }

                if (!array_key_exists($id_type_montant, $return['categories'][$id_cat]['montants'])) {
                    $return['categories'][$id_cat]['montants'][$id_type_montant] = array(
                        'total_ht'  => 0,
                        'total_ttc' => 0,
                        'total_tva' => 0
                    );
                    if ($showCoprods) {
                        $return['categories'][$id_cat]['montants'][$id_type_montant]['coprods'] = array();
                        foreach ($coprods as $id_coprod => $cp_label) {
                            $return['categories'][$id_cat]['montants'][$id_type_montant]['coprods'][(int) $id_coprod] = array(
                                'total_part_ht'  => 0,
                                'total_part_tva' => 0,
                                'total_part_ttc' => 0,
                                'total_paid_ht'  => 0,
                                'total_paid_tva' => 0,
                                'total_paid_ttc' => 0,
                            );
                        }
                    }
                }

                $return['categories'][$id_cat]['total_ht'] += $montant_ht;
                $return['categories'][$id_cat]['total_tva'] += $montant_tva;
                $return['categories'][$id_cat]['total_ttc'] += $montant_ttc;

                if ($showCoprods) {
                    foreach ($coprods as $id_coprod => $cp_label) {
                        $return['categories'][$id_cat]['coprods'][(int) $id_coprod]['total_part_ht'] += $coprods_montants[(int) $id_coprod]['total_part_ht'];
                        $return['categories'][$id_cat]['coprods'][(int) $id_coprod]['total_part_tva'] += $coprods_montants[(int) $id_coprod]['total_part_tva'];
                        $return['categories'][$id_cat]['coprods'][(int) $id_coprod]['total_part_ttc'] += $coprods_montants[(int) $id_coprod]['total_part_ttc'];
                        $return['categories'][$id_cat]['coprods'][(int) $id_coprod]['total_paid_ht'] += $coprods_montants[(int) $id_coprod]['total_paid_ht'];
                        $return['categories'][$id_cat]['coprods'][(int) $id_coprod]['total_paid_tva'] += $coprods_montants[(int) $id_coprod]['total_paid_tva'];
                        $return['categories'][$id_cat]['coprods'][(int) $id_coprod]['total_paid_ttc'] += $coprods_montants[(int) $id_coprod]['total_paid_ttc'];
                    }
                }

                $return['categories'][$id_cat]['montants'][$id_type_montant]['total_ht'] += $montant_ht;
                $return['categories'][$id_cat]['montants'][$id_type_montant]['total_tva'] += $montant_tva;
                $return['categories'][$id_cat]['montants'][$id_type_montant]['total_ttc'] += $montant_ttc;

                if ($showCoprods) {
                    foreach ($coprods as $id_coprod => $cp_label) {
                        $return['categories'][$id_cat]['montants'][$id_type_montant]['coprods'][(int) $id_coprod]['total_part_ht'] += $coprods_montants[(int) $id_coprod]['total_part_ht'];
                        $return['categories'][$id_cat]['montants'][$id_type_montant]['coprods'][(int) $id_coprod]['total_part_tva'] += $coprods_montants[(int) $id_coprod]['total_part_tva'];
                        $return['categories'][$id_cat]['montants'][$id_type_montant]['coprods'][(int) $id_coprod]['total_part_ttc'] += $coprods_montants[(int) $id_coprod]['total_part_ttc'];
                        $return['categories'][$id_cat]['montants'][$id_type_montant]['coprods'][(int) $id_coprod]['total_paid_ht'] += $coprods_montants[(int) $id_coprod]['total_paid_ht'];
                        $return['categories'][$id_cat]['montants'][$id_type_montant]['coprods'][(int) $id_coprod]['total_paid_tva'] += $coprods_montants[(int) $id_coprod]['total_paid_tva'];
                        $return['categories'][$id_cat]['montants'][$id_type_montant]['coprods'][(int) $id_coprod]['total_paid_ttc'] += $coprods_montants[(int) $id_coprod]['total_paid_ttc'];
                    }
                }
            }

            foreach ($return['categories'] as $id_cat => $tot_cat) {
                if ((float) $return['total_ht']) {
                    $return['categories'][$id_cat]['total_ht_percent'] = ($return['categories'][$id_cat]['total_ht'] / $return['total_ht']) * 100;
                }
                if ((float) $return['total_tva']) {
                    $return['categories'][$id_cat]['total_tva_percent'] = ($return['categories'][$id_cat]['total_tva'] / $return['total_tva']) * 100;
                }
                if ((float) $return['total_ttc']) {
                    $return['categories'][$id_cat]['total_ttc_percent'] = ($return['categories'][$id_cat]['total_ttc'] / $return['total_ttc']) * 100;
                }
            }
        }

        return $return;
    }

    public function getTotalAmounts($status = false)
    {
        $baseArray = array(
            'total_frais_ht'     => 0,
            'total_frais_ttc'    => 0,
            'total_recettes_ht'  => 0,
            'total_recettes_ttc' => 0,
            'solde_ht'           => 0,
            'solde_ttc'          => 0
        );

        $return = $baseArray;

        $return['categories'] = array();

        if (!is_null($status)) {
            $return['status'] = array();
        }

        if (!$this->isLoaded()) {
            return $return;
        }

        $montants = $this->getChildrenObjects('montants');
        $coprods = $this->getChildrenObjects('coprods');

        foreach ($coprods as $cp) {
            $return['coprod_' . $cp->id] = array(
                'solde_ht'           => 0,
                'solde_ttc'          => 0,
                'total_paid_ht'      => 0,
                'total_paid_ttc'     => 0,
                'total_received_ht'  => 0,
                'total_received_ttc' => 0
            );
        }

        foreach ($montants as $montant) {
            $m_status = (int) $montant->getData('status');
            $id_category = (int) $montant->getData('id_category_montant');

            if (!isset($return['categories'][$id_category])) {
                $return['categories'][$id_category] = $baseArray;
                foreach ($coprods as $cp) {
                    $return['categories'][$id_category]['coprod_' . $cp->id] = array(
                        'solde_ht'  => 0,
                        'solde_ttc' => 0
                    );
                }
            }

            if ($status) {
                if (!isset($return['status'][$m_status])) {
                    $return['status'][$m_status] = $baseArray;
                    $return['status'][$m_status]['categories'] = array();

                    foreach ($coprods as $cp) {
                        $return['status'][$m_status]['coprod_' . $cp->id] = array(
                            'solde_ht'           => 0,
                            'solde_ttc'          => 0,
                            'total_paid_ht'      => 0,
                            'total_paid_ttc'     => 0,
                            'total_received_ht'  => 0,
                            'total_received_ttc' => 0
                        );
                    }
                }

                if (!isset($return['status'][$m_status]['categories'][$id_category])) {
                    $return['status'][$m_status]['categories'][$id_category] = $baseArray;
                    foreach ($coprods as $cp) {
                        $return['status'][$m_status]['categories'][$id_category]['coprod_' . $cp->id] = array(
                            'solde_ht'  => 0,
                            'solde_ttc' => 0
                        );
                    }
                }
            }

            $value_ht = (float) $montant->getData('amount');
            $tva_tx = (float) $montant->getData('tva_tx');
            $value_ttc = BimpTools::calculatePriceTaxIn($value_ht, $tva_tx);
            $type = (int) $montant->getData('type');

            switch ($type) {
                case 1:
                    $return['total_frais_ht'] += $value_ht;
                    $return['total_frais_ttc'] += $value_ttc;
                    $return['solde_ht'] -= $value_ht;
                    $return['solde_ttc'] -= $value_ttc;
                    $return['categories'][$id_category]['total_frais_ht'] += $value_ht;
                    $return['categories'][$id_category]['total_frais_ttc'] += $value_ttc;
                    $return['categories'][$id_category]['solde_ht'] -= $value_ht;
                    $return['categories'][$id_category]['solde_ttc'] -= $value_ttc;

                    if ($status) {
                        $return['status'][$m_status]['total_frais_ht'] += $value_ht;
                        $return['status'][$m_status]['total_frais_ttc'] += $value_ttc;
                        $return['status'][$m_status]['solde_ht'] -= $value_ht;
                        $return['status'][$m_status]['solde_ttc'] -= $value_ttc;
                        $return['status'][$m_status]['categories'][$id_category]['total_frais_ht'] += $value_ht;
                        $return['status'][$m_status]['categories'][$id_category]['total_frais_ttc'] += $value_ttc;
                        $return['status'][$m_status]['categories'][$id_category]['solde_ht'] -= $value_ht;
                        $return['status'][$m_status]['categories'][$id_category]['solde_ttc'] -= $value_ttc;
                    }
                    break;

                case 2:
                    $return['total_recettes_ht'] += $value_ht;
                    $return['total_recettes_ttc'] += $value_ttc;
                    $return['solde_ht'] += $value_ht;
                    $return['solde_ttc'] += $value_ttc;
                    $return['categories'][$id_category]['total_recettes_ht'] += $value_ht;
                    $return['categories'][$id_category]['total_recettes_ttc'] += $value_ttc;
                    $return['categories'][$id_category]['solde_ht'] += $value_ht;
                    $return['categories'][$id_category]['solde_ttc'] += $value_ttc;

                    if ($status) {
                        $return['status'][$m_status]['total_recettes_ht'] += $value_ht;
                        $return['status'][$m_status]['total_recettes_ttc'] += $value_ttc;
                        $return['status'][$m_status]['categories'][$id_category]['total_recettes_ht'] += $value_ht;
                        $return['status'][$m_status]['categories'][$id_category]['total_recettes_ttc'] += $value_ttc;
                        $return['status'][$m_status]['solde_ht'] += $value_ht;
                        $return['status'][$m_status]['solde_ttc'] += $value_ttc;
                        $return['status'][$m_status]['categories'][$id_category]['solde_ht'] += $value_ht;
                        $return['status'][$m_status]['categories'][$id_category]['solde_ttc'] += $value_ttc;
                    }
                    break;
            }

            $paiements = $montant->getPaiements();


            foreach ($coprods as $cp) {
                $cp_part = (float) $montant->getCoProdPart($cp->id);
                if ($cp_part > 0) {
                    $cp_amount_ht = (float) ($value_ht * ($cp_part / 100));
                    $cp_amount_ttc = (float) ($value_ttc * ($cp_part / 100));

                    switch ($type) {
                        case 1:
                            $return['coprod_' . $cp->id]['solde_ht'] -= $cp_amount_ht;
                            $return['coprod_' . $cp->id]['solde_ttc'] -= $cp_amount_ttc;
                            $return['categories'][$id_category]['coprod_' . $cp->id]['solde_ht'] -= $cp_amount_ht;
                            $return['categories'][$id_category]['coprod_' . $cp->id]['solde_ttc'] -= $cp_amount_ttc;

                            if ($status) {
                                $return['status'][$m_status]['categories'][$id_category]['coprod_' . $cp->id]['solde_ht'] -= $cp_amount_ht;
                                $return['status'][$m_status]['categories'][$id_category]['coprod_' . $cp->id]['solde_ttc'] -= $cp_amount_ttc;
                                $return['status'][$m_status]['coprod_' . $cp->id]['solde_ht'] -= $cp_amount_ht;
                                $return['status'][$m_status]['coprod_' . $cp->id]['solde_ttc'] -= $cp_amount_ttc;
                            }

                            if (isset($paiements[(int) $cp->id])) {
                                $return['coprod_' . $cp->id]['total_paid_ttc'] += (float) $paiements[(int) $cp->id];
                                $return['coprod_' . $cp->id]['total_paid_ht'] += BimpTools::calculatePriceTaxEx((float) $paiements[(int) $cp->id], $tva_tx);

                                if ($status) {
                                    $return['status'][$m_status]['coprod_' . $cp->id]['total_paid_ttc'] += (float) $paiements[(int) $cp->id];
                                    $return['status'][$m_status]['coprod_' . $cp->id]['total_paid_ht'] += BimpTools::calculatePriceTaxEx((float) $paiements[(int) $cp->id], $tva_tx);
                                }
                            }
                            break;

                        case 2:
                            $return['coprod_' . $cp->id]['solde_ht'] += $cp_amount_ht;
                            $return['coprod_' . $cp->id]['solde_ttc'] += $cp_amount_ttc;
                            $return['categories'][$id_category]['coprod_' . $cp->id]['solde_ht'] += $cp_amount_ht;
                            $return['categories'][$id_category]['coprod_' . $cp->id]['solde_ttc'] += $cp_amount_ttc;

                            if ($status) {
                                $return['status'][$m_status]['categories'][$id_category]['coprod_' . $cp->id]['solde_ht'] += $cp_amount_ht;
                                $return['status'][$m_status]['categories'][$id_category]['coprod_' . $cp->id]['solde_ttc'] += $cp_amount_ttc;
                                $return['status'][$m_status]['coprod_' . $cp->id]['solde_ht'] += $cp_amount_ht;
                                $return['status'][$m_status]['coprod_' . $cp->id]['solde_ttc'] += $cp_amount_ttc;
                            }

                            if (isset($paiements[(int) $cp->id])) {
                                $return['coprod_' . $cp->id]['total_received_ttc'] += (float) $paiements[(int) $cp->id];
                                $return['coprod_' . $cp->id]['total_received_ht'] += BimpTools::calculatePriceTaxEx((float) $paiements[(int) $cp->id], $tva_tx);

                                if ($status) {
                                    $return['status'][$m_status]['coprod_' . $cp->id]['total_received_ttc'] += (float) $paiements[(int) $cp->id];
                                    $return['status'][$m_status]['coprod_' . $cp->id]['total_received_ht'] += BimpTools::calculatePriceTaxEx((float) $paiements[(int) $cp->id], $tva_tx);
                                }
                            }
                            break;
                    }
                }
            }
        }
        return $return;
    }

    public function GetFreeBilletsRatio()
    {
        if ($this->isLoaded()) {
            $tarifs = BimpObject::getInstance($this->module, 'BMP_EventTarif');
            $freeTarifs = array();

            foreach ($tarifs->getList(array(
                'id_event' => (int) $this->id,
                'amount'   => 0
                    ), null, null, 'id', 'asc', 'array', array('id')) as $item) {
                $freeTarifs[] = $item['id'];
            }

            $nTotal = 0;
            $nFree = 0;

            if ((int) $this->getData('status') > 1) {
                foreach ($this->getChildrenObjects('billets') as $billet) {
                    $qty = (int) $billet->getData('quantity');
                    $nTotal += $qty;
                    if (in_array((int) $billet->getData('id_tarif'), $freeTarifs)) {
                        $nFree += $qty;
                    }
                }
            } else {
                foreach ($tarifs->getList(array(
                    'id_event' => (int) $this->id
                        ), null, null, 'id', 'asc', 'array', array('id', 'previsionnel')) as $tarif) {
                    $nTotal += (int) $tarif['previsionnel'];
                    if (in_array((int) $tarif['id'], $freeTarifs)) {
                        $nFree += (int) $tarif['previsionnel'];
                    }
                }
            }

            if ($nTotal > 0) {
                return (float) $nFree / ($nTotal - $nFree );
            }
        }

        return 0;
    }

    public function getBilletsAmounts()
    {
        if ((int) $this->getData('status') === 1) {
            return $this->getPrevisionnels();
        }

        $amounts = array(
            'total_billets_ttc'        => 0,
            'total_billets_ht'         => 0,
            'total_dl_dist_ttc'        => 0,
            'total_dl_dist_ht'         => 0,
            'total_dl_prod_ttc'        => 0,
            'total_dl_prod_ht'         => 0,
            'total_frais_materiel_ttc' => 0,
            'total_frais_materiel_ht'  => 0,
            'total_nb_billets'         => 0,
            'coprods'                  => array()
        );

        if ($this->isLoaded()) {
            $coprods = $this->getCoProds(true);

            foreach ($coprods as $id_cp => $cp_label) {
                $amounts['coprods'][(int) $id_cp] = array(
                    'total_billets_ttc'        => 0,
                    'total_billets_ht'         => 0,
                    'total_dl_dist_ttc'        => 0,
                    'total_dl_dist_ht'         => 0,
                    'total_dl_prod_ttc'        => 0,
                    'total_dl_prod_ht'         => 0,
                    'total_frais_materiel_ttc' => 0,
                    'total_frais_materiel_ht'  => 0,
                    'total_nb_billets'         => 0
                );
            }

            $ventes = $this->getChildrenObjects('billets');
            $frais_billet = (float) $this->getData('frais_billet');

            foreach ($ventes as $vente) {
                $total = (float) $vente->getTotal();
                $dl_dist = (float) $vente->getTotalDLDist();
                $dl_prod = (float) $vente->getTotalDLProd();
                $qty = (float) $vente->getData('quantity');
                $id_cp = (int) $vente->getData('id_coprod');
                $frais_billets_ht = $frais_billet * $qty;

                $amounts['total_billets_ttc'] += $total;
                $amounts['total_dl_dist_ttc'] += $dl_dist;
                $amounts['total_dl_prod_ttc'] += $dl_prod;
                $amounts['total_frais_materiel_ht'] += $frais_billets_ht;
                $amounts['total_nb_billets'] += $qty;

                $amounts['coprods'][$id_cp]['total_billets_ttc'] += $total;
                $amounts['coprods'][$id_cp]['total_dl_dist_ttc'] += $dl_dist;
                $amounts['coprods'][$id_cp]['total_dl_prod_ttc'] += $dl_prod;
                $amounts['coprods'][$id_cp]['total_frais_materiel_ht'] += $frais_billets_ht;
                $amounts['coprods'][$id_cp]['total_nb_billets'] += $qty;
            }

            $billets_tva_tx = (float) $this->getBilletsTvaTx();
            $frais_billets_tva_tx = (float) $this->getMontantTvaTx(self::$id_frais_billets_materiels);

            $amounts['total_billets_ht'] = BimpTools::calculatePriceTaxEx($amounts['total_billets_ttc'], $billets_tva_tx);
            $amounts['total_dl_dist_ht'] = BimpTools::calculatePriceTaxEx($amounts['total_dl_dist_ttc'], $billets_tva_tx);
            $amounts['total_dl_prod_ht'] = BimpTools::calculatePriceTaxEx($amounts['total_dl_prod_ttc'], $billets_tva_tx);
            $amounts['total_frais_materiel_ttc'] = BimpTools::calculatePriceTaxIn($amounts['total_frais_materiel_ht'], $frais_billets_tva_tx);

            foreach ($amounts['coprods'] as $id_cp => $cp_amounts) {
                $amounts['coprods'][(int) $id_cp]['total_billets_ht'] = BimpTools::calculatePriceTaxEx($cp_amounts['total_billets_ttc'], $billets_tva_tx);
                $amounts['coprods'][(int) $id_cp]['total_dl_dist_ht'] = BimpTools::calculatePriceTaxEx($cp_amounts['total_dl_dist_ttc'], $billets_tva_tx);
                $amounts['coprods'][(int) $id_cp]['total_dl_prod_ht'] = BimpTools::calculatePriceTaxEx($cp_amounts['total_dl_prod_ttc'], $billets_tva_tx);
                $amounts['coprods'][(int) $id_cp]['total_frais_materiel_ttc'] = BimpTools::calculatePriceTaxIn($cp_amounts['total_frais_materiel_ht'], $frais_billets_tva_tx);
            }
        }

        return $amounts;
    }

    public function getBilletsNumbers()
    {
        $numbers = array(
            'total_payants'  => 0,
            'total_gratuits' => 0,
            'total_loc'      => 0,
            'total'          => 0
        );

        if ((int) $this->getData('status') === 1) {
            $tarifs = $this->getChildrenObjects('tarifs');

            foreach ($tarifs as $tarif) {
                $amount = (float) $tarif->getData('amount');
                $qty = (int) $tarif->getData('previsionnel');

                if (!$amount) {
                    $numbers['total_gratuits'] += $qty;
                } else {
                    $numbers['total_payants'] += $qty;
                }
                $numbers['total'] += $qty;
            }
        } else {
            $tarifs = $this->getChildrenObjects('tarifs', array(), 'id', 'asc', true);

            $ventes = $this->getChildrenObjects('billets');
            foreach ($ventes as $vente) {
                $qty = (float) $vente->getData('quantity');

                if (!$qty) {
                    continue;
                }

                $id_tarif = (int) $vente->getData('id_tarif');

                $amount = 0;
                if (isset($tarifs[$id_tarif])) {
                    $amount = (float) $tarifs[$id_tarif]->getData('amount');
                }

                if ($amount) {
                    $numbers['total_payants'] += $qty;
                } else {
                    $numbers['total_gratuits'] += $qty;
                }
                $numbers['total'] += $qty;
            }
        }

        $numbers['total_loc'] = (int) $this->getData('billets_loc');
        $numbers['total'] += $numbers['total_loc'];

        return $numbers;
    }

    public function getCoprodsSoldes()
    {
        $return = array();

        if (!$this->isLoaded()) {
            return $return;
        }

        $montants = $this->getChildrenObjects('montants');
        $coprods = $this->getCoProds();

        foreach ($coprods as $id_cp => $cp_name) {
            $return[$id_cp] = 0;
        }

        foreach ($montants as $montant) {
            $type = (int) $montant->getData('type');
            $paiements = $montant->getPaiements();

            foreach ($coprods as $id_cp => $cp_name) {
                $paid_ht = 0;
                $part = $montant->getCoProdPartAmount((int) $id_cp);

                if (isset($paiements[(int) $id_cp]) && (float) $paiements[(int) $id_cp]) {
                    $paid_ht = BimpTools::calculatePriceTaxEx($paiements[(int) $id_cp], (float) $montant->getData('tva_tx'));
                }

                switch ($type) {
                    case BMP_TypeMontant::BMP_TYPE_FRAIS:
                        $return[(int) $id_cp] -= $part;
                        $return[(int) $id_cp] += $paid_ht;
                        break;

                    case BMP_TypeMontant::BMP_TYPE_RECETTE:
                        $return[(int) $id_cp] += $part;
                        $return[(int) $id_cp] -= $paid_ht;
                        break;
                }
            }
        }

        return $return;
    }

    public function getSoldes()
    {
        $return = array(
            'frais'    => 0,
            'recettes' => 0,
            'solde'    => 0
        );

        if (!$this->isLoaded()) {
            return $return;
        }

        $montants = $this->getChildrenObjects('montants');

        foreach ($montants as $montant) {
            $amount = $montant->getData('amount');
            $type = (int) $montant->getData('type');

            switch ($type) {
                case BMP_TypeMontant::BMP_TYPE_FRAIS:
                    $return['frais'] += $amount;
                    $return['solde'] -= $amount;
                    break;

                case BMP_TypeMontant::BMP_TYPE_RECETTE:
                    $return['recettes'] += $amount;
                    $return['solde'] += $amount;
                    break;
            }
        }

        $cp_soldes = $this->getCoprodsSoldes();

        foreach ($cp_soldes as $id_cp => $cp_solde) {
            if ($cp_solde < 0) {
                $return['recettes'] -= $cp_solde;
                $return['solde'] -= $cp_solde;
            } else {
                $return['frais'] += $cp_solde;
                $return['solde'] -= $cp_solde;
            }
        }

        return $return;
    }

    public function calcMontant($id_type_montant, $id_coprod = null)
    {
        if (!$this->isLoaded()) {
            return;
        }

        $montant = BimpObject::getInstance($this->module, 'BMP_EventMontant');

        $filters = array(
            'id_event'   => $this->id,
            'id_montant' => (int) $id_type_montant
        );

        if (!is_null($id_coprod)) {
            $filters['id_coprod'] = (int) $id_coprod;
        }

        $list = $montant->getList($filters);

        $items = array();
        foreach ($this->getChildrenObjects('calcs_montants') as $eventCalcMontant) {
            $calcMontant = $eventCalcMontant->getChildObject('calc_montant');
            if ((int) $calcMontant->getData('id_target') === $id_type_montant) {
                $items[] = $eventCalcMontant;
            }
        }

        foreach ($list as $elem) {
            $montant = BimpCache::getBimpObjectInstance($this->module, 'BMP_EventMontant', (int) $elem['id']);
            if ($montant->isLoaded()) {
//                $id_coprod = (int) $montant->getData('id_coprod');
                if (count($items)) {
                    $amount_ht = 0;
                    foreach ($items as $item) {
                        if ((int) $item->getData('active')) {
                            $amount_ht += $item->getAmount($id_coprod);
                        }
                    }

                    // Traitement particulier SACEM: 
                    if ((int) $id_type_montant === self::$id_sacem_billets_montant) {
                        $calc_sacem_billets = $this->getCalcMontant(self::$id_calc_sacem_billets);
                        if (BimpObject::objectLoaded($calc_sacem_billets) && (int) $calc_sacem_billets->getData('active')) {
                            if ($amount_ht < self::$sacem_billets_min) {
                                $amount_ht = self::$sacem_billets_min;
                            }
                        }
                    }

                    if ((float) $amount_ht !== (float) $montant->getData('amount')) {
                        $montant->set('amount', $amount_ht);
                        $montant->update();
                    }
                }
            }
        }
    }

    public static function getTotalComptable($events, $id_coprod = 0, $cp_label = 'Le Fil')
    {
        $amounts = array(
            'categories'       => array(),
            'total_frais'      => 0,
            'total_recettes'   => 0,
            'solde'            => 0,
            'solde_without_dl' => 0
        );

        $total_coprods = array();
        $total_dl_dist = 0;

        $cp_instance = BimpObject::getInstance('bimpmargeprod', 'BMP_EventCoProd');
        $soc_instance = BimpObject::getInstance('bimpcore', 'Bimp_Societe');

        foreach ($events as $id_event) {
            $event = BimpCache::getBimpObjectInstance('bimpmargeprod', 'BMP_Event', (int) $id_event);

            if (!BimpObject::objectLoaded($event)) {
                continue;
            }

            $cp_soldes = $event->getCoprodsSoldes();

            $billets_amounts = $event->getBilletsAmounts();
            $montants = $event->getChildrenObjects('montants');

            foreach ($montants as $montant) {
                $paiements = $montant->getPaiements();
                if (!isset($paiements[(int) $id_coprod]) || !(float) $paiements[(int) $id_coprod]) {
                    continue;
                }

                $amount_ttc = (float) $paiements[(int) $id_coprod];
                $amount_ht = (float) BimpTools::calculatePriceTaxEx($amount_ttc, (float) $montant->getTvaTx());

                if (!round($amount_ht, 2)) {
                    continue;
                }

                $tm = BimpCache::getBimpObjectInstance($event->module, 'BMP_TypeMontant', (int) $montant->getData('id_montant'));

                if (!BimpObject::objectLoaded($tm)) {
                    continue;
                }

                $id_category = (int) $tm->getData('id_category');

                if (!isset($amounts['categories'][$id_category])) {
                    $category = BimpCache::getBimpObjectInstance($event->module, 'BMP_CategorieMontant', $id_category);

                    $amounts['categories'][$id_category] = array(
                        'name'           => $category->getData('name'),
                        'color'          => $category->getData('color'),
                        'total_frais'    => 0,
                        'total_recettes' => 0,
                        'rows'           => array()
                    );
                }

                $filtre = $tm->id . "-" . $montant->getTvaTx();
                $row = array(
                    'status'       => $montant->displayData('status', 'default', false),
                    'type_montant' => $tm->getData('name'),
                    'code'         => $tm->getData('code_compta'),
                    'tva'          => BimpTools::displayFloatValue($montant->getTvaTx()) . '%',
                    'frais'        => (isset($amounts['categories'][$id_category]['rows'][$filtre]) ? $amounts['categories'][$id_category]['rows'][$filtre]['frais'] : 0),
                    'recette'      => (isset($amounts['categories'][$id_category]['rows'][$filtre]) ? $amounts['categories'][$id_category]['rows'][$filtre]['recette'] : 0)
                );

                switch ((int) $tm->getData('type')) {
                    case BMP_TypeMontant::BMP_TYPE_FRAIS:
                        $amounts['categories'][$id_category]['total_frais'] += $amount_ht;
                        $row['frais'] += $amount_ht;
                        $amounts['total_frais'] += $amount_ht;
                        $amounts['solde'] -= $amount_ht;
                        break;

                    case BMP_TypeMontant::BMP_TYPE_RECETTE:
                        $amounts['categories'][$id_category]['total_recettes'] += $amount_ht;
                        $row['recette'] += $amount_ht;
                        $amounts['total_recettes'] += $amount_ht;
                        $amounts['solde'] += $amount_ht;
                        break;
                }

                $amounts['categories'][$id_category]['rows'][$filtre] = $row;
            }


            if (!(int) $id_coprod) {
                if (!empty($cp_soldes)) {
                    foreach ($cp_soldes as $id_cp => $cp_solde) {
                        if (!(float) $cp_solde) {
                            continue;
                        }

                        $id_soc_cp = (int) $cp_instance->getSavedData('id_soc', (int) $id_cp);

                        if (!isset($total_coprods[$id_soc_cp])) {
                            $total_coprods[$id_soc_cp] = array(
                                'total_frais'    => 0,
                                'total_recettes' => 0
                            );
                        }

                        if ($cp_solde > 0) {
                            $total_coprods[$id_soc_cp]['total_recettes'] += $cp_solde;
                        } else {
                            $cp_solde *= -1;
                            $total_coprods[$id_soc_cp]['total_frais'] += $cp_solde;
                        }
                    }
                }
            } elseif (isset($cp_soldes[(int) $id_coprod]) && (float) $cp_soldes[(int) $id_coprod]) {
                $cp_solde = (float) $cp_soldes[(int) $id_coprod];

                if (!isset($total_coprods[0])) {
                    $total_coprods[0] = array(
                        'total_frais'    => 0,
                        'total_recettes' => 0
                    );
                }

                if ($cp_solde > 0) {
                    $total_coprods[0]['total_recettes'] += $cp_solde;
                } else {
                    $cp_solde *= -1;
                    $total_coprods[0]['total_frais'] += $cp_solde;
                }
            }

            if (isset($billets_amounts['coprods'][(int) $id_coprod])) {
                $total_dl_dist += (float) $billets_amounts['coprods'][(int) $id_coprod]['total_dl_dist_ht'];
            } elseif (!(int) $id_coprod) {
                $total_dl_dist += (float) $billets_amounts['total_dl_dist_ht'];
            }
        }


        //presentation en euro et "" quand 0
        foreach ($amounts['categories'] as $id_categ => $rows) {
            foreach ($rows['rows'] as $nom => $row) {
                if ($amounts['categories'][$id_categ]['rows'][$nom]['frais'] == 0)
                    $amounts['categories'][$id_categ]['rows'][$nom]['frais'] = "";
                else
                    $amounts['categories'][$id_categ]['rows'][$nom]['frais'] = BimpTools::displayMoneyValue($amounts['categories'][$id_categ]['rows'][$nom]['frais'], 'EUR');
                if ($amounts['categories'][$id_categ]['rows'][$nom]['recette'] == 0)
                    $amounts['categories'][$id_categ]['rows'][$nom]['recette'] = "";
                else
                    $amounts['categories'][$id_categ]['rows'][$nom]['recette'] = BimpTools::displayMoneyValue($amounts['categories'][$id_categ]['rows'][$nom]['recette'], 'EUR');
            }
        }

        // Ajout DL Distributeur
        if ($total_dl_dist > 0) {
            if (!isset($amounts['categories'][self::$id_billets_category])) {
                $category = BimpCache::getBimpObjectInstance($event->module, 'BMP_CategorieMontant', self::$id_billets_category);
                $amounts['categories'][self::$id_billets_category] = array(
                    'name'           => $category->getData('name'),
                    'color'          => $category->getData('color'),
                    'total_frais'    => 0,
                    'total_recettes' => 0,
                    'rows'           => array()
                );
            }
            $amounts['categories'][self::$id_billets_category]['rows'][] = array(
                'status'       => '',
                'type_montant' => 'Droits de location distributeur',
                'code'         => '',
                'tva'          => '',
                'frais'        => '',
                'recette'      => BimpTools::displayMoneyValue($total_dl_dist, 'EUR')
            );

            $amounts['categories'][self::$id_billets_category]['total_recettes'] += $total_dl_dist;
            $amounts['solde'] += $total_dl_dist;
            $amounts['total_recettes'] += $total_dl_dist;
        }

        // Ajout totaux coprods: 
        if (!empty($total_coprods)) {
            if (!isset($amounts['categories'][self::$id_coprods_category])) {
                $category = BimpCache::getBimpObjectInstance($event->module, 'BMP_CategorieMontant', self::$id_coprods_category);
                $amounts['categories'][self::$id_coprods_category] = array(
                    'name'           => $category->getData('name'),
                    'color'          => $category->getData('color'),
                    'total_frais'    => 0,
                    'total_recettes' => 0,
                    'rows'           => array()
                );
            }

            foreach ($total_coprods as $id_soc_cp => $total_cp) {
                if (!(int) $id_coprod) {
                    $solde = (float) $total_cp['total_frais'] - (float) $total_cp['total_recettes'];
                } else {
                    $solde = (float) $total_cp['total_recettes'] - (float) $total_cp['total_frais'];
                }

                $soc_label = '';

                if (!$id_soc_cp) {
                    if ((int) $id_coprod) {
                        $soc_label = 'Le Fil';
                    } else {
                        $soc_label = 'Inconnu';
                    }
                } else {
                    $soc_label = $soc_instance->getSavedData('nom', (int) $id_soc_cp);
                }

                $row = array(
                    'status'       => '',
                    'type_montant' => $soc_label,
                    'code'         => '',
                    'tva'          => '',
                    'frais'        => '',
                    'recette'      => ''
                );

                if ($solde > 0) {
                    $amounts['categories'][self::$id_coprods_category]['total_recettes'] += $solde;
                    $row['recette'] = BimpTools::displayMoneyValue($solde, 'EUR');
                    $amounts['total_recettes'] += $solde;
                    $amounts['solde'] += $solde;
                } else {
                    $solde *= -1;
                    $amounts['categories'][self::$id_coprods_category]['total_frais'] += $solde;
                    $row['frais'] = BimpTools::displayMoneyValue($solde, 'EUR');
                    $amounts['total_frais'] += $solde;
                    $amounts['solde'] -= $solde;
                }

                $amounts['categories'][self::$id_coprods_category]['rows'][] = $row;
            }
        }

        $amounts['solde_without_dl'] = $amounts['solde'] - $total_dl_dist;

        return $amounts;
    }

    // Checks-up:

    public function checkCoprodsParts()
    {
        $errors = array();

        if ($this->isLoaded()) {
            $coprods = $this->getCoProds();
            $eventMontant = BimpObject::getInstance($this->module, 'BMP_EventMontant');
            $categs = $eventMontant->getAllCategoriesArray();

            $cpdp = BimpObject::getInstance($this->module, 'BMP_EventCoProdDefPart');
            $list = $cpdp->getList(array(
                'id_event' => (int) $this->id
            ));

            $categories = array();

            foreach ($list as $item) {
                if (!isset($categories[(int) $item['id_category_montant']])) {
                    $categories[(int) $item['id_category_montant']] = array();
                }
                $categories[(int) $item['id_category_montant']][(int) $item['id_event_coprod']] = (float) $item['part'];
            }

            foreach ($categories as $id_cat => $cat_coprods) {
                $total = 0;
                $total_wo_def = 0;

                $coprods_def = array();
                foreach ($cat_coprods as $id_coprod => $part) {
                    $def_part = (float) $this->db->getValue('bmp_event_coprod', 'default_part', '`id` = ' . $id_coprod);
                    if ($def_part !== $part) {
                        $total_wo_def += $part;
                    } else {
                        $coprods_def[] = $id_coprod;
                    }
                    $total += $part;
                }

                if ($total > 100) {
                    if ($total_wo_def < 100 && count($coprods_def)) {
                        $def_part = (100 - $total_wo_def) / count($coprods_def);
                        foreach ($coprods_def as $id_cp) {
                            $where = '`id_event_coprod` = ' . (int) $id_cp . ' AND `id_category_montant` = ' . (int) $id_cat;
                            $where .= ' AND `id_event` = ' . (int) $this->id;
                            if ($this->db->update('bmp_event_coprod_def_part', array(
                                        'part' => (float) $def_part
                                            ), $where) <= 0) {
                                $msg = 'Echec de la mise à jour de la part du Co-producteur "' . $coprods[$id_cp] . '" ';
                                $msg .= 'pour la catégorie "' . $categs[$id_cat] . '"';
                                $errors[] = $msg;
                            }
                        }
                    } else {
                        $diff = round((100 - $total) / count($cat_coprods), 2, PHP_ROUND_HALF_UP);
                        foreach ($cat_coprods as $id_cp => $part) {
                            $where = '`id_event_coprod` = ' . (int) $id_cp . ' AND `id_category_montant` = ' . (int) $id_cat;
                            $where .= ' AND `id_event` = ' . (int) $this->id;
                            if ($this->db->update('bmp_event_coprod_def_part', array(
                                        'part' => (float) ($part - $diff)
                                            ), $where) <= 0) {
                                $msg = 'Echec de la mise à jour de la part du Co-producteur "' . $coprods[$id_cp] . '" ';
                                $msg .= 'pour la catégorie "' . $categs[$id_cat] . '"';
                                $errors[] = $msg;
                            }
                        }
                    }
                }
            }

            $eventMontants = $this->getChildrenObjects('montants');
            foreach ($eventMontants as $em) {
                $em->checkCoprodsParts();
            }
        }

        return $errors;
    }

    // Traitements: 

    public function createMontant($id_type_montant, $amount = 0, $id_coprod = 0, &$errors = array())
    {
        if (!$this->isLoaded()) {
            $errors[] = 'ID de l\'événement absent';
            return null;
        }

        $typeMontant = BimpCache::getBimpObjectInstance($this->module, 'BMP_TypeMontant', $id_type_montant);
        if (!BimpObject::objectLoaded($typeMontant)) {
            $errors[] = 'Le type de montant d\'ID ' . $id_type_montant . ' n\'existe pas';
        } else {
            $montant = BimpObject::getInstance($this->module, 'BMP_EventMontant');
            $montant->validateArray(array(
                'id_event'            => (int) $this->id,
                'id_montant'          => (int) $id_type_montant,
                'id_coprod'           => (int) $id_coprod,
                'id_category_montant' => (int) $typeMontant->getData('id_category'),
                'type'                => (int) $typeMontant->getData('type'),
                'amount'              => (float) $amount,
                'tva_tx'              => (float) BimpTools::getTaxeRateById($typeMontant->getData('id_taxe')),
                'comments'            => ''
            ));

            $errors = $montant->create();

            if (!count($errors)) {
                return $montant;
            }
        }

        return null;
    }

    public function calcPrevisionnels()
    {
        if (!$this->isLoaded()) {
            return;
        }

        if ((int) $this->getData('status') !== 1) {
            return;
        }

        $previsionnels = $this->getPrevisionnels();

        $coprods = $this->getCoProds();

        $id_billets_montant = (int) $this->getBilletsIdTypeMontant();
        $eventMontant = $this->getMontant($id_billets_montant);
        if (BimpObject::objectLoaded($eventMontant)) {
            $current_amount = (float) $eventMontant->getData('amount');
            if ($current_amount !== (float) $previsionnels['total_billets_ht']) {
                $eventMontant->set('amount', $previsionnels['total_billets_ht']);
                $eventMontant->update();
            }
        }

        foreach ($coprods as $id_cp => $cp_name) {
            $eventMontant = $this->getMontant($id_billets_montant, (int) $id_cp);
            if (BimpObject::objectLoaded($eventMontant)) {
                if ((float) $eventMontant->getData('amount')) {
                    $eventMontant->set('amount', 0);
                    $eventMontant->update();
                }
            }
        }

        $id_type_montant = 0;
        switch ($id_billets_montant) {
            case self::$id_billets_2_1_type_montant:
                $id_type_montant = self::$id_billets_5_5_type_montant;
                break;

            case self::$id_billets_5_5_type_montant:
                $id_type_montant = self::$id_billets_2_1_type_montant;
                break;
        }

        if ((int) $id_type_montant) {
            $eventMontant = $this->getMontant($id_type_montant);
            if (BimpObject::objectLoaded($eventMontant)) {
                if ((float) $eventMontant->getData('amount')) {
                    $eventMontant->set('amount', 0);
                    $eventMontant->update();
                }
            }

            foreach ($coprods as $id_cp => $cp_name) {
                $eventMontant = $this->getMontant($id_type_montant, (int) $id_cp);
                if (BimpObject::objectLoaded($eventMontant)) {
                    if ((float) $eventMontant->getData('amount')) {
                        $eventMontant->set('amount', 0);
                        $eventMontant->update();
                    }
                }
            }
        }

        $eventMontant = $this->getMontant(self::$id_dl_prod_montant);
        if (BimpObject::objectLoaded($eventMontant)) {
            $current_amount = (float) $eventMontant->getData('amount');
            if ($current_amount !== (float) $previsionnels['total_dl_prod_ht']) {
                $eventMontant->set('amount', $previsionnels['total_dl_prod_ht']);
                $eventMontant->update();
            }
        }

        foreach ($coprods as $id_cp => $cp_name) {
            $eventMontant = $this->getMontant(self::$id_dl_prod_montant, (int) $id_cp);
            if (BimpObject::objectLoaded($eventMontant)) {
                if ((float) $eventMontant->getData('amount')) {
                    $eventMontant->set('amount', 0);
                    $eventMontant->update();
                }
            }
        }


        $eventMontant = $this->getMontant(self::$id_frais_billets_materiels);
        if (BimpObject::objectLoaded($eventMontant)) {
            $current_amount = (float) $eventMontant->getData('amount');
            if ($current_amount !== (float) $previsionnels['total_frais_materiel_ht']) {
                $eventMontant->set('amount', $previsionnels['total_frais_materiel_ht']);
                $eventMontant->update();
            }
        }

        foreach ($coprods as $id_cp => $cp_name) {
            $eventMontant = $this->getMontant(self::$id_frais_billets_materiels, (int) $id_cp);
            if (BimpObject::objectLoaded($eventMontant)) {
                if ((float) $eventMontant->getData('amount')) {
                    $eventMontant->set('amount', 0);
                    $eventMontant->update();
                }
            }
        }

        $eventMontant = $this->getMontant(self::$id_bar20_type_montant);
        if (BimpObject::objectLoaded($eventMontant)) {
            $current_amount = (float) $eventMontant->getData('amount');
            if ($current_amount !== (float) $previsionnels['bar_ht']) {
                $eventMontant->set('amount', $previsionnels['bar_ht']);
                $eventMontant->update();
            }
        }

        foreach ($coprods as $id_cp => $cp_name) {
            $eventMontant = $this->getMontant(self::$id_bar20_type_montant, (int) $id_cp);
            if (BimpObject::objectLoaded($eventMontant)) {
                if ((float) $eventMontant->getData('amount')) {
                    $eventMontant->set('amount', 0);
                    $eventMontant->update();
                }
            }
        }

        $eventMontant = $this->getMontant(self::$id_bar55_type_montant);
        if (BimpObject::objectLoaded($eventMontant)) {
            $current_amount = (float) $eventMontant->getData('amount');
            if ($current_amount !== 0) {
                $eventMontant->set('amount', 0);
                $eventMontant->update();
            }
        }

        foreach ($coprods as $id_cp => $cp_name) {
            $eventMontant = $this->getMontant(self::$id_bar55_type_montant, (int) $id_cp);
            if (BimpObject::objectLoaded($eventMontant)) {
                if ((float) $eventMontant->getData('amount')) {
                    $eventMontant->set('amount', 0);
                    $eventMontant->update();
                }
            }
        }
    }

    public function calcBilletsAmount()
    {
        if (!$this->isLoaded()) {
            return;
        }

        $amounts = $this->getBilletsAmounts();

        $coprods = $this->getCoProds(true);
        $status = (int) $this->getData('status');
        $id_billets_montant = (int) $this->getBilletsIdTypeMontant();

        foreach ($coprods as $id_cp => $cp_label) {
            if ($status === 1) {
                if (!(int) $id_cp) {
                    $billets_montant = $this->getMontant($id_billets_montant);
                    if (!BimpObject::objectLoaded($billets_montant)) {
                        $this->createMontant($id_billets_montant, $amounts['total_billets_ht'], $id_cp);
                    } elseif ((float) $amounts['total_billets_ht'] !== $billets_montant->getData('amount')) {
                        $billets_montant->set('amount', $amounts['total_billets_ht']);
                        $billets_montant->update();
                    }

                    $dl_prod_montant = $this->getMontant(self::$id_dl_prod_montant);
                    if (!BimpObject::objectLoaded($dl_prod_montant)) {
                        $this->createMontant(self::$id_dl_prod_montant, $amounts['total_dl_prod_ht'], $id_cp);
                    } elseif ((float) $amounts['total_dl_prod_ht'] !== (float) $dl_prod_montant->getData('amount')) {
                        $dl_prod_montant->set('amount', $amounts['total_dl_prod_ht']);
                        $dl_prod_montant->update();
                    }
                } else {
                    $billets_montant = $this->getMontant($id_billets_montant, (int) $id_cp);
                    if (BimpObject::objectLoaded($billets_montant)) {
                        if ((float) $billets_montant->getData('amount') !== 0) {
                            $billets_montant->set('amount', 0);
                            $billets_montant->update();
                        }
                    }

                    $dl_prod_montant = $this->getMontant(self::$id_dl_prod_montant, (int) $id_cp);
                    if (BimpObject::objectLoaded($dl_prod_montant)) {
                        if ((float) $dl_prod_montant->getData('amount') !== 0) {
                            $dl_prod_montant->set('amount', 0);
                            $dl_prod_montant->update();
                        }
                    }
                }
            } elseif (isset($amounts['coprods'][(int) $id_cp])) {
                $billets_montant = $this->getMontant($id_billets_montant, (int) $id_cp);
                if (!BimpObject::objectLoaded($billets_montant)) {
                    $this->createMontant($id_billets_montant, $amounts['coprods'][(int) $id_cp]['total_billets_ht'], $id_cp);
                } elseif ((float) $amounts['coprods'][(int) $id_cp]['total_billets_ht'] !== $billets_montant->getData('amount')) {
                    $billets_montant->set('amount', $amounts['coprods'][(int) $id_cp]['total_billets_ht']);
                    $billets_montant->update();
                }

                $dl_prod_montant = $this->getMontant(self::$id_dl_prod_montant, (int) $id_cp);
                if (!BimpObject::objectLoaded($dl_prod_montant)) {
                    $this->createMontant(self::$id_dl_prod_montant, $amounts['coprods'][(int) $id_cp]['total_dl_prod_ht'], $id_cp);
                } elseif ((float) $amounts['coprods'][(int) $id_cp]['total_dl_prod_ht'] !== (float) $dl_prod_montant->getData('amount')) {
                    $dl_prod_montant->set('amount', $amounts['coprods'][(int) $id_cp]['total_dl_prod_ht']);
                    $dl_prod_montant->update();
                }
            }
        }

        $frais_billets_montant = $this->getMontant(self::$id_frais_billets_materiels);
        if (!BimpObject::objectLoaded($frais_billets_montant)) {
            $this->createMontant(self::$id_frais_billets_materiels, $amounts['total_frais_materiel_ht'], 0);
        } elseif ((float) $frais_billets_montant->getData('amount') !== (float) $amounts['total_frais_materiel_ht']) {
            $frais_billets_montant->set('amount', (float) $amounts['total_frais_materiel_ht']);
            $frais_billets_montant->update();
        }
    }

    // Rendus HTML:

    public function renderMontantsRecap($montant_type = 0, $coprods_parts_mode = 'ht', $coprods_paiements_mode = 'ht')
    {
        $html = '';

        $totaux = $this->getMontantsRecap($montant_type);

//        $html .= '<pre>';
//        $html .= print_r($totaux, 1);
//        $html .= '</pre>';
//        return $html;

        $show_coprods = (int) $this->showCoprods();
        $colspan = 4;
        if ($show_coprods) {
            $coprods = $this->getCoProds(true);
            $coprods[0] = 'Le Fil';

            $colspan += (2 * count($coprods));
        }

        $html .= '<table class="bimp_list_table" style="width: auto!important;">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Montant</th>';
        $html .= '<th style="text-align: center;">Montant HT</th>';
//        $html .= '<th style="text-align: center;">Montant TVA</th>';
//        $html .= '<th style="text-align: center;">Montant TTC</th>';

        if ($show_coprods) {
            foreach ($coprods as $id_coprod => $cp_label) {
                $html .= '<th style="text-align: center;">Part ' . $cp_label . ' ' . ($coprods_parts_mode === 'ttc' ? 'TTC' : 'HT') . '</th>';
            }
            foreach ($coprods as $id_coprod => $cp_label) {
                $html .= '<th style="text-align: center;">';
                switch ($montant_type) {
                    case 0: $html .= 'Paiement/reçu';
                        break;
                    case 1: $html .= 'Paiement';
                        break;
                    case 2: $html .= 'Reçu';
                        break;
                }
                $html .= ' ' . $cp_label . ' ' . ($coprods_paiements_mode === 'ttc' ? 'TTC' : 'HT') . '</th>';
            }
        }

        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        foreach ($totaux['categories'] as $id_cat => $tot_cat) {
            if ((float) $tot_cat['total_ht'] == 0) {
                continue;
            }

            $categ = BimpCache::getBimpObjectInstance($this->module, 'BMP_CategorieMontant', (int) $id_cat);

            if ($categ->isLoaded()) {

                $td_bk_col = BimpTools::changeColorLuminosity('#' . $categ->getData('color'), 80);
                $td_bk_col = BimpTools::setColorSL('#' . $categ->getData('color'), null, 0.9);

                $html .= '<tr>';
                $html .= '<td style="padding: 15px 0 0 0" colspan="' . $colspan . '">';
                $html .= '<div style="font-weight:bold;color: #fff; background-color: #' . $categ->getData('color') . '!important;padding: 5px;">';
                $html .= $categ->getData('name');
                $html .= '</div>';
                $html .= '</td>';
                $html .= '</tr>';

                // Montants: 
                foreach ($tot_cat['montants'] as $id_type_montant => $tot_montants) {
                    if ((float) $tot_montants['total_ht'] == 0) {
                        continue;
                    }

                    $html .= '<tr>';
                    $tm = BimpCache::getBimpObjectInstance($this->module, 'BMP_TypeMontant', (int) $id_type_montant);

                    if ($tm->isLoaded()) {
                        $html .= '<td style="font-weight: bold; color: #' . $categ->getData('color') . ';text-align:right;">' . $tm->getData('name') . '</td>';

                        $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($tot_montants['total_ht'], '', true) . '</td>';
//                        $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($tot_montants['total_tva'], '', true) . '</td>';
//                        $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($tot_montants['total_ttc'], '', true) . '</td>';

                        if ($show_coprods) {
                            $fl = 1;
                            // Parts Coprods: 
                            foreach ($coprods as $id_coprod => $cp_label) {
                                $html .= '<td style="text-align: center;' . ($fl ? ' border-left: 1px solid #505050;' : '') . '">';
                                $html .= BimpTools::displayMoneyValue($tot_montants['coprods'][(int) $id_coprod][($coprods_parts_mode === 'ttc' ? 'total_part_ttc' : 'total_part_ht')], '', true);
                                $html .= '</td>';
                                $fl = 0;
                            }
                            $fl = 1;
                            // Paiements Coprods: 
                            foreach ($coprods as $id_coprod => $cp_label) {
                                $html .= '<td style="text-align: center;' . ($fl ? ' border-left: 1px solid #505050;' : '') . '">';
                                $html .= BimpTools::displayMoneyValue($tot_montants['coprods'][(int) $id_coprod][($coprods_paiements_mode === 'ttc' ? 'total_paid_ttc' : 'total_paid_ht')], '', true);
                                $html .= '</td>';
                                $fl = 0;
                            }
                        }
                    } else {
                        $html .= '<td colspan="' . $colspan . '">' . BimpRender::renderAlerts('Le type de montant d\'ID ' . $id_type_montant . ' n\'existe pas') . '</td>';
                    }

                    $html .= '</tr>';
                }


                $html .= '<tr>';

                // Total catégorie
                $html .= '<td style="font-weight: bold;background-color: ' . $td_bk_col . '!important; color: #' . $categ->getData('color') . ';text-align:right;">Total</td>';
                $html .= '<td style="text-align: center;background-color: ' . $td_bk_col . '!important;">' . BimpTools::displayMoneyValue($tot_cat['total_ht'], '', true) . (isset($tot_cat['total_ht_percent']) ? ' (' . BimpTools::displayFloatValue($tot_cat['total_ht_percent']) . '%)' : '') . '</td>';
//                $html .= '<td style="text-align: center;background-color: ' . $td_bk_col . '!important;">' . BimpTools::displayMoneyValue($tot_cat['total_tva'], '', true) . '</td>';
//                $html .= '<td style="text-align: center;background-color: ' . $td_bk_col . '!important;">' . BimpTools::displayMoneyValue($tot_cat['total_ttc'], '', true) . '</td>';

                if ($show_coprods) {
                    $fl = 1;
                    // Parts Coprods: 
                    foreach ($coprods as $id_coprod => $cp_label) {
                        $html .= '<td style="text-align: center;background-color: ' . $td_bk_col . '!important;' . ($fl ? 'border-left: 1px solid #505050;' : '') . '">';
                        $html .= BimpTools::displayMoneyValue($tot_cat['coprods'][(int) $id_coprod][($coprods_parts_mode === 'ttc' ? 'total_part_ttc' : 'total_part_ht')], '', true);
                        $html .= '</td>';
                        $fl = 0;
                    }
                    $fl = 1;
                    // Paiements Coprods: 
                    foreach ($coprods as $id_coprod => $cp_label) {
                        $html .= '<td style="text-align: center;background-color: ' . $td_bk_col . '!important;' . ($fl ? 'border-left: 1px solid #505050;' : '') . '">';
                        $html .= BimpTools::displayMoneyValue($tot_cat['coprods'][(int) $id_coprod][($coprods_paiements_mode === 'ttc' ? 'total_paid_ttc' : 'total_paid_ht')], '', true);
                        $html .= '</td>';
                        $fl = 0;
                    }
                }

                $html .= '</tr>';
            } else {
                $html .= '<tr>';
                $html .= '<td colspan="' . $colspan . '">' . BimpRender::renderAlerts('La catégorie d\'ID ' . $id_cat . ' n\'existe pas') . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '<tr>';
        $html .= '<td colspan="' . $colspan . '" style="height: 25px">';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</tbody>';

        // Totaux généraux: 
        $html .= '<tfoot>';
        $html .= '<tr style="border: 2px solid #505050; font-size: 14px">';
        $html .= '<td style="text-align: right">Total</td>';
        $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($totaux['total_ht'], '', true) . '</td>';
//        $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($totaux['total_tva'], '', true) . '</td>';
//        $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($totaux['total_ttc'], '', true) . '</td>';

        if ($show_coprods) {
            $fl = 1;
            // Parts Coprods: 
            foreach ($coprods as $id_coprod => $cp_label) {
                $html .= '<td style="text-align: center;' . ($fl ? ' border-left: 1px solid #505050;' : '') . '">';
                $html .= BimpTools::displayMoneyValue($totaux['coprods'][(int) $id_coprod][($coprods_parts_mode === 'ttc' ? 'total_part_ttc' : 'total_part_ht')], '', true);
                $html .= '</td>';
                $fl = 0;
            }
            $fl = 1;
            // Paiements Coprods: 
            foreach ($coprods as $id_coprod => $cp_label) {
                $html .= '<td style="text-align: center;' . ($fl ? ' border-left: 1px solid #505050;' : '') . '">';
                $html .= BimpTools::displayMoneyValue($totaux['coprods'][(int) $id_coprod][($coprods_paiements_mode === 'ttc' ? 'total_paid_ttc' : 'total_paid_ht')], '', true);
                $html .= '</td>';
                $fl = 0;
            }
        }

        $html .= '</tr>';
        $html .= '</tfoot>';

        $html .= '</table>';

        return $html;
    }

    public function renderMontantsTotaux()
    {
        $coprods = $this->getCoProds();

        $colspan = 3;
        if (count($coprods)) {
            $colspan += 1 + count($coprods);
        }

        $status = array(
            array('code' => null, 'title' => 'généraux', 'id' => 'generals', 'tab' => 'Généraux'),
            array('code' => 2, 'title' => 'des montants confirmés', 'id' => 'confirmed', 'tab' => 'Confirmés'),
            array('code' => 1, 'title' => 'des montants à confirmer', 'id' => 'to_confirm', 'tab' => 'A confirmer'),
            array('code' => 3, 'title' => 'des montants optionnels', 'id' => 'optionals', 'tab' => 'Optionnels')
        );

        $tabs = array();

        $all_amounts = $this->getTotalAmounts(true);

        foreach ($status as $s) {
            if (!is_null($s['code'])) {
                if (!isset($all_amounts['status'][$s['code']])) {
                    continue;
                }

                $amounts = $all_amounts['status'][$s['code']];
            } else {
                $amounts = $all_amounts;
            }

            $tab = array(
                'title'   => $s['tab'],
                'id'      => $s['id'],
                'content' => ''
            );

            $html = '';

            $html .= '<div class="row">';

            // Totaux généraux: 
            $html .= '<div class="col-sm-12 col-md-6 col-lg-6">';
            $html .= '<div class="objectFieldsTableContainer">';

            $html .= '<table class="objectFieldsTable foldable open center-align">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th colspan="4">Totaux ' . $s['title'] . '</th>';
            $html .= '</tr>';
            $html .= '<tr class="col_headers">';
            $html .= '<th></th>';
            $html .= '<th>HT</th>';
            $html .= '<th>TVA</th>';
            $html .= '<th>TTC</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            $html .= '<tr>';
            $html .= '<th>Total recettes</th>';
            $html .= '<td>' . BimpTools::displayMoneyValue($amounts['total_recettes_ht'], 'EUR', true) . '</td>';
            $html .= '<td>' . BimpTools::displayMoneyValue($amounts['total_recettes_ttc'] - $amounts['total_recettes_ht'], 'EUR', true) . '</td>';
            $html .= '<td>' . BimpTools::displayMoneyValue($amounts['total_recettes_ttc'], 'EUR', true) . '</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<th>Total charges</th>';
            $html .= '<td>' . BimpTools::displayMoneyValue($amounts['total_frais_ht'] * -1, 'EUR', true) . '</td>';
            $html .= '<td>' . BimpTools::displayMoneyValue(($amounts['total_frais_ttc'] * -1) - ($amounts['total_frais_ht'] * -1), 'EUR', true) . '</td>';
            $html .= '<td>' . BimpTools::displayMoneyValue($amounts['total_frais_ttc'] * -1, 'EUR', true) . '</td>';
            $html .= '</tr>';

            $html .= '<tr class="strong">';
            $html .= '<th>Solde</th>';
            $html .= '<td>' . BimpTools::displayMoneyValue($amounts['solde_ht'], 'EUR', true) . '</td>';
            $html .= '<td>' . BimpTools::displayMoneyValue($amounts['solde_ttc'] - $amounts['solde_ht'], 'EUR', true) . '</td>';
            $html .= '<td>' . BimpTools::displayMoneyValue($amounts['solde_ttc'], 'EUR', true) . '</td>';
            $html .= '</tr>';

            $rest_ht = $amounts['solde_ht'];
            $rest_ttc = $amounts['solde_ttc'];

            if (count($coprods)) {
                foreach ($coprods as $id_cp => $cp_name) {
                    $html .= '<tr class="title">';
                    $html .= '<td colspan="4">' . $cp_name . '</td>';
                    $html .= '</tr>';

                    $html .= '<tr>';
                    $html .= '<th>Part du solde</th>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($amounts['coprod_' . $id_cp]['solde_ht'], 'EUR', true) . '</td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($amounts['coprod_' . $id_cp]['solde_ttc'] - $amounts['coprod_' . $id_cp]['solde_ht'], 'EUR', true) . '</td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($amounts['coprod_' . $id_cp]['solde_ttc'], 'EUR', true) . '</td>';
                    $html .= '</tr>';

                    if ((float) $amounts['coprod_' . $id_cp]['total_paid_ttc']) {
                        $html .= '<tr>';
                        $html .= '<th>Total paiements</th>';
                        $html .= '<td>' . BimpTools::displayMoneyValue($amounts['coprod_' . $id_cp]['total_paid_ht'], 'EUR', true) . '</td>';
                        $html .= '<td>' . BimpTools::displayMoneyValue($amounts['coprod_' . $id_cp]['total_paid_ttc'] - $amounts['coprod_' . $id_cp]['total_paid_ht'], 'EUR', true) . '</td>';
                        $html .= '<td>' . BimpTools::displayMoneyValue($amounts['coprod_' . $id_cp]['total_paid_ttc'], 'EUR', true) . '</td>';
                        $html .= '</tr>';
                    }

                    if ((float) $amounts['coprod_' . $id_cp]['total_received_ttc']) {
                        $html .= '<tr>';
                        $html .= '<th>Total reçus</th>';
                        $html .= '<td>' . BimpTools::displayMoneyValue($amounts['coprod_' . $id_cp]['total_received_ht'] * -1, 'EUR', true) . '</td>';
                        $html .= '<td>' . BimpTools::displayMoneyValue(($amounts['coprod_' . $id_cp]['total_received_ttc'] - $amounts['coprod_' . $id_cp]['total_received_ht']) * -1, 'EUR', true) . '</td>';
                        $html .= '<td>' . BimpTools::displayMoneyValue($amounts['coprod_' . $id_cp]['total_received_ttc'] * -1, 'EUR', true) . '</td>';
                        $html .= '</tr>';
                    }

                    $solde_ht = $amounts['coprod_' . $id_cp]['solde_ht'] + $amounts['coprod_' . $id_cp]['total_paid_ht'] - $amounts['coprod_' . $id_cp]['total_received_ht'];
                    $solde_ttc = $amounts['coprod_' . $id_cp]['solde_ttc'] + $amounts['coprod_' . $id_cp]['total_received_ht'] - $amounts['coprod_' . $id_cp]['total_received_ttc'];

                    $rest_ht -= $amounts['coprod_' . $id_cp]['solde_ht'];
                    $rest_ttc -= $amounts['coprod_' . $id_cp]['solde_ttc'];

                    $html .= '<tr class="strong">';
                    $html .= '<th>Solde ' . $cp_name . '</th>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($solde_ht, 'EUR', true) . '</td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($solde_ttc - $solde_ht, 'EUR', true) . '</td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($solde_ttc, 'EUR', true) . '</td>';
                    $html .= '</tr>';
                }

                $html .= '<tr>';
                $html .= '<td colspan="4" style="height: 5px;"></td>';
                $html .= '</tr>';

                $html .= '<tr>';
                $html .= '<td style="text-align: right;font-weight: bold; background-color: #DCDCDC; font-size: 14px; color: #282828;">Résultat le Fil</td>';
                $html .= '<td style="font-weight: bold; background-color: #DCDCDC; font-size: 14px; color: #282828;">' . BimpTools::displayMoneyValue($rest_ht, 'EUR', true) . '</td>';
                $html .= '<td style="font-weight: bold; background-color: #DCDCDC; font-size: 14px; color: #282828;">' . BimpTools::displayMoneyValue($rest_ttc - $rest_ht, 'EUR', true) . '</td>';
                $html .= '<td style="font-weight: bold; background-color: #DCDCDC; font-size: 14px; color: #282828;">' . BimpTools::displayMoneyValue($rest_ttc, 'EUR', true) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';

            $html .= '</div>';
            $html .= '</div>';

            if (count($amounts['categories'])) {
                // Charges par catégories: 
                $html .= '<div class="col-sm-12 col-md-6 col-lg-6">';
                $html .= '<div class="objectFieldsTableContainer">';
                $html .= '<table class="objectFieldsTable">';

                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th colspan="4">Charges par catégories</th>';
                $html .= '</tr>';
                $html .= '<tr class="col_headers">';
                $html .= '<th>Catégorie</th>';
                $html .= '<th style="text-align: center;">HT</th>';
                $html .= '<th style="text-align: center;">TVA</th>';
                $html .= '<th style="text-align: center;">TTC</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';

                $total_frais_ht = 0;

                foreach ($amounts['categories'] as $id_category => $cat_amounts) {
                    if (in_array($id_category, array(self::$id_taxes_category, self::$id_bar_category))) {
                        continue;
                    }
                    if (!(float) $cat_amounts['total_frais_ht']) {
                        continue;
                    }
                    $category = BimpCache::getBimpObjectInstance($this->module, 'BMP_CategorieMontant', (int) $id_category);
                    if ($category->isLoaded()) {
                        $cat_name = $category->getData('name');
                        $cat_color = $category->getData('color');
                    } else {
                        $cat_name = 'Catégorie ' . $id_category;
                        $cat_color = '4b4b4b';
                    }

                    $total_frais_ht += $cat_amounts['total_frais_ht'];
                    $total_frais_ttc += $cat_amounts['total_frais_ttc'];

                    $html .= '<tr style="font-weight: bold; color: #' . $cat_color . ';">';
                    $html .= '<td>' . $cat_name . '</td>';
                    $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($cat_amounts['total_frais_ht'], 'EUR') . '</td>';
                    $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($cat_amounts['total_frais_ttc'] - $cat_amounts['total_frais_ht'], 'EUR') . '</td>';
                    $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($cat_amounts['total_frais_ttc'], 'EUR') . '</td>';
                    $html .= '</tr>';
                }

                $html .= '<tr style="font-weight: bold;">';
                $html .= '<td style="background-color: #EE7D00; color: #fff; text-align: right">Total</td>';
                $html .= '<td style="background-color: #DCDCDC;text-align: center;">' . BimpTools::displayMoneyValue($total_frais_ht, 'EUR') . '</td>';
                $html .= '<td style="background-color: #DCDCDC;text-align: center;">' . BimpTools::displayMoneyValue($total_frais_ttc - $total_frais_ht, 'EUR') . '</td>';
                $html .= '<td style="background-color: #DCDCDC;text-align: center;">' . BimpTools::displayMoneyValue($total_frais_ttc, 'EUR') . '</td>';

                $html .= '</tr>';

                $html .= '</tbody>';

                $html .= '</table>';
                $html .= '</div>';
                $html .= '</div>';
            }

            $html .= '</div>';

            $html .= '<div class="row">';

            if (count($amounts['categories'])) {
                // Détail recettes: 
                $html .= '<div class="col-sm-12 col-md-6 col-lg-6">';
                $html .= '<div class="objectFieldsTableContainer">';
                $html .= '<table class="objectFieldsTable">';

                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th colspan="2">';
                $html .= 'Recette Nette';
                $html .= '</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';

                $category = BimpObject::getInstance('bimpmargeprod', 'BMP_CategorieMontant');

                $sacem_billets_rate = (float) $this->getCalcMontantRate(self::$id_calc_sacem_billets);
                $sacem_secu_rate = (float) $this->getCalcMontantRate(self::$id_calc_sacem_secu);
                $cnv_billets_rate = (float) $this->getCalcMontantRate(self::$id_calc_cnv_billets);

                $billets_ht_brut = round((float) $this->getMontantAmount((int) $this->getBilletsIdTypeMontant(), null, $s['code']), 2);
                $billets_ht_net = $billets_ht_brut;

                $html .= '<tr>';
                $html .= '<td>Billetterie HT BRUT</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($billets_ht_brut, 'EUR', true) . '</td>';
                $html .= '</tr>';

                $sacem_billets = $billets_ht_brut * ($sacem_billets_rate / 100);
                $sacem_groupe = (float) $this->getMontantAmount((int) self::$id_sacem_groupe, null);
                $billets_ht_net -= $sacem_billets;
                $billets_ht_net -= $sacem_groupe;

                $html .= '<tr>';
                $html .= '<td>SACEM Billetterie</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($sacem_billets * -1, 'EUR', true) . '</td>';
                $html .= '</tr>';

                if ($sacem_groupe != 0) {
                    $html .= '<tr>';
                    $html .= '<td>SACEM artistique</td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($sacem_groupe * -1, 'EUR', true) . '</td>';
                    $html .= '</tr>';
                }

                $sacem_secu = $sacem_billets * ($sacem_secu_rate / 100);
                $sacem_secu += $sacem_groupe * ($sacem_secu_rate / 100);

                $billets_ht_net -= $sacem_secu;

                $html .= '<tr>';
                $html .= '<td>Sécu. sociale s/ SACEM</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($sacem_secu * -1, 'EUR', true) . '</td>';
                $html .= '</tr>';

                $sacem_bar = (float) $this->getMontantAmount(self::$id_sacem_bar_montant, null);
                $billets_ht_net -= $sacem_bar;

                $html .= '<tr>';
                $html .= '<td>SACEM Bar</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($sacem_bar * -1, 'EUR', true) . '</td>';
                $html .= '</tr>';

                $cnv_billets = round($billets_ht_brut * ($cnv_billets_rate / 100), 2);
                $billets_ht_net -= $cnv_billets;

                $html .= '<tr>';
                $html .= '<td>CNV</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($cnv_billets * -1, 'EUR', true) . '</td>';
                $html .= '</tr>';

                $html .= '<tr class="strong">';
                $html .= '<td>Billetterie HT NET</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($billets_ht_net, 'EUR', true) . '</td>';
                $html .= '</tr>';

                $html .= '<tr style="font-weight: bold">';
                $html .= '<td>Ratio invitations / total billets</td>';
                $html .= '<td>' . round($this->GetFreeBilletsRatio() * 100, 2) . ' %</td>';
                $html .= '</tr>';

                $html .= '<tr><td></td><td></td></tr>';

                $bar_ht_brut = 0;
                $bar_ht_brut += (float) $this->getMontantAmount((int) self::$id_bar55_type_montant, null, $s['code']);
                $bar_ht_brut += (float) $this->getMontantAmount((int) self::$id_bar20_type_montant, null, $s['code']);

                $frais_bar = (float) $this->getMontantAmount((int) self::$id_achats_bar_montant, null, $s['code']);

                $bar_ht_net = $bar_ht_brut - $frais_bar;

                $html .= '<tr>';
                $html .= '<td>Recettes bar HT BRUT</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($bar_ht_brut, 'EUR', true) . '</td>';
                $html .= '</tr>';

                $html .= '<tr>';
                $html .= '<td>Approvisionnement bar</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($frais_bar * -1, 'EUR', true) . '</td>';
                $html .= '</tr>';

                $html .= '<tr class="strong">';
                $html .= '<td>Recettes bar HT NET</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($bar_ht_net, 'EUR', true) . '</td>';
                $html .= '</tr>';

                $html .= '<tr><td></td><td></td></tr>';

                $total_autre_brut = 0;

                foreach ($amounts['categories'] as $id_cat => $cat_amounts) {
                    if (in_array($id_cat, array(
                                self::$id_bar_category,
                                self::$id_billets_category
                            ))) {
                        continue;
                    }

                    $total_autre_brut += $cat_amounts['total_recettes_ht'];
                }

//                $sacem_autre += (float) $this->getMontantAmount((int) self::$id_sacem_groupe, null);
//                if ($sacem_autre > 0)
//                    $sacem_autre += $sacem_autre * ($sacem_secu_rate / 100);
//                $total_autre_net = $total_autre_brut - $sacem_autre;

                $html .= '<tr>';
                $html .= '<td>Autres recettes HT</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_autre_brut, 'EUR', true) . '</td>';
                $html .= '</tr>';

                $total_autre_net = $total_autre_brut;

//                $html .= '<tr>';
//                $html .= '<td>SACEM autres recettes</td>';
//                $html .= '<td>' . BimpTools::displayMoneyValue($sacem_autre * -1, 'EUR', true) . '</td>';
//                $html .= '</tr>';
//
//                $html .= '<tr class="strong">';
//                $html .= '<td>Autre recettes NET</td>';
//                $html .= '<td>' . BimpTools::displayMoneyValue($total_autre_net, 'EUR', true) . '</td>';
//                $html .= '</tr>';

                $tatal_recettes = $billets_ht_net + $bar_ht_net + $total_autre_net;

                $html .= '<tr style="font-weight: bold">';
                $html .= '<td style="background-color: #EE7D00; color: #fff; text-align: right">Total</td>';
                $html .= '<td style="background-color: #DCDCDC">' . BimpTools::displayMoneyValue($tatal_recettes, 'EUR') . '</td>';
                $html .= '</tr>';

                $html .= '</tbody>';

                $html .= '</table>';
                $html .= '</div>';
                $html .= '</div>';
            }

            // Calculs break: 
            $html .= '<div class="col-sm-12 col-md-6 col-lg-6">';
            $html .= $this->renderPrevisionnelBreaks();
            $html .= '</div>';

            $html .= '</div>';

            $tab['content'] = $html;
            $tabs[] = $tab;
        }

        if (count($tabs)) {
            return BimpRender::renderNavTabs($tabs, 'totaux_by_status');
        }
        return BimpRender::renderAlerts('Aucun montant à calculer', 'warning');
    }

    public function renderSoldes()
    {
        $soldes = $this->getSoldes();

        $html = '';
        $html .= '<div class="row">';
        $html .= '<div class="col-sm-10 col-md-8 col-lg-6">';
        $html .= '<div class="objectFieldsTableContainer">';

        $html .= '<table class="objectFieldsTable foldable open">';

        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th colspan="4">';
        $html .= 'Solde';
        $html .= '<span class="foldable-caret"></span>';
        $html .= '</th>';
        $html .= '</tr>';

        $html .= '<tr class="col_headers">';
        $html .= '<th>Solde</th>';
        $html .= '<th>Charges</th>';
        $html .= '<th>Recettes</th>';

        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $html .= '<tr>';
        $html .= '<td>' . BimpTools::displayMoneyValue($soldes['solde'], 'EUR') . '</td>';
        $html .= '<td>' . BimpTools::displayMoneyValue($soldes['frais'], 'EUR') . '</td>';
        $html .= '<td>' . BimpTools::displayMoneyValue($soldes['recettes'], 'EUR') . '</td>';
        $html .= '</tr>';

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderFraisHotel()
    {
        $tabs = array();
        $montants = array(
            array('id_type_montant' => 8, 'title' => 'Frais d\'hôtel', 'label' => 'Hôtel', 'id' => 'frais_hotel'),
            array('id_type_montant' => 9, 'title' => 'Catering Le Fil', 'label' => 'Catering Le Fil', 'id' => 'catering'),
            array('id_type_montant' => 10, 'title' => 'Repas extérieurs', 'label' => 'Repas extérieurs', 'id' => 'repas_ext'),
        );

        foreach ($montants as $m) {
            $html = '';
            $eventMontant = BimpCache::findBimpObjectInstance($this->module, 'BMP_EventMontant', array(
                        'id_event'   => (int) $this->id,
                        'id_montant' => $m['id_type_montant'],
                        'id_coprod'  => 0
            ));
            if (BimpObject::objectLoaded($eventMontant)) {
                $html = $eventMontant->renderChildrenList('details', 'default', true, $m['title'], 'far_file-alt');
            } else {
                $html .= BimpRender::renderAlerts($m['title'] . ': montant correspondant non trouvé');
            }
            $tabs[] = array(
                'id'      => $m['id'],
                'title'   => $m['label'],
                'content' => $html
            );
        }

        return BimpRender::renderNavTabs($tabs);
    }

    public function renderPrevisionnelBreaks()
    {
        $html = '';

        $tarifs = $this->getChildrenObjects('tarifs');

        $nTarifs = count($tarifs);

        $total_billets_ttc = 0;
        $nbBillets = 0;

        $prix_moyen_ttc = 0;
        $prix_moyen_ht = 0;
        $prix_moyen_hors_loc_ht = 0;
        $prix_moyen_net = 0;

        $ca_bar_moyen_ttc = 0;
        $ca_bar_moyen_ht = 0;
        $ca_bar_moyen_net = 0;

        $ca_moyen_ttc = 0;
        $ca_moyen_net = 0;

        $break_billets = 0;
        $break_total = 0;

        $event_billets_instance = BimpObject::getInstance($this->module, 'BMP_EventBillets');

        $debug = (int) BimpDebug::isActive('debug');
        $status = (int) $this->getData('status');

        if ($debug) {
            $html .= '<h3>Détails calculs: </h3>';
        }

        $nb_billets_payants = 0;
        $nb_billets_gratuits = 0;

        if ($nTarifs > 0) {
            $id_billets_type_montant = (int) $this->getBilletsIdTypeMontant();
            $billets_tva_tx = (float) $this->getMontantTvaTx($id_billets_type_montant);

            foreach ($tarifs as $tarif) {
                $prix_ttc = (float) $tarif->getData('amount');
                $qty = 0;
                if ($status > 1) {
                    $qty = BimpStats::getTotal($event_billets_instance, 'quantity', array(
                                'id_event' => (int) $this->id,
                                'id_tarif' => (int) $tarif->id
                    ));
                } else {
                    $qty = (float) $tarif->getData('previsionnel');
                }

                if ($qty) {
                    if ($prix_ttc) {
                        $total_billets_ttc += $prix_ttc * $qty;
                        $nb_billets_payants += $qty;
                    } else {
                        $nb_billets_gratuits += $qty;
                    }
                    $nbBillets += $qty;
                }
            }

            $total_billets_ht = BimpTools::calculatePriceTaxEx($total_billets_ttc, $billets_tva_tx);

            if ($nbBillets) {
                $prix_moyen_ttc = $total_billets_ttc / $nb_billets_payants;
                $prix_moyen_ht = $total_billets_ht / $nb_billets_payants;
            }

            if ($debug) {
                $html .= '<h4>Bar: </h4>';
            }

            if ($status > 1) {
                $montant_bar_20 = $this->getMontant(self::$id_bar20_type_montant);
                if (BimpObject::objectLoaded($montant_bar_20)) {
                    $ca_bar_moyen_ht += (float) $montant_bar_20->getData('amount');
                    $ca_bar_moyen_ttc += BimpTools::calculatePriceTaxIn((float) $montant_bar_20->getData('amount'), $montant_bar_20->getTvaTx());
                }

                $montant_bar_55 = $this->getMontant(self::$id_bar55_type_montant);
                if (BimpObject::objectLoaded($montant_bar_55)) {
                    $ca_bar_moyen_ht += (float) $montant_bar_55->getData('amount');
                    $ca_bar_moyen_ttc += BimpTools::calculatePriceTaxIn((float) $montant_bar_55->getData('amount'), $montant_bar_55->getTvaTx());
                }

                if ($nbBillets) {
                    if ($ca_bar_moyen_ht) {
                        $ca_bar_moyen_ht /= $nbBillets;
                    }
                    if ($ca_bar_moyen_ttc) {
                        $ca_bar_moyen_ttc /= $nbBillets;
                    }
                }
            } else {
                $montant_bar_20 = $this->getMontant(self::$id_bar20_type_montant);
                $ca_bar_moyen_ttc = (float) $this->getData('ca_moyen_bar');
                if (BimpObject::objectLoaded($montant_bar_20)) {
                    $ca_bar_moyen_ht = BimpTools::calculatePriceTaxEx($ca_bar_moyen_ttc, (float) $montant_bar_20->getTvaTx());
                } else {
                    $ca_bar_moyen_ht = $ca_bar_moyen_ttc;
                }
            }

            $ca_bar_moyen_net = $ca_bar_moyen_ht;

            if ($debug) {
                $html .= 'CA Bar moyen ttc: ' . $ca_bar_moyen_ttc . '<br/>';
                $html .= 'CA Bar moyen ht: ' . $ca_bar_moyen_ht . '<br/>';
            }

            $rate = (float) $this->getCalcMontantRate(self::$id_calc_frais_bar);
            $frais_bar = ($ca_bar_moyen_ht * ($rate / 100));
            if ($debug) {
                $html .= 'Charges Bar Moyen: ' . $frais_bar . ' (taux: ' . $rate . ')<br/>';
            }

            $rate = (float) $this->getCalcMontantRate(self::$id_calc_sacem_bar);
            $sacem_bar = $ca_bar_moyen_ht * ($rate / 100);
            if ($debug) {
                $html .= 'SACEM Bar Moyen: ' . $sacem_bar . ' (taux: ' . $rate . ')<br/>';
            }

//            $rate = (float) $this->getCalcMontantRate(self::$id_calc_sacem_secu);
//            $sacem_bar_secu = ($sacem_bar * ($rate / 100));
//            if ($debug) {
//                $html .= 'SACEM bar sécu: ' . $sacem_bar_secu . ' (taux: ' . $rate . ')<br/>';
//            }

            $ca_bar_moyen_net = $ca_bar_moyen_ht - $frais_bar - $sacem_bar;
            if ($debug) {
                $html .= '<strong>CA Bar moyen net HT: ' . $ca_bar_moyen_net . '</strong><br/>';
            }
        }

        if ($prix_moyen_ht) {
            if ($debug) {
                $html .= '<h4>Billets: </h4>';
            }

            $sacem_billets = 0;
            $sacem_secu = 0;
            $cnv = 0;

            if ($debug) {
                $html .= 'Prix billet moyen HT: ' . $prix_moyen_ht . '<br/>';
            }

            $rate = (float) $this->getCalcMontantRate(self::$id_calc_sacem_billets);
            $sacem_billets = ($prix_moyen_ht * ($rate / 100));
            if ($debug) {
                $html .= 'SACEM billets: ' . $sacem_billets . ' (taux: ' . $rate . ')<br/>';
            }

            $rate = (float) $this->getCalcMontantRate(self::$id_calc_sacem_secu);
            $sacem_secu = ($sacem_billets * ($rate / 100));
            if ($debug) {
                $html .= 'SACEM sécu: ' . $sacem_secu . ' (taux: ' . $rate . ')<br/>';
            }

            $rate = (float) $this->getCalcMontantRate(self::$id_calc_cnv_billets);
            $cnv = ($prix_moyen_hors_loc_ht * ($rate / 100));
            if ($debug) {
                $html .= 'CNV: ' . $cnv . ' (taux: ' . $rate . ')<br/>';
            }

            $prix_moyen_net = $prix_moyen_ht - $sacem_billets - $sacem_secu - $cnv;

            if ($debug) {
                $html .= '<strong>CA billet moyen net: ' . $prix_moyen_net . '</strong><br/><br/>';
                $html .= '<h4>Charges (hors bar et taxes): </h4>';
            }

            $total_frais_hors_bar = 0;
            $list_frais = $this->getChildrenObjects('frais');

            $excludes = array(
                self::$id_cnv_montant,
                self::$id_sacem_bar_montant,
                self::$id_sacem_billets_montant,
                self::$id_sacem_secu_montant,
                self::$id_achats_bar_montant
            );

            foreach ($list_frais as $montant) {
                if (!in_array((int) $montant->getData('id_montant'), $excludes)) {
                    $tm = $montant->getChildObject('type_montant');
                    $total_frais_hors_bar += (float) $montant->getData('amount');
                    if ($debug) {
                        $html .= ' - ' . $tm->getData('name') . ': ' . $montant->getData('amount') . '<br/>';
                    }
                }
            }

            if ($debug) {
                $html .= 'Total charges: ' . $total_frais_hors_bar . '<br/><br/>';
                $html .= '<h4>Recettes (hors bar et vente de billets)</h4>';
            }

            $total_extra_recettes = 0;
            $list_recettes = $this->getChildrenObjects('recettes');
            foreach ($list_recettes as $montant) {
                if (!in_array((int) $montant->getData('id_category_montant'), array(14, 15))) {
                    $tm = $montant->getChildObject('type_montant');
                    $total_extra_recettes += (float) $montant->getData('amount');

                    if ($debug) {
                        $html .= ' - ' . $tm->getData('name') . ': ' . $montant->getData('amount') . '<br/>';
                    }
                }
            }

            $solde = $total_frais_hors_bar - $total_extra_recettes;

            if ($debug) {
                $html .= 'Total recettes: ' . $total_extra_recettes . '<br/><br/>';
                $html .= 'SOLDE: ' . $solde . '<br/><br/>';
            }

            $ca_moyen_net = $prix_moyen_net + $ca_bar_moyen_net;
            $ca_moyen_ttc = $prix_moyen_ttc + $ca_bar_moyen_ttc;
            $break_billets = $solde / $prix_moyen_net;
            $break_total = $solde / $ca_moyen_net;
        }

        $table_id = 'objectViewTable_' . rand(0, 999999);

        $html .= '<div class="objectFieldsTableContainer">';
        $html .= '<table class="' . $this->object_name . '_FieldsTable objectFieldsTable foldable open" id="' . $table_id . '">';

        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th colspan="2">' . BimpRender::renderIcon('fas_chart-pie', 'iconLeft') . 'Indicateurs' . ($status === 1 ? ' (Prévisionnel)' : '') . '</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $html .= '<tr>';
        $html .= '<th>Ventes billets TOTAL TTC</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($total_billets_ttc, 'EUR') . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Prix billet moyen TTC</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($prix_moyen_ttc, 'EUR') . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Prix billet moyen HT</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($prix_moyen_ht, 'EUR') . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Prix billet moyen net</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($prix_moyen_net, 'EUR') . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Break Billetterie</th>';
        $html .= '<td><span class="danger">' . round($break_billets, 2) . ' billets</span></td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Break toute recette</th>';
        $html .= '<td><span class="danger">' . round($break_total, 2) . ' billets</span></td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>CA Moyen TTC / billet</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($ca_moyen_ttc, 'EUR') . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>CA moyen net HT / billet</th>';
        $html .= '<td>' . BimpTools::displayMoneyValue($ca_moyen_net, 'EUR') . '</td>';
        $html .= '</tr>';

        if ($status === 1) {
            $prev_dl_dist = $nb_billets_payants * (float) $this->getData('default_dl_dist');
            $prev_dl_prod = $nb_billets_payants * (float) $this->getData('default_dl_prod');

            $html .= '<tr>';
            $html .= '<th>Total DL distributeurs prévisionnel</th>';
            $html .= '<td>' . BimpTools::displayMoneyValue($prev_dl_dist, 'EUR') . '</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<th>Total DL producteurs prévisionnel</th>';
            $html .= '<td>' . BimpTools::displayMoneyValue($prev_dl_prod, 'EUR') . '</td>';
            $html .= '</tr>';
        }

        $html .= '<tr>';
        $html .= '<th>Nombre de billets payants</th>';
        $html .= '<td>' . $nb_billets_payants . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Nombre d\'invitations</th>';
        $html .= '<td>' . $nb_billets_gratuits . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th>Ratio invitations / total billets</th>';
        $html .= '<td>' . round($this->GetFreeBilletsRatio() * 100, 2) . ' %</td>';
        $html .= '</tr>';

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        return $html;
    }

    public function renderTotalBillets()
    {
        if (!$this->isLoaded()) {
            return '';
        }

        $amounts = $this->getBilletsAmounts();

        $billets_tva_tx = $this->getMontantTvaTx($this->getBilletsIdTypeMontant());
        $has_coprods = (isset($amounts['coprods']) && count($amounts['coprods']) > 1);

        $html .= '<div class="objectFieldsTableContainer">';
        $html .= '<table class="objectFieldsTable foldable open">';

        $html .= '<thead>';
        $html .= '<tr class="col_headers">';
        if ($has_coprods) {
            $html .= '<th></th>';
        }
        $html .= '<th>Unités vendues</th>';
        $html .= '<th>Total HT hors DL</th>';
        $html .= '<th>Total DL Prod. HT</th>';
        $html .= '<th>Total HT</th>';
        $html .= '<th>TVA (' . BimpTools::displayFloatValue($billets_tva_tx) . '%)</th>';
        $html .= '<th>Total TTC</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        if ($has_coprods) {
            $coprods = $this->getCoProds(true);
            $coprods[0] = 'Le Fil';

            foreach ($coprods as $id_cp => $cp_name) {
                if (!isset($amounts['coprods'][(int) $id_cp])) {
                    continue;
                }

                $total_ht = $amounts['coprods'][(int) $id_cp]['total_billets_ht'] + $amounts['coprods'][(int) $id_cp]['total_dl_prod_ht'];
                $total_ttc = $amounts['coprods'][(int) $id_cp]['total_billets_ttc'] + $amounts['coprods'][(int) $id_cp]['total_dl_prod_ttc'];
                $total_tva = $total_ttc - $total_ht;

                $html .= '<tr>';
                $html .= '<th>' . $cp_name . '</th>';
                $html .= '<td>' . $amounts['coprods'][(int) $id_cp]['total_nb_billets'] . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($amounts['coprods'][(int) $id_cp]['total_billets_ht'], 'EUR') . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($amounts['coprods'][(int) $id_cp]['total_dl_prod_ht'], 'EUR') . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_ht, 'EUR') . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_tva, 'EUR') . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_ttc, 'EUR') . '</td>';
                $html .= '</tr>';
            }
        }

        $total_ht = $amounts['total_billets_ht'] + $amounts['total_dl_prod_ht'];
        $total_ttc = $amounts['total_billets_ttc'] + $amounts['total_dl_prod_ttc'];
        $total_tva = $total_ttc - $total_ht;

        $html .= '<tr' . ($has_coprods ? ' class="total_row"' : '') . '>';
        if ($has_coprods) {
            $html .= '<th>Total: </th>';
        }
        $html .= '<td>' . $amounts['total_nb_billets'] . '</td>';
        $html .= '<td>' . BimpTools::displayMoneyValue($amounts['total_billets_ht'], 'EUR') . '</td>';
        $html .= '<td>' . BimpTools::displayMoneyValue($amounts['total_dl_prod_ht'], 'EUR') . '</td>';
        $html .= '<td>' . BimpTools::displayMoneyValue($total_ht, 'EUR') . '</td>';
        $html .= '<td>' . BimpTools::displayMoneyValue($total_tva, 'EUR') . '</td>';
        $html .= '<td>' . BimpTools::displayMoneyValue($total_ttc, 'EUR') . '</td>';
        $html .= '</tr>';
        $html .= '</tbody>';

        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    public function renderBilanComptable($amounts, $with_status = true)
    {
        $html = '';

        $html .= '<div class="row">';
        $html .= '<div class="col-sm-12 col-md-10 col-lg-8">';
        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        if ($with_status) {
            $html .= '<th>Statut</th>';
        }
        $html .= '<th>Type montant</th>';
        $html .= '<th>Code comptable</th>';
        $html .= '<th>Tx TVA</th>';
        $html .= '<th>Charges HT</th>';
        $html .= '<th>Recettes HT</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        foreach ($amounts['categories'] as $id_cat => $cat) {
            $html .= '<tr>';
            $html .= '<td colspan="6" style="padding-top: 15px; border-bottom: 2px solid #' . $cat['color'] . '!important; color: #' . $cat['color'] . '; font-weight: bold">' . $cat['name'] . '</td>';
            $html .= '</tr>';

            foreach ($cat['rows'] as $r) {
                $style = 'font-weight: bold; color: #' . $cat['color'] . ';';
                $html .= '<tr>';
                if ($with_status) {
                    $html .= '<td>' . $r['status'] . '</td>';
                }
                $html .= '<td style="' . $style . '">' . $r['type_montant'] . '</td>';
                $html .= '<td style="' . $style . '">' . $r['code'] . '</td>';
                $html .= '<td style="' . $style . '">' . $r['tva'] . '</td>';
                $html .= '<td style="' . $style . '">' . $r['frais'] . '</td>';
                $html .= '<td style="' . $style . '">' . $r['recette'] . '</td>';
                $html .= '</tr>';
            }

            $td_bk_col = BimpTools::setColorSL('#' . $cat['color'], null, 0.9);

            $html .= '<tr>';
            $html .= '<td colspan="' . ($with_status ? '4' : '3') . '" ';
            $html .= 'style="font-weight: bold;background-color: ' . $td_bk_col . '!important; color: #' . $cat['color'] . ';text-align: right;padding-right: 20px;">';
            $html .= 'Total : </td>';
            $html .= '<td style="background-color: ' . $td_bk_col . '!important; color:  #' . $cat['color'] . ';font-weight: bold;">' . BimpTools::displayMoneyValue($cat['total_frais'], 'EUR') . '</td>';
            $html .= '<td style="background-color: ' . $td_bk_col . '!important; color:  #' . $cat['color'] . ';font-weight: bold;">' . BimpTools::displayMoneyValue($cat['total_recettes'], 'EUR') . '</td>';
            $html .= '</tr>';
        }

        $html .= '<tr>';
        $html .= '<td colspan=' . ($with_status ? '6' : '5') . '" style="height: 15px"></td>';
        $html .= '</tr>';
        $html .= '</tbody>';

        $html .= '<tfoot>';
        $html .= '<tr style="border: 2px solid #505050;border-bottom-width: 1px;font-weight: bold; font-size: 14px;">';
        $html .= '<td colspan="' . ($with_status ? '4' : '3') . '" style="text-align: right;padding-right: 20px;">Total : </td>';
        $html .= '<td>' . BimpTools::displayMoneyValue($amounts['total_frais'], 'EUR') . '</td>';
        $html .= '<td>' . BimpTools::displayMoneyValue($amounts['total_recettes'], 'EUR') . '</td>';
        $html .= '</tr>';

        if ($amounts['solde'] !== $amounts['solde_without_dl']) {
            $html .= '<tr style="border: 2px solid #505050;border-top-width: 1px;border-bottom-width: 1px;font-weight: bold; font-size: 14px">';
            $html .= '<td colspan="' . ($with_status ? '4' : '3') . '" style="text-align: right;padding-right: 20px;">Solde (hors DL): </td>';
            $html .= '<td colspan="2">' . BimpTools::displayMoneyValue($amounts['solde_without_dl'], 'EUR') . '</td>';
            $html .= '</tr>';
        }
        $html .= '<tr style="border: 2px solid #505050;border-top-width: 1px;font-weight: bold; font-size: 14px">';
        $html .= '<td colspan="' . ($with_status ? '4' : '3') . '" style="text-align: right;padding-right: 20px;">Solde final: </td>';
        $html .= '<td colspan="2">' . BimpTools::displayMoneyValue($amounts['solde'], 'EUR') . '</td>';
        $html .= '</tr>';
        $html .= '</tfoot>';

        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderEventBilansComptables()
    {
        if (!$this->isLoaded()) {
            return '';
        }

        $coprods = $this->getCoProds(true);

        $tabs = array();

        $coprods[0] = 'Le Fil';

        foreach ($coprods as $id_coprod => $cp_label) {
            $amounts = $this->getTotalComptable(array($this->id), $id_coprod, $cp_label);
            $html = $this->renderBilanComptable($amounts);

            $tabs[] = array(
                'id'      => 'coprod_' . $id_coprod,
                'title'   => 'Bilan Comptable ' . $cp_label,
                'content' => $html
            );
        }

        return BimpRender::renderNavTabs($tabs, 'bilans');
    }

    public function renderEventsBilanComptable($items)
    {
        $events = array();

        $html = '';
        $html .= '<h2>' . count($items) . ' événements pris en compte</h2>';

        foreach ($items as $item) {
            $events[] = (int) $item['id'];
        }

        $amounts = $this->getTotalComptable($events);
        $html .= $this->renderBilanComptable($amounts, false);

        $billets = array(
            'total'          => 0,
            'total_payants'  => 0,
            'total_gratuits' => 0,
            'total_loc'      => 0,
        );

        foreach ($events as $id_event) {
            $event = BimpCache::getBimpObjectInstance('bimpmargeprod', 'BMP_Event', $id_event);
            if (BimpObject::objectLoaded($event)) {
                $event_billets = $event->getBilletsNumbers();
                $billets['total'] += $event_billets['total'];
                $billets['total_payants'] += $event_billets['total_payants'];
                $billets['total_gratuits'] += $event_billets['total_gratuits'];
                $billets['total_loc'] += $event_billets['total_loc'];
            }
        }

        $html .= '<div class="row" style="margin-top: 30px">';
        $html .= '<div class="col-sm-12 col-md-10 col-lg-8">';
        $html .= '<table class="bimp_list_table" style="width: auto">';
        $html .= '<tbody>';
        $html .= '<tr>';
        $html .= '<th>Nb spectateurs payants</th>';
        $html .= '<td style="min-width: 120px; text-align: center;">' . $billets['total_payants'] . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th>Nb spectateurs gratuits</th>';
        $html .= '<td style="min-width: 120px; text-align: center;">' . $billets['total_gratuits'] . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th>Nb spectateurs locataires</th>';
        $html .= '<td style="min-width: 120px; text-align: center;">' . $billets['total_loc'] . '</td>';
        $html .= '</tr>';
        $html .= '<tr class="total_row">';
        $html .= '<th>Nb spectateurs total</th>';
        $html .= '<td style="min-width: 120px; text-align: center;">' . $billets['total'] . '</td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    public function renderSyntheseEvents($items)
    {
        if (count($items) > 1000) {
            return BimpRender::renderAlerts('Un trop grand nombre d\'événements (' . count($items) . ', max: 1000) ont été trouvés pour la période indiquée.<br/>', 'danger');
        }
        $maxEventsDisplayed = 30;

        if (!count($items)) {
            return BimpRender::renderAlerts('Aucun événement', 'warning');
        }

        $html = '';

        $events = array();
        $montants = array(
            'recettes' => array(
                'total'        => 0,
                'categories'   => array(),
                'events_total' => array(),
            ),
            'charges'  => array(
                'total'        => 0,
                'categories'   => array(),
                'events_total' => array(),
            )
        );

        $billets = array(
            'total'          => 0,
            'total_payants'  => 0,
            'total_gratuits' => 0,
            'total_loc'      => 0,
            'events'         => array()
        );

        foreach ($items as $item) {
            $event = BimpCache::getBimpObjectInstance('bimpmargeprod', 'BMP_Event', (int) $item['id']);

            if (BimpObject::objectLoaded($event)) {
                $amounts = $event->getMontantsRecap();
                $events[] = $event;

                foreach ($amounts['categories'] as $id_cat => $cat) {
                    if (!isset($montants['recettes']['categories'][$id_cat])) {
                        $montants['recettes']['categories'][$id_cat] = array(
                            'total'        => 0,
                            'montants'     => array(),
                            'events_total' => array()
                        );
                    }
                    if (!isset($montants['charges']['categories'][$id_cat])) {
                        $montants['charges']['categories'][$id_cat] = array(
                            'total'        => 0,
                            'montants'     => array(),
                            'events_total' => array()
                        );
                    }

                    foreach ($cat['montants'] as $id_montant => $montant) {
                        if (!isset($montants['recettes']['categories'][$id_cat]['montants'][$id_montant])) {
                            $montants['recettes']['categories'][$id_cat]['montants'][$id_montant] = array(
                                'total'  => 0,
                                'events' => array()
                            );
                        }
                        if (!isset($montants['charges']['categories'][$id_cat]['montants'][$id_montant])) {
                            $montants['charges']['categories'][$id_cat]['montants'][$id_montant] = array(
                                'total'  => 0,
                                'events' => array()
                            );
                        }

                        $amount = 0;

                        if (isset($montant['coprods'][0]['total_part_ht'])) {
                            $amount = (float) $montant['coprods'][0]['total_part_ht'];
                        } elseif (isset($montant['total_ht'])) {
                            $amount = (float) $montant['total_ht'];
                        }

                        if ($amount > 0) {
                            $montants['recettes']['total'] += $amount;
                            $montants['recettes']['events_total'][(int) $event->id] += $amount;
                            $montants['recettes']['categories'][$id_cat]['total'] += $amount;
                            $montants['recettes']['categories'][$id_cat]['events_total'][(int) $event->id] += $amount;
                            $montants['recettes']['categories'][$id_cat]['montants'][$id_montant]['total'] += $amount;
                            $montants['recettes']['categories'][$id_cat]['montants'][$id_montant]['events'][(int) $event->id] = $amount;
                        } elseif ($amount < 0) {
                            $amount *= -1;

                            $montants['charges']['total'] += $amount;
                            $montants['charges']['events_total'][(int) $event->id] += $amount;
                            $montants['charges']['categories'][$id_cat]['total'] += $amount;
                            $montants['charges']['categories'][$id_cat]['events_total'][(int) $event->id] += $amount;
                            $montants['charges']['categories'][$id_cat]['montants'][$id_montant]['total'] += $amount;
                            $montants['charges']['categories'][$id_cat]['montants'][$id_montant]['events'][(int) $event->id] = $amount;
                        }
                    }
                }

                $event_billets = $event->getBilletsNumbers();

                $billets['total'] += $event_billets['total'];
                $billets['total_payants'] += $event_billets['total_payants'];
                $billets['total_gratuits'] += $event_billets['total_gratuits'];
                $billets['total_loc'] += $event_billets['total_loc'];
                $billets['events'][(int) $event->id] = $event_billets;
            }
        }

        $html .= '<h2>' . count($events) . ' événements</h2>';

        $only_total = (count($events) > $maxEventsDisplayed);

        $colspan = 2;

        if ($only_total) {
            $html .= BimpRender::renderAlerts('Il y a plus de ' . $maxEventsDisplayed . ' événements.<br/>Seuls les montants totaux sont affichés', 'warning');
        } else {
            $colspan += count($events);
        }

        $html .= '<div class="h_auto_scroll" style="padding-bottom: 30px;max-width: 1290px;">';
        $html .= '<table class="bimp_list_table center-align">';
        $html .= '<thead>';
        if (!$only_total) {
            $html .= '<tr>';
            $html .= '<th style="min-width: 200px; max-width: 200px;"></th>';

            $html .= '<th style="min-width: 100px; max-width: 100px;">Total</th>';
            foreach ($events as $event) {
                $DT = new DateTime($event->getData('date'));
                $html .= '<th style="min-width: 100px; max-width: 100px;">' . $DT->format('d/m/Y') . '</th>';
            }
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<td style="min-width: 200px; max-width: 200px;font-weight: bold; text-align: right; padding-right: 20px;">Evénement</td>';
            $html .= '<td style="min-width: 100px; max-width: 100px;"></td>';
            foreach ($events as $event) {
                $html .= '<td style="min-width: 100px; max-width: 100px;">' . $event->displayData('name') . '</td>';
            }
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<td style="min-width: 200px; max-width: 200px;font-weight: bold; text-align: right; padding-right: 20px;">Type</td>';
            $html .= '<td style="min-width: 100px; max-width: 100px;"></td>';
            foreach ($events as $event) {
                $html .= '<td style="min-width: 100px; max-width: 100px;">' . $event->displayData('type') . '</td>';
            }
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<td style="min-width: 200px; max-width: 200px;font-weight: bold; text-align: right; padding-right: 20px;">Lieu</td>';
            $html .= '<td style="min-width: 100px; max-width: 100px;"></td>';
            foreach ($events as $event) {
                $html .= '<td style="min-width: 100px; max-width: 100px;">' . $event->displayData('place') . '</td>';
            }
            $html .= '</tr>';

            $html .= '</thead>';
        }
        $html .= '<tbody>';

        $html .= '<tr>';
        $html .= '<td colspan="' . $colspan . '">';

        $html .= '<div class="v_scroll" style="overflow-x: hidden; max-height: 600px;">';
        $html .= '<table class="bimp_list_table center-align">';
        $html .= '<tbody>';

        // Recettes: 
        $html .= '<tr>';
        $html .= '<td colspan="' . $colspan . '" style="border-width: 2px; border-color: #505050; text-align: left;font-weight: bold; font-size: 15px; padding: 8px; background-color: #F0F0F0!important">RECETTES</td>';
        $html .= '</tr>';
        foreach ($montants['recettes']['categories'] as $id_cat => $cat) {

            if ($cat['total'] === 0) {
                continue;
            }

            $category = BimpCache::getBimpObjectInstance('bimpmargeprod', 'BMP_CategorieMontant', (int) $id_cat);

            if (BimpObject::objectLoaded($category)) {
                $color = $category->getData('color');

                $html .= '<tr><td colspan="' . $colspan . '" style="text-align: left;padding-top: 15px; color: #' . $color . '; font-weight: bold; border-bottom: 2px solid #' . $color . '">' . $category->displayData('name') . '</td></tr>';

                foreach ($cat['montants'] as $id_montant => $montant) {
                    if ($montant['total'] === 0) {
                        continue;
                    }

                    $html .= '<tr>';

                    $type_montant = BimpCache::getBimpObjectInstance('bimpmargeprod', 'BMP_TypeMontant', (int) $id_montant);

                    if (BimpObject::objectLoaded($type_montant)) {
                        $html .= '<td style="min-width: 200px; max-width: 200px;text-align: left;font-weight: bold; color: #' . $color . '">' . $type_montant->getData('name') . '</td>';
                        $html .= '<td style="min-width: 100px; max-width: 100px;background-color: #F0F0F0!important; font-weight: bold;">' . BimpTools::displayMoneyValue((float) $montant['total'], '') . '</td>';
                        if (!$only_total) {
                            foreach ($events as $event) {
                                $amount = 0;
                                if (isset($montant['events'][(int) $event->id])) {
                                    $amount = (float) $montant['events'][(int) $event->id];
                                }
                                $html .= '<td style="min-width: 100px; max-width: 100px;">' . BimpTools::displayMoneyValue($amount, '', true) . '</td>';
                            }
                        }
                    } else {
                        $html .= '<td style="text-align: left;" colspan="' . $colspan . '">' . BimpRender::renderAlerts('Le type de montant d\'ID ' . $id_cat . ' n\'existe pas') . '</td>';
                    }

                    $html .= '</tr>';
                }

                $bk_color = BimpTools::setColorSL('#' . $color, null, 0.9);
                $td_style = 'background-color: ' . $bk_color . '!important;';
                $html .= '<tr>';
                $html .= '<td style="min-width: 200px; max-width: 200px;' . $td_style . 'text-align: right; padding-right: 20px; font-weight: bold; color: #' . $color . '">Total ' . $category->getData('name') . '</td>';
                $html .= '<td style="min-width: 100px; max-width: 100px;' . $td_style . '">' . BimpTools::displayMoneyValue($cat['total'], '', true) . '</td>';
                if (!$only_total) {
                    foreach ($events as $event) {
                        $amount = 0;
                        if (isset($cat['events_total'][(int) $event->id])) {
                            $amount = (float) $cat['events_total'][(int) $event->id];
                        }
                        $html .= '<td style="min-width: 100px; max-width: 100px;' . $td_style . '">' . BimpTools::displayMoneyValue($amount, '', true) . '</td>';
                    }
                }
                $html .= '</tr>';
            } else {
                $html .= '<tr><td style="text-align: left;" colspan="' . $colspan . '">' . BimpRender::renderAlerts('La catégorie d\'ID ' . $id_cat . ' n\'existe pas') . '</td></tr>';
            }
        }

        $html .= '<tr class="margin_row">';
        $html .= '<td colspan="' . $colspan . '"></td>';
        $html .= '</tr>';

        $html .= '<tr class="total_row" style="font-weight: bold; font-size: 13px;">';
        $html .= '<th style="min-width: 200px; max-width: 200px;">Total Recettes</th>';
        $html .= '<td style="min-width: 100px; max-width: 100px;background-color: #DCDCDC!important;">' . BimpTools::displayMoneyValue($montants['recettes']['total'], '', true) . '</td>';
        if (!$only_total) {
            foreach ($events as $event) {
                $amount = 0;
                if (isset($montants['recettes']['events_total'][(int) $event->id])) {
                    $amount = (float) $montants['recettes']['events_total'][(int) $event->id];
                }
                $html .= '<td style="min-width: 100px; max-width: 100px;">' . BimpTools::displayMoneyValue($amount, '', true) . '</td>';
            }
        }
        $html .= '</tr>';

        $html .= '<tr class="margin_row">';
        $html .= '<td colspan="' . $colspan . '" style="height: 30px"></td>';
        $html .= '</tr>';


        // Charges: 
        $html .= '<tr>';
        $html .= '<td colspan="' . $colspan . '" style="border-width: 2px; border-color: #505050;text-align: left;font-weight: bold; font-size: 15px; padding: 8px; background-color: #F0F0F0!important">CHARGES</td>';
        $html .= '</tr>';
        foreach ($montants['charges']['categories'] as $id_cat => $cat) {

            if ($cat['total'] === 0) {
                continue;
            }

            $category = BimpCache::getBimpObjectInstance('bimpmargeprod', 'BMP_CategorieMontant', (int) $id_cat);

            if (BimpObject::objectLoaded($category)) {
                $color = $category->getData('color');

                $html .= '<tr><td colspan="' . $colspan . '" style="text-align: left;padding-top: 15px; color: #' . $color . '; font-weight: bold; border-bottom: 2px solid #' . $color . '">' . $category->displayData('name') . '</td></tr>';

                foreach ($cat['montants'] as $id_montant => $montant) {
                    if ($montant['total'] === 0) {
                        continue;
                    }

                    $html .= '<tr>';
                    $type_montant = BimpCache::getBimpObjectInstance('bimpmargeprod', 'BMP_TypeMontant', (int) $id_montant);
                    if (BimpObject::objectLoaded($type_montant)) {
                        $html .= '<td style="min-width: 200px; max-width: 200px;text-align: left;font-weight: bold; color: #' . $color . '">' . $type_montant->getData('name') . '</td>';
                        $html .= '<td style="min-width: 100px; max-width: 100px;background-color: #F0F0F0!important; font-weight: bold;">' . BimpTools::displayMoneyValue((float) $montant['total'], '') . '</td>';
                        if (!$only_total) {
                            foreach ($events as $event) {
                                $amount = 0;
                                if (isset($montant['events'][(int) $event->id])) {
                                    $amount = (float) $montant['events'][(int) $event->id];
                                }
                                $html .= '<td style="min-width: 100px; max-width: 100px;">' . BimpTools::displayMoneyValue($amount, '', true) . '</td>';
                            }
                        }
                    } else {
                        $html .= '<td style="text-align: left;" colspan="' . $colspan . '">' . BimpRender::renderAlerts('Le type de montant d\'ID ' . $id_cat . ' n\'existe pas') . '</td>';
                    }
                    $html .= '</tr>';
                }

                $bk_color = BimpTools::setColorSL('#' . $color, null, 0.9);
                $td_style = 'background-color: ' . $bk_color . '!important;';
                $html .= '<tr>';
                $html .= '<td style="min-width: 200px; max-width: 200px;' . $td_style . ' text-align: right; padding-right: 20px; font-weight: bold; color: #' . $color . '">Total ' . $category->getData('name') . '</td>';
                $html .= '<td style="min-width: 100px; max-width: 100px;' . $td_style . '">' . BimpTools::displayMoneyValue($cat['total'], '', true) . '</td>';
                if (!$only_total) {
                    foreach ($events as $event) {
                        $amount = 0;
                        if (isset($cat['events_total'][(int) $event->id])) {
                            $amount = (float) $cat['events_total'][(int) $event->id];
                        }
                        $html .= '<td style="min-width: 100px; max-width: 100px;' . $td_style . '">' . BimpTools::displayMoneyValue($amount, '', true) . '</td>';
                    }
                }
                $html .= '</tr>';
            } else {
                $html .= '<tr><td style="text-align: left;" colspan="' . $colspan . '">' . BimpRender::renderAlerts('La catégorie d\'ID ' . $id_cat . ' n\'existe pas') . '</td></tr>';
            }
        }

        $html .= '<tr class="margin_row">';
        $html .= '<td colspan="' . $colspan . '"></td>';
        $html .= '</tr>';

        $html .= '<tr class="total_row" style="font-weight: bold; font-size: 13px;">';
        $html .= '<th style="min-width: 200px; max-width: 200px;">Total charges</th>';
        $html .= '<td style="min-width: 100px; max-width: 100px;background-color: #DCDCDC!important;">' . BimpTools::displayMoneyValue($montants['charges']['total'], '', true) . '</td>';
        if (!$only_total) {
            foreach ($events as $event) {
                $amount = 0;
                if (isset($montants['charges']['events_total'][(int) $event->id])) {
                    $amount = (float) $montants['charges']['events_total'][(int) $event->id];
                }
                $html .= '<td style="min-width: 100px; max-width: 100px;">' . BimpTools::displayMoneyValue($amount, '', true) . '</td>';
            }
        }
        $html .= '</tr>';

        $html .= '<tr class="margin_row">';
        $html .= '<td colspan="' . $colspan . '"></td>';
        $html .= '</tr>';

        $html .= '<tr class="total_row" style="font-weight: bold; font-size: 13px; border: 2px solid #505050">';
        $html .= '<th style="min-width: 200px; max-width: 200px;">Résultat</th>';
        $resultat = (float) $montants['recettes']['total'] - (float) $montants['charges']['total'];
        $html .= '<td style="min-width: 100px; max-width: 100px;background-color: #505050!important; color: #fff; font-weight: bold;">' . BimpTools::displayMoneyValue($resultat, '') . '</td>';
        if (!$only_total) {
            foreach ($events as $event) {
                $recettes = 0;
                $charges = 0;
                if (isset($montants['recettes']['events_total'][(int) $event->id])) {
                    $recettes = (float) $montants['recettes']['events_total'][(int) $event->id];
                }
                if (isset($montants['charges']['events_total'][(int) $event->id])) {
                    $charges = (float) $montants['charges']['events_total'][(int) $event->id];
                }
                $resultat = $recettes - $charges;
                $html .= '<td style="min-width: 100px; max-width: 100px;">' . BimpTools::displayMoneyValue($resultat, '', true) . '</td>';
            }
        }
        $html .= '</tr>';

        $html .= '<tr class="margin_row">';
        $html .= '<td colspan="' . $colspan . '" style="height: 30px"></td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th style="min-width: 200px; max-width: 200px;">Nb Spectateurs payants</th>';
        $html .= '<td style="min-width: 100px; max-width: 100px;background-color: #505050!important; color: #fff; font-weight: bold;">' . $billets['total_payants'] . '</td>';
        if (!$only_total) {
            foreach ($events as $event) {
                $html .= '<td style="min-width: 100px; max-width: 100px;">' . $billets['events'][(int) $event->id]['total_payants'] . '</td>';
            }
        }
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th style="min-width: 200px; max-width: 200px;">Nb Spectateurs gratuits</th>';
        $html .= '<td style="min-width: 100px; max-width: 100px;background-color: #505050!important; color: #fff; font-weight: bold;">' . $billets['total_gratuits'] . '</td>';
        if (!$only_total) {
            foreach ($events as $event) {
                $html .= '<td style="min-width: 100px; max-width: 100px;">' . $billets['events'][(int) $event->id]['total_gratuits'] . '</td>';
            }
        }
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th style="min-width: 200px; max-width: 200px;">Nb Spectateurs locataires</th>';
        $html .= '<td style="min-width: 100px; max-width: 100px;background-color: #505050!important; color: #fff; font-weight: bold;">' . $billets['total_loc'] . '</td>';
        if (!$only_total) {
            foreach ($events as $event) {
                $html .= '<td style="min-width: 100px; max-width: 100px;">' . $billets['events'][(int) $event->id]['total_loc'] . '</td>';
            }
        }
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th style="min-width: 200px; max-width: 200px;">Nb Spectateurs total</th>';
        $html .= '<td style="min-width: 100px; max-width: 100px;background-color: #505050!important; color: #fff; font-weight: bold;">' . $billets['total'] . '</td>';
        if (!$only_total) {
            foreach ($events as $event) {
                $html .= '<td style="min-width: 100px; max-width: 100px;">' . $billets['events'][(int) $event->id]['total'] . '</td>';
            }
        }
        $html .= '</tr>';

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    // Events:

    public function onMontantChange(BMP_EventMontant $eventMontant)
    {
        if (!$this->isLoaded()) {
            return;
        }

        $id_type_montant = (int) $eventMontant->getData('id_montant');
//        $id_coprod = (int) $eventMontant->getData('id_coprod');
        // Traitements des calculs automatiques dont le montant intervient dans le montant source:
        $sql = BimpTools::getSqlSelect(array('a.id_target'));
        $sql .= BimpTools::getSqlFrom('bmp_calc_montant', array(array(
                        'table' => 'bmp_event_calc_montant',
                        'on'    => 'b.id_calc_montant = a.id',
                        'alias' => 'b'
        )));

        $sql .= ' WHERE ' . (int) $id_type_montant . ' IN (';
        $sql .= 'SELECT c.id_type_montant FROM llx_bmp_calc_montant_type_montant c WHERE c.id_calc_montant = a.id';
        $sql .= ')';
        $sql .= ' AND a.active = 1 AND b.active = 1 AND b.id_event = ' . (int) $this->id;

        $rows = $this->db->executeS($sql, 'array');

        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $this->calcMontant((int) $r['id_target'], null);
            }
        }
    }

    public function onChildSave(BimpObject $child)
    {
        if (in_array($child->object_name, array('BMP_EventBillets', 'BMP_EventTarif'))) {
            if ((int) $this->getData('status') === 1) {
                $this->calcPrevisionnels();
            } else {
                $this->calcBilletsAmount();
            }
        } elseif ($child->object_name === 'BMP_EventMontant') {
            $this->onMontantChange($child);
        } elseif ($child->object_name === 'BMP_EventCalcMontant') {
            $calcMontant = $child->getChildObject('calc_montant');
            if (!is_null($calcMontant) && $calcMontant->isLoaded()) {
                $this->calcMontant((int) $calcMontant->getData('id_target'), null);
            }
        }
    }

    // Overrides:

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors) && $this->isLoaded()) {
            // Création des montants frais/recettes obligatoires:
            $typeMontant = BimpObject::getInstance($this->module, 'BMP_TypeMontant');
            $list = $typeMontant->getList(array(
                'required' => 1
            ));


            foreach ($list as $item) {
                $eventMontant = BimpObject::getInstance($this->module, 'BMP_EventMontant');
                $montant_errors = $eventMontant->validateArray(array(
                    'id_event'            => (int) $this->id,
                    'id_category_montant' => (int) $item['id_category'],
                    'id_montant'          => (int) $item['id'],
                    'amount'              => 0,
                    'status'              => 1,
                    'type'                => $item['type'],
                    'id_coprod'           => 0
                ));
                if (!count($montant_errors)) {
                    $montant_errors = $eventMontant->create();
                }
                if (count($montant_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($montant_errors, 'Des erreurs sont survenues lors de la création du montant "' . $item['name'] . '"');
                }
            }
            unset($typeMontant);

            // Création des Calculs Automatiques obligatoires:
            $calcMontant = BimpObject::getInstance($this->module, 'BMP_CalcMontant');
            $list = $calcMontant->getList(array(
                'required' => 1,
                'active'   => 1
            ));

            foreach ($list as $item) {
                $eventCalcMontant = BimpObject::getInstance($this->module, 'BMP_EventCalcMontant');
                $cm_errors = $eventCalcMontant->validateArray(array(
                    'id_event'        => (int) $this->id,
                    'id_calc_montant' => (int) $item['id'],
                    'percent'         => (float) $item['percent'],
                    'active'          => 1
                ));
                if (!count($cm_errors)) {
                    $cm_errors = $eventCalcMontant->create();
                }
                if (count($cm_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($cm_errors, 'Des erreurs sont survenues lors de la création du calcul automatique "' . $item['label'] . '"');
                }
            }

            // Création des tarifs standards:
            $tarifs = BimpTools::getPostFieldValue('tarifs');
            if (is_array($tarifs)) {
                foreach ($tarifs as $name) {
                    $tarif = BimpObject::getInstance($this->module, 'BMP_EventTarif');
                    $tarif_errors = $tarif->validateArray(array(
                        'id_event'     => (int) $this->id,
                        'name'         => $name,
                        'amount'       => 0,
                        'previsionnel' => 0,
                        'ca_moyen_bar' => 0
                    ));

                    if (!count($tarif_errors)) {
                        $tarif_errors = $tarif->create();
                    }
                    if (count($tarif_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($tarif_errors, 'Des erreurs sont survenues lors de la création du tarif "' . $name . '"');
                    }
                }
            }

            // Création des ventes de billets pour chaque vendeur actif: 
            $tarifs = $this->getChildrenObjects('tarifs');

            if (count($tarifs)) {
                BimpObject::loadClass($this->module, 'BMP_Vendeur');
                foreach (BMP_Vendeur::getVendeurs() as $vendeur) {
                    $vendeur_tarifs = $vendeur->getData('tarifs');
                    foreach ($tarifs as $tarif) {
                        if (in_array($tarif->getData('name'), $vendeur_tarifs)) {
                            $vente = BimpObject::getInstance($this->module, 'BMP_EventBillets');
                            $vente_errors = $vente->validateArray(array(
                                'id_event'      => (int) $this->id,
                                'id_soc_seller' => (int) $vendeur->getData('id_soc'),
                                'seller_name'   => (string) $vendeur->getData('label'),
                                'id_tarif'      => (int) $tarif->id,
                                'quantit'       => 0
                            ));
                            if (!count($vente_errors)) {
                                $vente_errors = $vente->create();
                            }
                            if (count($vente_errors)) {
                                $vendeur_label = $vendeur->displayVendeur('nom', false, true);
                                $warnings[] = BimpTools::getMsgFromArray($vente_errors, 'Des erreurs sont survenues lors de la création de la vente de billets au tarif "' . $tarif->getData('name') . '" pour le vendeur "' . $vendeur_label . '"');
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function update()
    {
        $current_status = (int) $this->getInitData('status');

        $errors = parent::update();

        if (!count($errors)) {
            $new_status = (int) $this->getData('status');

            if ($new_status === 1) {
                if ($current_status !== $new_status) {
                    $amount_20 = (float) $this->getMontantAmount((int) self::$id_bar20_type_montant, 0);
                    $amount_55 = (float) $this->getMontantAmount((int) self::$id_bar55_type_montant, 0);

                    $this->db->update($this->getTable(), array(
                        'bar_20_save' => $amount_20,
                        'bar_55_save' => $amount_55
                            ), '`id` = ' . (int) $this->id);
                }
                $this->calcPrevisionnels();
            } else {
                $this->calcBilletsAmount();

                if ($current_status === 1) {
                    $eventMontant = BimpCache::findBimpObjectInstance($this->module, 'BMP_EventMontant', array(
                                'id_event'   => $this->id,
                                'id_montant' => self::$id_bar20_type_montant,
                                'id_coprod'  => 0
                    ));
                    if (BimpObject::objectLoaded($eventMontant)) {
                        $eventMontant->updateField('amount', (float) $this->getData('bar_20_save'));
                    }

                    $eventMontant = BimpCache::findBimpObjectInstance($this->module, 'BMP_EventMontant', array(
                                'id_event'   => $this->id,
                                'id_montant' => self::$id_bar55_type_montant,
                                'id_coprod'  => 0
                    ));
                    if (BimpObject::objectLoaded($eventMontant)) {
                        $eventMontant->updateField('amount', (float) $this->getData('bar_55_save'));
                    }
                }
            }
        }
        return $errors;
    }
}
