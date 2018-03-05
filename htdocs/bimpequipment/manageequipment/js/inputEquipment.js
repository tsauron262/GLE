/**
 * @param {string} itsSelector unique selector of the input where you add scanned datas
 * @param {string} functionToCall the name of the function triggered when you press enter or tab
 * @param {string} inputQtySelector the input where to set the amount of product
 * @returns {undefined}
 */
function initIE(itsSelector, functionToCall, inputQtySelector) {
    if (typeof (inputQtySelector) === 'undefined')
        inputQtySelector = false;
    initEventsIE(itsSelector, functionToCall, inputQtySelector);
}


function initEventsIE(itsSelector, functionToCall, inputQtySelector) {
    var scanElt = $(itsSelector);

    scanElt.on('keyup', function (e) {
        if (e.keyCode === 13) { // code for "Enter"
            prepareAjaxIE($(this), functionToCall, inputQtySelector);
            e.preventDefault();

        }
    });

    scanElt.on('keydown', function (e) {
        if (e.keyCode === 9) { // code for "Tab"
            prepareAjaxIE($(this), functionToCall, inputQtySelector);
            e.preventDefault();
        }
    });
}

/**
 * Check if the text is a ref/barcode/serial or an amount and exec the good function
 */
function prepareAjaxIE(element, functionToCall, inputQtySelector) {
    var ref = element.val();
    if (-1000 <= ref && ref <= 1000 && inputQtySelector != false) {
        $(inputQtySelector).val(ref);
    } else if (ref !== '') {
        eval(functionToCall + '(ref)');
        if (inputQtySelector != false)
            $(inputQtySelector).val(1);
    }
    element.val('');
    element.focus();
}
