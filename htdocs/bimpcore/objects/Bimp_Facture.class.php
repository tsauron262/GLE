<?php

class Bimp_Facture extends BimpObject
{

    public static $status_list = array(
        0 => array('label' => 'Brouillon', 'icon' => 'file-text', 'classes' => array('warning')),
        1 => array('label' => 'Validée', 'icon' => 'check', 'classes' => array('info')),
        2 => array('label' => 'Fermée', 'icon' => 'check', 'classes' => array('success')),
        3 => array('label' => 'Abandonnée', 'icon' => 'times-circle', 'classes' => array('danger')),
    );

    public function displayPaid()
    {
        if ($this->isLoaded()) {
            $paid = $this->dol_object->getSommePaiement();
            return BimpTools::displayMoneyValue($paid, 'EUR');
        }

        return '';
    }

    public function displayPDFButton()
    {
        $ref = $this->getData('facnumber');

        if ($ref) {
            $file = DOL_DATA_ROOT . '/facture/' . $ref . '/' . $ref . '.pdf';
            if (file_exists($file)) {
                $url = DOL_URL_ROOT . '/document.php?modulepart=facture&file=' . htmlentities($ref . '/' . $ref . '.pdf');
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
