/**
 * Ajax call
 */
var DOMAINE = "http://http://127.0.0.1/bimp-erp/autreprojet";
//var DOMAINE = "http://sucsenscene.fr";
var URL_SERVER = DOMAINE+'/bimpcheckbillet/interface.php';
var URL_TICKETS = DOMAINE+'/bimpcheckbillet/img/tickets/';

/**
 * @param {Array} ids_prods
 */
function getField(ids_prods, products) {
    $.ajax({
        type: "POST",
        url: URL_SERVER,
        data: {
            ids_prods_extern: ids_prods,
            sender: 'prestashop',
            action: 'get_tariff_from_prestashop'
        },
        error: function () {
            alert('Erreur serveur 7543');
        },
        success: function (json) {
            try {
                var out = JSON.parse(json);
                if (out.errors.length !== 0) {
                    alert('Erreur serveur 9541');
                } else if (out.tariffs !== undefined) {
                    out.tariffs.forEach(function (tariff) {
                        var qty = 1;
                        products.forEach(function (product) {
                            if (product.id === tariff.id_prod_extern)
                                qty = product.qty;
                        });
                        printFields(tariff, qty);
                    });
                    var nb_input = $('.box').find('input').length;
                    if (nb_input !== 0) {
                        $('<button class="btn btn-primary" name="validate_extra">Valider tous les tickets</button>').insertAfter('div#main_container');
                        fillTickets(out.tariffs);
                    } else {
                        createTickets();
                    }
                } else
                    alert('Erreur serveur 4964');
            } catch (e) {
                alert('Erreur serveur 8541');
            }
        }
    });
}

function addIdEvent(tickets) {

    var ids_tariff = [];
    tickets.forEach(function (ticket) {
        ids_tariff.push(ticket.id_tariff);
    });

    $.ajax({
        type: "POST",
        url: URL_SERVER,
        data: {
            ids_tariff: ids_tariff,
            sender: 'prestashop',
            action: 'get_ids_events_by_ids_tariffs'
        },
        error: function () {
            alert('Erreur serveur 7743');
        },
        success: function (json) {
            try {
                var out = JSON.parse(json);
                if (out.errors.length !== 0) {
                    alert('Erreur serveur 9521');
                } else if (out.ids_events !== undefined) {
                    tickets.forEach(function (ticket) {
                        ticket.id_event = out.ids_events[ticket.id_tariff];
                    });
                    checkOrderAndCreateTickets(tickets);
                } else
                    alert('Erreur serveur 5364');
            } catch (e) {
                alert('Erreur serveur 4541');
            }
        }
    });
}

function checkOrderAndCreateTickets(tickets) {

    $.ajax({
        type: "POST",
        url: URL_SERVER,
        data: {
            id_order: tickets[0].id_order,
            tickets: tickets,
            sender: 'prestashop',
            action: 'check_order'
        },
        error: function () {
            alert('Erreur serveur 2581');
        },
        success: function (json) {
            try {
                var out = JSON.parse(json);
                if (out.errors.length !== 0) {
                    alert('Erreur serveur 6752');
                } else if (out.ids_inserted !== undefined) {
                    location.reload();
                } else
                    alert('Erreur serveur 2427');
            } catch (e) {
                alert('Erreur serveur 3741');
            }
        }
    });
}

function createTicket(id_event, id_tariff, price, first_name, last_name,
        extra_1, extra_2, extra_3, extra_4, extra_5, extra_6, id_order) {

    $.ajax({
        type: "POST",
        url: URL_SERVER,
        data: {
            id_event: id_event,
            id_tariff: id_tariff,
            price: price,
            first_name: first_name,
            last_name: last_name,
            extra_1: extra_1,
            extra_2: extra_2,
            extra_3: extra_3,
            extra_4: extra_4,
            extra_5: extra_5,
            extra_6: extra_6,
            id_order: id_order,
            sender: 'prestashop',
            action: 'create_ticket'
        },
        error: function () {
            alert('Erreur serveur 7592.');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                alert(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                alert("Le ticket a été créée.");
            } else {
                alert('Erreur serveur 3885.');
            }
        }
    });
}

