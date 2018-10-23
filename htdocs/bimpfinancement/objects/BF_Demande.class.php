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
    const BF_DEMANDE_TERMINE = 999;
    
    public static $status_list = array(
        self::BF_DEMANDE_BROUILLON => array('label' => 'Brouillon', 'classes' => array('warning')),
        self::BF_DEMANDE_ATT_RETOUR => array('label' => 'En attente de retour', 'classes' => array('important')),
        self::BF_DEMANDE_SIGNE_ATT_CESSION => array('label' => 'Signé - en attente de cession', 'classes' => array('important')),
        self::BF_DEMANDE_CEDE => array('label' => 'Cédé', 'classes' => array('danger')),
        self::BF_DEMANDE_SANS_SUITE => array('label' => 'Sans suite', 'classes' => array('danger')),
        self::BF_DEMANDE_RECONDUIT => array('label' => 'Reconduit', 'classes' => array('danger')),
        self::BF_DEMANDE_REMPLACE => array('label' => 'Remplacé', 'classes' => array('danger')),
        self::BF_DEMANDE_TERMINE => array('label' => 'Terminé', 'classes' => array('success')),
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

    public function renderViewLoadedScript()
    {
        if ($this->isLoaded()) {
            return '<script type="text/javascript">onBFDemandeViewLoaded(' . $this->id . ');</script>';
        }
        return '';
    }
    
    public function actionGenerateContrat($success){

        if (!$this->isLoaded()) { 
            return array("La demande de financement n'à pas d'ID");
        } else { 
            (int) $id = $this->getData('id'); 
            $errors = array();
            $where = '`id_demande` = ' . $id;
        }

        $id_client = (int) $this->getData('id_client');
        $id_commercial = (int) $this->getData('id_commercial');
        $montant_materiels = $this->getData('montant_materiels');
        $montant_services = $this->getData('montant_services');
        $montant_logiciels = $this->getData('montant_logiciels');
        $id_status = $this->getData('status');
        $accepted = $this->getData('accepted');
        $duree_prevu = $this->getData('duration');
        $periodicite_prevu = $this->getData('periodicity');
        $id_client_contact = $this->getData('id_client_contact');
        $assurance = $this->getData('insurance');
        $id_supplier = $this->getData('id_supplier');
        $id_supplier_contact = $this->getData('id_supplier_contact');
        $date_loyer = $this->getData('date_loyer');
        $date_livraison = $this->getdata('date_livraison');
        $date_creation = $this->getData('date_create');

        if(!$accepted) { $errors[] = "La banque doit avoir valider"; }
        if(!$date_livraison) { $errors[] = "Date de livraison manquante"; }
        if(!$date_loyer){ $errors[] = "Date de mise en loyer manquante"; } 
        if(!$montant_materiels && !$montant_logiciels && !$montant_services) { $errors[] = 'Logiciels, Services ou Matériels non rensseignés'; }
        if(!$id_client) { $errors[] = "Pas de client";}
        if(!$id_commercial) { $errors[] = "Commercial obligatoire"; }


        $loyers = $this->db->getRows('bf_rent', $where." ORDER BY id DESC", null, 'array', array('id', 'quantity', 'amount_ht', 'payment', 'periodicity', 'position'));
        $refinanceur = $this->db->getRows('bf_refinanceur', $where, null, 'array', array('id', 'position', 'name', 'status', 'rate', 'coef', 'comment'));
        $intercalaire = $this->db->getRows('bf_rent_except', $where, null, 'array', array('id', 'date', 'amount', 'payement'));
        $frais_divers = $this->db->getRows('bf_frais_divers', $where, null, 'array', array('id', 'date', 'amount'));
        if(is_null($refinanceur)) { 
            $errors[] = "Refinanceur manquant"; 
        }

        if(!count($errors)) {
            global $langs, $user;
            if(!is_null($loyers)) {
                $date_de_fin = new DateTime($this->getData('date_loyer'));
                foreach ($loyers as $ligne) {
                    $date_de_fin->add(new DateInterval("P".$ligne['quantity']*$ligne['periodicity']."M"));
                }
            }
            BimpTools::loadDolClass('contrat');
            $contrat = new Contrat($this->db->db);
            $contrat->socid = $id_client;
            $contrat->date_contrat = $date_creation;
            $contrat->commercial_signature_id = $id_commercial;
            $contrat->commercial_suivi_id = $id_commercial;
            $contrat->mise_en_service = $date_livraison;
            $contrat->fin_validite = $date_de_fin;

            
            $where_element_element = "`fk_source` = " . $id . " AND `sourcetype` = 'demande' AND `targettype` = 'contrat'";
            $ElementElement = $this->db->getRows('element_element', $where_element_element, null, 'array', array('fk_source', 'sourcetype', 'targettype'));
            if(!$ElementElement) {
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
                    $success = "Contrat créer avec success";
                } else {
                    $errors[] = $contrat->error;
                }
                
            } else {
                // On met à jours le contrat
                $warnings = "Le contrat existe";
            }
        }
        return array(
            'warnings' => $warnings,
            'errors' => $errors,
            'success' => $success,

        );
    }


    public function renderCommandesList()
    {
        $html = '';

        if ($this->isLoaded()) {
            $asso = new BimpAssociation($this, 'commandes');
            $list = $asso->getAssociatesList();

//            echo '<pre>';
//            print_r($list);
//            exit;
            if (count($list)) {
                krsort($list);
                $propal = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>Réf.</th>';
                $html .= '<th>Statut</th>';
                $html .= '<th>Montant TTC</th>';
                $html .= '<th>Fichier</th>';
                $html .= '</tr>';
                $html .= '</thead>';
                $html .= '<tbody>';

                foreach ($list as $id_propal) {
                    if ($propal->fetch($id_propal)) {
                        $html .= '<tr>';
                        $html .= '<td>' . $propal->getRef() . '</td>';
                        $html .= '<td>' . $propal->displayData('fk_statut') . '</td>';
                        $html .= '<td>' . $propal->displayData('total_ttc') . '</td>';
                        $html .= '<td>' . $propal->displayPDFButton(false) . '</td>';
                        $html .= '</tr>';
                    }
                }

                $html .= '</tbody>';
                $html .= '</table>';
            }

            $button = array( // bouton pour afficher formulaire associations
           'label' => 'Gérer les commandes associées',
           'classes' => array('btn', 'btn-default'),
           'attr' => array(
               'onclick' => $this->getJsLoadModalForm('commandes')
           )
       );

       $html = BimpRender::renderPanel('Commandes associées', $html, '', array(
                           'type'     => 'secondary',
                           'foldable' => true,
                           'icon'     => 'fas_dolly',
               'header_buttons' => array($button)
               ));
        }

        return $html;
    }

    public function getInfosExtraBtn()
    {
        $buttons = array();

        $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';

        $buttons[] = array(
            'label'   => 'Générer le contrat',
            'icon'    => 'fas_file-contract',
            'onclick' => $this->getJsActionOnclick('generateContrat', array(
                'file_type' => 'pret'
                    ), array(
                'success_callback' => $callback
            ))
        );

        return $buttons;
    }
}
