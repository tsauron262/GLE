<?php

class demandeController extends BimpController
{

    public function ajaxProcessCalculateMontantLoyerHT()
    {
        $errors = array();
        $warnings = array();
        $amount_ht = 0;

        $id_demande_refin = (int) BimpTools::getValue('id_demande_refin', 0, 'int');

        if ($id_demande_refin) {
            $dr = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_DemandeRefinanceur', $id_demande_refin);

            if (!BimpObject::objectLoaded($dr)) {
                $errors[] = 'La demande refinanceur #' . $id_demande_refin . ' n\'existe plus';
            }
        } else {
            $dr = BimpObject::getInstance('bimpfinancement', 'BF_DemandeRefinanceur');
        }

        if (!count($errors)) {
            if (!BimpObject::objectLoaded($dr)) {
                $dr->set('id_demande', (int) BimpTools::getValue('id_demande', 0, 'int'));
            }

            $id_refin = BimpTools::getValue('id_refinanceur', null, 'int');
            if (!is_null($id_refin)) {
                $dr->set('id_refinanceur', $id_refin);
            }

            $qty = BimpTools::getValue('qty', null, 'float');
            if (!is_null($qty)) {
                $dr->set('qty', $qty);
            }

            $periodicity = BimpTools::getValue('periodicity', null);
            if (!is_null($periodicity)) {
                $dr->set('periodicity', $periodicity);
            }

            $rate = BimpTools::getValue('rate', null, 'float');
            if (!is_null($rate)) {
                $dr->set('rate', $rate);
            }
            
            $amount_ht = $dr->calculateAmountHt($errors);
        }
        
        return array(
            'errors'     => $errors,
            'warnings'   => $warnings,
            'amount_ht'  => $amount_ht,
            'request_id' => BimpTools::getValue('request_id', 0, 'int')
        );
    }
}
