<?php

class BC_Graph extends BC_Panel
{
    
    public $component_name = 'Graph';
    public static $type = 'graph';
    
    public $fieldX = '';
    
    
    public function __construct(\BimpObject $object, $name, $content_only = false, $level = 1, $title = null, $icon = null, $id_config = null) {
        
        $this->params_def['options'] = array('data_type' => 'array', 'default' => array());
        $this->params_def['xDateConfig'] = array('data_type' => 'array', 'default' => array());
        $this->params_def['date'] = array('data_type' => 'array', 'default' => array());
        $this->params_def['x'] = array('data_type' => 'string', 'default' => '');
        $this->params_def['y'] = array('data_type' => 'string', 'default' => '');
        $this->params_def['data_callback'] = array('data_type' => 'string', 'default' => '');
        $this->params_def['yConfig'] = array('data_type' => 'array', 'default' => array());
        $this->params_def['use_k'] = array('data_type' => 'boolean', 'default' => 0);
        $this->params_def['filters'] = array('data_type' => 'array', 'json' => true, 'default' => json_decode(BimpTools::getPostFieldValue('param_filters', '[]'), true), 'request' => true);
//        echo '<pre>';
//        print_r($_POST);
//        echo '<br/><br/>';
//        print_r(BimpTools::getPostFieldValue('param_filters'));die('rr');
        
        return parent::__construct($object, $name, 'graph', $content_only, $level, $title, $icon, $id_config);
    }
    
    
    
    public function renderHtmlContent() {
        if (count($this->errors)) {
            return parent::renderHtml();
        }
        
        $html = '';
        $html .= '<div class="chartOption"></div>';
        $html .= '<div class="chartContainer" style="height: 800px; width: 100%;"></div>';
        $html .= '<script src="https://canvasjs.com/assets/script/jquery.canvasjs.min.js"></script>';

        return $html;
    }
    
    public function initUserData($dataForm){
        /* recup des champ client*/
        $dataTmp = explode('&', $dataForm);
        $dataClient = array();
        foreach($dataTmp as $val){
            $dataTmp2 = explode('=', $val);
            if(isset($dataTmp2[1]))
                $dataClient[$dataTmp2[0]] = $dataTmp2[1];
        }
        
        
        $this->formData = $this->params['options'];
        
        if(is_array($this->params['xDateConfig']) && count($this->params['xDateConfig'])){
            if(isset($this->params['xDateConfig']['field']))
                $this->fieldX = $this->params['xDateConfig']['field'];
            if(is_array($this->params['xDateConfig']['params']) && count($this->params['xDateConfig']['params'])){
                $this->formData['xDateConfig'] = array(
                    'name'      => 'Affichage',
                    'type'      => 'select',
                    'values'    => array(),
                    'value'     => $this->params['xDateConfig']['params'][0]
                );
                if(in_array('day', $this->params['xDateConfig']['params']))
                        $this->formData['xDateConfig']['values']['day'] = 'Jour';
                if(in_array('month', $this->params['xDateConfig']['params']))
                        $this->formData['xDateConfig']['values']['month'] = 'Mois';
                if(in_array('hour', $this->params['xDateConfig']['params']))
                        $this->formData['xDateConfig']['values']['hour'] = 'Heure';
            }
           
            if(isset($this->params['xDateConfig']['date1']) && $this->params['xDateConfig']['date1']){
                $this->formData['date1'] = array(
                    'name' => 'Du',
                    'type' => 'date'
                );
                if(isset($this->params['xDateConfig']['def']) && $this->params['xDateConfig']['def']){
                    $date = null;
                    if($this->params['xDateConfig']['def'] == '1year'){
                        $date=date("Y-m-d", strtotime("-1 year"));
                    }
                    if($this->params['xDateConfig']['def'] == '1month'){
                        $date=date("Y-m-d", strtotime("-1 month"));
                    }
                    if($date){
                        $this->formData['date1']['value'] = $date;//'2023-04-01';
                    }
                }
            }
           
            if(isset($this->params['xDateConfig']['date2']) && $this->params['xDateConfig']['date2']){
                $this->formData['date2'] = array(
                    'name' => 'Au',
                    'type' => 'date'
                );
                if(isset($this->params['xDateConfig']['def']) && $this->params['xDateConfig']['def']){
//                    if($this->params['xDateConfig']['def'] == '1year'){
//                        $this->formData['date2']['value'] = '2023-04-01';
//                    }
                }
            }
        }
        
        foreach($this->formData as $tmpName => $tmpData){
        }
        
        $this->userOptions = array();
        foreach($this->formData as $input_name => $datas){
            $val = '';
            if(isset($dataClient[$input_name])){
                $val = $dataClient[$input_name];
            }
            elseif(isset($datas['value'])){
                $val = $datas['value'];
            }
            $this->formData[$input_name]['value'] = $val;
            $this->userOptions[$input_name] = $val;
            
            if($val != ''){
                switch($input_name) {
                    case 'date1':
                        $this->addFieldFilterValue($this->fieldX, array('operator' => '>', 'value' => $val));
                        break;
                    case 'date2':
                        $this->addFieldFilterValue($this->fieldX, array('operator' => '<', 'value' => $val));
                        break;
                }
            }
        }
        
    }
    
