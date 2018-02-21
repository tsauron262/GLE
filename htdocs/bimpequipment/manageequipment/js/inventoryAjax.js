/**
 * Globals variable
 */

/* global DOL_URL_ROOT, productid */

var idEntrepot;
var cntProduct = 0;

var products = [];


/**
 * Ajax call
 */

function getStockAndSerial(ref) {
    var ligneExists = setScanned(ref);
    if (ligneExists !== 'tr inexistant') {  // if it is a serial
        return;
    }
    var qtyToAdd = parseInt($('input#qty').val());
    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            ref: ref,
            idEntrepot: idEntrepot,
            action: 'getStockAndSerial'
        },
        error: function () {
            setMessage('alertEnregistrer', 'Erreur serveur 5916.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertEnregistrer');
            } else if (out.equipments.length !== 0) {
                out.equipments.forEach(function (item) {
                    addFieldEquipment(out.id, out.ref, item, out.label);
                });
                if (out.serial !== '')
                    setScanned(out.serial);
            } else if (out.stocks.length !== 0) {
                if ($('table#productTable tr#' + out.id).length === 0) {
                    if (0 < qtyToAdd)
                        addFieldProduct(out.id, out.stocks, qtyToAdd, out.label, out.ref);
                    else
                        setMessage('alertEnregistrer', "Il faut ajouter des produits avant d'en enlever.", 'error');
                } else {
                    addQuantity(out.id, qtyToAdd);
                }
            } else {
                setMessage('alertEnregistrer', "Cette entrée n'est ni une référence, ni un code barre ni un numéro de série.", 'error');
            }

        }
    });
}

function addProduct(prodId) {
    var qtyToAdd = parseInt($('input#qty').val());
    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            prodId: prodId,
            idEntrepot: idEntrepot,
            action: 'getStock'
        },
        error: function () {
            setMessage('alertEnregistrer', 'Erreur serveur 5456.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertEnregistrer');
            } else if (out.stocks.length !== 0) {
                addFieldProduct(out.id, out.stocks, qtyToAdd, out.label, out.ref);
            } else {
                setMessage('alertEnregistrer', "Cet identifiant n'est pas renseigné dans la base de donnée.", 'error');
            }

        }
    });
}

/**
 * Ready
 */

$(document).ready(function () {

    $('#entrepot').select2({placeholder: 'Rechercher ...'});
    initEvents();
    initIE('input[name=refScan]', 'getStockAndSerial', 'input#qty');

});



/**
 * Functions
 */

function initEvents() {
    $('#addProduct').on('click', function () {
        var productId = productid.value;
        if (productId !== '') {
            if ($('#productTable tr#' + productId).length !== 0) {
                addQuantity(productId, parseInt($('input#qty').val()));
            } else {
                addProduct(productId);
            }
        } else {
            setMessage('alertProd', 'Veuillez sélectionner un produit pour l\'ajouter au tableau.', 'error');
        }
    });

    $('#entrepot').on('change', function () {
        if ($(this).prop('preventOnClickEvent')) {
            $(this).prop('preventOnClickEvent', false);
        } else if (idEntrepot === undefined) {
            idEntrepot = $(this).val();
            $('#allTheFiche').css('visibility', 'visible');
            $('#allTheFiche').addClass('fade-in');
        } else if (products.length !== 0) {
            var confirmed = confirm('Vous etes sur le point d\'annuler tous les enregistrements, continuer ?');
            if (confirmed) {
                idEntrepot = $(this).val();
                $('table#productTable tr[id]').remove();
                cntProduct = 0;
            } else {
                $('#entrepot').prop('preventOnClickEvent', true);
                $('#entrepot').select2('val', idEntrepot, true);
            }
        } else {
            idEntrepot = $(this).val();
        }
    });

//    $('#enregistrer').click(function () {
//        if (cntProduct !== 0 && confirm('Etes-vous sur de vouloir transférer ' + cntProduct + ' groupes de produit ?')) {
//            saveproducts(cntProduct);
//            $('table#productTable tr[id]').remove();
//            products = [];
//            cntProduct = 0;
//        } else {
//            setMessage('alertEnregistrer', 'Vous devez ajouter des produits avant de les transférer.', 'error');
//        }
//    });
}

/* Add a line in the table of equipments */
function addFieldEquipment(productId, refUrl, serial, label) {

    cntProduct++;
    productId = parseInt(productId);
    var line = '<tr id="' + serial + '">';
    line += '<td name="cnt">' + cntProduct + '</td>';    // cnt ligne
    line += '<td>' + productId + '</td>';    // id
    line += '<td>' + refUrl + '</td>';    // refUrl
    line += '<td>' + serial + '</td>';    // num série
    line += '<td>' + label + '</td>';    // label
    line += '<td></td>';   // Quantité Totale
    line += '<td></td>';   // Quantité Manquante
    line += '<td></td>';   // Quantité Indiqué
    line += '<td></td>';   // Modifier
    line += '<td style="text-align:center"><img src="css/moins.ico" class="clickable remove "></td></tr>'; // supprimer
    $(line).appendTo('#productTable tbody');
    initRemoveLine(serial);

    document.querySelector("#bipAudio2").play();
}

