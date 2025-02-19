<?php

	if (isset($_POST['code'], $_POST['user'], $_POST['value']))	{
		require ('../../main.inc.php');
		require_once ('../classes/BimpCache.php');
		$user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $_POST['user']);
		$errors = $user->saveUserParam(
			$_POST['code'],
			($_POST['value'] ? 'yes' : 'no')
		);

		if (!count($errors))	{
			http_response_code(200);
		}

	}
	else	{
		echo '<h3>Erreur !</h3>';
	}
