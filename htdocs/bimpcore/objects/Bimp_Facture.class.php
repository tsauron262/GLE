<?php

class Bimp_Facture extends BimpObject
{

    public static $status_list = array(
        0 => array('label' => 'Brouillon', 'icon' => 'file-text', 'classes' => array('warning')),
        1 => array('label' => 'Validée', 'icon' => 'check', 'classes' => array('info')),
        2 => array('label' => 'Fermée', 'icon' => 'check', 'classes' => array('success')),
        3 => array('label' => 'Abandonnée', 'icon' => 'times-circle', 'classes' => array('danger')),
    );

    public function isDeletable()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        // Suppression autorisée seulement pour les brouillons:
        if ((int) $this->getData('fk_statut') > 0) {
            return 0;
        }

        // Si facture BL, on interdit la suppression s'il existe un facture hors expédition pour la commande:
        $rows = (int) $this->db->getRows('br_commande_shipment', '`id_facture` = ' . (int) $this->id, null, 'object', array('id', 'id_commande_client'));
        if (!is_null($rows) && count($rows)) {
            foreach ($rows as $row) {
                $id_facture = $this->db->getValue('commande', 'id_facture', '`rowid` = ' . (int) $row->id_commande_client);
                if (!is_null($id_facture) && (int) $id_facture) {
                    return 0;
                }
            }
        }

