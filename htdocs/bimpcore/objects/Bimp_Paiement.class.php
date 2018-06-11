<?php

class Bimp_Paiement extends BimpObject
{

    public function renderCaisseInput()
    {
        global $user;
        $caisse = BimpObject::getInstance('bimpcaisse', 'BC_Caisse');
        $id_caisse = (int) $caisse->getUserCaisse((int) $user->id);

        $html = '';
        if ($id_caisse) {
            $caisse->fetch($id_caisse);
            if (!BimpObject::objectLoaded($caisse)) {
                $html .= BimpRender::renderAlerts('Erreur: la caisse à laquelle vous êtes connecté semble ne plus exister');
                $id_caisse = 0;
            } else {
                $html .= $caisse->getData('name');
            }
        }

        if (!$id_caisse) {
            $html .= BimpRender::renderAlerts('Vous n\'êtes connecté à aucune caisse', 'warning');
            $html .= '<div style="text-align: center; margin: 15px 0">';
            $url = DOL_URL_ROOT . '/bimpcaisse/index.php';
            $html .= '<a class="btn btn-default" href="' . $url . '" target="_blank"><i class="fa fa-external-link iconLeft"></i>Se connecter à une caisse</a>';
            $html .= '</div>';
        }

        $html .= '<input type="hidden" name="id_caisse" value="' . $id_caisse . '"/>';

        return $html;
    }

    public function getAmountFromFacture()
    {
        $id_facture = (int) BimpTools::getValue('fields/id_facture', 0);

        if ($id_facture) {
            $facture = BimpObject::getInstance('bimpcore', 'Bimp_Facture', $id_facture);
            if (BimpObject::objectLoaded($facture)) {
                return (float) round(($facture->dol_object->total_ttc - $facture->dol_object->getSommePaiement()), 2);
            }
        }

        return 0;
    }

