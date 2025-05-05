<?php
define("NOLOGIN", 1);
define('NOSCANPOSTFORINJECTION', 1);
define('NOSCANPHPSELFFORINJECTION', 1);
define('NOSCANGETFORINJECTION', 1);
require_once("../../main.inc.php");


if($_GET['hub_verify_token'] == 'kjsklfbfjfhzemhmhvckleoiho'){
	ob_clean();
	header('Content-Type: application/json');
	file_put_contents(DOL_DATA_ROOT.'/webhook.json', json_encode($_REQUEST));
	die($_GET['hub_challenge']);
}
if(isset($_GET['webhook'])){
	ob_clean();
	header('Content-Type: application/json');
	echo file_get_contents(DOL_DATA_ROOT.'/webhook.json');
	die('fin');
}



error_reporting(E_ALL);
ini_set('display_errors', 1);


require_once('../class/en_social2.class.php');
require_once('../class/en_social.class.php');
$object = new en_social2();


if(isset($_GET['code'])) {
	$token = $object->getCodeToToken($_GET['code']);
//	echo 'token : '.$token;
	$object->saveToken('ig', $token);
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'log') {
	$data = json_decode($_REQUEST['log'],1);

	/*
	 * trucage
	 */
	$data['authResponse']['accessToken'] = 'EAAaI7w3JtZCQBO163vqeNILlUmZCur2uOvMUxaHNgvEtVuVH8SLsfmoQHHQxXzdXGhPjr4WhZAgCuQVYc8emwS8rpo27ClZB6c7unNXLrE0lZCdedkPJfigMoTw46r4hEqIKhbsObvtlytnZCEV6RuwVcZBzSU9AYXdJJa3GGdXgFNUcu21VGFi7NhdpZCN80S2Sd5W7l8ClLYBRa4yxYGoZD';
	if(isset($data['authResponse']['accessToken']) && $data['status'] == 'connected') {
		if(isset($data['authResponse']['graphDomain']) == 'facebook') {
			$object->saveToken('fb', $data['authResponse']['accessToken'], $data['authResponse']['data_access_expiration_time']);
		} else {
			$expires = 0;
		}
		file_put_contents(DOL_DATA_ROOT.'/info2.json', $_REQUEST['log']);
	} else {
		echo 'Pas de token';
	}
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'longToken') {
	$object2 = new en_social($_REQUEST['type'], $_REQUEST['token']);
	$object2->getLongToken();
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'codeToToken') {
	$object2 = new en_social('igc', $_REQUEST['code']);
	$object2->getCodeToToken();
}




if(isset($_POST['action']) && $_POST['action'] == 'SendTest') {
	$message = $_POST['message'];
	$object->createMessage($message);

//	$message = array(
//		array("c"=>"00FF00", "t" => "104"),
//		array("c"=>"FF0000", "t" => "5"),
//	);
//
//	$this->createMessage($message, '', 'fb', 'adams', 'Matrix');
}

if(isset($_POST['action']) && $_POST['action'] == 'sendFollow') {
	$object->checkSocial();
}





?>
<html xmlns="http://www.w3.org/1999/html">
<head>

<script>
	window.fbAsyncInit = function() {
		FB.init({
			appId      : '1685319125525164',
			// appId      : '1839410170148852',
			cookie     : true,
			xfbml      : true,
			version    : 'v22.0'
		});

		FB.AppEvents.logPageView();

	};

	(function(d, s, id){
		var js, fjs = d.getElementsByTagName(s)[0];
		if (d.getElementById(id)) {return;}
		js = d.createElement(s); js.id = id;
		js.src = "https://connect.facebook.net/en_US/sdk.js";
		fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));

	function checkLoginState() {
		FB.getLoginStatus(function(response) {
			window.location.href = '?action=log&log='+JSON.stringify(response);
				statusChangeCallback(response);
		});
	}

	function statusChangeCallback(response){
		console.log(response);
	}
</script>

	<style>
	.centerGrand {
		text-align: center;
		margin: 0 auto;
	}
	.centerGrand table {
		width: 70%;
		margin-left: 15%;
	}
	.centerGrand td {
		text-align: center;
		width: 300px;
		height: 300px;
		background-size: cover;
	}
	.centerGrand img {
		text-align: center;
		width: 100%;
		background-size: cover;
	}
	</style>

</head>
<body>
<div class="centerGrand">
	<h1>Connectez vos réseaux sociaux pour les lier à votre écran</h1>
	<table><tr><td style="background-color:#3b5998">
				<?php
				if((isset($_REQUEST['action']) && $_REQUEST['action'] == 'log') || (isset($_REQUEST['action']) && $_REQUEST['action'] == 'choicePage')) {
						if(isset($_REQUEST['pageId'])) {
							$object->savePageId($_REQUEST['pageId']);
							echo 'Page sauvegardée';
						} else {
							echo '
<h1>Choisir page</h1>
<form method="post">
	<input type="hidden" name="action" value="choicePage"/>';
							$result = $object->getPages();
							foreach ($result as $key => $value) {
								echo '<input type="radio" name="pageId" value="'.$key.'"/>'.$value.'<br/>';
							}
							echo '	<input type="submit" value="Envoyer"/>
</form>';
						}
				}
				else{
					echo '<fb:login-button
	scope="public_profile"
	onlogin="checkLoginState();">
</fb:login-button>';
				}

echo'
</td><td>
<a href="https://www.instagram.com/oauth/authorize?enable_fb_login=0&force_authentication=1&client_id=4058718447734278&redirect_uri=https://erp.loucreezart.fr/lou1/en/public/social.php&response_type=code&scope=instagram_business_basic%2Cinstagram_business_manage_messages%2Cinstagram_business_manage_comments%2Cinstagram_business_content_publish%2Cinstagram_business_manage_insights">
	<img src="./insta.jpg"/>
</a>
			</td>
		</tr>
	</table>
</div>';

if(isset($_REQUEST['debug'])) {
	echo '
<h1>Connect Instagram</h1>
<form method="post" action="">
	<input type="submit" value="Envoyer"/>
</form>

<h1>Test publi</h1>
<form method="post">
	<input type="hidden" name="action" value="SendTest"/>
	<input type="text" name="message" placeholder="Message"/>
	<input type="submit" value="Envoyer"/>
</form>


<h1>Test api</h1>
<form method="post">
	<input type="hidden" name="action" value="sendFollow"/>
	<input type="submit" value="Envoyer"/>
</form>


<h1>Test long token</h1>
<form method="post">
	<input type="hidden" name="action" value="longToken"/>
	<select name="type">
		<option value="fb">Facebook</option>
		<option value="ig">Instagram</option>
		<option value="igc">Instagram2</option>
	</select>
	<input type="text" name="token" placeholder="Token"/>
	<input type="submit" value="Envoyer"/>
</form>
</body>
</html>

<h1>Code => token IG</h1>
<form method="post">
	<input type="hidden" name="action" value="codeToToken"/>
	<input type="text" name="code" placeholder="Token"/>
	<input type="submit" value="Envoyer"/>
</form>
</body>
</html>



</body>
</html>';
}
