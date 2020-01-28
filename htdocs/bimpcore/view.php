<?php

require_once("../main.inc.php");

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

$errors = array();

$module = BimpTools::getValue('module', '');
$object_name = BimpTools::getValue('object_name', '');
$id_object = BimpTools::getValue('id_object', 0);
$view = BimpTools::getValue('view', 'default');

if (!$module) {
    $errors[] = 'Module absent';
}
if (!$object_name) {
    $errors[] = 'Module absent';
}
if (!$id_object) {
    $errors[] = 'Module absent';
}

if (!count($errors)) {
    $object = BimpCache::getBimpObjectInstance($module, $object_name, $id_object);

    if (!BimpObject::objectLoaded($object)) {
        if (is_null($object)) {
            $object = BimpObject::getInstance($module, $object_name);
        }
        if (!is_a($object, $object_name)) {
            $errors[] = 'Le type d\'objet ' . $module . ' - ' . $object_name . ' n\'existe pas';
        } else {
            $errors[] = BimpTools::ucfirst($object->getLabel('the')) . ' d\'ID ' . $id_object . ' n\'existe pas';
        }
    }
}

top_htmlhead('', $title, 0, 0, array(), array());

echo '<body>';
BimpCore::displayHeaderFiles();

?>

<style>
    @media print {
    .no_print,
    .btn,
    .objectIcon,
    .header_buttons,
    .headerTools,
    .rowButton,
    .panel-footer,
    .paginationContainer {
        display: none;
    }
}
</style>

<?php 

if (count($errors)) {
    echo BimpRender::renderAlerts($errors);
} else {
    $html .= '<div class="buttonsContainer no_print" style="margin: 30px 0;text-align: center">';
    $html .= '<button class="btn btn-primary btn-large" onclick="window.print();">';
    $html .= BimpRender::renderIcon('fas_print', 'iconLeft') . 'Imprimer';
    $html .= '</button>';
    $html .= '<button class="btn btn-danger btn-large" onclick="window.close();">';
    $html .= BimpRender::renderIcon('fas_times', 'iconLeft') . 'Fermer';
    $html .= '</button>';
    $html .= '</div>';
    
    echo $html;

    $bc_view = new BC_View($object, $view);
    echo $bc_view->renderHtml();
}

echo '</body></html>';