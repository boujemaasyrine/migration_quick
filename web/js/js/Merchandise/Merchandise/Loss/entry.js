/**
 * Created by mchrif on 10/02/2016.
 */
var loss = [];
var productNameCache = {};
var productCodeCache = {};
var lossEntryTable = null;
var previous = "";
var regex = /^[0-9]+([,\.]{1}[0-9]+)?$/;

$(document)
    .on('focus', "#loss_sheet_model", function () {
        previous = $(this).val();
    })
    .on('change', '#loss_sheet_model', function () {
        event.preventDefault();
        if ($(this).val() != '') {
            if (previous !== "") {
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
                            getLineByLoss($('#loss_sheet_model').val());
                        }
                    }
                });

            } else {
                getLineByLoss($('#loss_sheet_model').val());
            }
        }
    });

function calculateSum(self) {
    var parentLine = $($(self).closest('tr')[0]);
    var currentTotalLossInput = $($($(self).closest('tr').children('td')[2]).find('input')[0]);
    var total = 0;
    currentTotalLossInput.val(total);

    $.each(parentLine.find('.qty-input'), function () {
        if ($(this).val() != '') {
            total = parseFloat($(this).val()) + parseFloat(total);
        }
        else {
            total = $(this).val() + parseFloat(total);
        }
        currentTotalLossInput.val(floatToString(total));
    })
}
$(document).on('keyup', '.splitted_input', function () {

    if ($(this).val().match(regex) == null && $(this).val() != '' && $(this).val() != null) {
        $(this).addClass('shadow-danger');
        $('.paginate_button').addClass('disabled');
        $('#loss-entry-table_length select').attr('disabled', true);
    } else {
        $(this).removeClass('shadow-danger');
        if (!validateEntries()) {
            $('.paginate_button').addClass('disabled');
            $('#loss-entry-table_length select').attr('disabled', true);
        } else {
            $('.paginate_button').removeClass('disabled');
            $('#loss-entry-table_length select').attr('disabled', false);
        }
    }
});

