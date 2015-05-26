<?php
	class SMSDecanet extends CommonObject{
		var $expe='';
		var $dest='';
		var $message='';
		var $deferred='';
		var $priority='';
		var $class='';
		var $error;
		
		function SMSDecanet($DB) {
			
		}
		
		function SmsSenderList() {
			global $conf;
                        
			$obj = new stdClass();
                        @$obj->number = $conf->global->MAIN_MAIL_SMS_FROM;
			$from[] = $obj;
			return $from;
		}
		
		function SmsSend() {
			global $langs, $conf;
			$langs->load("smsdecanet@smsdecanet");
			$to = str_replace('+','',$this->dest);
			if(!preg_match('/^[0-9]+$/', $to)) {
				$this->error = $langs->trans('errorRecipient');
				return 0;
			}
			$donnees = array(
				'text'=>$this->message,
				'recipient'=>$to,
				'sendername'=>$this->expe,
				'messagetruncationallowed'=>'1',
				'action'=>'send',
				'login'=>$conf->global->DECANETSMS_EMAIL,
				'pass'=>$conf->global->DECANETSMS_PASS,
				'flash'=>($this->class==0)?1:0,
				'lang'=>$langs->defaultlang,
				'deferred'=>$this->deferred
				);
			$result = $this->sendRequest($donnees);
			if($result->code==1) {
				$this->error = $result->details;
				dol_syslog(get_class($this)."::SmsSend ".print_r($result, true), LOG_ERR);
				return 0;
			} else {
				return 1;
			}
		}
		
		function sendRequest($donnees) {
			global $conf;
			$url = (intval($conf->global->DECANETSMS_SSL)==1)?'https':'http';
			$url.='://www.decanet.fr/api/sms.php';	
			foreach($donnees as $key=>$value) { $donnees_ctn .= $key.'='.$value.'&'; }
			rtrim($donnees_ctn,'&');
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch,CURLOPT_POST,count($donnees));
			curl_setopt($ch,CURLOPT_POSTFIELDS,$donnees_ctn);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSLVERSION , 3);
			$data=curl_exec($ch);
			curl_close($ch);
			return json_decode($data);
		}

	}
?>
