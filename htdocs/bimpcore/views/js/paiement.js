function onFacturePaymentChange($container) {
    var total_payments = 0;
    var total_to_pay = $container.find('[name="total_to_pay"]').val();

    if (total_to_pay === '') {
        total_to_pay = 0;
    } else {
        total_to_pay = parseFloat(total_to_pay);
    }

    $container.find('input.facture_payment_input').each(function () {
        var value = $(this).val();
        if (value !== '') {
            total_payments += parseFloat(value);
        }
    });

    var diff = total_to_pay - total_payments;
    var rest_to_pay = 0;
    var to_return = 0;

    if (diff > 0) {
        rest_to_pay = diff;
    } else {
        to_return = -diff;
    }

    displayMoneyValue(total_payments, $container.find('span.total_payments'));
    displayMoneyValue(rest_to_pay, $container.find('span.rest_to_pay'));
    displayMoneyValue(to_return, $container.find('span.to_return'));

    $container.find('[name="total_paid_amount"]').val(total_payments);
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

    $container.find('input.facture_payment_input').each(function () {
        var to_pay = parseFloat($(this).data('to_pay'));
        if (to_pay > rest) {
            to_pay = Math.round10(rest, -2);
        }
        $(this).val(to_pay);
        rest -= to_pay;
        rest_to_pay -= to_pay;
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

    displayMoneyValue(total_paid, $container.find('span.total_payments'));
    displayMoneyValue(rest_to_pay, $container.find('span.rest_to_pay'));
    displayMoneyValue(rest, $container.find('span.to_return'));
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