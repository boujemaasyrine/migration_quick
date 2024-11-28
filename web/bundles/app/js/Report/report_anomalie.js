/**
 * Created by bbarhoumi on 12/04/2016.
 */

$(function () {

    var filters = ['diffCashbox', 'annulations', 'corrections', 'especes', 'titreRestaurant', 'abandons'];
    var sliders = {};

    $.each(filters, function (index, filter) {
        console.log(filterMax[filter], filter);
        sliders[filter] = document.getElementById('range-' + filter);
        noUiSlider.create(sliders[filter], {
            start: [0, 100],
            connect: true,
            step: 0.01,
            range: {
                'min': 0,
                'max': filterMax[filter] > 100 ? filterMax[filter] : 100
            },
            format: wNumb({
                decimals: 0
            })
        });

        var first = document.getElementById('cashbox_counts_anomalies_filter_' + filter + '_firstInput');
        var second = document.getElementById('cashbox_counts_anomalies_filter_' + filter + '_secondInput');

        sliders[filter].noUiSlider.set([first.value, second.value]);
        sliders[filter].noUiSlider.on('update', function (values, handle) {
            if (handle) {
                second.value = values[handle];
                document.getElementById("max-" + filter).innerHTML = Math.round(values[handle] * 100)/ 100;
            } else {
                first.value = values[handle];
                document.getElementById("min-" + filter).innerHTML = Math.round(values[handle] * 100) / 100;
            }
        });
    });

    $('#cashbox_counts_anomalies_filter_startDate').change(function () {
        refreshFilters();
    });

    $('#cashbox_counts_anomalies_filter_endDate').change(function () {
        refreshFilters();
    });

    function refreshFilters() {
        loader.block();

        var postData = $('#anomaliesFilterForm').serializeArray();
        $.ajax({
            type: "POST",
            url: Routing.generate("report_cashbox_counts_anomalies_update"),
            data: postData,
            success: function (returnData) {
                $.each(returnData, function (index, filter) {

                    sliders[index].noUiSlider.destroy();
                    noUiSlider.create(sliders[index], {
                        start: [0, 100],
                        connect: true,
                        step: 0.01,
                        range: {
                            'min': 0,
                            'max': filter['max'] > 100 ? filter['max'] : 100
                        },
                        format: wNumb({
                            decimals: 0
                        })
                    });
                    var first = document.getElementById('cashbox_counts_anomalies_filter_' + index + '_firstInput');
                    var second = document.getElementById('cashbox_counts_anomalies_filter_' + index + '_secondInput');

                    sliders[index].noUiSlider.on('update', function (values, handle) {
                        if (handle) {
                            second.value = values[handle];
                            document.getElementById("max-" + index).innerHTML = Math.round(values[handle]*100)/100;
                        } else {
                            first.value = values[handle];
                            document.getElementById("min-" + index).innerHTML = Math.round(values[handle]*100)/100;
                        }
                    });
                    sliders[index].noUiSlider.set([filter['first'], filter['second']]);
                });
                loader.unblock();
            },
        });
    }
});
