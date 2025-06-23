<?php

class BimpCore
{

	public static $is_init = false;
	public static $conf_cache = null;
	public static $conf_cache_def_values = array();
	private static $context = '';
	private static $max_execution_time = 0;
	private static $memory_limit = 0;
	private static $logs_extra_data = array();
	public static $files = array(
		'js'  => array(
			'jpicker'           => '/includes/jquery/plugins/jpicker/jpicker-1.1.6.js',
			'SignaturePad'      => '/bimpcore/views/js/SignaturePad.object.js',
			'moment'            => '/bimpcore/views/js/moment.min.js',
//            'bootstrap'         => '/bimpcore/views/js/bootstrap.min.js',
			'bootstrap'         => '/bimpcore/views/js/bootstrap.js',
			'datetimepicker'    => '/bimpcore/views/js/bootstrap-datetimepicker.js',
			'functions'         => '/bimpcore/views/js/functions.js',
			'scroller'          => '/bimpcore/views/js/scroller.js',
			'ajax'              => '/bimpcore/views/js/ajax.js',
//            '/bimpcore/views/js/component.js',
			'modal'             => '/bimpcore/views/js/modal.js',
			'object'            => '/bimpcore/views/js/object.js',
			'filters'           => '/bimpcore/views/js/filters.js',
			'form'              => '/bimpcore/views/js/form.js',
			'list'              => '/bimpcore/views/js/list.js',
			'graph'             => '/bimpcore/views/js/graph.js',
			'view'              => '/bimpcore/views/js/view.js',
			'viewsList'         => '/bimpcore/views/js/viewsList.js',
			'listCustom'        => '/bimpcore/views/js/listCustom.js',
			'statsList'         => '/bimpcore/views/js/statsList.js',
			'page'              => '/bimpcore/views/js/page.js',
			'table2csv'         => '/bimpcore/views/js/table2csv.js',
			'buc'               => '/bimpuserconfig/views/js/buc.js',
			'bimpcore'          => '/bimpcore/views/js/bimpcore.js',
			'bimp_api'          => '/bimpapi/views/js/bimp_api.js',
			'bimpDocumentation' => '/bimpcore/views/js/BimpDocumentation.js',
			'bds_operations'    => '/bimpdatasync/views/js/operations.js',
			'touch_punch'       => '/bimpcore/views/js/jquery.ui.touch-punch.min.js',
			'hashurl'           => '/bimpcore/views/js/hashurl.js',
		),
		'css' => array(
			'fonts'          => '/bimpcore/views/css/fonts.css',
			'jPicker'        => '/includes/jquery/plugins/jpicker/css/jPicker-1.1.6.css',
			'bimpcore'       => '/bimpcore/views/css/bimpcore.css',
			'userConfig'     => '/bimpuserconfig/views/css/userConfig.css',
			'bds_operations' => '/bimpdatasync/views/css/operations.css'
		)
	);
	public static $js_vars = array();
	public static $filesInit = false;
	public static $layoutInit = false;
	public static $config = null;
	public static $dev_mails = array(
		'tommy'   => 't.sauron@bimp.fr',
		'florian' => 'f.martinez@bimp.fr',
		'alexis'  => 'al.bernard@bimp.fr',
		'romain'  => 'r.PELEGRIN@bimp.fr',
		'peter'   => 'p.tkatchenko@bimp.fr',
		'franck'  => 'f.lauby@ldlc.com'
	);
	public static $html_purifier = null;

	// Initialisation:

	public static function init()
	{
		if (!self::$is_init) {

			$_SESSION['dol_tz_string'] = BimpCore::getConf('main_timezone');

			BimpDebug::addDebugTime('Début affichage page');

			self::$is_init = true;

			global $noBootstrap;

			$extends_entity = BimpCore::getExtendsEntity();

			global $user;
			$use_css_v2 = ((int) self::getConf('use_css_v2'));

			if (!self::isModeDev()) {
				if ((int) self::getConf('use_public_files_external_dir') || (int) self::getConf('use_erp_updates_v2')) {
					if (defined('DOL_DOCUMENT_ROOT') && !file_exists(DOL_DOCUMENT_ROOT . '/bimpressources')) {
						BimpCore::addlog('Dossier "bimpressources" absent', 4, 'bimpcore');
						self::setConf('use_public_files_external_dir', '0', 'bimpcore', -1, true);
						self::setConf('use_erp_updates_v2', '0', 'bimpcore', -1, true);
					}
				}
			}

			$is_context_private = self::isContextPrivate();

			if ($noBootstrap) {
				self::$files['js'] = BimpTools::unsetArrayValue(self::$files['js'], '/bimpcore/views/js/bootstrap.min.js');
			}

			// Traitements CSS :
			$css_dir = 'css' . ($use_css_v2 ? '_v2' : '');

			if ($use_css_v2) {
				self::$files['css']['fonts'] = '/bimpcore/views/css_v2/fonts.css';

				if ($is_context_private) {
					if ($extends_entity && file_exists(DOL_DOCUMENT_ROOT . '/bimpcore/views/' . $css_dir . '/entities/' . $extends_entity . '/bimpcore.css')) {
						self::$files['css']['bimpcore'] = '/bimpcore/views/' . $css_dir . '/entities/' . $extends_entity . '/bimpcore.css';
					} else {
						self::$files['css']['bimpcore'] = '/bimpcore/views/' . $css_dir . '/bimpcore.css';
					}
				} else {
					if ($extends_entity && file_exists(DOL_DOCUMENT_ROOT . '/bimpcore/views/' . $css_dir . '/entities/' . $extends_entity . '/bimpcore_public.css')) {
						self::$files['css']['bimpcore'] = '/bimpcore/views/' . $css_dir . '/entities/' . $extends_entity . '/bimpcore_public.css';
					} else {
						self::$files['css']['bimpcore'] = '/bimpcore/views/' . $css_dir . '/bimpcore_public.css';
					}
				}
			} else {
				if ($is_context_private) {
					if ($extends_entity && file_exists(DOL_DOCUMENT_ROOT . '/bimpcore/views/' . $css_dir . '/bimpcore_' . $extends_entity . '.css')) {
						self::$files['css']['bimpcore'] = '/bimpcore/views/' . $css_dir . '/bimpcore_' . $extends_entity . '.css';
					}
				} else {
					if ($extends_entity && file_exists(DOL_DOCUMENT_ROOT . '/bimpcore/views/' . $css_dir . '/bimpcore_public_' . $extends_entity . '.css')) {
						self::$files['css']['bimpcore'] = '/bimpcore/views/' . $css_dir . '/bimpcore_public_' . $extends_entity . '.css';
					} else {
						self::$files['css']['bimpcore'] = '/bimpcore/views/' . $css_dir . '/bimpcore_public.css';
					}
				}
			}

			// Traitements JS :
			if ($is_context_private) {
				self::$files['js']['notification'] = '/bimpcore/views/js/notification.js';
			}
			if (!self::isModuleActive('bimpdatasync')) {
				unset(self::$files['css']['bds_operations']);
				unset(self::$files['js']['bds_operations']);
			}

			BimpConfig::initCacheServeur();
			self::checkSqlUpdates();
		}
	}

	// Gestion Layout / js / css:

	public static function initLayout()
	{
		if (!self::$layoutInit) {
			self::$layoutInit = true;
		}

		$layout = BimpLayout::getInstance();

		// Ajout fichiers CSS BimpCore:
		foreach (self::$files['css'] as $css_file) {
			$layout->addCssFile(self::getFileUrl($css_file, true, false));
		}

		// Ajout fichiers JS BimpCore:
		foreach (self::$files['js'] as $js_file) {
			$layout->addJsFile(self::getFileUrl($js_file, true, false));
		}

		// Ajouts variables JS:
		$layout->addJsVars(self::getJsVars());

		// Ajouts variables local:
		if (class_exists('Session')) {
			$layout->addJsLocalVars(array(
				'bimp_hash' => Session::getHash()
			));
		}

		self::$filesInit = true;
	}

	public static function getJsVars()
	{
		global $user, $conf, $dolibarr_main_url_root;
		$vars = array(
			'dol_url_root'                     => (DOL_URL_ROOT != '') ? '\'' . DOL_URL_ROOT . '\'' : '\'' . $dolibarr_main_url_root . '\'',
			'entity'                           => $conf->entity,
			'id_user'                          => (BimpObject::objectLoaded($user) ? $user->id : 0),
			'bimp_context'                     => '\'' . self::getContext() . '\'',
			'theme'                            => '\'' . (isset($user->conf->MAIN_THEME) ? $user->conf->MAIN_THEME : $conf->global->MAIN_THEME) . '\'',
			'sessionHideMenu'                  => (BimpController::getSessionConf('hideMenu') == "true" ? 1 : 0),
			'bimp_use_local_storage'           => (int) BimpCore::getConf('use_browser_local_storage'),
			'bimp_local_storage_prefixe'       => '\'' . BimpCore::getConf('bimp_local_storage_prefixe') . '\'',
			'bimp_debug_local_storage'         => (int) BimpCore::getConf('js_debug_local_storage'),
			'bimp_debug_notifs'                => (int) BimpCore::getConf('js_debug_notifs'),
			'bimp_notifications_refresh_delay' => (int) BimpCore::getConf('user_notifications_refresh_delay'),
			'dol_token'                        => '\'' . newToken() . '\''
		);

		$notifs = '{';
		if (self::isContextPrivate() && BimpObject::objectLoaded($user)) {
			$notification = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNotification');

//			global $user;
//			if ($user->login == 'f.martinez') {
//				$config_notification = $notification->getList(array());
//			} else {
			$config_notification = $notification->getList(array('active' => 1));
//			}

			foreach ($config_notification as $cn) {
				if (BimpCore::isModuleActive($cn['module'])) {
					$notifs .= $cn['id'] . ": {";
					$notifs .= "nom: '" . $cn['nom'] . "', ";
					$notifs .= "id_notification: '" . $cn['id'] . "', ";
					$notifs .= "module: '" . $cn['module'] . "', ";
					$notifs .= "storage_key: '" . 'user_' . $user->id . '_' . $cn['nom'] . '\',';
					$notifs .= "obj: null},";
				}
			}
		}
		$notifs .= '}';

		$vars['bimp_notifications_actives'] = $notifs;

		return $vars;
	}

	public static function displayHeaderFiles($echo = true)
	{
		$html = '';
		if (!self::$filesInit) {
			foreach (self::$files['css'] as $css_file) {
				$url = self::getFileUrl($css_file);
				if ($url) {
					$html .= '<link type="text/css" rel="stylesheet" href="' . $url . '"/>' . "\n";
				}
			}

			$js_vars = self::getJsVars();

			if (!empty($js_vars)) {
				$html .= "\n" . '<script ' . BimpTools::getScriptAttribut() . '>' . "\n";
				foreach ($js_vars as $var_name => $var_value) {
					$html .= "\t" . 'var ' . $var_name . ' = ';
					if (BimpTools::isNumericType($var_value)) {
						$html .= $var_value;
					} else {
						$html .= '\'' . $var_value . '\'';
					}
					$html .= ';' . "\n";
				}
				$html .= '</script>' . "\n\n";
			}

			foreach (self::$files['js'] as $js_file) {
				$url = self::getFileUrl($js_file);
				if ($url) {
					$html .= '<script ' . BimpTools::getScriptAttribut() . ' src="' . $url . '"></script>' . "\n";
				}
			}

			self::$filesInit = true;
		}

		if ($echo) {
			echo $html;
		}

		return $html;
	}

