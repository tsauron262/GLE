// Notifications:
var bimp_msg_enable = true;

function bimp_msg(msg, className, $container) {
    if (!bimp_msg_enable) {
        return;
    }

    if (typeof (className) === 'undefined') {
        className = 'info';
    }
    var html = '<p class="alert alert-' + className + '">';
    html += msg;
    html += '</p>';

    if ($container && (typeof ($container) === 'object') && $container.length) {
        $container.html(html).stop().slideDown(250, function () {
            $(this).css('height', 'auto');
        });
    } else {
        $container = $('#page_notifications');

        if (!$container.length) {
            return;
        }

        $container.append(html).show().css('height', 'auto');

        var $p = $container.find('p:last-child').css('margin-left', '370px').animate({
            'margin-left': 0
        }, {
            'duration': 250,
            complete: function () {
                setTimeout(function () {
                    if (!$p.data('hold')) {
                        $p.fadeOut(500, function () {
                            $p.remove();
                            if (!$container.find('p').length) {
                                $container.hide();
                            }
                        });
                    }
                }, 8000);
            }
        });
    }
}

function bimp_display_element_popover($element, content, side) {
    if ($element.length) {
        if (typeof (side) === 'undefined') {
            side = 'right';
        }
//        $element.popover('destroy');
        $element.popover({
            html: true,
            content: content,
            placement: side,
            trigger: 'manual',
            container: 'body'
        }).popover('show');
    }
}

//Modals:

function loadModalIFrame($button, url, title) {
    bimpModal.loadIframe($button, url, title);
}

function loadImageModal($button, src, title) {
    bimpModal.loadImage($button, src, title);
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

function hidePopovers($container) {
    $container.find('.bs-popover').each(function () {
        $(this).popover('hide');
    });
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
                        $table.children('tbody,tfoot').add($table.children('thead').children('tr.col_headers')).fadeOut(250, function () {
                            $table.removeClass('open').addClass('closed');
                        });
                    } else {
                        $table.children('tbody,tfoot').add($table.children('thead').children('tr.col_headers')).fadeIn(250, function () {
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
    $container.find('.bs-popover').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            $(this).popover();
            $(this).click(function () {
                $(this).popover('hide');
            });
            $(this).data('event_init', 1);
        }
    });
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
    });
    $container.find('select').each(function () {
        if (!parseInt($(this).data('color_event_init'))) {
            checkSelectColor($(this));
            $(this).change(function () {
                checkSelectColor($(this));
            });
            $(this).data('color_event_init', 1);
        }
    });
    $container.find('.nav-tabs').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            $(this).find('li > a').click(function (e) {
                e.preventDefault();
                $(this).tab('show');
            });
            $(this).data('event_init', 1);
        }
    });
    $container.find('.hideOnClickOut').each(function () {
        $(this).click(function (e) {
            e.stopPropagation();
        });
    });
    $container.find('.displayProductStocksBtn').each(function () {
        $(this).click(function (e) {
            e.stopPropagation();
            displayProductStocks($(this), $(this).data('id_product'), $(this).data('id_entrepot'));
        });
    });
    $container.find('a[data-toggle="tab"]').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            $(this).on('shown.bs.tab', function (e) {
                var target = '' + e.target;
                var tab_id = target.replace(/^.*#(.*)$/, '$1');
                var $content = $('#' + tab_id);
                if ($content.length) {
                    setCommonEvents($content);
                }
            });
            $(this).data('event_init', 1);
        }
    });
    
    $container.find('.classfortooltip').each(function() {
        $(this).removeClass('classfortooltip');
        $(this).popover({
            container: 'body',
            placement: 'bottom',
            html: true,
            trigger: 'hover',
            content: $(this).data('title')
        });
    });
    checkMultipleValues();
    
    
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

function checkSelectColor($select) {
    var color = $select.find('option:selected').data('color');
    if (color) {
        $select.css({'color': '#' + color, 'font-weight': 'bold', 'border-bottom-color': '#' + color});
    } else {
        $select.css({'color': '#3C3C3C', 'font-weight': 'normal', 'border-bottom-color': 'rgba(0, 0, 0, 0.2)'});
    }
}

function inputQtyUp($qtyInputContainer) {
    var $input = $qtyInputContainer.find('input.qtyInput');
    if ($input.length) {
        var val = $input.val();
        if (val === '') {
            val = 0;
        }
        var step = 1;
        var decimals = parseInt($input.data('decimals'));
        if (decimals > 0) {
            val = Math.round10(parseFloat(val), -decimals);
            step = parseFloat($input.data('step'));
        } else {
            val = parseInt(val);
            step = parseInt($input.data('step'));
        }
        if (isNaN(step) || typeof (step) === 'undefined') {
            step = 1;
        }
        if (typeof (val) === 'number') {
            val += step;
            if (decimals > 0) {
                val = Math.round10(val, -decimals);
            }
            $input.val(val).change();
            checkInputQty($qtyInputContainer);
        }
    }
}

