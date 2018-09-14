<?php

include_once 'header.php';
include_once 'footer.php';




$arryofcss = array("./srcScan/style.css", "../lib/css/chosen.min.css", "../scan/scan.css", "../css/check_ticket.css", "../css/home.css.css");
$arryofjs = array("../lib/js/chosen.jquery.min.js", "../js/check_ticket.js", "../js/annexes.js",
"./srcScan/adapter.js", "./srcScan/vue.min.js", "./srcScan/instascan.min.js"
 );



printHeader('Valider ticket', $arryofjs, $arryofcss);

print '<body>';


print '
    <div id="app">
      <div class="sidebar">
        <section class="cameras">
          <ul>
            <li v-if="cameras.length === 0" class="empty">No cameras found</li>
            <li v-for="camera in cameras">
              <span v-if="camera.id == activeCameraId" :title="formatName(camera.name)" class="active">{{ formatName(camera.name) }}</span>
              <span v-if="camera.id != activeCameraId" :title="formatName(camera.name)">
                <a @click.stop="selectCamera(camera)">{{ formatName(camera.name) }}</a>
              </span>
            </li>
          </ul>
        </section>
      </div>';

print      '
    </div>';



print '<fieldset class="container_form">';
print '<legend><span>Valider ticket</span></legend>';

print '<div class="preview-container"><video id="preview"></video></div>';
print '<script type="text/javascript" src="./srcScan/app.js"></script>';

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
</div></td>
<td>
<div class="btn-group btn-group-toggle" data-toggle="buttons">
          <ul>
            <li v-if="cameras.length === 0" class="empty">No cameras found</li>
            <li v-for="camera in cameras">
              <span v-if="camera.id == activeCameraId" :title="formatName(camera.name)" class="active">{{ formatName(camera.name) }}</span>
              <span v-if="camera.id != activeCameraId" :title="formatName(camera.name)">
                <a @click.stop="selectCamera(camera)">{{ formatName(camera.name) }}</a>
              </span>
            </li>
          </ul>
</div></td>
<td></td>';

print '<td><input id="cntEntry" value=0 disabled style="width: 100px"/></td></tr></table>';



print '
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
