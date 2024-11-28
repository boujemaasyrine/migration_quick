/**
 * Ce Fichier contient tout le JS pour l'ajout et la modification d'une commande
 * @type {null}
 */
var suppliers = null;
var products = [];
var supplierPlanning = null;
var orderPicker = null;
var pendingsOrders = null;
var supplierSelector = "#order_supplier";
var orderDateSelector = "#order_dateOrder";
var deliveryDateSelector = "#order_dateDelivery";
var caPrevSelector = "#order_caPrev";
var horsPlanning = false;
var consultingTableInit = false;
var deliveryDate = null;

ajaxCall({
    url: Routing.generate('find_suppliers'),
    dataType: 'json'
}, function (data) {
    suppliers = data.data
});

function selectSupplier(id) {
    $(supplierSelector).find("option").removeAttr('selected');
    $(supplierSelector).find("option[value=" + id + "]").attr('selected', 'selected');
}

function getProductBy(key, value) {
    var i;
    for (i in products) {
        var p = products[i];
        if (p.hasOwnProperty(key)) {
            if (p[key].toString() == value.toString()) {
                return p;
            }
        }
    }
    return null;
}

function updateTotalValorization() {
    var val = 0;
    $.each($('.cmd-line'),function(key,value){
        var lineVal = parseInt($(value).find('.product-qty-input').val()) *
            parseFloat($(value).find('.product-unit-price-input').html()) ;
        $(value).find('.val_line').html(floatToString(lineVal));
        val  = val  + lineVal ;
    });
    $('#order-val-total').html(floatToString(val));
}

function addNewLine(isSubmitted) {

    $('#code-product').removeClass('shadow-danger');
    $('#qty-cmd').removeClass('shadow-danger');

    if (typeof isSubmitted != 'undefined' && $('#code-product').val().trim() == '') {
        return true;
    }

    if ($('#code-product').val().trim() == '') {
        $('#code-product').focus();
        $('#code-product').addClass('shadow-danger');
        return false;
    }

    if ($('#qty-cmd').val().trim() == '' || parseInt($('#qty-cmd').val().trim()) < 0) {
        $('#qty-cmd').focus();
        $('#qty-cmd').addClass('shadow-danger');
        return false;
    }

    var labelProduct = $('#label-product').val();
    var expdUnit = $('#expd-unit').html();
    var codeProduct = $('#code-product').val();
    var qtyCmd = $('#qty-cmd').val();

    var product = getProductBy('code', codeProduct);

    if (product == null) {
        $('#code-product').focus();
        $('#code-product').addClass('shadow-danger');
        return false;
    }

    var numLigne = parseInt($('#products-table').attr('line-count'));
    $('#products-table').attr('line-count', numLigne + 1);

    var newLine = $('#products-table')
        .attr('data-prototype')
        .replace(/_line_number_/g, numLigne)
        .replace(/__name_product__/g, labelProduct)
        .replace(/__unit__/g, Translator.trans(expdUnit))
        .replace(/__unit_price__/g, floatToString(product.unit_price))
        .replace(/__val_line__/g, floatToString(product.unit_price * qtyCmd))
        .replace(/__rapport_unit__/g, rapportUnits(product))
        .replace(/__ref_product__/g, codeProduct);


    $("#new-line").before(newLine);

    var newLineQtyDom = $('.cmd-line:last .stock_qty');
    newLineQtyDom.html("<span class='min-loader'></span>");
    getProductQty(product, function (result, dom) {
        if (result.type == 'real') {
            dom.html(result.qty + " " + Translator.trans(result.inv_unit_label) + " (R)");
        } else {
            dom.html(result.qty + " " + Translator.trans(result.inv_unit_label) + " (T)");
        }
    }, newLineQtyDom);

    $('#order_lines_' + numLigne + '_product').val(product.id);
    $('#order_lines_' + numLigne + '_qty').val(qtyCmd);

    updateTotalValorization();

    resetNewLine();

    $('#code-product').focus();
}

