<?php

require_once '../main.inc.php';

ini_set('display_errors', 1);

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/controllers/fixeTabsController.php';

global $bimp_fixe_tabs;
$bimp_fixe_tabs = new fixeTabsController('bimpcore', 'fixeTabs');

require DOL_DOCUMENT_ROOT . '/bimphotline/inter_chronos.php';

$bimp_fixe_tabs->display();