function checkOrderStatus(id_order) {

    $.ajax({
        type: "POST",
        url: URL_SERVER,
        data: {
            id_order: id_order,
            sender: 'prestashop',
            action: 'check_order_status'
        },
        error: function () {
            alert('Erreur serveur 4568.');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                alert(out.errors, 'alertSubmit');
            } else if (out.status !== undefined) {
                if (parseInt(out.status) === 1) { // not filled
                    getField(id_prods, products);
                    createTickets();
                } else { // filled
                    createTickets();
                    $('div[name=tariff]').hide();
                    $('h5').remove();
                }
            } else {
                alert('Erreur serveur 7852.');
            }
        }
    });
}

function fillTickets(tariffs) {
    $.ajax({
        type: "POST",
        url: URL_SERVER,
        data: {
            id_order: id_order,
            sender: 'prestashop',
            action: 'get_filled_tickets'
        },
        error: function () {
            alert('Erreur serveur 6586');
        },
        success: function (json) {
            try {
                var out = JSON.parse(json);
                if (out.errors.length !== 0) {
                    alert('Erreur serveur 4642');
                } else {
                    fillPreviousTickets(tariffs, out.tickets);
                    var selector = $('div[name=ticket]').not('[previously_filled]');
                    selector.append('<button class="btn btn-primary" name="validate_one" style="margin-bottom: 10px;">Valider uniquement ce ticket</button><br/>');
                    //$('<button class="btn btn-primary" name="create_tickets" style="margin: 0px 20px 0px 20px;">Générer les tickets</button>').insertAfter('div[name=tariff]:last');
                    $('<img id="loading" src="img/loader.gif" style="display: none; width: 40px; height: 40px;">').appendTo('div[name=tariff]:last');
                    initEvents();
                    $('div[name=tariff]:first').prev().prev().before('<p>Merci de remplir les champs suivants pour obtenir vos tickets.<p>');
                }
            } catch (e) {
                alert('Erreur serveur 3741' + e);
            }
        }
    });
}

function createTickets() {

    $.ajax({
        type: "POST",
        url: URL_SERVER,
        data: {
            id_order: id_order,
            sender: 'prestashop',
            action: 'create_tickets'
        },
        beforeSend: function () {
            $('button[name=create_tickets]').hide();
            $('img#loading').css('display', 'block');
        },
        complete: function () {
            $('button[name=create_tickets]').show();
            $('img#loading').css('display', 'none');
        },
        error: function () {
            alert('Erreur serveur 5721');
        },
        success: function (json) {
            try {
                var out = JSON.parse(json);
                if (out.code_return != 0) {
                    if (out.errors.length !== 0) {
                        alert(out.errors);
                    } else if (true) {
                        $('a[name=downloadTickets]').parent().remove();
                        $('<div class="box"><a class="btn btn-primary" name="downloadTickets" href="' + URL_TICKETS + 'ticket' + $('input#ticket_identifier').val() + '.pdf' + '" download >Télécharger Billets</a></div>').insertAfter('div[name=tariff]:last');
                    }
                }
            } catch (e) {
                alert('Erreur serveur 3741');
            }
        }
    });
}

/**
 * Ready
 */

$(document).ready(function () {
    if (id_order > 0) {
        checkOrderStatus(id_order);
    }
});

/**
 * Events
 */