	public static function getFileUrl($file_path, $use_tms = true, $include_root = true)
	{
		$url = '';

		if (preg_match('/^\/+(.+)$/', $file_path, $matches)) {
			$file_path = $matches[1];
		}

		$debug = false;

		if ($debug) {
			echo '<div style="margin-top: 100px; margin-left: 300px">';
			echo '<br/>getUrl : ' . $file_path;
		}


		if (file_exists(DOL_DOCUMENT_ROOT . '/' . $file_path)) {
			if ($use_tms && (int) BimpCore::getConf('use_files_tms')) {
				$external_dir = '';
				if ((int) BimpCore::getConf('use_public_files_external_dir') && (!defined('NO_PUBLIC_FILES_EXTERNAL_DIR') || !NO_PUBLIC_FILES_EXTERNAL_DIR)) {
					$external_dir = '/bimpressources';
				}
				$pathinfo = pathinfo($file_path);

				if (strpos($pathinfo['dirname'], '/views/') !== false) {
					$tms = filemtime(DOL_DOCUMENT_ROOT . '/' . $file_path);

					if ($tms !== false) {
						$out_dir = DOL_DOCUMENT_ROOT . $external_dir . '/' . $pathinfo['dirname'];
						$out_file = $pathinfo['filename'] . '_tms_' . $tms . '.' . $pathinfo['extension'];

						if (preg_match('/^\/+(.+)$/', $out_file, $matches)) {
							$out_file = $matches[1];
						}
						if ($debug) {
							echo '<br/>TEST : ' . $out_dir . '/' . $out_file;
						}

						$err = '';
						if ($external_dir && !file_exists($out_dir)) {
							$err = BimpTools::makeDirectories($out_dir, DOL_DOCUMENT_ROOT);

							if ($err) {
								BimpCore::addlog('Echec création du dossier "' . $out_dir . '"', 3, 'bimpcore', null, array(
									'Erreur'  => $err,
									'Fichier' => $out_file
								));
							}

							if ($debug) {
								echo '<br/>Err : ' . $err;
							}
						}

						if (!$err && !file_exists($out_dir . '/' . $out_file)) {
							// Suppr du fichier existant:
							foreach (scandir($out_dir) as $f) {
								if (in_array($f, array('.', '..'))) {
									continue;
								}

								if (preg_match('/^' . preg_quote($pathinfo['filename']) . '_tms_\d+\.' . preg_quote($pathinfo['extension']) . '$/', $f)) {
									if ($debug) {
										echo '<br/>DEL' . $out_dir . '/' . $f;
									}
									unlink($out_dir . '/' . $f);
								}
							}

							if ($debug) {
								echo '<br/>COPY - ';
							}

							if (!copy(DOL_DOCUMENT_ROOT . '/' . $file_path, $out_dir . '/' . $out_file)) {
								BimpCore::addlog('Echec création du fichier "' . $out_dir . '/' . $out_file . '" - Vérifier les droits', Bimp_Log::BIMP_LOG_ALERTE);
								if ($debug) {
									echo 'FAIL';
								}
							} else {
								if ($debug) {
									echo 'OK';
								}
							}
						}

						if (file_exists($out_dir . '/' . $out_file)) {
							$url = $external_dir . '/' . $pathinfo['dirname'] . '/' . $out_file;
						}
					}
				}
			}

			if (!$url) {
				$url = $file_path;
			}
		}

		if ($url) {
			if ($include_root) {
				$prefixe = DOL_URL_ROOT;
				if ($prefixe == "/") {
					$prefixe = "";
				} elseif ($prefixe != "") {
					$prefixe .= "/";
				} elseif ($prefixe == "") {
					$prefixe = "/";
				}

				return $prefixe . $url;
			}
			if ($debug) {
				echo '<br/>URL : ' . $url;
				echo '</div>';
			}

			return $url;
		}

		if ($debug) {
			echo '</div>';
		}

		BimpCore::addlog('FICHIER ABSENT: "' . DOL_DOCUMENT_ROOT . '/' . $file_path . '"', Bimp_Log::BIMP_LOG_ERREUR);
		return '';
	}

	public static function checkRessourcesDir($dir = '', &$errors = array(), &$success = '')
	{
		if (is_dir(DOL_DOCUMENT_ROOT . '/' . $dir)) {
			foreach (scandir(DOL_DOCUMENT_ROOT . '/' . $dir) as $f) {
				if (in_array($f, array('.', '..'))) {
					continue;
				}

				if (is_dir(DOL_DOCUMENT_ROOT . '/' . $dir . '/' . $f)) {
					self::checkRessourcesDir($dir . '/' . $f, $errors, $success);
				} else {
					$success .= ($success ? '<br/>' : '') . 'TEST ' . $dir . '/' . $f;
					if (!file_exists(DOL_DOCUMENT_ROOT . '/bimpressources/' . $dir . '/' . $f)) {
						$err = '';
						if (!is_dir(DOL_DOCUMENT_ROOT . '/bimpressources/' . $dir)) {
							$err = BimpTools::makeDirectories(DOL_DOCUMENT_ROOT . '/bimpressources/' . $dir, DOL_DOCUMENT_ROOT);

							if ($err) {
								$errors[] = 'Echec création du dossier "' . DOL_DOCUMENT_ROOT . '/bimpressources/' . $dir . '"';
							}
						}

						if (!$err) {
							if (!copy(DOL_DOCUMENT_ROOT . '/' . $dir . '/' . $f, DOL_DOCUMENT_ROOT . '/bimpressources/' . $dir . '/' . $f)) {
								$errors[] = 'Echec de la copie du fichier "' . DOL_DOCUMENT_ROOT . '/' . $dir . '/' . $f; // . '" - <pre>' . print_r(error_get_last(), 1) . '</pre>';
							} else {
								$success .= ($success ? '<br/>' : '') . 'COPIE ' . $dir . '/' . $f . ' OK';
							}
						}
					} else {
						$success .= ' - EXISTS';
					}
				}
			}
		} else {
			$errors[] = $dir . ' n\'existe pas';
		}
	}

	// Gestion Versions et mises à jours:

	public static function checkSqlUpdates($execute = false)
	{
		global $no_erp_updates;
		if ($no_erp_updates) {
			return;
		}

		if ((int) self::getConf('use_erp_updates_v2')) {
			self::checkErpUpdates();
			return;
		}

		if (BimpTools::isSubmit('ajax')) {
			return;
		}

		global $user;

		if (!$user->admin) {
			return;
		}

		if ((int) BimpCore::getConf('check_versions_lock', 0)) {
			return;
		}

		$updates = self::getBimpcoreUpdates();
		$modules_updates = BimpCore::getModulesUpdates();
		$modules_extends_updates = BimpCore::getModulesExtendsUpdates();

		$menu_update = 0;
		if (self::isModuleActive('bimptheme')) {
			BimpObject::loadClass('bimptheme', 'Bimp_Menu');
			$menu_update = (int) Bimp_Menu::getFullMenuUpdateVersion();
		}

		if (!empty($updates) || !empty($modules_updates) || !empty($modules_extends_updates) || $menu_update) {
			if (!$execute && !BimpTools::isSubmit('bimpcore_update_confirm')) {
				echo 'MISE A JOUR SQL AUTO <br/><br/>';
				if ($menu_update) {
					echo 'Le liste complète des élements du menu BimpThème doit être mise à jour à la version: ' . $menu_update . '<br/><br/>';
				}
				if (!empty($updates)) {
					echo 'Màj SQL bimpcore: <pre>';
					print_r($updates);
					echo '</pre>';
				}
				if (!empty($modules_updates)) {
					echo 'Màj SQL modules: <pre>';
					print_r($modules_updates);
					echo '</pre>';
				}
				if (!empty($modules_extends_updates)) {
					echo 'Màj SQL extensions modules: <pre>';
					print_r($modules_extends_updates);
					echo '</pre>';
				}

				$url = $_SERVER['REQUEST_URI'];
				if (empty($_SERVER['QUERY_STRING'])) {
					$url .= '?';
				} else {
					$url .= '&';
				}
				$url .= 'bimpcore_update_confirm=1';
				echo '<button type="button" onclick="window.location = \'' . $url . '\'">OK</button>';
				exit;
			} else {
				$bdb = BimpCache::getBdb();

				if (!empty($updates) || !empty($modules_updates) || !empty($modules_extends_updates) || $menu_update) {
					BimpCore::setConf('check_versions_lock', 1);
				}

				if ($menu_update) {
					echo 'Mise à jour du menu BimpThème complet: ';
					$menu_errors = Bimp_Menu::updateFullMenu();
					if (count($menu_errors)) {
						echo '[ECHEC]<pre>';
						print_r($menu_errors);
						echo '</pre>';
					} else {
						echo '[OK]';
					}
					echo '<br/><br/>';
				}

				if (!empty($updates)) {
					foreach ($updates as $dev => $dev_updates) {
						sort($dev_updates);
						$new_version = 0;
						$dev_dir = DOL_DOCUMENT_ROOT . '/bimpcore/updates/' . $dev . '/';
						foreach ($dev_updates as $version) {
							$new_version = $version;
							$version = (string) $version;
							if (!file_exists($dev_dir . $version . '.sql') && preg_match('/^[0-9]+$/', $version)) {
								if (file_exists($dev_dir . $version . '.0.sql')) {
									$version .= '.0';
								}
							}
							if (!file_exists($dev_dir . $version . '.sql')) {
								echo 'FICHIER ABSENT: ' . $dev_dir . $version . '.sql <br/>';
								continue;
							}
							echo 'Mise a jour du module bimpcore a la version: ' . $dev . '/' . $version;
							$file_errors = array();
							if ($bdb->executeFile($dev_dir . $version . '.sql', $file_errors)) {
								echo ' [OK]<br/>';
							} else {
								echo ' [ECHEC]<pre>' . print_r($file_errors, 1) . '</pre><br/>';
							}
						}
						echo '<br/>';

						BimpCore::setVersion($dev, $new_version);
					}
				}

				if (!empty($modules_updates)) {
					foreach ($modules_updates as $module => $module_updates) {
						$dir = DOL_DOCUMENT_ROOT . '/' . $module . '/sql/';

						if (!file_exists($dir) || !is_dir($dir)) {
							echo 'ERREUR. Le dossier de mise à jour du module "' . $module . '" n\'existe pas <br/><br/>';
							continue;
						}

						sort($module_updates);
						$new_version = 0;

						foreach ($module_updates as $version) {
							$new_version = $version;
							$version = (string) $version;

							if (!file_exists($dir . $version . '.sql') && preg_match('/^[0-9]+$/', $version)) {
								if (file_exists($dir . $version . '.0.sql')) {
									$version .= '.0';
								}
							}
							if (!file_exists($dir . $version . '.sql')) {
								echo 'FICHIER ABSENT: ' . $dir . $version . '.sql <br/>';
								continue;
							}
							echo 'Mise a jour du module "' . $module . '" à la version: ' . $version;
							if ($bdb->executeFile($dir . $version . '.sql', $file_errors)) {
								echo ' [OK]<br/>';
							} else {
								echo ' [ECHEC]<pre>' . print_r($file_errors, 1) . '</pre><br/>';
							}
						}
						echo '<br/>';

						if ($new_version) {
							BimpCore::setConf('module_version_' . $module, $new_version, 0);
						}
					}
				}

				if (!empty($modules_extends_updates)) {
					foreach ($modules_extends_updates as $module => $extends_updates) {
						if (BimpCore::getExtendsVersion() && isset($extends_updates['version'])) {
							$dir = DOL_DOCUMENT_ROOT . '/' . $module . '/extends/versions/' . BimpCore::getExtendsVersion() . '/sql/';
							if (!file_exists($dir) || !is_dir($dir)) {
								continue;
							}

							sort($extends_updates['version']);
							$new_version = 0;

							foreach ($extends_updates['version'] as $extend_version) {
								$new_version = $extend_version;
								$extend_version = (string) $extend_version;

								if (!file_exists($dir . $extend_version . '.sql') && preg_match('/^[0-9]+$/', $extend_version)) {
									if (file_exists($dir . $extend_version . '.0.sql')) {
										$extend_version .= '.0';
									}
								}
								if (!file_exists($dir . $extend_version . '.sql')) {
									echo 'FICHIER ABSENT: ' . $dir . $extend_version . '.sql <br/>';
									continue;
								}
								echo 'Mise a jour du module "' . $module . '" à la version: ' . $extend_version . ' (extension de la version "' . BimpCore::getExtendsVersion() . '"';
								if ($bdb->executeFile($dir . $extend_version . '.sql', $file_errors)) {
									echo ' [OK]<br/>';
								} else {
									echo ' [ECHEC]<pre>' . print_r($file_errors, 1) . '</pre><br/>';
								}
							}
							echo '<br/>';

							if ($new_version) {
								BimpCore::setConf('module_sql_version_' . $module . '_version_' . BimpCore::getExtendsVersion(), $new_version);
							}
						}

						if (BimpCore::getExtendsEntity() != '' && isset($extends_updates['entity'])) {
							$dir = DOL_DOCUMENT_ROOT . '/' . $module . '/extends/entities/' . BimpCore::getExtendsEntity() . '/sql/';
							if (!file_exists($dir) || !is_dir($dir)) {
								continue;
							}

							sort($extends_updates['entity']);
							$new_version = 0;

							foreach ($extends_updates['entity'] as $extend_version) {
								$new_version = $extend_version;
								$extend_version = (string) $extend_version;

								if (!file_exists($dir . $extend_version . '.sql') && preg_match('/^[0-9]+$/', $extend_version)) {
									if (file_exists($dir . $extend_version . '.0.sql')) {
										$extend_version .= '.0';
									}
								}
								if (!file_exists($dir . $extend_version . '.sql')) {
									echo 'FICHIER ABSENT: ' . $dir . $extend_version . '.sql <br/>';
									continue;
								}
								echo 'Mise a jour du module "' . $module . '" à la version: ' . $extend_version . ' (extension de l\'entité "' . BimpCore::getExtendsEntity() . '"';
								if ($bdb->executeFile($dir . $extend_version . '.sql', $file_errors)) {
									echo ' [OK]<br/>';
								} else {
									echo ' [ECHEC]<pre>' . print_r($file_errors, 1) . '</pre><br/>';
								}
							}
							echo '<br/>';

							if ($new_version) {
								BimpCore::setConf('module_sql_version_' . $module . '_entity_' . BimpCore::getExtendsEntity(), $new_version);
							}
						}
					}
				}

				BimpCore::setConf('check_versions_lock', 0);

				$url = str_replace('bimpcore_update_confirm=1', '', $_SERVER['REQUEST_URI']);
				echo '<br/><button type="button" onclick="window.location = \'' . $url . '\'">OK</button>';
				exit;
			}
		}
	}

