<?php

require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/DocFinancementPDF.php';

class ContratFinancementPDF extends DocFinancementPDF
{

    public static $doc_type = 'contrat';
    public $type_pdf = '';
    public $signature_bloc = true;
    public $use_docsign = true;
    public $signature_bloc_label = '';
    public $object_signature_params_field_name = 'signature_contrat_params';
    public $signature_title = 'Signature';
    public $signature_pro_title = 'Signature + Cachet avec SIRET';
    public $client_data;
    public $loueur_data;
    public $cessionnaire_data;
    public $cg_file = DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/cg_contrat.pdf';
    public $cg_page_start = 0;
    public $cg_page_number = 4;
    public $display_line_amounts = true;

    # Params:
    public static $full_blocs = array(
        'renderAfterLines' => 0
    );

    public function __construct($db, $demande, $client_data = array(), $loueur_data = array(), $cessionnaire_data = array(), $type = 'papier')
    {
        $this->type_pdf = $type;
        $this->client_data = $client_data;
        $this->loueur_data = $loueur_data;
        $this->cessionnaire_data = $cessionnaire_data;

        parent::__construct($db, $demande);

        $this->doc_name = 'Contrat de location';
    }

    public function initData()
    {
        
    }

    public function initHeader()
    {
        parent::initHeader();
        $this->header_vars['doc_ref'] = '';
        $this->header_vars['doc_name'] = '';
        $this->pdf->topMargin = 30;
    }

    public function isTargetCompany()
    {
        if (isset($this->client_data['is_company'])) {
            return (int) $this->client_data['is_company'];
        }

        return 0;
    }

    public function renderTop()
    {
        $html = '';

        $html .= '<div style="font-size: 12px; font-weight: bold; text-align: center; color: #' . $this->primary . '">';
        $html .= 'CONTRAT DE LOCATION <br/>';
        $html .= '<span style="font-size: 10px; font-weight: normal; text-align: center; color: #000000">';
        $html .= 'N° ' . str_replace('DF', '', $this->demande->getRef());
        $html .= '</span>';
        $html .= '</div>';

        $html .= '<div style="font-size: 9px">';
        $html .= '<p style="font-size: 10px; font-weight: bold; color: #' . $this->primary . '">Le locataire</p>';

        $html .= '<p>';

        $is_company = (int) BimpTools::getArrayValueFromPath($this->client_data, 'is_company', 0);

        if ($is_company) {
            $errors = array();
            $nom = BimpTools::getArrayValueFromPath($this->client_data, 'nom', '', $errors, true, 'Nom du client absent');
            $address = BimpTools::getArrayValueFromPath($this->client_data, 'address', '', $errors, true, 'Adresse du siège du client absente');
            $forme_jur = BimpTools::getArrayValueFromPath($this->client_data, 'forme_juridique', '', $errors, true, 'Forme juridique du client absente');
            $capital = BimpTools::getArrayValueFromPath($this->client_data, 'capital', '', $errors, true, 'Capital social du client absent');
            $siren = BimpTools::getArrayValueFromPath($this->client_data, 'siren', '', $errors, true, 'N° SIREN du client absent');
            $rcs = BimpTools::getArrayValueFromPath($this->client_data, 'rcs', '');
            $representant = BimpTools::getArrayValueFromPath($this->client_data, 'representant', '', $errors, true, 'Représentant du client absent');
            $repr_qualité = BimpTools::getArrayValueFromPath($this->client_data, 'repr_qualite', '', $errors, true, 'Qualité du représentant du client absent');

            if (!count($errors)) {
                $html .= '"' . $nom . '", ' . $forme_jur . ' au capital de ' . $capital . '.<br/>';
                $html .= 'Entreprise immatriculée sous le numéro ' . $siren;
                if ((int) BimpTools::getArrayValueFromPath($this->client_data, 'insee', 0)) {
                    $html .= ' à l\'INSEE ';
                } elseif ($rcs) {
                    $html .= ' au RCS de ' . $rcs . ' ';
                }

                $html .= 'dont le siège social est situé : ' . $address . ' - ';
                $html .= 'Représentée par ' . $representant . ' en qualité de ' . $repr_qualité . '.';
            } else {
                $this->errors = BimpTools::merge_array($this->errors, $errors);
            }
        } else {
            $nom = BimpTools::getArrayValueFromPath($this->client_data, 'nom', '', $errors, true, 'Nom du client absent');
            $address = BimpTools::getArrayValueFromPath($this->client_data, 'address', '', $errors, true, 'Adresse du client absente');

            $html .= '"' . $nom . '", particulier.<br/>';
            if ($address) {
                $html .= 'Domicilié à l\'adresse : ' . $address . '.';
            }
        }

        $html .= '</p>';

        $html .= '<p style="font-size: 10px; font-weight: bold; color: #' . $this->primary . '">Le loueur</p>';
        $html .= '<p>';
        $html .= 'La société LDLC PRO LEASE, SAS au capital de 100 000,00 € dont le siège social est situé à LIMONEST (69760), 2 rue des érables, ';
        $html .= 'enregistrée sous le numéro siren 838 651 594 auprès du RCS de Lyon, représentée par M. Olivier VILLEMONTE de la CLERGERIE, ';
        $html .= 'intervenant en qualité de Président.';
        $html .= '</p>';

        $html .= '<p>';
        $html .= 'Le loueur donne en location, l’équipement désigné ci-dessous (ci-après « équipement »), au locataire qui l’accepte, ';
        $html .= 'aux Conditions Particulières et aux Conditions Générales composées de deux pages recto.';
        $html .= '</p>';
        $html .= '</div>';

        $this->writeContent($html);
    }

