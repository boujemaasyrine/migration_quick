
var progressTimeInterval;
function calculateReport(progressId, tmpId) {
    $('#export-rapport-link').attr('href', '#');
    $('#export-excel-link').attr('href', '#');
    $("#generate-btn").attr('disabled', 'disabled');

    progressTimeInterval = window.setInterval(function () {
        progressBarSuivi(
            progressId,
            progressTimeInterval,
            '#report-progress',
            function (result) {
                return result.progress + '% (' + result.proceeded + "/" + result.total + " " + Translator.trans('product_label') + ")";
            }, function () {

                ajaxCall({
                    url: Routing.generate('get_result', {'tmp': tmpId})
                }, function (dataReceived) {
                    $("#generate-btn").removeAttr('disabled');
                    $('.btn-export').removeAttr('disabled');
                    $('#data-zone').html(dataReceived.html);
                    $('#export-rapport-link').attr('href', Routing.generate('export_control_stock_pdf', {tmp: tmpId}));
                    $('#export-excel-link').attr('href', Routing.generate('export_excel', {tmp: tmpId}));
                    $('#progress-bars-container').hide();
                    $('#progress-bars-container .progress-bar').css('width', '0%');
                    fixTableHeaderWidth('#constrol-stock-table');
                    fixMyHeader('#constrol-stock-table', function (x) {
                        fixTableHeaderWidth(x);
                    });

                });
            }
        );
    }, 1000);
}

$(function () {

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

    $('#generate-btn').on('click', function () {
        $("#generate-btn").attr('disabled',true);
        $('.btn-export').attr('disabled',true);
        $('#progress').show();
        if ($('#form_startDate').val().trim() == '' || $('#form_endDate').val().trim() == '') {
            return;
        }

        var startMoement = moment($('#form_startDate').val().trim(), "DD/MM/YYYY");
        var endMoment = moment($('#form_endDate').val().trim(), "DD/MM/YYYY");

        if (startMoement.diff(endMoment, 'days') > 0) {
            return;
        }

        ajaxCall({
            url: Routing.generate("control_stock_report"),
            method: 'POST',
            data: $("form[name='form']").serialize(),
            dataType: 'json'
        }, function (receivedData) {
            if(receivedData.error != undefined){
                $('#body').prepend(receivedData.htmlMessage);
            }
            else if (receivedData.progressID != undefined) {
                $('#progress-bars-container').show();
                calculateReport(receivedData.progressID, receivedData.tmpID);
            }
        });
    });

    var today = moment();
    var yesterday = today.subtract(1, 'd');

    var startDay = $('#form_startDate').pickadate('picker');
    var endDay = $('#form_endDate').pickadate('picker');
    startDay.set('disable', [{
        from: -3000, to: [yesterday.year(), yesterday.month(), yesterday.date()]
    }]);
    endDay.set('disable', [{
        from: -3000, to: [yesterday.year(), yesterday.month(), yesterday.date()]
    }]);

});

$(document).on('click', '.cat-tr', function () {
    $(this).siblings(".product-tr[cat-id='" + $(this).attr('cat-id') + "']").slideToggle();
});