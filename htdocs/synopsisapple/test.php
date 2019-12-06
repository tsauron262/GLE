<?php
require_once('../main.inc.php');
llxHeader();
?>

<link type="text/css" rel="stylesheet" href="appleGSX.css"/>
<script type="text/javascript" src="./appleGsxScripts.js"></script>
<script type="text/javascript" src="../bimpsupport/views/js/sav.js"></script>
<script type="text/javascript" src="../bimpapple/views/js/gsx.js"></script>
<script>use_gsx_v2 = true;</script>

<div id="loadGSXForm" style="background-color: #E6E6E6; width: 100%; padding: 10px; margin-bottom: 30px;">
    <label for="serialInput">Entrez un numéro de série: </label>
    <!--013409004309660-->
    <!--C2QMDSAVFH00-->
    <input type="text" name="serialInput" id="gsx_equipment_serial" value="C2QMDSAVFH00" onfocus="this.value = ''"/>
    <button id="gsx_button" onclick="loadGSXView($(this));">&nbsp;&nbsp;OK&nbsp;&nbsp;</button>
</div>
<div id="gsxResultContainer"></div>
<div id="requestResult"></div>



<?php


llxFooter();

