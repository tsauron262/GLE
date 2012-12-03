<?php
/** * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures * all the essential functionalities required for any enterprise. * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com * * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of * the GNU General Public License as published by the Free Software Foundation; either * version 2 of the License, or (at your option) any later version. * * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. * See the GNU General Public License for more details. * * You should have received a copy of the GNU General Public License along with this program; * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, * Boston, MA  02110-1301, USA */
define('ROOT_PATH', dirname(__FILE__));require_once ROOT_PATH . '/lib/common/CommonFunctions.php';

session_start();
$styleSheet = CommonFunctions::getTheme();
?>

<html>
<head>
<title>OrangeHRM - New Level of HR Management</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="themes/<?php echo $styleSheet;?>/css/style.css" rel="stylesheet" type="text/css">
<style type="text/css">
<!--
.bodyTXT {    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    color: #666666;
}
.style2 {color: #339900}
-->
</style>
</head>
<body>
<!-- <body bgcolor="#FFFFFF" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0"> -->
<!-- ImageReady Slices (orange_new.psd) -->

<table width="100%"  border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td><img src="themes/<?php echo $styleSheet;?>/pictures/spacer.gif" width="5" height="1" alt=""></td>
        <td><img src="themes/<?php echo $styleSheet;?>/pictures/spacer.gif" width="167" height="1" alt=""></td>
        <td><img src="themes/<?php echo $styleSheet;?>/pictures/spacer.gif" width="23" height="1" alt=""></td>
        <td><img src="themes/<?php echo $styleSheet;?>/pictures/spacer.gif" width="71" height="1" alt=""></td>
        <td><img src="themes/<?php echo $styleSheet;?>/pictures/spacer.gif" width="391" height="1" alt=""></td>
        <td><img src="themes/<?php echo $styleSheet;?>/pictures/spacer.gif" width="60" height="1" alt=""></td>
      </tr>
    </table></td>
    <td width="20%">&nbsp;</td>
  </tr>
</table>
<!-- End ImageReady Slices -->
</body>
</html>
