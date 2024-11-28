/**
 * Created by mchrif on 29/03/2016.
 */

$(function () {
    function initializeDatePicker() {
        var from = moment($('.count_date').first().attr('data-last-closured-date'), 'YYYY/MM/DD');
        from.add(1, 'days');
        fiscalDatepicker = initDatePicker('.count_date', {
            disable: [
                true,
                {
                    from: new Date(from.format('YYYY/MM/DD')),
                    to: new Date( moment())
                }
            ]
        });
    }
    initializeDatePicker();

    var modalBox = null;
    // TODO : add date constraint based on date closured
    /**
     * Lister cashier/Date modification
     */
    $(document).on('change', '#cashbox_count_date', function () {
        postCountingCashBox();
    });

    $(document).ready(function () {
        processSummaryCalculation();
    });

    function postCountingCashBox() {
        if ($('#count_date').val() != '') {
            var date = $('#cashbox_count_date').val();
            //if (date.split(' ').length === 1) {
            //    $('#cashbox_count_date').val(date + ' 00:00:00')
            //}
            loader.block();
            ajaxCall({
                method: 'POST',
                url: Routing.generate('cashbox_counting', {}),
                data: $('#chest_count_form').serialize()
            }, function (res) {
                if (res.errors === undefined) {
                    $('#cashbox_count_container').html(res.data[0]);
                    syncRealCashBlock();
                }

                initializeDatePicker();
            }, null, function () {
                loader.unblock();
            });
        }
    }

    /**
     * This event will send the form then if success it will show the Chest gap
     */
    $(document).on('click', '#validateChestCounting', function () {
        if(! countingIsValid){
            return;
        }
        var gap = parseFloat($($('.chest_total_gap').first()).text());
        bootbox.confirm(
            {
                title: Translator.trans('chest.validation_confirmation', {}),
                message: Translator.trans('chest.validation_confirmation_body', {gap: "<span class='bold'>" + floatToString(gap) + "<i class='glyphicon glyphicon-euro'></i></span>"}),
                closeButton: false,
                buttons: {
                    'confirm': {
                        label: Translator.trans('keyword.yes'),
                        className: 'btn-validate margin-right-left'
                    },
                    'cancel': {
                        label: Translator.trans('keyword.no'),
                        className: 'btn-default margin-right-left'
                    }
                },
                callback: function (result) {
                    if (result) {
                        $('.exchange_fund_rate_select').removeAttr('disabled');
                        loader.block();
                        $('#chest_count_date').removeAttr('disabled');
                        $('#chest_count_cashboxFund_initialCashboxFunds').removeAttr('disabled');
                        ajaxCall({
                            method: 'POST',
                            url: Routing.generate('validate_chest', {}),
                            data: $('#chest_count_form').serialize()
                        }, function (res) {
                            if (res.errors === undefined) {
                                var status = '';
                                if (res.data.gap > 25) {
                                    status = 'green-text';
                                } else if (res.data.gap < -25) {
                                    status = 'red-text';
                                }
                                var body = "<p style='text-align: center'><h3 style='text-align: center;'>" + Translator.trans('chest.validation_confirmation_body', {gap: "<span class='bold " + status + "'>" + floatToString(gap) + "<i class='glyphicon glyphicon-euro'></i></span>"}) +
                                    "</h3></p>";
                                var footer = "";
                                if (res.data.gap !== 0) {
                                    footer += "<a href='" + res.data.download_url +
                                        "' class='btn btn-print'> " + Translator.trans('btn.print') + "</a>";
                                }
                                footer += "<span id='okBtn' data-small-chest-id='"+ res.data.smallChestId +"' style='margin-right: 5px;' class='btn btn-close'> " + Translator.trans('btn.close') + "</span>";
                                modalBox = showDefaultModal(Translator.trans('chest.chest_count_validated'), body, footer, '98%', '', false);
                            } else {
                                $('#chest_count_container').html(res.data[0]);
                                processSummaryCalculation();
                                initializeDatePicker();
                            }
                        }, null, function () {
                            loader.unblock();
                        });
                    } else {

                    }
                }
            });
    });

    $(document).on('click', '#okBtn', function () {
        loader.block();
        if(inChestCountAdminClosing()) {
            nextStep('comparable_day','chest_count');
        } else {
            nextStep('chest_list','chest_count');
        }
    });

    $(document).on('click', '.delete-row', function () {
        $(this).closest('.row').remove();
        calculateExchangeFundTotal();
    });

    // prevent form from submission when press enter key
    $(window).keydown(function (event) {
        if (event.keyCode == KEY_ENTER) {
            event.preventDefault();
            return false;
        }
    });

    $(document).on('keyup', '.only-number input[type=text]', function(e){
        e.stopPropagation();
        //Disable submit button
        if(e.keyCode ==32){
            $(this).val($.trim($(this).val()));
        }
        if(isNaN($(this).val())){
            $('#validateChestCounting').attr('disabled', true);
            $(this).addClass('shadow-danger');
            $('#chest_preview_table').addClass('shadow-danger');
            countingIsValid = false;

        }else{
            $(this).removeClass('shadow-danger');
            if(! $( ".only-number input[type=text]" ).hasClass( "shadow-danger" )){
                $('#validateChestCounting').attr('disabled', false);
                countingIsValid = true;
                $('#chest_preview_table').removeClass('shadow-danger');
            }
        }
    });
});