function initEvents() {
    $('button[name=validate_extra]').click(function () {
        $('button[name=validate_extra]').hide();
        $('button[name=validate_one]').hide();
        $('.p_error').remove();
        $('.error_submit').removeClass('error_submit');
        var stop = checkInput();
        if (stop === false) {
            var tickets = getTickets();
            addIdEvent(tickets);
        }
    });

    $('button[name=validate_one]').click(function () {
//        $('button[name=validate_extra]').hide();
//        $('button[name=validate_one]').hide();
        $('.p_error').remove();
        $('.error_submit').removeClass('error_submit');
        var stop = checkInput($(this).parent());
        if (stop === false) {
            var ticket = getTicket($(this).parent());
            addIdEvent(ticket);
        }
    });

    $('button[name=create_tickets]').click(function () {
        createTickets();
    });
}

/**
 * Functions
 */

function printFields(tariff, qty) {

    var html = '';
    var cnt_ticket = 0;

    for (var i = 1; i <= qty; i++) {
        if (i !== 1)
            html += '<br/>';
        html += '<h5>Ticket n°' + i + '</h5>';
        html += '<div name="ticket" cnt=' + cnt_ticket + ' id_tariff=' + tariff.id + '>';
        
        var length_befor_add_field = html.length;

        //names
        if (tariff.require_names == 1) {
            html += '<label><strong>Prénom ' + returnStar(tariff.require_names === 1) + '</strong></label><br/>';
            html += '<input class="form-control" name="first_name" require=' + tariff.require_names + ' maxlength=256 style="width: 300px"><br/>';
            html += '<label><strong>Nom ' + returnStar(tariff.require_names === 1) + '</strong></label><br/>';
            html += '<input class="form-control" name="last_name" require=' + tariff.require_names + ' maxlength=256 style="width: 300px"><br/>';
        }

        // extra
        for (var j = 1; j <= 6; j++) {
            var name_type = 'type_extra_' + j;
            var value_type = tariff[name_type];
            var name_name = 'name_extra_' + j;
            var value_name = tariff[name_name];
            var name_input = 'extra_' + j
            var key_require = 'require_extra_' + j;
            var is_required = tariff[key_require];
            if (value_type !== 0 && value_name !== undefined) {
                html += '<label><strong>' + value_name + ' ' + returnStar(is_required) + '</strong></label><br/>';
                if (value_type === 1) { // int
                    html += '<input class="form-control bfh-number" cnt=' + i + ' name="' + name_input + '" step="1" type="number" style="width: 120px" ' + (is_required ? 'require=1' : '') + ' /><br/>';
                } else if (value_type === 2) { // float
                    html += '<input class="form-control bfh-number" cnt=' + i + ' name="' + name_input + '" step="0.01" type="number" style="width: 120px" ' + (is_required ? 'require=1' : '') + ' /><br/>';
                } else { // string
                    html += '<input class="form-control" cnt=' + i + ' name="' + name_input + '" maxlength=256 style="width: 300px" ' + (is_required ? 'require=1' : '') + ' /><br/>';
                }
            }
        }
        
        if (length_befor_add_field === html.length) {
            html += '<p>Tous les champs de ce ticket sont remplis par défaut, il est donc directement téléchargeable.</p>';
//            $('div[name="ticket"][cnt="' + cnt_ticket + '"][id_tariff="' + tariff.id + '"]').css('background', 'white');
        }
        
        html += '</div>';
        cnt_ticket++;
    }
    $('div#field_place' + tariff.id_prod_extern).append(html);
}

function returnStar(condition) {
    if (condition)
        return '<text style="color: red;">*</text>';
    return '';
}

function checkInput(ticket_element) {
    ticket_element = ticket_element || 1;
    var stop = false;
    var selector;
    if (ticket_element !== 1)
        selector = ticket_element.find('input[require=1]');
    else
        selector = $('input[require=1]');

    selector.each(function () {
        if ($(this).val() === '' && !$(this).parent().attr('previously_filled')) {
            stop = true;
            addError($(this));
        }
    });
    return stop;
}

function addError(element, message) {
    message = message || 'Ce champ est requis';
    $(element).addClass('error_submit');
    $('<p class="p_error">' + message + '</p>').insertAfter(element);
}

