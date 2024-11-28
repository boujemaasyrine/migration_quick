var DateRange = {
    DAY_BEFORE: 0,
    TODAY: 1,
    WEEK: 2,
    MONTH: 3,
};
var ticketsCount = false;
var caNetHtva = false;
var ajaxRequests = [];
$(document).ready(function () {

    loadCharts(DateRange.TODAY);
    loadCaPrev(DateRange.TODAY);
    loadCaBrut(DateRange.TODAY);
    loadCaNetHtva(DateRange.TODAY);
    loadCancellation(DateRange.TODAY);
    loadAbandons(DateRange.TODAY);
    loadTicketsCount(DateRange.TODAY);
    loadCorrections(DateRange.TODAY);
    loadNotCountedCashboxCount(DateRange.TODAY);
    loadDiffCashbox(DateRange.TODAY);
    loadTakeOut(DateRange.TODAY);
    loadDrive(DateRange.TODAY);
    loadKiosk(DateRange.TODAY);
    loadDelivery(DateRange.TODAY);

});

function loadCharts(dateRange) {
    var options = {
        series: {
            lines: {
                show: true,
                fill: 0.5
            },
            points: {
                show: true,
                radius: 4
            }
        },
        xaxis: {
            tickColor: '#fcfcfc',
            mode: 'categories',
        },
        grid: {
            borderColor: '#eee',
            borderWidth: 1,
            hoverable: true,
            backgroundColor: '#fcfcfc'
        },
        yAxis: {
            min: 0,
            tickColor: '#eee',
            // position: 'right' or 'left'
            tickFormatter: function (v) {
                return v + ' visitors';
            }
        },
        shadowSize: 0,
        tooltip: true,
        tooltipOpts: {
            content: function (label, x, y) {
                return label + ' : ' + y;
            },
            cssClass: "plot-tooltip"
        }
    };
    var chart2 = $('.chart-area');
    var chart = $('.chart-bar');
    chart.empty();
    chart2.empty();
    chart.waitMe({
        bg: false,
        color: '#23b7e5'
    });
    chart2.waitMe({
        bg: false,
        color: '#23b7e5'
    });
    ajaxRequests.push(
        $.ajax({
            type: "GET",
            contentType: 'application/json; charset=utf-8',
            data: {date_range: dateRange},
            dataType: 'json',
            url: Routing.generate('dashboard_hour_by_hour'),
            error: function () {
                console.log("An error occurred.");
            },
            success: function (data) {
                var limit_hour = 0;
                var closing_hour = parseInt(data.closing_hour),
                    opening_hour = parseInt(data.opening_hour);
                if (closing_hour > data.opening_hour) {
                    limit_hour = closing_hour;
                } else {
                    limit_hour = 23;
                }

                var data_ca_prev = [], data_ca_brut = [];
                // CA Prev
                for (var i = opening_hour; i <= limit_hour; i++) {
                    data_ca_prev.push([i + ':00', parseFloat(data['hourByHour']['ca_prev'][i]).toFixed(2)]);
                }
                if (closing_hour < opening_hour)
                    for (var i = 0; i <= closing_hour; i++) {
                        data_ca_prev.push([i + ':00', parseFloat(data['hourByHour']['ca_prev'][i]).toFixed(2)]);
                    }
                // CA Brut
                for (var j = opening_hour; j <= limit_hour; j++) {
                    data_ca_brut.push([j + ':00', data['hourByHour']['ca'][j].indexOf('*') >= 0 ? "0.00" : parseFloat(data['hourByHour']['ca'][j]).toFixed(2)]);
                }
                if (closing_hour < opening_hour)
                    for (var j = 0; j <= closing_hour; j++) {
                        data_ca_brut.push([j + ':00', data['hourByHour']['ca'][j].indexOf('*') >= 0 ? "0.00" : parseFloat(data['hourByHour']['ca'][j]).toFixed(2)]);
                    }

                var data2 = [
                        {
                            "label": Translator.trans('report.sales.hour_by_hour.ca_prev') + " (€)",
                            "color": "#9cd159",
                            "data": data_ca_prev
                        }, {
                            "label": Translator.trans('report.sales.hour_by_hour.ca_brut') + " (€)",
                            "color": "#2f80e7",
                            "data": data_ca_brut
                        }
                    ]
                ;
                chart.waitMe("hide");
                if (chart.length)
                    $.plot(chart, data2, options);

            }
        })
    );
    ajaxRequests.push(
        $.ajax({
            type: "GET",
            contentType: 'application/json; charset=utf-8',
            data: {date_range: dateRange},
            dataType: 'json',
            url: Routing.generate('dashboard_ca_budget'),
            error: function () {
                console.log("An error occurred.");
            },
            success: function (data) {
                var data_ca = [], data_ca_one = [];
                for (var i = 0; i < data.length; i++) {
                    data_ca.push([data[i]['day'], parseFloat(data[i]['caNetHt']).toFixed(2)]);
                }
                for (var i = 0; i < data.length; i++) {
                    data_ca_one.push([data[i]['day'], parseFloat(data[i]['caNetNOne']).toFixed(2)]);
                }
                var data2 = [
                        {
                            "label": Translator.trans('ca_net_htva', 'message'),
                            "color": "#9cd159",
                            "data": data_ca
                        },
                        {
                            "label": Translator.trans('ca_net_htva_n-1', 'message'),
                            "color": "#2f80e7",
                            "data": data_ca_one
                        }
                    ]
                ;
                chart2.waitMe("hide");
                if (chart2.length)
                    $.plot(chart2, data2, options);
            }
        })
    );
}

