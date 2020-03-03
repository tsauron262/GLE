<?php

class BimpRelanceClientsLine extends BimpObject
{

    const RELANCE_ATTENTE_MAIL = 0;
    const RELANCE_ATTENTE_COURRIER = 1;
    const RELANCE_OK_MAIL = 10;
    const RELANCE_OK_COURRIER = 11;
    const RELANCE_ABANDON = 20;

    public static $status_list = array(
        self::RELANCE_ATTENTE_MAIL     => array('label' => 'En attente d\'envoi par mail', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::RELANCE_ATTENTE_COURRIER => array('label' => 'En attente d\'envoi par courrier', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::RELANCE_OK_MAIL          => array('label' => 'Envoyée par e-mail', 'icon' => 'fas_check', 'classes' => array('success')),
        self::RELANCE_OK_COURRIER      => array('label' => 'Envoyée par courrier', 'icon' => 'fas_check', 'classes' => array('success')),
        self::RELANCE_ABANDON          => array('label' => 'Relance abandonnée', 'icon' => 'fas_times', 'classes' => array('danger'))
    );

    public function getPdfFilepath()
    {
        $file = $this->getData('pdf_file');

        if ($file) {
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                return $client->getFilesDir() . '/' . $file;
            }
        }

        return '';
    }

    public function getPdfFileUrl()
    {
        $file = $this->getData('pdf_file');
        if ($file) {
            $client = $this->getChildObject('client');
            if (BimpObject::objectLoaded($client)) {
                return $client->getFileUrl($file);
            }
        }

        return '';
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $url = $this->getPdfFileUrl();
            if ($url) {
                $buttons[] = array(
                    'label'   => 'Fichier PDF',
                    'icon'    => 'fas_file-pdf',
                    'onclick' => 'window.open(\'' . $url . '\');'
                );
            }

            $status = (int) $this->getData('status');
            if ($status < 10) {
                $buttons[] = array(
                    'label'   => 'Courrier envoyé',
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsNewStatusOnclick(self::RELANCE_OK_COURRIER)
                );
                $buttons[] = array(
                    'label'   => 'Abandonner',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsNewStatusOnclick(self::RELANCE_ABANDON)
                );
            } else {
                $buttons[] = array(
                    'label'   => 'Remettre en attente',
                    'icon'    => 'fas_undo',
                    'onclick' => $this->getJsNewStatusOnclick(self::RELANCE_ATTENTE_COURRIER)
                );
            }
        }

        return $buttons;
    }

    public function getListBulkActions()
    {
        $actions = array();

        $actions[] = array(
            'label'   => 'Courriers envoyés',
            'icon'    => 'fas_check',
            'onclick' => 'setSelectedObjectsNewStatus($(this), \'list_id\', ' . self::RELANCE_OK_COURRIER . ', {}, \'Veuillez confirmer l\\\'envoi des relances sélectionnées\')'
        );

        $actions[] = array(
            'label'   => 'Abandonner',
            'icon'    => 'fas_times',
            'onclick' => 'setSelectedObjectsNewStatus($(this), \'list_id\', ' . self::RELANCE_ABANDON . ', {}, \'Veuillez confirmer l\\\'abandon des relances sélectionnées\')'
        );

        $actions[] = array(
            'label'   => 'Remettre en attente',
            'icon'    => 'fas_undo',
            'onclick' => 'setSelectedObjectsNewStatus($(this), \'list_id\', ' . self::RELANCE_ATTENTE_COURRIER . ', {}, \'Veuillez confirmer la mise en attente d\\\'envoi des relances sélectionnées\')'
        );

        return $actions;
    }

    public function displayFactures()
    {
        $html = '';

        $factures = $this->getData('factures');

        if (is_array($factures)) {
            foreach ($factures as $id_fac) {
                $html .= ($html ? '<br/>' : '');
                $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);
                if (BimpObject::objectLoaded($fac)) {
                    $html .= $fac->getLink();
                } else {
                    $html .= '<span class="danger">La facture d\'ID ' . $id_fac . ' n\'existe plus</span>';
                }
            }
        }

        return $html;
    }

    public function onNewStatus($new_status, $current_status, $extra_data, $warnings)
    {
        if ($this->isLoaded()) {
            if ($new_status >= 10) {
                global $user;
                $this->set('date_send', date('Y-m-d H:i:s'));
                if (BimpObject::objectLoaded($user)) {
                    $this->set('id_user_send', (int) $user->id);
                }
            } else {
                $this->set('date_send', '0000-00-00 00:00:00');
                $this->set('id_user_send', 0);
            }

            $this->update($w, true);
        }
    }
}