function getTickets() {
    var tickets = [];
    var previously_filled;
    var nb_ticket = $('div[name=ticket]').length;
    for (var i = 0; i < nb_ticket; i++) {
        previously_filled = $('div[name=ticket]').eq(i).attr('previously_filled');
        if (previously_filled !== 'true') {
            tickets.push({
                id_tariff: parseInt($('div[name=ticket]').eq(i).attr('id_tariff')),
                id_product: $('div[name=ticket]').eq(i).parent().attr('id_product'),
                price: $('div[name=ticket]').eq(i).parent().attr('price'),
                first_name: $('div[name=ticket]').eq(i).find('input[name=first_name]').val() || '',
                last_name: $('div[name=ticket]').eq(i).find('input[name=last_name]').val() || '',
                extra_1: $('div[name=ticket]').eq(i).find('input[name=extra_1]').val() || '',
                extra_2: $('div[name=ticket]').eq(i).find('input[name=extra_2]').val() || '',
                extra_3: $('div[name=ticket]').eq(i).find('input[name=extra_3]').val() || '',
                extra_4: $('div[name=ticket]').eq(i).find('input[name=extra_4]').val() || '',
                extra_5: $('div[name=ticket]').eq(i).find('input[name=extra_5]').val() || '',
                extra_6: $('div[name=ticket]').eq(i).find('input[name=extra_6]').val() || '',
                id_order: parseInt($('p#order_id:first').text()),
            });
        }
    }
    return tickets;
}

function getTicket(ticket_element) {
    ticket_element = ticket_element || 1;
    var ticket = [];

    ticket.push({
        id_tariff: parseInt(ticket_element.attr('id_tariff')),
        id_product: ticket_element.parent().attr('id_product'),
        price: ticket_element.parent().attr('price'),
        first_name: ticket_element.find('input[name=first_name]').val() || '',
        last_name: ticket_element.find('input[name=last_name]').val() || '',
        extra_1: ticket_element.find('input[name=extra_1]').val() || '',
        extra_2: ticket_element.find('input[name=extra_2]').val() || '',
        extra_3: ticket_element.find('input[name=extra_3]').val() || '',
        extra_4: ticket_element.find('input[name=extra_4]').val() || '',
        extra_5: ticket_element.find('input[name=extra_5]').val() || '',
        extra_6: ticket_element.find('input[name=extra_6]').val() || '',
        id_order: parseInt($('p#order_id:first').text()),
    });

    return ticket;
}

function fillPreviousTickets(tariffs, tickets) {

    var id_prod_extern;
    var product_cnt = new Object();
    var i;
    var ticket_element;

    try {
        tickets.forEach(function (ticket) {

            // get tickets element
            tariffs.forEach(function (tariff) {
                if (tariff.id === parseInt(ticket.id_tariff))
                    id_prod_extern = tariff.id_prod_extern;
            });

            if (product_cnt[id_prod_extern] >= 0) {
                product_cnt[id_prod_extern]++;
            } else {
                product_cnt[id_prod_extern] = 0;
            }

            i = product_cnt[id_prod_extern];


            ticket_element = $('div[id_product="' + id_prod_extern + '"]').find('div[name=ticket]').eq(i);

            ticket_element.css('background', '#c2c2c2');
            ticket_element.attr('previously_filled', true);
            ticket_element.find('input').prop('disabled', true);
            ticket_element.find('input').removeAttr('require');
            ticket_element.find('label').find('text').remove();
            addText(ticket, ticket_element);
        });
    } catch (e) {
    }
}

function addText(ticket, ticket_element) {

    var key;

    ticket_element.find('input[name=first_name]').val(ticket.first_name);
    ticket_element.find('input[name=last_name]').val(ticket.last_name);

    for (var i = 1; i <= 6; i++) {
        key = 'extra_' + i;
        if (ticket[key] !== null) {
            ticket_element.find('input[name=' + key + ']').val(ticket[key]);
        }
    }
}

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
};
