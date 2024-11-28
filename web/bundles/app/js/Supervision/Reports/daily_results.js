/**
 * Created by hcherif on 27/05/2016.
 */

$(function () {

    $('.selectize').selectize({
        plugins: ['remove_button']
    });

    $(document).on('change', '#daily_results_startDate', function(){
        var comparableStartDate = $('#daily_results_compareStartDate');
        if ($(this).val() != ''){
            loader.show();
            var date = $(this).val();
            var comparableEndDate = $('#daily_results_compareEndDate');
            var endDate = $('#daily_results_endDate');

            if (comparableEndDate.val() == ''){
                date = date.replace(/\//g, '-');
                ajaxCall({
                        url: Routing.generate('supervision_get_comparable_date',{date : date}),
                        method: GET
                    },
                    function (res) {
                        if (res.errors === undefined) {
                            comparableStartDate.removeAttr('disabled');
                            comparableStartDate.val(res.date);
                        }

                    });
            }
            else{

                var date1 = moment(endDate.val(), "DD/MM/YYYY");
                var date2 = moment(date, "DD/MM/YYYY");
                var diff = date1.diff(date2);

                var comparableEnd = moment(comparableEndDate.val(), "DD/MM/YYYY");
                comparableEnd.subtract(diff, 'milliseconds');
                comparableStartDate.removeAttr('disabled');
                comparableStartDate.val(comparableEnd.format("DD/MM/YYYY"));
            }
            loader.hide();
        }
        else {
            comparableStartDate.val('');
            comparableStartDate.attr('disabled', 'disabled');
        }


    });

    $(document).on('change', '#daily_results_endDate', function(){
        var comparableEndDate = $('#daily_results_compareEndDate');
        if($(this).val() != ''){
            loader.show();
            var date = $(this).val();
            var comparableStartDate = $('#daily_results_compareStartDate');
            var startDate = $('#daily_results_startDate');
            if (comparableStartDate.val() == '') {
                date = date.replace(/\//g, '-');
                ajaxCall({
                        url: Routing.generate('supervision_get_comparable_date', {date: date}),
                        method: GET
                    },
                    function (res) {
                        if (res.errors === undefined) {
                            comparableEndDate.removeAttr('disabled');
                            comparableEndDate.val(res.date);
                        }
                        loader.hide();
                    });
            }
            else{

                var date1 = moment(date, "DD/MM/YYYY");
                var date2 = moment(startDate.val(), "DD/MM/YYYY");
                var diff = date1.diff(date2);

                var comparableStart = moment(comparableStartDate.val(), "DD/MM/YYYY");
                comparableStart.add(diff, 'milliseconds');
                comparableEndDate.removeAttr('disabled');
                comparableEndDate.val(comparableStart.format("DD/MM/YYYY"));
            }
            loader.hide();
        }
        else {
            comparableEndDate.val('');
            comparableEndDate.attr('disabled', 'disabled');
        }

    });

    $(document).on('change', '#daily_results_compareStartDate', function(){
        if ($(this).val() != ''){
            var endDate = $('#daily_results_endDate');
            if(endDate.val() != ''){
                loader.show();
                var date = $(this);
                var comparableEndDate = $('#daily_results_compareEndDate');
                var startDate = $('#daily_results_startDate');
                var date1 = moment(endDate.val(), "DD/MM/YYYY");
                var date2 = moment(startDate.val(), "DD/MM/YYYY");
                var diff = date1.diff(date2);
                var comparableStart = moment(date.val(), "DD/MM/YYYY");
                comparableStart.add(diff, 'milliseconds');
                comparableEndDate.removeAttr('disabled');
                comparableEndDate.val(comparableStart.format("DD/MM/YYYY"));
                loader.hide();
            }
        }

    });

    $(document).on('change', '#daily_results_compareEndDate', function(){
        if ($(this).val() != ''){
            var startDate = $('#daily_results_startDate');
            if(startDate.val() != ''){
                loader.show();
                var date = $(this);
                var comparableStartDate = $('#daily_results_compareStartDate');
                var endDate = $('#daily_results_endDate');
                var date1 = moment(endDate.val(), "DD/MM/YYYY");
                var date2 = moment(startDate.val(), "DD/MM/YYYY");
                var diff = date1.diff(date2);
                var comparableEnd = moment(date.val(), "DD/MM/YYYY");
                comparableEnd.subtract(diff, 'milliseconds');
                comparableStartDate.removeAttr('disabled');
                comparableStartDate.val(comparableEnd.format("DD/MM/YYYY"));
                loader.hide();
            }
        }

    });

});