    public function renderFacturesAmountsInputs()
    {
        $id_client = (int) BimpTools::getValue('fields/id_client');
        if (!$id_client) {
            return BimpRender::renderAlerts('Client absent');
        }
        $facture = BimpObject::getInstance('bimpcore', 'Bimp_Facture');
        $list = $facture->getList(array(
            'fk_soc'    => $id_client,
            'paye'      => 0,
            'fk_statut' => array(
                'operator' => '>',
                'value'    => 0
            )
                ), null, null, 'id', 'asc', 'array', array('rowid'));
        if (!count($list)) {
            return BimpRender::renderAlerts('Aucune facture à payer trouvée pour ce client');
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

        $html .= '<div id="factures_payments_' . $rand . '">';

        $html .= '<div style="margin: 15px 0; text-align: right">';
        $html .= '<span style="font-weight: bold;">Somme totale versée:&nbsp;&nbsp;</span>';
        $html .= BimpInput::renderInput('text', 'total_paid_amount', 0, $options);
        $html .= '</div>';

        $options['extra_class'] = 'facture_payment_input';

        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<th>Facture</th>';
        $html .= '<th>Date</th>';
        $html .= '<th style="text-align: center;">Montant TTC</th>';
        $html .= '<th style="text-align: center;">Payé</th>';
        $html .= '<th style="text-align: center;">Reste à régler</th>';
        $html .= '<th style="text-align: center;">Montant règlement</th>';
        $html .= '</thead>';

        $html .= '<tbody>';

        $i = 1;

        $total_ttc = 0;
        $total_paid = 0;
        $total_to_pay = 0;

        foreach ($list as $item) {
            if ($facture->fetch((int) $item['rowid'])) {
                $montant_ttc = round((float) $facture->dol_object->total_ttc, 2);
                $paid = round((float) $facture->dol_object->getSommePaiement(), 2);
                $to_pay = $montant_ttc - $paid;
                if ($to_pay > -0.01 && $to_pay < 0.01) {
                    continue;
                }

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

                $html .= '<tr>';
                $html .= '<td>' . $facture->dol_object->getNomUrl(1) . '</td>';
                $html .= '<td>' . $DT->format('d / m / Y') . '</td>';
                $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($montant_ttc, 'EUR') . '</td>';
                $html .= '<td style="text-align: center;">' . BimpTools::displayMoneyValue($paid, 'EUR') . '</td>';
                $html .= '<td style="text-align: center; font-weight: bold">' . BimpTools::displayMoneyValue($to_pay, 'EUR') . '</td>';
                $html .= '<td style="text-align: right;">';
                $html .= '<input type="hidden" name="amount_' . $i . '_id_facture" value="' . $facture->id . '"/>';
                $html .= BimpInput::renderInput('text', 'amount_' . $i, '', $options);
                $html .= '</td>';
                $html .= '</tr>';

                unset($DT);
                $i++;
            }
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
            $html .= '</tr>';
            $html .= '</tfoot>';
        }

        $html .= '</table>';

        $html .= '<input type="hidden" name="total_to_pay" value="' . $total_to_pay . '"/>';

        $html .= '<div class="total_payments_container" style="font-weight: bold; margin: 20px 0 5px; padding: 8px 12px; color: #3C3C3C; font-size: 14px; text-align: center; background-color: #D8D8D8">';
        $html .= 'Total paiements:&nbsp;<span class="total_payments">0,00 &euro;</span>';
        $html .= '</div>';
        $html .= '<div class="rest_to_pay_container" style="font-weight: bold; margin: 5px 0; padding: 8px 12px; color: #fff; font-size: 14px; text-align: center; background-color: #A00000">';
        $html .= 'Reste à régler:&nbsp;<span class="rest_to_pay">' . BimpTools::displayMoneyValue($total_to_pay, 'EUR') . '</span>';
        $html .= '</div>';
        $html .= '<div class="to_return_container" style="font-weight: bold; margin: 5px 0 10px; padding: 8px 12px; color: #fff; font-size: 14px; text-align: center; background-color: #348B41">';
        $html .= 'A rendre:&nbsp;<span class="to_return">0,00 &euro;</span>';
        $html .= '</div>';

        $html .= '<script type="text/javascript">';
        $html .= 'onClientFacturesPaymentsInputsLoaded($(\'#factures_payments_' . $rand . '\'))';
        $html .= '</script>';
        $html .= '</div>';

        return $html;
    }

    public function validatePost()
    {
        $errors = parent::validatePost();

        if (BimpTools::isSubmit('id_mode_paiement')) {
            $this->dol_object->paiementid = (int) BimpTools::getValue('id_mode_paiement', 0);
        }
        if (BimpTools::isSubmit('id_account')) {
            $this->dol_object->fk_account = (int) BimpTools::getValue('id_account', 0);
        }

        if (!(int) $this->dol_object->paiementid) {
            $errors[] = 'Mode de paiement absent';
        }

        if (!(int) $this->dol_object->fk_account) {
            $errors[] = 'Compte financier absent';
        }

        return $errors;
    }

    public function create(&$warnings = array())
    {
        $errors = array();

        global $user, $conf;

        $id_caisse = (int) BimpTools::getValue('id_caisse');
        if (!$id_caisse) {
            $errors[] = 'Caisse absente';
        } else {
            $caisse = BimpObject::getInstance('bimpcaisse', 'BC_Caisse', $id_caisse);
            if (!BimpObject::objectLoaded($caisse)) {
                $errors[] = 'La caisse d\'ID ' . $id_caisse . ' n\'existe pas';
            } elseif (!(int) $caisse->getData('status')) {
                $errors[] = 'La caisse "' . $caisse->getData('name') . '" est fermée';
            } elseif ((int) $caisse->getData('id_current_user') !== (int) $user->id) {
                $errors[] = 'Vous n\'êtes pas connecté à la caisse sélectionnée';
            }
        }

        $total_paid = (float) BimpTools::getValue('total_paid_amount');
        $total_factures = 0;

        if (is_null($total_paid) || !$total_paid) {
            $errors[] = 'Montant total payé absent';
        }

        $type_paiement = $this->db->getValue('c_paiement', 'code', '`id` = ' . (int) $this->dol_object->paiementid);
        if (is_null($type_paiement) || !(string) $type_paiement) {
            $errors[] = 'Mode paiement invalide';
        }

        if (count($errors)) {
            return $errors;
        }

        $this->dol_object->datepaye = dol_now();

        $i = 1;

        BimpTools::loadDolClass('compta/facture', 'facture');
        $facture = new Facture($this->db->db);

        while (BimpTools::isSubmit('amount_' . $i)) {
            $id_facture = (int) BimpTools::getValue('amount_' . $i . '_id_facture', 0);
            $amount = (float) BimpTools::getValue('amount_' . $i, 0);

            if ($id_facture <= 0) {
                $errors[] = 'ID facture invalide (ligne ' . $i . ')';
            } elseif ($amount !== 0) {
                if ($facture->fetch($id_facture) <= 0) {
                    $errors[] = 'Facture d\'ID ' . $id_facture . ' inexistante';
                } else {
                    $diff = $facture->total_ttc - $amount;
                    if ($diff > -0.01 && $diff < 0.01) {
                        $amount = $facture->total_ttc; // Eviter les problèmes d'arrondis
                    }

                    $this->dol_object->amounts[$id_facture] = $amount;
                    $total_factures += $amount;
                }
            }
            $i++;
        }

        $total_factures = round($total_factures, 2);
        $total_paid = round($total_paid, 2);

        if ($total_factures > $total_paid) {
            $errors[] = 'Le champ "Somme totale versée" (' . $total_paid . ') est inférieur au total des réglements des factures (' . $total_factures . ')';
            return $errors;
        }

        $errors = parent::create($warnings);

        if (!count($errors)) {
            // Ajout du paiement au compte financier:
            if (!empty($conf->banque->enabled)) {
                $this->dol_object->error = '';
                $this->dol_object->errors = array();

                $centre = $this->db->getValue('entrepot', 'label', '`rowid` = ' . (int) $caisse->getData('id_entrepot'));
                if (is_null($centre)) {
                    $centre = 'inconnu';
                }
                $label = 'Paiement facture client (Caisse "' . $caisse->getData('name') . '" - Centre "' . $centre . '")';
                if ($this->dol_object->addPaymentToBank($user, 'payment', $label, $this->dol_object->fk_account, '', '') <= 0) { // todo: ajouter nom émetteur et banque émetteur. 
                    $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'ajout du paiement au compte financier');
                }
            }

            // Correction fonds de caisse: 
            $caisse_mvt = 0;
            if ($type_paiement === 'LIQ') {
                $caisse_mvt = $total_factures;
            } elseif ($total_paid > $total_factures) {
                $caisse_mvt = $total_factures - $total_paid;
            }

            if ($caisse_mvt !== 0) {
                $fonds = (float) $caisse->getData('fonds');
                $fonds += $caisse_mvt;
                $caisse->set('fonds', $fonds);
                $update_errors = $caisse->update();
                if (count($update_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($update_errors, 'Echec de la mise à jour du fonds de caisse (Nouveau montant: ' . $fonds . ')');
                }
            }
        }

        return $errors;
    }
    
    public function getDolObjectCreateParams()
    {
        global $user;
        return array($user, 1);
    }
}
