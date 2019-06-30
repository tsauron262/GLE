<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/Bimp_Paiement.class.php';

class Bimp_PaiementFourn extends Bimp_Paiement
{

    public static $paiement_factures_types = array(FactureFournisseur::TYPE_STANDARD, FactureFournisseur::TYPE_DEPOSIT, FactureFournisseur::TYPE_REPLACEMENT, FactureFournisseur::TYPE_SITUATION);
    public static $rbt_factures_type = array(FactureFournisseur::TYPE_CREDIT_NOTE);
    public $useCaisse = false;

    public function __construct($module, $object_name)
    {
        BimpObject::__construct($module, $object_name);
    }

    // Getters: 

    public function getFourn()
    {
        return BimpTools::getPostFieldValue('id_fourn', 0);
    }

    public function getAmountFromFacture()
    {
        $id_facture = (int) BimpTools::getValue('fields/id_facture', 0);

        if ($id_facture) {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', $id_facture);
            if (BimpObject::objectLoaded($facture)) {
                return (float) round($facture->getRemainToPay(), 2);
            }
        }

        return 0;
    }

    // Affichages: 

    public function DisplayAccount()
    {
        if ($this->isLoaded() && isset($this->dol_object->bank_account) && (int) $this->dol_object->bank_account) {
            BimpTools::loadDolClass('compta/bank', 'account');
            $account = new Account($this->db->db);
            if ($account->fetch((int) $this->dol_object->bank_account) > 0) {
                return $account->getNomUrl(1);
            } else {
                return BimpRender::renderAlerts('Le compte bancaire d\'ID ' . $this->dol_object->bank_account . ' n\'existe pas');
            }
        }

        return '';
    }

    // Rendus HTML: 

    public function renderCaisseInput()
    {
        return '';
    }

