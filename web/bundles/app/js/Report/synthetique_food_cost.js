var tmp = null;
var progressTimeInterval;

function getResult() {
    ajaxCall({
        url: Routing.generate('synthetic_foodcost_get_result', {tmp: tmp})
    }, function (receivedData) {
        if (receivedData.html != undefined) {
            //hide porgress bar
            $('#progress-bars-container').hide();
            $('#progress-bars-container').find('#progress-bar').css('width', '0%');

            //inject html
            $('#data-zone').html(receivedData.html);
            $('#generate_foodcost_synthetic').attr('disabled',false);
            $('#generate_foodcost_synthetic_force').attr('disabled',false);
            $('.btn-export').attr('disabled',false);
        }
    });
}

function calculateReport(progressId, tmpId) {
    $('#data-zone').html('');
    progressTimeInterval = window.setInterval(function () {
        progressBarSuivi(
            progressId,
            progressTimeInterval,
            '#report-progress',
            function (result) {
                return result.progress + "%";
            }, function () {
                tmp = tmpId;
                getResult();
            }
        );
    }, 1000);
}

function launch(force) {
    $('#progress').show();
    tmp = null;
    ajaxCall({
            url: Routing.generate('synthetic_foodcost_launch_calcul', {force: force}),
            data: $('#foodCostSynthForm').serialize(),
            method: 'POST'
        },
        function (receivedData) {

            if (receivedData.errors) {
                $('#form-container').html(receivedData.html);
                initDatePicker();
                $('#generate_foodcost_synthetic').attr('disabled', false);
                $('#generate_foodcost_synthetic_force').attr('disabled', false);
                $('.btn-export').attr('disabled', false);
                $('#progress').hide();
            } else if (receivedData.progressID != null && receivedData.tmpID != null) {
                //show progress bar
                $('#progress-bars-container').show();
                //launch suivi
                calculateReport(receivedData.progressID, receivedData.tmpID);
            }
        });
}

$(document).on('click', '#generate_foodcost_synthetic', function () {
    $('#generate_foodcost_synthetic').attr('disabled',true);
    $('#generate_foodcost_synthetic_force').attr('disabled',true);
    $('.btn-export').attr('disabled',true);
    launch(0);
});

$(document).on('click', '#generate_foodcost_synthetic_force', function () {
    $('#generate_foodcost_synthetic').attr('disabled',true);
    $('#generate_foodcost_synthetic_force').attr('disabled',true);
    $('.btn-export').attr('disabled',true);
    launch(1);
});

$(document).on('click', '#export-btn-foodcost', function () {

    if (tmp != null) {
        window.location.href = Routing.generate('print_food_cost_synthetic', {tmp: tmp})
    }

});

$(document).on('click', '#export-excel-btn-foodcost', function () {

    if (tmp != null) {
        window.location.href = Routing.generate('synth_fc_export_excel', {rapport: tmp})
    }

});

$(function () {
    $('#generate_foodcost_synthetic_force').tootltipBootstrap();
    function importTickets() {
        loader.unblock();
        apiLoader.blockApiLoader();
        $.ajax({
            'url': Routing.generate('import_recent_tickets'),
            'success': function () {
                apiLoader.unblockApiLoader();
            }
        });
    }

    importTickets();
});

$(document).on('change', '#form_beginDate , #form_endDate', function(){
    $('#generate_foodcost_synthetic').attr('disabled',false);
    $('#generate_foodcost_synthetic_force').attr('disabled',false);
    $('.btn-export').attr('disabled',false);
});