function refreshWidget(funcName) {
    var fn = window[funcName];
    if (typeof fn === "function") fn();
}

$(document).on("click", ".btn-refresh", function () {
    var t = $(this).data("func");
    refreshWidget(t);
});


function loadCaPrev(dateRange) {
    var container = $('#daily_budget_container');
    var refresh_btn = "<button class='btn-xs white btn-refresh' data-func='loadCaPrev'><i class='fa fa-refresh' aria-hidden='true'></i>" + Translator.trans('refresh') + "</button>";
    container.empty();
    container.waitMe({
        bg: false,
        color: '#ffffff'
    });
    ajaxRequests.push(
        $.ajax({
            type: "GET",
            contentType: 'application/json; charset=utf-8',
            data: {date_range: dateRange},
            dataType: 'json',
            url: Routing.generate('dashboard_ca_prev_api'),
            error: function () {
                console.log("An error occurred.");
                container.append(refresh_btn);
            },
            success: function (result) {
                if (result.status == 1) {
                    container.append("<div class='h2 mt0'>" + parseInt(result.ca_prev) + " € </div><div class='text-uppercase'>" + Translator.trans('dayly_budget') + "</div>");
                } else {
                    console.log("An error occurred.");
                    container.append(refresh_btn);
                }
            },
            complete: function () {
                container.waitMe("hide");
            }
        })
    );
}

function loadCaBrut(dateRange) {
    var container = $('#ca_brut_ttc_container');
    var refresh_btn = "<button class='btn-xs white btn-refresh' data-func='loadCaBrut'><i class='fa fa-refresh' aria-hidden='true'></i>" + Translator.trans('refresh') + "</button>";
    container.empty();
    container.waitMe({
        bg: false,
        color: '#ffffff'
    });
    ajaxRequests.push(
        $.ajax({
            type: "GET",
            contentType: 'application/json; charset=utf-8',
            data: {date_range: dateRange},
            dataType: 'json',
            url: Routing.generate('dashboard_ca_brut_api'),
            error: function () {
                console.log("An error occurred.");
                container.append(refresh_btn);
            },
            success: function (result) {
                if (result.status == 1) {
                    if (isNaN(result.caBrut)) {
                        result.caBrut = 0;
                    }
                    container.append("<div class='h2 mt0'>" + parseInt(result.caBrut) + " € </div><div class='text-uppercase'>" + Translator.trans('ca_brut_ttc') + "</div>");
                } else {
                    console.log("An error occurred.");
                    container.append(refresh_btn);
                }
            },
            complete: function () {
                container.waitMe("hide");
            }
        })
    );
}

