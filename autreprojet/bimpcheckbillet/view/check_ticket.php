<?php

include_once 'header.php';
include_once 'footer.php';

//$arryofjs = array('../js/check_ticket');

printHeader('Valider ticket'/*, $arryofjs */);

print '<body>';
print '<fieldset class="container_form">';
print '<legend><span>Valider ticket</span></legend>';

print '<label for="event">Evènement </label><br/>';
print '<select class="chosen-select" name="event"><option></option></select><br/><br/>';

print '
        <canvas></canvas>
        <ul></ul>
        <script type="text/javascript" src="../scan/jquery.js"></script>
        <script type="text/javascript" src="../scan2/js/qrcodelib.js"></script>
        <script type="text/javascript" src="../scan2/js/webcodecamjs.js"></script>
        <script type="text/javascript" src="../scan/scan.js"></script>
        <script type="text/javascript">
            var txt = "innerText" in HTMLElement.prototype ? "innerText" : "textContent";
            var arg = {
                resultFunction: function(result) {
                    alert(result.code);
                    traiteCode(result.code);
                },   // string, DecoderWorker file location
            };
            var decoder = new WebCodeCamJS("canvas").buildSelectMenu(document.createElement(\'select\'), \'environment|back\').init(arg).play();
             
        </script>
        <link rel="stylesheet" type="text/css" href="../lib/css/chosen.min.css">
        <script type="text/javascript" src="../lib/js/chosen.jquery.min.js"></script>
        <script type="text/javascript" src="../js/check_ticket.js"></script>
        <script type="text/javascript" src="../js/annexes.js"></script>
        <link rel="stylesheet" type="text/css" href="../scan/scan.css">


<div id="input">
    <input type="text" id="barcode"/>
</div>
<br/>
<br/>
<br/>
<div id="alertSubmit">
</div>
</fieldset>
</body>';

printFooter();