/* Add a line in the table of product */
function addFieldProduct(productId, qtyTotale, qtyGiven, label, refUrl) {

    cntProduct++;
    qtyTotale = parseInt(qtyTotale);
    qtyGiven = parseInt(qtyGiven);

    var qtyMissing = qtyTotale - qtyGiven;
    var line = '<tr id=' + productId + '>';
    line += '<td name="cnt">' + cntProduct + '</td>';    // cnt ligne
    line += '<td>' + productId + '</td>';
    line += '<td>' + refUrl + '</td>';
    line += '<td></td>';
    line += '<td>' + label + '</td>';
    line += '<td name="qtyTotale"    >' + qtyTotale + '</td>';
    line += '<td name="qtyMissing"    >' + qtyMissing + '</td>';
    line += '<td name="qtyGiven">' + qtyGiven + '</td>';
    line += '<td><input name="modify" type="number" class="custInput" style="width: 60px" value=1 > <img name="modify" src="css/ok.ico" class="clickable"></td>';
    line += '<td style="text-align:center"><img src="css/moins.ico" class="clickable remove"></td></tr>';
    $(line).appendTo('#productTable tbody');

    initRemoveLine(productId);
    adaptColor('table#productTable tr#' + productId);

    $('table#productTable tr#' + productId + ' img[name=modify]').click(modifyQuantity);

    document.querySelector("#bipAudio").play();
}

function initRemoveLine(idTr) {
    $('table#productTable tr#' + idTr + ' td img.remove').click(function () {
        $(this).parent().parent().remove();
        cntProduct = 1;
        $('table#productTable td[name=cnt]').each(function () {
            $(this).text(cntProduct);
            cntProduct++;
        })
        cntProduct--;
    });
}

function modifyQuantity() {
    var selectoTr = 'table#productTable tr#' + $(this).parent().parent().attr('id');
    var modifyValue = parseInt($(selectoTr + ' td input[name=modify]').val());
    var total = parseInt($(selectoTr + ' td[name=qtyTotale]').text());

    var newMissing = total - modifyValue;
    $(selectoTr + ' td[name=qtyMissing]').text(newMissing);
    $(selectoTr + ' td[name=qtyGiven]').text(modifyValue);

    adaptColor(selectoTr);
}


function addQuantity(idProduct, qty) {
    var selectorTr = 'table#productTable tr#' + idProduct;
    var initMissing = parseInt($(selectorTr + ' td[name=qtyMissing]').text());
    var initGiven = parseInt($(selectorTr + ' td[name=qtyGiven]').text());

    var newMissing = initMissing - qty;
    var newGiven = initGiven + qty;
    $(selectorTr + ' td[name=qtyMissing]').text(newMissing);
    $(selectorTr + ' td[name=qtyGiven]').text(newGiven);

    adaptColor(selectorTr);

    document.querySelector("#bipAudio").play();
}

/* for equipment */
function setScanned(serial) {
    if ($('#productTable tr#' + serial).length === 0) {
        return 'tr inexistant';
    }
    if ($('#productTable tr#' + serial).attr('scanned')) {
        setMessage('alertEnregistrer', 'Vous avez déjà scanné cet équipement.', 'error');
        return false;
    } else {
        $('#productTable tr#' + serial).css('background', '#bfbfbf');
        $('#productTable tr#' + serial).attr('scanned', true);
        return true;
    }
}

/* for products */
function adaptColor(selectorTr) {
    var color;
    var missing = parseInt($(selectorTr + ' td[name=qtyMissing]').text());
    if (missing <= 0) {
        color = '#bfbfbf';
        $(selectorTr).attr('scanned', true);
    } else {
        color = '#ffffff';
        $(selectorTr).attr('scanned', false);
    }

    $(selectorTr).css('background', color);
}


/**
 * 
 * @param String idElement id of the element to append the message in
 * @param String message the message you want to displ  ay
 * @param String type 'mesgs' => normal message (green) else => error message (red)
 */
function setMessage(idElement, message, type) {
    var backgroundColor;
    (type === 'mesgs') ? backgroundColor = '#25891c ' : backgroundColor = '#ff887a ';
    if (type === "error")
        document.querySelector("#bipError").play();

    $('#' + idElement).append('<div id="alertdiv" style="background-color: ' + backgroundColor + ' ; opacity: 0.9 ; display: inline ; float: left; margin: 5px ; border-radius: 8px; padding: 10px; color:black">' + message + '</div>');
    setTimeout(function () {
        $("#alertdiv").fadeOut(1000);
        setTimeout(function () {
            $("#alertdiv").remove();
        }, 1000);
    }, 10000);
}

/**
 * Print and array of string in
 * @param {Array} errors
 * @param {String} idAlertPlaceHolder the id of the element where you want to
 *  set the message in.
 * @dependent setMessage()
 */
function printErrors(errors, idAlertPlaceHolder) {
    for (var i = 0; i < errors.length && i < 100; i++) {
        setMessage(idAlertPlaceHolder, errors[i], 'error');
    }
}