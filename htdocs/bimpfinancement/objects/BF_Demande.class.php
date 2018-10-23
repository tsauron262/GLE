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
        self::BF_DEMANDE_BROUILLON         => array('label' => 'Brouillon', 'classes' => array('warning')),
        self::BF_DEMANDE_ATT_RETOUR        => array('label' => 'En attente de retour', 'classes' => array('important')),
        self::BF_DEMANDE_SIGNE_ATT_CESSION => array('label' => 'Signé - en attente de cession', 'classes' => array('important')),
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

    public function getInfosExtraBtn()
    {
        $buttons = array();

        $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';

        $buttons[] = array(
            'label'   => 'Créer un contrat',
            'icon'    => 'fas_file-contract',
            'onclick' => $this->getJsActionOnclick('generateContrat', array(
                'file_type' => 'pret'
                    ), array(
                'success_callback' => $callback
            ))
        );

        return $buttons;
    }

    // Rendus HTML: 

    public function renderViewLoadedScript()
    {
        if ($this->isLoaded()) {
            return '<script type="text/javascript">onBFDemandeViewLoaded(' . $this->id . ');</script>';
        }
        return '';
    }

    public function renderCommandesList()
    {

        $asso = new BimpAssociation($this, 'commandes');
        $list = $asso->getAssociatesList();

        // Créer la liste sur le modèle de BS_SAV.class.php->renderPropalesList()
        // à la fin:

        $html = '';

        $button = array(// bouton pour afficher formulaire associations
            'label'   => 'Gérer les commandes associées',
            'classes' => array('btn', 'btn-default'),
            'attr'    => array(
                'onclick' => $this->getJsLoadModalForm('commandes')
            )
        );

        $html = BimpRender::renderPanel('Commandes associées', $html, '', array(
                    'type'           => 'secondary',
                    'foldable'       => true,
                    'icon'           => 'fas_dolly',
                    'header_buttons' => array($button)
        ));

        return $html;
    }

    // Actions: 

    public function actionGenerateContrat($data, &$success)
    {
        if (!$this->isLoaded()) {
            return array("ID de la demande de financement absent");
        }

        $errors = array();
        $warnings = array();
        $success = "Contrat créé avec succès";

        $id_client = (int) $this->getData('id_client');
        $id_commercial = (int) $this->getData('id_commercial');
        $montant_materiels = $this->getData('montant_materiels');
        $montant_services = $this->getData('montant_services');
        $montant_logiciels = $this->getData('montant_logiciels');
        $accepted = $this->getData('accepted');
        $date_loyer = $this->getData('date_loyer');
        $date_livraison = $this->getdata('date_livraison');
        $date_creation = $this->getData('date_create');

        if (!$accepted) {
            $errors[] = "La banque doit avoir validé";
        }
        if (!$date_livraison) {
            $errors[] = "Date de livraison manquante";
        }
        if (!$date_loyer) {
            $errors[] = "Date de mise en loyer manquante";
        }
        if (!$montant_materiels && !$montant_logiciels && !$montant_services) {
            $errors[] = 'Logiciels, Services ou Matériels non renseignés';
        }
        if (!$id_client) {
            $errors[] = "Pas de client";
        }
        if (!$id_commercial) {
            $errors[] = "Commercial obligatoire";
        }

        $loyers = $this->getChildrenObjects('rents', array(), 'position', 'asc');
        $refinanceur = $this->getChildrenObjects('refinanceurs');

        if (is_null($refinanceur)) {
            $errors[] = "Refinanceur manquant";
        }

        if (!count($errors)) {
            global $user;
            if (!is_null($loyers) && count($loyers)) {
                $date_de_fin = new DateTime($this->getData('date_loyer'));
                foreach ($loyers as $loyer) {
                    $date_de_fin->add(new DateInterval("P" . $loyer->getData('quantity') * $loyer->getData('periodicity') . "M"));
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
//
//            $where_element_element = "`fk_source` = " . $this->id . " AND `sourcetype` = 'demande' AND `targettype` = 'contrat'";
//            $ElementElement = $this->db->getRows('element_element', $where_element_element, null, 'array', array('fk_source', 'sourcetype', 'targettype'));

            if (!(int) $this->getData('id_contrat')) {
                if ($contrat->create($user) > 0) {
                    $contrat->validate($user);
                    addElementElement('demande', 'contrat', $this->id, $contrat->id);

                    $DT = new DateTime($date_livraison);

                    $i = 0;
                    foreach ($loyers as $loyer) {
                        $i++;
                        $loyer_periodicity = (int) $loyer->getData('periodicity');
                        $loyer_qty = (int) $loyer->getData('quantity');
                        $description = 'Paiement ' . BF_Rent::$periodicities[$loyer_periodicity] . ' de ';
                        $description .= BimpTools::displayMoneyValue($loyer->getData('amount_ht'), 'EUR');
                        $description .= ' sur ' . $loyer_qty . ' ';
                        $description .= ($loyer_qty === 1 ? BF_Rent::$period_label[$loyer_periodicity] : BF_Rent::$period_label_plur[$loyer_periodicity]);

                        $start_date = $DT->format('Y-m-d');
                        $DT->add(new DateInterval("P" . $loyer_qty * $loyer_periodicity . "M"));

                        $contrat->error = '';
                        $contrat->errors = array();

                        if ($contrat->addline($description, (float) $loyer->getData('amount_ht'), $loyer_qty, 0, 0, 0, 0, 0, $start_date, $DT->format('Y-m-d')) <= 0) {
                            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($contrat), 'Echec de l\'ajout d\'une ligne au contrat pour le loyer n° ' . $i);
                        }
                    }
                    $this->updateField('id_contrat', (int) $contrat->id);
                } else {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($contrat), 'Echec de la création du contrat');
                }
            } else {
                // On met à jours le contrat
                $errors[] = "Le contrat existe déjà";
            }
        }
        return array(
            'warnings' => $warnings,
            'errors'   => $errors,
        );
    }
}
