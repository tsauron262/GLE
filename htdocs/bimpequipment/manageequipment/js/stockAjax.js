/**
 * Globals variable
 */

/* global DOL_URL_ROOT */

var entrepotId;
var orderId;
var cntProduct = 0;

/**
 * Ajax call
 */

/**
 * Insert modifications in database
 * 
 * @param {Object} products
 * @param {Bool} isTotal is the order total or partial ?
 * @returns {undefined}
 */
function modifyOrder(products, isTotal) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            action: 'modifyOrder',
            entrepotId: entrepotId,
            isTotal: isTotal,
            products: products,
            orderId: orderId
        },
        error: function () {
            setMessage('alertEnregistrer', 'Erreur interne 1534.', 'error');
        },
        success: function (out) {
            var outP = JSON.parse(out);
            if (outP.errors.length !== 0) {
                printErrors(outP.errors, 'alertEnregistrer');
            } else if (1 < products.length) {
                localStorage.setItem('enregistrementOK', products.length + ' Groupes de produits on été rajouté avec succès.')
                window.location.reload();
            } else if (1 === products.length) {
                localStorage.setItem('enregistrementOK', 'Un groupe de produit a été rajouté avec succès.')
                window.location.reload();
            }
        }
    });
}

function getRemainingLignes() {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            action: 'getRemainingLignes',
            entrepotId: entrepotId,
            orderId: orderId
        },
        error: function () {
            setMessage('alertEnregistrer', 'Erreur interne 1535.', 'error');
        },
        success: function (out) {
            var outP = JSON.parse(out);
            if (outP.errors.length !== 0) {
                printErrors(outP.errors, 'alertEnregistrer');
            } else {
                var lignes = outP.lignes;   // lignes = 1 object containing multiple object ligne
                var tabProd = getTabProduct(lignes);
                var tabEquipment = getTabEquipment(lignes);
                tabProd.forEach(function (prod) {
                    addProduct(prod);
                });
                tabEquipment.forEach(function (equipment) {
                    for (var j = 0; j < equipment.remainingQty; j++)
                        addEquipment(equipment);
                });

                initEvents();
            }
        }
    });
}

function getTabProduct(lignes) {
    var tabProduct = [];
    $.each(lignes, function (index, ligne) {
        if (!ligne.isEquipment)
            tabProduct.push(ligne);
    });
    return tabProduct;
}

function getTabEquipment(lignes) {
    var tabEquipment = [];
    $.each(lignes, function (index, ligne) {
        if (ligne.isEquipment)
            tabEquipment.push(ligne);
    });
    return tabEquipment;
}

/**
 * Ready
 */

$(document).ready(function () {
    if (localStorage.getItem('enregistrementOK'))
    {
        alert(localStorage.getItem('enregistrementOK'));
        localStorage.clear();
    }
    $('#entrepot').select2({placeholder: 'Rechercher ...'});
    orderId = getUrlParameter('id');
    getRemainingLignes();
});
/**
 * Functions
 */

function addProduct(ligne) {

    cntProduct++;
    var line = '<tr id="' + cntProduct + '">';
    line += '<td name="cnt">' + cntProduct + '</td>';    // cnt ligne
    line += '<td name="productId">' + ligne.prodId + '</td>';    // id
    line += '<td>' + ligne.refurl + '</td>';    // refUrl
    line += '<td></td>';    // num série
    line += '<td>' + ligne.label + '</td>';    // label
    line += '<td>' + ligne.remainingQty + '</td>';
    line += '<td name="qty">0</td>';
    line += '<td><input name="modify" type="number" class="custInput" min=0 value=' + parseInt(ligne.remainingQty) + ' style="width: 50px"> <img src="css/ok.ico" class="clickable modify" style="margin-bottom:3px"></td>';
    line += '<td>' + ligne.price_unity + ' €</td>';
    line += '<td style="text-align:center"><input type="checkbox" name="stocker"></td></tr>';
    $(line).appendTo('#productTable tbody');
}

function addEquipment(ligne) {

    cntProduct++;
    var line = '<tr id="' + cntProduct + '">';
    line += '<td name="cnt">' + cntProduct + '</td>';    // cnt ligne
    line += '<td name="productId">' + ligne.prodId + '</td>';    // id
    line += '<td>' + ligne.refurl + '</td>';    // refUrl
    line += '<td><input name="serial" class="custInput"></td>';    // num série
    line += '<td>' + ligne.label + '</td>';    // label
    line += '<td></td>';
    line += '<td></td>';
    line += '<td></td>';
    line += '<td>' + ligne.price_unity + ' €</td>';
    line += '<td style="text-align:center"><input type="checkbox" name="stocker"></td></tr>';
    $(line).appendTo('#productTable tbody');
}


function initEvents() {

    $('.modify').click(modifyQuantity);

    $('#entrepot').change(function () {
        entrepotId = $('#entrepot').val();
        $('input[name=serial]').first().focus();
    });

    $('input[name=checkAll]').change(function () {
        var isChecked = $(this).prop('checked');
        $('table#productTable [name=stocker]').prop('checked', isChecked);
    });

    $('input[name=stocker]').change(changeCheckbox);

    $('#enregistrer').click(function () {
        if (!entrepotId) {
            setMessage('alertEnregistrer', 'Veuillez sélectionner un entrepôt avant d\'enregistrer.', 'error');
        } else {
            $('p[name=confTransfert]').text('Etes-vous sur de vouloir mettre en stock ces produits ?');
            $('div [name=confirmEnregistrer]').show();
            location.hash = '#okEnregistrer';
        }
    });

    $('input#okEnregistrer').click(function () {
        saveProducts();
        $('div [name=confirmEnregistrer]').hide();
    });

    $('input#noEnregistrer').click(function () {
        $('div [name=confirmEnregistrer]').hide();
    });

    $('input[name=serial]').on('keyup', function (e) {
        if (e.keyCode === 13) { // code for "Enter"
            validateSerial($(this));
            e.preventDefault();
        }
    });

    $('input[name=serial]').on('keydown', function (e) {
        if (e.keyCode === 9) { // code for "Tab"
            validateSerial($(this));
            e.preventDefault();
        }
    });
}