    public function renderFacturesAmountsInputs()
    {
        $id_fourn = (int) $this->getFourn();

        if (!$id_fourn) {
            return BimpRender::renderAlerts('Fournisseur absent');
        }

        $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFourn');
        if (BimpTools::isSubmit('param_values/fields/id_facture')) {
            $list[] = array('rowid' => (int) BimpTools::getValue('param_values/fields/id_facture', 0));
        } else {
            $list = $facture->getList(array(
                'fk_soc'    => $id_fourn,
                'paye'      => 0,
                'type'      => FactureFournisseur::TYPE_STANDARD,
                'fk_statut' => 1
                    ), null, null, 'id', 'asc', 'array', array('rowid'));
        }

        if (!count($list)) {
            return BimpRender::renderAlerts('Aucune facture à payer trouvée pour ce fournisseur', 'warning');
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

        $html .= '<div id="factures_payments_' . $rand . '" class="factures_payment_container">';

        $html .= '<div style="margin: 15px 0; text-align: right">';
        $html .= '<span style="font-weight: bold;">Somme totale versée:&nbsp;&nbsp;</span>';
        $html .= BimpInput::renderInput('text', 'total_paid_amount', 0, $options);
        $html .= '</div>';

        $options['extra_class'] = 'facture_payment_input';
        $options['avoirs'] = 0;

        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<th>Facture</th>';
        $html .= '<th>Date</th>';
        $html .= '<th style="text-align: center;">Montant TTC</th>';
        $html .= '<th style="text-align: center;">Payé</th>';
        $html .= '<th style="text-align: center;">Reste à régler</th>';
        $html .= '<th style="text-align: center;">Avoirs utilisés</th>';
        $html .= '<th style="text-align: center;">Montant règlement</th>';
        $html .= '</thead>';

        $html .= '<tbody>';

        $i = 1;

        $total_ttc = 0;
        $total_paid = 0;
        $total_to_pay = 0;

        $at_least_one = false;
        foreach ($list as $item) {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', (int) $item['rowid']);
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
                }

                $at_least_one = true;

                $options['data']['to_pay'] = $to_pay;

                if ($montant_ttc < 0) {
                    $options['data']['min'] = $montant_ttc;
                    $options['data']['max'] = 0;
                } else {
                    $options['data']['min'] = 0;
                    $options['data']['max'] = $montant_ttc;
                }

                $total_ttc += $montant_ttc;
                $total_paid += $paid;
                $total_to_pay += $to_pay;

                $DT = new DateTime($this->db->db->iDate($facture->dol_object->date));

                $html .= '<tr class="facture_payment_row">';
                $html .= '<td>' . $facture->dol_object->getNomUrl(1) . '</td>';
                $html .= '<td>' . $DT->format('d / m / Y') . '</td>';
                $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($montant_ttc, 'EUR') . '</td>';
                $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($paid, 'EUR') . '</td>';
                $html .= '<td style="text-align: center; font-weight: bold">' . BimpTools::displayMoneyValue($to_pay, 'EUR') . '</td>';
                $html .= '<td class="facture_avoirs" style="text-align: center; font-weight: bold"></td>';
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
            return BimpRender::renderAlerts('Aucune facture à payer trouvée pour ce client', 'warning');
        }

        $html .= '</tbody>';

        if (count($list) > 1) {
            $html .= '<tfoot>';
            $html .= '<tr>';
            $html .= '<td colspan="2">Total TTC</td>';
            $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($total_ttc, 'EUR') . '</td>';
            $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($total_paid, 'EUR') . '</td>';
            $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($total_to_pay, 'EUR') . '</td>';
            $html .= '<td></td>';
            $html .= '<td></td>';
            $html .= '</tr>';
            $html .= '</tfoot>';
        }

        $html .= '</table>';

        $html .= '<input type="hidden" name="total_to_pay" value="' . $total_to_pay . '"/>';
        $html .= '<input type="hidden" name="total_avoirs" value="0"/>';
        $html .= '<input type="hidden" name="avoirs_amounts" value=""/>';

        $html .= '<div class="total_payments_container" style="font-weight: bold; margin: 20px 0 5px; padding: 8px 12px; color: #3C3C3C; font-size: 14px; text-align: center; background-color: #D8D8D8">';
        $html .= 'Total paiements:&nbsp;<span class="total_payments">0,00 &euro;</span>';
        $html .= '</div>';
        $html .= '<div class="total_avoirs_container" style="display: none; font-weight: bold; margin: 5px 0; padding: 8px 12px; color: #fff; font-size: 14px; text-align: center; background-color: #D2AF00">';
        $html .= 'Total avoirs utilisés:&nbsp;<span class="total_avoirs">0,00 &euro;</span>';
        $html .= '</div>';
        $html .= '<div class="rest_to_pay_container" style="font-weight: bold; margin: 5px 0; padding: 8px 12px; color: #fff; font-size: 14px; text-align: center; background-color: #A00000">';
        $html .= 'Reste à régler:&nbsp;<span class="rest_to_pay">' . BimpTools::displayMoneyValue($total_to_pay, 'EUR') . '</span>';
        $html .= '</div>';
        $html .= '<div class="to_return_container" style="font-weight: bold; margin: 5px 0 10px; padding: 8px 12px; color: #fff; font-size: 14px; text-align: center; background-color: #348B41">';
        $html .= 'Rendu monnaie:&nbsp;<span class="to_return">0,00 &euro;</span>';
        $html .= '</div>';

        $html .= '<script type="text/javascript">';
        $html .= 'onClientFacturesPaymentsInputsLoaded($(\'#factures_payments_' . $rand . '\'))';
        $html .= '</script>';
        $html .= '</div>';

        return $html;
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        global $db, $user, $conf;

        $id_account = (int) BimpTools::getPostFieldValue('id_account', 0);
        $account = null;

        if ($id_account) {
            BimpTools::loadDolClass('compta/bank', 'account');
            $account = new Account($db);
            $account->fetch((int) BimpCore::getConf('bimpcaisse_id_default_account'));

            if (!BimpObject::objectLoaded($account)) {
                $errors[] = 'Compte bancaire invalide';
            }
        } else {
            $errors[] = 'Compte bancaire absent';
        }

        $total_to_pay = (float) BimpTools::getValue('total_to_pay', 0);
        $total_avoirs = (float) BimpTools::getValue('total_avoirs', 0);
        $total_paid = (float) BimpTools::getValue('total_paid_amount');

        $avoirs = json_decode(BimpTools::getValue('avoirs_amounts', ''), true);
        $total_factures_versements = 0;

        if ((is_null($total_paid) || !$total_paid) && (is_null($avoirs) || !count($avoirs))) {
            $errors[] = 'Montant total payé absent';
        }

        $type_paiement = $this->db->getValue('c_paiement', 'code', '`id` = ' . (int) $this->dol_object->paiementid);
        if (is_null($type_paiement) || !(string) $type_paiement) {
            $errors[] = 'Mode de paiement invalide';
        } elseif (($total_paid + $total_avoirs) > $total_to_pay && $type_paiement !== 'LIQ') {
            $errors[] = 'Le versement d\'une somme supérieure au total des factures n\'est possible que pour un paiement en espèces';
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

        // Calcul du total payé par facture. 
        while (BimpTools::isSubmit('amount_' . $i)) {
            $id_facture = (int) BimpTools::getValue('amount_' . $i . '_id_facture', 0);
            $amount = (float) BimpTools::getValue('amount_' . $i, 0);

            if ($id_facture <= 0) {
                $errors[] = 'ID facture invalide (ligne ' . $i . ')';
            } elseif ($amount !== 0) {
                $factures[$id_facture] = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', $id_facture);

                if (!$factures[$id_facture]->isLoaded()) {
                    $errors[] = 'Facture d\'ID ' . $id_facture . ' inexistante';
                    unset($factures[$id_facture]);
                } else {
                    $avoir_used = 0;
                    $paid = (float) $factures[$id_facture]->dol_object->getSommePaiement();
                    $paid += (float) $factures[$id_facture]->dol_object->getSumCreditNotesUsed();
                    $paid += (float) $factures[$id_facture]->dol_object->getSumDepositsUsed();
                    $paid = round($paid, 2);

                    foreach ($avoirs as $avoir) {
                        if ($avoir['input_name'] === 'amount_' . $i) {
                            $avoir_used += (float) $avoir['amount'];
                        }
                    }

                    $diff = $factures[$id_facture]->dol_object->total_ttc - $paid - $avoir_used - $amount;
                    if ($diff > -0.01 && $diff < 0.01) {
                        $amount = ($factures[$id_facture]->dol_object->total_ttc - $paid - $avoir_used); // Eviter les problèmes d'arrondis
                    }

                    $this->dol_object->amounts[$id_facture] = $amount;
                    $total_factures_versements += $amount;
                }
            }
            $i++;
        }

        $total_factures_versements = round($total_factures_versements, 2);
        $total_paid = round($total_paid, 2);

        if ($total_factures_versements > $total_paid) {
            $errors[] = 'Le champ "Somme totale versée" (' . $total_paid . ') est inférieur au total des réglements des factures (' . $total_factures_versements . ')';
            return $errors;
        }

        if ($total_factures_versements > 0) {
            $errors = parent::create($warnings);
        }

        if (!count($errors)) {
            // Insertion des avoirs éventuels: 
            $i = 1;
            while (BimpTools::isSubmit('amount_' . $i)) {
                $id_facture = (int) BimpTools::getValue('amount_' . $i . '_id_facture', 0);
                if (isset($factures[$id_facture]) && $factures[$id_facture]->isLoaded()) {
                    foreach ($avoirs as $avoir) {
                        if ($avoir['input_name'] === 'amount_' . $i) {
                            BimpTools::resetDolObjectErrors($factures[$id_facture]->dol_object);
                            if ($factures[$id_facture]->dol_object->insert_discount((int) $avoir['id_discount']) <= 0) {
                                $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($factures[$id_facture]->dol_object), 'Echec de l\'insertion de l\'avoir client d\'ID ' . $avoir['id_discount']);
                            }
                        }
                    }
                }
                $i++;
            }

            if ($this->isLoaded()) {
                // Ajout du paiement au compte financier:
                if (!empty($conf->banque->enabled)) {
                    $this->dol_object->error = '';
                    $this->dol_object->errors = array();
                    $label = 'Paiement facture fournisseur';

                    if ($this->dol_object->addPaymentToBank($user, 'payment', $label, $this->dol_object->fk_account, $nom_emetteur, $banque_emetteur) <= 0) {
                        $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'ajout du paiement au compte bancaire "' . $account->bank . '"');
                    }
                }
            }

            foreach ($factures as $id_facture => $facture) {
                $facture->checkIsPaid();
            }
        }

        return $errors;
    }

    public function updateDolObject(&$errors = array())
    {
        return 1;
    }
}
