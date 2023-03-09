<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class BimpFinancCronExec
{

    public function checkAppleCareSerials()
    {
        BimpObject::loadClass('bimpfinanc', 'BimpRevalorisation');
        BimpRevalorisation::checkAppleCareSerials();

        return 'OK';
    }

    public function checkBilledApplecareReval()
    {
        BimpObject::loadClass('bimpfinanc', 'BimpRevalorisation');
        $errors = BimpRevalorisation::checkBilledApplecareReval();

        if (count($errors)) {
            BimpCore::addlog('Erreurs lors de la validation auto des revalorisations AppleCare', 3, 'bimpcore', null, array(
                'Erreurs' => $errors
            ));
        }

        return 'OK';
    }
}
