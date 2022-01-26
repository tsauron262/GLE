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

        $form.find('tr.part_row').each(function () {
            var part = {
                'part_number': $(this).data('part_number')
            };

            var $input = $(this).find('input.part_qty_input');
            if ($input.length) {
                part.qty = parseInt($input.val());
            } else {
                var $check_list = $(this).find('.part_serials_check_list');

                if ($check_list.length) {
                    var serials = [];

                    $check_list.find('.check_list_item_input:checked').each(function () {
                        serials.push($(this).val());
                    });

                    if (serials.length) {
                        part.serials = serials;
                    }
                }
            }

            parts.push(part);
        });

        extra_data.parts = parts;
        return extra_data;
    };
}

var ConsignedStocks = new ConsignedStocks();