<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpFile.class.php';

class BS_SavFile extends BimpFile
{

    public function create(&$warnings = [], $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            if ((int) BimpTools::getPostFieldValue('in_fac_email', 0)) {
                $file_errors = array();
                $sav = $this->getParentInstance();

                if (BimpObject::objectLoaded($sav) && is_a($sav, 'BS_SAV')) {
                    $file_errors = $sav->addFacEmailFile($this->id);
                } else {
                    $file_errors[] = 'SAV associé non trouvé';
                }

                if (count($file_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($file_errors, 'Echec ajout du fichier à la liste des fichiers à joindre à l\'e-mail de facturation');
                }
            }
        }

        return $errors;
    }
}