function setInputDate(selector, date) {
    var picker = $(selector).pickadate('picker');
    picker.set('select', date, {format: 'dd/mm/yyyy'});
    if (date == null || date.trim() == '') {
        resetInputValue(selector);
    } else {
        setInputValue(selector, date);
    }
}

function getOrderDate(id) {
    var idZoneError = 'orderDateZoneError';
    $("#" + idZoneError).remove();
    resetInputValue(orderDateSelector);
    resetInputValue(order_dateDelivery);
    $('#order_dateOrder_error_zone').hide();
    ajaxCall({
        url: Routing.generate("get_next_planning", {'supplier': id}),
        dataType: 'json'
    }, function (data) {
        if (data.data != null) {
            setInputDate(orderDateSelector, data.data.order);
            setInputDate(deliveryDateSelector, data.data.delivery);
            highlightDeliveryDate(data.data.delivery);
            deliveryDate = data.data.delivery;
            var order = testPendingOrderExist();
            if (order != null) {
                showEditOrderPopUp(order);
            }
        } else {
            appendErrorMsgTozone($('.orderDateInputFieldZone'), Translator.trans('command.new.js.date_not_found'), idZoneError);
        }

    });
}

function getProductsBySupplier(supplierId) {
    products = [];
    ajaxCall({
            url: Routing.generate('get_product_by_supplier', {supplier: supplierId}),
            dataType: 'json'
        },
        function (data) {
            if (typeof data.data != 'undefined' && data.data.length > 0) {
                products = data.data;
            } else {
                showPopError(Translator.trans('command.new.js.product_not_found'));
            }
        })
}

function resetOrderForm() {
    resetInputValue(caPrevSelector);
    resetInputValue(orderDateSelector);
    resetInputValue(deliveryDateSelector);
    $(".cmd-line").remove();
    resetNewLine();
    $('.form-error').remove();
}

function resetNewLine() {
    $("#code-product").val("");
    $("#label-product").val("");
    $("#expd-unit").html("");
    $("#stock-qty").html("-");
    $("#qty-cmd").val("");
    $("#new-unit-price").html("");
    $("#rapport_expd_inv").html("");
}

function saveAsDraft(idOrder) {
    //Function NO MORE USED
    $("form[name=order]").attr('action', Routing.generate("save_as_draft", {'order': idOrder}));
    $("form[name=order]").submit();
}

function refreshDatepicker() {
    // THIS FUNCTION IS NO LONGER USED
    orderPicker.set('disable', true);

    if (supplierPlanning == null || supplierPlanning.length == 0) {
        return;
    }

    var disable = [];
    var orderDays = [];
    $.each(supplierPlanning, function (key, value) {
        orderDays.push(value.order + 1);
    });

    for (var j = 1; j < 8; j++) {
        if ($.inArray(j, orderDays) == -1) {
            disable.push(j);
        }
    }
    orderPicker.set('disable', false);
    orderPicker.set('disable', disable)
}

function getOrderDays() {
    var orderDays = [];

    if (typeof supplierPlanning == 'undefined' || supplierPlanning == null || supplierPlanning.length == 0) {
        return [];
    }

    $.each(supplierPlanning, function (key, value) {
        orderDays.push(value.order);
    });
    return orderDays;
}

function getSuppliersPlanning(supplier) {
    supplierPlanning = null;
    ajaxCall({
        url: Routing.generate('supplier_planning_json', {supplier: supplier}),
        dataType: 'json'
    }, function (data) {
        supplierPlanning = data.data;
        //refreshDatepicker();
        loader.hide();
    });
}

function testPendingOrderExist() {
    var dateOrder = $('#order_dateOrder').val();
    var pendingOrder = searchInArrayByKeyValue(pendingsOrders, 'dateOrder', dateOrder);
    return pendingOrder;
}

