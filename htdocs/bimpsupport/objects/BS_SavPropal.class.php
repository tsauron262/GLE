<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/Bimp_Propal.class.php';

class BS_SavPropal extends Bimp_Propal
{

    public function getModelsPdfArray()
    {
        return array(
            'bimpdevissav' => 'Devis SAV'
        );
    }
    
    public function getObjectForEmailsLogs()
    {
        $sav = $this->getSav();
        
        if (BimpObject::objectLoaded($sav)) {
            return $sav;
        }
        
        return $this;
    }

    public function getLinesListHeaderExtraBtn()
    {
        $buttons = array();

        // Remise globale: 
//        if ($this->isActionAllowed('setRemiseGlobale') && $this->canSetAction('setRemiseGlobale')) {
//            $buttons[] = array(
//                'label'       => 'Remise globale',
//                'icon_before' => 'percent',
//                'classes'     => array('btn', 'btn-default'),
//                'attr'        => array(
//                    'onclick' => $this->getJsActionOnclick('setRemiseGlobale', array('remise_globale' => (float) $this->getData('remise_globale')), array(
//                        'form_name' => 'remise_globale'
//                    ))
//                )
//            );
//        }

        return $buttons;
    }

    public function actionSetRemiseGlobale($data, &$success)
    {
        $result = parent::actionSetRemiseGlobale($data, $success);
        $result['success_callback'] = 'bimp_reloadPage();';
        return $result;
    }

    public function getUrl($forced_context = '')
    {
        if ($this->isLoaded()) {
            $sav = $this->getSav();
            if (BimpObject::objectLoaded($sav)) {
                return $sav->getUrl($forced_context) . '&navtab-maintabs=devis';
            }

            return DOL_URL_ROOT . '/bimpcommercial/index.php?fc=propal&id=' . $this->id;
        }

        return '';
    }

    public function getFilesArray($with_deleted = 0)
    {
        if ($this->isLoaded()) {
            return BimpCache::getObjectFilesArray('bimpcommercial', 'Bimp_Propal', $this->id, $with_deleted);
        }

        return array();
    }
}
