/**
 * Created by mchrif on 10/02/2016.
 */
$.fn.DataTable.ext.pager.numbers_length = 5;

var specialInit = null;
function init() {
    initDatePicker();
    if (specialInit) {
        specialInit();
    }
}

var initProductAutocomplete = null;
var selectAllItemsInCategorie = null;

var closeFormSheetModel = $("#closeFormSheetModel");
var sheetModelFormTable = null;
var sheetListDatatable = null;
var sheetModelBlock = $("#sheetModelBlock");
var productCache = {};

function initSheetProductTable() {
    sheetModelFormTable = initSimpleDataTable('#sheetModelFormTable', {
        retrieve: true,
        lengthChange: true,
        processing: false,
        serverSide: false,
        searching: true,
        ordering: false,
        orderFixed: [ 4, 'asc' ],
        createdRow: function ( row, data, index ) {
            //$(row).attr('id', 'row-' + index);
            var productId=$(row).find('.btnRemoveSheetModelLine').attr('data-product-id');
            $("#orderInSheet_"+productId).val(index);
        }
    });
}

$(document).on('click', '.orderUp', function () {
    var row = $(this).closest('tr');
    var currentPage = sheetModelFormTable.page();
    //move added row to desired index (here the row we clicked on)
    var index = sheetModelFormTable.row(row).index(),
        rowCount = sheetModelFormTable.data().length - 1,
        tempRow;

    if (index > 0) {
        tempRow = sheetModelFormTable.row(index - 1).data();
        var tempRowNode=sheetModelFormTable.row(index - 1).node();
        sheetModelFormTable.row(index - 1).data(sheetModelFormTable.row(index).data());
        sheetModelFormTable.row(index).data(tempRow);
        //update order in sheet value
        var productId=$(row).find('.btnRemoveSheetModelLine').attr('data-product-id');
        var tmpProductId=$(tempRowNode).find('.btnRemoveSheetModelLine').attr('data-product-id');

        $("#orderInSheet_"+productId).val(index);
        $("#orderInSheet_"+tmpProductId).val(index-1);
    }

    //refresh the current page
    sheetModelFormTable.page(currentPage).draw(false);
});
$(document).on('click', '.orderDown', function () {
    var row = $(this).closest('tr');
    var currentPage = sheetModelFormTable.page();
    //move added row to desired index (here the row we clicked on)
    var index = sheetModelFormTable.row(row).index(),
        rowCount = sheetModelFormTable.data().length - 1,
        insertedRow = sheetModelFormTable.row(rowCount).data(),
        tempRow;

    if (index < rowCount) {
        tempRow = sheetModelFormTable.row(index + 1).data();
        var tempRowNode=sheetModelFormTable.row(index + 1).node();
        sheetModelFormTable.row(index + 1).data(sheetModelFormTable.row(index).data());
        sheetModelFormTable.row(index).data(tempRow);
        //update order in sheet value
        var productId=$(row).find('.btnRemoveSheetModelLine').attr('data-product-id');
        var tmpProductId=$(tempRowNode).find('.btnRemoveSheetModelLine').attr('data-product-id');

        $("#orderInSheet_"+productId).val(index);
        $("#orderInSheet_"+tmpProductId).val(index+1);
    }

    //refresh the current page
    sheetModelFormTable.page(currentPage).draw(false);
});

