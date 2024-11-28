/**
 * Created by mchrif on 10/02/2016.
 */

$(function () {
    specialInit = function () {
        //$("#containerSheetModelLines").sortable();
        initProductAutocomplete($('#default-modal-box'));
        initSheetProductTable();
        $('.bootstrap_tooltipped').tootltipBootstrap()
    };

    $(document).on('click', '#btnAddNewSheet', function () {
        var self = $(this);
        loader.block(self);
        ajaxCall({
            method: 'GET',
            url: Routing.generate('api_save_loss_sheet_model', {
                "type": sheetsType
            })
        }, function (res) {
            if (res.errors === undefined) {
                showDefaultModal('', res.data[0], '', '98%',null,  false);
                init();
            }
        }, null, function () {
            loader.unblock(self);
        })
    });

    // sheet form
    $(document).on('click', '#validateFormSheetModel', function (e) {
        var isAjax = $(this).attr('data-is-ajax');
        bootbox.confirm(
            {
                title: Translator.trans('popup.do_you_confirm_this_action'),
                message: Translator.trans( 'loss.confirm.message'),
                closeButton: false,
                buttons: {
                    'cancel': {
                        label: Translator.trans('keyword.no'),
                        className: 'btn-default  margin-right-left'
                    },
                    'confirm': {
                        label: Translator.trans('keyword.yes'),
                        className: 'btn-validate'
                    }
                },
                callback: function (result) {
                    if (result) {
                        var oldLength = sheetModelFormTable.page.len();
                        sheetModelFormTable.page.len( -1 ).draw();
                        loader.block();
                        if(isAjax) {
                            ajaxCall({
                                    url: Routing.generate('api_save_loss_sheet_model', {
                                        "type": sheetsType
                                    }),
                                    method: POST,
                                    data: $("#sheet_model_form").serialize()
                                },
                                function (res) {
                                    if (res.errors === undefined) {
                                        if ($("#sheet_model_form").hasClass('oldSheetModel')) {
                                            Notif.success(
                                                Translator.trans('sheet_model.notification.edited_with_success', {
                                                    id: res.data[1]
                                                }));
                                        } else {
                                            Notif.success(Translator.trans('sheet_model.notification.created_with_success', {
                                                id: res.data[1]
                                            }), null, null, [
                                                {
                                                    addClass: 'btn btn-primary',
                                                    text: Translator.trans('sheet_model.notification.btn.create_another_one'),
                                                    onClick: function ($noty) {
                                                        $("#btnAddNewSheet").click();
                                                        $noty.close();
                                                    }
                                                },
                                                {
                                                    addClass: 'btn btn-info',
                                                    text: Translator.trans('btn.close'),
                                                    onClick: function ($noty) {
                                                        $noty.close();
                                                    }
                                                }
                                            ]);
                                        }
                                        sheetListDatatable.ajax.reload();
                                        $('#default-modal-box').modal('hide');
                                        $('#default-modal-box p').html('');
                                    } else {
                                        if (res.data.length) {
                                            $($('#default-modal-box .modal-body>p')[0]).html(res.data[0]);
                                            init();
                                        }
                                    }
                                    loader.unblock();
                                }, function(jqXHR, textStatus, errorThrown){console.log(jqXHR);}, null, true);
                        } else {
                            var action = $("#sheet_model_form").attr('action');
                            var tempUrl = $.addParamToUrl(action, 'type', sheetsType);
                            $("#sheet_model_form").attr('action', tempUrl);
                            //sheetModelFormTable.destroy();
                            $("#sheet_model_form").submit();
                            $("#sheet_model_form").attr('action', action);
                        }
                    }
                }
            });
    });

    // Edit and copy inventory sheet
    $(document).on('click', '.btnEditSheetModel', function (e) {
        var self = $(this);
        var id = $(this).attr('data-id');
        loader.block();
        ajaxCall({
            method: GET,
            url: Routing.generate('api_save_loss_sheet_model', {
                sheetModel: id,
                type: sheetsType
            })
        }, function (res) {
            loader.unblock();
            if (res.errors === undefined) {
                showDefaultModal( '', res.data[0],  '', '98%', '100%',false);
                init();
            }
        }, null, function () {
            loader.unblock();
        })
    });

    // select part
    $(document).on('click', '.product-line', function () {
        var productId = $(this).attr('data-product-id');
        var productExternalId = $(this).attr('data-product-external-id');
        var productName = $(this).attr('data-product-name');
        var categoryName = $(this).attr('data-category-name');

        // check if the element already exist in the table
        var exist = false;
        var rows = sheetModelFormTable.rows().data();
        if (rows[0] !== undefined && rows[0].length !== undefined) {
            for (var i = 0; i < rows[0].length; i++) {
                var row = $(rows[0][i]);
                var rowId = $(row[1]).val();
                if (parseInt(rowId) === parseInt(productId)) {
                    exist = true;
                    break;
                }
            }
        }
        if (!exist) {
            $(this).fadeOut(50);
            var containerSheetModelLines = $('#containerSheetModelLines');
            var nbr = parseInt(containerSheetModelLines.attr('data-count'));
            containerSheetModelLines.attr('data-count', nbr + 1);
            var prototype = containerSheetModelLines.data('prototype')
                .replace(/_line_number_/g, nbr)
                .replace(/_id_product_/g, productId)
                .replace(/_ref_product_/g, productExternalId)
                .replace(/_label_product_/g, productName)
                .replace(/_category_name_/g, categoryName)
                .replace(/_order_in_sheet_/g, nbr)
                .replace(/_num_/g, sheetModelFormTable.data().length);
            sheetModelFormTable.row.add($(prototype)).draw();
            Notif.success(Translator.trans('sheet_model.notification.item_selected_with_success', {
                'product_name': productName
            }));
        } else {
            $(this).fadeOut(50);
        }
    });

    $(document).on('click', '#print_btn', function() {
        var form = $($(this).closest('form'));
        var action = form.attr('action');
        var tempUrl = $.addParamToUrl(action, 'download', true);
        tempUrl = $.addParamToUrl(tempUrl, 'type', sheetsType);
        form.attr('action', tempUrl);
        var oldlength = sheetModelFormTable.page.len();
        sheetModelFormTable.page.len( -1 ).draw();
        form.submit();
        //sheetModelFormTable = initSimpleDataTable('#sheetModelFormTable', {
        //    "lengthChange": false,
        //    processing: false,
        //    serverSide: false,
        //    searching: true,
        //    lengthMenu: true,
        //    ordering: false
        //});
        sheetModelFormTable.page.len(oldlength ).draw();
        form.attr('action', action);
    });

    $(document).on('click', '.btnRemoveSheetModelLine', function () {
        var productId = $(this).attr('data-product-id');
        $(".product-line" + productId).fadeIn(50);
        sheetModelFormTable.row($(this).closest('tr')).remove().draw();
    });

});
