// successCallBack : soit fonction callback en cas de succès, soit message par défaut en cas de succès
// errorCallBack : soit fonction callback en cas d'erreur, soit message par défaut en cas d'erreur

// Traitements Ajax:
var ajaxRequestsUrl = './index.php';

function bimp_json_ajax(action, data, $resultContainer, successCallBack, errorCallBack, display_processing) {
    var display_result_errors_only = false;
    if ((typeof (successCallBack) === 'string') ||
            typeof (successCallBack) === 'function') {
        display_result_errors_only = true;
    }

    if (typeof (display_processing) === 'undefined') {
        display_processing = true;
    }

    if (display_processing) {
        bimp_display_msg('Traitement en cours', $resultContainer, 'info');
    }

    if (typeof (data) === 'object') {
        data.ajax = 1;
        data.action = action;
    } else if (typeof (data) === 'string') {
        data += '&ajax=1&action=' + action;
    }

    var ajaxRequestUrl = '';
    if (typeof (data.ajaxRequestUrl) !== 'undefined') {
        ajaxRequestUrl = data.ajaxRequestUrl;
    } else {
        ajaxRequestUrl = ajaxRequestsUrl;
    }

    $.ajax({
        type: "POST",
        url: ajaxRequestUrl,
        dataType: 'json',
        data: data,
        success: function (result) {
            bimp_displayAjaxResult(result, $resultContainer, display_result_errors_only);
            if (!result.errors.length) {
                if ((typeof (result.success) === 'string') && result.success) {
                    bimp_display_msg(result.success, $resultContainer, 'success');
                } else if (typeof (successCallBack) === 'string') {
                    bimp_display_msg(successCallBack, $resultContainer, 'success');
                }
                if (typeof (successCallBack) === 'function') {
                    successCallBack(result);
                }
            } else {
                if (typeof (errorCallBack) === 'function') {
                    errorCallBack(result);
                }
            }
        },
        error: function () {
            if (typeof (errorCallBack) === 'string') {
                bimp_display_msg(errorCallBack, $resultContainer, 'danger');
            } else {
                bimp_display_msg('Une erreur est survenue. La requête n\'a pas aboutie', $resultContainer, 'danger');
            }
            if (typeof (errorCallBack) === 'function') {
                errorCallBack();
            }
        }
    });
}

function bimp_display_msg(msg, $container, className) {
    if (!$container || (typeof ($container) === 'undefined') || !$container.length) {
        return;
    }
    var html = '';
    if (typeof (className) !== 'undefined') {
        html += '<p class="alert alert-' + className + ' alert-dismissible">';
        html += '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
    } else {
        html += '<p>';
    }
    html += msg + '</p>';
    $container.html(html).slideDown(250);
}

function bimp_displayAjaxResult(data, $container, errors_only) {
    if (typeof (errors_only) === 'undefined') {
        errors_only = false;
    }
    if (!$container || (typeof ($container) === 'undefined') || !$container || !$container.length) {
        $container = false;
    } else {
        $container.hide().html('');
    }

    if (typeof (data) === 'undefined') {
        bimp_display_msg('Une erreur est survenue: aucune données reçues', $container, 'danger');
        return;
    }

    if (!bimp_display_result_errors(data, $container)) {
        if (!errors_only) {
            bimp_display_result_success(data, $container);
        }
    }
}

function bimp_display_result_errors(result, $container) {
    if (typeof (result.errors) !== 'undefined') {
        if (result.errors.length) {
            var msg = result.errors.length;
            if (result.errors.length > 1) {
                msg += ' erreurs détectées';
            } else {
                msg += ' erreur détectée';
            }
            msg += '<br/><br/>';
            var n = 1;
            for (var i in result.errors) {
                msg += n + '- ' + result.errors[i] + '<br/>';
                n++;
            }
            if ($container) {
                bimp_display_msg(msg, $container, 'danger');
            }
            return true;
        }
    }
    return false;
}

function bimp_display_result_success(result, $container) {
    if (typeof (result.success) !== 'undefined') {
        if (typeof (result.success) === 'string') {
            bimp_display_msg(result.success, $container, 'success');
        } else {
            bimp_display_msg('Opération réalisée avec succès', $container, 'success');
        }
    }
}

