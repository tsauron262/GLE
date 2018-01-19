function BimpAjax(action, data, $resultContainer, params) {
    var bimpAjax = this;

    if ((typeof ($resultContainer) !== 'object') ||
            !$resultContainer.length) {
        $resultContainer = null;
    }

    this.$resultContainer = $resultContainer;

    this.url = ajaxRequestsUrl;
    this.type = 'POST';
    this.dataType = 'json';

    this.display_success = true;
    this.display_errors = true;
    this.display_processing = false;

    this.append_html = false;

    this.processing_msg = 'Traitement en cours';
    this.success_msg = 'Opération effectuée avec succès';
    this.error_msg = 'Une erreur est survenue, l\'opération n\'a pas aboutie';

    this.success = function () {
    };
    this.error = function () {
    };

    if (typeof (params) === 'object') {
        for (i in params) {
            bimpAjax[i] = params[i];
        }
    }

    if (!/\?/.test(bimpAjax.url)) {
        bimpAjax.url += '?';
    } else {
        bimpAjax.url += '&';
    }
    bimpAjax.url += 'ajax=1&action=' + action;

    if (this.display_processing && this.$resultContainer) {
        bimp_display_msg(this.processing_msg, this.$resultContainer, 'info');
    }

    $.ajax({
        url: bimpAjax.url,
        type: bimpAjax.type,
        dataType: bimpAjax.dataType,
        data: data,
        success: function (result) {
            if (bimpAjax.display_processing && bimpAjax.$resultContainer) {
                bimpAjax.$resultContainer.html('').stop().slideUp(250);
            }
            if ((typeof (result.errors) !== 'undefined') && result.errors && result.errors.length) {
                bimpAjax.display_result_errors(result.errors);

                if (typeof (bimpAjax.error) === 'function') {
                    bimpAjax.error(result);
                }
            } else {
                bimpAjax.display_result_success(result);

                if (bimpAjax.append_html) {
                    if (bimpAjax.$resultContainer && typeof (result.html) === 'string') {
                        bimpAjax.$resultContainer.stop().slideUp(250, function () {
                            bimpAjax.$resultContainer.html(result.html).slideDown(250);
                        });
                    }
                }
                if (typeof (bimpAjax.success) === 'function') {
                    bimpAjax.success(result);
                }
            }
        },
        error: function () {
            if (bimpAjax.display_errors) {
                bimpAjax.display_result_errors(bimpAjax.error_msg);
            }
        }
    });

    this.display_result_errors = function (errors) {
        if (!bimpAjax.display_errors) {
            return;
        }

        if (typeof (errors) !== 'undefined') {
            var msg = '';
            if (typeof (errors) === 'string') {
                msg = errors;
            } else if (typeof (errors) === 'object') {
                msg = errors.length;
                if (errors.length > 1) {
                    msg += ' erreurs détectées';
                } else {
                    msg += ' erreur détectée';
                }
                msg += '<br/><br/>';
                var n = 1;
                for (var i in errors) {
                    msg += n + '- ' + errors[i] + '<br/>';
                    n++;
                }
                bimp_display_msg(msg, bimpAjax.$resultContainer, 'danger');
            }
        }
    };

    this.display_result_success = function (result) {
        if (!bimpAjax.display_success) {
            return;
        }

        var msg = '';
        if (typeof (result.success) === 'string') {
            msg = result.success;
        } else {
            msg = bimpAjax.success_msg;
        }

        bimp_display_msg(result.success, bimpAjax.$resultContainer, 'success');
    };
}