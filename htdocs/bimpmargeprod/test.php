<?php

require_once '../bimpcore/main.php';

ini_set('display_errors', 1);

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

$instance = BimpObject::getInstance('bimpmargeprod', 'BMP_TypeMontant');

$errors = $instance->rebuildAllCalcMontantsCaches();

echo '<pre>';
print_r($errors);
exit;
