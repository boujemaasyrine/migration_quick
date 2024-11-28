/**
 * Created by mchrif on 03/03/2016.
 */


$(function () {
    var productCache = {};
    specialInit = function () {
        //$("#containerSheetModelLines").sortable();
        initProductAutocomplete();
    };

    specialInit();

    function initProductAutocomplete() {
        // TODO marwen: Factorize this in one function
        var searchByCode = $('#searchByCode');
        searchByCode.autocomplete({
            autoFill: true,
            source: function (request, response) {
                var term = request.term;
                if (termKey(term) in productCache) {
                    response(productCache[termKey(term)]);
                    return;
                }
                ajaxCall({
                        url: Routing.generate('find_active_products', {
                            code: term,
                            selectedType: cibledProducts,
                            filterSecondary: 'true'
                        }),
                        method: GET
                    },
                    function (res) {
                        if (res.errors == undefined) {
                            productCache[termKey(term)] = $.map(res.data[0], function (item) {
                                return {
                                    id: item.id,
                                    name: item.name,
                                    label: item.externalId + '- ' + item.name,
                                    value: "",
                                    externalId: item.externalId
                                };
                            });
                            response(productCache[termKey(term)]);
                        }
                    }, null, null, true
                );
            },
            select: function (event, ui) {
                var line = $('.product-line' + ui.item.id);
                var productId = line.attr('data-product-id');
                var productExternalId = line.attr('data-product-external-id');
                var productName = line.attr('data-product-name');
                var categoryName = line.attr('data-category-name');
                if (line.css('display') !== 'none') {
                    line.fadeOut(50);
                    var containerSheetModelLines = $('#containerSheetModelLines');
                    var nbr = parseInt(containerSheetModelLines.attr('data-count'));
                    containerSheetModelLines.attr('data-count', nbr + 1);
                    var prototype = containerSheetModelLines.data('prototype')
                        .replace(/_line_number_/g, nbr)
                        .replace(/_id_product_/g, productId)
                        .replace(/_ref_product_/g, productExternalId)
                        .replace(/_label_product_/g, productName)
                        .replace(/_category_name_/g, categoryName)
                        .replace(/_num_/g, sheetModelFormTable.data().length);
                    //containerSheetModelLines.append(prototype);
                    sheetModelFormTable.row.add($(prototype)).draw();
                    //$("#containerSheetModelLines").sortable();

                    Notif.success(Translator.trans('sheet_model.notification.item_selected_with_success', {
                        'product_name': productName
                    }));
                } else {
                    Notif.alert(Translator.trans('sheet_model.notification.item_already_exist', {
                        'product_name': productName
                    }));
                }
                $(this).val(''); return false;
            }
        });

        var searchByProductName = $('#searchByProductName');
        searchByProductName.autocomplete({
            autoFill: true,
            source: function (request, response) {
                var term = request.term;
                if (termKey(term) in productCache) {
                    response(productCache[termKey(term)]);
                    return;
                }
                ajaxCall({
                        url: Routing.generate('find_active_products', {
                            term: term,
                            selectedType: cibledProducts,
                            filterSecondary: 'true'
                        }),
                        method: GET
                    },
                    function (res) {
                        if (res.errors == undefined) {
                            productCache[termKey(term)] = $.map(res.data[0], function (item) {
                                return {
                                    id: item.id,
                                    name: item.name,
                                    label: item.name,
                                    value: item.name,
                                    externalId: item.externalId
                                };
                            });
                            response(productCache[termKey(term)]);
                        }
                    }, null, null, true
                );
            },
            select: function (event, ui) {
                var line = $('.product-line' + ui.item.id);
                var productId = line.attr('data-product-id');
                var productExternalId = line.attr('data-product-external-id');
                var productName = line.attr('data-product-name');
                var categoryName = line.attr('data-category-name');
                if (line.css('display') !== 'none') {
                    line.fadeOut(50);
                    var containerSheetModelLines = $('#containerSheetModelLines');
                    var nbr = parseInt(containerSheetModelLines.attr('data-count'));
                    containerSheetModelLines.attr('data-count', nbr + 1);
                    var prototype = containerSheetModelLines.data('prototype')
                        .replace(/_line_number_/g, nbr)
                        .replace(/_id_product_/g, productId)
                        .replace(/_ref_product_/g, productExternalId)
                        .replace(/_label_product_/g, productName)
                        .replace(/_category_name_/g, categoryName)
                        .replace(/_num_/g, sheetModelFormTable.data().length);
                    sheetModelFormTable.row.add($(prototype)).draw();
                    $('#searchByProductName').val(productName);

                    Notif.success(Translator.trans('sheet_model.notification.item_selected_with_success', {
                        'product_name': productName
                    }));
                } else {
                    Notif.alert(Translator.trans('sheet_model.notification.item_already_exist', {
                        'product_name': productName
                    }));
                }
                $('#searchByCode').val(productExternalId);
                $(this).val(''); return false;
            }
        });
    }

    // construct termKey for cache purpose
    function termKey(term) {
        return term;
    }

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
                .replace(/_num_/g, sheetModelFormTable.data().length);
            sheetModelFormTable.row.add($(prototype)).draw();
            Notif.success(Translator.trans('sheet_model.notification.item_selected_with_success', {
                'product_name': productName
            }));
        } else {
            $(this).fadeOut(50);
        }
    });

    $(document).on('click', '.btnRemoveSheetModelLine', function () {
        var productId = $(this).attr('data-product-id');
        $(".product-line" + productId).fadeIn(50);
        $(this).closest('tr').remove();
        sheetModelFormTable.row($(this).closest('tr')).remove().draw();
        //$("#containerSheetModelLines").sortable();
    });

    $(document).on('click', '#print_btn', function() {
        var form = $($(this).closest('form'));
        var action = form.attr('action');
        var tempUrl = $.addParamToUrl(action, 'download', true);
        form.attr('action', tempUrl);
        //sheetModelFormTable.destroy();
        var oldLength = sheetModelFormTable.page.len();
        sheetModelFormTable.page.len( -1 ).draw();
        form.submit();
        sheetModelFormTable.page.len(oldLength).draw();
        //sheetModelFormTable = initSimpleDataTable('#sheetModelFormTable', {
        //    "lengthChange": false,
        //    processing: false,
        //    serverSide: false,
        //    searching: true,
        //    lengthMenu: true,
        //    ordering: false
        //});
        form.attr('action', action);
    });

    $(document).on('click', '#validateFormSheetModel', function () {
        var form = $('#sheet_model_form');
        bootbox.confirm(
            {
                title: Translator.trans('inventory.new_sheet.confirm.message'),
                message: Translator.trans('popup.do_you_confirm_this_action'),
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
                        loader.block();
                        sheetModelFormTable.page.len( -1 ).draw();
                        //sheetModelFormTable.destroy();
                        form.submit();
                    }
                }
            });
    });

});