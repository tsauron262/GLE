<?php
	class SMSDecanet {
		var $expe='';
		var $dest='';
		var $message='';
		var $deferred='';
		var $priority='';
		var $class='';
		var $error = false;
		
		function SMSDecanet($DB) {
			
		}
		
		function SmsSenderList() {
			global $conf;
			
			$from = unserialize($conf->global->DECANETSMS_FROM);
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
			if(isset($result->error)) {
				$this->error = $result->error;
				return 0;
			} else {
				return $result->message_id;
			}
		}
		
		function sendRequest($donnees) {
			global $conf;
			$url = (intval($conf->global->DECANETSMS_SSL)==1)?'https':'http';
			$url='https://www.decanet.fr/api/sms.php';	
			foreach($donnees as $key=>$value) { $donnees_ctn .= $key.'='.$value.'&'; }
			rtrim($donnees_ctn,'&');
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch,CURLOPT_POST,count($donnees));
			curl_setopt($ch,CURLOPT_POSTFIELDS,$donnees_ctn);
			$data=curl_exec($ch);
			curl_close($ch);
			return json_decode($data);
		}

	}
?>