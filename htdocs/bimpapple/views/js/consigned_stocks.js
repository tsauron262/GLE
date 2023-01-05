function ConsignedStocks() {
    this.receiveAll = function ($button) {
        var $container = $button.findParentByClass('part_qties_inputContainer');

        if ($.isOk($container)) {
            $container.find('input.part_qty_input').each(function () {
                var max = parseInt($(this).data('max'));
                $(this).val(max);
            });

            $container.find('.part_serials_check_list').find('.check_list_item_input').each(function () {
                $(this).prop('checked', true).change();
            });
        }
    };

    this.receiveNone = function ($button) {
        var $container = $button.findParentByClass('part_qties_inputContainer');

        if ($.isOk($container)) {
            $container.find('input.part_qty_input').each(function () {
                $(this).val(0);
            });

            $container.find('.part_serials_check_list').find('.check_list_item_input').each(function () {
                $(this).prop('checked', false).change();
            });
        }
    };

    this.onReceiveFormSubmit = function ($form, extra_data) {
        var parts = [];
        var has_error = false;

        $form.find('tr.part_row').each(function () {
            var part = {
                'part_number': $(this).data('part_number')
            };

            var $input = $(this).find('input.part_qty_input');
            if ($input.length) {
                part.qty = parseInt($input.val());
            } else {
                var serials = [];

                var $check_list = $(this).find('.part_serials_check_list');
                if ($check_list.length) {
                    $check_list.find('.check_list_item_input:checked').each(function () {
                        serials.push({'serial': $(this).val(), 'manual': 0});
                    });
                }

                var $inputs = $(this).find('input.part_serials_text_input');
                if ($inputs.length) {
                    $inputs.each(function () {
                        var $input = $(this);
                        $input.removeClass('error');
                        var serial = $(this).val();
                        if (serial) {
                            for (var i in serials) {
                                if (serials[i].serial === serial) {
                                    $input.addClass('error');
                                    bimp_msg('Le numéro de série "' + serial + '" a été sélectionné ou saisi 2 fois', 'danger');
                                    has_error = true;
                                    break;
                                }
                            }
                            serials.push({'serial': serial, 'manual': 1});
                        }
                    });
                }

                if (serials.length) {
                    part.serials = serials;
                }
            }

            parts.push(part);
        });

        if (has_error) {
            return false;
        }
        
        if (!parts.length) {
            bimp_msg('Aucun compoosant à réceptionner', 'danger', null, true);
            return false;
        }

        extra_data.parts = parts;
        return extra_data;
    };
}

function ConsignedStocksShipment() {
    this.saveParts = function ($button, id_cs_shipment) {
        var $form = $button.findParentByClass('parts_form');

        if ($.isOk($form)) {
            var parts = [];

            $form.find('input.part_qty_input').each(function () {
                var $row = $(this).findParentByTag('tr');

                if ($.isOk($row)) {
                    var part_number = $row.data('part_number');

                    if (part_number) {
                        var qty = parseInt($(this).val());

                        if (isNaN(qty)) {
                            qty = 0;
                        }

                        parts.push({
                            'number': part_number,
                            'qty': qty
                        });
                    }
                }
            });

            $form.find('.part_serials_check_list').each(function () {

                var $row = $(this).findParentByTag('tr');

                if ($.isOk($row)) {
                    var part_number = $row.data('part_number');

                    if (part_number) {
                        var serials = [];

                        $(this).find('.check_list_item_input').each(function () {
                            if ($(this).prop('checked')) {
                                serials.push($(this).val());
                            }
                        });

                        parts.push({
                            'number': part_number,
                            'serials': serials
                        });
                    }
                }
            });

            setObjectAction($button, {
                module: 'bimpapple',
                object_name: 'ConsignedStockShipment',
                id_object: id_cs_shipment
            }, 'saveParts', {
                parts: parts
            }, null, $form.find('.ajaxResultContainer'));
        }
    };
}

var ConsignedStocks = new ConsignedStocks();
var ConsignedStocksShipment = new ConsignedStocksShipment();