function validateEntries() {
    var valid = true;

    $('.splitted_input').each(function () {
        if ($(this).val().match(regex) == null && $(this).val() != '' && $(this).val() != null && $(this).val() != '_inventory_initial_qty_') {
            $(this).addClass('shadow-danger');
            valid = false;
        }

    });
    return valid;
}
$(document).on('click', '#validate-loss-product', function () {
    loader.block();
    if (validateEntries()) {
        lossEntryTable.destroy();
        $('#loss_sheet_status').val('set');
        sheet = $('#id-sheet').text();
        var url = null;
        if (yesterdayLoss) {
            url = 'previous_day_loss';
        } else {
            url = 'loss_entry';
        }
        $('#lossSetForm').attr("action", Routing.generate(url, {
            lossSheet: $('#loss_sheet_id').val(),
            type: lossSheetType
        }));
        $('#loss_sheet_type').removeAttr("disabled");
        $('#loss_sheet_entryDate').removeAttr("disabled");
        $('#lossSetForm').submit();
    } else {
        loader.unblock();
        bootbox.confirm({
            title: Translator.trans('loss.errors.title'),
            message: Translator.trans('loss.errors.message'),
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

function getLineByLoss(modelId) {
    loader.block();
    ajaxCall({
            url: Routing.generate('get_line_by_loss', {modelId: modelId}),
            dataType: 'json'
        },
        function (data) {
            if (typeof data.data != 'undefined' && data.data.length != 0) {
                $('#table_loss').html(data.data['0']);
                initProductAutocomplete();
                initLossEntryTable();
                loadLossSheetLines(data.data['lines']);
                $('#table_loss').fadeIn();
            } else {
                Notif.alert(Translator.trans('loss.entry.js.no_loss_sheet'), 500, 3000);
            }
            loader.unblock();
        });
}

function loadLossSheetLines(lines) {
    lines.sort(function (a, b) {
        if (a.order < b.order) {
            return -1;
        }

        if (a.order > b.order) {
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

        var containerLines = $('#loss_list_article');
        var nbr = parseInt(containerLines.attr('data-count'));
        containerLines.attr('data-count', nbr + 1);
        var prototype = containerLines.data('prototype')
            .replace(/_line_number_/g, nbr)
            .replace(/_product_id_/, idProduct)

            .replace(/_first_entry_cnt_/g, '')
            .replace(/_second_entry_cnt_/g, '')
            .replace(/_third_entry_cnt_/g, '')

            .replace(/_product_reference_/g, refProduct)
            .replace(/_product_name_/g, productName);

        if (lossSheetType === "articles_loss_model") {
            prototype = prototype.replace(/_qty_modal_btn_/g, '<span class="text-muted input-group-addon setUnitsInDetails btn_open_qtys_modal" style=" padding-right: 2px; padding-left: 2px; float: left; color:#000; background: #fff;"> <i class="glyphicon glyphicon-option-vertical"></i></span>')
                .replace(/_data_block_/g, 'data-inventory-qty="' + productInventoryQty + '" data-usage-qty="' + productUsageQty + '" data-inventory-unit-label="' + elem.labelUnitInventory + '" data-usage-unit-label="' +
                    elem.labelUnitUsage + '" data-expedition-unit-label="' + elem.labelUnitExped + '" data-product-name="' + productName + '"')
                .replace(/_inventory_label_total_/g, '<span class="text-muted input-group-addon" style="margin-right: 5px; padding-right: 2px; padding-left: 2px;">' + Translator.trans(elem.labelUnitInventory) + '</span>')
                .replace(/_inventory_label_splited_/g, '<span class="text-muted input-group-addon" style="margin-right: 5px; padding-right: 2px; padding-left: 2px;">' + Translator.trans(elem.labelUnitInventory) + '</span>');
        } else {
            var soldingCanalsId = elem.soldingCanalsId;
            prototype = prototype.replace(/_qty_modal_btn_/g, '')
                .replace(/_data_block_/g, '')
                .replace(/_inventory_label_total_/g, '')
                .replace(/_inventory_label_splited_/g, '')
                .replace(/__name__/g, nbr)
                .replace(/_solding_canals_id_/g, soldingCanalsId);
            var isTransformedProduct = elem.isTransformedProduct;
            if (isTransformedProduct === false) {
                prototype = $(prototype);
                $(prototype.find('.solding_canals')).html('');
            }
        }
        lossEntryTable.row.add($(prototype)).draw();
    });
}

$(document).on('click', '#btnAddNewLossSheetLine', function () {
    var idProduct = $('#productId').val();
    var productReference = $('#productReference').val();
    var productName = $('#productName').val();
    if (idProduct != '' && productName != '') {
        var containerLossListLines = $('#loss_list_article');
        var nbr = parseInt(containerLossListLines.attr('data-count'));
        containerLossListLines.attr('data-count', nbr + 1);
        var prototype = containerLossListLines.data('prototype')
            .replace(/_line_number_/g, nbr)
            .replace(/_product_reference_/g, productReference)
            .replace(/_product_id_/g, idProduct)

            .replace(/_first_entry_cnt_/g, '')
            .replace(/_second_entry_cnt_/g, '')
            .replace(/_third_entry_cnt_/g, '')

            .replace(/_product_name_/g, productName);
        if (lossSheetType === "articles_loss_model") {
            var dataInventoryQty = selectedArticle.inventoryQty;
            var dataUsageQty = selectedArticle.usageQty;
            var dataInventoryUnitLabel = selectedArticle.labelUnitInventory;
            var dataUsageUnitLabel = selectedArticle.labelUnitUsage;
            var dataExpeditionUnitLabel = selectedArticle.labelUnitExped;

            prototype = prototype.replace(/_qty_modal_btn_/g, '<span class="text-muted input-group-addon setUnitsInDetails btn_open_qtys_modal" style="float: left; color:#000; background: #fff; padding-right: 2px; padding-left: 2px;"> <i class="glyphicon glyphicon-option-vertical"></i></span>')
                .replace(/_data_block_/g, 'data-inventory-qty="' + dataInventoryQty + '" data-usage-qty="' + dataUsageQty + '" data-inventory-unit-label="' + dataInventoryUnitLabel + '" data-usage-unit-label="' +
                    dataUsageUnitLabel + '" data-expedition-unit-label="' + dataExpeditionUnitLabel + +'" data-product-name="' + productName + '"')
                .replace(/_inventory_label_total_/g, '<span class="text-muted input-group-addon" style="margin-right: 5px; padding-right: 2px; padding-left: 2px;">' + Translator.trans(dataInventoryUnitLabel) + '</span>')
                .replace(/_inventory_label_splited_/g, '<span class="text-muted input-group-addon" style="margin-right: 5px; padding-right: 2px; padding-left: 2px;">' + Translator.trans(dataInventoryUnitLabel) + '</span>');
        } else {
            var soldingCanalsId = selectedArticle.soldingCanalsId;
            prototype = prototype.replace(/_qty_modal_btn_/g, '')
                .replace(/_data_block_/g, '')
                .replace(/_inventory_label_total_/g, '')
                .replace(/_inventory_label_splited_/g, '')
                .replace(/__name__/g, nbr)
                .replace(/_solding_canals_id_/g, soldingCanalsId);
            var isTransformedProduct = selectedArticle.isTransformedProduct;
            if (isTransformedProduct === false) {
                prototype = $(prototype);
                $(prototype.find('.solding_canals')).html('');
            } else {
                prototype = $(prototype);
                var firstAllowedCanal = JSON.parse($(prototype.find('.solding_canals')[0]).attr('data-allowed-solding-canals'))[0];
                $(prototype.find('.solding_canals')[0]).val(firstAllowedCanal);
            }
        }
        lossEntryTable.row.add($(prototype)).draw();
        $('#productId').val('');
        $('#productName').val('');
        $('#productReference').val('');
    } else {
        highlightInput("#productName", 'shadow-danger');
    }
});

var selectedArticle = null;
function initProductAutocomplete() {
    var productIdInput = $("#productId");
    var productNameInput = $("#productName");
    var productReference = $('#productReference');

    if (lossSheetType === "articles_loss_model") {
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
                            selectedType: $('#sheet_model_linesType').val(),
                            filterSecondary: 'true'
                        }),
                        method: GET
                    },
                    function (res) {
                        if (res.errors == undefined) {
                            productCodeCache[termKey(term)] = $.map(res.data[0], function (item) {
                                var obj = {
                                    id: item.id,
                                    name: item.name,
                                    label: item.externalId,
                                    value: item.externalId,
                                    externalId: item.externalId
                                };

                                if (lossSheetType === "articles_loss_model") {
                                    obj = $.extend(obj, {
                                        inventoryQty: item.inventoryQty,
                                        usageQty: item.usageQty,
                                        labelUnitInventory: item.labelUnitInventory,
                                        labelUnitUsage: item.labelUnitUsage,
                                        labelUnitExped: item.labelUnitExped
                                    })
                                }

                                return obj;
                            });
                            response(productCodeCache[termKey(term)]);
                        }
                    }, null, null, true
                );
            },
            select: function (event, ui) {
                productNameInput.val(ui.item.name);
                productIdInput.val(ui.item.id);
                selectedArticle = ui.item;
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
                            selectedType: $('#sheet_model_linesType').val(),
                            filterSecondary: 'true'
                        }),
                        method: GET
                    },
                    function (res) {
                        if (res.errors == undefined) {
                            productNameCache[termKey(term)] = $.map(res.data[0], function (item) {
                                var obj = {
                                    id: item.id,
                                    name: item.name,
                                    label: item.externalId + '- ' + item.name,
                                    value: item.name,
                                    externalId: item.externalId
                                };

                                if (lossSheetType === "articles_loss_model") {
                                    obj = $.extend(obj, {
                                        inventoryQty: item.inventoryQty,
                                        usageQty: item.usageQty,
                                        labelUnitInventory: item.labelUnitInventory,
                                        labelUnitUsage: item.labelUnitUsage,
                                        labelUnitExped: item.labelUnitExped
                                    })
                                }

                                return obj;
                            });
                            response(productNameCache[termKey(term)]);
                        }
                    }, null, null, true
                );
            },
            select: function (event, ui) {
                productIdInput.val(ui.item.id);
                productReference.val(ui.item.externalId);
                selectedArticle = ui.item;
            }
        });
    } else {
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
                            selectedType: $('#sheet_model_linesType').val(),
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
                                    label: item.codePlu + '- ' + item.name,
                                    value: item.codePlu,
                                    codePlu: item.codePlu,
                                    isTransformedProduct: item.isTransformedProduct,
                                    soldingCanalsId: item.soldingCanalsId
                                };
                            });
                            response(productCodeCache[termKey(term)]);
                        }
                    }, null, null, true
                );
            },
            select: function (event, ui) {
                productIdInput.val(ui.item.id);
                productNameInput.val(ui.item.name);
                //productReference.val(ui.item.codePlu);
                selectedArticle = ui.item;
            }
        });
        productNameInput.autocomplete({
            autoFill: true,
            source: function (request, response) {
                var term = request.term;
                if (termKey(term) in productCodeCache) {
                    response(productCodeCache[termKey(term)]);
                    return;
                }
                ajaxCall({
                        url: Routing.generate('find_active_products', {
                            term: term,
                            selectedType: $('#sheet_model_linesType').val(),
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
                                    label: item.codePlu + '- ' + item.name,
                                    value: item.name,
                                    codePlu: item.codePlu,
                                    isTransformedProduct: item.isTransformedProduct,
                                    soldingCanalsId: item.soldingCanalsId
                                };
                            });
                            response(productCodeCache[termKey(term)]);
                        }
                    }, null, null, true
                );
            },
            select: function (event, ui) {
                productIdInput.val(ui.item.id);
                productReference.val(ui.item.codePlu);
                selectedArticle = ui.item;
            }
        });
    }


}
function termKey(term) {
    return term + '_' + $('#sheet_model_linesType').val();
}

