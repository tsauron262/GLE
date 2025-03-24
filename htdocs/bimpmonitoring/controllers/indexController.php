<?php

require_once DOL_DOCUMENT_ROOT . '/bimpreservation/controllers/reservationController.php';

class indexController extends BimpController
{
    public function renderMonitoringView(){
		error_reporting(E_ALL);
		ini_set('display_errors', 1);


		$html = '';
		foreach($this->getInstancesInfos() as $returnPriority => $returns) {
			foreach ($returns as $return) {
				if ($return['error'] == 1) {
					$class = 'error';
				} elseif ($return['warning'] == 1) {
					$class = 'warning';
				} else {
					$class = 'info';
				}

				$content = '<pre>' . print_r($return, true) . '</pre>';


				$content = BimpRender::renderPanel($return['url'], $content, '', array('open' => ($return['error'] == 1 || $return['warning'] == 1), 'panel_class' => $class));

				$xs = 12;
				$sm = 6;
				$md = 4;
				$html .= '<div class="col_xs-' . $xs . ' col-sm-' . $sm . ' col-md-' . $md . '">' . $content . '</div>';

			}
		}

        return $html;
    }

	public function getInstancesInfos(){
		$returns = array();
		$objs = BimpCache::getBimpObjectObjects($this->module, 'bimpmonitoring', array('actif'=>1));
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

			if(isset($return['check_versions_lock']) && $return['check_versions_lock'] == 1){
				$warning = 1;
			}
			$return['url'] = $obj->getData('url');
			$return['error'] = $error;
			$return['warning'] = $warning;
			$returns[($error*2+$warning)][] = $return;
		}
		$returns = array_reverse($returns);
		return $returns;
	}

    public function getInfos($obj){

        if($obj->getData('is_bimperp') == 1){
            $url = str_replace('//bimpmonitoring', '/bimpmonitoring', $obj->getData('url').'/bimpmonitoring/public/info.php');
        }
        else{
            $url = $obj->getData('url');
        }
		$ctx = stream_context_create(array('http'=>
											   array(
												   'timeout' => 1,  //1200 Seconds is 20 Minutes
											   )
		));

		$returnT = file_get_contents($url, false, $ctx);
		if ($returnT === false) {
			$return = array('status' => 0, 'error'=> 'instance '.$obj->getData('url').' timeout', 'url' => $url);
		}
		elseif ($returnT == '') {
			$return = array('status' => 0, 'error'=> 'instance '.$obj->getData('url').' retour vide', 'url' => $url);
		}
		else{
			$test = $obj->getData('test');
			if($test == '') {
				$return = json_decode($returnT, true);
			}
			else{
				if(stripos($returnT, $test) !== false){
					$return = array('status' => 1);
				}
				else{
					$return = array('status' => 0, 'error'=> 'test "'.$test.'" non trouvé', 'url' => $url);
				}
			}
		}

        if(!is_array($return) || !isset($return['status'])){
            $return = array('status' => 0, 'error'=> 'pas de recuperation des donnees', 'url' => $url, 'retour brut' => '<iframe>'.$returnT.'</iframe>');
        }

        return $return;
    }
}
