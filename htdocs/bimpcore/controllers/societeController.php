<?php

class societeController extends BimpController
{

    public function ajaxProcessCheckSocieteSiren()
    {
        $errors = array();
        $warnings = array();
        $data = array();

        $module = BimpTools::getValue('module', 'bimpcore');
        $object_name = BimpTools::getValue('object_name', 'Bimp_Societe');
        $id_object = BimpTools::getValue('id_object', 0);
        $field = BimpTools::getValue('field', '');
        $value = BimpTools::getValue('value', '');

        if (!$field) {
            $errors[] = 'Type de numéro absent';
        }

        if (!$value) {
            $errors[] = 'N° SIRET ou SIREN absent';
        }

        if (!count($errors)) {
            $instance = BimpCache::getBimpObjectInstance($module, $object_name, $id_object);

            if (!is_a($instance, 'Bimp_Societe')) {
                $errors[] = 'Objet invalide';
            } else {
                $errors = $instance->checkSiren($field, $value, $data, $warnings);
            }
        }

        return array(
            'errors'     => $errors,
            'warnings'   => $warnings,
            'data'       => $data,
            'siren_ok'   => (count($errors) ? 0 : 1),
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }
}
