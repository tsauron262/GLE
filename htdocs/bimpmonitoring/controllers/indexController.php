<?php

require_once DOL_DOCUMENT_ROOT . '/bimpreservation/controllers/reservationController.php';

class indexController extends BimpController
{
    public function renderMonitoringView(){
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
        $objs = BimpCache::getBimpObjectObjects($this->module, 'bimpmonitoring', array());
        $html = '';
        foreach ($objs as $obj) {
            $return = $this->getInfos($obj);

			$error = $warning = 0;

            if($return['status'] == 0){
				$error = 1;
            }
			if(isset($return['lastlogin'])) {
				$lastLog = strtotime($return['lastlogin']);
				$nbJours = (time() - $lastLog) / (60*60*24);
				if($nbJours > 25) {
					$warning = 1;
				}
				$return['lastlogin'] .= ' ('.round($nbJours).' jours)';
			}

			if($error == 1){
				$class = 'error';
			}
			elseif($warning == 1){
				$class = 'warning';
			}
			else{
				$class = 'info';
			}

            $content = '<pre>'.print_r($return, true).'</pre>';


            $content = BimpRender::renderPanel($obj->getData('url'), $content, '', array('open' => true, 'panel_class'=> $class));

            $xs = 12;
            $sm = 6;
            $md = 4;
            $html .= '<div class="col_xs-'.$xs.' col-sm-'.$sm.' col-md-'.$md.'">'.$content.'</div>';

        }


        return $html;
    }

    public function getInfos($obj){

        if($obj->getData('is_bimperp') == 1){
            $url = str_replace('//bimpmonitoring', '/bimpmonitoring', $obj->getData('url').'/bimpmonitoring/public/info.php');
        }
        else{
            $url = $obj->getData('url');
        }
//        die($_SERVER['HTTP_HOST']);
        if(stripos($url, $_SERVER['HTTP_HOST']) !== false){
            $tabTmp = explode($_SERVER['HTTP_HOST'], $url);
//            $url = '127.0.0.1'.$tabTmp[1];
            $url = DOL_DOCUMENT_ROOT.'../'.$tabTmp[1];
            if(is_file($url))
                require($url);
            else
                $return = array('status' => 0, 'error'=> 'instance local '.$obj->getData('url').' introuvable');
        }
        else{
            $ctx = stream_context_create(array('http'=>
                                                   array(
                                                       'timeout' => 1,  //1200 Seconds is 20 Minutes
                                                   )
            ));

            $return = file_get_contents($url, false, $ctx);
            if ($return === false) {
                $return = array('status' => 0, 'error'=> 'instance '.$obj->getData('url').' timeout', 'url' => $url);
            }
            elseif ($return == '') {
                $return = array('status' => 0, 'error'=> 'instance '.$obj->getData('url').' retour vide', 'url' => $url);
            }
            else{
                $return = json_decode($return, true);
            }
        }

        if(!is_array($return) || !isset($return['status'])){
            $return = array('status' => 0, 'error'=> 'pas de recuperation des donnees', 'url' => $url);
        }

        return $return;
    }
}
