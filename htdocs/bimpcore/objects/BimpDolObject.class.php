<?php

if (!defined('BIMP_LIB')) {
    require_once __DIR__ . '/../Bimp_Lib.php';
}

class BimpDolObject extends BimpObject
{
    public static $dol_module = '';
    
    public function actionGeneratePdf($data, &$success, $errors = array(), $warnings = array())
    {
        $success = 'PDF généré avec succès';

        if ($this->isLoaded()) {
            if (!$this->isDolObject() || !method_exists($this->dol_object, 'generateDocument')) {
                $errors[] = 'Cette fonction n\'est pas disponible pour ' . $this->getLabel('the_plur');
            } else {
                if (!isset($data['model']) || !$data['model']) {
                    $data['model'] = $this->getModelPdf();
                }
                global $langs;
                $this->dol_object->error = '';
                $this->dol_object->errors = array();
                if ($this->dol_object->generateDocument($data['model'], $langs) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Des erreurs sont survenues lors de la génération du PDF');
                } else {
                    $ref = dol_sanitizeFileName($this->getRef());
                    $url = DOL_URL_ROOT . '/document.php?modulepart=' . static::$dol_module . '&file=' . $ref . '/' . $ref . '.pdf';
                    $success_callback = 'window.open(\'' . $url . '\');';
                }
            }
        } else {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }
    
    
    public function getModelPdf()
    {
        if ($this->field_exists('model_pdf')) {
            return $this->getData('model_pdf');
        }

        return '';
    }
}
