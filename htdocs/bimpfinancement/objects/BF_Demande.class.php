<?php

class BF_Demande extends BimpObject
{

    const BF_DEMANDE_BROUILLON = 0;
    const BF_DEMANDE_ATT_RETOUR = 1;
    const BF_DEMANDE_SIGNE = 2;
    const BF_DEMANDE_SIGNE_ATT_CESSION = 3;
    const BF_DEMANDE_CEDE = 4;
    const BF_DEMANDE_SANS_SUITE = 5;
    const BF_DEMANDE_RECONDUIT = 6;
    const BF_DEMANDE_REMPLACE = 7;
    const BF_DEMANDE_SIGNE_ANOM = 888;
    const BF_DEMANDE_TERMINE = 999;

    public static $status_list = array(
        self::BF_DEMANDE_BROUILLON         => array('label' => 'Brouillon', 'classes' => array('warning')),
        self::BF_DEMANDE_ATT_RETOUR        => array('label' => 'Signé - en attente de retour', 'classes' => array('important')),
        self::BF_DEMANDE_SIGNE_ATT_CESSION => array('label' => 'Signé - en attente de cession', 'classes' => array('important')),
        self::BF_DEMANDE_SIGNE_ANOM        => array('label' => 'Signé mais anomalie', 'classes' => array('danger')),
        self::BF_DEMANDE_CEDE              => array('label' => 'Cédé', 'classes' => array('danger')),
        self::BF_DEMANDE_SANS_SUITE        => array('label' => 'Sans suite', 'classes' => array('danger')),
        self::BF_DEMANDE_RECONDUIT         => array('label' => 'Reconduit', 'classes' => array('danger')),
        self::BF_DEMANDE_REMPLACE          => array('label' => 'Remplacé', 'classes' => array('danger')),
        self::BF_DEMANDE_TERMINE           => array('label' => 'Terminé', 'classes' => array('success')),
    );
    public static $durations = array(
        24 => '24 mois',
        36 => '36 mois',
        48 => '48 mois',
        60 => '60 mois',
        72 => '72 mois',
        84 => '84 mois'
    );
    public static $periodicities = array(
        1  => 'Mensuelle',
        3  => 'Trimestrielle',
        6  => 'Semestrielle',
        12 => 'Annuelle'
    );
    public static $annexes = array(
        0 => '-',
        1 => 'OFC',
        2 => 'OA'
    );
    public static $calc_modes = array(
        0 => '-',
        1 => 'A terme échu',
        2 => 'A terme à échoir'
    );

    // Getters: 

    public function renderHeaderExtraLeft()
    {   
        $html = '';
        if ($this->isLoaded()) {
            BimpTools::loadDolClass('societe');
            $id_client = $this->getData('id_client');
            $client = new Societe($this->db->db);
            $client->fetch($id_client);
            $note = $this->db->getRow('societe_extrafields', 'fk_object = ' . $id_client, null, 'object', array('notecreditsafe'));
            if (is_object($client)) {
                $html .= '<b>Client : </b>' . $client->getNomUrl(1);
                $html .= '<div style="margin-top: 10px">';
                $html .= '<strong>Notre crédit safe du client: </strong>';
                if($note->notecreditsafe) {
                    $html .= '<i>' . $note->notecreditsafe . '</i>';
                } else {
                    $html .= '<i>Ce client n\'à pas de note crédit safe</i>';
                }
                $html .= '</div>';
            }
        }
        return $html;
    }

    public function getClient_contactsArray()
    {
        $contacts = array();
        $id_client = (int) $this->getData('id_client');
        if (!is_null($id_client) && $id_client) {
            $where = '`fk_soc` = ' . $id_client;
            $rows = $this->db->getRows('socpeople', $where, null, 'array', array('rowid', 'firstname', 'lastname'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $contacts[(int) $r['rowid']] = BimpTools::ucfirst($r['firstname']) . ' ' . strtoupper($r['lastname']);
                }
            }
        }
        return $contacts;
    }

