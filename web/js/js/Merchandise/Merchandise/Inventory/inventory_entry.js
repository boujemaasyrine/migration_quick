/**
 * Created by mchrif on 10/02/2016.
 */

$(function () {
    var inventoryBlock = $("#inventory_block");
    var iventoryListDatatable = null;
    var inventorySheetFormTable = null;
    var productCodeCache = {};
    var productNameCache = {};
    var fiscalDatepicker = null;

    if ($('#inventory_sheet_sheetModel').val() === '') {
        $('#btnCreateInventorySheetFromModel').prop('disabled', true);
    }

    init();
    function initFiscalDatepicker() {
        var from = moment($('#inventory_sheet_fiscalDate').first().attr('data-from-date'), 'YYYY/MM/DD');
        fiscalDatepicker = initDatePicker('#inventory_sheet_fiscalDate', {
            disable: [
                true,
                {from: new Date(from.format('YYYY/MM/DD')),
                    to: new Date(new Date().setDate(new Date().getDate() - 1))}
            ],
            onOpen: function () {
                $('#inventory_sheet_fiscalDate_root .picker__today').remove();
            }
        });
    }

    function init() {
        inventorySheetFormTable = initSimpleDataTable('#inventorySheetFormTable', {
            paging: true,
            searching: true,
            ordering: true,
            aaSorting: [],
            processing: false
        });
        initProductAutocomplete();
        initDatePicker();
        initFiscalDatepicker();

    }
    iventoryListDatatable = initSimpleDataTable('#inventory-table', {

        searching: true,
        processing: true,
        serverSide: true,
        "order": [[1, "desc"]],
        ajax: {
            "url": Routing.generate('inventory_list'),
            "type": "GET"
        },
        "columns": [
            {"data": "id"},
            {
                "data": "createdAt.timestamp",
                "render": function (d) {
                    return moment.unix(d).format('DD/MM/YYYY HH:mm:ss');
                }
            },
            {
                "data": "fiscalDate.timestamp",
                "render": function (d) {
                    return moment.unix(d).format('DD/MM/YYYY');
                }
            },
            {
                "data": "sheetModelLabel"
            },
            {
                "data": null,
                "orderable": false,
                render: function (row) {
                    var today = moment();
                    var date = moment.unix(row.createdAt.timestamp);

                    var action = $('#inventory-table').attr('data-template');
                    action = action.replace(/_id_/g, row.id);
                    return action;

                },
                "targets": 0
            }
        ],
        "drawCallback": function () {
            $('[data-toggle="tooltip"]').tooltip();
        }
    });

    $(document).on('click', '#btnAddNewInventoryEntry', function () {
        var self = $(this);
        loader.block(self);
        ajaxCall({
            method: 'GET',
            url: Routing.generate('inventory_entry')
        }, function (res) {
            if (res.errors === undefined) {
                inventoryBlock.html(res.data[0]);
                inventoryBlock.fadeIn();
                init();
            }
        }, null, function () {
            loader.unblock(self);
        })
    });

    $(document).on('click', '#btnAddNewLine', function (e) {
        var refProduct = $('#productReference').val();
        var idProduct = $('#productId').val();
        var productName = $('#productName').val();
        var productUsageQty = $('#productUsageQty').val();
        var productInventoryQty = $('#productInventoryQty').val();
        var productExpedQty = $('#productExpedQty').val();
        if (idProduct != '' && productName != '') {
            var containerLines = $('#containerLines');
            var nbr = parseInt(containerLines.attr('data-count'));
            containerLines.attr('data-count', nbr + 1);
            var prototype = containerLines.data('prototype')
                .replace(/_line_number_/g, nbr)
                .replace(/_id_product_/g, idProduct)
                .replace(/_usage_product_cnt_/g, productUsageQty)
                .replace(/_inventory_product_cnt_/g, productInventoryQty)
                .replace(/_exped_product_cnt_/g, productExpedQty)
                .replace(/_label_product_/g, productName);

            if (selectedItem != null) {
                prototype = prototype.replace(/_inventory_product_usage_unit_label_/g, Translator.trans(selectedItem.labelUnitUsage))
                    .replace(/_inventory_product_inventory_unit_label_/g, Translator.trans(selectedItem.labelUnitInventory))
                    .replace(/_inventory_product_exped_unit_label_/g, Translator.trans(selectedItem.labelUnitExped))
            } else {
                prototype = prototype.replace(/_inventory_product_usage_unit_label_/g, '')
                    .replace(/_inventory_product_inventory_unit_label_/g, '')
                    .replace(/_inventory_product_exped_unit_label_/g, '')
            }

            if (refProduct !== undefined) {
                prototype = prototype
                    .replace(/_ref_product_/g, refProduct);
            } else {
                prototype = prototype
                    .replace(/_ref_product_/g, '');
            }
            inventorySheetFormTable.row.add($(prototype)).draw();
            $('#productId').val('');
            $('#productName').val('');
            $('#productReference').val('');
        } else {
            highlightInput("#productName", 'shadow-danger');
        }
    });

    $(document).on('click', '.btnRemoveLine', function () {
        inventorySheetFormTable
            .row($(this).parents('tr'))
            .remove()
            .draw();
    });

    $(document).on('click', '#validateInventory', function () {
        if(validateEntries()) {
        inventorySheetFormTable.destroy();
        $('#inventory_sheet_sheetModel').removeAttr('disabled');
        loader.block();
        $('#inventory_sheet_form').submit();
        }else{
            loader.unblock();
            bootbox.confirm({
                title: Translator.trans('loss.errors.title'),
                message:  Translator.trans('loss.errors.message'),
                closeButton: false,
                buttons: {
                    'cancel': {
                        label: Translator.trans('btn.cancel'),
                        className: 'hidden'
                    },
                    'confirm': {
                        label: Translator.trans('loss.errors.validate'),
                        className: 'btn-primary margin-right-left'
                    }
                }
                ,
                callback: function () {
                }
            });
        }
    });

    var previous = '';
    $(document).on('change', '#inventory_sheet_sheetModel', function () {
        if($('#inventory_sheet_sheetModel').val() !== '') {
            if (previous === '') {
                //loadSheetModelInInventorySheet();
                $('#btnCreateInventorySheetFromModel').prop('disabled', false);
            }
        }
    });

    function loadSheetModelInInventorySheet() {
        loader.block();
        ajaxCall({
            method: 'POST',
            url: Routing.generate('load_inventory_entry'),
            data: $("#inventory_sheet_form").serialize()
        }, function (res) {
            if (res.errors === undefined) {
                inventoryBlock.html(res.data[0]);
                inventoryBlock.fadeIn();
                init();
                iventoryListDatatable.ajax.reload();
                loadInventorySheetLines(res.data['lines']);
                $('#table_container').fadeIn();
            }
        }, null, function () {
            loader.unblock();
        })
    }

    $(document).on('click', '#btnCreateInventorySheetFromModel', function (e) {
        if (previous === '') {
            loadSheetModelInInventorySheet();
        } else {
            bootbox.confirm({
                title: Translator.trans('loss.confirm.data_will_be_erased'),
                message: Translator.trans('popup.do_you_confirm_this_action'),
                closeButton: false,
                buttons: {
                    'cancel': {
                        label: Translator.trans('btn.cancel'),
                        className: 'btn-default margin-right-left'
                    },
                    'confirm': {
                        label: Translator.trans('loss.confirm.continue'),
                        className: 'btn-primary margin-right-left'
                    }
                },
                callback: function (result) {
                    if (result) {
                        loadSheetModelInInventorySheet();
                    }
                }
            });
        }
    });

    function loadInventorySheetLines(lines) {
        lines.sort(function(a,b) {
            if(a.order < b.order) {
                return -1;
            }

            if(a.order > b.order) {
                return 1;
            }

            return 0;
        });

        lines.forEach(function (elem) {
            var refProduct = elem.refProduct;
            var idProduct = elem.idProduct;
            var productName = elem.productName;

            /**
             * needed to make unit convertion (total purpose maybe)
             */
            var productUsageQty = elem.productUsageQty;
            var productInventoryQty = elem.productInventoryQty;

            var containerLines = $('#containerLines');
            var nbr = parseInt(containerLines.attr('data-count'));
            containerLines.attr('data-count', nbr + 1);

            var prototype = containerLines.data('prototype')
                .replace(/_line_number_/g, nbr)
                .replace(/_id_product_/g, idProduct)
                .replace(/_usage_product_cnt_/g, '')
                .replace(/_inventory_product_cnt_/g, '')
                .replace(/_exped_product_cnt_/g, '')

                .replace(/_inventory_product_usage_unit_label_/g, Translator.trans(elem.labelUnitUsage))
                .replace(/_inventory_product_inventory_unit_label_/g, Translator.trans(elem.labelUnitInventory))
                .replace(/_inventory_product_exped_unit_label_/g, Translator.trans(elem.labelUnitExped))
                .replace(/_label_product_/g, productName);
            if (refProduct !== undefined) {
                prototype = prototype
                    .replace(/_ref_product_/g, refProduct);
            } else {
                prototype = prototype
                    .replace(/_ref_product_/g, '');
            }
            inventorySheetFormTable.row.add($(prototype)).draw();
        });
    }

    $(document).on('click', '.btnEntryInventory', function (e) {
        var self = $(this);
        var id = $(this).attr('data-id');
        var params = {
            validated: false
        };
        if ($("#inventory_sheet_id").val() !== '') {
            params.inventorySheet = $("#inventory_sheet_id").val();
        }
        loader.block();
        ajaxCall({
            method: 'GET',
            url: Routing.generate('inventory_entry', {
                'inventorySheet': id
            })
        }, function (res) {
            if (res.errors === undefined) {
                inventoryBlock.html(res.data[0]);
                inventoryBlock.fadeIn();
                init();
            } else {

            }
        }, null, function () {
            loader.unblock();
        })
    });

    $(document).on('click', '.btnDetailsInventory', function (e) {
        var self = $(this);
        var id = $(this).attr('data-id');
        loader.block(self);
        ajaxCall({
            method: 'GET',
            url: Routing.generate('inventory_sheet_details', {
                'inventorySheet': id
            })
        }, function (res) {
            if (res.errors === undefined) {

                showDefaultModal(Translator.trans('inventory.title.consult'), res.data[0], '', 1000, null);

                //inventoryBlock.html(res.data[0]);
                //inventoryBlock.fadeIn();
                init();
            }
        }, null, function () {
            loader.unblock(self);
        })
    });

    var selectedItem = null;

    function initProductAutocomplete() {
        var productIdInput = $("#productId");
        var productNameInput = $("#productName");
        var productReference = $('#productReference');
        productReference.autocomplete({
            autoFill: true,
            source: function (request, response) {
                var term = request.term;
                if (termKey(term) in productCodeCache) {
                    response(productCodeCache[termKey(term)]);
                    return;
                }
                ajaxCall({
                        url: Routing.generate('find_active_products', {
                            code: term,
                            selectedType: "article",
                            filterSecondary: 'true'
                        }),
                        method: GET
                    },
                    function (res) {
                        if (res.errors == undefined) {
                            productCodeCache[termKey(term)] = $.map(res.data[0], function (item) {
                                return {
                                    id: item.id,
                                    name: item.name,
                                    label: item.externalId,
                                    value: item.externalId,
                                    externalId: item.externalId,
                                    labelUnitUsage: item.labelUnitUsage,
                                    labelUnitInventory: item.labelUnitInventory,
                                    labelUnitExped: item.labelUnitExped
                                };
                            });
                            response(productCodeCache[termKey(term)]);
                        }
                    }, null, null, true
                );
            },
            select: function (event, ui) {
                productNameInput.val(ui.item.name);
                productIdInput.val(ui.item.id);
                selectedItem = ui.item;
            }
        });
        productNameInput.autocomplete({
            autoFill: true,
            source: function (request, response) {
                var term = request.term;
                if (termKey(term) in productNameCache) {
                    response(productNameCache[termKey(term)]);
                    return;
                }
                ajaxCall({
                        url: Routing.generate('find_active_products', {
                            term: term,
                            selectedType: "article",
                            filterSecondary: 'true'
                        }),
                        method: GET
                    },
                    function (res) {
                        if (res.errors == undefined) {
                            productNameCache[termKey(term)] = $.map(res.data[0], function (item) {
                                return {
                                    id: item.id,
                                    name: item.name,
                                    label: item.name,
                                    externalId: item.externalId,
                                    labelUnitUsage: item.labelUnitUsage,
                                    labelUnitInventory: item.labelUnitInventory,
                                    labelUnitExped: item.labelUnitExped
                                };
                            });
                            response(productNameCache[termKey(term)]);
                        }
                    }, null, null, true
                );
            },
            select: function (event, ui) {
                productIdInput.val(ui.item.id);
                productReference.val(ui.item.externalId);
                selectedItem = ui.item;
            }
        });
    }

    $(document).on('keyup', '.qty-input', function () {

        var regex = /^[0-9]+([,\.]{1}[0-9]+)?$/;

        if($(this).val().match(regex) ==null && $(this).val()!='' && $(this).val()!=null) {
            $(this).addClass('shadow-danger');
            $('.paginate_button').addClass('disabled');
            $('#inventorySheetFormTable_length select').attr('disabled', true);
        }else{

            $(this).removeClass('shadow-danger');
            if(! validateEntries()){
                $('.paginate_button').addClass('disabled');
                $('#inventorySheetFormTable_length select').attr('disabled',true);
            }else{
                $('.paginate_button').removeClass('disabled');
                $('#inventorySheetFormTable_length select').attr('disabled',false);

                var tr = $($(this).closest('tr'));
                //var thStock = $(tr.find('.theorical_stock').first());
                var rlStock = $(tr.find('.real_stock').first());
                // var usageQty = parseFloat(thStock.attr('data-usage-qty'));
                var usageQty = parseFloat(rlStock.attr('data-usage-qty'));
                //var inventoryQty = parseFloat(thStock.attr('data-inventory-qty'));
                var inventoryQty = parseFloat(rlStock.attr('data-inventory-qty'));
                // var theoricStock = parseFloat(thStock.attr('data-theoric-stock'));
                var realStock = parseFloat(rlStock.attr('data-real-stock'));
                //var unitLabel = thStock.attr('data-product-inventory-unit-label');
                var unitLabel = rlStock.attr('data-product-inventory-unit-label');

                var expedCnt = parseFloat($(tr.find('.expedCnt').first()).val());
                var inventoryCnt = parseFloat($(tr.find('.inventoryCnt').first()).val());
                var usageCnt = parseFloat($(tr.find('.usageCnt').first()).val());
                //var result = theoricStock;
                var result = realStock;
                result += (inventoryCnt + expedCnt * inventoryQty + usageCnt/usageQty)-realStock;
                // thStock.text(result + " " + unitLabel);
                rlStock.text(result.toFixed(2) + " " + unitLabel);

            }
        }
    });

    // construct termKey for cache purpose
    function termKey(term) {
        return term;
    }

    // prevent form from submission when press enter key
    $(window).keydown(function (event) {
        if (event.keyCode == KEY_ENTER) {
            event.preventDefault();
            return false;
        }
    });

    var keyUpNamespace = 'next_cell';
    $(document).on('focus', '.qty-input', function () {
        var self = this;
        var e = shortcutController.add(KEY_ENTER, null, null, function () {
            var columnIndex = inventorySheetFormTable.cell($(self).closest('td')[0]).index().column;
            var nextCell = $($($($(self).closest('tr').next('tr')[0]).children('td')[columnIndex]).find('input')[0]);
            nextCell.focus();
            nextCell.select();
        }, keyUpNamespace);
        $(self).focusout(function () {
            $(document).unbind('keyup' + '.' + keyUpNamespace);
        });
    });
    function  validateEntries(){
        var valid = true;
        var regex = /^[0-9]+([,\.]{1}[0-9]+)?$/;
        $('.splitted_input').each(function() {
            if($(this).val().match(regex) ==null && $(this).val()!='' && $(this).val()!=null && $(this).val() !='_inventory_initial_qty_'){
                $(this).addClass('shadow-danger');
                valid=false;
            }

        });
        return valid;
    }
    $(document)
        .on('focus', "#inventory_sheet_sheetModel", function () {
            previous = this.value;
        })
        .on('change', '#inventory_sheet_sheetModel', function () {
            event.preventDefault();
            if ($(this).val() != '') {
                $('#btnCreateInventorySheetFromModel').removeAttr("disabled");
            } else {
                $('#btnCreateInventorySheetFromModel').attr("disabled", 'disabled');
            }
        });

});
