<?php

include_once 'header.php';
include_once 'footer.php';


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
        <link rel="stylesheet" type="text/css" href="../css/check_ticket.css">
        <link rel="stylesheet" type="text/css" href="../css/home.css.css">


<div id="input">
    <input type="text" id="barcode"/>
</div>
<div id="alertSubmit"></div>
<br/>
<div style="width = 100%; text-align: center">
    <img id="imgOk" src="../img/checked.png" style="width: 128px; height: 128px; display: none"/>
    <img id="imgEr" src="../img/error.png"   style="width: 128px; height: 128px; display: none"/>
    <br/>

    <button id="showHistory" class="btn btn-primary" style="float: left">Voir l\'historique</button><br/><br/>

    <div id="history" toggled="false" style="text-align: left; display: block; height: 100px; width: 520px; overflow: hidden; border: 1px solid black;">
    </div>
</div>    

</fieldset>
</body>';

printFooter();
