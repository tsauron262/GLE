<?php

require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php';

class BDSExportProcess extends BDSProcess
{

    public static function getClassName()
    {
        return 'BDSExportProcess';
    }
}
