<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

define('ROOT_PATH', dirname(__FILE__));

require_once ROOT_PATH . '/lib/common/Language.php';
//$lan = new Language();
require_once ROOT_PATH . '/language/default/lang_default_full.php';
//require_once($lan->getLangPath("full.php"));
require_once ROOT_PATH . '/language/fr/lang_fr_full.php';
$url = 'lib/controllers/PublicController.php?recruitcode=ApplicantViewJobs';
?>
<html>
<head>
<title><?php echo $lang_Recruit_ApplicantVacancyList_Title; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<iframe align="center" src="<?php echo $url; ?>" id="rightMenu" name="rightMenu" width="100%" height="100%" frameborder="0"></iframe>
</body>
</html>