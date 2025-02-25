<?php

namespace PHPSTORM_META {

//	$instances = [
//		'Bimp_User' => \Bimp_User::class,
//		'BIMP_Task' => \BIMP_Task::class
//	];
//
//	override(\BimpCache::getBimpObjectInstance(1, 0, 2), map($instances));

	override(\BimpCache::getBimpObjectInstance(1, 0, 2), map([
		"" => "$1"
	]));

	override(\BimpObject::getInstance(1, 0, 2), map([
		"" => "$1"
	]));
}
