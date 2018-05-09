<?php

require_once DOL_DOCUMENT_ROOT . '/bimpsupport/centre.inc.php';

class BS_SavPret extends BimpObject
{

    public function getCreateJsCallback()
    {
        return 'alert(\'Affichage PDF\');';
    }

    public function getCentresArray()
    {
        $centres = array(
            '' => ''
        );
        global $tabCentre;
        foreach ($tabCentre as $code => $centre) {
            $centres[$code] = $centre[2];
        }

        return $centres;
    }

    public function getEquipmentsArray()
    {
        $equipments = array();

        $sav = $this->getParentInstance();
        if (BimpObject::objectLoaded($sav)) {
            $code_centre = (string) $sav->getData('code_centre');
            if ($code_centre) {
//                echo $code_centre;
                $unreturned = $this->getUnreturnedEquipments($code_centre);
                $instance = BimpObject::getInstance('bimpequipment', 'Equipment');
                $list = $instance->getList(array(
                    'p.position'    => 1,
                    'p.type'        => 7,
                    'p.code_centre' => $code_centre
                        ), null, null, 'id', 'desc', 'array', array(
                    'a.id'
                        ), array(
                    array(
                        'table' => 'be_equipment_place',
                        'alias' => 'p',
                        'on'    => 'p.id_equipment = a.id'
                    )
                ));
                foreach ($list as $item) {
                    if (in_array((int) $item['id'], $unreturned)) {
                        continue;
                    }

                    if ($instance->fetch((int) $item['id'])) {
                        $equipments[(int) $item['id']] = $instance->getData('serial') . ' - ' . $instance->displayProduct('nom', true);
                    }
                }
            }
        }

        return $equipments;
    }

    public function getUnreturnedEquipments($code_centre = '')
    {
        $filters = array(
            'returned' => 0,
        );
        if ($code_centre) {
            $filters['code_centre'] = $code_centre;
        }
        $list = $this->getList($filters, null, null, 'id', 'desc', 'array', array('id_equipment'));
        $items = array();
        foreach ($list as $item) {
            $items[] = (int) $item['id_equipment'];
        }
        return $items;
    }

    public function getListExtraBtn()
    {
        $buttons = array();

        $callback = 'function(result) {if (typeof (result.file_url) !== \'undefined\' && result.file_url) {window.open(result.file_url)}}';

        $buttons[] = array(
            'label'   => 'Générer Bon de prêt',
            'icon'    => 'fas_file-pdf',
            'onclick' => $this->getJsActionOnclick('generatePDF', array(
                'file_type' => 'pret'
                    ), array(
                'success_callback' => $callback
            ))
        );

        return $buttons;
    }

    // Actions: 

    public function actionGeneratePDF($data, &$success)
    {
        $success = 'Fichier PDF généré avec succès';

        $errors = array();
        $file_url = '';
        
        if (!in_array($file_type, array('pret'))) {
            $errors[] = 'Type de fichier PDF invalide';
        } else {
            require_once DOL_DOCUMENT_ROOT . "/bimpsupport/core/modules/bimpsupport/modules_bimpsupport.php";

        if ($file_type === 'pret') {
            $prets = $this->getChildrenObjects('prets');
            if (!count($prets)) {
                $errors[] = 'Aucun pret enregistré pour ce sav';
                return '';
            }
        }

        $errors = bimpsupport_pdf_create($this->db->db, $this, 'sav', $file_type);

        if (!count($errors)) {
            $ref = '';
            switch ($file_type) {
                case 'pc':
                    $ref = 'PC-' . $this->getData('ref');
                    break;
                case 'destruction':
                    $ref = 'Destruction-' . $this->getData('ref');
                    break;
                case 'destruction2':
                    $ref = 'Destruction2-' . $this->getData('ref');
                    break;
                case 'pret':
                    $ref = 'Pret-' . $this->getData('ref');
                    break;
            }

//            $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('sav/' . $this->id . '/' . $ref . '.pdf');
        }
        }

        return array(
            'errors'   => $errors,
            'file_url' => $file_url
        );
    }

    // Overrides: 

    public function create()
    {
        $sav = $this->getParentInstance();
        if (BimpObject::objectLoaded($sav)) {
            $id_client = (int) $sav->getData('id_client');
            if (!$id_client) {
                return array('Aucun client enregistré pour ce SAV');
            }
            $code_centre = (string) $sav->getData('code_centre');
            if (!$code_centre) {
                return array('Aucun centre enregistré pour ce SAV');
            }
            $this->set('id_client', $id_client);
            $this->set('code_centre', $code_centre);
        } else {
            return array('SAV non spécifié');
        }
        $errors = parent::create();

        if ($this->isLoaded()) {
            $this->set('ref', 'PRET' . $this->id);
            $this->update();
        }

        return $errors;
    }
}
