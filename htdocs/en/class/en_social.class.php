<?php

class en_social
{
	private $appId = '1839410170148852';
//	private $appId = '1685319125525164';
	private $appSecret = "f8169d1cb7330e0053ff9f5a3245b1f7"; // Replace with your Facebook App Secret
	private $url = '';
	private $token = '';
	private $mode = ''; // fb or ig
	private $method = 'GET';
//	private $pageId = '';

	/*
	 * id configuration 688737993799979
	 */


	public function __construct($mode, $token)
	{
		if ($mode == 'fb') {
			$this->url = 'https://graph.facebook.com/v12.0';
//			$this->token = 'EAAaI7w3JtZCQBOZCEXwZC0I5tSlurTUZA5ysgmZBsZAh1GQc42MQD3FjZAOsiuP9eogl74fdqwExa6YxZBZBJH3Q1MFLMEpjpZBeZBYSQZBX5PFeLzMd3K80YzxuxdIBwKBfrWMrdojtZCWNnwU5wA82aqXQR2EPk56sNxfvI22bx0oeSkUPa0wF1ERyI0MCt';
//			$this->pageId = '183853124811817';
		} elseif ($mode == 'ig') {
			$this->url = 'https://graph.instagram.com/v12.0';
			$this->appId = '4058718447734278';
			$this->appSecret = 'ec5350f3b425726911499ba1ac8ff37a';
//			$this->token = 'IGAA5rYgdSDgZABZAE04Mi1QeXNlMno5bHZAOM2R2TnhMVEtkTGdBOVlydjFzYnFKNDQ0bm1lWGZAUUzRZAR0VaWlcwRHdlV29fS1NoajNoVXZAjeGY2dUVONkplbHQ2UDNlZA1I5UTRVbF9teEFtUGxEZA3hVM215eVpleEVOdjBjaWRXSQZDZD';
//			$this->pageId = '9524903440933786';
		}elseif ($mode == 'igc') {
			$this->url = 'https://api.instagram.com';
			$this->method = 'POST';
			$this->appId = '4058718447734278';
			$this->appSecret = 'ec5350f3b425726911499ba1ac8ff37a';
//			$this->token = 'IGAA5rYgdSDgZABZAE04Mi1QeXNlMno5bHZAOM2R2TnhMVEtkTGdBOVlydjFzYnFKNDQ0bm1lWGZAUUzRZAR0VaWlcwRHdlV29fS1NoajNoVXZAjeGY2dUVONkplbHQ2UDNlZA1I5UTRVbF9teEFtUGxEZA3hVM215eVpleEVOdjBjaWRXSQZDZD';
//			$this->pageId = '9524903440933786';
			$mode = 'ig';
		}

		else {
			die('mode inconnue' . $mode);
		}
		$this->token = $token;
		$this->mode = $mode;
	}

	public function getPageToken($pageId)
	{
		$url = $this->url . "/{$pageId}?fields=access_token&access_token={$this->token}";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		if ($response === false) {
			echo "cURL Error: " . curl_error($ch);
		} else {
			$data = json_decode($response, true);
			if (isset($data["access_token"])) {
				return $data["access_token"];
			}
		}
		return null;
	}

	public function getPages()
	{
		$url = $this->url . "/me/accounts?type=page&access_token={$this->token}";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);

		$return = array();
		$followers = 0;
		if ($response === false) {
			echo "cURL Error: " . curl_error($ch);
		} else {
			$data = json_decode($response, true);
			if (isset($data["data"])) {
				foreach ($data["data"] as $page) {
					$return[$page['id']] = $page['name'];

				}
			}
		}
		return $return;
	}


	public function getCodeToToken()
	{
		/*
		 * FB
		 */
//		$shortLivedToken = "EAAaI7w3JtZCQBOzDiZAx7BWVPB3hfuZATeMYDzmXA6tZAgFv13azRVXptNyhDruLh52JjVC8vAyXNEGG2Mk3iUVim4YZBfSI6AtaX3Ecwgz7YNKB1VwYVVGHhGB56I7yuLisMQjaADSZBRmZCO2AXMWeVJPuLmEBGDTu0FxZCRD1EaxOzPFpsj01wwzvTJrVc6rTI0ZAeURSYbktLRFi69WoCo7JgnJGU"; // Replace with your short-lived token


		$data = array(
			'grant_type'                      => 'authorization_code',
			'client_id'                       => $this->appId,
			'client_secret'                   => $this->appSecret,
			'redirect_uri'					  => 'https://erp.loucreezart.fr/lou1/en/public/social.php',
			'code' 							  => $this->token
		);
		$response = $this->sendCurl($this->url . "/oauth/access_token", $data);

		if ($response === false) {
//			echo "cURL Error: " . curl_error($ch);
		} else {
			$data = json_decode($response, true);

			if (isset($data['access_token'])) {
				$longLivedToken = $data['access_token'];
//				echo "Long-lived access token: " . $longLivedToken;
			} else {
				echo "Unable to refresh token. Response: " . print_r($data, true);
			}
		}

		return $longLivedToken;
	}

	public function sendCurl($url, $data = array())
	{
		$ch = curl_init();

		if($this->method == 'GET'){
			$url .= '?' . http_build_query($data);
		}
		else {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);

		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}

	public function getLongToken()
	{
		/*
		 * FB
		 */
//		$shortLivedToken = "EAAaI7w3JtZCQBOzDiZAx7BWVPB3hfuZATeMYDzmXA6tZAgFv13azRVXptNyhDruLh52JjVC8vAyXNEGG2Mk3iUVim4YZBfSI6AtaX3Ecwgz7YNKB1VwYVVGHhGB56I7yuLisMQjaADSZBRmZCO2AXMWeVJPuLmEBGDTu0FxZCRD1EaxOzPFpsj01wwzvTJrVc6rTI0ZAeURSYbktLRFi69WoCo7JgnJGU"; // Replace with your short-lived token

		$varToken = ($this->mode == 'ig')? 'access_token' : ($this->mode . '_exchange_token');

		$dataR = [
				'grant_type'                      => ($this->mode . '_exchange_token'),
				'client_id'                       => $this->appId,
				'client_secret'                   => $this->appSecret,
				$varToken						  => $this->token,
			];
		if($this->mode == 'fb')
			$url = $this->url . "/oauth/access_token";
		else
			$url = $this->url . "/access_token";
		$response = $this->sendCurl($url, $dataR);

		if ($response === false) {
			echo "cURL Error: " . curl_error($ch);
		} else {
			$data = json_decode($response, true);

			if (isset($data['access_token'])) {
				$longLivedToken = $data['access_token'];
//				echo "Long-lived access token: " . $longLivedToken;
			} else {
				echo "Unable to refresh token. Response: " . print_r($data, true). print_r($dataR, true);
			}
		}
		return $longLivedToken;
	}

	public function getFollowers($pageId)
	{
		if($pageId == '') {
			if($this->mode == 'fb')
				return 'N/C';
			else
				$pageId = 'me';
		}
		$url = $this->url . "/{$pageId}?fields=followers_count&access_token={$this->token}";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);

		$followers = 0;
		if ($response === false) {
			echo "cURL Error: " . curl_error($ch);
		} else {
			$data = json_decode($response, true);

			if (isset($data['followers_count'])) {
				$followers = $data['followers_count'];
				echo "Number of followers: " . $followers;
			} else {
				echo "Unable to retrieve the number of followers ".$this->mode." ".$this->url.". Response: " . print_r($data, true);
			}
		}

		curl_close($ch);
		return $followers;
	}
}
