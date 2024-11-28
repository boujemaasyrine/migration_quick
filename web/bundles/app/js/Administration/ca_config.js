/**
 * Created by anouira on 12/04/2016.
 */

var calendar = null;
var currentCa = null;
var noComprabableDays = null;

function saveCa(caId, val, btn) {
    ajaxCall({
        url: Routing.generate('save_single_ca', {'ca': caId}),
        dataType: 'json',
        method: 'post',
        data: {
            ca: val
        }
    }, function (recievedData) {
        if (recievedData.data == null) {
            showPopError(recievedData.error);
        } else {
            $(btn).hide('slow');
            $(btn).siblings('.ca_inputs').attr('hidden-value',val);
            Materialize.toast("<span class='glyphicon glyphicon-ok'></span> &nbsp;&nbsp; Enregistré avec succès", 4000, "confirmation-toast")
        }
    })
}

function testDOMNumeric(dom) {
    if ($(dom).val().trim() == '' ||
        isNaN($(dom).val().trim()) ||
        parseInt($(dom).val().trim()) < 0) {
        $(dom).focus();
        $(dom).addClass('shadow-danger');
        return false;
    }
    return true;
}

function testCa() {
    return testDOMNumeric('#ca_typed');
}

function isNoComparableDay(day) {
    for (var i = 0; i < noComprabableDays.length; i++) {
        if (noComprabableDays[i].date == day) {
            return true;
        }
    }
    return false;
}

function initPickers() {
    $.each($('.datepicker'), function (key, value) {
        var picker = $(value).pickadate('picker');
        var self = $(this);
        picker.on('start', $('.picker').appendTo('body'));
        picker.on('open', function () {
            $.each(noComprabableDays, function (key, value) {
                addClassToDate(self.attr('id'), value.date, 'no-comprable-day');
            })

        })

        picker.on('render', function () {
            $.each(noComprabableDays, function (key, value) {
                addClassToDate(self.attr('id'), value.date, 'no-comprable-day');
            })
        })
    });
}

function addClassToDate(inputDateId, date, className) {
    var day = $("#" + inputDateId + "_table.picker__table").find(".picker__day.picker__day--infocus[aria-label='" + date + "']");
    console.log(day.length)
    if (day.length == 0 || day == null) {
        return;
    }
    day.addClass(className);
}

$(document).on('click', '.save-ca-btn', function () {
    $(this).siblings('input').removeClass('shadow-danger');
    if (testDOMNumeric($(this).siblings('input'))) {
        var caId = $(this).attr('ca-id');
        var val = $(this).siblings('input').val();
        saveCa(caId, val, $(this));
    }
});

$(document).on('click', ".detail-btn", function () {
    loader.show();
    var self = $(this);
    var dateToDisplay = self.siblings('input').attr('date');
    ajaxCall({
        url: Routing.generate('details_ca_prev', {'caPrev': self.attr('ca-id')}),
        dataType: 'json'
    }, function (data) {
        currentCa = self.attr('ca-id');
        showDefaultModal(Translator.trans('pop_details_title'), data.data, '', '70%');
        initDatePicker();
        loader.hide();
        initPickers();

        $.each($('.datepicker'), function (key, value) {
            if (isNoComparableDay($(value).val())) {
                $(value).addClass('shadow-danger');
            }
        });

    });
});

$(document).on('change', '#ca_typed', function () {
    $('#ca_typed').removeClass('shadow-danger');
    if (testCa()) {
        $('#ca_to_be_sended_calculated').removeAttr('checked');
        $('#ca_to_be_sended_typed').prop('checked', 'checked');
    }
});

