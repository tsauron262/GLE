//var image = new FormData();

/**
 * Ajax call
 */
//function createEvent(label, date_start, date_end) {
//
//    //get the input and the file
//    var input = document.querySelector('input[type=file]'),
//            file = input.files[0];
//
//    //if the file isn't a image nothing happens.
//    //you are free to implement a fallback
//    if (!file || !file.type.match(/image.*/))
//        return;
//
//    //Creates the FormData object and attach to a key name "file"
//    var fd = new FormData();
//    fd.append("file", file);
//
//    $.ajax({
//        type: "POST",
//        url: "../interface.php",
//        contentType: false,
//        processData: false,
//        cache: false,
//        data: {
//            label: label,
//            date_start: date_start,
//            date_end: date_end,
////            image: new FormData(form),
//            action: 'create_event'
//        },
//        error: function () {
//            setMessage('alertSubmit', 'Erreur serveur 1895.', 'error');
//        },
//        success: function (rowOut) {
//            var out = JSON.parse(rowOut);
//            if (out.errors.length !== 0) {
//                printErrors(out.errors, 'alertSubmit');
//            } else if (out.code_return > 0) {
//                setMessage('alertSubmit', "L'évènement a été créée.", 'msg');
//            } else {
//                setMessage('alertSubmit', 'Erreur serveur 1853.', 'error');
//            }
//        }
//    });
//}

function createEvent() {
    var formData = new FormData($('#create_form')[0]);
    
    $.ajax({
        url: '../interface.php',
        type: 'POST',
        data: formData,
        success: function (rowOut) {
            
        },
        cache: false,
        contentType: false,
        processData: false
    });
}
/**
 * Ready
 */
$(document).ready(function () {
    $('input[name=date_start]').datepicker({dateFormat: 'dd/mm/yy'})
    $('input[name=date_end]').datepicker({dateFormat: 'dd/mm/yy'})
    initEvents();

});

/**
 * Function
 */


function initEvents() {
    $("#img_event").change(function () {
        readURL(this, '#img_display');
    });

    $('#create_form').submit(function (e) {
        e.preventDefault();
        if (window.FormData !== undefined) {
            createEvent();
        } else {
            alert('pas compatible avec navigateur');
        }

        return false;
    });
}

//        createEvent($('input[name=label]').val(),
//                $('input[name=date_start]').val(),
//                $('input[name=date_end]').val(),
//                $('#img_event'));
//        e.preventDefault();
//    });
//}



function readURL(input, id_placeholder_img) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $(id_placeholder_img).attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}










// TODO DELETE this function
function dump(obj) {
    var out = '';
    for (var i in obj) {
        out += i + ": " + obj[i] + "\n";
    }

    alert(out);

    // or, if you wanted to avoid alerts...

    var pre = document.createElement('pre');
    pre.innerHTML = out;
    document.body.appendChild(pre)
}