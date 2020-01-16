<?php

BimpTools::loadDolClass('compta/facture', 'facture');

//require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';
class Bimp_Paiement extends BimpObject
{

    public $useCaisse = false;
    public $facs_amounts = null;
    public static $paiement_factures_types = array(Facture::TYPE_STANDARD, Facture::TYPE_DEPOSIT, Facture::TYPE_REPLACEMENT, Facture::TYPE_SITUATION);
    public static $rbt_factures_type = array(Facture::TYPE_CREDIT_NOTE);

    const STATUS_COMMENCER = 1;

    public static $status_list = [
        self::STATUS_COMMENCER => ['label' => 'Commencer', 'classes' => ['warning'], 'icon' => 'hourglass-start']
    ];

    public function __construct($module, $object_name)
    {
        $this->useCaisse = (int) BimpCore::getConf('use_caisse_for_payments');

        parent::__construct($module, $object_name);
    }

    // Droits user: 

    public function canEdit()
    {
//        if($this->getData('exported') == 1) { // CanXXX : seulement les droits user!
//            return 0;
//        }
        return 1;
    }

    public function canDelete()
    {
        return $this->canEdit();
    }

    // Getters booléens: 

    public function isEditable($force_edit = false, &$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }
        
        if ($this->getData('exported') == 1) {
            $errors[] = 'Paiement exporté en compta';
            return 0;
        }

        if ($this->useCaisse) {
            $bc_paiement = BimpCache::findBimpObjectInstance('bimpcaisse', 'BC_Paiement', array(
                        'id_paiement' => (int) $this->id
                            ), true);
            if (BimpObject::objectLoaded($bc_paiement)) {
                $p_errors = array();
                if (!$bc_paiement->isEditable($force_edit, $p_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($p_errors);
                    return 0;
                }
            }
        }

        return 1;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        return $this->isEditable($force_delete, $errors);
    }

    // Getters: 

    public function getAmountFromFacture()
    {
        $id_facture = (int) BimpTools::getPostFieldValue('id_facture', 0);

        if ($id_facture) {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
            if (BimpObject::objectLoaded($facture)) {
                return (float) round($facture->getRemainToPay(), 2);
            }
        }

        return 0;
    }

    public function getDolObjectCreateParams()
    {
        global $user;
        return array($user, 1);
    }

