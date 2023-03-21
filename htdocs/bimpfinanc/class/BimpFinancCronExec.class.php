<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class BimpFinancCronExec
{

    public $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function checkAppleCareSerials()
    {
        BimpObject::loadClass('bimpfinanc', 'BimpRevalorisation');

        $nbOk = 0;
        BimpRevalorisation::checkAppleCareSerials($nbOk);

        return 'OK - ' . $nbOk . ' serials traités';
    }

    public function checkBilledApplecareReval()
    {
        BimpObject::loadClass('bimpfinanc', 'BimpRevalorisation');

        $nbOk = 0;
        $errors = BimpRevalorisation::checkBilledApplecareReval(null, $nbOk);

        if (count($errors)) {
            BimpCore::addlog('Erreurs lors de la validation auto des revalorisations AppleCare', 3, 'bimpcore', null, array(
                'Erreurs' => $errors
            ));
        }

        return 'OK - ' . $nbOk . ' revals traitées';
    }
}