function validateSerial(element) {
    if (element.parent().parent().find('input[name=serial]').val() !== '') {
        element.parent().parent().next().find('input[name=serial]').focus();
        element.parent().parent().find('input[name=stocker]').prop('checked', true);
        document.querySelector("#bipAudio2").play();
    }
}

function changeCheckbox() {
    if (!$(this).prop('checked'))
        $('input[name=checkAll]').prop('checked', false);
    else {
        var allchecked = true;
        $('input[name=stocker]').each(function () {
            if (!$(this).prop('checked'))
                allchecked = false;
        });
        if (allchecked)
            $('input[name=checkAll]').prop('checked', true);
    }
}

function modifyQuantity() {
    var idLine = $(this).parent().parent().attr('id');
    var selectoTr = 'table#productTable tr#' + idLine;
    var newQty = parseInt($(selectoTr + ' td input[name=modify]').val());
    $(selectoTr + ' td[name=qty]').text(newQty);
    document.querySelector("#bipAudio").play();
    if (newQty === 0)
        $(selectoTr + ' td input[name=stocker]').prop('checked', false);
    else
        $(selectoTr + ' td input[name=stocker]').prop('checked', true);
}

/* Create product object for each line, then call ajax to save those products */
function saveProducts() {
    var products = [];

    $('table#productTable tr').each(function () {
        if ($(this).find('td input[name=stocker]').prop('checked')) { // is the line checked ?
            if ($(this).find('td input[name=modify]').length) {
                var newProd = {
                    id_prod: parseInt($(this).find('td[name=productId]').text()),
                    qty: parseInt($(this).find('td[name=qty]').text())
                };
                products.push(newProd);
            } else {
                var newEquipment = {
                    id_prod: parseInt($(this).find('td[name=productId]').text()),
                    serial: $(this).find('input[name=serial]').val()
                };
                products.push(newEquipment);
            }
        }
    });

    var stopProcess = checkEntries(products);

    if (stopProcess)
        return;

    if (products.length === 0) {
        setMessage('alertEnregistrer', 'Veuillez cocher des lignes pour effectuer la mise en stock.', 'error');
    } else {
        var isTotal = checkIfStatutIsTotal();
        modifyOrder(products, isTotal);
    }
}

function checkEntries(products) {
    var fieldName = [];
    fieldName['qty'] = 'quantité';
    fieldName['serial'] = 'numéro de série';
    var stopProcess = false;
    var cntProduct = 0;
    products.forEach(function (prod) {
        ++cntProduct;
        for (var field in prod) {
            if (prod[field] === '') {
                stopProcess = true;
                setMessage('alertEnregistrer', 'Une ligne a été cochée sans renseigner le champ "' + fieldName[field] + '".', 'error');
                $('table#productTable tr#' + cntProduct + '> td').css('border-top', 'solid 1px red');
                $('table#productTable tr#' + cntProduct + '> td').css('border-bottom', 'solid 1px red');
                $('table#productTable tr#' + cntProduct + '> td:first').css('border-left', 'solid 1px red');
                $('table#productTable tr#' + cntProduct + '> td:last').css('border-right', 'solid 1px red');
//                $('table#productTable tr#' + cntProduct+' td[name=cnt]').css('opacity', '0.5');
            }
        }
    });
    return stopProcess;
}

/* Check is every lines of the order is fullfilled */
function checkIfStatutIsTotal() {
    var stop = false;
    // search if ther is at least 1 line which is unchecked
    $('table#productTable tr > td > input[name=stocker]').each(function () {
        if (!$(this).prop('checked')) {
            stop = true;
        }
    });
    if (stop)   // at least one checkbox is unchecked
        return false;

    // search if the amount is at least equal to the minimum defined in the order
    $('table#productTable tr td[name=qty]').each(function () {
        if (parseInt($(this).text()) < parseInt($(this).attr('initValue'))) {
            stop = true;
        }
    });

    if (stop)   // at least 1 amount is less than the one defined in the order
        return false;
    return true;
}


/**
 * 
 * @param {String} idElement id of the element to append the message in
 * @param {String} message the message you want to displ  ay
 * @param {String} type 'mesgs' => normal message (green) else => error message (red)
 */
function setMessage(idElement, message, type) {
    var backgroundColor;
    if (type === 'mesgs') {
        backgroundColor = '#25891c ';
    } else {
        document.querySelector("#bipError").play();
        backgroundColor = '#ff887a ';
    }

    $('#' + idElement).append('<div id="alertdiv" style="color:black ; background-color: ' + backgroundColor + ' ; opacity: 0.9 ; display: inline ; float: left; margin: 5px ; border-radius: 8px; padding: 10px;">' + message + '</div>');
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


/* Get the parameter sParam */
var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;
    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
}