	public static function checkErpUpdates($debug_mode = false)
	{
		if ($debug_mode) {
			echo '<br/>----- START CHECK ERP UPDATES -----<br/>';
		}

		global $user, $no_erp_updates;
		if ($no_erp_updates) {
			if ($debug_mode) {
				echo '***** NO ERP UPDATES *****<br/>';
			}
			return;
		}

		function loadUpdatesInfos($bdb, $type = 'global', $name = '')
		{
			$infos = json_decode((string) $bdb->getValue('bimpcore_conf', 'value', 'name = \'erp_' . $type . ($name ? '_' . $name : '') . '_updates_infos\' AND module = \'bimpcore\' AND entity = 0'), 1);
			return (empty($infos) ? array() : $infos);
		}

		function addUpdatesInfos($bdb, &$errors, $type = 'global', $name = '')
		{
			if ($bdb->insert('bimpcore_conf', array(
					'name'   => 'erp_' . $type . ($name ? '_' . $name : '') . '_updates_infos',
					'module' => 'bimpcore',
					'value'  => ''
				)) <= 0) {
				$errors[] = 'Echec insertion des infos de mise à jour Type : ' . $type . ($name ? ' (' . $name . ')' : '') . ' - ' . $bdb->err();
				return false;
			}

			return true;
		}

		function upUpdatesInfos($bdb, &$errors, $infos, $type = 'global', $name = '')
		{
			if ($bdb->update('bimpcore_conf', array(
					'value' => json_encode($infos)
				), 'name = \'erp_' . $type . ($name ? '_' . $name : '') . '_updates_infos\' AND module = \'bimpcore\' AND entity = 0') <= 0) {
				$errors[] = 'Echec enregistrement des infos de mise à jour Type : ' . $type . ($name ? ' (' . $name . ')' : '') . ' - ' . $bdb->err();
				return false;
			}

			return true;
		}

		$erase_cache_server = false;
		$pull_info = array();

		$pull_infos_file = DOL_DOCUMENT_ROOT . '/bimpressources/pull_infos.json';
		if (file_exists($pull_infos_file)) {
			$pull_info = json_decode(file_get_contents($pull_infos_file), 1);
		} elseif ($debug_mode) {
			if (is_dir(DOL_DOCUMENT_ROOT . '/bimpressources')) {
				echo 'FICHIER pull_infos.json absent, création du fichier avec l\'index 1 : ';

				$pull_info = array(
					'idx'   => 1,
					'start' => date('Y-m-d H:i:s'),
					'end'   => date('Y-m-d H:i:s')
				);
				if (file_put_contents($pull_infos_file, json_encode($pull_info))) {
					echo 'Fichier créé avec succès<br/>';
				} else {
					die('ECHEC CREATION DU FICHIER pull_infos.json');
				}
			} else {
				die('PAS DE DOSSIER "bimpressources"');
			}
		}

		if (isset($pull_info['idx'])) {
			if ($debug_mode) {
				echo 'PULL INFOS<pre>' . print_r($pull_info, 1) . '</pre><br/>';
			}

			// on sleep tant qu'il y a un pull en cours non terminé
			$n = 0;
			while (empty($pull_info['end'])) {
				$n++;
				if ($n > 10) {
					if ($debug_mode) {
						die('Pull non terminé');
					}
					die('ERP EN COURS DE MISE A JOUR. MERCI DE PATIENTER QUELQUES INSTANTS AVANT D\'ACTUALISER CETTE PAGE.');
				}

				sleep(3);
				$pull_info = json_decode(file_get_contents($pull_infos_file), 1);
			}

			if ((int) self::getConf('use_public_files_external_dir')) {
				if (isset($pull_info['post_process']) && !(int) $pull_info['post_process']) {
					$pull_info['post_process'] = 1;
					file_put_contents($pull_infos_file, json_encode($pull_info, 1));

					if ($debug_mode) {
						echo '<br/>GIT PULL POST PROCESS : ';
					}

					$post_process_infos = '';
					$post_process_errors = BimpCore::afterGitPullProcess(false, $post_process_infos);
					if (count($post_process_errors)) {
						$errors[] = BimpTools::getMsgFromArray('Erreurs GIT PULL POST PROCESS', $post_process_errors);

						if ($debug_mode) {
							echo 'Erreurs <pre>' . print_r($post_process_errors, 1) . '</pre>';
						}
					} elseif ($debug_mode) {
						echo 'OK <br/>';
						echo $post_process_infos;
						echo '<br/><br/>';
					} else {
						BimpCore::addlog('GIT PULL POST PROCESS OK', 1, 'maj', null, array(
							'Infos' => $post_process_infos
						));;
					}
				}
			}
			$errors = array();
			$bdb = BimpCache::getBdb(true); // sans transactions
			$ext_version = self::getExtendsVersion();
			$ext_entity = self::getExtendsEntity();

			$bimpcore_updates = $modules_updates = array();
			$modules_version_updates = $modules_entity_updates = array();
			$menu_update = 0;
			$global_updates = $version_updates = $entity_updates = false;
			$token = BimpTools::randomPassword(12, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', false);

			// On vérifies que les mises à jour correspondent bien au dernier pull
			$global_infos = loadUpdatesInfos($bdb);
			if ($debug_mode) {
				echo '<br/>INFOS MAJ GLOBALE : <pre>' . print_r($global_infos, 1) . '</pre><br/>';
			}
			if (empty($global_infos)) {
				if ($debug_mode) {
					echo '<br/>INFOS MAJ GLOBALES ABSENTES, ajout en base : ';
				}

				if (!addUpdatesInfos($bdb, $errors)) {
					if ($debug_mode) {
						echo 'ECHEC<br/>';
					}
				} else {
					if ($debug_mode) {
						echo 'OK<br/>';
					}
				}
			}
			if (!isset($global_infos['idx'])) {
				$global_infos['idx'] = 0;
			}
			if ((int) $global_infos['idx'] > (int) $pull_info['idx']) {
				if ($debug_mode) {
					echo 'Index de Màj (' . $global_infos['idx'] . ') supérieur à l\'index du pull (' . $pull_info['idx'] . '), correction de l\'index du fichier : ';
				}
				$pull_info['idx'] = $global_infos['idx'];
				if (file_put_contents($pull_infos_file, json_encode($pull_info))) {
					if ($debug_mode) {
						echo 'OK<br/>';
					}
				} else {
					if ($debug_mode) {
						echo 'ECHEC<br/>';
					}
				}
			}

			if ($debug_mode || (isset($global_infos['idx']) && (int) $global_infos['idx'] < (int) $pull_info['idx'])) {
				$bimpcore_updates = self::getBimpcoreUpdates();
				$modules_updates = BimpCore::getModulesUpdates();
				if (self::isModuleActive('bimptheme')) {
					BimpObject::loadClass('bimptheme', 'Bimp_Menu');
					$menu_update = Bimp_Menu::getFullMenuUpdateVersion();
				}

				if (!empty($bimpcore_updates) || !empty($modules_updates) || $menu_update) {
					$global_updates = true;
				}

				if ($debug_mode) {
					if (!$global_updates) {
						echo '*** AUCUNE MISE A JOUR GLOBALE A EFFECTUER ***<br/><br/>';
					} else {
						if (!empty($bimpcore_updates)) {
							echo 'MAJ Bimpcore<pre>' . print_r($bimpcore_updates, 1) . '</pre>';
						}
						if (!empty($modules_updates)) {
							echo 'MAJ Modules<pre>' . print_r($modules_updates, 1) . '</pre>';
						}
						if ($menu_update) {
							echo 'MAJ Menu : ' . $menu_update . '<br/><br/>';
						}
					}
				}

				upUpdatesInfos($bdb, $errors, array(
					'idx'     => (int) $pull_info['idx'],
					'id_user' => $user->id,
					'start'   => date('Y-m-d H:i:s'),
					'end'     => ($global_updates ? '' : date('Y-m-d H:i:s')),
					'token'   => $token
				));
			}

			$version_infos = array();
			if ($ext_version) {
				$version_infos = loadUpdatesInfos($bdb, 'version', $ext_version);
				if ($debug_mode) {
					echo '<br/>INFOS MAJ VERSION : <pre>' . print_r($version_infos, 1) . '</pre><br/>';
				}
				if (empty($version_infos)) {
					if ($debug_mode) {
						echo '<br/>INFOS MAJ VERSION ABSENTES, ajout en base : ';
					}

					if (!addUpdatesInfos($bdb, $errors, 'version', $ext_version)) {
						if ($debug_mode) {
							echo 'ECHEC<br/>';
						}
					} else {
						if ($debug_mode) {
							echo 'OK<br/>';
						}
					}
				}
				if (!isset($version_infos['idx'])) {
					$version_infos['idx'] = 0;
				}
				if ($debug_mode || ((int) $version_infos['idx'] < (int) $pull_info['idx'])) {
					$modules_version_updates = BimpCore::getModulesExtendsUpdates('version');

					if (!empty($modules_version_updates)) {
						$version_updates = true;
					}

					if ($debug_mode) {
						if (!$version_updates) {
							echo '*** AUCUNE MISE A JOUR DE VERSION A EFFECTUER ***<br/><br/>';
						} else {
							echo 'MAJ MODULES VERSION<pre>' . print_r($modules_version_updates, 1) . '</pre>';
						}
					}

					upUpdatesInfos($bdb, $errors, array(
						'idx'     => (int) $pull_info['idx'],
						'id_user' => $user->id,
						'start'   => date('Y-m-d H:i:s'),
						'end'     => ($version_updates ? '' : date('Y-m-d H:i:s')),
						'token'   => $token
					), 'version', $ext_version);
				}
			}

			$entity_infos = array();
			if ($ext_entity) {
				$entity_infos = loadUpdatesInfos($bdb, 'entity', $ext_entity);
				if ($debug_mode) {
					echo '<br/>INFOS MAJ ENTITÉ : <pre>' . print_r($entity_infos, 1) . '</pre><br/>';
				}
				if (empty($entity_infos)) {
					if ($debug_mode) {
						echo '<br/>INFOS MAJ ENTITÉ ABSENTES, ajout en base : ';
					}

					if (!addUpdatesInfos($bdb, $errors, 'entity', $ext_entity)) {
						if ($debug_mode) {
							echo 'ECHEC<br/>';
						}
					} else {
						if ($debug_mode) {
							echo 'OK<br/>';
						}
					}
				}

				if (!isset($entity_infos['idx'])) {
					$entity_infos['idx'] = 0;
				}

				if ($debug_mode || ((int) $entity_infos['idx'] < (int) $pull_info['idx'])) {
					$modules_entity_updates = BimpCore::getModulesExtendsUpdates('entity');

					if (!empty($modules_entity_updates)) {
						$entity_updates = true;
					}

					if ($debug_mode) {
						if (!$entity_updates) {
							echo '*** AUCUNE MISE A JOUR ENTITÉ A EFFECTUER ***<br/><br/>';
						} else {
							echo 'MAJ MODULES ENTITÉ<pre>' . print_r($modules_entity_updates, 1) . '</pre>';
						}
					}

					upUpdatesInfos($bdb, $errors, array(
						'idx'     => (int) $pull_info['idx'],
						'id_user' => $user->id,
						'start'   => date('Y-m-d H:i:s'),
						'end'     => ($entity_updates ? '' : date('Y-m-d H:i:s')),
						'token'   => $token
					), 'entity', $ext_entity);
				}
			}

			if (!count($errors)) {
				$tbdb = BimpCache::getBdb(); // Avec transactions

				// Pour chaque type de mise à jour, on actualise les infos en base pour vérifier qu'un autre utilisateur n'a pas pris le lead (via token)
				// On éxécute les màj si le token est toujours valide

				if ($global_updates) {
					sleep(1);
					$global_infos = loadUpdatesInfos($bdb);
					if (isset($global_infos['token']) && $global_infos['token'] === $token) {
						if ($debug_mode) {
							echo '<br/>***** EXEC MAJ GLOBALES *****<br/>';
						}

						$global_updates_errors = array();
						$tbdb->db->commitAll();

						// Exécution des màj globales :
						if ($menu_update) {
							$erase_cache_server = true;
							if ($debug_mode) {
								echo 'Maj full menu ' . $menu_update . ' : ';
							}
							$menu_errors = Bimp_Menu::updateFullMenu();
							if (count($menu_errors)) {
								if ($debug_mode) {
									echo 'ECHEC<pre>' . print_r($menu_errors, 1) . '</pre>';
								}
								$global_updates_errors[] = BimpTools::getMsgFromArray($menu_errors, 'Echec de la mise à jour complète du menu');
							} elseif ($debug_mode) {
								echo 'OK<br/>';
							}
						}

						if (!empty($bimpcore_updates)) {
							$erase_cache_server = true;
							foreach ($bimpcore_updates as $dev => $dev_updates) {
								sort($dev_updates);
								$new_version = 0;
								$dev_dir = DOL_DOCUMENT_ROOT . '/bimpcore/updates/' . $dev . '/';
								foreach ($dev_updates as $version) {
									$version = (string) $version;
									if (!file_exists($dev_dir . $version . '.sql') && preg_match('/^[0-9]+$/', $version)) {
										if (file_exists($dev_dir . $version . '.0.sql')) {
											$version .= '.0';
										}
									}
									if (file_exists($dev_dir . $version . '.sql')) {
										if ($debug_mode) {
											echo 'Mise à jour du module bimpcore à la version ' . $dev . '/' . $version . ' : ';
										}
										$tbdb->db->begin();
										$file_errors = array();
										if (!$bdb->executeFile($dev_dir . $version . '.sql', $file_errors)) {
											if ($debug_mode) {
												echo 'ECHEC<pre>' . print_r($file_errors, 1) . '</pre>';
											}
											$global_updates_errors[] = BimpTools::getMsgFromArray($file_errors, 'Echec de l\'exécution du fichier "' . $dev_dir . $version . '.sql' . '"');
											$tbdb->db->rollback();
											break;
										} elseif ($debug_mode) {
											echo 'OK<br/>';
										}

										$new_version = (float) $version;
										$tbdb->db->commitAll();
									}
								}

								if ($new_version) {
									BimpCore::setVersion($dev, $new_version, true);
								}
							}
						}

						if (!empty($modules_updates)) {
							$erase_cache_server = true;
							foreach ($modules_updates as $module => $module_updates) {
								$dir = DOL_DOCUMENT_ROOT . '/' . $module . '/sql/';

								if (!file_exists($dir) || !is_dir($dir)) {
									continue;
								}

								sort($module_updates);
								$new_version = 0;

								foreach ($module_updates as $version) {
									$version = (string) $version;

									if (!file_exists($dir . $version . '.sql') && preg_match('/^[0-9]+$/', $version)) {
										if (file_exists($dir . $version . '.0.sql')) {
											$version .= '.0';
										}
									}
									if (!file_exists($dir . $version . '.sql')) {
										continue;
									}

									if ($debug_mode) {
										echo 'Mise à jour du module ' . $module . ' à la version ' . $version . ' : ';
									}

									$tbdb->db->begin();
									$file_errors = array();
									if (!$bdb->executeFile($dir . $version . '.sql', $file_errors)) {
										if ($debug_mode) {
											echo 'ECHEC<pre>' . print_r($file_errors, 1) . '</pre>';
										}
										$global_updates_errors[] = BimpTools::getMsgFromArray($file_errors, 'Echec de l\'exécution du fichier "' . $dir . $version . '.sql' . '"');
										$tbdb->db->rollback();
										break;
									} elseif ($debug_mode) {
										echo 'OK<br/>';
									}

									$new_version = (float) $version;
									$tbdb->db->commitAll();
								}

								if ($new_version) {
									BimpCore::setConf('module_version_' . $module, $new_version, 'bimpcore', 0, true);
								}
							}
						}

						if (count($global_updates_errors)) {
							$errors[] = BimpTools::getMsgFromArray($global_updates_errors, 'Erreurs lors de la mise à jour globale');
						} else {
							$global_infos['end'] = date('Y-m-d H:i:s');
							upUpdatesInfos($bdb, $errors, $global_infos);

							BimpCore::addLog('Maj globale effectuée avec succès', 1, 'maj', null, array(
								'infos' => $global_infos
							), true);
						}
					} elseif ($debug_mode) {
						echo 'Màj globale : token invalide.<br/>';
					}
				}

				if ($version_updates) {
					sleep(1);
					$version_infos = loadUpdatesInfos($bdb, 'version', $ext_version);
					if (isset($version_infos['token']) && $version_infos['token'] === $token) {
						if ($debug_mode) {
							echo '<br/>***** EXEC MAJ VERSION *****<br/>';
						}
						$version_updates_errors = array();
						$tbdb->db->commitAll();

						// Exécution des màj version :
						if (!empty($modules_version_updates)) {
							$erase_cache_server = true;
							foreach ($modules_version_updates as $module => $extend_updates) {
								$dir = DOL_DOCUMENT_ROOT . '/' . $module . '/extends/versions/' . $ext_version . '/sql/';
								if (!file_exists($dir) || !is_dir($dir)) {
									continue;
								}

								sort($extend_updates);
								$new_version = 0;

								foreach ($extend_updates as $extend_version) {
									$extend_version = (string) $extend_version;

									if (!file_exists($dir . $extend_version . '.sql') && preg_match('/^[0-9]+$/', $extend_version)) {
										if (file_exists($dir . $extend_version . '.0.sql')) {
											$extend_version .= '.0';
										}
									}
									if (file_exists($dir . $extend_version . '.sql')) {
										if ($debug_mode) {
											echo 'Mise à jour du module ' . $module . ' à la version ' . $extend_version . ' (version "' . $ext_version . '") : ';
										}

										$tbdb->db->begin();
										$file_errors = array();
										if (!$bdb->executeFile($dir . $extend_version . '.sql', $file_errors)) {
											if ($debug_mode) {
												echo 'ECHEC<pre>' . print_r($file_errors, 1) . '</pre>';
											}
											$version_updates_errors[] = BimpTools::getMsgFromArray($file_errors, 'Echec de l\'exécution du fichier "' . $dir . $extend_version . '.sql' . '"');
											$tbdb->db->rollback();
											break;
										} elseif ($debug_mode) {
											echo 'OK<br/>';
										}

										$tbdb->db->commitAll();
										$new_version = (float) $extend_version;
									}
								}

								if ($new_version) {
									BimpCore::setConf('module_sql_version_' . $module . '_version_' . $ext_version, $new_version, 'bimpcore', 0, true);
								}
							}
						}

						if (count($version_updates_errors)) {
							$errors[] = BimpTools::getMsgFromArray($version_updates_errors, 'Erreurs lors de la mise à jour de la version "' . $ext_version . '"');
						} else {
							$version_infos['end'] = date('Y-m-d H:i:s');
							upUpdatesInfos($bdb, $errors, $version_infos, 'version', $ext_version);

							BimpCore::addLog('Maj version effectuée avec succès', 1, 'maj', null, array(
								'infos' => $version_infos
							), true);
						}
					} elseif ($debug_mode) {
						echo 'Màj version : token invalide.<br/>';
					}
				}

				if ($entity_updates) {
					sleep(1);
					$entity_infos = loadUpdatesInfos($bdb, 'entity', $ext_entity);
					if (isset($entity_infos['token']) && $entity_infos['token'] === $token) {
						if ($debug_mode) {
							echo '<br/>***** EXEC MAJ ENTITÉ *****<br/>';
						}
						$entity_updates_errors = array();
						$tbdb->db->commitAll();

						// Exécution des màj entité :
						if (!empty($modules_entity_updates)) {
							$erase_cache_server = true;
							foreach ($modules_entity_updates as $module => $extend_updates) {
								$dir = DOL_DOCUMENT_ROOT . '/' . $module . '/extends/entities/' . $ext_entity . '/sql/';
								if (!file_exists($dir) || !is_dir($dir)) {
									continue;
								}

								sort($extend_updates);
								$new_version = 0;

								foreach ($extend_updates as $extend_version) {
									$new_version = $extend_version;
									$extend_version = (string) $extend_version;

									if (!file_exists($dir . $extend_version . '.sql') && preg_match('/^[0-9]+$/', $extend_version)) {
										if (file_exists($dir . $extend_version . '.0.sql')) {
											$extend_version .= '.0';
										}
									}

									if (file_exists($dir . $extend_version . '.sql')) {
										if ($debug_mode) {
											echo 'Mise à jour du module ' . $module . ' à la version ' . $extend_version . ' (entité "' . $ext_entity . '") : ';
										}
										$tbdb->db->begin();
										$file_errors = array();
										if (!$bdb->executeFile($dir . $extend_version . '.sql', $file_errors)) {
											if ($debug_mode) {
												echo 'ECHEC<pre>' . print_r($file_errors, 1) . '</pre>';
											}
											$entity_updates_errors[] = BimpTools::getMsgFromArray($file_errors, 'Echec de l\'exécution du fichier "' . $dir . $extend_version . '.sql' . '"');
											$tbdb->db->rollback();
											break;
										} elseif ($debug_mode) {
											echo 'OK<br/>';
										}

										$tbdb->db->commitAll();
										$new_version = (float) $extend_version;
									}
								}

								if ($new_version) {
									BimpCore::setConf('module_sql_version_' . $module . '_entity_' . BimpCore::getExtendsEntity(), $new_version);
								}
							}
						}

						if (count($entity_updates_errors)) {
							$errors[] = BimpTools::getMsgFromArray($entity_updates_errors, 'Erreurs lors de la mise à jour de l\'entité "' . $ext_entity . '"');
						} else {
							$entity_infos['end'] = date('Y-m-d H:i:s');
							upUpdatesInfos($bdb, $errors, $entity_infos, 'entity', $ext_entity);

							BimpCore::addLog('Maj entité effectuée avec succès', 1, 'maj', null, array(
								'infos' => $entity_infos
							), true);
						}
					}
				}
			}

			if ($erase_cache_server) {
				BimpCache::eraseCacheServer();
			}

			if ($debug_mode) {
				echo '<br/><br/>FIN<br/><br/>';
				if (count($errors)) {
					echo 'ERREURS : <pre>' . print_r($errors, 1) . '</pre>';
				} else {
					echo 'AUCUNE ERREUR<br/>';
				}
			} else {
				if (count($errors)) {
					self::addlog('Erreurs mise à jour ERP', 4, 'maj', null, array(
						'Infos pull' => $pull_info,
						'Erreurs'    => $errors
					), true);
				}
				// on sleep tant qu'il y a des maj sql en cours non terminées
				$n = 0;
				while ((isset($global_infos['end']) && empty($global_infos['end'])) ||
					($ext_version && isset($version_infos['end']) && empty($version_infos['end'])) ||
					($ext_entity && isset($entity_infos['end']) && empty($entity_infos['end']))) {
					$n++;
					sleep(3);
					$updates_infos = json_decode((string) $bdb->getValue('bimpcore_conf', 'value', 'name = \'erp_updates_infos\' AND module = \'bimpcore\' AND entity = 0'), 1);

					if ($n > 20) {
						die('ERP EN COURS DE MISE A JOUR. MERCI DE PATIENTER QUELQUES INSTANTS AVANT D\'ACTUALISER CETTE PAGE.');
					}
				}
			}
		}
	}

	public static function getBimpcoreUpdates()
	{
		$dir = DOL_DOCUMENT_ROOT . '/bimpcore/updates';
		$updates = array();
		foreach (scandir($dir) as $subDir) {
			if (in_array($subDir, array('.', '..'))) {
				continue;
			}

			if (preg_match('/^[a-z]+$/', $subDir) && is_dir($dir . '/' . $subDir)) {
				$current_version = (float) BimpCore::getBimpCoreSqlVersion($subDir);
				foreach (scandir($dir . '/' . $subDir) as $f) {
					if (in_array($f, array('.', '..'))) {
						continue;
					}
					if (preg_match('/^(\d+(\.\d{1})*)\.sql$/', $f, $matches)) {
						if ((float) $matches[1] > (float) $current_version) {
							if (!isset($updates[$subDir])) {
								$updates[$subDir] = array();
							}
							$updates[$subDir][] = (float) $matches[1];
						}
					}
				}
				if (isset($updates[$subDir])) {
					sort($updates[$subDir]);
				}
			}
		}

		return $updates;
	}

	public static function getModulesUpdates()
	{
		$updates = array();

		$cache = self::getConfCache();

		global $user;

		if (isset($cache[0]['bimpcore'])) {
			foreach ($cache[0]['bimpcore'] as $name => $value) {
				if (preg_match('/^module_version_(.+)$/', $name, $matches)) {
					$module = $matches[1];

					$dir = DOL_DOCUMENT_ROOT . '/' . $module . '/sql';
					if (file_exists($dir) && is_dir($dir)) {
						$files = scandir($dir);

						foreach ($files as $f) {
							if (in_array($f, array('.', '..'))) {
								continue;
							}

							if (preg_match('/^(\d+\.\d)\.sql$/', $f, $matches2)) {
								if ((float) $matches2[1] > (float) $value) {
									if (!isset($updates[$module])) {
										$updates[$module] = array();
									}
									$updates[$module][] = (float) $matches2[1];
								}
							}
						}
					}
				}
			}
		}

		return $updates;
	}

	public static function getModulesExtendsUpdates($type_filter = '')
	{
		$version = BimpCore::getExtendsVersion();
		$ext_entity = BimpCore::getExtendsEntity();

		if (!$version && !$ext_entity) {
			return array();
		}

		$updates = array();

		$cache = self::getConfCache();

		if (isset($cache[0]['bimpcore'])) {
			$modules = array('bimpcore');

			foreach ($cache[0]['bimpcore'] as $name => $value) {
				if (preg_match('/^module_version_(.+)$/', $name, $matches)) {
					if (!in_array($matches[1], $modules)) {
						$modules[] = $matches[1];
					}
				}
			}

			foreach ($modules as $module) {
				if ($version && (!$type_filter || $type_filter == 'version')) {
					$dir = DOL_DOCUMENT_ROOT . '/' . $module . '/extends/versions/' . $version . '/sql';
					if (file_exists($dir) && is_dir($dir)) {
						$current_version = (float) BimpCore::getConf('module_sql_version_' . $module . '_version_' . $version, 0);
						$files = scandir($dir);

						foreach ($files as $f) {
							if (in_array($f, array('.', '..'))) {
								continue;
							}

							if (preg_match('/^(\d+\.\d)\.sql$/', $f, $matches2)) {
								if ((float) $matches2[1] > $current_version) {
									if (!isset($updates[$module])) {
										$updates[$module] = array();
									}

									if ($type_filter) {
										$updates[$module][] = (float) $matches2[1];
									} else {
										if (!isset($updates[$module]['version'])) {
											$updates[$module]['version'] = array();
										}

										$updates[$module]['version'][] = (float) $matches2[1];
									}
								}
							}
						}
					}
				}

				if ($ext_entity && (!$type_filter || $type_filter == 'entity')) {
					$dir = DOL_DOCUMENT_ROOT . '/' . $module . '/extends/entities/' . $ext_entity . '/sql';
					if (file_exists($dir) && is_dir($dir)) {

						$current_version = (float) BimpCore::getConf('module_sql_version_' . $module . '_entity_' . $ext_entity, 0);

						$files = scandir($dir);

						foreach ($files as $f) {
							if (in_array($f, array('.', '..'))) {
								continue;
							}

							if (preg_match('/^(\d+\.\d)\.sql$/', $f, $matches2)) {
								if ((float) $matches2[1] > $current_version) {
									if (!isset($updates[$module])) {
										$updates[$module] = array();
									}

									if ($type_filter) {
										$updates[$module][] = (float) $matches2[1];
									} else {
										if (!isset($updates[$module]['entity'])) {
											$updates[$module]['entity'] = array();
										}

										$updates[$module]['entity'][] = (float) $matches2[1];
									}
								}
							}
						}
					}
				}
			}
		}

		return $updates;
	}

	public static function getBimpCoreSqlVersion($dev = '')
	{
		$versions = self::getConf('bimpcore_version');

		$bdb = BimpCache::getBdb(true);
		if (!is_array($versions) || empty($versions)) {
			if ((string) $versions) {
				$versions = json_decode($versions, 1);
			}

			$update = false;

			if (empty($versions)) {
				// On vérifie valeur en base:
				$value = $bdb->getValue('bimpcore_conf', 'value', '`name` = \'bimpcore_version\'');

				if (!(string) $value) {
					$bdb->insert('bimpcore_conf', array(
						'name'  => 'bimpcore_version',
						'value' => json_encode(array(
							'florian' => 0,
							'tommy'   => 0,
							'romain'  => 0,
							'alexis'  => 0
						)),
					));
				} else {
					if (preg_match('/^[0-9]+(\.[0-9])*$/', $value)) {
						// Pour compat:
						$versions = array(
							'florian' => (float) $value
						);
						$update = true;
					} else {
						$versions = json_decode($value, 1);
					}
				}
			}
		}

		if ($dev && !isset($versions[$dev])) {
			$versions[$dev] = 0;
			$update = true;
		}

		if ($update) {
			if (!empty($versions)) { // Pour éviter un écrasement...
				$bdb->update('bimpcore_conf', array(
					'value' => json_encode($versions)
				), '`name` = \'bimpcore_version\' AND `module` = \'bimpcore\'');
				self::$conf_cache[0]['bimpcore']['bimpcore_version'] = json_encode($versions);
			}
		}

		if ($dev) {
			return (float) BimpTools::getArrayValueFromPath($versions, $dev, 0);
		}

		return $versions;
	}

	public static function setVersion($dev, $version, $no_transactions = false)
	{
		$versions = self::getBimpCoreSqlVersion();

		if (!isset($versions[$dev])) {
			$versions[$dev] = 0;
		}

		$versions[$dev] = $version;

		self::setConf('bimpcore_version', $versions, 'bimpcore', 0, $no_transactions);
	}

	public static function afterGitPullProcess($force_process = false, &$success = '')
	{
		$errors = array();

		if ($force_process || ((int) BimpCore::getConf('use_files_tms') && (int) BimpCore::getConf('use_public_files_external_dir'))) {
			if (!file_exists(DOL_DOCUMENT_ROOT . '/bimpressources')) {
				BimpCore::addlog('ATTENTION dossier "bimpressources" absent', Bimp_Log::BIMP_LOG_URGENT);
			} else {
				self::checkRessourcesDir('bimpcore/views/fonts', $errors, $success);
			}
		}

		return $errors;
	}

	// Gestion BimpCore Conf:

	public static function getConfCache()
	{
		if (is_null(self::$conf_cache)) {
			self::$conf_cache = array();

			$rows = BimpCache::getBdb()->getRows('bimpcore_conf');

			if (is_array($rows)) {
				foreach ($rows as $r) {
					$module = $r->module;

					if (!$module) {
						$module = 'bimpcore';
					}

					if (isset($r->entity)) {
						self::$conf_cache[$r->entity][$module][$r->name] = $r->value;
					} else {
						self::$conf_cache[0][$module][$r->name] = $r->value;
					}
				}
			}
		}

		return self::$conf_cache;
	}

	public static function getConf($name, $default = null, $module = 'bimpcore', &$source = '', $entity = null)
	{
		// Si le paramètre n'est pas enregistré en base, on retourne en priorité la valeur par défaut
		// passée en argument de la fonction.
		// si cette argument est null on retourne la valeur par défaut définie dans le YML de la config du module (si elle est existe)
		// on retourne null sinon.
		// Le système de cache permet de vérifier une seule fois la valeur en base et la valeur par défaut du YML.


		if (!$module) {
			$module = 'bimpcore';
		}

		$cache = self::getConfCache();

		if (BimpTools::isModuleDoliActif('MULTICOMPANY')) {
			if (is_null($entity)) {
				$entity = getEntity('bimp_conf', 0);
			}

			if (isset($cache[$entity][$module][$name])) {
				$source = ($entity ? 'entity_' . $entity : 'global');
				return $cache[$entity][$module][$name];
			}
		} elseif (is_null($entity)) {
			$entity = 0;
		}

		if (isset($cache[0][$module][$name])) {
			$source = 'global';
			return $cache[0][$module][$name];
		}

		// Check éventuelle erreur sur le module:
		if (isset($cache[(int) $entity])) {
			foreach ($cache[(int) $entity] as $module_name => $params) {
				if (isset($params[$name])) {
					BimpCore::addlog('BimpCore::getConf() - Erreur module possible', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', null, array(
						'Paramètre'                                  => $name,
						'Entité'                                     => $entity,
						'Module demandé'                             => $module,
						'Module trouvé (tel que enregistré en base)' => $module_name
					), true);
					break;
				}
			}
		}

		$source = 'val_def';

		if (!is_null($default)) {
			return $default;
		}

		if (!isset(self::$conf_cache_def_values[$module][$name])) {
			self::$conf_cache_def_values[$module][$name] = BimpModuleConf::getParamDefaultValue($name, $module, true);
		}

		return self::$conf_cache_def_values[$module][$name];
	}

