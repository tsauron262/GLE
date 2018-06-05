<?php

class Bimp_Facture extends BimpObject
{

    public static $status_list = array(
        0 => array('label' => 'Brouillon', 'icon' => 'file-text', 'classes' => array('warning')),
        1 => array('label' => 'Validée', 'icon' => 'check', 'classes' => array('info')),
        2 => array('label' => 'Fermée', 'icon' => 'check', 'classes' => array('success')),
        3 => array('label' => 'Abandonnée', 'icon' => 'times-circle', 'classes' => array('danger')),
    );

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

    public function createFromCommande(Commande $commande)
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
        $this->dol_object->note_private = $commande->note_private;
        $this->dol_object->note_public = $commande->note_public;

        $this->dol_object->origin = $commande->element;
        $this->dol_object->origin_id = $commande->id;

        // get extrafields from original line
        $commande->fetch_optionals($commande->id); // todo: suppr.

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
    
    
}