    public function getSupplier_contactsArray()
    {
        $contacts = array();
        $id_supplier = (int) $this->getData('id_supplier');
        if ($id_supplier) {
            $where = '`fk_soc` = ' . $id_supplier;
            $rows = $this->db->getRows('socpeople', $where, null, 'array', array('rowid', 'firstname', 'lastname'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $contacts[(int) $r['rowid']] = BimpTools::ucfirst($r['firstname']) . ' ' . strtoupper($r['lastname']);
                }
            }
        }
        return $contacts;
    }

    public function verif_exist_document($object){
        $modifiable = true;
        // Modification des champs de la bese de données si les documents ont été supprimer
        $leContrat = $this->db->getRows('contrat', 'rowid = ' . $this->getData('id_contrat'), null, 'array', array('rowid'));
        $laFacture = $this->db->getRows('facture', 'rowid = ' . $this->getData('id_facture'), null, 'array', array('rowid'));
        $laFactureClient = $this->db->getRows('facture', 'rowid = ' . $this->getData('id_facture_client'), null, 'array', array('rowid'));
        $laFactureFournisseur = $this->db->getRows('facture', 'rowid = ' . $this->getData('id_facture_fournisseur'), null, 'array', array('rowid'));
        if(!$leContrat && $object == 'contrat') {$this->updateField('id_contrat', 0); $modifiable = false; }
        if(!$laFacture && $object == 'facture') {$this->updateField('id_facture', 0); $modifiable = false; }
        if(!$laFactureClient && $object == 'factureC') {$this->updateField('id_facture_client', 0); $modifiable = false;}
        if(!$laFactureFournisseur && $object == 'factureF') {$this->updateField('id_facture_fournisseur', 0); $modifiable = false;}

        return $modifiable;
    }

        public function getDemandeData($object) {
            global $langs;

            $demande = $this->db->getRows('bf_demande', '`id` = ' . $this->getData('id'), null, 'object', array('*'));

            // A VOIR
            foreach ($demande as $nbr) { $demande = $nbr;}
            /////////

            $modif = $this->verif_exist_document($object);
            
            if($object != 'render') {
                if(!$demande->accepted) { $errors[] = $langs->trans('erreurBanqueValid'); }
                if(!$demande->date_livraison) { $errors[] = $langs->trans('erreurLivraisonDate'); }
                if(!$demande->date_loyer){ $errors[] = $langs->trans('erreurLoyerDate'); } 
                if(!$demande->montant_materiels && !$demande->montant_services && !$demande->montant_logiciels) { $errors[] = $langs->trans('erreurMontant'); }
                if(!$demande->id_client) { $errors[] = $langs->trans('erreurIdClient');}
                if(!$demande->id_commercial) { $errors[] = $langs->trans('erreurIdCommercial'); }
            }
                if(!count($errors)) {
                    $data = array(
                        'id_demande' => $demande->id,
                        'id_client' => $demande->id_client,
                        'id_contact_client' => $demande->id_client_contact,
                        'id_fournisseur' => $demande->id_supplier,
                        'id_contact_fournisseur' => $demande->id_supplier_contact,
                        'id_commercial' => $demande->id_commercial,
                        'id_contrat' => $demande->id_commercial,
                        'id_facture_frais' => $demande->id_facture_frais,
                        'id_facture_loyer_intercalaire' => $demande->id_facture_loyer_intercalaire,
                        'id_facture_client' => $demande->id_facture_client,
                        'id_facture_fournisseur' => $demande->id_facture_fournisseur,
                        'vr_achat' => $demande->vr,
                        'vr_vente' => $demande->vr_vente,
                        'com_com' => $demande->commission_commerciale,
                        'com_fin' => $demande->commission_financiere,
                        'materiels' => $demande->montant_materiels,
                        'logiciels' => $demande->montant_logiciels,
                        'services' => $demande->montant_services,
                        'statut' => $demande->status,
                        'accepted' => $demande->accepted,
                        'duree_prevu' => $demande->duration,
                        'periode_prevu' => $demande->periodicity,
                        'date_loyer' => $demande->date_loyer,
                        'date_livraison' => $demande->date_livraison,
                        'date_creation' => $demande->date_create,
                        'assurance' => $demande->insurance,
                        'where' => '`id_demande` = ' . $demande->id,
                        'modif' => $modif
                    );
                    return (object) $data; 
                } else {
                    return $errors;
                }
           
            
        }