function loadCaNetHtva(dateRange) {
    var container = $('#ca_net_htva_container');
    var refresh_btn = "<button class='btn-xs white white btn-refresh' data-func='loadCaNetHtva'><i class='fa fa-refresh' aria-hidden='true'></i>" + Translator.trans('refresh') + "</button>";
    container.empty();
    container.waitMe({
        bg: false,
        color: '#ffffff'
    });
    caNetHtva = false;
    ajaxRequests.push(
        $.ajax({
            type: "GET",
            contentType: 'application/json; charset=utf-8',
            data: {date_range: dateRange},
            dataType: 'json',
            url: Routing.generate('dashboard_ca_net_htva_api'),
            error: function () {
                console.log("An error occurred.");
                container.append(refresh_btn);
            },
            success: function (result) {
                if (result.status == 1) {
                    if (isNaN(result.caNetHTva)) {
                        result.caNetHTva = 0;
                    }
                    container.append("<div class='h2 mt0'>" + parseInt(result.caNetHTva) + " € </div><div class='text-uppercase'>" + Translator.trans('ca_net_htva') + "</div>");
                    caNetHtva = parseInt(result.caNetHTva);
                } else {
                    console.log("An error occurred.");
                    container.append(refresh_btn);
                }
            },
            complete: function () {
                container.waitMe("hide");
            }
        })
    );
}

function loadCancellation(dateRange) {
    var container = $('#cancels_container');
    var refresh_btn = "<button class='btn-xs white btn-refresh' data-func='loadCancellation'><i class='fa fa-refresh' aria-hidden='true'></i>" + Translator.trans('refresh') + "</button>";
    container.empty();
    container.waitMe({
        bg: false,
        color: '#ffffff'
    });
    ajaxRequests.push(
        $.ajax({
            type: "GET",
            contentType: 'application/json; charset=utf-8',
            data: {date_range: dateRange},
            dataType: 'json',
            url: Routing.generate('dashboard_cancellation_api'),
            error: function () {
                console.log("An error occurred.");
                container.append(refresh_btn);
            },
            success: function (result) {
                if (result.status == 1) {
                    container.append("<div class='h2 mt0'>" + parseInt(result.cancellations_count) + " (" + parseInt(result.cancellations_value) + " € )</div><div class='text-uppercase'>" + Translator.trans('cancels') + "</div>");
                } else {
                    console.log("An error occurred.");
                    container.append(refresh_btn);
                }
            },
            complete: function () {
                container.waitMe("hide");
            }
        })
    );
}

function loadAbandons(dateRange) {
    var container = $('#abandons_container');
    var refresh_btn = "<button class='btn-xs white btn-refresh' data-func='loadAbandons'><i class='fa fa-refresh' aria-hidden='true'></i>" + Translator.trans('refresh') + "</button>";
    container.empty();
    container.waitMe({
        bg: false,
        color: '#ffffff'
    });
    ajaxRequests.push(
        $.ajax({
            type: "GET",
            contentType: 'application/json; charset=utf-8',
            data: {date_range: dateRange},
            dataType: 'json',
            url: Routing.generate('dashboard_abandons_api'),
            error: function () {
                console.log("An error occurred.");
                container.append(refresh_btn);
            },
            success: function (result) {
                if (result.status == 1) {
                    container.append("<div class='h2 mt0'>" + parseInt(result.abandons_count) + " (" + parseInt(result.abandons_value) + " € )</div><div class='text-uppercase'>" + Translator.trans('abandons') + "</div>");
                } else {
                    console.log("An error occurred.");
                    container.append(refresh_btn);
                }
            },
            complete: function () {
                container.waitMe("hide");
            }
        })
    );
}

function loadCorrections(dateRange) {
    var container = $('#corrections_container');
    var refresh_btn = "<button class='btn-xs white btn-refresh' data-func='loadCorrections'><i class='fa fa-refresh' aria-hidden='true'></i>" + Translator.trans('refresh') + "</button>";
    container.empty();
    container.waitMe({
        bg: false,
        color: '#ffffff'
    });
    ajaxRequests.push(
        $.ajax({
            type: "GET",
            contentType: 'application/json; charset=utf-8',
            data: {date_range: dateRange},
            dataType: 'json',
            url: Routing.generate('dashboard_corrections_api'),
            error: function () {
                console.log("An error occurred.");
                container.append(refresh_btn);
            },
            success: function (result) {
                if (result.status == 1) {
                    container.append("<div class='h2 mt0'>" + parseInt(result.corrections_count) + " (" + parseInt(result.corrections_value) + " € )</div><div class='text-uppercase'>" + Translator.trans('corrections') + "</div>");
                } else {
                    console.log("An error occurred.");
                    container.append(refresh_btn);
                }
            },
            complete: function () {
                container.waitMe("hide");
            }
        })
    );
}

