<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/Bimp_FactureFourn.class.php');

class BimpFactureFournForDol extends Bimp_FactureFourn
{

    public function __construct($db)
    {

        require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';

        return parent::__construct('bimpcommercial', 'Bimp_FactureFourn');
    }

    public function sendRelanceExpertiseBimp()
    {

        $sends = array();
        $filtres = array('expertise' => 0, 'datef' => array('operator' => '>=', 'value' => '2022-04-01'), 'a__ef.type' => array('operator' => '!=', 'value' => 'S'), 'exported' => 1);
        $joins = array();
//        $joins['ef'] = array(
//                        'alias' => 'ef',
//                        'table' => 'facture_extrafields',
//                        'on'    => 'ef' . '.' . 'fk_facture' . ' = ' . 'a' . '.' . 'rowid'
//                    );
//        die('count : '.count(BimpObject::getBimpObjectList('bimpcommercial', 'Bimp_Facture', $filtres, $joins)));
        $facts = BimpObject::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', $filtres, null, null, $joins);
        $nb = 0;
        foreach ($facts as $fact) {
            $nb++;
            $sends[$fact->getCommercialId()][] = $fact->getLink();
        }

        $nbMail = 0;
        foreach ($sends as $idUser => $data) {
            $msg = 'Bonjour, voici les factures qui n\'ont pas d\'expertise Bimp<br/>';
            foreach ($data as $link) {
                $msg .= '<br/>' . $link;
            }
            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $idUser);
//            echo '<pre>';
//            print_r(array('Factures sans expertise BIMP', $user->getData('email'), null, $msg));
//            echo '<br/><br/>';
            mailSyn2('Factures sans expertise BIMP', $user->getData('email'), null, $msg);
            $nbMail++;
        }
        $this->output = $nb . ' factures ' . $nbMail . ' mail';

        return 0;
    }

//    public function sendRappelMargeNegative() {} => Déplacée dans BimpCommericalCronExec
}