    public function getFacsAmounts()
    {
        if (is_null($this->facs_amounts)) {
            if ($this->isLoaded()) {
                $this->facs_amounts = array();

                $rows = $this->db->getRows('paiement_facture', '`fk_paiement` = ' . (int) $this->id, null, 'array', array('fk_facture', 'amount'));

                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        $this->facs_amounts[(int) $r['fk_facture']] = (float) $r['amount'];
                    }
                }
            } else {
                return array();
            }
        }

        return $this->facs_amounts;
    }

    public function getListsExtraButtons()
    {
        $buttons = array();
        if ($this->isLoaded()) {
            $buttons[] = array(
                'label'   => 'Afficher dans un nouvel onglet',
                'icon'    => 'fas_external-link-alt',
                'onclick' => 'window.open(\'' . DOL_URL_ROOT . '/compta/paiement/card.php?id=' . $this->id . '\')'
            );
        }
        return $buttons;
    }

    public function getFacturesSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        if (!isset($joins['pf'])) {
            $joins['pf'] = array(
                'alias' => 'pf',
                'table' => 'paiement_facture',
                'on'    => 'pf.fk_paiement = ' . $main_alias . '.rowid'
            );
        }

        $filters['pf.fk_facture'] = $value;
    }

    public function getClientSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        if (!isset($joins['pf'])) {
            $joins['pf'] = array(
                'alias' => 'pf',
                'table' => 'paiement_facture',
                'on'    => 'pf.fk_paiement = ' . $main_alias . '.rowid'
            );
        }
        if (!isset($joins['facture'])) {
            $joins['facture'] = array(
                'alias' => 'facture',
                'table' => 'facture',
                'on'    => 'facture.rowid = pf.fk_facture'
            );
        }

        $filters['facture.fk_soc'] = $value;
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array())
    {
        switch ($field_name) {
            case 'id_facture':
                if (!isset($joins['pf'])) {
                    $joins['pf'] = array(
                        'alias' => 'pf',
                        'table' => 'paiement_facture',
                        'on'    => 'pf.fk_paiement = a.rowid'
                    );
                }
                $filters['pf.fk_facture'] = array(
                    'in' => $values
                );
                break;

            case 'id_client';
                if (!isset($joins['pf'])) {
                    $joins['pf'] = array(
                        'alias' => 'pf',
                        'table' => 'paiement_facture',
                        'on'    => 'pf.fk_paiement = a.rowid'
                    );
                }
                if (!isset($joins['facture'])) {
                    $joins['facture'] = array(
                        'alias' => 'facture',
                        'table' => 'facture',
                        'on'    => 'facture.rowid = pf.fk_facture'
                    );
                }
                $filters['facture.fk_soc'] = array(
                    'in' => $values
                );
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors);
    }

    public function getCustomFilterValueLabel($field_name, $value)
    {
        switch ($field_name) {
            case 'id_facture':
                $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $value);
                if (BimpObject::objectLoaded($fac)) {
                    return $fac->getRef();
                }
                break;

            case 'id_client';
                $cli = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $value);
                if (BimpObject::objectLoaded($cli)) {
                    return $cli->getRef() . ' - ' . $cli->getName();
                }
                break;
        }

        return parent::getCustomFilterValueLabel($field_name, $value);
    }

    // Affichages: 

    public function displayType()
    {
        if ($this->isLoaded()) {
            return $this->dol_object->type_libelle;
        }
    }

    public function displayAccount()
    {
        if ($this->isLoaded() && isset($this->dol_object->fk_account) && (int) $this->dol_object->fk_account) {
            BimpTools::loadDolClass('compta/bank', 'account');
            $account = new Account($this->db->db);
            if ($account->fetch((int) $this->dol_object->fk_account) > 0) {
                return $account->getNomUrl(1);
            } else {
                return BimpRender::renderAlerts('Le compte bancaire d\'ID ' . $this->dol_object->fk_account . ' n\'existe pas');
            }
        }

        return '';
    }

    public function displayAmount($id_facture = 0, $mult = 1)
    {
        if ($this->isLoaded()) {
            if ((int) $id_facture) {
                $amounts = $this->getFacsAmounts();

                if (isset($amounts[(int) $id_facture])) {
                    return BimpTools::displayMoneyValue((float) $amounts[(int) $id_facture] * $mult);
                }
            } else {
                return BimpTools::displayMoneyValue((float) $this->getData('amount') * $mult);
            }
        }

        return '';
    }

    public function displayFactures()
    {
        $html = '';

        if ($this->isLoaded()) {
            $rows = $this->db->getRows('paiement_facture', '`fk_paiement` = ' . (int) $this->id, null, 'array', array(
                'fk_facture',
                'amount'
            ));

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    if ($html) {
                        $html .= '<br/>';
                    }
                    $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['fk_facture']);
                    if (BimpObject::objectLoaded($fac)) {
                        $html .= $fac->getNomUrl(1, 1, 1, 'full');
                    } else {
                        $html .= '<span class="danger">';
                        $html .= 'La facture d\'ID ' . $r['fk_facture'] . ' n\'existe plus';
                        $html .= '</span>';
                    }

                    $html .= '<span style="display: inline-block; margin-left: 15px">';
                    $html .= '  (' . BimpTools::displayMoneyValue((float) $r['amount']) . ')';
                    $html .= '</span>';
                }
            }
        }

        return $html;
    }

    public function displayClient()
    {
        $html = '';

        if ($this->isLoaded()) {
            $sql = 'SELECT f.fk_soc FROM ' . MAIN_DB_PREFIX . 'facture f';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'paiement_facture pf on pf.fk_facture = f.rowid';
            $sql .= ' WHERE pf.fk_paiement = ' . (int) $this->id;

            $result = $this->db->executeS($sql, 'array');

            if (!empty($result)) {
                $clients = array();

                foreach ($result as $r) {
                    if ((int) $r['fk_soc'] && !in_array((int) $r['fk_soc'], $clients)) {
                        $clients[] = (int) $r['fk_soc'];

                        $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $r['fk_soc']);
                        if (BimpObject::objectLoaded($soc)) {
                            if ($html) {
                                $html .= '<br/>';
                            }
                            $html .= BimpObject::getInstanceNomUrlWithIcons($soc->dol_object);
                        }
                    }
                }
            }
        }

        return $html;
    }

    // Rendus: 

    public function renderUseCaisseInput()
    {
        $html = '';
        if ($this->useCaisse) {
            $value = 0;
            global $user;
            $caisse = BimpObject::getInstance('bimpcaisse', 'BC_Caisse');
            $id_caisse = (int) $caisse->getUserCaisse((int) $user->id);

            if ($id_caisse) {
                $caisse = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Caisse', $id_caisse);
                if (BimpObject::objectLoaded($caisse)) {
                    $value = 1;
                }
            }

            $html .= BimpInput::renderInput('toggle', 'use_caisse', $value);
        } else {
            $html .= '<span class="danger">';
            $html .= 'NON';
            $html .= '</span>';

            $html .= '<input type="hidden" value="0" name="use_caisse"/>';
        }

        return $html;
    }

    public function renderFacturesAmountsInputs()
    {
        $is_rbt = (int) BimpTools::getPostFieldValue('is_rbt', 0); // Cas d'un remboursement ou non.

        $id_client = (int) BimpTools::getPostFieldValue('id_client', 0);

        if (!$id_client) {
            return BimpRender::renderAlerts('Client absent');
        }

        $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
        if (BimpTools::isSubmit('param_values/fields/id_facture')) {
            $list[] = array('rowid' => (int) BimpTools::getValue('param_values/fields/id_facture', 0));
        } else {
            $list = $facture->getList(array(
                'fk_soc'    => $id_client,
                'paye'      => 0,
                'type'      => array('IN' => (!$is_rbt ? self::$paiement_factures_types : self::$rbt_factures_type)),
                'fk_statut' => 1,
                'total_ttc' => array(
                    'operator' => ($is_rbt ? '<' : '>'),
                    'value'    => 0
                )
                    ), null, null, 'id', 'asc', 'array', array('rowid'));
        }

        if (!count($list)) {
            if ($is_rbt) {
                return BimpRender::renderAlerts('Aucun avoir ou trop perçu à rembourser trouvé pour ce client', 'warning');
            } else {
                return BimpRender::renderAlerts('Aucune facture à payer trouvée pour ce client', 'warning');
            }
        }

        $rand = rand(111111, 999999);

        $options = array(
            'addon_right' => '<i class="fa fa-euro"></i>',
            'data'        => array(
                'data_type' => 'number',
                'decimals'  => 2,
                'min'       => 0
            ),
            'style'       => 'max-width: 90px'
        );

        $html .= '<div id="factures_payments_' . $rand . '" class="factures_payment_container" data-is_rbt="' . (int) $is_rbt . '">';

        $html .= '<div style="margin: 15px 0; text-align: right">';
        $html .= '<span style="font-weight: bold;">Somme totale ' . ($is_rbt ? 'remboursée' : 'versée') . ':&nbsp;&nbsp;</span>';
        $html .= BimpInput::renderInput('text', 'total_paid_amount', 0, $options);
        $html .= '</div>';

        $options['extra_class'] = 'facture_payment_input';
        $options['avoirs'] = 0;

        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<th>' . ($is_rbt ? 'Avoir' : 'Facture') . '</th>';
        $html .= '<th>Date</th>';
        $html .= '<th style="text-align: center;">Montant TTC</th>';
        $html .= '<th style="text-align: center;">' . ($is_rbt ? 'Remboursé' : 'Payé') . '</th>';
        $html .= '<th style="text-align: center;">' . ($is_rbt ? 'Reste à rembourser' : 'Reste à régler') . '</th>';
        if (!$is_rbt) {
            $html .= '<th style="text-align: center;">Avoirs utilisés</th>';
        }
        $html .= '<th style="text-align: center;">Montant ' . ($is_rbt ? 'remboursement' : 'règlement') . '</th>';
        $html .= '</thead>';

        if ($is_rbt) {
            $colspan = 6;
        } else {
            $colspan = 7;
        }
        $html .= '<tbody>';

        $i = 1;

        $total_ttc = 0;
        $total_paid = 0;
        $total_to_pay = 0;

        $at_least_one = false;
        foreach ($list as $item) {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $item['rowid']);
            if ($facture->isLoaded()) {
                $montant_ttc = round((float) $facture->dol_object->total_ttc, 2);
                $paid = round((float) $facture->getTotalPaid(), 2);
                $to_pay = $montant_ttc - $paid;
                $to_pay = round($to_pay, 2);

                if ($to_pay < 0.01 && $to_pay > -0.01) {
                    $facture->setObjectAction('classifyPaid');
                    continue;
                } elseif ($is_rbt && $to_pay >= 0) {
                    continue;
                } elseif (!$is_rbt && $to_pay <= 0) {
                    continue;
                }

                if ($is_rbt) {
                    $montant_ttc *= -1;
                    $paid *= -1;
                    $to_pay *= -1;
                }

                $at_least_one = true;

                $options['data']['min'] = 0;
                $options['data']['to_pay'] = $to_pay;
                $options['data']['total_amount'] = $montant_ttc;

                $total_ttc += $montant_ttc;
                $total_paid += $paid;
                $total_to_pay += $to_pay;

                $DT = new DateTime($this->db->db->iDate($facture->dol_object->date));

                $html .= '<tr class="facture_payment_row">';
                $html .= '<td>' . $facture->getNomUrl(0, 1, 1) . '</td>';
                $html .= '<td>' . $DT->format('d / m / Y') . '</td>';
                $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($montant_ttc, 'EUR') . '</td>';
                $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($paid, 'EUR') . '</td>';
                $html .= '<td style="text-align: center; font-weight: bold">' . BimpTools::displayMoneyValue($to_pay, 'EUR') . '</td>';
                if (!$is_rbt) {
                    $html .= '<td class="facture_avoirs" style="text-align: center; font-weight: bold"></td>';
                }
                $html .= '<td style="text-align: right;">';
                $html .= '<input type="hidden" name="amount_' . $i . '_id_facture" value="' . $facture->id . '"/>';
                $html .= BimpInput::renderInput('text', 'amount_' . $i, '', $options);
                $html .= '</td>';
                $html .= '</tr>';

                unset($DT);
                $i++;
            }
        }

        if (!$at_least_one) {
            $html .= '<tr>';
            $html .= '<td colspan="' . $colspan . '">';
            if ($is_rbt) {
                return BimpRender::renderAlerts('Aucun avoir à rembourser trouvé pour ce client', 'warning');
            } else {
                return BimpRender::renderAlerts('Aucune facture à payer trouvée pour ce client', 'warning');
            }
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';

        if (count($list) > 1) {
            $html .= '<tfoot>';
            $html .= '<tr>';
            $html .= '<td colspan="2">Total TTC</td>';
            $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($total_ttc, 'EUR') . '</td>';
            $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($total_paid, 'EUR') . '</td>';
            $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($total_to_pay, 'EUR') . '</td>';
            if (!$is_rbt) {
                $html .= '<td></td>';
            }
            $html .= '<td></td>';
            $html .= '</tr>';
            $html .= '</tfoot>';
        }

        $html .= '</table>';

        $html .= '<input type="hidden" name="total_to_pay" value="' . $total_to_pay . '"/>';
        $html .= '<input type="hidden" name="total_avoirs" value="0"/>';
        $html .= '<input type="hidden" name="avoirs_amounts" value=""/>';

        $html .= '<div class="total_payments_container" style="font-weight: bold; margin: 20px 0 5px; padding: 8px 12px; color: #3C3C3C; font-size: 14px; text-align: center; background-color: #D8D8D8">';
        $html .= 'Total ' . ($is_rbt ? 'remboursements' : 'paiements') . ':&nbsp;<span class="total_payments">0,00 &euro;</span>';
        $html .= '</div>';
        if (!$is_rbt) {
            $html .= '<div class="total_avoirs_container" style="display: none; font-weight: bold; margin: 5px 0; padding: 8px 12px; color: #fff; font-size: 14px; text-align: center; background-color: #D2AF00">';
            $html .= 'Total avoirs utilisés:&nbsp;<span class="total_avoirs">0,00 &euro;</span>';
            $html .= '</div>';
        }
        $html .= '<div class="rest_to_pay_container" style="font-weight: bold; margin: 5px 0; padding: 8px 12px; color: #fff; font-size: 14px; text-align: center; background-color: #A00000">';
        $html .= 'Reste à ' . ($is_rbt ? 'rembourser' : 'régler') . ':&nbsp;<span class="rest_to_pay">' . BimpTools::displayMoneyValue($total_to_pay, 'EUR') . '</span>';
        $html .= '</div>';
        $html .= '<div class="to_return_container" style="font-weight: bold; margin: 5px 0 10px; padding: 8px 12px; color: #fff; font-size: 14px; text-align: center; background-color: #348B41">';
        $html .= ($is_rbt ? 'Trop remboursé' : 'Trop perçu') . ':&nbsp;<span class="to_return">0,00 &euro;</span>';
        $html .= '</div>';

        if (!$is_rbt) {
            $html .= '<div class="to_return_option_container" style="margin: 10px 0; font-size: 14px; display: none">';
            $html .= '<span class="bold">Trop perçu :</span>';

            $html .= BimpInput::renderInput('select', 'to_return_option', '', array(
                        'options' => array(
                            ''       => '',
                            'keep'   => 'Conserver dans la facture',
                            'return' => 'Rendre en espèce',
                            'remise' => 'Convertir en remise client'
                        )
            ));

            $html .= '</div>';
        }

        $html .= '<script type="text/javascript">';
        $html .= 'onClientFacturesPaymentsInputsLoaded($(\'#factures_payments_' . $rand . '\'))';
        $html .= '</script>';
        $html .= '</div>';

        return $html;
    }

    // Gestion extra fields: 

    public function fetchExtraFields()
    {
        $fields = array(
            'type' => ''
        );

        if ($this->isLoaded()) {
            $fields['type'] = (int) $this->db->getValue($this->getTable(), 'fk_paiement', 'rowid = ' . (int) $this->id);
        }

        return $fields;
    }

    public function getExtraFieldFilterKey($field, &$joins, $main_alias = '')
    {
        switch ($field) {
            case 'type':
                return $main_alias . '.fk_paiement';
        }
        return '';
    }

    public function insertExtraFields()
    {
        return array();
    }

    public function updateExtraFields()
    {
        return array();
    }

    public function updateExtraField($field_name, $value, $id_object)
    {
        return array();
    }

    public function deleteExtraFields()
    {
        return array();
    }

    public function getExtraFieldSavedValue($field, $id_object)
    {
        switch ($field) {
            case 'type':
                return (int) $this->db->getValue($this->getTable(), 'fk_paiement', 'rowid = ' . (int) $id_object);
        }

        return null;
    }

    // Traitements: 

    public function onDelete()
    {
        $errors = array();

        if ($this->isDeletable(false, $errors)) {
            if ($this->useCaisse) {
                $BC_Caisse = BimpObject::getInstance('bimpcaisse', 'BC_Caisse');//pour chargé la classe
                $errors = array_merge($errors, BC_Caisse::onPaiementDelete($this->id, $this->dol_object->type_code, (float) $this->getData('amount')));
            }
        }

        return $errors;
    }

    // Overrides: 

    public function reset()
    {
        unset($this->facs_amounts);
        $this->facs_amounts = null;
        parent::reset();
    }

    public function validatePost()
    {
        $errors = parent::validatePost();

        if (BimpTools::isSubmit('id_mode_paiement')) {
            $id_paiement = BimpTools::getValue('id_mode_paiement', 0);
            if (is_string($id_paiement)) {
                if (preg_match('/^\d+$/', $id_paiement)) {
                    $id_paiement = (int) $id_paiement;
                } else {
                    $id_paiement = (int) dol_getIdFromCode($this->db->db, $id_paiement, 'c_paiement', 'code', 'id');
                }
            }
            $this->dol_object->paiementid = (int) $id_paiement;
        }

//        if (BimpTools::isSubmit('id_account')) {
//            $this->dol_object->fk_account = (int) BimpTools::getValue('id_account', 0);
//        }

        if (!(int) $this->dol_object->paiementid) {
            $errors[] = 'Mode de paiement absent';
        }

//        if (!(int) $this->dol_object->fk_account) {
//            $errors[] = 'Compte financier absent';
//        }

        return $errors;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        global $db, $user, $conf;
        $caisse = null;
        $id_caisse = 0;
        $account = null;
        $use_caisse = false;

        if ($this->useCaisse && (int) BimpTools::getValue('use_caisse', 0)) {
            $use_caisse = true;
            $id_caisse = (int) BimpTools::getValue('id_caisse', 0);
            if (!$id_caisse) {
                $errors[] = 'Caisse absente';
            } else {
                $caisse = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Caisse', $id_caisse);
                if (!BimpObject::objectLoaded($caisse)) {
                    $errors[] = 'La caisse d\'ID ' . $id_caisse . ' n\'existe pas';
                } else {
                    if ($caisse->isValid($errors)) {
                        $account = $caisse->getChildObject('account');
                    }
                }
            }
        } else {
            $id_account = (int) BimpTools::getValue('id_account', 0);

            if (!$id_account) {
                $errors[] = 'Veuillez sélectionner un compte bancaire';
            } else {
                BimpTools::loadDolClass('compta/bank', 'account');
                $account = new Account($db);
                $account->fetch($id_account);

                if (!BimpObject::objectLoaded($account)) {
                    $errors[] = 'Le compte bancaire d\'ID ' . $id_account . ' n\'existe pas';
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        if (!BimpObject::objectLoaded($account)) {
            $errors[] = 'Compte bancaire invalide';
            return $errors;
        }

        // Check du mode de paiement: 
        $type_paiement = $this->db->getValue('c_paiement', 'code', '`id` = ' . (int) $this->dol_object->paiementid);

        if (is_null($type_paiement) || !(string) $type_paiement) {
            $errors[] = 'Mode paiement invalide';
        } elseif (!$use_caisse && $type_paiement === 'LIQ') {
            $errors[] = 'Le réglement en espèce n\'est possible que pour un paiement en caisse';
        }

        if (count($errors)) {
            return $errors;
        }

        $nom_emetteur = '';
        $banque_emetteur = '';

        if (in_array($type_paiement, array('CHQ', 'VIR'))) {
            $nom_emetteur = BimpTools::getPostFieldValue('nom_emetteur', '');
        }
        if ($type_paiement === 'CHQ') {
            $banque_emetteur = BimpTools::getPostFieldValue('banque_emetteur', '');
        }

        $this->dol_object->datepaye = dol_now();
        $this->dol_object->fk_account = (int) $account->id;

        $single_amount = BimpTools::getPostFieldValue('single_amount', null);

        if (is_null($single_amount)) {
            // Paiement de plusieurs factures 
            $is_rbt = BimpTools::getValue('is_rbt', null);

            if (is_null($is_rbt)) {
                return array('Type d\'opération absent');
            }

            // Check montants: 
            $total_paid = (float) BimpTools::getValue('total_paid_amount');

            $avoirs = json_decode(BimpTools::getValue('avoirs_amounts', ''), true);
            $total_factures_versements = 0;

            if ((is_null($total_paid) || !$total_paid) && (is_null($avoirs) || !count($avoirs))) {
                $errors[] = 'Montant total payé absent';
            }

            $i = 1;

            BimpTools::loadDolClass('compta/facture', 'facture');
            $factures = array();

            $to_return_option = BimpTools::getPostFieldValue('to_return_option', '');
            $total_to_return = 0;
            $to_return_amounts = array();
            $facs_to_convert = array();


            // Calcul du total payé par facture. 
            while (BimpTools::isSubmit('amount_' . $i)) {
                $id_facture = (int) BimpTools::getValue('amount_' . $i . '_id_facture', 0);
                $amount = (float) BimpTools::getValue('amount_' . $i, 0);

                if ($id_facture <= 0) {
                    $errors[] = 'ID facture invalide (ligne ' . $i . ')';
                } elseif ($amount !== 0) {
                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);

                    if (!$facture->isLoaded()) {
                        $errors[] = 'Facture d\'ID ' . $id_facture . ' inexistante';
                    } elseif ($is_rbt && !in_array((int) $facture->getData('type'), self::$rbt_factures_type)) {
                        $errors[] = 'La facture ' . $facture->getNomUrl(0, 1, 1) . ' est de type "' . $facture->displayData('type') . '" et ne peut donc pas faire l\'objet d\'un remboursement';
                    } elseif (!$is_rbt && !in_array((int) $facture->getData('type'), self::$paiement_factures_types)) {
                        $errors[] = 'La facture ' . $facture->getNomUrl(0, 1, 1) . ' est de type "' . $facture->displayData('type') . '" et ne peut donc pas faire l\'objet d\'un règlement';
                    } else {
                        $factures[$id_facture] = $facture;

                        $avoir_used = 0;
                        $to_pay = round((float) $facture->getRemainToPay(), 2);
                        $mult = 1;

                        if (!$is_rbt) {
                            foreach ($avoirs as $avoir) {
                                if ($avoir['input_name'] === 'amount_' . $i) {
                                    $avoir_used += (float) $avoir['amount'];
                                }
                            }
                        } else {
                            $mult = -1;
                        }

                        $diff = $to_pay - $avoir_used - ($amount * $mult);

                        if ($diff < 0.01 && $diff > -0.01) {
                            $diff = 0;
                        }

                        if ($is_rbt) {
                            if ($diff > 0) {
                                $errors[] = 'Il n\'est pas possible d\'enregistrer des sommes supérieures aux montants dûs pour les remboursements (' . $to_pay . ', ' . $diff . ', ' . $amount . ')';
                                break;
                            }
                        } else {
                            if ($diff < 0) {
                                if (!$to_return_option) {
                                    $errors[] = 'Veuillez sélectionner une option pour le traitement du trop perçu';
                                    break;
                                }

                                switch ($to_return_option) {
                                    case 'remise':
                                        $facs_to_convert[] = $id_facture;
                                        break;

                                    case 'return':
                                        if (!$use_caisse) {
                                            $errors[] = 'Le rendu monnaie du trop perçu n\'est possible que pour un paiement en caisse';
                                            break 2;
                                        }
                                        $to_return_amounts[$id_facture] = $diff;
                                        $total_to_return += abs($diff);
                                        break;
                                }
                            }
                        }



                        $total_factures_versements += $amount;

                        if ($is_rbt) {
                            $amount *= -1;
                        }

                        $this->dol_object->amounts[$id_facture] = $amount;
                    }
                }
                $i++;
            }

            if (count($errors)) {
                return $errors;
            }

            if ($total_factures_versements) {
                $errors = parent::create($warnings);
            }

            if (!count($errors)) {
                // Check paiements factures: 
                foreach ($this->dol_object->amounts as $id_facture => $amount) {
                    if (BimpObject::objectLoaded($factures[(int) $id_facture])) {
                        $factures[(int) $id_facture]->checkIsPaid();
                    }
                }

                // Insertion des avoirs éventuels: 
                if (!empty($avoirs) && !$is_rbt) {
                    BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                    $i = 1;
                    while (BimpTools::isSubmit('amount_' . $i)) {
                        $id_facture = (int) BimpTools::getValue('amount_' . $i . '_id_facture', 0);
                        if ($id_facture && isset($factures[$id_facture]) && BimpObject::objectLoaded($factures[$id_facture])) {
                            $facture = $factures[$id_facture];
                            foreach ($avoirs as $avoir) {
                                if ($avoir['input_name'] === 'amount_' . $i) {
                                    $discount = new DiscountAbsolute($this->db->db);
                                    $discount->fetch((int) $avoir['id_discount']);

                                    if (!BimpObject::objectLoaded($discount)) {
                                        $warnings[] = 'L\'avoir client d\'ID ' . $avoir['id_discount'] . ' n\'existe plus et n\'a donc pas pu être utilisé en paiement de la facture ' . $facture->getNomUrl(0, 1, 1, 'full');
                                    } elseif ((int) $discount->discount_type !== 0) {
                                        $warnings[] = 'L\'avoir client d\'ID ' . $avoir['id_discount'] . ' n\'est pas valide (il s\'agit d\'un avoir fournisseur) et n\'a donc pas pu être utilisé en paiement de la facture ' . $facture->getNomUrl(0, 1, 1, 'full');
                                    } elseif ((int) $discount->fk_facture || (int) $discount->fk_facture_line) {
                                        $warnings[] = 'L\'avoir client d\'ID ' . $avoir['id_discount'] . ' a déjà été consommé et n\'a donc pas pu être utilisé en paiement de la facture ' . $facture->getNomUrl(0, 1, 1, 'full');
                                    } else {
                                        // Ajout de l'avoir à la facture: 
                                        if ($discount->link_to_invoice(0, (int) $facture->id) < 0) {
                                            $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($discount), 'Echec de l\'utilisation de l\'avoir client #' . $discount->id . ' en paiement de la facture ' . $facture->getNomUrl(0, 1, 1, 'full'));
                                        } else {
                                            $facture->checkIsPaid();
                                        }
                                    }
                                }
                            }
                        }
                        $i++;
                    }
                }


                if ($this->isLoaded()) {
                    // Création du paiement pour le rendu monnaie si nécessaire: 
                    if ($to_return_option === 'return' && $total_to_return > 0) {
                        // Création du paiement: 
                        $p = new Paiement($db);
                        $p->datepaye = dol_now();
                        $p->amounts = $to_return_amounts;
                        $p->paiementid = (int) dol_getIdFromCode($db, 'LIQ', 'c_paiement');
                        $p->note = 'Rendu monnaie d\'un trop perçu (Paiement #' . $this->id . ')';

                        if ($p->create($user) < 0) {
                            $msg = 'Echec de la création du paiement pour le rendu monnaie de ' . BimpTools::displayMoneyValue($total_to_return, 'EUR');
                            $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($p), $msg);
                        } else {
                            // Ajout du paiement au compte financier: 
                            if (!empty($conf->banque->enabled)) {
                                if ($p->addPaymentToBank($user, 'payment', 'Rendu monnaie du trop perçu (Paiement #' . $this->id . ')', $account->id, '', '') < 0) {
                                    $msg = 'Echec de l\'ajout du rendu monnaire de ' . BimpTools::displayMoneyValue($total_to_return, 'EUR') . ' au compte bancaire ' . $account->bank;
                                    $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($p, $errors), $msg);
                                }
                            }

                            // Enregistrement du paiement dans la caisse: 
                            $paiement_errors = $caisse->addPaiement($p);
                            if (count($paiement_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($paiement_errors, 'Des erreurs sont survenues lors de l\'enregistrement en caisse du rendu monnaie de ' . BimpTools::displayMoneyValue($total_to_return, 'EUR'));
                            }
                        }
                    }

                    // Conversion des trop-perçus en remises si nécessaire: 
                    if ($to_return_option === 'remise') {
                        foreach ($facs_to_convert as $id_facture) {
                            $facture = isset($factures[(int) $id_facture]) ? $factures[(int) $id_facture] : null;
                            if (BimpObject::objectLoaded($facture)) {
                                $fac_errors = $facture->convertToRemise();
                                if (count($fac_errors)) {
                                    $warnings[] = BimpTools::getMsgFromArray($fac_errors, 'Echec de la conversion en remise du trop perçu pour la facture ' . $facture->getRef());
                                }
                            }
                        }
                    }
                }
            }
        } else {
            // Paiement unique pour une seule facture: 
            if (!$single_amount) {
                $errors[] = 'Veuillez indiquer un montant pour ce paiement';
            }

            $id_facture = (int) BimpTools::getPostFieldValue('id_facture', 0);
            if (!$id_facture) {
                $errors[] = 'ID de la facture concernée absent';
            } else {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
                if (!BimpObject::objectLoaded($facture)) {
                    $errors[] = 'La facture d\'ID ' . $id_facture . ' n\'existe pas';
                } else {
                    $errors = $facture->checkSingleAmoutPaiement($single_amount);
                }
            }

            if (!count($errors) && $single_amount) {
                $this->dol_object->amounts[$id_facture] = $single_amount;
                $errors = parent::create($warnings, $force_create);
            }
        }

        if ($this->isLoaded()) {
            // Ajout du paiement au compte financier:
            if (!empty($conf->banque->enabled)) {
                $this->dol_object->error = '';
                $this->dol_object->errors = array();
                $label = 'Paiement facture client';

                if ($use_caisse) {
                    $centre = $this->db->getValue('entrepot', 'ref', '`rowid` = ' . (int) $caisse->getData('id_entrepot'));
                    if (is_null($centre)) {
                        $centre = 'inconnu';
                    }
                    $label .= '(Caisse "' . $caisse->getData('name') . '" - Centre "' . $centre . '")';
                }

                if ($this->dol_object->addPaymentToBank($user, 'payment', $label, $account->id, $nom_emetteur, $banque_emetteur) <= 0) {
                    $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'ajout du paiement au compte bancaire "' . $account->bank . '"');
                }
            }

            // Enregistrement du paiement dans la caisse: 
            if ($use_caisse) {
                $paiement_errors = $caisse->addPaiement($this->dol_object);
                if (count($paiement_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($paiement_errors, 'Echec de l\'enregistrement en caisse du paiement');
                }
            }
        }

        return $errors;
    }

    public function updateDolObject(&$errors = array())
    {
        // Màj du n°:
        if (!$this->isLoaded()) {
            return 0;
        }
        if (is_null($this->dol_object)) {
            $errors[] = 'Objet Dolibarr invalide';
            return 0;
        }

        if (!isset($this->dol_object->id) || !$this->dol_object->id) {
            $errors[] = 'Objet Dolibarr invalide';
            return 0;
        }

        $this->dol_object->error = '';
        $this->dol_object->errors = array();

        if ($this->dol_object->update_num($this->getData('num_paiement')) <= 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour du numéro');
        }

        return (count($errors) ? 0 : 1);
    }
}
