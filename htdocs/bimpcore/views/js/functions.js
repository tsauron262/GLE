// Notifications:
var bimp_msg_enable = true;
var ctrl_down = false;
var bimp_decode_textarea = null;

function bimp_msg(msg, className, $container) {
    if (!bimp_msg_enable) {
        return;
    }

    if (typeof (className) === 'undefined') {
        className = 'info';
    }
    var html = '<div class="bimp_msg alert alert-' + className + '">';
    html += msg;
    html += '</div>';

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

        var $div = $container.find('div.bimp_msg:last-child').css('margin-left', '370px').animate({
            'margin-left': 0
        }, {
            'duration': 250,
            complete: function () {
                setTimeout(function () {
                    if (!$div.data('hold')) {
                        $div.fadeOut(500, function () {
                            $div.remove();
                            if (!$container.find('div.bimp_msg').length) {
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

        if (typeof ($element.data('bs.popover')) !== 'undefined') {
            $element.data('bs.popover').options.content = content;
            $element.data('bs.popover').options.side = side;
            $element.popover('show');
        } else {
            $element.popover({
                html: true,
                content: content,
                placement: side,
                trigger: 'manual',
                container: 'body'
            });
            $element.popover('show');
            $element.addClass('bs-popover');
        }
    }
}

function bimp_destroy_element_popover($element) {
    if (typeof ($element.data('bs.popover')) !== 'undefined') {
        $element.popover('destroy');
        $element.removeClass('bs-popover');
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

function toggleElementDisplay($element, $button) {
    if (!$.isOk($element)) {
        return;
    }

    if ($element.css('display') === 'none') {
        $element.stop().slideDown(250);
        if ($.isOk($button)) {
            if ($button.hasClass('open-close')) {
                $button.removeClass('action-open').addClass('action-close');
            }
        }
    } else {
        $element.stop().slideUp(250);
        if ($.isOk($button)) {
            if ($button.hasClass('open-close')) {
                $button.removeClass('action-close').addClass('action-open');
            }
        }
    }
}

function hidePopovers($container) {
    $container.find('.bs-popover').each(function () {
        $(this).popover('hide');
    });
}

// Evenements: 

function setCommonEvents($container) {
    //Foldable custom: 
    $container.find('.foldable_container').each(function () {
        if (!parseInt($(this).data('foldable_container_event_init'))) {
            var $caption = $(this).children('.foldable_caption');

            $caption.click(function () {
                var $foldableContainer = $(this).findParentByClass('foldable_container');
                if ($.isOk($foldableContainer)) {
                    if ($foldableContainer.hasClass('open')) {
                        $foldableContainer.removeClass('open').addClass('closed');
                        $foldableContainer.children('.foldable_content').slideUp(250);
                    } else {
                        $foldableContainer.removeClass('closed').addClass('open');
                        $foldableContainer.children('.foldable_content').slideDown(250);
                    }
                }
            });
            $(this).data('foldable_container_event_init', 1);
        }
    });
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
            $panel.children('.panel-heading').find('.panel_header_icon').click(function (e) {
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
        if (!parseInt($(this).data('popup_btn_event_init'))) {
            setDisplayPopupButtonEvents($(this));
            $(this).data('popup_btn_event_init', 1);
        }
    });
    // bootstrap popover:
    $container.find('.bs-popover').each(function () {
        if (!parseInt($(this).data('bs_popover_event_init'))) {
            $(this).popover();
            $(this).click(function () {
                $(this).popover('hide');
            });
            $(this).data('bs_popover_event_init', 1);
        }
    });
    // Auto-expand: 
    if (!parseInt($container.data('auto_expand_event_init'))) {
        $container.on('input.auto_expand', 'textarea.auto_expand', function () {
            checkInputAutoExpand(this);
        });
        $container.data('auto_expand_event_init', 1);
    }
    $container.find('.auto_expand').each(function () {
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
        if (!parseInt($(this).data('nav_tabs_event_init'))) {
            $(this).find('li > a[data-toggle="tab"]').click(function (e) {
                e.preventDefault();
                $(this).tab('show');
            });
            $(this).find('li > a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var $tabContent = $($(e.target).attr('href'));
                if ($.isOk($tabContent)) {
                    $tabContent.find('.object_list_table').each(function () {
                        checkListWidth($(this));
                    });
                }
            });

            $(this).data('nav_tabs_event_init', 1);
        }
    });
    $container.find('.hideOnClickOut').each(function () {
        if (!parseInt($(this).data('hide_on_click_event_init'))) {
            $(this).click(function (e) {
                e.stopPropagation();
            });
            $(this).data('hide_on_click_event_init', 1);
        }
    });
    $container.find('.displayProductStocksBtn').each(function () {
        if (!parseInt($(this).data('on_click_event_init'))) {
            $(this).click(function (e) {
                e.stopPropagation();
                displayProductStocks($(this), $(this).data('id_product'), $(this).data('id_entrepot'));
            });

            $(this).data('on_click_event_init', 1);
        }
    });
    $container.find('a[data-toggle="tab"]').each(function () {
        if (!parseInt($(this).data('toggle_tab_event_init'))) {
            $(this).on('shown.bs.tab', function (e) {
                var target = '' + e.target;
                var tab_id = target.replace(/^.*#(.*)$/, '$1');

                var $li = $('a[href="#' + tab_id + '"]').parent('li');
                if ($li.length && $li.parent('ul').data('navtabs_id') === 'maintabs') {
                    var prev = '' + e.relatedTarget;
                    prev = prev.replace(/^.*#(.*)$/, '$1');

                    var $prevLi = $('a[href="#' + prev + '"]').parent('li');
                    $prevLi.data('scrollTop', parseInt($(window).scrollTop()));
                    var scrollTop = parseInt($li.data('scrollTop'));
                    if (!isNaN(scrollTop)) {
                        $(window).scrollTop(scrollTop);
                    } else if (object_header_scroll_trigger && $(window).scrollTop() > object_header_scroll_trigger) {
                        $(window).scrollTop(object_header_scroll_trigger + 1);
                    }
                }

                var $content = $('#' + tab_id);
                if ($content.length) {
                    setCommonEvents($content);
                }
            });
            $(this).data('toggle_tab_event_init', 1);
        }
    });

    $container.find('.classfortooltip').each(function () {
        $(this).removeClass('classfortooltip').addClass('bs-popover');
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

function onWindowScroll() {
    $('.object_page_header').each(function () {
        if (!$.isOk($(this).findParentByClass('modal'))) {
            setObjectHeaderPosition($(this));
        }
    });
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

function checkInputAutoExpand(input) {
    var minRows = $(input).data('min_rows'), rows;
    if (!minRows) {
        minRows = 3;
    }
    input.rows = minRows;
    rows = Math.floor((input.scrollHeight - input.baseScrollHeight) / 16);
    input.rows = rows + minRows;
}

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
            $input.val(val);
            checkInputQty($qtyInputContainer);
            $input.change();
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
            $input.val(val);
            checkInputQty($qtyInputContainer);
            $input.change();
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

function checkAll($container, filter) {
    if (typeof (filter) === 'undefined') {
        filter = '';
    }
    if ($.isOk($container)) {
        $container.find('input[type="checkbox"]' + filter).each(function () {
            $(this).prop('checked', true).change();
        });
    }
}

function uncheckAll($container, filter) {
    if (typeof (filter) === 'undefined') {
        filter = '';
    }
    if ($.isOk($container)) {
        $container.find('input[type="checkbox"]' + filter).each(function () {
            $(this).prop('checked', false).change();
        });
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

function getUrlParams(param) {
    var search = window.location.search.replace('?', '');
    var args = search.split('&');
    var params = {};
    for (i in args) {
        var regex = new RegExp('^(.*)=(.*)$');
        if (regex.test(args[i])) {
            var name = args[i].replace(regex, '$1');
            var value = args[i].replace(regex, '$2');
            if (name && value) {
                params[name] = value;
            }
        }
    }
    return params;
}

function bimp_reloadPage() {
    // Recharge la page en cours en tenant compte des onglets actifs: 

    var url = window.location.pathname + '?';

    var navtabs = {};

    var $navtabs = $('body').find('.nav-tabs');
    if ($navtabs.length) {
        $navtabs.each(function () {
            var name = 'navtab-' + $(this).data('navtabs_id');
            var active = '';
            $(this).find('li').each(function () {
                if (!active) {
                    if ($(this).hasClass('active')) {
                        active = $(this).data('navtab_id');
                    }
                }
            });
            if (active) {
                navtabs[name] = active;
            }
        });
    }

    var tab = '';
    if (window.location.hash) {
        tab = window.location.hash.replace('#', '');
    }

    var params = getUrlParams();

    var first = true;
    var hasParams = false;
    for (var param_name in params) {
        if (param_name === 'tab') {
            if (!tab) {
                tab = params[param_name];
            }
        } else if (!/^navtab\-?.*$/.test(param_name)) {
            if (!first) {
                url += '&';
            } else {
                first = false;
            }
            url += param_name + '=' + params[param_name];
            hasParams = true;
        }
    }

    first = true;
    for (var tabname in navtabs) {
        if (!first) {
            url += '&';
        } else {
            if (hasParams) {
                url += '&';
            }
            first = false;
        }
        url += tabname + '=' + navtabs[tabname];
    }

    if (tab) {
        if (hasParams) {
            url += '&';
        }
        url += 'tab=' + tab;
    }

    window.location = url;
}

function bimp_htmlDecode(html) {
    if (!bimp_decode_textarea) {
        bimp_decode_textarea = document.createElement('textarea');
    }

    bimp_decode_textarea.innerHTML = html;
    return bimp_decode_textarea.value;
}

// Ajouts jQuery:

$.fn.tagName = function () {
    if (this.length) {
        var elem = this.get(0);
        if (elem && elem.tagName) {
            return elem.tagName.toLowerCase();
        }
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
        $(this).find('.bs-popover').popover('hide');
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
                $notifications.find('div.bimp_msg').each(function () {
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
                            $notifications.find('div.bimp_msg').each(function () {
                                var $div = $(this);
                                $div.data('hold', 0);
                                setTimeout(function () {
                                    if (!$div.data('hold')) {
                                        $div.fadeOut(500, function () {
                                            $div.remove();
                                            if (!$notifications.find('div.bimp_msg').length) {
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

    $(window).scroll(function () {
        onWindowScroll();
    });

    $('body').keydown(function (e) {
        if (e.key === 'Control') {
            ctrl_down = true;
            $(this).find('.object_page_header').each(function () {
                setObjectHeaderPosition($(this));
            });
        } else if (ctrl_down) {
            if (e.key === 'ArrowRight') {
                navTabNext('maintabs');
            } else if (e.key === 'ArrowLeft') {
                navTabPrev('maintabs');
            }
        }
    });
    $('body').keyup(function (e) {
        if (e.key === 'Control') {
            ctrl_down = false;
            $(this).find('.object_page_header').each(function () {
                setObjectHeaderPosition($(this));
            });
        }
    });
});
