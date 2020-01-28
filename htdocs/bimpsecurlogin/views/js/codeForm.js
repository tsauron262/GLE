$(function () {
    var body = $("body");
    $('#error_js').hide();
    var code1 = $('input[name="sms_code_1"]');
    var code2 = $('input[name="sms_code_2"]');
    var code3 = $('input[name="sms_code_3"]');
    var code4 = $('input[name="sms_code_4"]');
    code1.select();

    function Pasted(e) {
        code1.prop("disabled", false);
        var memoire = code1.val();
        code1.val('');
        setTimeout(function () {
            traitementCode(memoire);
        }, 100);
    }

    function for_iphone(e) {
        var memoire = code1.val();
        traitementCode(memoire);
    }

    function traitementCode(memoire) {
        var pasted = code1.val();
        if (pasted >= 0 && pasted.length == 4) {
            code1.attr("value", pasted[0]);
            code1.val(pasted[0]);
            code2.val(pasted[1]);
            code3.val(pasted[2]);
            code4.val(pasted[3]);
            disabled('all', pasted);
        } else {
            if (pasted.length != 1) {
                $('#error_js').slideDown();
                $("#error_js").text('Collage impossible. Le code doit contenir 4 chiffres').delay(5000);
                $('#error_js').slideUp(200);
                code1.val(memoire);
            }
        }
    }

    function Input(e) {
        var key = e.key;
        target = $(e.target);
        next = target.next('input');
        if (key == 'Enter') {
            $('.btn').click();
        }
        if (key < 0 || key > 9) {
            e.preventDefault();
            return false;
        }
        if (key === 9) {
            return true;
        }
        if (!next || !next.length) {
            next = body.find("input").eq(0);
        }
        var re = new RegExp("^[0-9]");
        if (re.test(e.key))
            next.select().focus();
    }

    function onSubmit(e) {
        $("input").prop("disabled", false);
    }


    function disabled(element, pasted) {
        var parsed = parseInt(pasted, 10);
        if (parsed >= 0 || pasted != '') {
            if (element = 'all') {
                code1.prop("disabled", true);
                code2.prop("disabled", true);
                code3.prop("disabled", true);
                code4.prop("disabled", true);
                $("#error_js").text('');
            }
        } else {
            $('#error_js').slideDown();
            $("#error_js").text("Merci de copier/coller des chiffres et non du texte").delay(4000);
            $('#error_js').slideUp(200);
        }
    }
    ;
    $('input[name="sms_code_1"]').on("keyup", function () {
        if ($(this).val().length == 4) {
            for_iphone();
        }
    });
    body.on("submit", function () {
        onSubmit();
    });

    body.on("keyup", "input", Input);
    body.on("paste", code1, Pasted);
});