function getPendingsOrder(supplier) {
    pendingsOrders = null;
    ajaxCall({
        url: Routing.generate('pendings_orders_by_supplier', {supplier: supplier}),
        dataType: 'json'
    }, function (data) {
        pendingsOrders = data.data;
        var order = testPendingOrderExist();
        if (order != null) {
            showEditOrderPopUp(order);
        }
    });
}

function showEditOrderPopUp(order) {
    var modalBody = $('#confirmation-edit-modal-box .modal-body').html();
    var modalTitlte = $('#confirmation-edit-modal-box .modal-title').html();

    modalBody = modalBody.replace(/__link_to_edit__/, Routing.generate('edit_order', {order: order.id}));

    showDefaultModal(modalTitlte, modalBody, ' ', null, null, false);
}

function resetInputDates() {
    //setInputDate(orderDateSelector, '');
    //setInputDate(deliveryDateSelector, '');
    $('#default-modal-box').modal('hide');
}

function addClassToDate(inputDateId, date, className) {
    var day = $("#" + inputDateId + "_table.picker__table").find(".picker__day.picker__day--infocus[aria-label='" + date + "']");
    if (day.length == 0 || day == null) {
        return;
    }
    day.addClass(className);
}

function addClassToCol(inputDateId, n, className) {
    if (n < 0 || n > 6) {
        return;
    }
    var days = $("#" + inputDateId + "_table.picker__table").find(".picker__day.picker__day--infocus");

    $.each(days, function (key, value) {

        var date = $(value).attr('aria-label');
        var momentDate = moment(date, 'DD/MM/YYYY');

        if (momentDate.day() == n) {
            $(value).addClass(className);
        }
    });
}

function highlightCols(inputDateId, tab, className) {
    $('#' + inputDateId + '_table .selectable-date').removeClass('selectable-date');

    if (typeof tab == 'undefined' || tab.length == 0) {
        return;
    }

    $.each(tab, function (key, value) {
        addClassToCol(inputDateId, value, className);
    });
}

function showConfirmationPopUp() {
    showDefaultModal('Confirmation', $('#confirmation-modal-box').html(), '');
}

function confirmationYes() {
    $('#default-modal-box').modal('hide');
    deliveryDate = null;
    setInputDate(deliveryDateSelector, '');
    $(deliveryDateSelector + "_table .selectable-date").removeClass('selectable-date');
    horsPlanning = true;
}

function confirmationNo() {
    horsPlanning = false;
    $('#default-modal-box').modal('hide');
    resetInputValue(orderDateSelector);
    resetInputValue(order_dateDelivery);
}

function isProductSelectable(product, selectedProducts, categories) {
    if ($.inArray(product.id, selectedProducts) == -1 && ( horsPlanning || categories.length == 0 ||
        ( searchInArrayByKeyValue(categories, 'id', product.category_id) != null) )) {
        return true;
    }
    return false;
}

function highlightDeliveryDate(date) {
    $(deliveryDateSelector + '_table .selectable-date').removeClass('selectable-date');
    addClassToDate("order_dateDelivery", date, 'selectable-date');
}

function showConfirmationDeliveryDate() {
    showDefaultModal('Confirmation', $('#confirmation-delivery-modal-box').html(), '');
}

function confirmationDeliveryNo() {
    $('#default-modal-box').modal('hide');
    setInputDate(deliveryDateSelector, deliveryDate);
}

function confirmationDeliveryYes() {
    $('#default-modal-box').modal('hide');
}

function disableEnableInputs() {

    var inputs = $('form[name=order]')
        .find('input, select, button[type=submit]')
        .not('#order_supplier ')
        .not('.numOrder')
        .not('.selectize-control .selectize-input input')
        .not('#expd-unit');

    if ($('#order_supplier').val() == '') {
        inputs.attr('disabled', 'disabled');
    } else {
        inputs.removeAttr('disabled');
    }

}