	public static function setConf($name, $value, $module = 'bimpcore', $entity = -1, $no_transactions = false)
	{
		if (!$module) {
			$module = 'bimpcore';
		}

		$errors = array();

		if ($value == "++") {
			$value = BimpCore::getConf($name, 0, $module) + 1;
		}

		if (BimpTools::isModuleDoliActif('MULTICOMPANY')) {
			if ($entity == -1) {
				$bimp_conf_entity = getEntity('bimp_conf', 0);
				$entity = (isset(self::$conf_cache[$bimp_conf_entity][$module][$name]) ? $bimp_conf_entity : 0);
			}
		} else {
			$entity = 0;
		}

		$current_val = (isset(self::$conf_cache[$entity][$module][$name]) ? self::$conf_cache[$entity][$module][$name] : null);

		$bdb = BimpCache::getBdb($no_transactions);

		if (is_null($current_val)) {
			if ($bdb->insert('bimpcore_conf', array(
					'name'   => $name,
					'value'  => $value,
					'module' => $module,
					'entity' => $entity
				)) <= 0) {
				$errors[] = 'Echec de l\'insertion du paramètre "' . $name . '" (Module ' . $module . ' - ID entité : ' . $entity . ') - ' . $bdb->err();
			}
		} else {
			if ($bdb->update('bimpcore_conf', array(
					'value' => $value
				), '`name` = \'' . $name . '\' AND `module` = \'' . $module . '\' AND entity = ' . $entity) <= 0) {
				$errors[] = 'Echec de la mise à jour du paramètre "' . $name . '" (Module ' . $module . ') - ' . $bdb->err();
			}
		}

		self::$conf_cache[$entity][$module][$name] = $value;

		return $errors;
	}