function loadTicketsCount(dateRange) {
    var container = $('#tickets_count_container');
    var refresh_btn = "<button class='btn-xs white btn-refresh' data-func='loadTicketsCount'><i class='fa fa-refresh' aria-hidden='true'></i>" + Translator.trans('refresh') + "</button>";
    container.empty();
    container.waitMe({
        bg: false,
        color: '#ffffff'
    });
    $('#avg_net_ticket_container').waitMe({
        bg: false,
        color: '#ffffff'
    });
    ticketsCount = false;
    ajaxRequests.push(
        $.ajax({
            type: "GET",
            contentType: 'application/json; charset=utf-8',
            data: {date_range: dateRange},
            dataType: 'json',
            url: Routing.generate('dashboard_tickets_count_api'),
            error: function () {
                console.log("An error occurred.");
                container.append(refresh_btn);
            },
            success: function (result) {
                if (result.status == 1) {
                    container.append("<div class='h2 mt0'>" + parseInt(result.tickets_count) + "</div><div class='text-uppercase'>" + Translator.trans('report.sales.hour_by_hour.tickets') + "</div>");
                    ticketsCount = parseInt(result.tickets_count);
                } else {
                    console.log("An error occurred.");
                    container.append(refresh_btn);
                }
            },
            complete: function () {
                container.waitMe("hide");
            }
        })
    );
}

function loadNotCountedCashboxCount(dateRange) {
    var container = $('#not_counted_cash_box_container');
    var refresh_btn = "<button class='btn-xs white btn-refresh' data-func='loadNotCountedCashboxCount'><i class='fa fa-refresh' aria-hidden='true'></i>" + Translator.trans('refresh') + "</button>";
    container.empty();
    container.waitMe({
        bg: false,
        color: '#ffffff'
    });
    ajaxRequests.push(
        $.ajax({
            type: "GET",
            contentType: 'application/json; charset=utf-8',
            data: {date_range: dateRange},
            dataType: 'json',
            url: Routing.generate('dashboard_not_counted_cashbox_api'),
            error: function () {
                console.log("An error occurred.");
                container.append(refresh_btn);
            },
            success: function (result) {
                if (result.status == 1) {
                    container.append("<div class='h2 mt0'>" + parseInt(result.not_counted_cashbox_count) + "</div><div class='text-uppercase'>" + Translator.trans('not_counted_cash_box') + "</div>");
                } else {
                    console.log("An error occurred: " + result.message);
                    container.append(refresh_btn);
                }
            },
            complete: function () {
                container.waitMe("hide");
            }
        })
    );
}

function loadTakeOut(dateRange) {
    var container = $('#takeout_container');
    var refresh_btn = "<button class='btn-xs btn-refresh' data-func='loadTakeOut'><i class='fa fa-refresh' aria-hidden='true'></i>" + Translator.trans('refresh') + "</button>";
    container.empty();
    container.waitMe({
        bg: false,
        color: '#23b7e5'
    });
    ajaxRequests.push(
        $.ajax({
            type: "GET",
            contentType: 'application/json; charset=utf-8',
            data: {date_range: dateRange},
            dataType: 'json',
            url: Routing.generate('dashboard_takeout_percentage_api'),
            error: function () {
                console.log("An error occurred.");
                container.append(refresh_btn);
            },
            success: function (result) {
                if (result.status == 1) {
                    container.append("<div class='panel-body' style='padding: 3px !important;'><h4 class='mt0'>" + result.takeout_percentage.toFixed(2) + "%</h4><p class='mb0 text-muted'>" + Translator.trans('takeout_comptoir') + "</p></div>");
                } else {
                    console.log("An error occurred: " + result.message);
                    container.append(refresh_btn);
                }
            },
            complete: function () {
                container.waitMe("hide");
            }
        })
    );
}

function loadDiffCashbox(dateRange) {
    var container = $('#diff_cashbox_container');
    var refresh_btn = "<button class='btn-xs btn-refresh' data-func='loadDiffCashbox'><i class='fa fa-refresh' aria-hidden='true'></i>" + Translator.trans('refresh') + "</button>";
    container.empty();
    container.waitMe({
        bg: false,
        color: '#23b7e5'
    });
    ajaxRequests.push(
        $.ajax({
            type: "GET",
            contentType: 'application/json; charset=utf-8',
            data: {date_range: dateRange},
            dataType: 'json',
            url: Routing.generate('dashboard_day_income_api'),
            error: function (error) {
                console.log("An error occurred.");
                console.log(error);
                container.append(refresh_btn);
            },
            success: function (result) {
                if (result.status == 1) {
                    container.append("<h4 class='mt0'>" + parseFloat(result.diff_cashbox).toFixed(2) + "  <small>€</small> </h4><p class='mb0 text-muted'>" + Translator.trans('diff_cashbox') + "</p>");
                } else {
                    console.log("An error occurred: " + result.message);
                    container.append(refresh_btn);
                }
            },
            complete: function () {
                container.waitMe("hide");
            }
        })
    );
}