function getProductQty(product, callback, dom) {
    ajaxCall({
        url: Routing.generate('last_product_qty', {product: product.id}),
        dataType: 'json'
    }, function (result) {
        if (typeof  result.data != 'undefined') {
            callback(result.data, dom);
        }
    })
}

function initQtyProducts() {
    var lines = $('.cmd-line');
    $.each(lines, function (key, value) {
        var p = {id: $(value).find('.hidden input').val()};
        var dom = $(value).find(".stock_qty");
        dom.html("<span class='min-loader'></span>");
        getProductQty(p, function (result, dom) {
            if (result.type == 'real') {
                dom.html(result.qty + " " + Translator.trans(result.inv_unit_label) + " (R)");
            } else {
                dom.html(result.qty + " " + Translator.trans(result.inv_unit_label) + " (T)");
            }
        }, dom);
    });

}

function rapportUnits(product){
    var l1 = "1 "+Translator.trans(product.unitExp)+" = "+product.inv_ratio+" "+Translator.trans(product.unitInv) ;
    var l2 = "1 "+Translator.trans(product.unitInv)+" = "+product.use_ratio+" "+Translator.trans(product.unitUse) ;
    return l1 + "<br>" + l2 ;
}

$(document).on('change', supplierSelector, function () {
    disableEnableInputs();
    horsPlanning = false;
    resetOrderForm();

    if ($(this).val() != '') {
        loader.show();
        resetInputValue(caPrevSelector);
        getProductsBySupplier($(this).val());
        getOrderDate($(this).val());
        getSuppliersPlanning($(this).val());
        getPendingsOrder($(this).val());
    }

});

$(document).on('click', '.add-line', function () {
    addNewLine();
});

$(document).on('click', '.remove-line', function () {
    $(this).parentsUntil('tbody', 'tr').remove();
    updateTotalValorization();
});

$(document).on('submit', 'form[name=order]', function () {
    $('#order_dateDelivery').removeAttr('disabled');
    var r = addNewLine(true);
    if (r) {
        loader.show();
        return true;
    }
});

