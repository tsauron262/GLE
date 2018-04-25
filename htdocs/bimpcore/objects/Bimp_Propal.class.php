<?php

class Bimp_Propal extends BimpObject
{

    public static $status_list = array(
        0 => array('label' => 'Brouillon', 'icon' => 'file-text', 'classes' => array('warning')),
        1 => array('label' => 'Validée', 'icon' => 'check', 'classes' => array('info')),
        2 => array('label' => 'Signée', 'icon' => 'check', 'classes' => array('info')),
        3 => array('label' => 'Non signée (fermée)', 'icon' => 'exclamation-circle', 'classes' => array('important')),
        4 => array('label' => 'Facturée (fermée)', 'icon' => 'check', 'classes' => array('success')),
    );

    public function displayPDFButton()
    {
        $ref = $this->getData('ref');

        if ($ref) {
            $file = DOL_DATA_ROOT . '/propale/' . $ref . '/' . $ref . '.pdf';
            if (file_exists($file)) {
                $url = DOL_URL_ROOT . '/document.php?modulepart=propal&file=' . htmlentities($ref . '/' . $ref . '.pdf');
                $onclick = 'window.open(\'' . $url . '\');';
                $button = '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
                $button .= '<i class="fas fa5-file-pdf iconLeft"></i>';
                $button .= $ref . '.pdf</button>';
                return $button;
            }
        }

        return $button;
    }
}
