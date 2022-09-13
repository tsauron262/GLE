<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/controllers/apiController.php';

class adminController extends apiController
{

    public function renderApiList()
    {
        $html = '';

        $apis = BimpAPI::getApisArray();

        if (empty($apis)) {
            $html .= BimpRender::renderAlerts('Aucune API active', 'warning');
        } else {
            $headers = array(
                'name'        => 'Nom système',
                'idx'         => 'Index',
                'name_public' => 'Nom public',
            );
            $rows = array();
            $params = array();

            $html .= BimpRender::renderBimpListTable($rows, $headers, $params);
        }

        return $html;
    }
}