    public function renderForm(){
        //gestion des options
        if(isset($this->formData) && count($this->formData)){
            
            $formHtml = '<form id="'. $list_id . '_' . $data['idGraph'] . '_chartForm">';
            $formHtml .= '<table class="bimp_list_table">';
            $formHtml .= '<tbody class="headers_col">';
            foreach($this->formData as $input_name => $optionData){
                $value = (isset($optionData['value'])? $optionData['value'] : '');
                $dataGraphe['params'][$input_name] = $value;
                $optionsInput = array();
                if(isset($optionData['values']))
                    $optionsInput['options'] = $optionData['values'];
                $formHtml .= '<tr><th>'.$optionData['name'].'</th><td>'.BimpInput::renderInput($optionData['type'], $input_name, $value, $optionsInput).'</td></tr>';
                
                
            }
            $formHtml .= '</tbody></table>'.BimpRender::renderButton(array('label' => 'Valider', 'classes'=>array('btnRefreshGraph'), 'type' => 'primary'));
            $formHtml .= '</form>';
            return $formHtml;
        }
    }
    
    public function getDatas($dataForm){
        global $modeCSV, $modeGraph;
        $modeCSV = $modeGraph = true;
        $success = "";
        $errors = array();
        $warnings = array();
        
        $this->initUserData($dataForm);


        $nameGraph = $this->name;
        
        
        

        $options = array();
        
        
        $options['animationEnabled'] = true;
        $options['theme'] = "light2";
        if($this->params['title'] != '')
            $options['title'] = array("text" => $this->params['title']);
        else
            $options['title'] = array("text" => $this->object->getLabel('', true));
        if($this->userOptions['xDateConfig']){
            switch ($this->userOptions['xDateConfig']) {
                case 'month' :
                    $options['axisX'] = array("title" => "Date", "valueFormatString" => 'MMM YYYY');
                    break;
                case 'day' :
                    $options['axisX'] = array("title" => "Date", "valueFormatString" => 'DD MMM YYYY');
                    break;
                default:
                    $options['axisX'] = array("title" => "Date", "valueFormatString" => 'HH:mm:ss DD MMM YYYY');
            }
        }
        else
            $options['axisX'] = array("title" => $this->params['x']);
        $options['axisY'] = array("title" => $this->params['y']);
        $options['toolTip'] = array("shared" => true);
        $options['legend'] = array(
            "cursor"             => "pointer",
            "verticalAlign"      => "top",
            "horizontalAlign"    => "left",
            "dockInsidePlotArea" => false,
            "itemclick"          => "toogleDataSeries",
        );
        
        
        $method = $this->params['data_callback'];
        if($method &&  method_exists($this->object, $method)){
            $dataGraphe = $this->object->$method($this->userOptions);
        }
        elseif(count($this->params['yConfig'])){
            if(method_exists($this->object, 'prepareForGraph')){
                $dataGraphe = $this->object->prepareForGraph();
            }
            $dataGraphe = $this->getDatasInfos($this->params['yConfig']);
        }
        else{
            $errors[] = 'Aucune methode pour charger le content';
        }
        
        $useK = false;
        if($this->params['use_k']){
            foreach($dataGraphe as $tmp1){
                foreach($tmp1['dataPoints'] as $tmp2){
                    if($tmp2['y'] > 200000 || $tmp2['y'] < -200000){
                        $useK = true;
                        $options['axisY']["title"] = 'k'.$options['axisY']["title"];
                        break 2;
                    }
                }
            }
        }
        if($useK){
            foreach($dataGraphe as $id1 => $tmp1){
                foreach($tmp1['dataPoints'] as $id2 =>$tmp2){
                    $dataGraphe[$id1]['dataPoints'][$id2]['y'] = round($tmp2['y'] / 1000);
                }
            }
        }
        
        $tmpDataStatic = array();
        $tmpDataStatic["type"] = "line";
        $tmpDataStatic["showInLegend"] = true;
        $tmpDataStatic["markerType"] = "square";

        if (isset($options['axisX']['valueFormatString']))
            $tmpDataStatic['xValueFormatString'] = $options['axisX']['valueFormatString'];
        if (isset($options['axisY']['valueFormatString']))
            $tmpDataStatic['yValueFormatString'] = $options['axisY']['valueFormatString'];
        foreach($dataGraphe as $i => $tmpData){
            $dataGraphe[$i] = BimpTools::overrideArray($tmpDataStatic, $tmpData);
        }
        $options['data'] = $dataGraphe;
        
//            echo '<pre>'; print_r($options);

        $optionsJson = json_encode($options);
        $optionsJson = str_replace('"new Date', ' new Date', $optionsJson);
        $optionsJson = str_replace('","y"', ',"y"', $optionsJson);
        $optionsJson = str_replace('"toogleDataSeries"', 'toogleDataSeries', $optionsJson);
//            }';
        return array(
            'errors'           => $errors,
            'formHtml'         => $this->renderForm(),
            'options'          => $optionsJson,
            'warnings'         => $warnings,
//            'success_callback' => $success_callback
        );
    }
    
    
    public function addFieldFilterValue($field_name, $value)
    {
        $this->params['filters'] = BimpTools::mergeSqlFilter($this->params['filters'], $field_name, $value);
    }
    
    
    public function getDatasInfos($params){
        $datas= array();
        $xFiled = $this->fieldX;
        if($this->userOptions['xDateConfig'] == 'month'){
            $xFiled = 'date_format('.$xFiled.', \'%Y-%m\')';
        }
        elseif($this->userOptions['xDateConfig'] == 'day'){
            $xFiled = 'date_format('.$xFiled.', \'%Y-%m-%d\')';
        }
        elseif($this->userOptions['xDateConfig'] == 'hour'){
            $xFiled = 'date_format('.$xFiled.', \'%Y-%m-%d %h:00:00\')';
        }
        $return_fields = array($xFiled.' as x');//.$this->fieldX);
        $joins = array();
        if(isset($params['fields'])){
            foreach($params['fields'] as $nameField => $tabField){
                $field = $nameField;
                if(is_array($tabField) && isset($tabField['field']))
                    $field = $tabField['field'];
                $calc = 'SUM';
                if(is_array($tabField) && isset($tabField['calc']))
                    $calc = $tabField['calc'];
                $name = '';
                if(is_array($tabField) && isset($tabField['title']))
                    $name = $tabField['title'];
                else{
                    $name = $this->object->getConf('fields/'.$nameField.'/label', array(), true, 'array');
                }
                $visible = 1;
                if(is_array($tabField) && isset($tabField['visible']))
                    $visible = $tabField['visible'];
                
                $return_fields[] = $calc.'('.$field.') as y';
                $data = array(
                    'name'      => $name,
                    'type' => 'column',
                    'visible'   => $visible,
                    'dataPoints'=> array()
                );
                $groupBy = 'x';
                $result = $this->object->getList($this->params['filters'], 10000, 1, null, null, 'array', $return_fields, $joins, null, 'ASC', $groupBy);
                foreach($result as $tmp)
                    $data['dataPoints'][] = array('x' => "new Date('".$tmp['x']."')", 'y' => floatval($tmp['y']));
                $datas[] = $data;
            }
        }
        
//        echo '<pre>';
//        print_r($datas);
        return $datas;
    }
    
    
    public function getGraphConsoDatas($params)
    {
        $data = array();  
        $cmds = BimpCache::getBimpObjectObjects('en', 'en_cmd', array(
            'generic_type'      => 'CONSUMPTION',
            'role'              => array('operator' => '>', 'value' => '0')
        ), 'role', $sortorder = 'desc');

        $i=0;
        foreach($cmds as $cmdData){
            $data[$i] = array("name" => $cmdData->getData('name'));
            if($params['type'] == 'columnArea'){
                if($cmdData->getData('role') == 1)
                    $data[$i]["type"] = "stackedColumn";
                elseif($cmdData->getData('role') > 1)
                    $data[$i]["type"] = "stackedArea";
            }
            else{
                $data[$i]["type"] = $params['type'];
            }
            if($cmdData->getData('role') > 1 || $cmdData->getData('id_cmd_parent') > 0)
                $data[$i]["visible"] = 0;


            $fields = array('date' => 'date', 'value' => 'value');
            $where = "cmd_id = ".$cmdData->id;
            if($params['date1'])
                $where .= " AND date > '".$params['date1']."'";
            if($params['date2'])
                $where .= " AND date < '".$params['date2']."'";

            if($params['xDateConfig'] == 'month'){
                $fields['value'] = 'MAX(value) as value';
                $fields['date'] = 'date_format(date, \'%Y-%m\') as date';
                $where .= ' GROUP BY date_format(date, \'%Y-%m\')';
            }

            $histos = $this->db->getRows('en_historyArch2', $where, null, 'object', $fields, 'date', 'ASC');
            $oldValue = null;
            foreach($histos as $histo){
                $val = $histo->value;
                if($params['dif'] == 1 && !is_null($oldValue))
                    $val = $val - $oldValue;
                if($cmdData->getData('role') == 3)
                    $val = -$val;
                if($params['dif'] != 1 || !is_null($oldValue))
                    $data[$i]['dataPoints'][] = array("x" => "new Date('".$histo->date."')", "y" => (int) ($val));
                $oldValue = $histo->value;
            }
            $i++;
        }
        return $data;
    }
}
