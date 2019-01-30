<?php

class refinanceurController extends BimpController
{

    public function ajaxProcessLoadRefinanceurCoefsForm()
    {
        $html = '';
        $errors = array();

        $id_refinanceur = (int) BimpTools::getValue('id_refinanceur');

        if (!$id_refinanceur) {
            $errors[] = 'ID du refinanceur absent';
        } else {
            $refinanceur = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Refinanceur', $id_refinanceur);
            if (!BimpObject::objectLoaded($refinanceur)) {
                $errors[] = 'Le refinanceur d\'ID ' . $id_refinanceur . ' n\'existe pas';
            } else {
                $html = $refinanceur->renderCoefsForm(true);
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0),
        )));
    }

    public function ajaxProcessSaveRefinanceursCoefs()
    {
        $errors = array();
        $warnings = array();

        $id_refinanceur = (int) BimpTools::getValue('id_refinanceur');
        $coefs = BimpTools::getValue('coefs', array());

        $refinanceur = null;

        if (!$id_refinanceur) {
            $errors[] = 'ID du refinanceur absent';
        } else {
            $refinanceur = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Refinanceur', $id_refinanceur);
            if (!BimpObject::objectLoaded($refinanceur)) {
                $errors[] = 'Le refinanceur d\'ID ' . $id_refinanceur . ' n\'existe pas';
            }
        }

        if (empty($coefs)) {
            $errors[] = 'valeurs des coeffecients absentes';
        }

        if (!count($errors)) {
            $errors = $refinanceur->setCoefs($coefs, $warnings, true);
        }

        die(json_encode(array(
            'errors'     => $errors,
            'warnings'   => $warnings,
            'request_id' => BimpTools::getValue('request_id', 0),
        )));
    }
}
