function onFacturePaymentChange($container) {
    var is_rbt = parseInt($container.data('is_rbt'));

    var total_to_return = 0;
    var total_payments = 0;
    var total_to_pay = $container.find('[name="total_to_pay"]').val();
    var total_avoirs = 0;
    var rest_to_pay = 0;

    if (total_to_pay === '') {
        total_to_pay = 0;
    } else {
        total_to_pay = parseFloat(total_to_pay);
    }

    rest_to_pay = total_to_pay;

    $container.find('input.facture_payment_input').each(function () {
        var line_total_paid = 0;
        var line_to_return = 0;

        var value = $(this).val();
        if (value === '') {
            value = 0;
            $(this).val(value);
        }
        value = parseFloat(value);

        var avoirs = parseFloat($(this).data('avoirs'));
        if (isNaN(avoirs)) {
            avoirs = 0;
        }

        line_total_paid = value + avoirs;

        var to_pay = parseFloat($(this).data('to_pay'));
        if (isNaN(to_pay)) {
            to_pay = 0;
        }

        if (to_pay >= 0) {
            if (line_total_paid > to_pay) {
                if (!is_rbt) {
                    line_to_return = (line_total_paid - to_pay);
                } else {
                    value = to_pay - avoirs;
                    $(this).val(value);
                    line_total_paid = value + avoirs;
                }
            }
        } else {
            if (line_total_paid < to_pay) {
                value = to_pay - avoirs;
                $(this).val(value);
                line_total_paid = value + avoirs;
            }
        }

        total_payments += value;
        total_avoirs += avoirs;
        total_to_return += line_to_return;
        rest_to_pay -= (line_total_paid - line_to_return);
    });

    displayMoneyValue(total_payments, $container.find('span.total_payments'));
    displayMoneyValue(rest_to_pay, $container.find('span.rest_to_pay'));
    displayMoneyValue(total_to_return, $container.find('span.to_return'));

    $container.find('[name="total_paid_amount"]').val(total_payments);

    if (total_to_return > 0) {
        $container.find('.to_return_option_container').stop().slideDown(250);
    } else {
        $container.find('.to_return_option_container').stop().slideUp(250);
    }
}

function onClientTotalPaidAmountChange($container) {
    var total_paid = $container.find('[name="total_paid_amount"]').val();
    if (total_paid === '') {
        total_paid = 0;
    } else {
        total_paid = parseFloat(total_paid);
    }

    var total_to_pay = $container.find('[name="total_to_pay"]').val();
    if (total_to_pay === '') {
        total_to_pay = 0;
    } else {
        total_to_pay = parseFloat(total_to_pay);
    }

    var rest = total_paid;
    var rest_to_pay = total_to_pay;
    var $lastInput = null;

    $container.find('input.facture_payment_input').each(function () {
        var avoirs = parseFloat($(this).data('avoirs'));
        if (isNaN(avoirs)) {
            avoirs = 0;
        }
        var to_pay = parseFloat($(this).data('to_pay'));
        if (isNaN(to_pay)) {
            to_pay = 0;
        }
        to_pay -= avoirs;
        if (to_pay >= 0) {
            if (to_pay > rest) {
                to_pay = Math.round10(rest, -2);
            }
        } else {
            if (to_pay < rest) {
                to_pay = Math.round10(rest, -2);
            }
        }
        $(this).val(to_pay);
        rest -= to_pay;
        rest_to_pay -= (to_pay + avoirs);
        $lastInput = $(this);
    });

    if (rest_to_pay < 0) {
        rest -= rest_to_pay;
        rest_to_pay = 0;
    } else if (rest_to_pay < 0.01) {
        rest_to_pay = 0;
    }

    if (rest < 0.01) {
        rest = 0;
    }

    if (rest > 0 && $.isOk($lastInput)) {
        $lastInput.val(Math.round10(parseFloat($lastInput.val()) + rest, -2));
    }

    total_paid = Math.round10(total_paid, -2);
    rest_to_pay = Math.round10(rest_to_pay, -2);
    rest = Math.round10(rest, -2);

    displayMoneyValue(total_paid, $container.find('span.total_payments'));
    displayMoneyValue(rest_to_pay, $container.find('span.rest_to_pay'));
    displayMoneyValue(rest, $container.find('span.to_return'));

    if (rest > 0) {
        $container.find('.to_return_option_container').stop().slideDown(250);
    } else {
        $container.find('.to_return_option_container').stop().slideUp(250);
    }
}

