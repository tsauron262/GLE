<?php

class Bimp_Propal extends BimpObject
{

    public static $status_list = array(
        0 => array('label' => 'Brouillon', 'icon' => 'file-text', 'classes' => array('warning')),
        1 => array('label' => 'Validée', 'icon' => 'check', 'classes' => array('info')),
        2 => array('label' => 'Signée', 'icon' => 'check', 'classes' => array('info')),
        3 => array('label' => 'Non signée (fermée)', 'icon' => 'exclamation-circle', 'classes' => array('important')),
        4 => array('label' => 'Facturée (fermée)', 'icon' => 'check', 'classes' => array('success')),
    );

    // Getters: 

    public function getIdSav()
    {
        if ($this->isLoaded()) {
            return (int) $this->db->getValue('bs_sav', 'id', '`id_propal` = ' . (int) $this->id);
        }

        return 0;
    }

    public function getModelPdf()
    {
        if ((int) $this->getIdSav()) {
            return 'bimpdevissav';
        }

        return 'bimpdevis';
    }

    // Affichages: 

    public function displayPDFButton($display_generate = true)
    {
        $html = '';

        $ref = $this->getData('ref');

        if ($ref) {
            $file = DOL_DATA_ROOT . '/propale/' . $ref . '/' . $ref . '.pdf';
            if (file_exists($file)) {
                $url = DOL_URL_ROOT . '/document.php?modulepart=propal&file=' . htmlentities($ref . '/' . $ref . '.pdf');
                $onclick = 'window.open(\'' . $url . '\');';
                $button = '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
                $button .= '<i class="fas fa5-file-pdf iconLeft"></i>';
                $button .= $ref . '.pdf</button>';
                $html .= $button;
            }

            if ($display_generate) {
                if (!class_exists('ModelePDFPropales')) {
                    require_once DOL_DOCUMENT_ROOT . '/core/modules/propale/modules_propale.php';
                }

                $models = ModelePDFPropales::liste_modeles($this->db->db);
                if (count($models)) {
                    $html .= '<div class="propalPdfGenerateContainer" style="margin-top: 15px">';
                    $html .= BimpInput::renderInput('select', 'propal_model_pdf', $this->getModelPdf(), array(
                                'options' => $models
                    ));
                    $onclick = 'var model = $(this).parent(\'.propalPdfGenerateContainer\').find(\'[name=propal_model_pdf]\').val();setObjectAction($(this), ' . $this->getJsObjectData() . ', \'generatePdf\', {model: model}, null, null, function() {window.location.reload()}, null);';
                    $html .= '<button type="button" onclick="' . $onclick . '" class="btn btn-default">';
                    $html .= '<i class="fas fa5-sync iconLeft"></i>Générer';
                    $html .= '</button>';
                    $html .= '</div>';
                }
            }
        }

        return $html;
    }

    // Actions:

    public function actionGeneratePdf($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'PDF généré avec succès';

        if ($this->isLoaded()) {
            if (!isset($data['model']) || !$data['model']) {
                $data['model'] = $this->getModelPdf();
            }
            global $langs;
            $this->dol_object->error = '';
            $this->dol_object->errors = array();
            if ($this->dol_object->generateDocument($data['model'], $langs) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Des erreurs sont survenues lors de la génération du PDF');
            }
        } else {
            $errors[] = 'ID de la proposition commerciale absent';
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Rendus HTML:

    public function renderMarginsTable()
    {
        $html = '';

        if ($this->isLoaded()) {
            if (!class_exists('FormMargin')) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmargin.class.php';
            }

            $form = new FormMargin($this->db->db);
            $marginInfo = $form->getMarginInfosArray($this->dol_object);

            if (!empty($marginInfo)) {
                global $conf;

                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>Marges</th>';
                $html .= '<th>Prix de vente</th>';
                $html .= '<th>Prix de revient</th>';
                $html .= '<th>Marge</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';
                if (!empty($conf->product->enabled)) {
                    $html .= '<tr>';
                    $html .= '<td>Marge / Produits</td>';
                    $html .= '<td>' . price($marginInfo['pv_products']) . '</td>';
                    $html .= '<td>' . price($marginInfo['pa_products']) . '</td>';
                    $html .= '<td>' . price($marginInfo['margin_on_products']) . '</td>';
                    $html .= '</tr>';
                }

                if (!empty($conf->service->enabled)) {
                    $html .= '<tr>';
                    $html .= '<td>Marge / Services</td>';
                    $html .= '<td>' . price($marginInfo['pv_services']) . '</td>';
                    $html .= '<td>' . price($marginInfo['pa_services']) . '</td>';
                    $html .= '<td>' . price($marginInfo['margin_on_services']) . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody>';

                $html .= '<tfoot>';
                if (!empty($conf->product->enabled) && !empty($conf->service->enabled)) {
                    $html .= '<tr>';
                    $html .= '<td>Marge totale</td>';
                    $html .= '<td>' . price($marginInfo['pv_total']) . '</td>';
                    $html .= '<td>' . price($marginInfo['pa_total']) . '</td>';
                    $html .= '<td>' . price($marginInfo['total_margin']) . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</tfoot>';
                $html .= '</table>';
            }
        }

        return $html;
    }

    // Overrides: 

    protected function updateDolObject(&$errors)
    {
        if (!$this->isLoaded()) {
            return 0;
        }
        if (is_null($this->dol_object)) {
            $errors[] = 'Objet Dolibarr invalide';
            return 0;
        }

        if (!isset($this->dol_object->id) || !$this->dol_object->id) {
            $errors[] = 'Objet Dolibarr invalide';
            return 0;
        }

        $bimpObjectFields = array();
        global $user;

        foreach ($this->data as $field => $value) {
            if ($this->field_exists($field)) {
                if ((int) $this->getConf('fields/' . $field . '/dol_extra_field', 0, false, 'bool')) {
                    $this->dol_object->array_options['options_' . $field] = $value;
                } else {
                    $prop = $this->getConf('fields/' . $field . '/dol_prop', $field);
                    if (is_null($prop)) {
                        $errors[] = 'Erreur de configuration: propriété de l\'objet Dolibarr non définie pour le champ "' . $field . '"';
                    } elseif (!property_exists($this->dol_object, $prop)) {
                        $bimpObjectFields[$field] = $value;
                    }
                }
            }
        }

        // Date: 
        $date = BimpTools::getDateForDolDate($this->getData('date'));
        if ($date !== $this->dol_object->date) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->set_date($user, $date) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de la date');
            }
        }

        // Date fin validité: 
        $date = BimpTools::getDateForDolDate($this->getData('fin_validite'));
        if ($date !== $this->dol_object->fin_validite) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->set_echeance($user, $date) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de la date de fin de validité');
            }
        }

        // Date livraison: 
        $date = BimpTools::getDateForDolDate($this->getData('date_livraison'));
        if ($date !== $this->dol_object->date_livraison) {
            $this->dol_object->error = '';
            $this->dol_object->errors = array();

            if ($this->dol_object->set_date_livraison($user, $date) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la mise à jour de la date de livraison');
            }
        }

        if (method_exists($this, 'beforeUpdateDolObject')) {
            $this->beforeUpdateDolObject();
        }

        // Mise à jour des champs Bimp_Propal:
        foreach ($bimpObjectFields as $field => $value) {
            $field_errors = $this->updateField($field, $value);
            if (count($field_errors)) {
                $errors[] = BimpTools::getMsgFromArray($field_errors, 'Echec de la mise à jour du champ "' . $field . '"');
            }
        }

        // Mise à jour des extra_fields: 
        if ($this->dol_object->update_extrafields($user) <= 0) {
            $errors[] = 'Echec de la mise à jour des champs supplémentaires';
        }

        if (!count($errors)) {
            return 1;
        }

        return 0;
    }
}