$(document).on('change', '#comparable_day, #date_1, #date_2, #date_3, #date_4, #date_5, #date_6, #date_7, #date_8', function () {
    $("#ca-detailed-form input.datepicker").removeClass('shadow-danger');
    var ok = true;
    $.each($("#ca-detailed-form input.datepicker"), function (key, value) {
        if ($(value).val().trim() == '') {
            $(value).addClass('shadow-danger');
            ok = false;
        }
    });

    if (ok) {
        var self = $(this);
        $('#refresh-icon').show();
        rotateElement($('#refresh-icon'));
        $('#ca_to_be_sended_typed').removeAttr('checked');
        $('#ca_to_be_sended_calculated').prop('checked', 'checked');
        ajaxCall({
            url: Routing.generate('ca_refresh_data'),
            data: $('#ca-detailed-form').serialize(),
            method: 'POST'
        }, function (recievedData) {
            $('#refresh-icon').hide();
            rotateElement($('#refresh-icon'),1);
            if (recievedData.data.ca != null) {
                $('#calculated-ca').html(Math.round(recievedData.data.ca) + " (€)");
                $('#ca-1').html(floatToString(recievedData.data.cas[0]));
                $('#ca-2').html(floatToString(recievedData.data.cas[1]));
                $('#ca-3').html(floatToString(recievedData.data.cas[2]));
                $('#ca-4').html(floatToString(recievedData.data.cas[3]));
                $('#ca-5').html(floatToString(recievedData.data.cas[4]));
                $('#ca-6').html(floatToString(recievedData.data.cas[5]));
                $('#ca-7').html(floatToString(recievedData.data.cas[6]));
                $('#ca-8').html(floatToString(recievedData.data.cas[7]));
                $('#m-1').html(floatToString(recievedData.data.cas.m1));
                $('#m-2').html(floatToString(recievedData.data.cas.m2));
                $('#variance').html(floatToString(recievedData.data.variance));
                $('#ca_comparable_day').html(floatToString(recievedData.data.cas.comparableDayCa) + " (€)");

            }
        });
    }
});

$(document).on('click', '#save-ca', function () {
    if (testCa()) {
        ajaxCall({
            url: Routing.generate('save_ca', {'ca': currentCa}),
            method: 'POST',
            data: $('#ca-detailed-form').serialize()
        }, function (recievedData) {
            if (recievedData.data == null) {
                console.log(recievedData.error);
            } else {
                window.location.reload();
            }
        });
    }
});

$(document).on('keyup','.ca_inputs',function(){

    if (hasTheModificatioRight != true){
        return;
    }

    if ($(this).val().trim() != $(this).attr('hidden-value')){
        $(this).siblings('.save-ca-btn').show();
    }else{
        $(this).siblings('.save-ca-btn').hide();
    }
});

var hasTheModificatioRight = null;
$(document).ready(function () {
    calendar = $('#calendar').fullCalendar({
        weekMode: 'liquid',
        header: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        lang: $('html').attr('lang'),
        height: 800,
        firstDay: 1,
        defaultView: 'month',
        buttonText: {
            today: Translator.trans('today')
        },
        eventRender: function (event, element, view) {
            var dateMoement = moment(event.date, "YYYY-MM-DD");
            var input = "<input hidden-value='" + Math.round(event.ca) + "' date='" + dateMoement.format('DD/MM/YYYY') + "' class='ca_inputs form-control' name=ca[" + event.date + "] type='text'  value='" + Math.round(event.ca) + "' >";
            var btnDetails = "<button ca-id='" + event.id + "' type='button' class='btn blue detail-btn'><span class='glyphicon glyphicon-eye-open'></span> <span class='ca-details'>" + Translator.trans('see_label') + "</span></button>";
            var btnSave = "<button style='display:none;' ca-id='" + event.id + "' type='button' class='btn green save-ca-btn'><span class='glyphicon glyphicon-ok'></span> <span class='ca-save-label'>" + Translator.trans('btn.validate') + "</span></button>";

            return "<div class='inputs-btns-container'>" + btnDetails + input + btnSave + "</div>";

        },
        events: Routing.generate('ca_json_list')
    });


    //GET NO COMPRABLE DAYS
    ajaxCall({
        url: Routing.generate('no_comparable_days')
    }, function (receivedData) {
        noComprabableDays = receivedData.data;
    });

    hasTheModificatioRight = hasTheRightFor('bud_prev_edit');

});
