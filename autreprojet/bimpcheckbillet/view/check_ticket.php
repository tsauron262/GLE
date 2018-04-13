<?php

include_once 'header.php';
include_once 'footer.php';


printHeader('Valider ticket');

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
        <script type="text/javascript" src="../js/check_ticket.js"></script>
        <script type="text/javascript" src="../js/annexes.js"></script>
        <script type="text/javascript" src="../js/chosen.jquery.min.js"></script>

    <link rel="stylesheet" type="text/css" href="../scan/scan.css">
    <link rel="stylesheet" type="text/css" href="../css/chosen.min.css">


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
