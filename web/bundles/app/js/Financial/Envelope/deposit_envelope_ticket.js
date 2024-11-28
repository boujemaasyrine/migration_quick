/**
 * Created by bbarhoumi on 02/05/2016.
 */
$(function () {
    var envelopes_table = initSimpleDataTable('#envelopes_table');
    var numberEnvelope = $('#number-envelope'),
        totalAmonut = $('#deposit_ticket_totalAmount');
    $(document).on('click', '#btn-validate', function () {
        if ($('#deposit_ticket_sousType').val() != '') {
            if (numberEnvelope.val() != 0){
                bootbox.confirm({
                    title: Translator.trans('deposit.envelope_ticket.validate'),
                    message: Translator.trans('deposit.envelope_ticket.validate_message',
                        {
                            nbre: numberEnvelope.val(),
                            amount: totalAmonut.val()
                        }),
                    closeButton: false,
                    buttons: {
                        'cancel': {
                            label: Translator.trans('keyword.no'),
                            className: 'btn btn-cancel btn-icon margin-right-left'
                        },
                        'confirm': {
                            label: Translator.trans('keyword.yes'),
                            className: 'btn btn-validate btn-icon margin-right-left'
                        }
                    },
                    callback: function (result) {
                        if (result) {
                            loader.block();
                            ajaxCall({
                                method: 'POST',
                                url: Routing.generate('deposit_ticket', {}),
                                data: $('#deposit-form').serialize()
                            }, function (res) {
                                if (res.errors === undefined) {
                                    showDefaultModal(res.data['header'], res.data[0], res.data['footer'], '98%', '', false);
                                } else {
                                    $('#form-container').html(res.data[0]);
                                }
                            }, null, function () {
                                loader.unblock();
                            });
                        } else {
                        }
                    }
                });
            }else{
                $.notify(Translator.trans('deposit.message.no_envelope'), {"status": "danger"});
            }
        } else if ($('#deposit_ticket_sousType').val() == '') {
            $.notify(Translator.trans('deposit.message.choose_type'), {"status": "danger"});
        }
    });
    $(document).on('change', '#deposit_ticket_sousType', function () {
        loader.block();
        ajaxCall({
            method: 'GET',
            url: Routing.generate('deposit_ticket', {}),
            data: $('#deposit-form').serialize()
        }, function (res) {
            if (res.errors === undefined) {
                envelopes_table.clear();
                envelopes_table.draw();
                if(res.data['affiliateCode'] != null){
                    $.each(res.data['envelopes'], function (key, value) {
                        envelopes_table.row.add([
                            value['number'],
                            value['reference'],
                            value['amount'],
                            value['sousType'],
                            value['owner'],
                            value['status'],
                            value['createdAt']
                        ]).draw().node();
                    });
                    numberEnvelope.val(res.data['envelopes'].length);
                    $('#total-amount-div').html(floatToString(res.data['total'])+'&euro;');
                    $('#deposit_ticket_affiliateCode').val(res.data['affiliateCode']);
                    totalAmonut.val(res.data['total']);
                }else{
                    numberEnvelope.val(0);
                    $('#total-amount-div').html('');
                    $('#deposit_ticket_affiliateCode').val('');
                }
                $('#affiliate-code').html(res.data['affiliateCode']);
            }
        }, null, function () {
            loader.unblock();
        });
    });
});