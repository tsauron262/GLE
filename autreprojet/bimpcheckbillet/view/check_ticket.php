<?php

include_once 'header.php';
include_once 'footer.php';



//$arrayofjs = array('../scan/jquery.js', '../scan2/js/qrcodelib.js',
//    '../scan2/js/webcodecamjs.js', '../js/check_ticket.js', '../js/annexes.js',
//    '../scan2/js/initScan.js');

   
//$arrayofcss = array('../scan/scan.css');

printHeader('Valider ticket');

print '
    <body>
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

    <link rel="stylesheet" type="text/css" href="../scan/scan.css">
    

<div id="input">
    <input type="text" id="barcode"/>
</div>
<br/>
<br/>
<br/>
<div id="alertSubmit">
</div>
</body>';

printFooter();
