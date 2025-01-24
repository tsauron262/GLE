function BimpLocation() {
    this.onNewFactureFormSubmit = function ($form, extra_data) {
        var lines = [];

        $form.find('input.line_check').each(function () {
            if ($(this).prop('checked')) {
                var id_line = parseInt($(this).data('id_line'));

                if (id_line) {
                    lines.push(id_line);
                }
            }
        });

        extra_data['lines'] = lines;
        return extra_data;
    };

    this.processSerial = function ($button) {
        if (!$.isOk($button) || $button.hasClass('disabled')) {
            return;
        }

        var $form = $button.findParentByClass('quickProcessForm');
        if (!$.isOk($form)) {
            bimp_msg('Erreur : formulaire absent', 'danger');
            BimpSound.play('danger');
            return;
        }

        var $resultContainer = $form.find('.quickProcessForm_ajax_result');

        var status = $form.find('select[name="quick_process_status"]').val();
        if (!status) {
            bimp_msg('Type d\'opération absent', 'danger');
            BimpSound.play('danger');
            return;
        }

        var serial = $form.find('input[name="quick_process_serial"]').val();
        if (!serial) {
            bimp_msg('Veuillez saisir un numéro de série à traiter', 'danger');
            BimpSound.play('danger');
            return;
        }

        setObjectAction($button, {
            module: 'bimplocation',
            object_name: 'BimpLocation',
            id_object: parseInt($form.data('id_location'))
        }, 'processSerial', {
            status: status,
            serial: serial
        }, $resultContainer, function () {
            $('input[name="quick_process_serial"]').val('');
        }, {
            remove_result_content: false,
            play_sounds: true
        });
    };
}

var BimpLocation = new BimpLocation();