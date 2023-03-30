<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/Bimp_Facture.class.php';

class BimpFactureForDol extends Bimp_Facture
{

    public function __construct($db)
    {
        return parent::__construct('bimpcommercial', 'Bimp_Facture');
    }

    public function createTaskChorus()
    {
        BimpObject::loadClass('bimptask', 'BIMP_Task');

        $list = BimpObject::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', array('chorus_status' => array(0, 1)));
        foreach ($list as $fact) {
            if (in_array($fact->getData('chorus_status'), array(0, 1))) {
                $sujet = 'Facture en attente d\'export Chorus ' . $fact->getRef();
//                $msg = 'La facture ' . $fact->getLink(array('modal_view' => '', 'card' => 0)) . ' est en attente d\'export chorus';
                // Test : 
                $msg = 'La facture {{Facture:' . $fact->id . '}} est en attente d\'export chorus';
                BIMP_Task::addAutoTask('facturation', $sujet, $msg, "facture_extrafields:fk_object=" . $fact->id . ' AND chorus_status > 1');
            }
        }

        return 0;
    }
}
