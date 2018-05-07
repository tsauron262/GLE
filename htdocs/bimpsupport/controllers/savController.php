<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/controllers/gsxController.php';

class savController extends gsxController
{

    public function renderGsx()
    {
        $sav = BimpObject::getInstance($this->module, 'BS_SAV', (int) BimpTools::getValue('id'));

        if (!$sav->isLoaded()) {
            return BimpRender::renderAlerts('ID du SAV absent ou invalide');
        }

        $id_equipment = (int) $sav->getData('id_equipment');

        if (!$id_equipment) {
            return BimpRender::renderAlerts('Aucun équipement associé à ce SAV');
        }
        
        $equipment = BimpObject::getInstance('bimpequipment', 'Equipment', $id_equipment);

        if (!$equipment->isLoaded()) {
            return BimpRender::renderAlerts('L\'équipement ' . $id_equipment . ' n\'existe pas');
        }

        $html = '';

        $html .= '<div id="loadGSXForm">';
        $html .= '<input type="hidden" value="' . $equipment->getData('serial') . '" id="gsx_equipment_serial" name="gsx_equipment_serial"/>';

        $rows = array(
            array(
                'label' => 'Equipement',
                'input' => $equipment->displayProduct('nom') . ' - N° série: ' . $equipment->getData('serial')
            )
        );

        $buttons = array(
            '<button id="loadGSXButton" type="button" class="btn btn-primary btn-large" onclick="loadGSXView($(this), ' . $sav->id . ')"><i class="fa fa-download iconLeft"></i>Charger les données GSX</button>'
        );

        $html .= BimpRender::renderFreeForm($rows, $buttons, 'Chargement des données Apple GSX', 'download');
        $html .= '</div>';

        $html .= '<div id="gsxContainer">';
        $html .= '<div id="gsxResultContainer"></div>';
        $html .= '<div id="requestResult"></div>';
        $html .= '</div>';
        return $html;
    }

    protected function ajaxProcessCreatePropal()
    {
        $errors = array();
        $success = 'Proposition commerciale créée avec succès';

        $id_sav = (int) BimpTools::getValue('id_sav', 0);
        if (!$id_sav) {
            $errors[] = 'ID du SAV absent';
        } else {
            $sav = BimpObject::getInstance($this->module, 'BS_SAV', $id_sav);
            if (!$sav->isLoaded()) {
                $errors[] = 'SAV d\'ID ' . $id_sav . ' non trouvé';
            } else {
                $errors = $sav->createPropal();
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'success'    => $success,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }
  
    protected function ajaxProcessGeneratePropal()
    {
        $errors = array();
        $success = 'Propale Généré avec succès';
        $file_url = '';

        $id_sav = (int) BimpTools::getValue('id_sav', 0);

        if (!$id_sav) {
            $errors[] = 'ID du SAV absent';
        }

        if (!count($errors)) {
            $sav = BimpObject::getInstance($this->module, 'BS_SAV', $id_sav);
            if (!$sav->isLoaded()) {
                $errors[] = 'SAV d\'ID ' . $id_sav . ' non trouvé';
            } else {
                $sav->generatePropal();
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'success'    => $success,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }
}
