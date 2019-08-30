<?php

BimpTools::loadDolClass('compta/facture', 'facture');

class Bimp_Paiement extends BimpObject
{

    public $useCaisse = false;
    public $facs_amounts = null;
    public static $paiement_factures_types = array(Facture::TYPE_STANDARD, Facture::TYPE_DEPOSIT, Facture::TYPE_REPLACEMENT, Facture::TYPE_SITUATION);
    public static $rbt_factures_type = array(Facture::TYPE_CREDIT_NOTE);

    public function __construct($module, $object_name)
    {
        $this->useCaisse = (int) BimpCore::getConf('use_caisse_for_payments');

        parent::__construct($module, $object_name);
    }

    // Getters: 

    public function getAmountFromFacture()
    {
        $id_facture = (int) BimpTools::getValue('fields/id_facture', 0);

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

    public function renderCaisseInput()
    {
        $html = '';

        if ($this->useCaisse) {
            global $user;
            $caisse = BimpObject::getInstance('bimpcaisse', 'BC_Caisse');
            $id_caisse = (int) $caisse->getUserCaisse((int) $user->id);

            if ($id_caisse) {
                $caisse = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Caisse', $id_caisse);
                if (!BimpObject::objectLoaded($caisse)) {
                    $html .= BimpRender::renderAlerts('Erreur: la caisse à laquelle vous êtes connecté semble ne plus exister');
                    $id_caisse = 0;
                } else {
                    $html .= $caisse->getData('name');
                    $html .= '<div style="text-align: center; margin: 15px 0">';
                    $url = DOL_URL_ROOT . '/bimpcaisse/index.php';
                    $html .= '<a class="btn btn-default" href="' . $url . '" target="_blank"><i class="fa fa-external-link iconLeft"></i>Se connecter à une autre caisse</a>';
                    $html .= '<span class="btn btn-default" onclick="reloadParentInput($(this), \'id_caisse\');"><i class="fas fa5-redo iconLeft"></i>Actualiser</span>';
                    $html .= '</div>';
                }
            }

            if (!$id_caisse) {
                $html .= BimpRender::renderAlerts('Vous n\'êtes connecté à aucune caisse', 'warning');
                $html .= '<div style="text-align: center; margin: 15px 0">';
                $url = DOL_URL_ROOT . '/bimpcaisse/index.php';
                $html .= '<a class="btn btn-default" href="' . $url . '" target="_blank"><i class="fa fa-external-link iconLeft"></i>Se connecter à une caisse</a>';
                $html .= '<span class="btn btn-default" onclick="reloadParentInput($(this), \'id_caisse\');"><i class="fas fa5-redo iconLeft"></i>Actualiser</span>';
                $html .= '</div>';
            }
        } else {
            $id_caisse = 0;
        }

        $html .= '<input type="hidden" name="id_caisse" value="' . $id_caisse . '"/>';

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
                $montant_ttc = (float) $facture->dol_object->total_ttc;
                $paid = (float) $facture->dol_object->getSommePaiement();
                $paid += (float) $facture->dol_object->getSumCreditNotesUsed();
                $paid += (float) $facture->dol_object->getSumDepositsUsed();
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

    public function create(&$warnings = array())
    {
        $errors = array();

        global $db, $user, $conf;
        $caisse = null;
        $id_caisse = 0;
        $account = null;
        $use_caisse = false;

        $is_rbt = BimpTools::getValue('is_rbt', null);

        if (is_null($is_rbt)) {
            return array('Type d\'opération absent');
        }

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

        // Check montants: 
        $total_to_pay = (float) BimpTools::getValue('total_to_pay', 0);
        $total_avoirs = (float) BimpTools::getValue('total_avoirs', 0);
        $total_paid = (float) BimpTools::getValue('total_paid_amount');

        $avoirs = json_decode(BimpTools::getValue('avoirs_amounts', ''), true);
        $total_factures_versements = 0;

        if ((is_null($total_paid) || !$total_paid) && (is_null($avoirs) || !count($avoirs))) {
            $errors[] = 'Montant total payé absent';
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
                    $paid = (float) $factures[$id_facture]->dol_object->getSommePaiement();
                    $paid += (float) $factures[$id_facture]->dol_object->getSumCreditNotesUsed();
                    $paid += (float) $factures[$id_facture]->dol_object->getSumDepositsUsed();

                    if (!$is_rbt) {
                        foreach ($avoirs as $avoir) {
                            if ($avoir['input_name'] === 'amount_' . $i) {
                                $avoir_used += (float) $avoir['amount'];
                            }
                        }
                    }

                    $to_pay = abs($factures[$id_facture]->dol_object->total_ttc) - abs($paid);
                    $diff = $to_pay - $avoir_used - $amount;

                    if ($diff > -0.01 && $diff < 0.01) {
                        $amount = (abs($factures[$id_facture]->dol_object->total_ttc) - abs($paid) - $avoir_used); // Eviter les problèmes d'arrondis
                        $diff = 0;
                    }

                    if ($diff < 0) {
                        if ($is_rbt) {
                            $errors[] = 'Il n\'est pas possible d\'enregistrer des sommes supérieures aux montants dûs pour les remboursements';
                            break;
                        }

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

//        $total_factures_versements = round($total_factures_versements, 2);
//        $total_paid = round($total_paid, 2);
//        $total_factures_versements = round($total_factures_versements, 2);
//        if ($total_factures_versements > ($total_paid + 0.009999999)) {
//            $errors[] = 'Le champ "Somme totale versée" (' . $total_paid . ') est inférieur au total des réglements des factures (' . $total_factures_versements . ')';
//            return $errors;
//        }

        if ($total_factures_versements > 0) {
            $errors = parent::create($warnings);
        }

        if (!count($errors)) {
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