	public static function RemoveConf($name, $module = 'bimpcore', $entity = -1)
	{
		if (!$module) {
			$module = 'bimpcore';
		}

		$errors = array();

		if (BimpTools::isModuleDoliActif('MULTICOMPANY')) {
			if ($entity == -1) {
				$bimp_conf_entity = getEntity('bimp_conf', 0);
				$entity = (isset(self::$conf_cache[$bimp_conf_entity][$module][$name]) ? $bimp_conf_entity : 0);
			}
		} else {
			$entity = 0;
		}

		$bdb = BimpCache::getBdb();

		$id_conf = (int) $bdb->getValue('bimpcore_conf', 'id', 'module = \'' . $module . '\' AND name = \'' . $name . '\' AND entity = ' . $entity);
		if ($id_conf) {
			if ($bdb->delete('bimpcore_conf', 'id = ' . $id_conf) <= 0) {
				$errors[] = 'Echec suppr. param "' . $name . '" - '; //. $this->db->err();
			}
		}

		if (!count($errors)) {
			if (isset(self::$conf_cache[$entity][$module][$name])) {
				unset(self::$conf_cache[$entity][$module][$name]);
			}

			if (isset(self::$conf_cache_def_values[$module][$name])) {
				unset(self::$conf_cache_def_values[$module][$name]);
			}
		}


		return $errors;
	}