$(document).on('blur', '#label-product', function () {
    var product = getProductBy('name', $(this).val());

    if (product == null) {
        $('#code-product').val('');
        $('#expd-unit').html('');
        $('#qty-cmd').val('');
        $('#label-product').val('');
        $("#stock-qty").html("-");
    }

});
$(function () {

    initQtyProducts();

    orderPicker = $('#order_dateOrder').pickadate('picker');

    if ($(supplierSelector).val() != '') {
        getProductsBySupplier($(supplierSelector).val());
        getSuppliersPlanning($(supplierSelector).val());
    }

    $('#code-product').autocomplete({
        source: function (request, response) {
            var result = [];
            var selectedProducts = [];
            var selectedProductsInputs = [];
            var orderDate = $('#order_dateOrder');
            var momentOrderDate = moment(orderDate.val(), "DD/MM/Y");
            var categories = searchInArrayByKeyValue(supplierPlanning, 'order', momentOrderDate.day());
            if (categories != null) {
                categories = categories.categories;
            } else {
                categories = [];
            }
            $.each(products, function (key, value) {
                selectedProductsInputs = $('.product input');
                $.each(selectedProductsInputs, function (key, value) {
                    selectedProducts.push(parseInt($(value).val()));
                });
                if (isProductSelectable(value, selectedProducts, categories)) {
                    if (value.code.toString().toUpperCase().indexOf(request.term.toUpperCase()) >= 0) {
                        result.push({value: value.code, label: value.code + " (" + value.name + ")", product: value});
                    }
                }
            });

            sortTabByProp(result,'value',request.term);

            response(result);
        },
        select: function (event, ui) {

            $('#stock-qty').html("<span class='min-loader'></span>");
            getProductQty(ui.item.product, function (result, dom) {
                if (result.type == 'real') {
                    dom.html(result.qty + " " + Translator.trans(result.inv_unit_label) + " (R)");
                } else {
                    dom.html(result.qty + " " + Translator.trans(result.inv_unit_label) + " (T)");
                }
            }, $('#stock-qty'));
            $('#expd-unit').html(Translator.trans(ui.item.product.unitExp));
            $('#label-product').val(ui.item.product.name);
            $('#new-unit-price').html(floatToString(ui.item.product.unit_price));
            $('#rapport_expd_inv').html(rapportUnits(ui.item.product));
            $('#qty-cmd').focus();
        },
        autoFocus : true
    });

    $('#label-product').autocomplete({
        source: function (request, response) {
            var result = [];
            var selectedProducts = [];
            var selectedProductsInputs = [];
            var orderDate = $('#order_dateOrder');
            var momentOrderDate = moment(orderDate.val(), "DD/MM/Y");
            var categories = searchInArrayByKeyValue(supplierPlanning, 'order', momentOrderDate.day());
            if (categories != null) {
                categories = categories.categories;
            } else {
                categories = [];
            }
            $.each(products, function (key, value) {
                selectedProductsInputs = $('.product input');
                $.each(selectedProductsInputs, function (key, value) {
                    selectedProducts.push(parseInt($(value).val()));
                });
                if (isProductSelectable(value, selectedProducts, categories)) {
                    if (value.name.toUpperCase().indexOf(request.term.toUpperCase()) >= 0) {
                        result.push({
                            value: value.name,
                            label: value.name + " (" + value.code + ")",
                            product: value
                        });
                    }
                }
            });

            sortTabByProp(result,'value',request.term);
            response(result);
        },
        select: function (event, ui) {

            $('#qty-cmd').focus();

            $('#stock-qty').html("<span class='min-loader'></span>");
            getProductQty(ui.item.product, function (result, dom) {
                if (result.type == 'real') {
                    dom.html(result.qty + " " + Translator.trans(result.inv_unit_label) + " (R)");
                } else {
                    dom.html(result.qty + " " + Translator.trans(result.inv_unit_label) + " (T)");
                }
            }, $('#stock-qty'));

            $('#expd-unit').html(Translator.trans(ui.item.product.unitExp));
            $('#code-product').val(ui.item.product.code);
            $('#new-unit-price').html(floatToString(ui.item.product.unit_price));
            $('#rapport_expd_inv').html(rapportUnits(ui.item.product));

            event.stopImmediatePropagation();
        },
        autoFocus : true

    });

    shortcutController.addCtrlShift(107, function () {
        addNewLine();
    });

    orderPicker.on('close', function () {
        var idZoneError = 'orderDateZoneError';
        $("#" + idZoneError).remove();

        var orderDate = $('#order_dateOrder');
        if (orderDate.val() == null || orderDate.val().trim() == '') {
            return;
        }
        var momentOrderDate = moment(orderDate.val(), "DD/MM/Y");

        if (supplierPlanning == null) {
            resetInputValue("#order_dateDelivery");
            //return;
        } else {
            for (var i = 0; i < supplierPlanning.length; i++) {
                if (supplierPlanning[i].order == momentOrderDate.day()) {
                    var diff = supplierPlanning[i].delivery - supplierPlanning[i].order;
                    if (diff < 0) {
                        diff = 7 + diff;
                    }
                    var delivery = momentOrderDate.add(diff, 'd').format('DD/MM/Y');
                    setInputDate(deliveryDateSelector, delivery);
                    highlightDeliveryDate(delivery);
                    deliveryDate = delivery;
                    horsPlanning = false;
                    break;
                }
            }
            var order = testPendingOrderExist();
            if (order != null) {
                showEditOrderPopUp(order);
            }
        }

        orderDate = $('#order_dateOrder');
        momentOrderDate = moment(orderDate.val(), "DD/MM/Y");
        var orderDays = getOrderDays();
        var exist = false;
        $.each(orderDays, function (key, value) {
            if (value == momentOrderDate.day()) {
                exist = true;
            }
        });

        if (!exist) {
            showConfirmationPopUp();
        }
    });

    orderPicker.on('render', function () {
        highlightCols('order_dateOrder', getOrderDays(), 'selectable-date');
    });

    orderPicker.on('open', function () {
        //Disable j-31
        var today = moment();
        var j_31 = today.subtract(31, 'days');

        orderPicker.set('disable', [
            {
                from: [2000, 1, 1], to: [j_31.year(), j_31.month(), j_31.date()]
            }
        ]);

        highlightCols('order_dateOrder', getOrderDays(), 'selectable-date');
        $('.picker__day--selected').removeClass('picker__day--selected');
        $("#order_dateOrder_table.picker__table")
            .find(".picker__day.picker__day--infocus[aria-label='" + $('#order_dateOrder').val() + "']")
            .addClass('picker__day--selected');
    });

    initPlanningConsulting();
    var keyupEvent = $.Event('keyup');
    keyupEvent.which = 13;
    shortcutController.addSimple(KEY_F7, function () {
        planningTable.column(0).search("",true,false,true).draw();
        if ($('#planning_command_modal').is(':visible')) {
            $('#planning_command_modal').modal('hide');
        } else {
            $('#planning_command_modal').modal('show');
            if ($('#order_supplier').val() != '') {
                var sname = $('#order_supplier').find('option[selected=selected]').html();

               // $('#planning-table_filter input[type=search]').trigger(keyupEvent);
                planningTable.column(0).search("^"+sname+"$",true,false,true).draw();
                $('#planning-table_filter input[type=search]').val(sname);
            }
        }
    });

    $(document).on('keyup','#planning-table_filter input[type=search]',function(){
        planningTable.column(0).search($(this).val(),true,false,true).draw();
    });

    var delivryPicker = $(deliveryDateSelector).pickadate('picker');

    delivryPicker.on('open', function () {
        if (deliveryDate != null) {
            highlightDeliveryDate(deliveryDate);
        }
    });

    delivryPicker.on('set', function () {
        if (deliveryDate != null) {
            highlightDeliveryDate(deliveryDate);
        }
    });

    delivryPicker.on('close', function () {
        if (!horsPlanning) {
            var dateSelected = $(deliveryDateSelector).val();
            if (dateSelected != deliveryDate) {
                showConfirmationDeliveryDate();
            }
        }
    });

    $('#qty-cmd').keypress(function (event) {
        if (event.which == 13) {
            addNewLine();
        }
    });

    $("form[name=order]").keypress(function (event) {
        if (event.which == 13) {
            event.stopPropagation();
            return false;
        }

    });

    $("#order_supplier").selectize();

    if ($("#order_supplier").val() != '') {
        $(supplierSelector).siblings('.selectize-control').find('.selectize-input input').css('position', 'absolute');
    }

    disableEnableInputs();

    updateTotalValorization();

});//End Document Ready

// Prevent the backspace key from navigating back.
$(document).unbind('keydown').bind('keydown', function (event) {
    var doPrevent = false;
    if (event.keyCode === 8) {
        var d = event.srcElement || event.target;
        if ((d.tagName.toUpperCase() === 'INPUT' &&
                (
                d.type.toUpperCase() === 'TEXT' ||
                d.type.toUpperCase() === 'PASSWORD' ||
                d.type.toUpperCase() === 'FILE' ||
                d.type.toUpperCase() === 'SEARCH' ||
                d.type.toUpperCase() === 'EMAIL' ||
                d.type.toUpperCase() === 'NUMBER' ||
                d.type.toUpperCase() === 'DATE' )
            ) ||
            d.tagName.toUpperCase() === 'TEXTAREA') {
            doPrevent = d.readOnly || d.disabled;
        }
        else {
            doPrevent = true;
        }
    }

    if (doPrevent) {
        event.preventDefault();
    }
});

$(document).on('change',".product-qty-input",function(){
    updateTotalValorization();
});

