/**
 * Created by mchrif on 01/03/2016.
 */
var datepickerDefaultLanguage = {
    labelMonthNext: Translator.trans('datepicker.labelMonthNext'),
    labelMonthPrev: Translator.trans('datepicker.labelMonthPrev'),
    labelMonthSelect: Translator.trans('datepicker.labelMonthSelect'),
    labelYearSelect: Translator.trans('datepicker.labelYearSelect'),
    monthsFull: [
        Translator.trans('datepicker.monthsFull.1'),
        Translator.trans('datepicker.monthsFull.2'),
        Translator.trans('datepicker.monthsFull.3'),
        Translator.trans('datepicker.monthsFull.4'),
        Translator.trans('datepicker.monthsFull.5'),
        Translator.trans('datepicker.monthsFull.6'),
        Translator.trans('datepicker.monthsFull.7'),
        Translator.trans('datepicker.monthsFull.8'),
        Translator.trans('datepicker.monthsFull.9'),
        Translator.trans('datepicker.monthsFull.10'),
        Translator.trans('datepicker.monthsFull.11'),
        Translator.trans('datepicker.monthsFull.12')
    ],
    monthsShort: [
        Translator.trans('datepicker.monthsShort.1'),
        Translator.trans('datepicker.monthsShort.2'),
        Translator.trans('datepicker.monthsShort.3'),
        Translator.trans('datepicker.monthsShort.4'),
        Translator.trans('datepicker.monthsShort.5'),
        Translator.trans('datepicker.monthsShort.6'),
        Translator.trans('datepicker.monthsShort.7'),
        Translator.trans('datepicker.monthsShort.8'),
        Translator.trans('datepicker.monthsShort.9'),
        Translator.trans('datepicker.monthsShort.10'),
        Translator.trans('datepicker.monthsShort.11'),
        Translator.trans('datepicker.monthsShort.12')
    ],
    weekdaysFull: [
        Translator.trans('datepicker.weekdaysFull.1'),
        Translator.trans('datepicker.weekdaysFull.2'),
        Translator.trans('datepicker.weekdaysFull.3'),
        Translator.trans('datepicker.weekdaysFull.4'),
        Translator.trans('datepicker.weekdaysFull.5'),
        Translator.trans('datepicker.weekdaysFull.6'),
        Translator.trans('datepicker.weekdaysFull.7')
    ],
    weekdaysShort: [
        Translator.trans('datepicker.weekdaysShort.1'),
        Translator.trans('datepicker.weekdaysShort.2'),
        Translator.trans('datepicker.weekdaysShort.3'),
        Translator.trans('datepicker.weekdaysShort.4'),
        Translator.trans('datepicker.weekdaysShort.5'),
        Translator.trans('datepicker.weekdaysShort.6'),
        Translator.trans('datepicker.weekdaysShort.7')
    ],
    weekdaysLetter: [
        Translator.trans('datepicker.weekdaysLetter.1'),
        Translator.trans('datepicker.weekdaysLetter.2'),
        Translator.trans('datepicker.weekdaysLetter.3'),
        Translator.trans('datepicker.weekdaysLetter.4'),
        Translator.trans('datepicker.weekdaysLetter.5'),
        Translator.trans('datepicker.weekdaysLetter.6'),
        Translator.trans('datepicker.weekdaysLetter.7')
    ],
    today: Translator.trans('datepicker.today'),
    clear: Translator.trans('datepicker.clear'),
    close: Translator.trans('datepicker.close')
};

var defaultOptions = {
    selectMonths: true, // Creates a dropdown to control month
    selectYears: 15, // Creates a dropdown of 15 years to control year
    closeOnSelect: true,
    format: 'dd/mm/yyyy',
    onClose: function () {
        $('.datepicker').blur();
        $('.picker').blur();
    },
    onSet: function (arg) {
        if ('select' in arg) { //prevent closing on selecting month/year
            this.close();
        }
    },
    firstDay: 1
};

defaultOptions = $.extend(defaultOptions, datepickerDefaultLanguage);
var initDatePicker = function (selector, options) {
    if (options !== undefined) {
        // check if there is onSet in option
        if (options['onSet'] !== undefined) {
            var oldOnset = options.onSet;
            defaultOptions.onSet = function (arg) {
                oldOnset(arg);
                if ('select' in arg) { //prevent closing on selecting month/year
                    this.close();
                }
            };
        }
        options = $.extend(options, defaultOptions);
    } else {
        options = defaultOptions;
    }
    var picker = null;
    if (selector === undefined) {
        var a = $(".datepicker").pickadate(options);
        picker = $(".datepicker").unbind("focus"), a
    } else {
        var a = $(selector).pickadate(options);
        picker = $(selector).unbind("focus"), a

    }
    return picker;
};
