<?php

include_once 'header.php';
include_once 'footer.php';


printHeader('Valider ticket'/*, $arryofjs */);

print '<body>';
print '<fieldset class="container_form">';
print '<legend><span>Valider ticket</span></legend>';

print '<label for="event">Evènement </label><br/>';
print '<select class="chosen-select" name="event"><option></option></select><br/><br/>';

print '<table><tr>';
print '<th>Son</th>';
print '<th style="width: 150px"></th>';
print '<th>Nombre de ticket scanné</th></tr>';
print '<tr><td>
<div class="btn-group btn-group-toggle" data-toggle="buttons">
    <label name="sound" class="btn btn-primary">        <input name="sound" value=0 type="radio"/>Sans</label>
    <label name="sound" class="btn btn-primary active"> <input name="sound" value=1 type="radio"/>Avec</label>
</div></td><td></td>';

print '<td><input id="cntEntry" value=0 disabled style="width: 100px"/></td></tr></table>';

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

    <div id="history" toggled="false" style="padding: 5px; text-align: left; display: block; height: 100px; width: 520px; overflow: hidden; border: 1px solid black;">
    </div>
</div>
       

<audio id="beepSound" preload="auto"><source src="../sound/beep.wav" type="audio/mp3"/></audio>
<audio id="errorSound" preload="auto"><source src="../sound/error.flac" type="audio/mp3"/></audio>

</fieldset>
</body>';

printFooter();
