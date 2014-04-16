function getXMLHttpRequest() {
    var xhr = null;
    if (window.XMLHttpRequest || window.ActiveXObject) {
        if (window.ActiveXObject) {
            try {
                xhr = new ActiveXObject("msxml2.XMLHTTP");
            }
            catch(e) {
                xhr = new ActiveXObject("Microsoft.XMLHTTP");
            }
        }
        else {
            xhr = new XMLHttpRequest();
        }
    }
    return xhr;
}

function onRequestResponse(xhr, requestType) {
    switch (requestType) {
        case 'newSerial':
            $('#serialResult').slideUp(250);
            $('#productInfos').html(xhr.responseText);
            $('#productInfos').slideDown(250);
            break;

        case 'searchParts':
            $('#partsResult').slideUp(250);
            $('#partsResult').html(xhr.responseText);
            $('#partsResult').slideDown(250);
            break;
    }
}

function setGetRequest(requestType, requestParams) {
    var xhr = getXMLHttpRequest();
    xhr.onreadystatechange = function(){
        //alert('state: ' + xhr.readyState + ', status: ' +xhr.status);
        var RT = requestType;
        if((xhr.readyState == 4) && ((xhr.status == 200) || (xhr.status == 0))) {
            onRequestResponse(xhr, RT);
        }
    }
    xhr.open("GET", './requestProcess.php?action='+requestType+requestParams);
    xhr.send();
}

function onComponentSearchSubmit() {
    var params = '&serial='+$('#curSerial').val()+'&filter='+$('#componentType').val();
    var search = $('#componentSearch').val()
    if (search) {
        if (!/^[a-zA-Z0-9 \-]+$/.test(search)) {
            alert('Votre recherche par mots-clés ne doit contenir que des caractères alphanumériques');
            return;
        }
        params += '&search='+search;
    }
    $('#partsResult').slideUp(250);
    $('#partsResult').html('Requête en cours de traitement...');
    $('#partsResult').slideDown(250);

    setGetRequest('searchParts', params);
}
$(document).ready(function() {
    $('#serialSubmit').click(function() {
        $('#serialResult').slideUp(250);
        $('#productInfos').slideUp(250);
        var serial = $('#serialInput').val();
        if (!serial) {
            $('#serialResult').html('<p class="error">Veuillez entrer un numéro de série</p>');
        } else if (!/^[0-9a-zA-Z]+$/.test(serial)) {
            $('#serialResult').html('<p class="error">Le format du numéro de série est incorrect</p>');
        } else {
            setGetRequest('newSerial', '&serial='+serial);
            $('#serialResult').html('<p>Requête en cours de traitement...</p>');
        }
        $('#serialResult').slideDown(250);
    });
});