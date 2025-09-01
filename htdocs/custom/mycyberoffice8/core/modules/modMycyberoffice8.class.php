<?php
/**
 *  MyCyberOffice
 *
 *  @author    LVSinformatique <contact@lvsinformatique.com>
 *  @copyright 2016 LVSInformatique
 *  @license   NoLicence
 *  This source file is subject to a commercial license from LVSInformatique
 *  Use, copy or distribution of this source file without written
 *  license agreement from LVSInformatique is strictly forbidden.
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

class modmycyberoffice8 extends DolibarrModules
{
    function __construct($db)
    {
        global $langs,$conf;
        $this->db = $db;
        $langs->load('mycyberoffice@mycyberoffice8');
        $this->numero = 171101;
        $this->rights_class = 'mycyberoffice8';

        $this->family = 'technic';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = $langs->trans('SynchronizationfromDolibarrERPCRMtoPrestashop');
        $this->version = '8.2.1';
        $this->editor_name = 'LVSInformatique';

        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->special = 2;
        $this->picto = 'mycyberoffice@mycyberoffice8';

        $this->module_parts = [
            'triggers' => 1,
            'hooks' => ['formfile',
                'fileslib',
                'productcard',
                'expeditioncard',
            ],
        ];

        $this->triggers = 1;

        $this->dirs = [];
        $r=0;

        $this->config_page_url = ['mycyberoffice_setupapage.php@mycyberoffice8'];

        $this->depends= ['modCyberoffice8'];
        $this->requiredby = [];
        $this->phpmin = [7,3];
        $this->need_dolibarr_version = [17,0];
        $this->langfiles = ['mycyberoffice@mycyberoffice8'];

        $this->const = [];

        $this->boxes = [];
        $r = 0;

        $this->rights = [];

        $this->menu = [];
        $r = 0;
    }

    function init($options = '')
    {
        global $db, $conf, $langs, $user, $mysoc;
        require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
        require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
        $rep = getcwd() . '/../mycyberoffice8';
        if (file_exists($rep)) {
            setEventMessages($langs->trans('errorcustom8'), '', 'errors');
            return -1;
        }
        if (!empty($conf->global->MAIN_MODULE_MYCYBEROFFICE)) {
            setEventMessages($langs->trans('MAIN_MODULE_MYCYBEROFFICEActive'), '', 'errors');
            return -1;
        }
        $rep = $conf->file->dol_document_root['main'] . '/mycyberoffice';
        if (file_exists($rep)) {
            $res = $this->rrmdir($rep);
        }
        $sql = [];
        return $this->_init($sql, $options);
    }

    function remove($options = '')
    {
        global $langs, $conf;

        $sql = [];
        return $this->_remove($sql, $options);
    }

    function load_tables()
    {
        return $this->_load_tables('');
    }
    function rrmdir($src)
	{
		$dir = opendir($src);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				$full = $src . '/' . $file;
				if ( is_dir($full) ) {
					$this->rrmdir($full);
				} else {
					unlink($full);
				}
			}
		}
		closedir($dir);
		rmdir($src);
	}
}
