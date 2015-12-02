function loadShippingForm() {
    var shipTo = $('#shipToNumber').val();
    var $div = $('#ajaxRequestResults');
    displayRequestMsg('requestProcess', '', $div);

    $.ajax({
        type: "POST",
        url: './ajaxProcess.php',
        dataType: 'json',
        data: {action: 'loadShippingForm', shipTo: shipTo},
        success: function (data) {
            $div.slideUp(250, function () {
                if (data.html) {
                    $div.html(data.html).slideDown(250, function () {
                        $('.partCheck').change(function () {
                            var $row = $(this).parent('td').parent('tr');
                            if ($(this).prop('checked')) {
                                onPartSelect($row);
                            } else {
                                onPartUnselect($row);
                            }
                        });
                        $('.partCheck').click(function (e) {
                            e.stopPropagation();
                        });
                        $('#createShipping').click(function () {
                            createShipping();
                        });
                        $('#partsPending').find('tbody').find('tr').click(function () {
                            if ($(this).find('input.partCheck').prop('checked')) {
                                $(this).find('input.partCheck').removeProp('checked');
                                onPartUnselect($(this));
                            } else {
                                $(this).find('input.partCheck').prop('checked', 'checked');
                                onPartSelect($(this));
                            }
                        });
                    });
                } else {
                    $div.html('<p class="error">Une erreur est survenue.</p>').slideDown(250);
                }
            });
        },
        error: function () {
            var msg = 'La demande n\'a pas pu aboutir pour une raison inconnue. Veuillez réessayer ultérieurement.';
            displayRequestMsg('error', msg, $div);
        }
    });
}
function createShipping() {
    if (!$('#shipToUsed').length) {
        alert('Erreur: numéro ship-to absent');
        return;
    }

    var shipInfos = {
        'length': $('#length').val(),
        'width': $('#width').val(),
        'height': $('#height').val(),
        'weight': $('#weight').val()
    };

    if (!$('tr.recapPartRow').length) {
        $('#partsListCheck').html('<p class="error">Veuillez sélectionner au moins un composant à expédier</p>');
        return;
    } else {
        $('#partsListCheck').html('');
    }

    if (!checkShippingform(shipInfos))
        return;

    var shipTo = $('#shipToUsed').val();
    var parts = [];

    $('#shippingInfos').find('tr.recapPartRow').each(function () {
        var id = parseInt($(this).attr('id').replace(/^recapPart_(\d+)$/, '$1'));
        var name = $(this).find('.partName').text();
        var ref = $(this).find('.partRef').text();
        var newRef = $(this).find('.partNewRef').text();
        var poNumber = $(this).find('.partPONumber').text();
        var serial = $(this).find('.partSerial').text();
        var sroNumber = $(this).find('.partSroNumber').text();
        var returnNbr = $(this).find('input.partReturnNbr').val();
        parts.push({
            id: id,
            name: name,
            ref: ref,
            newRef: newRef,
            poNumber: poNumber,
            serial: serial,
            sroNumber: sroNumber,
            returnNbr: returnNbr
        });
    });

    displayRequestMsg('requestProcess', '', $('#shippingRequestResponseContainer'));

    $.ajax({
        type: "POST",
        url: './ajaxProcess.php',
        dataType: 'json',
        data: {action: 'createShipping', shipTo: shipTo, parts: parts, shipInfos: shipInfos},
        success: function (data) {
            if (data.result.status) {
                $('#shipTo').slideUp(250);
                $('#ajaxRequestResults').slideUp(250, function () {
                    $(this).html(data.result.html).slideDown(250);
                });
            } else {
                $('#shippingRequestResponseContainer').html(data.result.html).slideDown();
            }
        },
        error: function () {
            var html = '<p class="error">La demande n\'a pas pu aboutir. Veuillez réessayer ultérieurement.</p>';
            $('#shippingRequestResponseContainer').html(html).slideDown();
        }
    });
}
function registerGsxShipment() {
    var $div = $('#gsxRequestResults');

    if (!$('#shipmentId').length) {
        displayRequestMsg('error', 'Erreur: ID de l\expédition absent.', $div);
        return;
    }

    var shipId = $('#shipmentId').val();
    var notes = $('#optionalNote').text();

    displayRequestMsg('requestProcess', '', $div);

    $.ajax({
        type: "POST",
        url: './ajaxProcess.php',
        dataType: 'json',
        data: {action: 'registerShipmentOnGsx', shipId: shipId, notes: notes},
        success: function (data) {
            if (data.result.ok) {
                var html = '<div class="tabBar container">';
                html += data.result.html + '</div>';
                $('#ajaxRequestResults').slideUp(250, function () {
                    $(this).html(html).slideDown(250, function() {
                        loadPartsReturnLabels(shipId);
                    });
                });
            } else {
                $div.slideUp(250, function () {
                    $(this).html(data.result.html).slideDown(250);
                });
            }
        },
        error: function () {
            displayRequestMsg('error', 'L\'opération n\'a pas pu aboutir en raison d\'une erreur inconnue.', $div);
        }
    });
}
function displayCurrentShipment(shipId) {
    $('#ajaxRequestResults').html('');

    if ($('#shipTo').length)
        $('#shipTo').slideUp();

    if ($('#currentShipmentList').length)
        $('#currentShipmentList').slideUp();

    displayRequestMsg('requestProcess', '', $('#ajaxRequestResults'));

    $.ajax({
        type: "POST",
        url: './ajaxProcess.php',
        dataType: 'json',
        data: {action: 'loadShipmentDetails', shipId: shipId},
        success: function (data) {
            if (data.ok) {

            } else {
                if ($('#shipTo').length)
                    $('#shipTo').slideDown(250);

                if ($('#currentShipmentList').length)
                    $('#currentShipmentList').slideDown(250);
            }

            $('#ajaxRequestResults').html(data.html);

            if ($('#builddoc_generatebutton').length) {
                $('#builddoc_generatebutton').click(function (e) {
                    e.preventDefault();

                    generateReturnPDF(shipId);
                });
            }
        },
        error: function () {
            if ($('#shipTo').length)
                $('#shipTo').slideDown(250);

            if ($('#currentShipmentList').length)
                $('#currentShipmentList').slideDown(250);
        }
    });
}
function loadPartsReturnLabels(shipId) {
    if ($('#partsLabelRequestInfos').length) {
        $('#partsLabelRequestInfos').slideUp(250, function () {
            var html = '<p class="requestProcess">Téléchargement en cours...</p>';
            $(this).html(html).slideDown(250);
        });
    }

    $.ajax({
        type: "POST",
        url: './ajaxProcess.php',
        dataType: 'json',
        data: {action: 'loadPartsReturnLabels', shipId: shipId},
        success: function (data) {
            if (data.ok) {
                displayCurrentShipment(shipId);
            } else if (data.html) {
                $('#partsLabelRequestInfos').slideUp(250, function () {
                    $(this).html(data.html).slideDown(250);
                });
            }
        },
        error: function () {
            $('#partsLabelRequestInfos').slideUp(250, function () {
                var html = '<p class="error">Une erreur inconnue est survenue</p>';
                $(this).html(html).slideDown(250);
            });
        }
    });
}
function generateReturnPDF(shipId) {
    $.ajax({
        type: "POST",
        url: './ajaxProcess.php',
        dataType: 'json',
        data: {action: 'generateReturnPDF', shipId: shipId},
        success: function (data) {
            if (data.ok) {
                displayCurrentShipment(shipId);
            } else {
                alert('Une erreur est survenue.');
            }
        },
        error: function () {
            alert('Une erreur est survenue.');
        }
    });
}
function onCaptionClick($caption) {
    if (!$caption.length)
        return;

    var $container = $caption.parent('.container');
    if (!$container.length)
        return;

    var $content = $container.find('.blocContent').first();
    if (!$content.length)
        return;

    if ($content.css('display') == 'none') {
        $content.stop().slideDown();
        $caption.find('span.arrow').attr('class', 'arrow upArrow');
    } else {
        $content.stop().slideUp();
        $caption.find('span.arrow').attr('class', 'arrow downArrow');
    }
}
function displayRequestMsg(type, msg, $div) {
    if ((type == 'requestProcess') && (msg === ''))
        msg = 'Requête en cours de traitement';

    if (!$div)
        $div = $('#requestResult');

    var html = '<p class="' + type + '">' + msg + '</p>';

    $div.html(html).hide().slideDown(250);
}
function onPartSelect($row) {
    var name = $row.find('td.partName').text();
    var ref = $row.find('td.partRef').text();
    var newRef = $row.find('td.partNewRef').text();
    var poNumber = $row.find('td.partPONumber').text();
    var serial = $row.find('td.partSerial').text();
    var repair = $row.find('td.partSroNumber').text();
    var returnNbr = $row.find('input.partReturnOrderNumber').val();
    var rowId = parseInt($row.attr('id').replace(/^part_(\d+)$/, '$1'));
    var html = '<tr id="recapPart_' + rowId + '" class="recapPartRow">';
    html += '<td class="partName">' + name + '</td>';
    html += '<td class="partRef">' + ref + '</td>';
    html += '<td class="partNewRef">' + newRef + '</td>';
    html += '<td class="partPONumber">' + poNumber + '</td>';
    html += '<td class="partSerial">' + serial + '</td>';
    html += '<td class="partSroNumber">' + repair + '</td>';
    html += '<td><input type="button" class="button" value="Retirer" onclick="removePart(' + rowId + ')"/></td>';
    html += '<input type="hidden" class="partReturnNbr" value="' + returnNbr + '"/>';
    html += '</tr>';
    $('#partsListRecapContainer').find('table').show().find('tbody').append(html);
}
function onPartUnselect($row) {
    var rowId = parseInt($row.attr('id').replace(/^part_(\d+)$/, '$1'));
    $('#recapPart_' + rowId).fadeOut(250, function () {
        $(this).remove();
    });
}
function removePart(rowId) {
    $('#part_' + rowId).find('.partCheck').removeProp('checked');
    $('#recapPart_' + rowId).fadeOut(250, function () {
        $(this).remove();
    });
}
function checkShippingform() {
    var check = true;
    $('input.shipInfo').each(function () {
        var val = $(this).val();
        var $div = $(this).parent('td').find('.inputCheckInfos');
        if (!val) {
            $div.html('<span class="notOk">Veuillez indiquer une valeur pour ce champ</span>');
            check = false;
        } else if (!/^[0-9]*$/.test(val)) {
            $div.html('<span class="notOk">Veuillez n\'indiquer qu\'un nombre entier dans ce champ</span>');
            check = false;
        } else
            $div.html('<span class="Ok"></span>');
    });
    return check;
}
function searchPartList() {
    var foundRows = [];
    var str = $('#partSearch').val().toLowerCase();
    var $trs = $('#partsPending').find('tbody').find('tr');
    if (str === '') {
        $trs.each(function () {
            $(this).show();
        });
        $('#')
        return;
    }

    $('#searchPartsReset').show();
    $('#partSearchResults').stop().css({
        'opacity': 1,
        'display': 'none'
    });

    $trs.each(function () {
        if ((str == $(this).find('td.partRef').text().toLowerCase()) ||
                (str == $(this).find('td.partNewRef').text().toLowerCase()) ||
                (str == $(this).find('td.partPONumber').text().toLowerCase()) || 
                (str == $(this).find('td.partSerial').text().toLowerCase())) {
            foundRows.push($(this));
        }
    });

    if (!foundRows.length) {
        var msg = 'Aucun composant trouvé';
        $('#partSearchResults').html('<p class="info">' + msg + '</p>').slideDown(250);
        return;
    }

    if (foundRows.length === 1) {
        if (!foundRows[0].find('input.partCheck').prop('checked')) {
            foundRows[0].find('input.partCheck').prop('checked', 'checked');
            onPartSelect(foundRows[0]);
            var msg = 'Le composant "' + foundRows[0].find('td.partName').text() + '" a été ajouté à l\'expédition';
            $('#partSearchResults').html('<p class="confirmation">' + msg + '</p>').slideDown();
        } else {
            var msg = 'Le composant "' + foundRows[0].find('td.partName').text() + '" a déjà été ajouté à l\'expédition';
            $('#partSearchResults').html('<p class="info">' + msg + '</p>').slideDown(250);
        }
        $trs.each(function () {
            $(this).show();
        });
        $('#partSearch').val('');
        $('#searchPartsReset').hide();
        return;
    }

    $trs.each(function () {
        $(this).hide();
    });

    for (id in foundRows) {
        foundRows[id].show();
    }

    var msg = foundRows.length + ' composants correspondent à votre recherche. Veuillez sélectionner ceux à expédier.';
    $('#partSearchResults').html('<p class="info">' + msg + '</p>').slideDown(250);
}
function resetPartSearch() {
    $('#partsPending').find('tbody').find('tr').each(function () {
        $(this).show();
    });
    $('#partSearch').val('');
    $('#partSearchResults').slideUp(250, function () {
        $(this).html('');
    });
    $('#searchPartsReset').hide();
}
function reinitPage() {
    $('#ajaxRequestResults').html('');
    $('#shipTo').show();
    $('#currentShipmentList').show();
}

$(document).ready(function () {
    $('#shipToSubmit').click(function () {
        loadShippingForm();
    });
    
    if ($('#shipmentToLoad').length) {
        var shipToLoadId = parseInt($('#shipmentToLoad').val());
        if (shipToLoadId)
            displayCurrentShipment(shipToLoadId);
    }
});