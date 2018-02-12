/**
 * Globals variable
 */

/* global DOL_URL_ROOT */


/**
 * Ajax call
 */

/**
 * Insert modifications in database
 * 
 * @param {Object} products
 * @returns {undefined}
 */
function modifyOrder(products, isTotal) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            action: 'modifyOrder',
            entrepotId: $('#entrepot').val(),
            isTotal: isTotal,
            products: products,
            orderId: getUrlParameter('id')
        },
        error: function () {
            console.log("Erreur PHP");
        },
        success: function (out) {
            setMessage('alertEnregistrer', products.length + ' Groupes de produits on été rajouté avec succès.', 'mesgs');
        }
    });
}



/**
 * Ready
 */

$(document).ready(function () {
    $('#entrepot').select2();

    initEvents();

});

/**
 * Functions
 */

function initEvents() {
    $('.modify').click(modifyQuantity);

    $('input[name=checkAll]').change(function () {
        var isChecked = $(this).prop('checked');
        $('table#productTable [name=stocker]').prop('checked', isChecked);
    });

    $('input[name=stocker]').change(changeCheckbox);

    $('#enregistrer').click(function () {
        $('p[name=confTransfert]').text('Etes-vous sur de vouloir mettre en stock ces produits ?');
        $('div [name=confirmEnregistrer]').show();
    });

    $('input#okEnregistrer').click(function () {
        saveProducts();
        $('div [name=confirmEnregistrer]').hide();
    });

    $('input#noEnregistrer').click(function () {
        $('div [name=confirmEnregistrer]').hide();
    });
}

function changeCheckbox() {
    if (!$(this).prop('checked'))
        $('input[name=checkAll]').prop('checked', false);
    else {
        var allchecked = true;
        $('input[name=stocker]').each(function () {
            if (!$(this).prop('checked'))
                allchecked = false;
        })
        if (allchecked)
            $('input[name=checkAll]').prop('checked', true);
    }
}

function modifyQuantity() {
    var idLine = $(this).parent().parent().attr('id');
    var selectoTr = 'table#productTable tr#' + idLine;
    var newQty = parseInt($(selectoTr + ' td input[name=modify]').val());
    $(selectoTr + ' td[name=qty]').text(newQty);

}

function saveProducts() {
    var products = [];

    $('table#productTable tr').each(function () {
        if ($(this).find('td input[name=stocker]').prop('checked')) { // is the kine checked ?
            var newProd = {
                id_prod: parseInt($(this).find('td[name=productId]').text()),
                qty: parseInt($(this).find('td[name=qty]').text())
            };
            products.push(newProd);
        }
    });

    if (products.length === 0) {
        setMessage('alertEnregistrer', 'Veuillez cocher des lignes pour effectuer la mise en stock.', 'error');
    } else {
        console.log(products);
        var isTotal = checkIfStatutIsTotal();
        modifyOrder(products, isTotal);
    }
}

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
    (type === 'mesgs') ? backgroundColor = '#25891c ' : backgroundColor = '#ff887a ';

    $('#' + idElement).append('<div id="alertdiv" style="background-color: ' + backgroundColor + ' ; opacity: 0.9 ; display: inline ; float: left; margin: 5px ; border-radius: 8px; padding: 10px;">' + message + '</div>');
    setTimeout(function () {
        $("#alertdiv").fadeOut(1000);
        setTimeout(function () {
            $("#alertdiv").remove();
        }, 1000);
    }, 10000);
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
;