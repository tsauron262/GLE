<?php

if (!defined('BWS_LIB_INIT')) {
	define('BWS_LIB_INIT', 1);

	$dir = __DIR__ . '/classes/';
	include_once $dir . 'BWSApi.php';

	$ext_version = BimpCore::getExtendsVersion();
	$ext_entity = BimpCore::getExtendsEntity();
	if ($ext_version) {
		if (file_exists(DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/version/' . $ext_version . '/classes/BWSApi.php')) {
			require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/version/' . $ext_version . '/classes/BWSApi.php';
		}
	}

	if ($ext_entity) {
		if (file_exists(DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/entities/' . $ext_entity . '/classes/BWSApi.php')) {
			require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/extends/entities/' . $ext_entity . '/classes/BWSApi.php';
		}
	}
}
