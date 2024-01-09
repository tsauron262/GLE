<?php

class Bimp_ImportPrelevementLine extends BimpObject
{

    var $refs = array();
    var $total_reste_a_paye = 0;
    var $ok = false;

    function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        $result = $this->actionInit(array());
        $errors = BimpTools::merge_array($errors, $result['errors']);

        return $errors;
    }

    function actionInit($data, &$success = '')
    {
        $errors = $warnings = array();
        $success = 'OK';
        $datas = explode(";", $this->getData('data'));

        $price = 0;
        $ref = '';
        $date = '';
        $facture = 0;
        if (isset($datas[7]))
            $price = str_replace(array(" ", " "), "", $datas[7]);
        else {
            $errors[] = 'Prix invalide';
        }
        if (isset($datas[6])) {
            $ref = $datas[6];
        } else {
            $errors[] = 'Facture invalide';
        }

        if (isset($datas[10]) && $data[10]) {
            $dateTab = explode("/", $datas[10]);
            if ($dateTab[2] > 2000)
                $date = new DateTime($dateTab[2] . '/' . $dateTab[1] . '/' . $dateTab[0]);
            else
                $date = new DateTime($datas[10]);
        } else {
            $errors[] = 'date invalide';
        }
        
        if ($ref != '') {
            $obj = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_Facture', array('ref' => $ref));
            if ($obj && $obj->isLoaded()) {
                $facture = $obj->id;
            }
        }

        $this->set('facture', $facture);
        $this->set('price', $price);
        $this->set('date', $date->format('Y-m-d'));
        $errors = $this->update($warnings);

        return array('errors' => $errors, 'warnings' => $warnings);
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded() && $this->isEditable()) {

            $buttons[] = array(
                'label'   => 'Reinitialiser la ligne',
                'icon'    => 'fas_undo',
                'onclick' => $this->getJsActionOnclick('init')
            );
        }
        return $buttons;
    }

    function fetch($id, $parent = null)
    {
//        global $modeCSV; $modeCSV = true;
        $return = parent::fetch($id, $parent);

        if ($this->getData('facture') > 0 && $this->getData('price') > 0)
            $this->ok = true;

        return $return;
    }

    function getRowStyle()
    {
        if ($this->ok)
            return 'background-color:green!important;opacity: 0.5;';
    }

    function isEditable($force_edit = false, &$errors = array()): int
    {
        return !$this->getInitData('traite');
    }

    function getDataInfo()
    {
        global $modeCSV;
        if ($modeCSV) {
            return $this->getData('data');
        } else {
            $return = '<span class=" bs-popover"';
            $return .= BimpRender::renderPopoverData($this->getData('data'), 'top', true);
            $return .= '>';
            $return .= substr($this->getData('data'), 0, 5) . '...';
            $return .= '</span>';
            return $return;
        }
    }
}
