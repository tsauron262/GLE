<?php
require_once('../main.inc.php');
llxHeader();
?>

<link type="text/css" rel="stylesheet" href="appleGSX.css"/>
<script type="text/javascript" src="./appleGsxScripts.js"></script>

<div style="background-color: #E6E6E6; width: 100%; padding: 10px; margin-bottom: 30px;">
    <label for="serialInput">Entrez un numéro de série: </label>
    <!--013409004309660-->
    <!--C2QMDSAVFH00-->
    <input type="text" name="serialInput" id="serialInput" value="C2QMDSAVFH00" onfocus="this.value = ''"/>
    <button id="serialSubmit">&nbsp;&nbsp;OK&nbsp;&nbsp;</button>
</div>
<div id="requestsResponsesContainer"></div>
<div id="requestResult"></div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#serialSubmit').click(function() {
            if (GSX) {
                GSX.loadProduct($('#serialInput').val());
            }
            else
                alert('Objet GSX non initialisé');
        });
    });
</script>

