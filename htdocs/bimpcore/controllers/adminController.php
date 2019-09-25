<?php

class adminController extends BimpController
{

    public function renderScriptsTabContent()
    {
        global $user;

        if (!$user->admin) {
            return BimpRender::renderAlerts('Vous n\'avez pas la permission d\'accéder à cet onglet');
        }

        $files = scandir(DOL_DOCUMENT_ROOT . '/bimpcore/scripts/');

        $html = '';

        foreach ($files as $f) {
            if (in_array($f, array('.', '..'))) {
                continue;
            }

            if (preg_match('/^(.*)\.php$/', $f, $matches)) {
                $html .= '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $f . '" target="_blank">';
                $html .= $matches[1];
                $html .= '</a><br/>';
            }
        }
        
        return $html;
    }
}