	public static function getUserGroupId($group_code)
	{
		// Codes groupes possibles: logistique / facturation / atradius / contrat / achat

		return (int) self::getConf('id_user_group_' . $group_code);
	}

	// Gestion params yml globaux:

	public static function getParam($full_path, $default_value = '', $type = 'string')
	{
		if (is_null(self::$config)) {
			self::$config = new BimpConfig('bimpcore', '', 'config');
		}

		if (!is_null(self::$config)) {
			return self::$config->get($full_path, $default_value, false, $type);
		}

		return $default_value;
	}

	// Getters divers:

	public static function getBimpUser()
	{
		global $bimpUser;

		if (!is_object($bimpUser)) {
			global $user;
			if (BimpObject::objectLoaded($user)) {
				$bimpUser = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $user->id);
			}
		}

		if (BimpObject::objectLoaded($bimpUser)) {
			return $bimpUser;
		}

		return null;
	}

	// Getters booléens:

	public static function isModuleActive($module)
	{
		global $conf;
		$name = strtoupper('MAIN_MODULE_' . $module);
		if (isset($conf->global->$name) && $conf->global->$name) {
			return 1;
		}

		return 0;
	}

	public static function isModeDev()
	{
		if (defined('MOD_DEV') && MOD_DEV) {
			return 1;
		}

		return 0;
	}

	public static function isUserDev()
	{
		global $user;
		if (BimpObject::objectLoaded($user)) {
			$bimpUser = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $user->id);

			if ((int) $bimpUser->getData('is_dev')) {
				return 1;
			}
		}

		return 0;
	}

	// Gestion ini:

	public static function setMaxExecutionTime($time)
	{
		set_time_limit(0);

		if ($time > self::$max_execution_time) {
			ini_set('max_execution_time', $time);
			self::$max_execution_time = $time;
		}
	}

	public static function setMemoryLimit($limit)
	{
		if ($limit > self::$memory_limit) {
			ini_set('memory_limit', $limit . 'M');
			self::$memory_limit = $limit;
		}
	}

	// Gestion extends:

	public static function getExtendsEntity()
	{
		$entity = BimpCore::getConf('extends_entity', '');

		if (!$entity && defined('BIMP_EXTENDS_ENTITY')) {
			return BIMP_EXTENDS_ENTITY;
		}

		return $entity;
	}

	public static function getExtendsVersion()
	{
		$version = BimpCore::getConf('extends_version', '');

		if (!$version && defined('BIMP_EXTENDS_VERSION')) {
			return BIMP_EXTENDS_VERSION;
		}

		return $version;
	}

	public static function isEntity($entity)
	{
		if (BimpCore::getExtendsEntity() != '') {
			if (is_array($entity)) {
				if (in_array(BimpCore::getExtendsEntity(), $entity)) {
					return 1;
				}
			} else {
				if (BimpCore::getExtendsEntity() == $entity) {
					return 1;
				}
			}
		}

		return 0;
	}

	public static function isVersion($version)
	{
		if (BimpCore::getExtendsVersion()) {
			if (is_array($version)) {
				if (in_array(BimpCore::getExtendsVersion(), $version)) {
					return 1;
				}
			} else {
				if (BimpCore::getExtendsVersion() == $version) {
					return 1;
				}
			}
		}

		return 0;
	}

	public static function requireFileForEntity($module, $file_name, $return_only = false)
	{
		// Priorités:
		// - Fichier "Entité"
		// - Fichier "Version"
		// - Fichier entité "default"
		// - Fichier de base

		$dir = DOL_DOCUMENT_ROOT . ($module ? '/' . $module : '') . '/';
		$final_file_path = '';
		$entity = self::getExtendsEntity();
		$version = self::getExtendsVersion();

		if ($entity && file_exists($dir . 'extends/entities/' . $entity . '/' . $file_name)) {
			$final_file_path = $dir . 'extends/entities/' . $entity . '/' . $file_name;
		} elseif ($version && file_exists($dir . 'extends/versions/' . $version . '/' . $file_name)) {
			$final_file_path = $dir . 'extends/versions/' . $version . '/' . $file_name;
		} elseif (file_exists($dir . 'extends/entities/default/' . $file_name)) {
			$final_file_path = $dir . 'extends/entities/default/' . $file_name;
		} elseif (file_exists($dir . $file_name)) {
			$final_file_path = $dir . $file_name;
		}

		if ($final_file_path) {
			if ($return_only) {
				return $final_file_path;
			}
			require_once $final_file_path;
			return true;
		}

		return false;
	}

	// Gestion du contexte:

	public static function getContext()
	{
		if (self::$context) {
			return self::$context;
		}

		if (isset($_REQUEST['bimp_context'])) {
			self::setContext($_REQUEST['bimp_context']);
			return self::$context;
		}

		return "private";
	}

	public static function setContext($context)
	{
		self::$context = $context;
	}

	public static function isContextPublic()
	{
		return (self::getContext() == 'public' ? 1 : 0);
	}

	public static function isContextPrivate()
	{
		return (self::getContext() != 'public' ? 1 : 0);
	}

	// Gestion des logs:

	public static function addLogs_debug_trace($msg)
	{
		$bt = debug_backtrace(null, 30);
		if (is_array($msg)) {
			$msg = implode(' - ', $msg);
		}
		static::addLogs_extra_data([$msg => BimpTools::getBacktraceArray($bt)]);
	}

	public static function addLogs_extra_data($array)
	{
		if (!is_array($array)) {
			$array = array($array);
		}

		static::$logs_extra_data = BimpTools::merge_array(static::$logs_extra_data, $array);
	}

	public static function addlog($msg, $level = 1, $type = 'bimpcore', $object = null, $extra_data = array(), $force = false)
	{
		// $bimp_logs_locked: Eviter boucles infinies

		$errors = array();
		global $bimp_logs_locked, $user;

		if (is_null($bimp_logs_locked)) {
			$bimp_logs_locked = 0;
		}

		if (!$bimp_logs_locked) {
			$bimp_logs_locked = 1;
			$extra_data = BimpTools::merge_array(static::$logs_extra_data, $extra_data);
			if (BimpCore::isModeDev() && (int) self::getConf('print_bimp_logs') && !defined('NO_BIMPLOG_PRINTS') && $level != Bimp_Log::BIMP_LOG_NOTIF) {
				$bt = debug_backtrace(null, 30);

				$html = '<div style="margin-top: 100px">';

				$html .= 'LOG ' . Bimp_Log::$levels[$level]['label'];
				$html .= '</div><br/><br/>';
				$html .= 'Message: ' . $msg . '<br/><br/>';

				if (is_a($object, 'BimpObject') && BimpObject::objectLoaded($object)) {
					$html .= 'Objet: ' . $object->getLink() . '<br/><br/>';
				}

				if (!empty($extra_data)) {
					$html .= 'Données: <pre>';
					$html .= print_r($extra_data, 1);
					$html .= '</pre>';
				}

				$html .= 'Backtrace: <br/>';
				$html .= BimpRender::renderBacktrace(BimpTools::getBacktraceArray($bt));

				die($html);
			}

			if (!$force && $level < Bimp_Log::BIMP_LOG_ERREUR && (int) BimpCore::getConf('mode_eco')) {
				return array();
			}

			if (!(int) BimpCore::getConf('use_bimp_logs') && !(int) BimpTools::getValue('use_logs', 0, 'int')) {
				return array();
			}

			$check = true;
			foreach (Bimp_Log::$exclude_msg_prefixes as $prefixe) {
				if (strpos($msg, $prefixe) === 0) {
					$check = false;
				}
			}

			foreach (Bimp_Log::$exclude_msg_parts as $part) {
				if (strpos($msg, $part) !== false) {
					$check = false;
				}
			}

			if ($check) {
				// On vérifie qu'on n'a pas déjà un log similaire:
//                $id_current_log = BimpCache::bimpLogExists($type, $level, $msg, $extra_data);
				$id_current_log = 0;

				$mod = '';
				$obj = '';
				$id = 0;

				if (is_a($object, 'BimpObject')) {
					$mod = $object->module;
					$obj = $object->object_name;
					$id = (int) $object->id;
				}

				$data = array(
					'id_user'    => (BimpObject::objectLoaded($user) ? (int) $user->id : 1),
					'obj_module' => $mod,
					'obj_name'   => $obj,
					'id_object'  => $id,
					'backtrace'  => BimpTools::getBacktraceArray(debug_backtrace(null, 15))
				);

				if (!$id_current_log) {
					if (defined('ID_ERP')) {
						$extra_data['ID ERP'] = ID_ERP;
					}

					$data = BimpTools::merge_array($data, array(
						'type'       => $type,
						'level'      => $level,
						'msg'        => $msg,
						'extra_data' => $extra_data,
					));
					$log = BimpObject::createBimpObject('bimpcore', 'Bimp_Log', $data, true, $errors);

					if (BimpObject::objectLoaded($log)) {
						BimpCache::addBimpLog((int) $log->id, $type, $level, $msg, $extra_data);
						if (BimpDebug::isActive()) {
							BimpDebug::incCacheInfosCount('logs', true);
						}
					} elseif (BimpCore::isModeDev()) {
						echo 'Echec création du log "' . $msg . '"<br/>';
						echo 'Extra data : <pre>';
						print_r($extra_data);
						echo '</pre>';
						echo '<pre>';
						print_r($errors);
						exit;
					}
				} else {
					if (BimpDebug::isActive()) {
						BimpDebug::incCacheInfosCount('logs', false);
					}

					$log = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Log', $id_current_log);
					$log->set('last_occurence', date('Y-m-d H:i:d'));
					$log->set('nb_occurence', $log->getData('nb_occurence') + 1);

					$warnings = array();
					$errUpdate = $log->update($warnings, true);

					if ((int) BimpCore::getConf('save_similar_logs_as_note')) {
						$data = array(); // inutile de mettre les data de bases (Type, level, msg, extra_data) qui sont forcéments identiques.

						if (defined('ID_ERP')) {
							$data['ID ERP'] = ID_ERP;
						}

						if (BimpObject::objectLoaded($user)) {
							$data['User'] = '#' . $user->id;
						}

						if (BimpObject::objectLoaded($object)) {
							$data['Objet'] = BimpObject::getInstanceNomUrl($object);
						}

						if (count($errUpdate)) {
							$data['Erreurs Màj log'] = $errUpdate;
						}

						$data['GET'] = BimpTools::htmlentities_array($_GET);
						$data['POST'] = BimpTools::htmlentities_array($_POST);
						$log->addNote('<pre>' . print_r($data, 1) . '</pre>');
					}
				}
			}

			$bimp_logs_locked = 0;
		}

		return $errors;
	}

	// Gestion des locks

	public static function checkObjectLock($object, &$token = '')
	{
		// On retourne un message d'erreur si blocage nécessaire. false sinon.

		if (!(int) self::getConf('use_objects_locks') || static::isModeDev()) {
			return false;
		}

		if (!is_a($object, 'BimpObject') || !BimpObject::objectLoaded($object)) {
			return false;
		}

		global $user;

		$bdb = BimpCache::getBdb(true);

		$where = 'obj_module = \'' . $object->module . '\'';
		$where .= ' AND obj_name = \'' . $object->object_name . '\'';
		$where .= ' AND id_object = ' . $object->id;

		for ($i = 0; $i < 10; $i++) {
			if ($i > 0) {
				sleep(1);
			}
			$row = $bdb->getRow('bimpcore_object_lock', $where, array('id', 'tms', 'id_user', 'token'), 'array', 'tms', 'DESC');

			if (!is_null($row)) {
				if ($token && $token == $row['token']) {
					// Si token fourni et correspond au lock en cours : pas de blocage, on conserve le lock actuel
					// On réinitialise tout de même le tms:
					$bdb->update('bimpcore_object_lock', array(
						'tms' => time()
					), 'id = ' . (int) $row['id']);
					return false;
				}

				if ((int) $row['tms'] < time() - 600) {
					// Si locké depuis + de 12 minutes
					if (!$token) {
						$token = BimpTools::randomPassword(12);
					}
					$bdb->update('bimpcore_object_lock', array(
						'id_user' => $user->id,
						'tms'     => time(),
						'token'   => $token
					), 'id = ' . (int) $row['id']);
					return false;
				}
			}

			if (is_null($row)) {
				// Pa de lock en cours, on en créé un:
				if (!$token) {
					$token = BimpTools::randomPassword(12);
				}
				global $bimp_object_locked_id;
				$bimp_object_locked_id = $bdb->insert('bimpcore_object_lock', array(
					'obj_module' => $object->module,
					'obj_name'   => $object->object_name,
					'id_object'  => $object->id,
					'tms'        => time(),
					'id_user'    => $user->id,
					'token'      => $token
				), true);
				return false;
			}
		}

		global $user;

		$msg = '';

		if ((int) $user->id === (int) $row['id_user']) {
			$msg = 'Vous avez déjà lancé une opération sur ' . $object->getLabel('the') . ' ' . $object->getRef(true) . '<br/>';
			$msg .= 'Veuillez attendre que l\'opération en cours soit terminée avant de relancer l\'enregistrement.<br/>';
			$msg .= '<b>Note: ceci est une protection volontaire pour éviter un écrasement de données. Il ne s\'agit pas d\'un bug</b>';

			$diff = ((int) $row['tms'] + 720) - time();
			$min = floor($diff / 60);
			$secs = $diff - ($min * 60);

			$msg .= '<div style="margin-top: 15px; font-weight: bold;">';
			$msg .= 'Si l\'opération en cours sur ' . $object->getLabel('this') . ' a échoué (erreur fatale, problème réseau, etc.), ';
			$msg .= 'le vérouillage sera automatiquement désactivé dans ';
			$msg .= ($min ? $min . ' min. ' . ($secs ? 'et ' : '') : '') . ($secs ? $secs . ' sec.' : '');
			$msg .= '</div>';

			if ($user->admin || (isset($object->allow_force_unlock) && $object->allow_force_unlock)) {
				$msg .= '<div style="margin: 15px 0; text-align: center">';
				if (!$user->admin) {
					$msg .= 'Si vous êtes sûr de n\'avoir aucune opération en cours, vous pouvez cliquer sur le bouton ci-dessous.<br/>';
					$msg .= 'Une fois fois le dévéroullage effectué, relancez l\'opération qui a été bloquée (en cliquant à nouveau sur le bouton "Enregistrer" ou "Valider")';
					$msg .= '<br/>';
				}

				$msg .= '<span class="btn btn-default" onclick="forceBimpObjectUnlock($(this), ' . $object->getJsObjectData() . ')">';

				if ($user->admin) {
					$msg .= 'Forcer le dévérouillage (Admins seulement)';
				} else {
					$msg .= 'Dévérouiller';
				}
				$msg .= '</span>';
				$msg .= '</div>';
			}
		} else {
			$lock_user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $row['id_user']);

			$msg = 'Une opération est déjà en cours sur ' . $object->getLabel('the') . ' ' . $object->getRef(true);
			if (BimpObject::objectLoaded($lock_user)) {
				$msg .= ' par l\'utilisateur ' . $lock_user->getLink();
			}
			$msg .= '<br/>';
			$msg .= 'Il est nécessaire d\'attendre que celle-ci soit terminée pour éviter un conflit sur l\'enregistrement des données.<br/>';
			$msg .= 'Merci d\'attendre une dizaine de secondes et de réessayer.<br/>';
			$msg .= '<b>Etant donné qu\'il est possible que les données de ' . $object->getLabel('this') . ' aient été modifiées, il est recommandé ';
			$msg .= ' <a href="javascript:bimp_reloadPage()">d\'actualiser la page</a> avant de retenter l\'opération</b><br/><br/>';
			$msg .= '<b>Note: ceci est une protection volontaire pour éviter un écrasement de données. Il ne s\'agit pas d\'un bug</b>';
		}

		return $msg;
	}

	public static function unlockObject($module, $object_name, $id_object, $token = '')
	{
		if (!(int) self::getConf('use_objects_locks') || static::isModeDev()) {
			return array();
		}

		$errors = array();

		if (!$module) {
			$errors = 'Module absent';
		}

		if (!$object_name) {
			$errors[] = 'Type d\'objet absent';
		}

		if (!$id_object) {
			$errors[] = 'ID objet absent (err 20)';
		}

		if (!count($errors)) {
			$bdb = BimpCache::getBdb(true);

			$where = 'obj_module = \'' . $module . '\'';
			$where .= ' AND obj_name = \'' . $object_name . '\'';
			$where .= ' AND id_object = ' . $id_object;

			if ($token) {
				$where .= ' AND token = \'' . $token . '\'';
			}

			if ($bdb->delete('bimpcore_object_lock', $where) <= 0) {
				$errors[] = 'Echec de la suppression du vérouillage - ' . $bdb->err();
			}
		}

		return $errors;
	}

	public static function forceUnlockCurrentObject()
	{
		global $bimp_object_locked_id;

		if ((int) $bimp_object_locked_id) {
			$bdb = BimpCache::getBdb(true, -1, true);
			$bdb->delete('bimpcore_object_lock', 'id = ' . $bimp_object_locked_id);
		}
	}

	// Sécurité :

	public static function checkRateLimit($type, &$errors = array())
	{
		global $user, $langs;

		$limit = (int) BimpCore::getConf('rate_limiting_' . $type . '_limit', 20);

		if (!$limit) {
			return 1;
		}

		$period = (int) BimpCore::getConf('rate_limiting_' . $type . '_period', 60);
		$reset_delay = (int) BimpCore::getConf('rate_limiting_' . $type . '_reset_delay', 30);

//		$is_mode_dev = (BimpCore::isModeDev() || $user->login == 'f.martinez');
		$is_mode_dev = false;
//		if ($is_mode_dev) {
//			$limit = 3;
//			$reset_delay = 10;
//		}

//		$id_user = 0;
//		if (BimpObject::objectLoaded($user)) {
//			$id_user = $user->id;
//		}

		$current_time = time();
//		$data = BimpCache::getCacheServeur('rate_limiting_' . $type);
		$data = (isset($_SESSION['rate_limiting_' . $type]) ? $_SESSION['rate_limiting_' . $type] : array());

		// Si durée depuis la dernière requête > délai de réinitialisation,
		// ou si durée depuis la première requête > période de vérification ET limite non atteinte,
		// on réinitialise le compteur:

//		if ($current_time - $data['last_access_time'] >= $reset_delay) {
//			$errors[] = 'REINIT 1';
//		}
//
//		if ($current_time - $data['start_time'] >= $period && $data['count'] < $limit) {
//			$errors[] = 'REINIT 2 - P = ' . $period .' - T = ' . ($current_time - $data['start_time']) .' - C = ' . $data['count'] . ' - L = ' . $limit;
//		}

		if (empty($data) ||
			($current_time - $data['last_access_time'] >= $reset_delay) ||
			($current_time - $data['start_time'] >= $period && $data['count'] < $limit)) {
			$data = array(
				'count'            => 0,
				'start_time'       => $current_time,
				'last_access_time' => $current_time
			);
		}

		$data['count']++;
		if ($data['count'] <= $limit) {
			$data['last_access_time'] = $current_time;
		}

		$_SESSION['rate_limiting_' . $type] = $data;
//		BimpCache::setCacheServeur('rate_limiting_' . $type, $data);

		// Vérif limite atteinte :
		if ($data['count'] >= $limit) {
			if ($data['count'] == $limit) {
				BimpCore::addlog('Limite de requêtes atteinte en ' . ($current_time - $data['start_time']) . ' sec (type : ' . $type . ') debut : ' . $data['start_time'] . ' maintenant : ' . $current_time, 2, 'secu', null, array(
					'Utilisateur' => $user->id,
					'Limit'       => $limit,
					'Count'       => $data['count'],
					'Period'      => $period,
					'Délai'       => $reset_delay
				));
			}

			if ($is_mode_dev) {
				$errors[] = 'Limite de requêtes atteinte (' . $data['count'] . '). Merci de patienter quelques instants avant de réessayer.<pre>' . print_r($_SESSION['rate_limiting_' . $type], 1) . '</pre>';
				return 0;
			}
		}

		return 1;
	}

	// Chargements librairies:

	public static function loadPhpExcel()
	{
		require_once DOL_DOCUMENT_ROOT . '/bimpcore/libs/PHPExcel-1.8/Classes/PHPExcel.php';
	}

	public static function loadPhpSpreadsheet()
	{
		if (!defined('PHPEXCELNEW_PATH')) {
			define('PHPEXCELNEW_PATH', DOL_DOCUMENT_ROOT . '/includes/phpoffice/phpspreadsheet/src/PhpSpreadsheet/');
		}

		require_once DOL_DOCUMENT_ROOT . '/includes/phpoffice/phpspreadsheet/src/autoloader.php';
		require_once DOL_DOCUMENT_ROOT . '/includes/Psr/autoloader.php';
		require_once PHPEXCELNEW_PATH . 'Spreadsheet.php';
	}

	public static function LoadHtmlPurifier()
	{
		require_once DOL_DOCUMENT_ROOT . '/bimpcore/libs/htmlpurifier-4.13.0/HTMLPurifier.auto.php';
	}

	public static function getHtmlPurifier()
	{
		if (is_null(self::$html_purifier)) {
			self::LoadHtmlPurifier();

			$config = HTMLPurifier_Config::createDefault();

			$root = '';

			if (defined('PATH_TMP') && PATH_TMP) {
				$root = PATH_TMP;
				$path = '/htmlpurifier/serialiser';
			} else {
				$root = DOL_DATA_ROOT;
				$path = '/bimpcore/htmlpurifier/serialiser';
			}

			if (!is_dir($root . $path)) {
				BimpTools::makeDirectories($path, $root);
			}

			$config->set('Cache.SerializerPath', $root . $path);

			self::$html_purifier = new HTMLPurifier($config);
		}

		return self::$html_purifier;
	}

	public static function loadBimpApiLib()
	{
		require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';
	}

	// Rendus HTML Globaux:

	public static function renderUserTopExtraToolsHtml()
	{
		$html = '';

		// Déclarer un bug:
		if (BimpCore::isModuleActive('bimptask') && BimpCore::getConf('allow_bug_task', null, 'bimptask')) {
			$task = BimpObject::getInstance('bimptask', 'BIMP_Task');
			$onclick = $task->getJsLoadModalForm('bug', 'Signaler un bug', array(
				'fields' => array(
					'url' => dol_escape_htmltag($_SERVER['REQUEST_URI'])
				)
			));

			$html .= '<div>';
			$html .= '<span class="btn btn-light-default" onclick="' . $onclick . '">';
			$html .= BimpRender::renderIcon('fas_bug', 'iconLeft') . 'Déclarer un bug';
			$html .= '</span>';
			$html .= '</div>';
		}

		// Change log ERP
		$onclick = "bimpModal.loadAjaxContent($(this), 'loadChangeLog', {}, 'ChangeLog ERP', 'Chargement', function (result, bimpAjax) {});";
		$onclick .= 'bimpModal.show();';
		$html .= '<div>';
		$html .= '<span class="btn btn-light-default" onclick="' . $onclick . '">';
		$html .= BimpRender::renderIcon('fas_book-medical', 'iconLeft') . 'ChangeLog ERP';
		$html .= '</span>';
		$html .= '</div>';

		// Outils devs:
		global $user;
		$is_user_dev = BimpCore::isUserDev();

		if ($is_user_dev || $user->login == 's.lehalle') {
			$html .= '<div style="margin: 5px 0; text-align: center; color: #7F7F7F; font-size: 11px">----- OUTILS DEV ------</div>';

			$onclick = "bimpModal.loadAjaxContent($(this), 'loadChangeLog', {type: 'dev'}, 'ChangeLog DEV', 'Chargement', function (result, bimpAjax) {});";
			$onclick .= 'bimpModal.show();';
			$html .= '<div>';
			$html .= '<span class="btn btn-light-default" onclick="' . $onclick . '">';
			$html .= BimpRender::renderIcon('fas_book-medical', 'iconLeft') . 'ChangeLog DEV';
			$html .= '</span>';
			$html .= '</div>';

			if (!BimpCore::isModeDev() && $is_user_dev) {
				$html .= '<a class="btn btn-light-default" href="' . DOL_URL_ROOT . '/synopsistools/git_pull_all.php?go=1" target="_blank">';
				$html .= BimpRender::renderIcon('fas_arrow-down', 'iconLeft') . 'GIT PULL ALL';
				$html .= '</a>';
			}
		}

		if ($html) {
			$content = '<div style="padding: 10px;">' . $html . '</div>';

			$label = BimpRender::renderIcon('fas_tools');
			return BimpRender::renderDropDownContent('userDropdown', $label, $content, array(
				'type'        => 'span',
				'extra_class' => 'nav-link header-icon',
				'side'        => 'left'
			));
		}

		return '';
	}

	public static function renderUserTopAccountHtml()
	{
		global $user;
		$label = Form::showphoto('userphoto', $user, 28, 28, 0, '', 'mini', 0);
//        $label = '<img class="avatar" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=userphoto&entity=1&file=' . $user->photo . '" alt="" style="display: inline-block; margin-right: 12px">';
		$label .= '<span class="mobile-hidden">' . $user->firstname . ' ' . $user->lastname . '</span>';

		$content = '';
		$content .= '<div style="padding: 15px;">';

		// Mon profile:
		$content .= '<div style="margin-bottom: 12px">';
		$content .= '<a href="' . DOL_URL_ROOT . '/bimpcore/index.php?fc=user&id=' . $user->id . '">';
		$content .= BimpRender::renderIcon('fas_user', 'iconLeft') . 'Mon profil';
		$content .= '</a>';
		$content .= '</div>';

		// Mon agenda:
		$content .= '<div style="margin-bottom: 12px">';
		$content .= '<a href="' . DOL_URL_ROOT . '/synopsistools/agenda/vue.php">';
		$content .= BimpRender::renderIcon('fas_calendar-alt', 'iconLeft') . 'Mon Agenda';
		$content .= '</a>';
		$content .= '</div>';

		// Ma messagerie:
		$content .= '<div style="margin-bottom: 12px">';
		$content .= '<a href="' . DOL_URL_ROOT . '/bimpmsg/index.php?fc=bal">';
		$content .= BimpRender::renderIcon('fas_envelope-open-text', 'iconLeft') . 'Ma messagerie';
		$content .= '</a>';
		$content .= '</div>';

		// Toutes mes tâches:
		if (BimpCore::isModuleActive('bimptask')) {
			$content .= '<div style="margin-bottom: 12px">';
			$content .= '<a href="' . DOL_URL_ROOT . '/bimpcore/index.php?fc=user&id=' . $user->id . '&navtab-maintabs=tasks&navtab-tasks=my_tasks">';
			$content .= BimpRender::renderIcon('fas_tasks', 'iconLeft') . 'Toutes mes tâches';
			$content .= '</a>';
			$content .= '</div>';
		}

		// Logout:
		$content .= '<div style="margin-top: 10px; text-align: center">';
		$content .= '<a class="btn btn-danger" href="' . DOL_URL_ROOT . '/user/logout.php">';
		$content .= BimpRender::renderIcon('fas_power-off', 'iconLeft') . 'Se déconnecter';
		$content .= '</a>';
		$content .= '</div>';

		$content .= '</div>';
		return BimpRender::renderDropDownContent('userDropdown', $label, $content, array(
			'type'        => 'span',
			'extra_class' => 'dropdown-profile modifDropdown'
		));
	}
}