function inputQtyDown($qtyInputContainer) {
    var $input = $qtyInputContainer.find('input.qtyInput');
    if ($input.length) {
        var val = $input.val();
        if (val === '') {
            val = 0;
        }
        var step = 1;
        var decimals = parseInt($input.data('decimals'));

        if (decimals > 0) {
            val = Math.round10(parseFloat(val), -decimals);
            step = parseFloat($input.data('step'));
        } else {
            val = parseInt(val);
            step = parseInt($input.data('step'));
        }
        if (isNaN(step) || typeof (step) === 'undefined') {
            step = 1;
        }
        if (typeof (val) === 'number') {
            val -= step;
            if (decimals > 0) {
                val = Math.round10(val, -decimals);
            }
            $input.val(val).change();
            checkInputQty($qtyInputContainer);
        }
    }
}

function inputQtyMax($qtyInputcontainer) {

}

function inputQtyMin($qtyInputcontainer) {

}

function checkInputQty($qtyInputContainer) {
    var $input = $qtyInputContainer.find('input.qtyInput');
    if ($input.length) {
        checkTextualInput($input);
    }
}

// Components: 

function getComponentParams($component) {
    var params = {};

    if ($.isOk($component)) {
        var $container = $component.children('.object_component_params');
        if ($.isOk($container)) {
            $container.find('input.object_component_param').each(function () {
                params[$(this).attr('name')] = $(this).val();
            });
        }
    }

    return params;
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
    if (value < 0) {
        negatif = true;
        value = -value;
    }

    if (value === null || isNaN(value)) {
        value = '0,00';
    } else {
        value = Math.round10(value, -2);
        value = '' + value;
        if (!/^[0-9]+\.[0-9]+/.test(value)) {
            value += ',0';
        }
        if (!/^[0-9]+\.[0-9]{2}$/.test(value)) {
            value += '0';
        }
    }

    value = value.replace(/^([0-9]+)\.?([0-9]?)([0-9]?)$/, '$1,$2$3');
    value = lisibilite_nombre(value);
    if (negatif)
        value = "-" + value;
    $container.html('<span class="' + classCss + '">' + value + ' ' + currency + '</span>');
}

function lisibilite_nombre(nbr) {
    var nombre = '' + nbr;
    var retour = '';
    var count = 0;
    for (var i = nombre.length - 1; i >= 0; i--)
    {
        if (count != 0 && count % 3 == 0)
            retour = nombre[i] + ' ' + retour;
        else
            retour = nombre[i] + retour;
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

function getRandomInt(max) {
    return Math.floor(Math.random() * Math.floor(max));
}
// Divers:

function getUrlParam(param) {
    var search = window.location.search.replace('?', '');
    var args = search.split('&');
    var value = '';
    for (i in args) {
        var regex = new RegExp('^' + param + '=(.*)$');
        if (regex.test(args[i])) {
            value = args[i].replace(regex, '$1');
        }
    }
    return value;
}

// Ajouts jQuery:

$.fn.tagName = function () {
    if (this.length) {
        return this.get(0).tagName.toLowerCase();
    }
    return '';
};

$.fn.findParentByClass = function (className) {
    if (!this.length) {
        return this;
    }

    var $parent = this.parent();
    while ($parent.length) {
        if ($parent.hasClass(className)) {
            return $parent;
        }
        $parent = $parent.parent();
    }
    return $();
};

$.fn.findParentByTag = function (tag) {
    if (!this.length) {
        return this;
    }

    tag = tag.toLowerCase();

    var $parent = this.parent();
    while ($parent.length) {
        if ($parent.tagName() === tag) {
            return $parent;
        }
        $parent = $parent.parent();
    }

    return $();
};

$.isOk = function (object) {
    if (object === null) {
        return false;
    }

    if (typeof (object) !== 'object') {
        return false;
    }

    if (typeof (object.length) === 'undefined') {
        return false;
    }

    if (!object.length) {
        return false;
    }

    return true;
};

function findParentByClass($element, className) {
    return $element.findParentByClass(className);
}

function findParentByTag($element, tag) {
    return $element.findParentByTag(tag);
}

$(document).ready(function () {
    $('body').click(function (e) {
        $(this).find('.hideOnClickOut').hide();
    });
    $('body').append('<div id="page_notifications"></div>');
    var $notifications = $('#page_notifications');
    $notifications.mouseover(function () {
        $(this).stop().animate({
            'width': '5px',
            'padding': 0
        }, {
            'duration': 250,
            complete: function () {
                $notifications.find('p').each(function () {
                    $(this).data('hold', 1);
                    $(this).stop(false, true);
                });
                setTimeout(function () {
                    $notifications.stop().animate({
                        'width': '430px',
                        'padding': '0 30px'
                    }, {
                        'duration': 250,
                        'complete': function () {
                            $notifications.find('p').each(function () {
                                var $p = $(this);
                                $p.data('hold', 0);
                                setTimeout(function () {
                                    if (!$p.data('hold')) {
                                        $p.fadeOut(500, function () {
                                            $p.remove();
                                            if (!$notifications.find('p').length) {
                                                $notifications.hide();
                                            }
                                        });
                                    }
                                }, 8000);
                            });
                        }
                    });
                }, 1500);
            }
        });
    });

    $('.object_header').each(function () {
        setCommonEvents($(this));
    });
    setCommonEvents($('body'));
});