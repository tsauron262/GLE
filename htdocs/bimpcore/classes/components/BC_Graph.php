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
        $this->params_def['mode'] = array('data_type' => 'string', 'default' => '');//rien ou doughnut
        $this->params_def['y'] = array('data_type' => 'string', 'default' => '');
        $this->params_def['data_callback'] = array('data_type' => 'string', 'default' => '');
        $this->params_def['yConfig'] = array('data_type' => 'array', 'default' => array());
        $this->params_def['use_k'] = array('data_type' => 'boolean', 'default' => 0);
        $this->params_def['relative'] = array('data_type' => 'boolean', 'default' => -1);
        $this->params_def['filters'] = array('data_type' => 'array', 'json' => true, 'default' => json_decode(BimpTools::getPostFieldValue('param_filters', '[]', 'json_nohtml'), true), 'request' => true);
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
        $html .= '<script '.BimpTools::getScriptAttribut().' src="'.BimpCore::getFileUrl('bimpcore/views/js/jquery.canvasjs.min.js').'"></script>';

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
                if(in_array('hour', $this->params['xDateConfig']['params']))
                        $this->formData['xDateConfig']['values']['hour'] = 'Heure';
                if(in_array('day', $this->params['xDateConfig']['params']))
                        $this->formData['xDateConfig']['values']['day'] = 'Jour';
                if(in_array('week', $this->params['xDateConfig']['params']))
                        $this->formData['xDateConfig']['values']['week'] = 'Semaine';
                if(in_array('month', $this->params['xDateConfig']['params']))
                        $this->formData['xDateConfig']['values']['month'] = 'Mois';
                if(in_array('year', $this->params['xDateConfig']['params']))
                        $this->formData['xDateConfig']['values']['year'] = 'Ans';
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
                    if($this->params['xDateConfig']['def'] == '5year'){
                        $date=date("Y-m-d", strtotime("-5 year"));
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
        if(isset($this->params['relative']) && $this->params['relative'] > -1){
            $this->formData['relative'] = array(
                'name' => 'Diférence',
                'type' => 'switch',
                'value'=> $this->params['relative']
            );
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
                        $this->addFieldFilterValue($this->fieldX, array('operator' => '>=', 'value' => $val));
                        break;
                    case 'date2':
                        $this->addFieldFilterValue($this->fieldX, array('operator' => '<=', 'value' => $val));
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
                case 'year' :
                    $options['axisX'] = array("title" => "Date", "valueFormatString" => 'YYYY');
                    break;
                case 'month' :
                    $options['axisX'] = array("title" => "Date", "valueFormatString" => 'MMM YYYY');
                    break;
                case 'day' :
                    $options['axisX'] = array("title" => "Date", "valueFormatString" => 'DD MMM YYYY');
                case 'week' :
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
        if($this->userOptions['xDateConfig'] == 'year'){
            $xFiled = 'date_format('.$xFiled.', \'%Y\')';
        }
        elseif($this->userOptions['xDateConfig'] == 'month'){
            $xFiled = 'date_format('.$xFiled.', \'%Y-%m\')';
        }
        elseif($this->userOptions['xDateConfig'] == 'day'){
            $xFiled = 'date_format('.$xFiled.', \'%Y-%m-%d\')';
        }
        elseif($this->userOptions['xDateConfig'] == 'hour'){
            $xFiled = 'date_format('.$xFiled.', \'%Y-%m-%d %h:00:00\')';
        }
        elseif($this->userOptions['xDateConfig'] == 'week'){
            $xFiled = 'MIN(date_format('.$xFiled.', \'%Y-%m-%d\'))';
        }
        $return_fields = array($xFiled.' as x');//.$this->fieldX);
        
        
        $groupBy = ($this->params['mode'] == 'doughnut')? null : 'x';
        if($this->userOptions['xDateConfig'] == 'week'){
            $groupBy = 'date_format('.$this->fieldX.', \'%Y-%u\')';
        }
        
        
        $joins = array();
        $i = 0;
        if(isset($params['fields'])){
            foreach($params['fields'] as $nameField => $tabField){
                $field = $nameField;
                if(is_array($tabField) && isset($tabField['field']))
                    $field = $tabField['field'];
                $calc = 'SUM';
                if(is_array($tabField) && isset($tabField['calc']))
                    $calc = $tabField['calc'];
                $type = 'column';
                if(is_array($tabField) && isset($tabField['type']))
                    $type = $tabField['type'];
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
                
                if($this->params['mode'] == 'doughnut'){
                    if(!isset($data)){
                        $data = array(
                            'type' => 'doughnut',
                            'dataPoints'=> array()
                        );
                    }
                    else{
                        $i = 0;
                    }
                }
                else{
                    $data = array(
                        'name'      => $name,
                        'type' => $type,
                        'visible'   => $visible,
                        'dataPoints'=> array()
                    );
                }
                
                $filters = $this->params['filters'];
                if(isset($tabField['filters']) && is_array($tabField['filters'])){
                    foreach($tabField['filters'] as $field_name => $value)
                        $filters = BimpTools::mergeSqlFilter($filters, $field_name, $value);
                }
                
                $oldValue = null;
                if($this->userOptions['relative'] == 1){
                    $filtersOldValue = $filters;
                    if(isset($this->userOptions['date1']))
                        $filtersOldValue[$this->fieldX] = array('operator' => '<', 'value' => $this->userOptions['date1']);
                    $resultOldValue = $this->object->getList($filtersOldValue, 1, 1, $this->fieldX, 'DESC', 'array', $return_fields, $joins, null, 'ASC');
                    if(isset($resultOldValue[0]))
                        $oldValue = $resultOldValue[0]['y'];
                }
                
                $result = $this->object->getList($filters, 10000, 1, $this->fieldX, 'ASC', 'array', $return_fields, $joins, null, 'ASC', $groupBy);
                
                /*
                 * todo rajouter pour le relative la recup de la derniére oldValue avant la date de début.
                 */
                
                foreach($result as $tmp){
                    $y = floatval($tmp['y']);
                    if($this->userOptions['relative'] == 1 && !is_null($oldValue))
                        $y = $y - $oldValue;
                        
                    if(is_array($tabField) && isset($tabField['reverse']) && $tabField['reverse'])
                        $y = -$y;
                    if($this->userOptions['relative'] != 1 || !is_null($oldValue))
                        $data['dataPoints'][] = array('x' => "new Date('".$tmp['x']."')", 'y' => $y, 'name'=> $name);
                    $oldValue = floatval($tmp['y']);
                }
                $datas[$i] = $data;
                $i++;
            }
        }
        return $datas;
    }
}