<?php

class indexController extends BimpController
{

    public function renderTestAssos()
    {
        $object = BimpObject::getInstance('bimphotline', 'Equipment');
        $list = new BimpList($object, 'default');
        $list->addBulkAssociation('contrats', 609, 'Associer au contrat HL10140002');
        $html = $list->render();

        unset($list);

        $list = new BimpList($object, 'default');
        $list->addBulkDeassociation('contrats', 609);
        $html .= $list->render();

        unset($list);

        return $html;
    }
}
