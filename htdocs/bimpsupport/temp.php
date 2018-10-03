<?php

class temp
{

    public function functionName($param)
    {        
        
        
        $prop->addline("Prise en charge :  : " . $ref .
                "\n" . "S/N : " . $serial .
                "\n" . "Garantie :
Pour du matériel couvert par Apple, la garantie initiale s'applique.
Pour du matériel non couvert par Apple, la garantie est de 3 mois pour les pièces et la main d'oeuvre.
Les pannes logicielles ne sont pas couvertes par la garantie du fabricant.
Une garantie de 30 jours est appliquée pour les réparations logicielles.
", 0, 1, 0, 0, 0, 0, (!is_null($client) ? $client->dol_object->remise_percent : 0), 'HT', 0, 0, 3);


        // Ajout du service prioritaire:
        if ((int) $this->getData('prioritaire')) {
            require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.product.class.php");
            $prodF = new ProductFournisseur($this->db->db);
            $prodF->fetch(self::$idProdPrio);
            $prodF->tva_tx = ($prodF->tva_tx > 0) ? $prodF->tva_tx : 0;
            $prodF->find_min_price_product_fournisseur($prodF->id, 1);

            $prop->addline($prodF->description, $prodF->price, 1, $prodF->tva_tx, 0, 0, $prodF->id, 0, 'HT', null, null, null, null, null, null, $prodF->product_fourn_price_id, $prodF->fourn_price);
        }

        //Ajout diagnostique
        if ($this->getData('diagnostic') != "") {
            $prop->addline("Diagnostic : " . $this->getData('diagnostic'), 0, 1, 0, 0, 0, 0, $client->dol_object->remise_percent, 'HT', 0, 0, 3);
        }

        $garantieHt = $garantieTtc = $garantiePa = 0;

        //Ajout des prod apple
        foreach ($this->getChildrenObjects("products") as $prod) {
            $prodG = new Product($this->db->db);
            $prodG->fetch($prod->getData("id_product"));
            $remise = $prod->getData("remise");
            $coefRemise = (100 - $remise) / 100;
            require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.product.class.php");
            $prodF = new ProductFournisseur($this->db->db);
            $prodF->find_min_price_product_fournisseur($prodG->id, $prod->getData("qty"));
            $prop->addline($prodG->description, $prodG->price, $prod->getData("qty"), $prodG->tva_tx, 0, 0, $prodG->id, $client->dol_object->remise_percent + $remise, 'HT', null, null, null, null, null, null, $prodF->product_fourn_price_id, $prodF->fourn_price);
            if (!$prod->getData("out_of_warranty")) {
                $garantieHt += $prodG->price * $prod->getData("qty") * $coefRemise;
                $garantieTtc += $prodG->price * $prod->getData("qty") * ($prodG->tva_tx / 100) * $coefRemise;
                $garantiePa += $prodF->fourn_price * $prod->getData("qty");
            } else
                $this->allGarantie = false;
        }

        //Ajout des Prod non Apple

        foreach ($this->getChildrenObjects("apple_parts") as $prod) {
            $tva = 20;
            $price = ($prod->getData("no_order") || $prod->getData("exchange_price") < 1) ? $prod->getData("stock_price") : $prod->getData("exchange_price");
            $price2 = BS_ApplePart::convertPrix($price, $prod->getData("part_number"), $prod->getData("label"));
            $label = $prod->getData("part_number") . " - " . $prod->getData("label");
            $label .= ($prod->getData("no_order")) ? " APPRO" : "";
            $prop->addline($label, $price2, $prod->getData("qty"), $tva, 0, 0, 0, $client->dol_object->remise_percent, 'HT', null, null, null, null, null, null, null, $price);
            if (!$prod->getData("out_of_warranty")) {
                $garantieHt += $price2 * $prod->getData("qty");
                $garantieTtc += $price2 * $prod->getData("qty") * ($tva / 100);
                $garantiePa += $price * $prod->getData("qty");
            } else
                $this->allGarantie = false;
        }

        //Ajout garantie
        if ($garantieHt > 0) {
            $tva = 100 * $garantieTtc / $garantieHt;
            $prop->addline("Garantie", -($garantieHt), 1, $tva, 0, 0, 0, $client->dol_object->remise_percent, 'HT', 0, 0, 1, -1, 0, 0, 0, -$garantiePa);
        }

        // Ajout infos supplémentaires:
        if ($this->getData('extra_infos') != "") {
            $prop->addline($this->getData('extra_infos'), 0, 1, 0, 0, 0, 0, $client->dol_object->remise_percent, 'HT', 0, 0, 3);
        }

        require_once(DOL_DOCUMENT_ROOT . "/core/modules/propale/modules_propale.php");
        $prop->fetch($prop->id);
        $prop->generateDocument(self::$propal_model_pdf, $langs);

        return $errors;
    }
}
