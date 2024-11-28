/**
 * Created by mchrif on 16/03/2016.
 */

$(function () {
    initProductAutocomplete = function (self) {
        var searchByCode = $('#searchByCode');
        searchByCode.autocomplete({
            autoFill: true,
            appendTo: $(self),
            source: function (request, response) {
                var term = request.term;
                if (termKey(term) in productCache) {
                    response(productCache[termKey(term)]);
                    return;
                }
                ajaxCall({
                        url: Routing.generate('find_active_products', {
                            term: term,
                            selectedType: cibledProducts
                        }),
                        method: GET
                    },
                    function (res) {
                        if (res.errors == undefined) {
                            productCache[termKey(term)] = $.map(res.data[0], function (item) {
                                return {
                                    id: item.id,
                                    name: item.name,
                                    label: item.codePlu + '- ' + item.name,
                                    value: item.codePlu + '- ' + item.name,
                                    codePlu: item.codePlu
                                };
                            });
                            response(productCache[termKey(term)]);
                        }
                    }, null, null, true
                );
            },
            select: function (event, ui) {
                var productId = ui.item.id;
                var productDuplicated = false;
                $('.product_id').each(function () {
                    if (!productDuplicated) {
                        if ($($(this).siblings('input')[0]).val() == productId) {
                            productDuplicated = true;
                        }
                    }
                });
                if(productDuplicated) {
                    Notif.alert(Translator.trans('sheet_model.notification.item_already_exist', {
                        'product_name': ui.item.name
                    }));
                } else {
                    var containerSheetModelLines = $('#containerSheetModelLines');
                    var nbr = parseInt(containerSheetModelLines.attr('data-count'));
                    containerSheetModelLines.attr('data-count', nbr + 1);
                    var prototype = containerSheetModelLines.data('prototype')
                        .replace(/_line_number_/g, nbr)
                        .replace(/_id_product_/g, ui.item.id)
                        .replace(/_ref_product_/g, ui.item.codePlu)
                        .replace(/_label_product_/g, ui.item.name)
                        .replace(/_order_in_sheet_/g, nbr)
                        .replace(/_num_/g, sheetModelFormTable.data().length);
                    //containerSheetModelLines.append(prototype);
                    sheetModelFormTable.row.add($(prototype)).draw();
                    //$("#containerSheetModelLines").sortable();
                    $('#searchByProductName').val(ui.item.name);
                }

                $(this).val('');
                return false;
            }
        });

        var searchByProductName = $('#searchByProductName');
        searchByProductName.autocomplete({
            autoFill: true,
            appendTo: $(self),
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
                                    codePlu: item.codePlu
                                };
                            });
                            response(productCache[termKey(term)]);
                        }
                    }, null, null, true
                );
            },
            select: function (event, ui) {
                var containerSheetModelLines = $('#containerSheetModelLines');
                var nbr = parseInt(containerSheetModelLines.attr('data-count'));
                containerSheetModelLines.attr('data-count', nbr + 1);

                // check if the element already exist in the table
                var exist = false;
                var rows = sheetModelFormTable.rows().data();
                if (rows[0] !== undefined && rows[0].length !== undefined) {
                    for (var i = 0; i < rows[0].length; i++) {
                        var row = $(rows[0][i]);
                        var rowId = $(row[1]).val();
                        if (parseInt(rowId) === parseInt(ui.item.id)) {
                            exist = true;
                            break;
                        }
                    }
                }
                if (!exist) {
                    var prototype = containerSheetModelLines.data('prototype')
                        .replace(/_line_number_/g, nbr)
                        .replace(/_id_product_/g, ui.item.id)
                        .replace(/_ref_product_/g, ui.item.codePlu)
                        .replace(/_label_product_/g, ui.item.name)
                        .replace(/_num_/g, sheetModelFormTable.data().length);
                    //containerSheetModelLines.append(prototype);
                    sheetModelFormTable.row.add($(prototype)).draw();
                    //$("#containerSheetModelLines").sortable();
                    $('#searchByCode').val(ui.item.codePlu);
                    Notif.success(Translator.trans('sheet_model.notification.item_selected_with_success', {
                        'product_name': ui.item.name
                    }));
                } else {
                    Notif.alert(Translator.trans('sheet_model.notification.item_already_exist', {
                        product_name: ui.item.name
                    }));
                }
            }
        });
    };

    // construct termKey for cache purpose
    function termKey(term) {
        return term;
    }
});