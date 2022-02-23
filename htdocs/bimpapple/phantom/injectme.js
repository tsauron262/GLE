
function pause(milliseconds) {
    var dt = new Date();
    while ((new Date()) - dt <= milliseconds) { /* Do nothing */
    }
}


function getIframeBody() {
    var i = document.getElementsByTagName('iframe');
    return i[0].contentWindow.document.body;
}


function traiteLogPass(log, paw) {
    var body = getIframeBody();
    traiteField('#account_name_text_field', log, body);
    setTimeout(function () {
        clickButton('#sign-in', body);
        console.log('aplogin');
        setTimeout(function () {
            console.log('avmdp');
            traiteField('#password_text_field', paw, body);
            setTimeout(function () {
                console.log('Ap mdp');
                clickButton('#sign-in', body);
            }, 1500);
        }, 1500);
    }, 1500);
}

function getEtape() {//1 envoie du code  //2 besoin du code  //3 token // envoie code plusieurs num
    var body = getIframeBody();
    var resultField = body.querySelector('.si-device-row');
    if (resultField != null) {
        console.log('besoin d\'envoie du code');
        return 1;
    }
    
    var resultField = body.querySelector('.si-phone-row');
    if (resultField != null) {
        console.log('besoin d\'envoie du code (plusieurs num)');
        return 1;
    }
    
    
    var i = document.getElementsByTagName('iframe');
    if (i != null && i[0] != undefined) {
        console.log('besoin de code');
        return 2;
    }
    console.log('pas besoin de code');
    return 3;
}

function envoieCode() {
    clickButton('.si-device-row', getIframeBody());
    focusButton('.si-phone-row', getIframeBody());
    clickButton('.si-phone-row', getIframeBody());
}

function traiteField(name, value, body) {
    var resultField = body.querySelector(name);
    if (resultField != null) {
        resultField.value = value;
        const event2 = new Event('input');
        resultField.dispatchEvent(event2);
    }
}

function clickButton(name, body) {
    event(name, body, 'click');
}
function focusButton(name, body) {
    event(name, body, 'focus');
}

function event(name, body, action){
    var button = body.querySelector(name);
    if (button != null) {
        const event = new Event(action, {"bubbles": true, "cancelable": false, "composed": true});
        button.dispatchEvent(event);
    }
    else
        console.log('Erreur : ' + name + ' introuvable');
}

function traiteCode(codeSMS) {
    var body = getIframeBody();
    if (body != null && body != undefined) {
        if (body.querySelector('#char0') != undefined) {
            console.log('Code : ' + codeSMS);
            for (i = 0; i < codeSMS.length; i++) {
                var resultField = body.querySelector('#char' + i);
                if (resultField != null){
                    resultField.value = codeSMS.substr(i, 1);
                    event = new Event('input', {"bubbles": true, "cancelable": false, "composed": true});
                    resultField.dispatchEvent(event);
                }
                else
                    console.log('Erreur, champ '+'#char' + i+' introuvable');
            }
            console.log('Fin de traitement du code');
        } else
            console.log("Zone code introuvable");
    }
}