$(function () {

    $(document).on('click', '.panel-heading', function (e) {
        var target = $(e.target);
        if ($(target[0]).hasClass('panel-heading')) {
            $($(target).find('a')[0]).click();
        } else if ($(target).hasClass('.select_this_categorie')) {
            loader.block();
            selectAllProductInCategory(target[0]);
            loader.unblock();
        }
    });

    function selectAllProductInCategory(self) {

        var categoryId = $(self).attr('data-category-id');
        var categoryContent = $('#category_content' + categoryId);
        var isEmtpty = true;
        var categorySelectedName = null;
        categoryContent.children().each(function (index, element) {
            if ($(element).css('display') != 'none') {
                var productId = $(element).attr('data-product-id');
                var productExternalId = $(element).attr('data-product-external-id');
                var productName = $(element).attr('data-product-name');
                var categoryName = $(element).attr('data-category-name');
                $(element).fadeOut(50);
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
                categorySelectedName = categoryName;
                isEmtpty = false;
                //$("#containerSheetModelLines").sortable();
            }
        });
        if (isEmtpty) {
            Notif.alert(Translator.trans('sheet_model.notification.all_item_of_this_category_are_already_selected'));
        } else {
            Notif.success(Translator.trans('sheet_model.notification.all_item_of_this_category_are_added_with_success', {
                'category_name': categorySelectedName
            }))
        }
    }

    $(document).on('click', '.select_this_categorie', function (e) {
        loader.block();
        selectAllProductInCategory(this);
        loader.unblock()
    });

    sheetListDatatable = initSimpleDataTable('#sheet-model-table', {
        searching: true,
        processing: true,
        serverSide: true,
        ajax: {
            "url": Routing.generate('api_sheet', {
                type: sheetsType
            }),
            "type": "GET"
        },
        "columns": [
            {"data": "id"},
            {"data": "label"},
            {
                "data": null,
                "orderable": false,
                render: function (row) {
                    var action = $('#sheet-model-table').attr('data-template');
                    action = action.replace(/_id_/g, row.id);
                    action = action.replace(/_sheet_label_/g, row.label);
                    return action;
                },
                "targets": 0
            }
        ],
        "drawCallback": function () {
            $('[data-toggle="tooltip"]').tooltip();
        }
    });

    initProductAutocomplete = function (self) {
        // TODO marwen: Factorize this in one function
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
                                    value: '',
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
                        .replace(/_order_in_sheet_/g, nbr)
                        .replace(/_category_name_/g, categoryName);
                    sheetModelFormTable.row.add($(prototype)).draw();
                    //$("#containerSheetModelLines").sortable();
                    $('#searchByProductName').val(productName);
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
                                    label: item.externalId + ' -' + item.name,
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
                        .replace(/_order_in_sheet_/g, nbr)
                        .replace(/_num_/g, sheetModelFormTable.data().length);
                    sheetModelFormTable.row.add($(prototype)).draw();
                    //$("#containerSheetModelLines").sortable();
                    $('#searchByCode').val(productExternalId);
                } else {
                    Notif.alert(Translator.trans('sheet_model.notification.item_already_exist', {
                        'product_name': productName
                    }));
                }
                $(this).val(''); return false;
            }
        });
    };

    // construct termKey for cache purpose
    function termKey(term) {
        return term;
    }

    /**
     * Lookup on each item group and if already exist in sheetModelFormTable hide it
     */
    function clearGroupsItem() {
        // lookuping in item gorup
        $('.product-line').each(function (index, elem) {
            var id = $(elem).attr('data-product-id');
            $('#sheetModelFormTable').find('tr').each(function (key, elem1) {
                var idElem1 = $($($(elem1).children('td')[0]).children('input')[0]).val();
                if (id === idElem1) {
                    $(elem).fadeOut();
                }
            });
        });
    }

    initSheetProductTable();

    $('.bootstrap_tooltipped').tootltipBootstrap();

    $(document).on('click', '.closeDefaultModal', function() {
        $('#default-modal-box').modal('hide');
        $('#default-modal-box p').html('');
    });

    $(document).on('click', '.removeSheetModel', function() {
        var sheetLabel = $(this).attr('sheet-label');
        var url = $(this).attr('data-delete-url');
        bootbox.confirm(
            {
                title: Translator.trans('sheet_model.delete_message', {'sheet_label': sheetLabel}),
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
                        ajaxCall({
                            url: url,
                            method: DELETE
                        }, function (res) {
                            if (res.errors == undefined) {
                                if(res.data.redirect !== undefined) {
                                    window.location.href = res.data.redirect;
                                }
                            } else {
                                loader.unblock();
                            }
                        }, null, null, false);
                    }
                }
            });
    });

});