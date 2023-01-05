<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpFile.class.php';

class BS_SavFile extends BimpFile
{

    public function getDefaultListExtraButtons()
    {
        $buttons = parent::getDefaultListExtraButtons();

        if ($this->isLoaded()) {
            $sav = $this->getParentInstance();

            if (BimpObject::objectLoaded($sav) && is_a($sav, 'BS_SAV')) {
                $files = $sav->getData('in_fac_emails_files');

                if (!in_array((int) $this->id, $files)) {
                    $buttons[] = array(
                        'label'   => 'Ajouter aux fichiers à joindre à la facture',
                        'icon'    => 'fas_plus-circle',
                        'onclick' => $this->getJsActionOnclick('addToSavFacEmail', array(), array(
                            'confirm_msg' => 'Veuillez confirmer'
                        ))
                    );
                }
            }
        }

        return $buttons;
    }

    public function actionAddToSavFacEmail($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Fichier ajouté à la liste des fichiers joints à la facture';

        if ($this->isLoaded($errors)) {
            $sav = $this->getParentInstance();

            if (BimpObject::objectLoaded($sav) && is_a($sav, 'BS_SAV')) {
                $errors = $sav->addFacEmailFile($this->id);
            } else {
                $errors[] = 'SAV lié absent';
            }
        }


        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

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
