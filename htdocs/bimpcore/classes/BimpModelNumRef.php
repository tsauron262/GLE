<?php

require_once(DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php');

if (!defined('BIMP_LIB')) {
    require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
}

class BimpModelNumRef
{

    public $error = '';

    function info()
    {
        global $langs;
        $langs->load("contracts");
        $langs->load("synopsisGene@synopsistools");
        return $langs->trans("NoDescription");
    }

    function getExample($mask, $modelId)
    {
        global $langs;
        $langs->load("contracts");
        $langs->load("synopsisGene@synopsistools");
        return $langs->trans("NoExample");
    }

    function canBeActivated()
    {
        return true;
    }

    function getNextValue($objsoc, $obj, $mask)
    {
        global $langs;
        return $langs->trans("NotAvailable");
    }

    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development')
            return $langs->trans("VersionDevelopment");
        if ($this->version == 'experimental')
            return $langs->trans("VersionExperimental");
        if ($this->version == 'dolibarr')
            return DOL_VERSION;
        return $langs->trans("NotAvailable");
    }
}

?>
