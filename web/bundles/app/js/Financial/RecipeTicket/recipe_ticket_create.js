/**
 * Created by mchrif on 21/04/2016.
 */

$(function () {
    //function initializeDatePicker() {
    //
    //    $('.ticketDate').bootstrapMaterialDatePicker({
    //
    //        // enable date picker
    //        date: true,
    //
    //        // enable time picker
    //        time: true,
    //
    //        // custom date format
    //        format: 'DD/MM/YYYY HH:mm:ss',
    //
    //        // min / max date
    //        minDate: null,
    //        maxDate: null,
    //
    //        // current date
    //        currentDate: null,
    //
    //        // Localization
    //        lang: 'fr',
    //
    //        // week starts at
    //        weekStart: 0,
    //
    //        // short time format
    //        shortTime: false,
    //
    //        // text for cancel button
    //        'cancelText': 'Cancel',
    //
    //        // text for ok button
    //        'okText': 'OK'
    //    });
    //
    //    $('.ticketDate').bootstrapMaterialDatePicker('setMinDate', moment().hour(0).minute(0).second(0));
    //    $('.ticketDate').bootstrapMaterialDatePicker('setMaxDate', moment().hour(23).minute(59).second(59));
    //}

    function initializeDatePicker() {
        var from = moment(startDate, 'DD/MM/YYYY');
        var to = new Date( (new Date()));
        ticketDatePicker = initDatePicker('#recipe_ticket_date', {
            disable: [
                true,
                {
                    from:  new Date(from.format('YYYY/MM/DD')),
                    to: to
                }
            ]
        });
    }

    initializeDatePicker();

    $("form[name=recipe_ticket]").on('submit', function () {
        loader.block();
    });
});