    public function actiongenfactfrais($success) {
        global $langs, $user;
        if(!$this->isLoaded) {
            return array($langs->trans('erreurDemandeId'));
        } else {
            $data = $this->getDemandeData('factureFrais');

        }
    }
    
    public function actiongenfactClient($success) {
        global $langs, $user;
        if(!$this->isLoaded()) {
            return array($langs->trans('erreurDemandeId'));
        } else {
            $data = $this->getDemandeData('factureC');
            if(is_array($data)) {return $data; } 
            else {
                // Si la facture n'existe pas
                BimpTools::loadDolClass('compta/facture', 'facture');
                $facture = new Facture($this->db->db);
                if(!$data->modif) {
                    $facture->socid = $data->id_client;
                    $facture->date = date('Y-m-d');
                    $facture->total_ht = $data->vr_achat;
                    $facture->total_tva = 0;
                    //return array($facture->socid);
                    if($facture->create($user) > 0) {
                        addElementElement('demande', 'facture', $id, $facture->id);
                        $facture->addLine($langs->trans('InvoiceDescriptionClient') . " DF" . $data->id_demande, $data->vr_achat, 1, 0);
                        $this->updateField('id_facture_client', $facture->id);
                        $success = $langs->trans('successInvoiceCreate');
                    } else {
                        return $facture->error;
                    }
                } else {
                    $facture->fetch($data->id_facture_client);
                    $success = $langs->trans('successInvoiceUpdate');
                }
            }
        }

        return array(
            'warnings' => $warnings,
            'errors' => $errors,
            'success' => $success,
        );
    }
    public function actionGenerateContrat($success){
        global $langs, $user;
        if (!$this->isLoaded()) { 
            return array($langs->trans('erreurDemandeId'));
        } else { 
            $data = $this->getDemandeData('contrat');
            if(is_array($data))
                return $data;
        }

        $loyers = $this->db->getRows('bf_rent', $data->where." ORDER BY position", null, 'array', array('id', 'quantity', 'amount_ht', 'payment', 'periodicity', 'position'));
        $refinanceur = $this->db->getRows('bf_refinanceur', $data->where, null, 'array', array('id', 'position', 'name', 'status', 'rate', 'coef', 'comment'));
        $intercalaire = $this->db->getRows('bf_rent_except', $data->where, null, 'array', array('id', 'date', 'amount', 'payement'));
        $frais_divers = $this->db->getRows('bf_frais_divers', $data->where, null, 'array', array('id', 'date', 'amount'));
        if(is_null($refinanceur)) { 
            $errors[] = $langs->trans('erreurIdRefinanceur'); 
        }
        if(!count($errors)) {
            if(!is_null($loyers)) {
                $date_de_fin = new DateTime($data->date_loyer);
                foreach ($loyers as $ligne) {
                    $date_de_fin->add(new DateInterval("P".$ligne['quantity']*$ligne['periodicity']."M"));
                }
            }
            BimpTools::loadDolClass('contrat');
            $contrat = new Contrat($this->db->db);
            $contrat->socid = $data->id_client;
            $contrat->date_contrat = $data->date_creation;
            $contrat->commercial_signature_id = $data->id_commercial;
            $contrat->commercial_suivi_id = $data->id_commercial;
            $contrat->mise_en_service = $data->date_livraison;
            $contrat->fin_validite = $date_de_fin;
            //return array($data->modif_contrat);
            if(!$data->modif) {
                if($contrat->create($user) > 0) { // Si le contrat est créer correstement
                    addElementElement('demande', 'contrat', $id, $contrat->id); // On ajoute une ligne dans llx_element_element
                    $this->updateField('id_contrat', (int) $contrat->id); // On met le numéro de contrat dans la demande
                    $contrat->validate($user); // On valide le contrat
                    // Définition de la date de début des loyers
                    $start_date_dynamic = $date_loyer;
                    // Tant qu'il y a des loyers
                    foreach ($loyers as $ligne) {
                        // Mise en forme de la description
                        $suite_desc = ($ligne['periodicity'] == 1) ? "Mois" :
                        $suite_desc = ($ligne['periodicity'] == 3) ? "Trimestres" :
                        $suite_desc = ($ligne['periodicity'] == 6) ? "Semestres" :
                        $suite_desc = ($ligne['periodicity'] == 12) ? "Ans" : "";
                        $description = "Payement " . BF_demande::$periodicities[$ligne['periodicity']] . " de " . $ligne['amount_ht'] . "€ sur " . $ligne['quantity'] . " " . $suite_desc;
                        $start_date = new DateTime($start_date_dynamic);
                        $start_date = $start_date->format('Y-m-d');
                        $end_date = new DateTime($start_date);
                        $end_date->add(new DateInterval("P" . $ligne['quantity'] * $ligne["periodicity"] . "M"));
                        $end_date = $end_date->format('Y-m-d');
                        $contrat->addline($description, $ligne['amount_ht'], $ligne['quantity'], 0, 0, 0, 0, 0, $start_date, $end_date);
                        $contrat->activateAll($user, $start_date);
                        $start_date_dynamic = $end_date;
                    }
                    // Création du contrat OK
                    $success = $langs->trans('successContratCreate');
                } else {
                    $errors[] = $contrat->error;
                }
                
            } else {
                // Récupération du contrat
                $contrat->fetch($this->getData('id_contrat'));
                //récupération et parcourt des lignes
                foreach ((object) $contrat->fetch_lines() as $line) {
                    $contrat->deleteline($line->id, $user);
                }
                foreach ($loyers as $ligne) {
                        // Mise en forme de la description
                        $suite_desc = ($ligne['periodicity'] == 1) ? "Mois" :
                        $suite_desc = ($ligne['periodicity'] == 3) ? "Trimestres" :
                        $suite_desc = ($ligne['periodicity'] == 6) ? "Semestres" :
                        $suite_desc = ($ligne['periodicity'] == 12) ? "Ans" : "";
                        $description = "Payement " . BF_demande::$periodicities[$ligne['periodicity']] . " de " . $ligne['amount_ht'] . "€ sur " . $ligne['quantity'] . " " . $suite_desc;
                        $start_date = new DateTime($start_date_dynamic);
                        $start_date = $start_date->format('Y-m-d');
                        $end_date = new DateTime($start_date);
                        $end_date->add(new DateInterval("P" . $ligne['quantity'] * $ligne["periodicity"] . "M"));
                        $end_date = $end_date->format('Y-m-d');
                        $contrat->addline($description, $ligne['amount_ht'], $ligne['quantity'], 0, 0, 0, 0, 0, $start_date, $end_date);
                        $contrat->activateAll($user, $start_date);
                        $start_date_dynamic = $end_date;
                    }
                $success = $langs->trans('successContratUpdate');
            }
        }
        return array(
            'warnings' => $warnings,
            'errors' => $errors,
            'success' => $success,

        );
    }

