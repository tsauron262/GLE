<?php

class demandeController extends BimpController
{

    public function ajaxProcessGetRefinanceurLoyerCalc()
    {
        $errors = array();

        $id_refinanceur = (int) BimpTools::getValue('id_refinanceur');

        $span_html = '';

        if ($id_refinanceur) {
            global $db;
            $bdb = new BimpDb($db);
            $id_demande = (int) $bdb->getValue('bf_demande_refinanceur', 'id_demande', '`id` = ' . $id_refinanceur);

            if ($id_demande) {
                $demande = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_Demande', $id_demande);
                if (BimpObject::objectLoaded($demande)) {
                    $refinanceur = BimpCache::getBimpObjectInstance('bimpfinancement', 'BF_DemandeRefinanceur', $id_refinanceur);
                    if (BimpObject::objectLoaded($refinanceur)) {
                        $span_html = $refinanceur->displayLoyerSuggest();
                    }
                }
            }
        }

        if (!$span_html) {
            $errors[] = 'Fail';
        }

        die(json_encode(array(
            'errors'     => $errors,
            'span_html'  => $span_html,
            'request_id' => BimpTools::getValue('request_id', 0),
        )));
    }
}
