<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class BimpFinancCronExec extends BimpCron
{

    public function checkAppleCareSerials()
    {
        BimpObject::loadClass('bimpfinanc', 'BimpRevalorisation');

        $nbOk = 0;
        BimpRevalorisation::checkAppleCareSerials($nbOk);

        $this->output .= 'OK - ' . $nbOk . ' serials traités';
        return 0;
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

        $this->output .= 'OK - ' . $nbOk . ' revals traitées';
        return 0;
    }
}
