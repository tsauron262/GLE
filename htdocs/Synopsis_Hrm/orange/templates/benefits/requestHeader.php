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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $lang_Benefits_Module_Title; ?></title>

<link href="../../themes/<?php echo $styleSheet;?>/css/time.css" rel="stylesheet" type="text/css" />
<link href="../../themes/<?php echo $styleSheet;?>/css/suggestions.css" rel="stylesheet" type="text/css" />

<script src="../../scripts/autoSuggest.js"></script>
<script src="../../scripts/suggestions.js"></script>

<script type="text/javascript">

	window.onload = function () {

		var dependents = new Array();

		<?php
			$dependents = array($records[6]);
			if (is_array($records[7]))
				$dependents = array_merge($dependents, $records[7]);
			if (is_array($records[8]))
				$dependents = array_merge($dependents, $records[8]);

			for ($i=0;$i<count($dependents);$i++) {
				echo "dependents[" . $i . "] = \"" . $dependents[$i] . "\";";
			}
		?>

        var oTextbox = new AutoSuggestControl(document.getElementById("txtPersonIncurringExpense"), new StateSuggestions(dependents));
    }

</script>

</head>

<script src="../../scripts/time.js"></script>
<script src="../../scripts/archive.js"></script>

<body>
