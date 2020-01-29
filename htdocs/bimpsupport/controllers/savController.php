<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/controllers/gsxController.php';

class savController extends gsxController
{

    public function display()
    {
        $sav = $this->config->getObject('', 'sav');
        if (BimpObject::objectLoaded($sav)) {
            $propal = $sav->getChildObject('propal');
            if (BimpObject::objectLoaded($propal)) {
                $errors = $propal->checkLines();
                if (count($errors)) {
                    foreach ($errors as $e) {
                        $this->addMsg($e, 'danger');
                    }
                }
            }
        }
        
        parent::display();
    }

    protected function canView()
    {
        global $user;
        return (int) $user->rights->BimpSupport->read;
    }

    public function renderGsx()
    {
        $sav = $this->config->getObject('', 'sav');

        if (!$sav->isLoaded()) {
            return BimpRender::renderAlerts('ID du SAV absent ou invalide');
        }

        if (!(int) $sav->getData('id_equipment')) {
            return BimpRender::renderAlerts('Equipement absent pour ce SAV');
        }

        $equipment = $sav->getChildObject('equipment');

        if (!$equipment->isLoaded()) {
            return BimpRender::renderAlerts('L\'équipement d\'ID ' . $sav->getData('id_equipment') . ' n\'existe pas');
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
            $sav = BimpCache::getBimpObjectInstance($this->module, 'BS_SAV', $id_sav);
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
            $sav = BimpCache::getBimpObjectInstance($this->module, 'BS_SAV', $id_sav);
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
