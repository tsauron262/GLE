

function send_detail(id) {
    var data = {};
    data['typeofobject'] = id;
    BimpAjax('displayDetails', data ,null, {
        display_processing: true,
        success: function (result, bimpAjax) {
//            console.log($('div#selected_object > div.panel-body'));
            $('div#selected_object').empty();
            $('div#selected_object').html(result.html);
        }
    });
}

$(document).ready(function () {
    $('td[name="display_details"]').click(function(){
        send_detail($(this).attr('id'));
    });
});