    public function renderDocInfos()
    {
        
    }

    public function renderAfterLines()
    {
        $html = '';

        $html .= '<div style="font-size: 9px">';
        $html .= '<p style="font-size: 10px; font-weight: bold; color: #' . $this->primary . '">Durées et loyers</p>';

        $html .= '<p>';
        $html .= 'Le loyer est ferme et non révisable en cours de contrat, payable ';
        if ((int) $this->demande->getData('mode_calcul') > 0) {
            $html .= 'par terme à échoir';
        } else {
            $html .= 'à terme échu';
        }

        $html .= ', par ' . lcfirst($this->demande->displayData('mode_paiement', 'default', false, true)) . '.';
        $html .= '</p>';

        switch ($this->demande->getData('formule')) {
            case 'evo':
            case 'evo_afs':
                $nb_loyers = $this->demande->getNbLoyers();
                $periodicity = (int) $this->demande->getData('periodicity');
                $periodicity_label = $this->demande->displayData('periodicity', 'default', 0, 1);
                $loyer_ht = (float) $this->demande->getData('loyer_mensuel_evo_ht') * $periodicity;
                $loyer_ttc = $loyer_ht * 1.2;

                $html .= '<table cellpadding="3px" style="text-align: center">';
                $html .= '<tr>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Nombre de loyers</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Montant HT</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Périodicité</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Montant TTC</th>';
                $html .= '</tr>';
                $html .= '<tr>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . $nb_loyers . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . BimpTools::displayMoneyValue($loyer_ht) . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . $periodicity_label . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . BimpTools::displayMoneyValue($loyer_ttc) . '</b></td>';
                $html .= '</tr>';
                $html .= '</table>';
                break;

            case 'dyn':
                $nb_loyers = $this->demande->getNbLoyers();
                $periodicity = (int) $this->demande->getData('periodicity');
                $periodicity_label = $this->demande->displayData('periodicity', 'default', 0, 1);
                $loyer_ht = (float) $this->demande->getData('loyer_mensuel_dyn_ht') * $periodicity;
                $loyer_ttc = $loyer_ht * 1.2;
                $loyer_suppl_ht = (float) $this->demande->getData('loyer_mensuel_suppl_ht') * $periodicity;
                $loyer_suppl_ttc = $loyer_suppl_ht * 1.2;

                $html .= '<table cellpadding="3px" style="text-align: center">';
                $html .= '<tr>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Nombre de loyers</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Montant HT</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Périodicité</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Montant TTC</th>';
                $html .= '</tr>';
                $html .= '<tr>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . $nb_loyers . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . BimpTools::displayMoneyValue($loyer_ht) . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . $periodicity_label . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . BimpTools::displayMoneyValue($loyer_ttc) . '</b></td>';
                $html .= '</tr>';
                $html .= '</table>';

                $html .= '<p>Suivi de : </p>';
                $html .= '<table cellpadding="3px" style="text-align: center">';
                $html .= '<tr>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Nombre de loyers</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Montant HT</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Périodicité</th>';
                $html .= '<th style="width: 120px; background-color: #' . $this->primary . '; color: #fff;">Montant TTC</th>';
                $html .= '</tr>';
                $html .= '<tr>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . (12 / $periodicity) . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . BimpTools::displayMoneyValue($loyer_suppl_ht) . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . $periodicity_label . '</b></td>';
                $html .= '<td style="background-color: #F2F2F2"><b>' . BimpTools::displayMoneyValue($loyer_suppl_ttc) . '</b></td>';
                $html .= '</tr>';
                $html .= '</table>';
                break;
        }

        $this->writeFullBlock($html);
        $html = '';

        $livraisons = BimpTools::getArrayValueFromPath($this->client_data, 'livraisons', '');
        if ($livraisons) {
            $html .= '<div style="font-size: 9px">';
            $html .= '<p style="font-size: 10px; font-weight: bold; color: #' . $this->primary . '">Site(s) de livraison / installation</p>';
            $html .= '<p style="font-weight: bold">';
            $html .= $livraisons;
            $html .= '</p>';
            $html .= '</div>';
        }

        $this->writeFullBlock($html);
        $html = '';

        $html .= '<div style="font-size: 9px">';
        $html .= '<p>';
        $html .= 'Le locataire déclare avoir été parfaitement informé de l’opération lors de la phase précontractuelle, avoir pris connaissance, reçu et accepter toutes les conditions particulières et générales. Il atteste que le contrat est en rapport direct avec son activité professionnelle et souscrit pour les besoins de cette dernière. Le signataire atteste être habilité à l’effet d’engager le locataire au titre du présent contrat. Le locataire reconnait avoir une copie des Conditions Générales, les avoir acceptées sans réserve y compris les clauses attribution de compétence et CNIL.';
        $html .= '</p>';

        $html .= '<p>';
        $html .= 'Fait en autant d’exemplaires que de parties, un pour chacune des parties';
        $html .= '</p>';

        $html .= '<p>';
        $html .= '<b>ANNEXES : </b>';
        $html .= '<ul>';
        $html .= '<li>Conditions générales composées de quatre pages recto</li>';
        $html .= '</ul>';
        $html .= '</p>';

        $html .= '<p>Fait à Limonest, le ' . date('d / m / Y') . ' </p>';
        $html .= '</div>';

        $this->writeFullBlock($html);
    }