function initLossEntryTable() {
    lossEntryTable = initSimpleDataTable('#loss-entry-table', {
        "lengthChange": true,
        "searching": true,
        "processing": false,
        "ordering": false,
        "paginate": true
    });
}

var keyUpNamespace = 'next_cell';
$(document).on('focus', '.qty-input', function () {
    var self = $(this);
    var e = shortcutController.add(KEY_ENTER, null, null, function () {
        var columnIndex = lossEntryTable.cell($(self).closest('td')[0]).index().column;
        var nextCell = $($($($(self).closest('tr').next('tr')[0]).children('td')[columnIndex]).find('input')[0]);
        nextCell.focus();
        nextCell.select();
    }, keyUpNamespace);
    $(self).focusout(function () {
        $(document).unbind('keyup' + '.' + keyUpNamespace);
    });
});

$(function () {

    initProductAutocomplete();
    initLossEntryTable();

    $(document).on('focus', '.solding_canal_input', function () {
        var self = $(this);
        var soldingCanalId = JSON.parse($(this).closest('.solding_canals').attr('data-allowed-solding-canals'));
        var options = $(this).find('option');

        options.each(function (index, elem) {
            if ($.inArray(parseInt($(elem).val()), soldingCanalId) === -1) {
                $(elem).attr('disabled', 'disabled');
            } else {
                $(elem).removeAttr('disabled');
            }
        });
    });

    $(document)
        .on('keyup', '.qty-input', function () {
            calculateSum(this);
        });

    $(document).on('click', '.btnRemoveSheetLossLine', function () {
        var elem = $(this).closest('tr')[0];
        var row = lossEntryTable.row(elem);
        row.remove();
        elem.remove();
    });

    // prevent form from submission when press enter key
    $(window).keydown(function (event) {
        if (event.keyCode == KEY_ENTER) {
            event.preventDefault();
            return false;
        }
    });

    var btnThatOpenedTheModal = null;
    var inventoryQty = null;
    var usageQty = null;
    $(document).on('click', '.setUnitsInDetails', function (e) {
        var qtyInput = $($(this).closest('div').find('input')[0]);
        var closestRow = $(this).closest('tr');

        var inventoryUnitLabel = $(closestRow).attr('data-inventory-unit-label');
        var usageUnitLabel = $(closestRow).attr('data-usage-unit-label');
        var expeditionUnitLabel = $(closestRow).attr('data-expedition-unit-label');
        var productName = $(closestRow).attr('data-product-name');

        inventoryQty = $(closestRow).attr('data-inventory-qty');
        usageQty = $(closestRow).attr('data-usage-qty');

        var proto = $('#formContainer').html();
        proto = proto.replace(/_inventory_initial_qty_/g, floatToString(parseFloat(qtyInput.val())))
            .replace(/_form_id_/g, 'qtysForm')
            .replace(/_unit_usage_/g, Translator.trans(usageUnitLabel))
            .replace(/_unit_inventory_/g, Translator.trans(inventoryUnitLabel))
            .replace(/_unit_exped_/g, Translator.trans(expeditionUnitLabel));

        btnThatOpenedTheModal = $(this);
        showDefaultModal(Translator.trans('loss.notification.entry_loosed_qty', {productName: productName}), proto, $('#qtysFormFooter').html(), '98%');
        $("#default-modal-box #usageQtyInput").first().focus();
        //$('#totalQty').text(parseFloat(qtyInput.val()));
        $('.error').html('');
        $('#qtysForm').validate(
            {
                rules: {
                    usageInput: {regex: regex},
                    inventoryInput: {regex: regex},
                    expedInput: {regex: regex}
                },
                messages: {},
                errorPlacement: function (error, element) {
                    var placement = $(element).attr('data-error');
                    if (placement) {
                        $(placement).append(error)
                    } else {
                        error.insertAfter(element);
                    }
                }
            }
        );
        calculateTotalModal();
    });

    $(document).on('click', '#saveQtys', function () {
        validateSaveQtys();
    });

    function validateSaveQtys() {
        if ($('#qtysForm').valid()) {
            var qty = parseFloat($('#qtysForm #totalQty').text());
            var qtyInput = $($(btnThatOpenedTheModal.closest('div')).find('input')[0]);
            qtyInput.val(floatToString(qty));
            calculateSum(btnThatOpenedTheModal);
            $('#default-modal-box').modal('hide');
        }
    }

    $(document).on('keyup', "#expeditionQtyInput", function (e) {
        if (e.keyCode == KEY_ENTER) {
            validateSaveQtys();
        }
    });
    $(document).on('keyup', "#inventoryQtyInput", function (e) {
        if (e.keyCode == KEY_ENTER) {
            validateSaveQtys();
        }
    });
    $(document).on('keyup', "#usageQtyInput", function (e) {
        if (e.keyCode == KEY_ENTER) {
            validateSaveQtys();
        }
    });

    $(document).on('keyup', '#qtysForm input', function (e) {
        calculateTotalModal();
    });

    function calculateTotalModal() {
        var total = 0;
        $('#qtysForm input').each(function (index, elem) {
            if ($(elem).val() && !isNaN($(elem).val())) {
                if ($($(elem)[0]).attr('id') == 'usageQtyInput') {
                    total += parseFloat($(elem).val()) / parseFloat(usageQty);
                } else if ($($(elem)[0]).attr('id') == 'expeditionQtyInput') {
                    total += parseFloat($(elem).val()) * parseFloat(inventoryQty);
                } else {
                    total += parseFloat($(elem).val());
                }
            }
        });

        $('#qtysForm #totalQty').html(floatToString(total));
    }

});