    public function actionGenerateFacture($success) {

         if (!$this->isLoaded()) { 
            return array($langs->trans('erreurDemandeId'));
        } else { 
            (int) $id = $this->getData('id'); 
            $errors = array();
           $data = $this->getDemandeData('facture');
           if(is_array($data)) return $data;
        }

        if(!$errors) {
            global $langs, $user;
            $liste_refinanceur = $this->db->getRows('bf_refinanceur', $data->where . " AND status = 2", null, 'array', array('id', 'position', 'name', 'status', 'rate', 'coef', 'comment'));
            if($liste_refinanceur) {
                if(!$data->modif) {
                    $cout_banque = 10; // A MODIFIER EN FONCTION DU TAUX ET COEF
                    $taux_tva = 0; // A MOFIFIER EN FONCTION DE SI ON A UN TAUX POUR LES EMPRUNS
                    $success = 'Montant enprum : ';
                    foreach ($liste_refinanceur as $refinanceur) {
                        $refinanceur = (object) $refinanceur;

                        $total_emprun = $montant_materiels + $montant_logiciels + $montant_services - $vr_vente;
                        $total_emprun += $total_emprun * $commission_commerciale / 100;
                        $total_emprun += $total_emprun * $commission_financiere / 100;

                        $total_emprun += $cout_banque;


                        BimpTools::loadDolClass('compta/facture', 'facture');
                        $facture = new Facture($this->db->db);
                        $facture->socid = $refinanceur->name;
                        $facture->total_ht = $total_emprun;
                        $facture->total_tva = 0;
                        $facture->date = date('Y-m-d');
                        $facture->date_lim_reglement = date('Y-m-d');

                        if($facture->create($user) > 0) {
                            $this->updateField('id_facture', (int) $facture->id);
                            addElementElement('demande', 'facture', $id, $facture->id);
                            $description = "Demande de financement numéro : ";
                            $description .= "$id";
                            $facture->addLine($description, $total_emprun, 1, $taux_tva);
                          
                        } else {
                            return $facture->error;
                        }


                        $success .= $total_emprun . ", ";

                    }
                    
                } else {
                    return array('La facture éxiste déjà');
                }
               
            } else {
                return array('Un refinanceur est obligatoire');
            }
            
            return array(
                'warnings' => $warnings,
                'errors' => $errors,
                'success' => $success
            );

        } else {
            return $errors;
        }



    }

