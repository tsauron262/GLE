<?php

require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/DocFinancementPDF.php';

class PvrFinancementPDF extends DocFinancementPDF
{

    public static $doc_type = 'pvr';
    public $target_label = 'Locataire';
    public $signature_bloc = true;
    public $object_signature_params_field_name = 'signature_pvr_params';

    public function __construct($db, $demande, $extra_data = array(), $options = array())
    {
        parent::__construct($db, $demande, $extra_data, $options);

        $this->doc_name = '';
    }

    public function initHeader()
    {
        parent::initHeader();
        $this->header_vars['doc_ref'] = '';
        $this->header_vars['doc_name'] = '';
        $this->pdf->topMargin = 30;
    }

    public function renderDocInfos()
    {
        $html = '';

        $html .= '<div style="font-size: 12px; font-weight: bold; text-align: center; color: #' . $this->primary . '">';
        $html .= 'PROCES VERBAL DE RECEPTION ET MISE EN SERVICE DE MATERIEL <br/>';
        $html .= '<span style="font-size: 10px; font-weight: normal; text-align: center; color: #000000">';
        $html .= 'CONTRAT N° ' . $this->demande->getRef();
        $html .= '</span>';
        $html .= '</div><br/><br/>';

        $this->writeContent($html);

        parent::renderDocInfos();
    }

    public function renderBottom()
    {
        $html = '<br/><br/>';

        $html .= '<div style="font-size: 8px">';

        $html .= 'Le locataire a choisi librement et sous sa responsabilité les équipements, objets du présent contrat, ';
        $html .= 'en s’assurant auprès de ses fournisseurs de leur compatibilité y compris dans le cas où ils sont incorporés dans un système préexistant.<br/><br/>';

        $html .= 'Le fournisseur déclare que le matériel, ci-dessus désigné, a bien été mis en service selon les normes du constructeur, ';
        $html .= 'et le locataire déclare avoir, ce jour, réceptionné ce matériel sans aucune réserve, en bon état de marche, sans vice ni défaut apparent et ';
        $html .= 'conforme à la commande passée au fournisseur. En conséquence, le locataire déclare accepter ledit matériel sans restriction, ni réserve, ';
        $html .= 'compte tenu du mandat qui lui a été fait par LDLC PRO LEASE.<br/><br/>';

        $html .= 'Le Loueur / Fournisseur déclare que les matériels livrés sont conformes aux normes et réglementations en vigueur notamment en ce qui ';
        $html .= 'concerne l’hygiène et la sécurité au travail.<br/><br/>';

        $html .= 'La signature du procès-verbal de réception et mise en service de matériel rend exigible le 1er loyer.<br/><br/>';

        $html .= 'Fait à Limonest, le ' . date('d / m / Y') . '<br/><br/>';
        $html .= '</div>';

        $this->writeContent($html);
    }

    public function renderSignatureBloc()
    {
        // /!\ !!!!! Ne pas modifier ce bloc : réglé précisément pour incrustation signature électronique. 
        if ($this->signature_bloc) {
            $is_company = (int) BimpTools::getArrayValueFromPath($this->extra_data, 'client_is_company', 0);
            $locataire_signature_label = 'Signature' . ($is_company ? ' et cachet' : '') . ' :';
            $loueur_signature_label = 'Signature et cachet :';

            $errors = array();

            $html = '<table style="width: 95%;font-size: 8px;" cellpadding="3">';
            $html .= '<tr>';

            // Signatue locataire: 
            $html .= '<td style="width: 33%">';
            $html .= '<span style="font-size: 9px; font-weight: bold">Pour le locataire :</span><br/>';

            $html .= BimpTools::getArrayValueFromPath($this->extra_data, 'client_representant', '', $errors, true, 'Représentant du client absent') . '<br/>';
            if ($is_company) {
                $html .= BimpTools::ucfirst(BimpTools::getArrayValueFromPath($this->extra_data, 'client_repr_qualite', '', $errors, true, 'Qualité du représentant du client absent'));
            }
            $html .= '</td>';

            $html .= '<td style="width: 33%"></td>';

            // Signature Loueur:
            $html .= '<td style="width: 33%">';
            $html .= '<span style="font-size: 9px; font-weight: bold">Pour le loueur :</span><br/>';
            $html .= BimpTools::getArrayValueFromPath($this->extra_data, 'loueur_nom', '', $errors, true, 'Nom du signataire loueur absent') . '<br/>';
            $html .= BimpTools::getArrayValueFromPath($this->extra_data, 'loueur_qualite', '', $errors, true, 'Qualité du signataire loueur absente');
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<td>Date : <br/>' . $locataire_signature_label . '<br/><br/><br/><br/><br/></td>';
            $html .= '<td></td>';
            $html .= '<td>Date : <br/>' . $loueur_signature_label . '<br/><br/><br/><br/><br/></td>';
            $html .= '</tr>';
            $html .= '</table>';

            $page = 0;
            $yPos = 0;

            $this->writeFullBlock($html, $page, $yPos);

            $this->signature_params['locataire'] = array(
                'elec'     => array(
                    'x_pos'            => 12,
                    'y_pos'            => (int) $yPos + 25,
                    'page'             => $page,
                    'width'            => 40,
                    'date_x_offset'    => 7,
                    'date_y_offset'    => -10,
                    'display_nom'      => 0,
                    'display_fonction' => 0
                ),
                'docusign' => array(
                    'anch' => 'Pour le locataire :',
                    'fs'   => 'Size8',
                    'x'    => 5,
                    'y'    => 88,
                    'date' => array(
                        'x' => 22,
                        'y' => 36
                    )
                )
            );
            $this->signature_params['loueur'] = array(
                'elec'     => array(
                    'x_pos'            => 131,
                    'y_pos'            => (int) $yPos + 25,
                    'page'             => $page,
                    'width'            => 40,
                    'date_x_offset'    => 7,
                    'date_y_offset'    => -10,
                    'display_nom'      => 0,
                    'display_fonction' => 0
                ),
                'docusign' => array(
                    'anch' => 'Pour le loueur :',
                    'fs'   => 'Size8',
                    'x'    => 5,
                    'y'    => 88,
                    'date' => array(
                        'x' => 22,
                        'y' => 36
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
}