        return 1;
    }

    public function onDelete()
    {
        $this->db->update('br_commande_shipment', array(
            'status' => 1
                ), '`id_facture` = ' . (int) $this->id . ' AND `status` = 4');

        $this->db->update('br_commande_shipment', array(
            'id_facture' => 0
                ), '`id_facture` = ' . (int) $this->id);

        $this->db->update('commande', array(
            'id_facture' => 0
                ), '`id_facture` = ' . (int) $this->id);
    }

    public function displayPaid()
    {
        if ($this->isLoaded()) {
            $paid = $this->dol_object->getSommePaiement();
            return BimpTools::displayMoneyValue($paid, 'EUR');
        }

        return '';
    }

    public function displayPDFButton()
    {
        $ref = $this->getData('facnumber');

        if ($ref) {
            $file = DOL_DATA_ROOT . '/facture/' . $ref . '/' . $ref . '.pdf';
            if (file_exists($file)) {
                $url = DOL_URL_ROOT . '/document.php?modulepart=facture&file=' . htmlentities($ref . '/' . $ref . '.pdf');
                $onclick = 'window.open(\'' . $url . '\');';
                $button = '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
                $button .= '<i class="fas fa5-file-pdf iconLeft"></i>';
                $button .= $ref . '.pdf</button>';
                return $button;
            }
        }

        return $button;
    }

    public function createFromCommande(Commande $commande, $id_account = 0, $public_note = '', $private_note = '')
    {
        global $user, $hookmanager;
        $this->reset();
        $error = 0;
        $this->dol_object->date = dol_now();
        $this->dol_object->source = 0;
        $this->dol_object->socid = $commande->socid;
        $this->dol_object->fk_project = $commande->fk_project;
        $this->dol_object->cond_reglement_id = $commande->cond_reglement_id;
        $this->dol_object->mode_reglement_id = $commande->mode_reglement_id;
        $this->dol_object->availability_id = $commande->availability_id;
        $this->dol_object->demand_reason_id = $commande->demand_reason_id;
        $this->dol_object->date_livraison = $commande->date_livraison;
        $this->dol_object->fk_delivery_address = $commande->fk_delivery_address;
        $this->dol_object->contact_id = $commande->contactid;
        $this->dol_object->ref_client = $commande->ref_client;
        $this->dol_object->note_private = $private_note;
        $this->dol_object->note_public = $public_note;

        $this->dol_object->origin = $commande->element;
        $this->dol_object->origin_id = $commande->id;

        $this->dol_object->fk_account = (int) $id_account;

        // get extrafields from original line
//        $commande->fetch_optionals($commande->id);

        foreach ($commande->array_options as $options_key => $value)
            $this->dol_object->array_options[$options_key] = $value;

        // Possibility to add external linked objects with hooks
        $this->dol_object->linked_objects[$this->dol_object->origin] = $this->dol_object->origin_id;
        if (!empty($commande->other_linked_objects) && is_array($commande->other_linked_objects)) {
            $this->dol_object->linked_objects = array_merge($this->dol_object->linked_objects, $commande->other_linked_objects);
        }

        $ret = $this->dol_object->create($user);

        foreach ($commande->lines as $i => $line) {
            $marginInfos = getMarginInfos($line->subprice, $line->remise_percent, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_fournprice, $line->pa_ht);
            $tva_rate = ((float) $line->tva_tx / 100);

            $fk_product = $line->fk_product;
            $desc = $line->desc;
            $qty = $line->qty;
            $pu_ht = (float) $line->subprice;
            $txtva = $line->tva_tx;
            $remise_percent = (float) $line->remise_percent;
            $txlocaltax1 = $line->localtax1_tx;
            $txlocaltax2 = $line->localtax2_tx;
            $price_base_type = 'HT';
            $date_start = $line->date_start;
            $date_end = $line->date_end;
            $ventil = 0;
            $info_bits = $line->info_bits;
            $fk_remise_except = $line->fk_remise_except;
            $pu_ttc = (float) $line->subprice * (1 + $tva_rate);
            $type = Facture::TYPE_STANDARD;
            $rang = $line->rang;
            $special_code = 0;
            $origin = '';
            $origin_id = 0;
            $fk_parent_line = $line->fk_parent_line;
            $fk_fournprice = $line->fk_fournprice;
            $pa_ht = $marginInfos[0];
            $label = $line->label;
            $array_options = $line->array_options;
            $situation_percent = 100;
            $fk_prev_id = '';
            $fk_unit = $line->fk_unit;

            $this->dol_object->addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $date_start, $date_end, $ventil, $info_bits, $fk_remise_except, $price_base_type, $pu_ttc, $type, $rang, $special_code, $origin, $origin_id, $fk_parent_line, $fk_fournprice, $pa_ht, $label, $array_options, $situation_percent, $fk_prev_id, $fk_unit);
        }

        if ($ret > 0) {
            $this->fetch($this->dol_object->id);

            // Actions hooked (by external module)
            $hookmanager->initHooks(array('invoicedao'));

            $parameters = array('objFrom' => $commande);
            $action = '';
            $reshook = $hookmanager->executeHooks('createFrom', $parameters, $this->dol_object, $action);    // Note that $action and $commande may have been modified by some hooks
            if ($reshook < 0)
                $error++;

            if (!$error) {
                return 1;
            } else
                return -1;
        } else
            return -1;
    }

    public function convertToRemise()
    {
        if (!$this->isLoaded()) {
            return array('ID de la facture absent');
        }
        global $langs, $user;

        $langs->load('bills');
        $langs->load('companies');
        $langs->load('compta');
        $langs->load('products');
        $langs->load('banks');
        $langs->load('main');

        $errors = array();
        $db = $this->db->db;

        $this->dol_object->fetch_thirdparty();

        // Check if there is already a discount (protection to avoid duplicate creation when resubmit post)
        $discountcheck = new DiscountAbsolute($db);
        $result = $discountcheck->fetch(0, $this->dol_object->id);

        $canconvert = 0;
        if ($this->dol_object->type == Facture::TYPE_DEPOSIT && $this->dol_object->paye == 1 && empty($discountcheck->id))
            $canconvert = 1; // we can convert deposit into discount if deposit is payed completely and not already converted (see real condition into condition used to show button converttoreduc)
        if (($this->dol_object->type == Facture::TYPE_CREDIT_NOTE || $this->dol_object->type == Facture::TYPE_STANDARD) && $this->dol_object->paye == 0 && empty($discountcheck->id))
            $canconvert = 1; // we can convert credit note into discount if credit note is not payed back and not already converted and amount of payment is 0 (see real condition into condition used to show button converttoreduc)
        if ($canconvert) {
            $db->begin();

            $amount_ht = $amount_tva = $amount_ttc = array();

            // Loop on each vat rate
            $i = 0;
            foreach ($this->dol_object->lines as $line) {
                if ($line->total_ht != 0) {  // no need to create discount if amount is null
                    $amount_ht[$line->tva_tx] += $line->total_ht;
                    $amount_tva[$line->tva_tx] += $line->total_tva;
                    $amount_ttc[$line->tva_tx] += $line->total_ttc;
                    $i ++;
                }
            }

            // Insert one discount by VAT rate category
            $discount = new DiscountAbsolute($db);
            if ($this->dol_object->type == Facture::TYPE_CREDIT_NOTE)
                $discount->description = '(CREDIT_NOTE)';
            elseif ($this->dol_object->type == Facture::TYPE_DEPOSIT)
                $discount->description = '(DEPOSIT)';
            elseif ($this->dol_object->type == Facture::TYPE_STANDARD || $this->dol_object->type == Facture::TYPE_REPLACEMENT || $this->dol_object->type == Facture::TYPE_SITUATION)
                $discount->description = '(EXCESS RECEIVED)';
            else {
                return array($langs->trans('CantConvertToReducAnInvoiceOfThisType'));
            }

            $discount->fk_soc = $this->dol_object->socid;
            $discount->fk_facture_source = $this->dol_object->id;

            if ($this->dol_object->type == Facture::TYPE_STANDARD || $this->dol_object->type == Facture::TYPE_REPLACEMENT || $this->dol_object->type == Facture::TYPE_SITUATION) {
                // If we're on a standard invoice, we have to get excess received to create a discount in TTC without VAT
                $sql = 'SELECT SUM(pf.amount) as total_paiements
						FROM llx_c_paiement as c, llx_paiement_facture as pf, llx_paiement as p
						WHERE pf.fk_facture = ' . $this->dol_object->id . ' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid';

                $resql = $db->query($sql);
                if (!$resql) {
                    return array($db->lasterror());
                }

                $res = $db->fetch_object($resql);
                $total_paiements = $res->total_paiements;

                $discount->amount_ht = $discount->amount_ttc = $total_paiements - $this->dol_object->total_ttc;
                $discount->amount_tva = 0;
                $discount->tva_tx = 0;

                $result = $discount->create($user);
                if ($result < 0) {
                    $msg = 'Echec de la création de la remise client';
                    $sqlError = $db->lasterror();
                    if ($sqlError) {
                        $msg .= ' - ' . $sqlError;
                    }
                    $errors[] = $msg;
                }
            }
            if ($this->dol_object->type == Facture::TYPE_CREDIT_NOTE || $this->dol_object->type == Facture::TYPE_DEPOSIT) {
                foreach ($amount_ht as $tva_tx => $xxx) {
                    $discount->amount_ht = abs($amount_ht[$tva_tx]);
                    $discount->amount_tva = abs($amount_tva[$tva_tx]);
                    $discount->amount_ttc = abs($amount_ttc[$tva_tx]);
                    $discount->tva_tx = abs($tva_tx);

                    $result = $discount->create($user);
                    if ($result < 0) {
                        $msg = 'Echec de la création de la remise client';
                        $sqlError = $db->lasterror();
                        if ($sqlError) {
                            $msg .= ' - ' . $sqlError;
                        }
                        $errors[] = $msg;
                        break;
                    }
                }
            }

            if (count($errors)) {
                $db->rollback();
                return $errors;
            }

            if ($this->dol_object->set_paid($user) >= 0) {
                $db->commit();
            } else {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'enregistrement du statut "payé" pour cette facture');
                $db->rollback();
            }
        }

        return $errors;
    }
}