function onClientFacturesPaymentsInputsLoaded($container) {
    $container.find('a').each(function () {
        var link_title = $(this).attr('title');
        if (link_title) {
            $(this).removeAttr('title');
            $(this).popover({
                trigger: 'hover',
                content: link_title,
                placement: 'bottom',
                html: true
            });
        }
    });

    $container.find('.facture_payment_input').each(function () {
        $(this).change(function () {
            onFacturePaymentChange($container);
        });
    });

    $container.find('[name="total_paid_amount"]').change(function () {
        onClientTotalPaidAmountChange($container);
    });
}

function onAvoirsChange($container) {
    if ($container.length) {
        var $form = $container.findParentByClass('Bimp_Paiement_form');
        if ($form.length) {
            var total_avoirs = 0;
            var amounts = [];
            var $rows = $container.find('.multipleValuesList').find('tr.itemRow');
            var $facturesInputs = $form.find('.factures_inputContainer').find('input.facture_payment_input');
            $facturesInputs.each(function () {
                $(this).data('avoirs', 0);
                $(this).findParentByClass('facture_payment_row').find('td.facture_avoirs').text('');
            });
            $rows.each(function () {
                var $row = $(this);
                var $td = $(this).find('td:nth-child(2)');
                var label = $td.html();
                label = label.replace('  <span class="danger">Erreur: Montant invalide - cet avoir n\'est pas pris en compte</span>', '');
                label = label.replace('  <span class="danger">Cet avoir ne peut pas être pris en compte car il dépasse le montant à payer de toutes les factures</span>', '');
                $row.removeAttr('style');
                $td.html(label);
                var amount = label.replace(/ /g, '');
                amount = parseFloat(amount.replace(/^.*\(\-?([0-9]+),([0-9]{2})TTC\)/, '$1.$2'));
                if (isNaN(amount)) {
                    var text = label + '  <span class="danger">Erreur: Montant invalide - cet avoir n\'est pas pris en compte</span>';
                    $row.css('background-color', '#FFEBEB');
                    $td.html(text);
                } else {
                    var check = false;
                    $facturesInputs.each(function () {
                        if (!check) {
                            var toPay = parseFloat($(this).data('to_pay'));
                            var avoirs = parseFloat($(this).data('avoirs'));
                            if (Math.round10((toPay - avoirs), -2) >= amount) {
                                var $input = $(this);
                                check = true;
                                total_avoirs += amount;
                                amounts.push({
                                    id_discount: $row.find('.item_value').val(),
                                    amount: amount,
                                    input_name: $input.attr('name')
                                });
                                avoirs += amount;
                                $input.data('avoirs', avoirs);
                                displayMoneyValue(avoirs, $input.findParentByClass('facture_payment_row').find('td.facture_avoirs'));
                            }
                        }
                    });
                    if (!check) {
                        var text = label + '  <span class="danger">Cet avoir ne peut pas être pris en compte car il dépasse le montant à payer de toutes les factures</span>';
                        $row.css('background-color', '#FFEBEB');
                        $td.html(text);
                    }
                }
            });

            $form.find('[name="total_avoirs"]').val(total_avoirs);
            $form.find('[name="avoirs_amounts"]').val(JSON.stringify(amounts));
            var $div = $form.find('.total_avoirs_container');
            displayMoneyValue(total_avoirs, $div.find('span.total_avoirs'));
            if (total_avoirs > 0) {
                $div.slideDown(250);
            } else {
                $div.slideUp(250);
            }
            onFacturePaymentChange($form.find('.factures_payment_container'));
        } else {
            bimp_msg('Une erreur est survenue (Formulaire non trouvé)', 'danger');
        }
    } else {
        bimp_msg('Une erreur est survenue (Container invalide)', 'danger');
    }
}

$(document).ready(function () {
    $('body').on('inputMultipleValuesChange', function (event) {
        if (event.input_name === 'avoirs') {
            onAvoirsChange(event.$container);
        }
    });
});