    public function getSignatureBlocHtml(&$errors = array())
    {
        $html = '<table style="width: 95%;font-size: 8px;" cellpadding="3">';
        $html .= '<tr>';

        // Signatue locataire: 
        $html .= '<td style="width: 33%">';
        $html .= '<span style="font-size: 9px; font-weight: bold">Pour le locataire :</span><br/>';
        $is_company = (int) BimpTools::getArrayValueFromPath($this->client_data, 'is_company', 0);
        $html .= BimpTools::getArrayValueFromPath($this->client_data, 'representant', '', $errors, true, 'Représentant du client absent') . '<br/>';
        if ($is_company) {
            $html .= BimpTools::ucfirst(BimpTools::getArrayValueFromPath($this->client_data, 'repr_qualite', '', $errors, true, 'Qualité du représentant du client absent')) . '<br/>';
        }
        $html .= '<br/><span style="font-style: italic">"Lu et approuvé"</span>';
        $html .= '</td>';

        // Signature Loueur:
        $html .= '<td style="width: 33%">';
        $html .= '<span style="font-size: 9px; font-weight: bold">Pour le loueur :</span><br/>';
        $html .= BimpTools::getArrayValueFromPath($this->loueur_data, 'nom', '', $errors, true, 'Nom du signataire loueur absent') . '<br/>';
        $html .= BimpTools::getArrayValueFromPath($this->loueur_data, 'qualite', '', $errors, true, 'Qualité du signataire loueur absente');
        $html .= '</td>';

        // Signature cessionnaire:
        $raison_cessionnaire = BimpTools::getArrayValueFromPath($this->cessionnaire_data, 'raison_social', '');
        $siren_cessionnaire = BimpTools::getArrayValueFromPath($this->cessionnaire_data, 'siren', '');
        $nom_cessionnaire = BimpTools::getArrayValueFromPath($this->cessionnaire_data, 'nom', '');
        $qualite_cessionnaire = BimpTools::getArrayValueFromPath($this->cessionnaire_data, 'qualite', '');

        $html .= '<td style="width: 33%">';
        $html .= '<span style="font-size: 9px; font-weight: bold">Pour le cessionnaire :</span><br/>';
        $html .= ($raison_cessionnaire ? $raison_cessionnaire : 'Nom: ') . '<br/>';
        $html .= 'SIREN : ' . $siren_cessionnaire . '<br/>';
        $html .= 'Représenté par : ' . $nom_cessionnaire . '<br/>';
        $html .= 'En qualité de : ' . $qualite_cessionnaire;
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td>Date : <br/>Signature :<br/><br/><br/><br/><br/></td>';
        $html .= '<td>Date : <br/>Signature :<br/><br/><br/><br/><br/></td>';
        $html .= '<td>Date : <br/>Signature :<br/><br/><br/><br/><br/></td>';
        $html .= '</tr>';
        $html .= '</table>';

        return $html;
    }

