/**
 * Globals variable
 */

/* global DOL_URL_ROOT */

var idCurrentEntrepot;
var idCurrentProd;
var cntEquip = 0;
var allSerialNumber = [];

/**
 * Contains equipment object
 *      serial
 *      id_entrepot
 *      id_product
 * @type Array
 */
var newEquipments = [];





/**
 * Ajax call
 */

function checkIsSerializable(id_product) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            id_product: id_product,
            action: 'checkIsSerializable'
        },
        error: function () {
            setMessage('alertMessage', 'Erreur serveur.', 'error');
        },
        success: function (out) {
            var outDec = JSON.parse(out);
            if (outDec.is_serialisable != false) {
                idCurrentProd = productid.value;
                $('#hereEquipment tr').last().remove();
                cntEquip--;
                addFieldEquipment();
            } else {
                setMessage('alertMessage', 'ce produit n\'est pas sérialisable.', 'error');
            }
        }
    });
}

function checkEquipment(serialNumber, currentEquipCnt) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            idCurrentEntrepot: idCurrentEntrepot,
            idCurrentProd: idCurrentProd,
            serialNumber: serialNumber,
            action: 'checkEquipment'
        },
        error: function () {
            setMessage('alertMessage', 'Erreur serveur.', 'error');
        },
        success: function (out) {
            var outDec = JSON.parse(out);
            var responseTd = 'tr#' + currentEquipCnt + ' td text.reponseServeur';
            if (allSerialNumber.includes(serialNumber)) {
                $(responseTd).text('Déjà scanné.');
                $(responseTd).css('color', 'red');
                $('tr#' + currentEquipCnt).attr('registered', false);
            } else if (outDec.code === 1) {
                $(responseTd).text('OK');
                $(responseTd).css('color', 'green');
                $('tr#' + currentEquipCnt).attr('registered', true);
                allSerialNumber.push(serialNumber);
                var newEquipment = {
                    serial: serialNumber,
                    id_entrepot: idCurrentEntrepot,
                    id_product: idCurrentProd
                };
                newEquipments.push(newEquipment);
            } else if (outDec.code === -1) {
                $(responseTd).text('Existe déjà.');
                $(responseTd).css('color', 'red');
                allSerialNumber.push(serialNumber);
                $('tr#' + currentEquipCnt).attr('registered', false);
            }
            $('input.custNote[cntEquip="' + currentEquipCnt + '"]').val(outDec.note);
        }
    });
}

function addEquipment() {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            newEquipments: newEquipments,
            action: 'addEquipment'
        },
        error: function () {
            setMessage('alertMessage', 'Erreur serveur.', 'error');
        },
        success: function (out) {
            var outDec = JSON.parse(out);
            if (outDec.errors.length !== 0) {
                var errors_msg = "Certains équipement n'ont pas pû être enregistrés<br>";
                for (i = 0; i < outDec.errors.length; i++) {
                    errors_msg += "Erreur numéro " + i + " " + outDec.errors[i] + "<br>";
                }
                setMessage('alertMessage', errors_msg, 'errors');
            } else {
                if (outDec.nbNewEquipment === 0)
                    setMessage('alertMessage', 'Ajouter des équipements avant de les enregistrer.', 'error');
                if (outDec.nbNewEquipment === 1)
                    setMessage('alertMessage', outDec.nbNewEquipment + " équipement a été enregistré avec succès", 'mesgs');
                else if (outDec.nbNewEquipment > 1)
                    setMessage('alertMessage', outDec.nbNewEquipment + " équipements ont été enregistrés avec succès", 'mesgs');
                setRegistered();
            }
            newEquipments = [];
        }
    });
}



/**
 * Ready
 */

$(document).ready(function () {

    $('#entrepot').select2();
    idCurrentEntrepot = $('#entrepot').val();
    idCurrentProd = productid.value;
    addFieldEquipment();
    initEvents();

});





/**
 * Functions
 */

function initEvents() {
    $('#search_productid').on('change', function () {
        checkIsSerializable(productid.value);
    });

    $('#entrepot').on('change', function () {
        idCurrentEntrepot = $(this).val();
    });

    $('#enregistrer').click(function () {
        addEquipment();
    });
}

/* Add a line in the table of equipments */
function addFieldEquipment() {

    $('#alertProd').empty();
    cntEquip++;

    var line = '<tr id="' + cntEquip + '" ><td>' + cntEquip + '</td>';      // Nombre de produits scannés
    line += '<td>' + productid.value + '</td><td>';      // Identifiant du produit
    line += '<input class="serialNumber" name="serial" cntEquip="' + cntEquip + '">'; // Numéro de série
    line += '</td><td><input class="custNote" type="text" name="note" cntEquip="' + cntEquip + '" disabled></td>';   // Note
    line += '<td><text class="reponseServeur"></text></td></tr>';   // Réponse serveur
    $(line).appendTo('#hereEquipment tbody');

    $('input.serialNumber[cntEquip="' + cntEquip + '"]').first().focus();

    $(".serialNumber").keyup(function (e) {
        if (e.keyCode === 13 && $(this).attr('valider') !== 'true') { // code for "Enter"
            validSN($(this));
        }
    });
    $(".serialNumber").focusout(function (e) {
        validSN($(this));
    });
}

function validSN(elem){
            var serialNumber = elem.val();
            if (serialNumber === '') {
                //setMessage('alertMessage', 'Veuillez saisir un numéro de série avant de valider.', 'error');
            } else if (productid.value !== '') {
                elem.attr('valider', 'true');
                elem.attr('disabled', 'true');
                //elem.blur(); // unfocus
                checkEquipment(serialNumber, elem.attr('cntEquip'));
                addFieldEquipment();
            } else {
                $('#alertProd').text('Veuillez sélectionner un produit.');
            }
    
}


function setRegistered() {
    $('#hereEquipment tr').each(function () {
        var registered = $(this).attr('registered');
        if (registered === 'true') {
            $(this).find(' td text.reponseServeur').text('Enregistré');
            $(this).css('background', '#bfbfbf');
            $(this).children().children().css('background', '#bfbfbf');
        } else if (registered) {
            $(this).css('background', '#bfbfbf');
            $(this).children().children().css('background', '#bfbfbf');
        }
    });
}



/**
 * 
 * @param String idElement id of the element to append the message in
 * @param String message the message you want to display
 * @param String type 'mesgs' => normal message (green) else => error message (red)
 */
function setMessage(idElement, message, type) {
    var backgroundColor;
    (type === 'mesgs') ? backgroundColor = '#25891c ' : backgroundColor = '#ff887a ';

    $('#' + idElement).hide().fadeIn(1000).append('<div id="alertdiv" style="background-color: ' + backgroundColor + ' ; opacity: 0.9 ; display: inline ; float: left; margin: 5px ; border-radius: 8px; padding: 10px;">' + message + '</div>');
    setTimeout(function () {
        $("#alertdiv").fadeOut(1000);
        setTimeout(function () {
            $("#alertdiv").remove();
        }, 1000);
    }, 10000);
}
