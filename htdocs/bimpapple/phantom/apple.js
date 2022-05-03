var page = require('webpage').create();
var system = require('system');

var debugScreen = false;
var url = 'https://login-partner-connect.apple.com/api/login';


setTimeout(function(){
    console.log('timeout script ');
    phantom.exit(0);
}, 180000);



if (system.args.length < 2 || system.args[1] == undefined || system.args[2] == undefined) {
    console.log('Erreur pas de log mdp');
    phantom.exit(0);
}
log = system.args[1];
paw = system.args[2];

console.log(paw+'r');
function pause(milliseconds) {
    var dt = new Date();
    while ((new Date()) - dt <= milliseconds) { /* Do nothing */
    }
}


var i = 0;
function debug(msg, screen) {
    console.log(msg);
    if (screen && debugScreen) {
        i++;
        console.log('Sreen : apple_' + i + '.png');
        page.render('apple_' + i + '.png');
    }
}




page.onConsoleMessage = function (msg) {
    debug('Client : ' + msg, true);
};







debug('avant chargement de ' + url, true);
page.open(url, function (status) {
    debug("Status: " + status);
    if (status === "success") {
        if (page.injectJs("injectme.js")) {
            console.log('ap inclusion');
            
            var idCode = getCode(0, function (codeSMS) {});
            console.log('ID ancien Code '+idCode);
            
            page.evaluate(function (log, paw) {
                setTimeout(function () {
                    console.log('Page loadé');
                    traiteLogPass(log, paw);
                }, 1500);
            }, log, paw);

            setTimeout(function () {
                debug('fin de traite log pass', true);
                var etape = page.evaluate(function () {
                    var etape = getEtape();
                    if (etape == 1) {
                        envoieCode();
                        etape = 2;
                    }
                    return etape;
                });

                debug('Passaga a l\'etape ' + etape);
                if (etape == 2) {
                    getCode(idCode, function (codeSMS) {
                        page.evaluate(function (codeSMS) {
                            traiteCode(codeSMS);
                        }, codeSMS);
                        setTimeout(function () {
                            traiteToken();
                        }, 6000);
                    });
                } else {
                    if (etape == 3) {
                        traiteToken();
                    } else
                        debug('Erreur, etape inconnue' + etape);
                }
            }, 10000);


        }



    }
});

function traiteToken() {
    var token = page.evaluate(function () {
        var resultField = document.querySelector('.partner--token');
        if (resultField != undefined)
            return resultField.value;
        else
            console.log("zone token introuvable");
    });
    debug('Token ok : ' + token, true);
    sendToken(token);
}

function ajax(paramsStr, onSuccess, onEchec) {
    if (window.XMLHttpRequest)    //  Objet standard
    {
        xhr = new XMLHttpRequest();     //  Firefox, Safari, ...
    } else if (window.ActiveXObject)      //  Internet Explorer
    {
        xhr = new ActiveXObject("Microsoft.XMLHTTP");
    }
    
    var urlAjax = 'https://erp2.bimp.fr/bimpinv01072020/bimpapple/phantom/phantom.php?' + paramsStr;
    
    xhr.open('GET', urlAjax, true);
    xhr.send(null);
    xhr.onreadystatechange = function ()
    {
        if (xhr.readyState == 4)
        {
            if (xhr.status == 200 && xhr.responseText != '') {
                onSuccess(xhr.responseText);
            } else {
                console.log('echec req status : '+xhr.status+' response '+xhr.responseText+ ' urlAjax '+urlAjax);
                onEchec();
            }
        }
    };
}

function getCode(id, onSuccess) {
    ajax('id=' + id, function (retour) {
        var tabResult = retour.split('|');
        if (tabResult[0] == '') {
            console.log('boucle ' + id);
            setTimeout(function () {
                getCode(tabResult[1], onSuccess);
            }, 3000);
        } else {
            console.log('code OK : ' + tabResult[0]);
            onSuccess(tabResult[0]);
        }
    }, function () {
        setTimeout(function () {
            getCode(id, onSuccess);
        }, 3000);
    });
    return id;
}

function sendToken(token) {
    if (token != '' && token != null) {
        console.log('token final ' + token);

        ajax('tok=' + token + '&log=' + log, function (response) {
            console.log('OK msg : ' + response);
            phantom.exit(0);
        }, function () {
            console.log('impossible d\'envoyé le token');
            phantom.exit(0);
        });
    } else {
        console.log('pas de token !!!');
        phantom.exit(0);
    }
}