    public function renderSignatureBloc()
    {
        // /!\ !!!!! Ne pas modifier ce bloc : réglé précisément pour incrustation signature électronique. 

        if ($this->signature_bloc) {
            $errors = array();

            $html = $this->getSignatureBlocHtml($errors);

            $page = 0;
            $yPos = 0;

            $this->writeFullBlock($html, $page, $yPos);

            $this->signature_params['locataire'] = array(
                'elec'     => array(
                    'x_pos'            => 12,
                    'y_pos'            => $yPos + 30,
                    'page'             => $page,
                    'width'            => 40,
                    'date_x_offset'    => 7,
                    'date_y_offset'    => -8,
                    'display_nom'      => 0,
                    'display_fonction' => 0
                ),
                'docusign' => array(
                    'anch' => 'Pour le locataire :',
                    'fs'   => 'Size7',
                    'x'    => 5,
                    'y'    => 107,
                    'date' => array(
                        'x' => 22,
                        'y' => 57
                    )
                )
            );
            $this->signature_params['cessionnaire'] = array(
                'elec'     => array(
                    'x_pos'            => 131,
                    'y_pos'            => $yPos + 30,
                    'page'             => $page,
                    'width'            => 40,
                    'date_x_offset'    => 7,
                    'date_y_offset'    => -8,
                    'display_nom'      => 0,
                    'display_fonction' => 0
                ),
                'docusign' => array(
                    'anch' => 'Pour le cessionnaire :',
                    'fs'   => 'Size7',
                    'x'    => 5,
                    'y'    => 107,
                    'date' => array(
                        'x' => 22,
                        'y' => 57
                    )
                )
            );

            $nom_cessionnaire = BimpTools::getArrayValueFromPath($this->cessionnaire_data, 'nom', '');
            $qualite_cessionnaire = BimpTools::getArrayValueFromPath($this->cessionnaire_data, 'qualite', '');

            if (!$nom_cessionnaire) {
                $this->signature_params['cessionnaire']['elec']['display_nom'] = 1;
                $this->signature_params['cessionnaire']['elec']['nom_x_offset'] = 21;
                $this->signature_params['cessionnaire']['elec']['nom_y_offset'] = -17;

                $this->signature_params['cessionnaire']['docusign']['texts'] = array(
                    'nom_cessionnaire' => array(
                        'label' => 'Nom signataire',
                        'x'     => 57,
                        'y'     => 30,
                        'w'     => 120,
                        'h'     => 13
                    )
                );
            }
            if (!$qualite_cessionnaire) {
                $this->signature_params['cessionnaire']['elec']['display_fonction'] = 1;
                $this->signature_params['cessionnaire']['elec']['fonction_x_offset'] = 18;
                $this->signature_params['cessionnaire']['elec']['fonction_y_offset'] = -14;

                $this->signature_params['cessionnaire']['docusign']['fonction'] = array(
                    'x' => 48,
                    'y' => 39,
                    'h' => 13,
                    'w' => 120
                );
            }

            $this->signature_params['loueur'] = array(
                'elec'     => array(
                    'x_pos'            => 71,
                    'y_pos'            => $yPos + 30,
                    'page'             => $page,
                    'width'            => 40,
                    'date_x_offset'    => 7,
                    'date_y_offset'    => -8,
                    'display_nom'      => 0,
                    'display_fonction' => 0
                ),
                'docusign' => array(
                    'anch' => 'Pour le loueur :',
                    'fs'   => 'Size7',
                    'x'    => 5,
                    'y'    => 107,
                    'date' => array(
                        'x' => 22,
                        'y' => 57
                    )
                )
            );

            if (is_a($this->bimpObject, 'BimpObject')) {
                if ($this->bimpObject->field_exists($this->object_signature_params_field_name)) {
                    $this->bimpObject->updateField($this->object_signature_params_field_name, $this->signature_params);
                }
            }
        }
    }

    public function renderContent()
    {
        parent::renderContent();
        $this->pdf->createHeader('');
        $this->cg_page_start = $this->pdf->getPage() + 1;

        $title = 'Conditions générales du contrat de location n°' . str_replace('DF', '', $this->demande->getRef());
        for ($i = 1; $i < 5; $i++) {
            $this->pdf->AddPage();
            $this->pdf->SetXY(0, 10);
            $this->pdf->Cell(0, 0, $title, 0, 2, 'C', 0);
        }

        if ($this->type_pdf === 'papier') {
            $this->pdf->SetXY(10, 150);
            $this->writeFullBlock($this->getSignatureBlocHtml());
        }
    }

    public function render($file_name, $display, $display_only = false)
    {
        if (parent::render($file_name, $display, $display_only)) {
            // Merge CGV:
            $pdf = new BimpConcatPdf();
            $pdf->mergeFiles($file_name, $this->cg_file, $file_name, $display, $this->cg_page_start, 1);
            return 1;
        }

        return 0;
    }
}
