var orders = [];
var order = null;
var numOrder = null;
var products = [];
var tout = null;
var inited1 = false;
var inited2 = false;
var inited3 = false;

function getOrderList(status, showLoader, hideLoader, callback) {
    if (showLoader != undefined && showLoader == true) {
        loader.show();
    }
    ajaxCall({
        url: Routing.generate("pending_list"),
        method: 'POST',
        data: {
            criteria: {
                status: status
            }
        },
        dataType: 'json'
    }, function (data) {
        orders = orders.concat(data.data);

        if (callback != undefined) {
            callback();
        }

        if (hideLoader != undefined && hideLoader == true) {
            loader.hide();
        }
    });
}

function getOrderBy(key, value) {
    var i;
    for (i in orders) {
        var o = orders[i];
        if (o.hasOwnProperty(key)) {
            if (o[key].toString() == value.toString()) {
                return o;
            }
        }
    }
    return null;
}

function initDeliveryForm(order, initLines) {

    if (order == null) {
        $('#delivery_supplier option').removeProp('selected');
        return;
    }

    $("#delivery_supplier option[value='" + order.supplier + "']").prop('selected', 'selected');

    $('#order_delivery_date').html(order.dateDelivery);
    setInputValue('#orderDate', order.dateOrder);

    if (initLines == undefined || initLines == true) {
        getOrderLines(order);
    }
    updateDeliveryValorisation();

    getProductsBySupplier(order.supplier_id);

}

function getOrderLines(order) {
    loader.show();
    $('#delivery-entry-table tbody').html('');
    ajaxCall({
        url: Routing.generate('order_lines', {'order': order.id}),
        dataType: 'json'
    }, function (data) {
        var n = 0;
        $.each(data.data, function (key, value) {
            addDeliveryLine(value, n);
            n++;
        });
        $('#delivery-entry-table tbody').attr('count', n);
        updateDeliveryValorisation();
        loader.hide();
    })
}

function addDeliveryLine(order, n) {
    var prototype = $('#delivery-entry-table tbody').attr('data-prototype');

    var lineValorization = floatToString(Math.round((parseFloat(order.price) * parseInt(order.qty)) * 100) / 100);
    var newLine = prototype
        .replace(/__name__/g, n)
        .replace(/__ref_product__/g, order.code)
        .replace(/__name_product__/g, order.article)
        .replace(/__unit__/g, Translator.trans(order.unit_exped))
        .replace(/__ordered_qty__/g, order.qty)
        .replace(/__unit_price__/g, floatToString(order.price))
        .replace(/__line_val__/, lineValorization);

    $('#delivery-entry-table tbody').append(newLine);

    $("#delivery_lines_" + n + "_product_id").val(order.code);
    $("#delivery_lines_" + n + "_qty").val(order.qty);
    $("#delivery_lines_" + n + "_valorization").val(floatToString(Math.round((parseFloat(order.price) * parseInt(order.qty)) * 100) / 100));
    $("#delivery_lines_" + n + "_new option").removeProp('selected');
    $("#delivery_lines_" + n + "_new option[value=0]").prop('selected', 'selected');
    $(".cmd-line ").last().find('.remove-line').remove();

    updateDeliveryValorisation();

}

function updateDeliveryValorisation() {

    var valorisation = 0;

    var valLines = $('.delivery-line-valorisation');

    $.each(valLines, function (key, value) {
        valorisation += parseFloat($(value).val());
    });

    var deliveryVal = floatToString(Math.round(valorisation * 100) / 100);

    $('#delivery_valorization').val(deliveryVal);
    $('#delivery-valorization-span').html(deliveryVal);
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
            }
        })
}

function getSelectedProductsId() {
    var selectedProducts = [];

    $.each($('.cmd-line .hidden input'), function (key, value) {
        selectedProducts.push(parseInt($(value).val()));
    });

    return selectedProducts;
}

