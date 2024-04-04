

function loadGraph(graph){
    if(!graph.data('loaded')){
        refreshGraph(graph);
        graph.data('loaded', true);
    }
}

function refreshGraph(graph){
    var extra_data = {};

    if (typeof (graph) !== 'undefined') {
        var $chart_conteneur = graph.find('.chartContainer');
        var $option_conteneur = graph.find('.chartOption');

        if ($.isOk($option_conteneur)) {
            extra_data['idGraph'] = graph.data('name');
            if($option_conteneur.find('form').length > 0){
                extra_data['form']= $option_conteneur.find('form').serialize();
         }
        if (graph.find('input[name=param_filters]').length) {
            extra_data['param_filters'] = graph.find('input[name=param_filters]').val();
        }
         
            setObjectAction(null, {
                module: graph.data('module'),
                object_name: graph.data('object_name'),
                id_object: 0
            }, 'getGraphData2', extra_data, null, function(result){
                eval('var options = '+ result.options);
                $option_conteneur.html(result.formHtml);
                $option_conteneur.find('.btnRefreshGraph').click(function(){refreshGraph(graph)});
                $chart_conteneur.CanvasJSChart(options);
                
            }, {
                no_triggers: true,
                display_processing: false,

            });
        }
    }

}
function toogleDataSeries(e){
    if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
            e.dataSeries.visible = false;
    } else{
            e.dataSeries.visible = true;
    }
    e.chart.render();
}


$(document).ready(function () {
    $('body').on('bimp_ready', function () {
        $('.object_graph').each(function () {
            loadGraph($(this));
        });
    });

    $('body').on('contentLoaded', function (e) {
        $('.object_graph').each(function () {
            loadGraph($(this));
        });
    });
});