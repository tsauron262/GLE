<?php

class BTC_export_societe extends BTC_export {
    
    const EXPORTED = 1;
    
    public function export(Bimp_Societe $client, $want = 'c', $date_element) {
        
        $file = $this->create_daily_file("tier", $date_element);
        $is_subsidiary = ($client->getData('is_subsidiary') ? true : false);
        $is_salarie = ($client->getData('is_salarie') ? true : false);
        $is_particulier = false;
        $is_client_interco = false;
        $compte_general_411 = '41100000';
        $compte_general_401 = '40100000';
        $attached_commercial = $this->db->getValue('societe_commerciaux', 'fk_user', 'fk_soc = ' . $client->id);
        $country = $this->db->getRow('c_country', '`rowid` = ' . $client->getData('fk_pays'));
        $i_dont_want_this_in_auxiliary_code = ["-", "'", ".", "(", ")", "_", '#', '@', ',', ':', '/', '+', '!', '?', '='];
        $is_client = ($client->getData('client') == 1 || $client->getData('client') == 3) ? true : false;
        $is_fournisseur = ($client->getData('fournisseur') == 1) ? true : false;
        if($attached_commercial) {
            $commercial = $this->getInstance('bimpcore', 'Bimp_User', $attached_commercial);
            $ean = strtoupper($commercial->getData('login'));
        } else {
            $ean = strtoupper('prdirection');
        }
        
        if($client->getData('fk_typent') != 8 && $client->getData('fk_typent') != 0) {
           $auxiliaire_client = "E";
           
        } else {
            $auxiliaire_client = "P";
            $is_particulier = true;
        }
        $auxiliaire_fournisseur = "F";
        $auxiliaire_client .= ($client->getData('zip')) ? substr($client->getData('zip'), 0, 2) : "00";
        
        $nom_societe = strtoupper($this->suppr_accents($client->getData('nom')));
        
        $specials_characters_for_replace = ["'", '"', "-", "(", ")", ".", ";", "/", "!", "_", "+", "="];
        
        foreach ($specials_characters_for_replace as $char) {
            $nom_societe = str_replace($char, "", $nom_societe);
        }

        $auxiliaire_fournisseur .= $this->sizing(str_replace(' ', '', $nom_societe),12);
        
        if($is_particulier) {
            $array_for_client = explode(" ", $nom_societe);
            if(count($array_for_client) > 1) {
                $auxiliaire_client .= substr($array_for_client[0], 0, 10);
                $auxiliaire_client .= substr($array_for_client[1], 0, 3);
                
            } else {
                $auxiliaire_client .= $array_for_client[0];
            }
            $auxiliaire_client = $this->sizing($auxiliaire_client, 14, false, true);
        } else {
            $auxiliaire_client .= $this->sizing(str_replace(' ', '', $nom_societe),10, false, true);
        }
        
        $auxiliaire_client = $this->sizing($auxiliaire_client, 14, false, true);
        $auxiliaire_fournisseur = $this->sizing($auxiliaire_fournisseur, 14, false, true);
        
        $nombre_code_auxiliaire_identique_client = count($this->db->getRows('societe', "`code_compta` LIKE '".$auxiliaire_client."%'"));
        $nombre_code_auxiliaire_identique_fournisseur = count($this->db->getRows('societe', "`code_compta_fournisseur` LIKE '".$auxiliaire_fournisseur."%'"));
        
        $auxiliaire_client .= ($nombre_code_auxiliaire_identique_client < 10) ? "0" . $nombre_code_auxiliaire_identique_client : $nombre_code_auxiliaire_identique_client; 
        $auxiliaire_fournisseur .= ($nombre_code_auxiliaire_identique_fournisseur < 10) ? "0" . $nombre_code_auxiliaire_identique_fournisseur : $nombre_code_auxiliaire_identique_fournisseur;
        
        if ($client->getData('is_subsidiary')) {
            $compte_general_411 = $client->getData('accounting_account');
            $compte_general_401 = $client->getData('accounting_account_fournisseur');
            $is_client_interco = true;
        }
        
        $structure = [
            'fixe' => ['***', 3],
            'journal' => ['CAE', 3],
            'code_auxiliaire' => [$auxiliaire_client, 17],
            'label' => [strtoupper($this->suppr_accents($client->getData('nom'))), 35],
            'nature' => ['CLI', 3],
            'lettrage' => ['X', 1],
            'compte_general' => [$compte_general_411, 17],
            'ean' => [$ean, 17],
            'table_1' => ['AUTO', 17],
            'table_2' => ['', 17],
            'table_3' => [($is_salarie) ? 'SA' : '', 17],
            'table_4' => [($is_particulier) ? "PAR" : "PRO", 17],
            'table_5' => ['', 17],
            'table_6' => ['', 17],
            'table_7' => ['', 17],
            'table_8' => ['', 17],
            'table_9' => ['', 17],
            'table_10' => ['', 17],
            'adresse' => [strtoupper($this->suppr_accents($client->getData('address'))), 35],
            'vide' => ['', 70],
            'code_postal' => [$client->getData('zip'), 9],
            'ville' => [strtoupper($this->suppr_accents($client->getData('town'))), 35],
            'vide_2' => ['', 47],
            'pays' => [strtoupper($country->code_iso), 3],
            'nom_abrege' => [strtoupper(str_replace(' ', '', $this->suppr_accents($client->getData('nom')))), 17],
            'langue' => [strtoupper($country->code), 3],
            'multi_devise' => ['-', 1],
            'devise_tier' => ['EUR', 3],
            'telephone' => [$this->suppr_accents($client->getData('phone')), 25],
            'fax' => ['', 25],
            'regime_tva' => [($country->in_ue == 1) ? $country->code_iso : '', 3],
            'mode_reglement' => ['', 3],
            'vide_3' => ['', 52],
            'siret' => [($country->code == 'FR') ? $client->getData('siret') : $client->getData('idprof4'), 17],
            'ape' => [$client->getData('ape'), 5],
            'prenom' => ['', 35],
            'vide_4' => ['', 70],
            'vide_5' => ['', 75],
            'adresse_mail' => [$this->suppr_accents($client->getData('email')), 54],
            'status_juridique' => [$this->suppr_accents($client->displayJuridicalStatus()), 3],
            'rib' => ['-', 1],
            'tv_encaissement' => ['TM', 3],
            'payeur' => ['', 17],
            'is_payeur' => ['-', 1],
            'avoir' => ['-', 1],
            'vide_6' => ['', 6],
            'conf' => ['0', 1],
            'vide_7' => ['', 156]
        ];
        
        if($is_client){
            $ecritures = $this->struct($structure);
        }

        $structure['code_auxiliaire'] = [$auxiliaire_fournisseur, 17];
        $structure['nature'] = ['FOU', 3];
        $structure['compte_general'] = [$compte_general_401, 17];
        $structure['ean'] = ['', 17];
        $structure['table_1'] = ['', 17];
        $structure['table_4'] = ['', 17];
        $structure['multi_devise'] = ['X', 1];
        
        if($is_fournisseur){
            $ecritures .= $this->struct($structure);
        }
        
        
        if($this->write_tra($ecritures, $file)) {
            $this->log('EXPORT TIERS', $client->getData('code_client'), $file);
            $client->updateField('exported', self::EXPORTED);
            $client->updateField('code_compta', $auxiliaire_client);
            $client->updateField('code_compta_fournisseur', $auxiliaire_fournisseur);
            if($want === 'c') {
                return $auxiliaire_client;
            } elseif($want === 'f') {
                return $auxiliaire_fournisseur;
            }
        }
        
    }
    
}