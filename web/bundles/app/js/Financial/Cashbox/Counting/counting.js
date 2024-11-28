/**
 * Created by mchrif on 29/03/2016.
 */

$(function () {

    function initializeDatePicker() {
        var from = moment($('.cashbox_date').first().attr('data-last-closured-date'), 'YYYY/MM/DD');
        var to = moment($('.cashbox_date').first().attr('data-max-date'), 'YYYY/MM/DD');
        fiscalDatepicker = initDatePicker('.cashbox_date', {
            disable: [
                true,
                {
                    from: new Date(from.format('YYYY/MM/DD')),
                    to: new Date(to.format('YYYY/MM/DD'))
                }
            ]
        });
    }
    initializeDatePicker();

    function importTickets() {
        loader.unblock();
        apiLoader.blockApiLoader();
        $.ajax({
            'url': Routing.generate('import_last_working_date_tickets'),
            'success': function () {
                apiLoader.unblockApiLoader();
            }
        });
    }
    importTickets();

    $(document).on('click', '#reload', function () {
        if(! cashBoxCountingIsValid){
            return;
        }
        loader.block();
        ajaxCall({
            method: 'POST',
            url: Routing.generate('cashbox_counting', {}),
            data: $('#cahsbox_count_form').serialize()
        }, function (res) {
            if (res.errors === undefined) {
                $('#cashbox_count_container').html(res.data[0]);
                syncRealCashBlock();
            }

            initializeDatePicker();
        }, null, function () {
            loader.unblock();
        });
        importTickets();
    });

    var modalBox = null;

    // TODO : add date constraint based on date closured

    /**
     * Lister cashier/Date modification
     */

    $(document).on('change', '#cashbox_count_date', function () {
        postCountingCashBox();
    });

    $(document).on('change', '#cashbox_count_cashier', function () {
        postCountingCashBox();
    });

    function postCountingCashBox() {
        if ($('#cashbox_count_date').val() != '') {
            var date = $('#cashbox_count_date').val();
            //if (date.split(' ').length === 1) {
            //    $('#cashbox_count_date').val(date + ' 00:00:00')
            //}
            loader.block();
            ajaxCall({
                method: 'POST',
                url: Routing.generate('cashbox_counting', {}),
                data: $('#cahsbox_count_form').serialize()
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
     * This event will send the form then if success it will show the Cashbox gap
     */
    //$(document).on('click', '#validateCashBoxCounting', function () {
    //    if ($('#cashbox_count_cashier').val() != '') {
    //        loader.block();
    //        ajaxCall({
    //            method: 'POST',
    //            url: Routing.generate('gap_cashbox_count', {}),
    //            data: $('#cahsbox_count_form').serialize()
    //        }, function (res) {
    //            if (res.errors === undefined) {
    //                modalBox = showDefaultModal(Translator.trans('cashbox.gap.title'), res.data[0], res.data['footer'], '98%', '', false);
    //            } else {
    //                $('#cashbox_count_container').html(res.data[0]);
    //                syncRealCashBlock();
    //                initializeDatePicker();
    //            }
    //        }, null, function () {
    //            loader.unblock();
    //        });
    //    }
    //});

    /**
     * This event will validate the cashbox gap and persist the counting
     */
    $(document).on('click', '#validateCashboxGap', function () {
        if(! cashBoxCountingIsValid){
            return;
        }
        if ($('#cashbox_count_cashier').val() != '') {
            var gap = parseFloat($($('.total_gap').first()).text());
            bootbox.confirm(
                {
                    title: Translator.trans('cashbox.validation_confirmation', {}),
                    message: Translator.trans('cashbox.validation_confirmation_body', {gap: "<span class='bold'>" + floatToString(gap) + "<i class='glyphicon glyphicon-euro'></i></span>"}),
                    closeButton: false,
                    buttons: {
                        'cancel': {
                            label: Translator.trans('keyword.no'),
                            className: 'btn-default margin-right-left'
                        },
                        'confirm': {
                            label: Translator.trans('keyword.yes'),
                            className: 'btn-validate margin-right-left'
                        }
                    },
                    callback: function (result) {
                        if (result) {
                            loader.block();
                            var date = $('#cashbox_count_date').val();
                            if (date.split(' ').length === 1) {
                                $('#cashbox_count_date').val(date + ' 07:00:00')
                            }
                            ajaxCall({
                                method: 'POST',
                                url: Routing.generate('validate_cashbox_count', {}),
                                data: $('#cahsbox_count_form').serialize()
                            }, function (res) {
                                if (res.errors === undefined) {
                                    //Notif.success(Translator.trans('cahsbox.counting.cashbox_count_validated_with_success', {}), 500, 500);
                                    var realCash = (parseFloat($('.total_real_cash').first().text())) - parseFloat($('.total_real_cash').first().text()) % 5;
                                    var enveloppe = res.data["enveloppe"];
                                    $('#cashbox_count_container').html(res.data[0]);
                                    syncRealCashBlock();
                                    initializeDatePicker();
                                    bootbox.confirm(
                                        {
                                            title: Translator.trans('cashbox.gap.gap_validated_with_success', {
                                                "operator": res.data.operator,
                                                "operator_name": res.data.operator_name
                                            }),
                                            message: Translator.trans('cashbox.do_you_want_created_an_enveloppe_for_this_count'),
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
                                                    var footer = "<span id='validateEnveloppe' class='btn btn-validate' style='margin-left: 10px;'>" + Translator.trans('btn.validate') + "</span><span id='cancelEnveloppeCreation' class='btn btn-default'>" + Translator.trans('btn.close') + "</span>"
                                                    showDefaultModal(Translator.trans('envelope.envelope_creation'), enveloppe, footer, '98%', '', true);
                                                    $('#amount').val(realCash);
                                                } else {
                                                    loader.block();
                                                    // redirect to new cashbox count
                                                    window.location.href = Routing.generate('cashbox_counting');
                                                }
                                            }
                                        });
                                }
                            }, null, function () {
                                loader.unblock();
                            });
                        }
                    }
                });
        }
    });

    $(document).on('click', '#validateEnveloppe', function () {
        loader.block('#validateEnveloppe');
        loader.block();
        ajaxCall({
            method: 'POST',
            url: Routing.generate('create_enveloppe', {}),
            data: $('#enveloppe_create_form').serialize()
        }, function (res) {
            if (res.errors === undefined) {
                // redirect to new cashbox count
                window.location.href =  Routing.generate('cashbox_counting');
            } else {
                $("#default-modal-box  p").html(res.data[0]);
                loader.unblock();
                loader.unblock('#validateEnveloppe');
            }
        }, null, function () {

        });
    });

    $(document).on('click', '#cancelEnveloppeCreation', function () {
        loader.block();
        // redirect to new cashbox count
        window.location.href = Routing.generate('cashbox_counting');
    });

    $(document).on('click', '.delete-row', function () {
        $(this).closest('.row').remove();
    });

    // prevent form from submission when press enter key
    $(window).keydown(function (event) {
        if (event.keyCode == KEY_ENTER) {
            event.preventDefault();
            return false;
        }
    });

    $(document).on('keyup', '#cashbox_count_cashContainer_totalAmount, .nextOnEnter', function (event) {
        if (event.keyCode == KEY_ENTER) {
            $('.nav-tabs > .active').next('li').find('a').trigger('click');
        }
    });

    $(document).on('keyup', '.only-number input[type=text]', function(e){
        e.stopPropagation();
        //Disable submit button
        if(e.keyCode ==32){
            $(this).val($.trim($(this).val()));
        }
        if(isNaN($(this).val())){
            $('#validateCashboxGap').attr('disabled', true);
            $('#cashbox_count_cashier').attr('disabled', true);
            $('#cashbox_count_date').attr('disabled', true);
            $('#reload').attr('disabled', true);
            $(this).addClass('shadow-danger');
            $('#total_cashbox_block').addClass('shadow-danger');
            cashBoxCountingIsValid = false;

        }else{
            $(this).removeClass('shadow-danger');
            if(! $( ".only-number input[type=text]" ).hasClass( "shadow-danger" )){
                $('#validateCashboxGap').attr('disabled', false);
                $('#cashbox_count_cashier').attr('disabled', false);
                $('#cashbox_count_date').attr('disabled', false);
                $('#reload').attr('disabled', false);
                cashBoxCountingIsValid = true;
                $('#total_cashbox_block').removeClass('shadow-danger');
            }
        }
    });
});