function initAutoCompleteForNewLine() {
    $('#code-product').autocomplete({
        source: function (request, response) {
            var result = [];
            $.each(products, function (key, value) {
                if ($.inArray(parseInt(value.code), getSelectedProductsId()) < 0 && value.code.toString().toUpperCase().indexOf(request.term.toUpperCase()) >= 0) {
                    result.push({value: value.code, label: value.code + " (" + value.name + ")", product: value});
                }
            });
            sortTabByProp(result, 'value', request.term);
            response(result);
        },
        select: function (event, ui) {
            $('#name-product').val(ui.item.product.name);
            $('#new-line-unit-price').html(floatToString(ui.item.product.unit_price));
            $('#unit-new-line').html(Translator.trans(ui.item.product.unitExp));
            $('#qty-product').focus();
        },
        autoFocus: true
    });

    $('#name-product').autocomplete({
        source: function (request, response) {
            var result = [];
            $.each(products, function (key, value) {
                if ($.inArray(parseInt(value.code), getSelectedProductsId()) < 0 && value.name.toUpperCase().indexOf(request.term.toUpperCase()) > 0) {
                    result.push({value: value.name, label: value.name + " (" + value.code + ")", product: value});
                }
            });
            sortTabByProp(result, 'value', request.term);
            response(result);
        },
        select: function (event, ui) {
            $('#code-product').val(ui.item.product.code);
            $('#new-line-unit-price').html(floatToString(ui.item.product.unit_price));
            $('#unit-new-line').html(Translator.trans(ui.item.product.unitExp));
            $('#qty-product').focus();
        },
        autoFocus: true
    });
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

function addNewDeliveryLine(isSubmitted) {
    $('#code-product').removeClass('shadow-danger');
    $('#qty-product').removeClass('shadow-danger');

    if (typeof  isSubmitted != 'undefined' && $('#code-product').val().trim() == '') {
        return true;
    }

    if ($('#code-product').val().trim() == '') {
        $('#code-product').focus();
        $('#code-product').addClass('shadow-danger');
        return false;
    }

    if ($('#qty-product').val().trim() == '' ||
        isNaN($('#qty-product').val().trim()) ||
        Number.isInteger(parseFloat($('#qty-product').val().trim())) == false ||
        parseInt($('#qty-product').val().trim()) <= 0
    ) {
        $('#qty-product').focus();
        $('#qty-product').addClass('shadow-danger');
        return false;
    }

    var product = getProductBy('code', $('#code-product').val().trim());

    if (product == null) {
        $('#code-product').focus();
        $('#code-product').addClass('shadow-danger');
        return false;
    }

    var count = parseInt($('#delivery-entry-table tbody').attr('count'));
    $('#delivery-entry-table tbody').attr('count', count + 1);

    var qty = $("#qty-product").val();

    var lineValorization = floatToString(Math.round((parseFloat(product.unit_price) * parseInt(qty)) * 100) / 100);

    var prototype = $('#delivery-entry-table tbody').attr('data-prototype');
    var newLine = prototype
        .replace(/__name__/g, count)
        .replace(/__ref_product__/g, product.code)
        .replace(/__name_product__/g, product.name)
        .replace(/__unit__/g, Translator.trans(product.unitExp))
        .replace(/__ordered_qty__/g, "0")
        .replace(/__unit_price__/g, floatToString(product.unit_price))
        .replace(/__line_val__/, lineValorization);

    $('#delivery-entry-table tbody').append(newLine);

    $("#delivery_lines_" + count + "_product_id").val(product.code);
    $("#delivery_lines_" + count + "_qty").val(qty);
    $("#delivery_lines_" + count + "_observation option[value='not_ordred']").attr('selected', 'selected');
    $("#delivery_lines_" + count + "_valorization").val(lineValorization);


    $('#code-product').val('');
    $('#qty-product').val('');
    $('#name-product').val('');
    $('#new-line-unit-price').html('');

    $('#code-product').focus();
    updateDeliveryValorisation();
    return true;

}

function init() {
    if (!((inited1 && inited2 && inited3))) {
        tout = window.setTimeout(function () {
            return init();
        }, 500);
    } else {
        clearTimeout(tout);
        var idOrder = $('#delivery_order').val();
        order = getOrderBy('id', idOrder);
        initDeliveryForm(order, false);
        loader.hide();
    }

}

function clearIndexes() {
    var lines = $('.cmd-line');
    var idPattern = /delivery_lines_[0-9]*/;
    var namePattern = /delivery\[lines]\[[0-9]*/;

    for (var i = 0; i < lines.length; i++) {
        var inputs = $(lines[i]).find('input, select');
        for (var j = 0; j < inputs.length; j++) {
            var value = $(inputs[j]);
            var id = value.attr('id');
            var name = value.attr('name');
            var newId = id.replace(idPattern, 'delivery_lines_' + i);
            var newName = name.replace(namePattern, 'delivery[lines][' + i);
            value.attr('id', newId);
            value.attr('name', newName);
        }
    }
}

$(document).on('change', '.delivery-line-qty', function (event) {
    if (parseInt($(this).val()) < 0) {
        event.stopPropagation();
        $(this).val(0);
    }
});

$(document).on('change', '#delivery_supplier', function () {

    if ($(this).val() == '') {
        $('#delivery_order option').removeClass('hidden');
    } else {
        $('#delivery_order option').addClass('hidden');
        var self = $(this);
        $.each(orders, function (key, value) {
            if (value.supplier == self.val()) {
                $("#delivery_order option[value='" + value.id + "']").removeClass('hidden');
            }
        });
    }
    $("#delivery_order option[value='']").removeClass('hidden');
    $("#delivery_order option[value='']").prop('selected', 'selected');
    $('.cmd-line').remove();
    $("#delivery-valorization-span").html('0.00');

});

$(document).on('change', '#delivery_order', function () {
    $('#delivery_order option').removeClass('hidden');
    $('#delivery_supplier option').removeProp('selected');
    $("#delivery_supplier option[value='']").prop('selected', 'selected');
    $('.cmd-line').remove();
    $("#delivery-valorization-span").html('0.00');

    var idOrder = $('#delivery_order').val();

    //Test if there's a delivery prepared for the order
    ajaxCall({
        url: Routing.generate('check_delivery_tmp', {order: idOrder})
    }, function (success) {
        if (success.data.exist) {
            window.location.href = Routing.generate('delivery_entry', {tmp: success.data.tmp_id})
        } else {
            order = getOrderBy('id', idOrder);
            initDeliveryForm(order);
        }
    });


});

$(document).on('change', '.delivery-line-qty', function () {
    var qty = $(this);
    var valLine = $(this).parentsUntil('tbody', 'tr').find('.delivery-line-valorisation');
    var orderQty = parseFloat($(this).parentsUntil('tbody', 'tr').find('.order-line-qty').html());
    var unitPrice = parseFloat($(this).parentsUntil('tbody', 'tr').find('.unit-price').html());
    var newVal = Math.round((parseFloat(qty.val()) * unitPrice) * 100) / 100;
    valLine.val(floatToString(newVal));
    $(this).parentsUntil('tbody', 'tr').find('.val-delivery-line').html(floatToString(newVal));
    console.log(qty.val());
    console.log(orderQty);
    if (parseFloat(qty.val()) != orderQty) {
        $(this).parentsUntil('tbody', 'tr').addClass('order-delivery-mismatch');
    } else {
        $(this).parentsUntil('tbody', 'tr').removeClass('order-delivery-mismatch');
    }
    updateDeliveryValorisation();
});

$(document).on('change', '.delivery-line-valorisation', function () {
    updateDeliveryValorisation();
});

$(document).on('click', '.remove-line', function () {

    $(this).parentsUntil('tbody', 'tr').remove();
    updateDeliveryValorisation();
});

$(document).on('click', '#print-bl-btn', function () {
    var form = $("form[name=delivery]");
    form.attr('action', Routing.generate('delivery_entry') + "?download=1");
    form.submit();
});

$(document).on('click', '#submit-bl-btn', function () {
    var form = $("form[name=delivery]");
    form.attr('action', Routing.generate('delivery_entry'));
    form.submit();
});

$("form[name='delivery']").on('submit', function () {
    var r = addNewDeliveryLine(true);
    if (r) {
        console.log($(this).attr('action'));
        if ($(this).attr('action').indexOf("?download=1") < 0)
            loader.show();
        return true;
    }
});

$("form[name=delivery]").on('submit', function () {
    return clearIndexes();
});

$(document).ready(function () {

    var today = moment();
    var tomorrow = today.add(1, 'd');

    var deliveryPickerDate = $('#delivery_date').pickadate('picker');
    deliveryPickerDate.set('disable', [{
        from: [tomorrow.year(), tomorrow.month(), tomorrow.date()], to: +3000
    }]);

    getOrderList('sended', true, false, function () {
        inited1 = true;
    });
    getOrderList('rejected', false, false, function () {
        inited2 = true;
    });
    getOrderList('modified', false, false, function () {
        inited3 = true;
    });

    initAutoCompleteForNewLine();

    shortcutController.addCtrlShift(107, function () {
        addNewDeliveryLine();
    });
    updateDeliveryValorisation();

    $('#qty-product').keypress(function (event) {
        if (event.which == 13) {
            addNewDeliveryLine();
        }
    });

    $("form[name=delivery]").keypress(function (event) {
        if (event.which == 13) {
            event.stopPropagation();
            return false;
        }
    });

    init();
});