function loadDrive(dateRange) {
    var container = $('#drive_container');
    var refresh_btn = "<button class='btn-xs btn-refresh' data-func='loadDrive'><i class='fa fa-refresh' aria-hidden='true'></i>" + Translator.trans('refresh') + "</button>";
    container.empty();
    container.waitMe({
        bg: false,
        color: '#23b7e5'
    });
    ajaxRequests.push(
        $.ajax({
            type: "GET",
            contentType: 'application/json; charset=utf-8',
            data: {date_range: dateRange},
            dataType: 'json',
            url: Routing.generate('dashboard_drive_percentage_api'),
            error: function () {
                console.log("An error occurred.");
                container.append(refresh_btn);
            },
            success: function (result) {
                if (result.status == 1) {
                    container.append("<div class='panel-body' style='padding: 3px !important;'><h4 class='mt0'>" + result.drive_percentage.toFixed(2) + "%</h4><p class='mb0 text-muted'>" + Translator.trans('drive') + "</p></div>");
                } else {
                    console.log("An error occurred: " + result.message);
                    container.append(refresh_btn);
                }
            },
            complete: function () {
                container.waitMe("hide");
            }
        })
    );
}

function loadKiosk(dateRange) {
    var container = $('#kiosk_container');
    var refresh_btn = "<button class='btn-xs btn-refresh' data-func='loadKiosk'><i class='fa fa-refresh' aria-hidden='true'></i>" + Translator.trans('refresh') + "</button>";
    container.empty();
    container.waitMe({
        bg: false,
        color: '#23b7e5'
    });
    ajaxRequests.push(
        $.ajax({
            type: "GET",
            contentType: 'application/json; charset=utf-8',
            data: {date_range: dateRange},
            dataType: 'json',
            url: Routing.generate('dashboard_kiosk_percentage_api'),
            error: function () {
                console.log("An error occurred.");
                container.append(refresh_btn);
            },
            success: function (result) {
                if (result.status == 1) {
                    container.append("<div class='panel-body' style='padding: 3px !important;'><h4 class='mt0'>" + result.kiosk_percentage.toFixed(2) + "%</h4><p class='mb0 text-muted'>" + Translator.trans('kiosk_out') + "</p></div>");
                } else {
                    console.log("An error occurred: " + result.message);
                    container.append(refresh_btn);
                }
            },
            complete: function () {
                container.waitMe("hide");
            }
        })
    );
}

function loadDelivery(dateRange) {
    var container = $('#delivery_container');
    var refresh_btn = "<button class='btn-xs btn-refresh' data-func='loadDelivery'><i class='fa fa-refresh' aria-hidden='true'></i>" + Translator.trans('refresh') + "</button>";
    container.empty();
    container.waitMe({
        bg: false,
        color: '#23b7e5'
    });
    ajaxRequests.push(
        $.ajax({
            type: "GET",
            contentType: 'application/json; charset=utf-8',
            data: {date_range: dateRange},
            dataType: 'json',
            url: Routing.generate('dashboard_delivery_percentage_api'),
            error: function () {
                console.log("An error occurred.");
                container.append(refresh_btn);
            },
            success: function (result) {
                if (result.status == 1) {
                    container.append("<div class='panel-body' style='padding: 3px !important;'><h4 class='mt0'>" + result.delivery_percentage.toFixed(2) + "%</h4><p class='mb0 text-muted'>" + Translator.trans('delivery') + "</p></div>");
                } else {
                    console.log("An error occurred: " + result.message);
                    container.append(refresh_btn);
                }
            },
            complete: function () {
                container.waitMe("hide");
            }
        })
    );
}

