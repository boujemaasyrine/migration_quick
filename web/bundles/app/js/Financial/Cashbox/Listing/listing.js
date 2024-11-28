/**
 * Created by bbarhoumi on 09/05/2016.
 */
$(function () {
    cashboxCounts = initSimpleDataTable('#cashbox_counts_table', {
        rowId: 'id',
        processing: true,
        serverSide: true,
        searching: true,
        pageLength: 10,
        "order": [[6, "desc"]],
        "columns": [
            {"data": "date"},
            {"data": "owner"},
            {"data": "cashier"},
            {"data": "realCaCounted"},
            {"data": "theoricalCa"},
            {"data": "difference"},
            {"data": "createdAt"},
            {
                className: 'actions-btn',
                "render": function (data, type, row) {
                    var btn = $('#cashbox_counts_table').attr('data-template');
                    return btn;
                }
            }
        ],
        ajax: {
            url: Routing.generate("cashbox_list_json"),
            data: function (d) {
                d.criteria = serializeArrayToObjectByKey($('#filterCasbocCountsForm').serializeArray(), 'name');
                return d;
            },
            type: 'post'
        }
    });
    $('#reset-filter').on('click', function () {
        resetFilter($('.filter-zone'));
        cashboxCounts.ajax.reload();
    });
    $("#export-btn").on('click', function () {
        submitExportDocumentFile(".filter-zone", cashboxCounts, Routing.generate("cashbox_list_json", {"download": 1}));
    });

    $(document).on('click', '.detail-btn', function () {
        loader.show();
        var cashboxCountId = $(this).parentsUntil('tbody', 'tr').attr('id');
        ajaxCall({
            url: Routing.generate('cashbox_count_detail', {'cashboxCount': cashboxCountId}),
            'type': 'json'
        }, function (data) {
            showDefaultModal(Translator.trans('cashbox.listing.details'), data.data, data.dataFooter, '90%');
            loader.hide();
        }, function (data) {
            loader.hide();
        });
    });

    $(document).on('click', '.delete-cashbox', function(){
        // loader.block();
        var btn= $(this);
        var cashboxCountId = $(this).parentsUntil('tbody', 'tr').attr('id');
        var url = Routing.generate('delete_cashbox_count',{'cashboxCount' : cashboxCountId});
        bootbox.prompt(
            {
                title: Translator.trans('cashbox.delete_message'),
                size: "small",
                message: Translator.trans('popup.do_you_confirm_this_action'),
                closeButton: false,
                inputType: 'password',
                buttons: {
                    'confirm': {
                        label: Translator.trans('shortcut.labels.confirm'),
                        className: 'btn-validate margin-right-left'
                    },
                    'cancel': {
                        label: Translator.trans('shortcut.labels.cancel'),
                        className: 'btn-default margin-right-left'
                    }
                },
                callback: function (result) {
                    if (null !== result) {
                        loader.block();
                        ajaxCall({
                            url: url,
                            method: POST,
                            data : {'password': result}
                        }, function (res) {
                            if (res.errors == undefined && res.deleted==1) {
                                Notif.success(Translator.trans('cashbox_success_deleted'));
                                location.reload();
                            }else if (res.deleted==-1) {
                                Notif.alert(Translator.trans('envelope.wrong_password'));
                            }else if (res.deleted==2){
                                Notif.alert(Translator.trans('cashbox.counted'));
                            }else{
                                Notif.alert(Translator.trans('cashbox.cant_be_deleted'));
                            }
                            loader.unblock();
                        }, function (res) {
                            Notif.alert(Translator.trans('cashbox.cant_be_deleted'));
                            loader.unblock();
                        }, null, false);
                    }
                }
            });
    });


});