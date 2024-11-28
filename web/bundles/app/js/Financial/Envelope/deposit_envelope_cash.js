/**
 * Created by bbarhoumi on 02/05/2016.
 */
$(function () {
    initSimpleDataTable('#envelopes_table');

    $(document).on('click', '#btn-validate', function () {
        bootbox.confirm({
            title: Translator.trans('deposit.envelope_cash.validate'),
            message: Translator.trans('deposit.envelope_cash.validate_message',
                {
                    nbre: $('#number-envelope').val(),
                    amount: $('#total-amount').val()
                }),
            closeButton: false,
            buttons: {

                'cancel': {
                    label: Translator.trans('keyword.no'),
                    className: 'btn btn-cancel btn-icon margin-right-left'
                },
                'confirm': {
                    label: Translator.trans('keyword.yes'),
                    className: 'btn btn-validate btn-icon margin-right-left pull-right'
                }
            },
            callback: function (result) {
                if (result) {
                    loader.block();
                    ajaxCall({
                        method: 'POST',
                        url: Routing.generate('deposit_cash', {}),
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
    });
});