// Actions: 

function toggleFoldableSection($caption) {
    var $section = $caption.parent('.foldable_section');
    if (!$section.length) {
        return;
    }
    var $content = $section.children('.foldable_section_content');
    if (!$content.length) {
        return;
    }

    if ($section.hasClass('open')) {
        $content.stop().slideUp(250, function () {
            $section.removeClass('open').addClass('closed');
            $(this).removeAttr('style');
        });

    } else {
        $content.stop().slideDown(250, function () {
            $section.removeClass('closed').addClass('open');
            $(this).removeAttr('style');
        });
    }
}

// Evenements: 

function setCommonEvents($container) {
    // foldable sections: 
    $container.find('.foldable_section_caption').each(function () {
        if (!parseInt($(this).data('foldable_event_init'))) {
            $(this).click(function () {
                toggleFoldableSection($(this));
            });
            $(this).data('foldable_event_init', 1);
        }
    });

    // foldable panels:
    $container.find('.panel.foldable').each(function () {
        if (!parseInt($(this).data('foldable_event_init'))) {
            var $panel = $(this);
            $panel.children('.panel-heading').click(function () {

                if ($panel.hasClass('open')) {
                    $panel.children('.panel-body, .panel-footer').slideUp(250, function () {
                        $panel.removeClass('open').addClass('closed');
                    });
                } else {
                    $panel.children('.panel-body, .panel-footer').slideDown(250, function () {
                        $panel.removeClass('closed').addClass('open');
                    });
                }
            });
            $panel.children('.panel-heading').find('.headerBtn').click(function (e) {
                e.stopPropagation();
            });
            $(this).data('foldable_event_init', 1);
        }
    });

    // foldable view tables:
    $container.find('.objectViewtable.foldable').each(function () {
        var $table = $(this);
        $table.children('thead').children('tr:first-child').each(function () {
            if (!parseInt($(this).data('foldable_event_init'))) {
                $(this).click(function () {
                    if ($table.hasClass('open')) {
                        $table.children('tbody,tfoot').fadeOut(250, function () {
                            $table.removeClass('open').addClass('closed');
                        });
                    } else {
                        $table.children('tbody,tfoot').fadeIn(250, function () {
                            $table.removeClass('closed').addClass('open');
                        });
                    }
                });
                $(this).data('foldable_event_init', 1);
            }
        });
    });

    // Popup
    $container.find('.displayPopupButton').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            setDisplayPopupButtonEvents($(this));
            $(this).data('event_init', 1);
        }
    });

    // bootstrap popover:
    $container.find('.bs-popover').popover();

    // Auto-expand: 
    $container.on('input.auto_expand', 'textarea.auto_expand', function () {
        var minRows = $(this).data('min_rows'), rows;
        if (!minRows) {
            minRows = 3;
        }
        this.rows = minRows;
        rows = Math.floor((this.scrollHeight - this.baseScrollHeight) / 16);
        this.rows = rows + minRows;
    });
    $container.find('textarea.auto_expand').each(function () {
        if (typeof (this.baseScrollHeight) === 'undefined') {
            var minRows = parseInt($(this).data('min_rows')), rows;
            if (!minRows) {
                minRows = 3;
            }
            this.baseScrollHeight = minRows * 16;

            this.rows = minRows;
            if (this.scrollHeight) {
                rows = Math.floor((this.scrollHeight - this.baseScrollHeight) / 16);
                this.rows = rows + minRows;
            }
        }
    });
}

function setDisplayPopupButtonEvents($button) {
    if (!$button.length) {
        return;
    }

    if (parseInt($button.data('event_init'))) {
        return;
    }

    var $popup = $button.parent().find('#' + $button.data('popup_id'));
    if ($popup.length) {
        $button.add($popup).mouseover(function () {
            $popup.show();
        }).mouseout(function () {
            $popup.hide();
        });
    }
    $button.data('event_init', 1);
}

// Rendus HTML 

