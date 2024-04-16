<?php

class BS_Sac extends BimpObject
{
    public $tmp_ids = array();

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();
        if(isset($_REQUEST['nb_create'])){
            for($i=0; $i<$_REQUEST['nb_create']; $i++){
                $this->set('ref', BimpTools::getNextRef('bs_sac', 'ref', 'SAC'.$_REQUEST['code_centre'], 5));
                $errors = BimpTools::merge_array($errors, parent::create());
                $this->tmp_ids[] = $this->id;
            }
            
        }
        
        return $errors;
    }
    
    public function getDefaultCodeCentre()
    {
        if (BimpTools::isSubmit('code_centre')) {
            return BimpTools::getValue('code_centre', '', 'aZ09');
        } else {
            global $user;
            $userCentres = explode(' ', $user->array_options['options_apple_centre']);
            foreach ($userCentres as $code) {
                if (preg_match('/^ ?([A-Z]+) ?$/', $code, $matches)) {
                    return $matches[1];
                }
            }

            $id_entrepot = (int) $this->getData('id_entrepot');
            if (!$id_entrepot) {
                $id_entrepot = (int) BimpTools::getValue('id_entrepot', 0, 'int');
            }
            if ($id_entrepot) {
                global $tabCentre;
                foreach ($tabCentre as $code_centre => $centre) {
                    if ((int) $centre[8] === $id_entrepot) {
                        return $code_centre;
                    }
                }
            }
        }

        return '';
    }
    

    public function getListCentre($field, $include_empty = false)
    {
        if ($this->isLoaded())
            $value = $this->getData($field);
        else
            $value = '';

        return static::getUserCentresArray($value, $include_empty);
    }
    
    public function getCreateJsCallback()
    {
        $data = $this->createEtiquette($this->tmp_ids, 'normal');
        return $data['success_callback'];
    }
    
    public function getHeaderButtons()
    {
        $buttons = array();


        if ($this->isLoaded()) {
            $buttons[] = array(
                'label'   => 'Générer etiquette',
                'icon'    => 'fas_file-pdf',
                'onclick' => $this->getJsActionOnclick('printEtiquettes', array(
                    'id_objects' => $this->id,
                    'type'  => 'normal'
                        ))
            );

           
        }

        return $buttons;
    }
    
    
    public function createEtiquette($ids, $type, $qty = 1){
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        if (!count($ids)) {
            $errors[] = 'ID des sacs absent';
        } else {

            if (!$type) {
                $errors[] = 'Type d\'étiquette à générer absent';
            } else {

                $url = DOL_URL_ROOT . '/bimpsupport/etiquette_sac.php?id_sacs=' . implode(',', $ids) . '&qty=1&type=' . $type;

                $success_callback = 'window.open(\'' . $url . '\')';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }
    
    public function displaySav(){
        $html = '';
        $list = $this->getSav(false);
        foreach ($list as $sav)
            $html .= $sav->getLink().'<br/>';
        return $html;
    }
    
    public function getSav($open = true){
        $filter = array('sacs'=>array('part_type'=>'middle', 'part' => '['.$this->id.']'));
        if($open)
            $filter['status'] = array('operator'=> '!=', 'value'=>999);
        return BimpCache::getBimpObjectObjects('bimpsupport', 'BS_SAV', $filter);
    }
    
    public function actionPrintEtiquettes($data, &$success)
    {
        if(!is_array($data['id_objects']))
            $data['id_objects'] = array($data['id_objects']);
        return $this->createEtiquette($data['id_objects'], isset($data['type']) ? (string) $data['type'] : '', isset($data['qty']) ? (int) $data['qty'] : 1);

    }

  
}