    public function getInfosExtraBtn()
    {
        global $langs;
        $buttons = array();
        $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';
        $getEtatContrat = $this->getData('status');

        if($getEtatContrat == 999) {

            // On récupère les VR
            $vr_achat = $this->getData('vr');
            $vr_vente = $this->getData('vr_vente');

            if($vr_achat != 0) {
                $buttons[] = array(
                    'label'   => $langs->trans('buttonGenCustomerInvoice'),
                    'icon'    => 'fas_file-contract',
                    'onclick' => $this->getJsActionOnclick('genFactClient', array(
                        'file_type' => 'pret'
                            ), array(
                        'success_callback' => $callback
                    ))
                );
            }
            
            if($vr_vente != 0) {
                $buttons[] = array(
                    'label'   => $langs->trans('buttonGenSupplierInvoice'),
                    'icon'    => 'fas_file-contract',
                    'onclick' => $this->getJsActionOnclick('genfactFourn', array(
                        'file_type' => 'pret'
                            ), array(
                        'success_callback' => $callback
                    ))
                );
            }

            
        } elseif($getEtatContrat == 1) {
            $buttons[] = array(
            'label'   => $langs->trans('buttonGenContrat'),
            'icon'    => 'fas_file-contract',
            'onclick' => $this->getJsActionOnclick('generateContrat', array(
                'file_type' => 'pret'
                    ), array(
                'success_callback' => $callback
            ))
        );
        }

        return $buttons;
    }

    public function genFacture()
    {   
        global $langs;
        $buttons = array();

        $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';

        $buttons[] = array(
            'label'   => $langs->trans('buttonGenBankInvoice'),
            'icon'    => 'fas_file-invoice-dollar',
            'onclick' => $this->getJsActionOnclick('generateFacture', array(
                'file_type' => 'pret'
                    ), array(
                'success_callback' => $callback
            ))
        );

        return $buttons;
    }
}