function renderLoading(msg, id_container) {
    var html = '<div class="content-loading"';
    if (id_container) {
        html += ' id="' + id_container + '">';
    }
    html += '>';
    html += '<div class="loading-spin"><i class="fa fa-spinner fa-spin"></i></div>';
    if (msg) {
        html += '<p class="loading-text">' + msg + '</p>';
    }
    html += '</div>';
    return html;
}

// Inputs

function selectSwitchOption($button) {
    if ($button.hasClass('selected')) {
        return;
    }

    var val = $button.data('value');
    var $container = $button.parent('div').parent('.switchInputContainer');
    $container.find('input').val(val).change();
    $container.find('.switchOption').each(function () {
        $(this).removeClass('selected');
    });
    $button.addClass('selected');
}

function updateTimerInput($input, input_name) {
    var $container = $input.parent('div.timer_input');
    var days = $container.find('[name=' + input_name + '_days]').val();
    if (!/^[0-9]+$/.test(days)) {
        days = 0;
        $container.find('[name=' + input_name + '_days]').val(days);
    }
    days = parseInt(days);
    var hours = parseInt($container.find('[name=' + input_name + '_hours]').val());
    var minutes = parseInt($container.find('[name=' + input_name + '_minutes]').val());
    var secondes = parseInt($container.find('[name=' + input_name + '_secondes]').val());
    var total_secs = secondes + (minutes * 60) + (hours * 3600) + (days * 86400);
    $container.find('input[name=' + input_name + ']').val(total_secs).change();
}

// Affichages: 

function displayMoneyValue(value, $container, classCss, currency) {
    var negatif = false;
    if (!$container.length) {
        return;
    }

    if (typeof (currency) === 'undefined') {
        currency = '&euro;';
    }
    if (typeof (classCss) === 'undefined') {
        classCss = '';
    }

    value = parseFloat(value);
    
    if(value < 0){
        negatif = true;
        value = -value;
    }

    if (value === null || isNaN(value)) {
        value = '0,00';
    } else {
        value = Math.round10(value, -2);
        value = '' + value;
        if (!/^[0-9]+\.[0-9]+/.test(value)) {
            value += '.0';
        }
        if (!/^[0-9]+\.[0-9]{2}$/.test(value)) {
            value += '0';
        }
    }

    value = value.replace(/^([0-9]+)\.?([0-9]?)([0-9]?)$/, '$1,$2$3');
    value = lisibilite_nombre(value);
    if(negatif)
        value = "-"+value;
    
    $container.html('<span class="'+classCss+'">'+value + ' ' + currency+'</span>');
}

function lisibilite_nombre(nbr)
{
    var nombre = ''+nbr;
    var retour = '';
    var count=0;
    for(var i=nombre.length-1 ; i>=0 ; i--)
    {
            if(count!=0 && count % 3 == 0)
                    retour = nombre[i]+' '+retour ;
            else
                    retour = nombre[i]+retour ;
            count++;
    }
    return retour.replace(" ,", ",");
}

// Math:

(function () {
    function decimalAdjust(type, value, exp) {
        if (typeof exp === 'undefined' || +exp === 0) {
            return Math[type](value);
        }
        value = +value;
        exp = +exp;
        if (value === null || isNaN(value) || !(typeof exp === 'number' && exp % 1 === 0)) {
            return NaN;
        }
        if (value < 0) {
            return -decimalAdjust(type, -value, exp);
        }
        value = value.toString().split('e');
        value = Math[type](+(value[0] + 'e' + (value[1] ? (+value[1] - exp) : -exp)));
        value = value.toString().split('e');
        return +(value[0] + 'e' + (value[1] ? (+value[1] + exp) : exp));
    }

    if (!Math.round10) {
        Math.round10 = function (value, exp) {
            return decimalAdjust('round', value, exp);
        };
    }
    if (!Math.floor10) {
        Math.floor10 = function (value, exp) {
            return decimalAdjust('floor', value, exp);
        };
    }
    if (!Math.ceil10) {
        Math.ceil10 = function (value, exp) {
            return decimalAdjust('ceil', value, exp);
        };
    }
})();