$('.segmented-control').on("click", "#day_before_btn", function () {
    abortAllAjax();
    $('#avg_net_ticket_container').empty();
    refreshId = setInterval(calculateTM, 500);
    loadCharts(DateRange.DAY_BEFORE);
    loadCaPrev(DateRange.DAY_BEFORE);
    loadCaBrut(DateRange.DAY_BEFORE);
    loadCaNetHtva(DateRange.DAY_BEFORE);
    loadCancellation(DateRange.DAY_BEFORE);
    loadAbandons(DateRange.DAY_BEFORE);
    loadTicketsCount(DateRange.DAY_BEFORE);
    loadCorrections(DateRange.DAY_BEFORE);
    loadNotCountedCashboxCount(DateRange.DAY_BEFORE);
    loadDiffCashbox(DateRange.DAY_BEFORE);
    loadTakeOut(DateRange.DAY_BEFORE);
    loadDrive(DateRange.DAY_BEFORE);
    loadKiosk(DateRange.DAY_BEFORE);
    loadDelivery(DateRange.DAY_BEFORE);
});

$('.segmented-control').on("click", "#today_btn", function () {
    abortAllAjax();
    $('#avg_net_ticket_container').empty();
    refreshId = setInterval(calculateTM, 500);
    loadCharts(DateRange.TODAY);
    loadCaPrev(DateRange.TODAY);
    loadCaBrut(DateRange.TODAY);
    loadCaNetHtva(DateRange.TODAY);
    loadCancellation(DateRange.TODAY);
    loadAbandons(DateRange.TODAY);
    loadTicketsCount(DateRange.TODAY);
    loadCorrections(DateRange.TODAY);
    loadNotCountedCashboxCount(DateRange.TODAY);
    loadDiffCashbox(DateRange.TODAY);
    loadTakeOut(DateRange.TODAY);
    loadDrive(DateRange.TODAY);
    loadKiosk(DateRange.TODAY);
    loadDelivery(DateRange.TODAY);
});

$('.segmented-control').on("click", "#this_week_btn", function () {
    abortAllAjax();
    $('#avg_net_ticket_container').empty();
    refreshId = setInterval(calculateTM, 500);
    loadCharts(DateRange.WEEK);
    loadCaPrev(DateRange.WEEK);
    loadCaBrut(DateRange.WEEK);
    loadCaNetHtva(DateRange.WEEK);
    loadCancellation(DateRange.WEEK);
    loadAbandons(DateRange.WEEK);
    loadTicketsCount(DateRange.WEEK);
    loadCorrections(DateRange.WEEK);
    loadNotCountedCashboxCount(DateRange.WEEK);
    loadDiffCashbox(DateRange.WEEK);
    loadTakeOut(DateRange.WEEK);
    loadDrive(DateRange.WEEK);
    loadKiosk(DateRange.WEEK);
    loadDelivery(DateRange.WEEK);
});

$('.segmented-control').on("click", "#this_month_btn", function () {
    abortAllAjax();
    $('#avg_net_ticket_container').empty();
    refreshId = setInterval(calculateTM, 500);
    loadCharts(DateRange.MONTH);
    loadCaPrev(DateRange.MONTH);
    loadCaBrut(DateRange.MONTH);
    loadCaNetHtva(DateRange.MONTH);
    loadCancellation(DateRange.MONTH);
    loadAbandons(DateRange.MONTH);
    loadTicketsCount(DateRange.MONTH);
    loadCorrections(DateRange.MONTH);
    loadNotCountedCashboxCount(DateRange.MONTH);
    loadDiffCashbox(DateRange.MONTH);
    loadTakeOut(DateRange.MONTH);
    loadDrive(DateRange.MONTH);
    loadKiosk(DateRange.MONTH);
    loadDelivery(DateRange.MONTH);
});

function calculateTM() {
    if ($.isNumeric(ticketsCount) && $.isNumeric(caNetHtva)) {
        clearInterval(refreshId);
        var container = $('#avg_net_ticket_container');
        container.empty();
        var avg;
        if(ticketsCount>0){
            avg = caNetHtva / ticketsCount;
            avg=avg.toFixed(2);
        }else{
            avg='---';
        }
        container.append("<div class='h2 mt0'>" + avg + " (€) </div><div class='text-uppercase'>" + Translator.trans('report.daily_result.avg_net_ticket') + "</div>");
        container.waitMe("hide");
    }
}

var refreshId = setInterval(calculateTM, 500);

function abortAllAjax() {
    for (var i = 0; i < ajaxRequests.length; i++) {
        ajaxRequests[i].abort();
    }
    ajaxRequests = [];
}
