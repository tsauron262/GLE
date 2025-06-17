<?php

if (isset($_REQUEST['nolog']) && $_REQUEST['nolog'] == 'ujgjhkhkfghgkvgkfdkshfiohf5453FF454FFDzelef') {
	define("NOLOGIN", 1);
	header('x-frame-options: ALLOWALL', true);
}

$lock_msg = '';
if (!isset($_REQUEST['no_menu'])) {
	require_once('../main.inc.php');
	llxHeader();

	require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpDb.php';
	global $db;
	$bdb = new BimpDb($db);
	$lock_msg = (string) $bdb->getValue('bimpcore_conf', 'value', 'name = \'git_pull_lock_msg\' AND module = \'bimpcore\'');
} else {
	require_once('../conf/conf.php');
}


error_reporting(E_ALL);
ini_set("display_errors", 1);

if ($lock_msg) {
	echo 'PULL vérouillés : ' . $lock_msg;
} else {
	if (defined('ID_ERP')) {
		echo '<h1>Serveur : ' . ID_ERP . '</h1>';
	}

	curl_init();

	$ok = (isset($_REQUEST['go']) && $_REQUEST['go']);
	$branche = (isset($_REQUEST['branche']) ? $_REQUEST['branche'] : 'master');

	echo '<form><input type="hidden" name="go" value="1"/><input type="text" name="branche" value="' . $branche . '"/><br/><input type="submit" value="Go"/></form>';

	if ($ok && $branche != '') {
		$ressources_dir = __DIR__ . '/../bimpressources/';
		$pull_infos_file = $ressources_dir . 'pull_infos.json';

		$pull_idx = 1;
		if (is_dir($ressources_dir)) {
			if (file_exists($pull_infos_file)) {
				$prev_pull_infos = json_decode(file_get_contents($pull_infos_file), 1);
				if (isset($prev_pull_infos['idx'])) {
					$pull_idx = (int) $prev_pull_infos['idx'] + 1;
					echo '<br/>Pull idx : ' . $pull_idx . '<br/>';
				}
			}
		} else {
			echo '<br/>**********<br/>/!\ Dossier bimpressources absent <br/>**********<br/><br/>';
		}

		$pull_infos = array(
			'idx'   => $pull_idx,
			'start' => time(),
			'end'   => ''
		);

		if (is_dir($ressources_dir)) {
			if (!file_put_contents($pull_infos_file, json_encode($pull_infos))) {
				echo 'ECHEC ENREGISTREMENT INFOS PULL.<br/>';
			}
		}

		$tabHook = array();

		if (!defined('MOD_DEV') || !MOD_DEV) {
			$tabHook[] = array(
				'url'  => WEBHOOK_SERVER . WEBHOOK_PATH_GIT_PULL,
				'data' => array(
					'secret' => WEBHOOK_SECRET_GIT_PULL,
					'branch' => $branche
				)
			);
		}
		//$tabHook[] = array(
		//    'url' => WEBHOOK_SERVER.WEBHOOK_PATH_REDIS_RESTART,//"http://10.192.20.5:9000/hooks/bimp8";
		//    'secret' => WEBHOOK_SECRET_REDIS_RESTART
		//);

		foreach ($tabHook as $hook) {
			$dir = DOL_DOCUMENT_ROOT . '/synopsistools/git_hook/' . $hook['url'];
			echo '<textarea style="width: 780px; height: 380px">';
			$ch = curl_init($hook['url']);
			$file_name = PATH_TMP . '/secret.json';
			$secretJson = json_encode($hook['data']);

			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $secretJson);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$result = curl_exec($ch);

			echo $result;

			if (curl_error($ch)) {
				print_r(curl_error($ch));
			}
			curl_close($ch);

			echo '</textarea>';
			echo '<br/><br/>';
			echo 'Hook : ' . $hook['url'] . ' OK';
			echo '<br/><br/>';

			$dirLogs = PATH_TMP . '/git_logs/';
			if (!is_dir($dirLogs)) {
				mkdir($dirLogs);
			}
			file_put_contents($dirLogs . time() . '.logs', $result);
		}

		if (file_exists($pull_infos_file)) {
			$pull_infos['end'] = time();
			$cur_pull_infos = json_decode(file_get_contents($pull_infos_file), 1);
			if (isset($cur_pull_infos['idx']) && $cur_pull_infos['idx'] <= $pull_idx) {
				if (!file_put_contents($pull_infos_file, json_encode($pull_infos))) {
					echo 'ECHEC ENREGISTREMENT FIN DU PULL.<br/>';
				} else {
					echo 'FIN DU PULL OK.<br/>';
				}
			}
		}

//        if (!isset($_REQUEST['no_after']) || !(int) $_REQUEST['no_after']) {
//            if (isset($_REQUEST['no_menu']) && (int) $_REQUEST['no_menu']) {
//                require_once('../main.inc.php');
//            }
//
//            if (!defined('BIMP_LIB')) {
//                require_once '../bimpcore/Bimp_Lib.php';
//            }
//
//            BimpCore::afterGitPullProcess();
//        }
	}
}

if (!isset($_REQUEST['no_menu'])) {
	llxFooter();
}
