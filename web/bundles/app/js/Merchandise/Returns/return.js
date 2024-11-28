/**
 * Created by anouira on 01/03/2016.
 */

var products = [];
var supplierId = null;

function getSelectedProductsId(){
    var selectedProducts = [];

    $.each($('.cmd-line .product input'),function(key,value){
        selectedProducts.push(parseInt($(value).val()));
    });

    return selectedProducts;
}

function refreshProducts() {
    ajaxCall({
            url: Routing.generate('get_product_by_supplier', {supplier: supplierId, filterSecondary: 'true'}),
            dataType: 'json'
        },
        function (data) {
            if (typeof data.data != 'undefined' && data.data.length > 0) {
                products = data.data;
            }
        });

}

function initAutoCompleteForNewLine() {
    $('#code-product').autocomplete({
        source: function (request, response) {
            var result = [];
            $.each(products, function (key, value) {
                if ($.inArray(value.id,getSelectedProductsId()) <0 && value.code.toString().toUpperCase().indexOf(request.term.toUpperCase()) >= 0) {
                    result.push({value: value.code, label: value.code + " (" + value.name + ")", product: value});
                }
            });
            sortTabByProp(result,'value',request.term);
            response(result);
        },
        select: function (event, ui) {

            var unitPrice = floatToString(parseFloat(ui.item.product.unit_price));
            $('#name-product').val(ui.item.product.name);
            $('#new-line-unit-label').html(Translator.trans(ui.item.product.unitInv));
            $('#new-line-unit-use-label').html(Translator.trans(ui.item.product.unitUse));
            $('#new-line-unit-exp-label').html(Translator.trans(ui.item.product.unitExp));
            $('#new-line-unit-price').html(unitPrice);
            $('#qty-cmd-exp').focus();

        },
        autoFocus : true
    });

    $('#name-product').autocomplete({
        source: function (request, response) {
            var result = [];
            $.each(products, function (key, value) {
                if ($.inArray(value.id,getSelectedProductsId()) <0 &&  value.name.toUpperCase().indexOf(request.term.toUpperCase()) >= 0) {
                    result.push({value: value.name, label: value.name + " (" + value.code + ")", product: value});
                }
            });
            sortTabByProp(result,'value',request.term);
            response(result);
        },
        select: function (event, ui) {
            var unitPrice = floatToString(parseFloat(ui.item.product.unit_price));
            $('#code-product').val(ui.item.product.code);
            $('#new-line-unit-price').html(unitPrice);
            $('#new-line-unit-label').html(Translator.trans(ui.item.product.unitInv));
            $('#new-line-unit-use-label').html(Translator.trans(ui.item.product.unitUse));
            $('#new-line-unit-exp-label').html(Translator.trans(ui.item.product.unitExp));
            $('#qty-cmd-exp').focus();
        },
        autoFocus : true
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

function addReturnLine(isSubmitted) {
    var codeProductDom = $('#code-product');
    var qtyCmdDom = $('#qty-cmd');
    var qtyUseCmdDom = $('#qty-cmd-use');
    var qtyExpCmdDom = $('#qty-cmd-exp');
    var newUnitPriceDom = $('#new-line-unit-price');
    var newStockQtyDom = $('#stock-qty');
    var newArticleDom = $('#name-product');

    codeProductDom.removeClass('shadow-danger');
    qtyCmdDom.removeClass('shadow-danger');

    if (typeof isSubmitted != 'undefined' && codeProductDom.val().trim() == ''){
        return true;
    }

    if (codeProductDom.val().trim() == '') {
        codeProductDom.addClass('shadow-danger');
        codeProductDom.focus();
        return false;
    }

    verifyInput(qtyCmdDom);
    verifyInput(qtyUseCmdDom);
    verifyInput(qtyExpCmdDom);

    var product = getProductBy('code', $('#code-product').val().trim());

    if (product == null) {
        codeProductDom.addClass('shadow-danger');
        return false;
    }

    if ($.inArray(product.id,getSelectedProductsId())>=0){
        codeProductDom.addClass('shadow-danger');
        return false;
    }

    var count = parseInt($('#regularization-lines tbody').attr('count'));
    $('#regularization-lines tbody').attr('count', count + 1);

    var useVal = (parseInt(qtyUseCmdDom.val())  / ( product.use_ratio * product.inv_ratio ))  * product.unit_price;
    var invVal = (parseInt(qtyCmdDom.val())  / (  product.inv_ratio ))  * product.unit_price;
    var expVal = parseInt(qtyExpCmdDom.val() ) * product.unit_price;
    var val = useVal + invVal + expVal ;
    val = Math.round(val *100)/100 ;

    var total = parseInt(qtyUseCmdDom.val())  / product.use_ratio ;
    total = total + parseInt(qtyCmdDom.val());
    total = total + parseInt(qtyExpCmdDom.val()) *  product.inv_ratio;
    total = Math.round(100* total)/100;

    var prototype = $('#regularization-lines tbody').attr('data-prototype');
    var newLine = prototype
        .replace(/__name__/g, count)
        .replace(/__ref_product__/g, product.code)
        .replace(/__name_product__/g, product.name)
        .replace(/__unit__/g, Translator.trans(product.unitInv))
        .replace(/__unit_use__/g, Translator.trans(product.unitUse))
        .replace(/__unit_exp__/g, Translator.trans(product.unitExp))
        .replace(/__unit_price__/g, floatToString(product.unit_price))
        .replace(/__total_qty__/g, floatToString(total))
        .replace(/__val__/g, floatToString(val));

    $('#new-line').before(newLine);

    $("#return_lines_" + count + "_product").val(product.id);
    $("#return_lines_" + count + "_qty").val(qtyCmdDom.val());
    $("#return_lines_" + count + "_qtyUse").val(qtyUseCmdDom.val());
    $("#return_lines_" + count + "_qtyExp").val(qtyExpCmdDom.val());


    codeProductDom.val('');
    qtyCmdDom.val('');
    qtyUseCmdDom.val('');
    qtyExpCmdDom.val('');
    newUnitPriceDom.html('');
    newStockQtyDom.val('');
    newArticleDom.val('');

    $('#new-line-unit-label, #new-line-unit-use-label, #new-line-unit-exp-label, #new-total-line ').html('');

    codeProductDom.focus();

    updateReturnVal();
}

function updateReturnVal() {
    var val = 0;
    var vals = $('.line-valorization');

    $.each(vals, function (key, value) {
        val += parseFloat($(value).html());
    });

    $('#regularization-val').html(floatToString(Math.round(100 * val) / 100));

}

function verifyInput(dom){
    dom.removeClass('shadow-danger');
    if (dom.val().trim() != '' &&
        (isNaN(dom.val().trim()) ||
        Number.isInteger(parseFloat(dom.val().trim())) == false ||
        parseInt(dom.val().trim()) < 0)
    ) {
        dom.addClass('shadow-danger');
        dom.focus();
        return false;
    }else if(dom.val().trim() == '') {
        dom.val(0);
    }
    return true;
}

function updadeNewTotal(){

    var qtyCmdDom = $('#qty-cmd');
    var qtyUseCmdDom = $('#qty-cmd-use');
    var qtyExpCmdDom = $('#qty-cmd-exp');
    var codeProductDom = $('#code-product');
    var product = getProductBy('code', codeProductDom.val().trim());

    verifyInput(qtyCmdDom);
    verifyInput(qtyUseCmdDom);
    verifyInput(qtyExpCmdDom);

    var total = parseInt(qtyUseCmdDom.val())  / product.use_ratio ;
    total += parseInt(qtyCmdDom.val());
    total += parseInt(qtyExpCmdDom.val()) *  product.inv_ratio;
    total = Math.round(100* total)/100;

    $('#new-total-line').html(floatToString(total)+" "+Translator.trans(product.unitInv));
}

function updateLineVal(tr){
    var qtyCmdDom = $(tr).find('.qty-line');
    var qtyUseCmdDom = $(tr).find('.qty-line-use');
    var qtyExpCmdDom = $(tr).find('.qty-line-exp');

    if (! (verifyInput(qtyCmdDom) && verifyInput(qtyUseCmdDom) && verifyInput(qtyExpCmdDom))){
        return false
    }

    var idProduct = $(tr).find('.product input').val();
    var product = getProductBy('id',parseInt(idProduct));
    var valLine = $(tr).find('.line-valorization');
    var totalLine =  $(tr).find('.total');


    var total = parseInt(qtyUseCmdDom.val())  / product.use_ratio ;
    total += parseInt(qtyCmdDom.val());
    total += parseInt(qtyExpCmdDom.val()) *  product.inv_ratio;
    total = Math.round(100* total)/100;

    var useVal = (parseInt(qtyUseCmdDom.val())  / ( product.use_ratio * product.inv_ratio ))  * product.unit_price;
    var invVal = (parseInt(qtyCmdDom.val())  / (  product.inv_ratio ))  * product.unit_price;
    var expVal = parseInt(qtyExpCmdDom.val() ) * product.unit_price;
    var val = useVal + invVal + expVal ;
    val = Math.round(val *100)/100 ;
    valLine.html(floatToString(val));
    totalLine.html(floatToString(total));
}

$(function () {

    supplierId = $("#return_supplier").val();

    if (supplierId != null) {
        refreshProducts();
    }

    initAutoCompleteForNewLine();

    updateReturnVal();

    $('#qty-cmd, #qty-cmd-use, #qty-cmd-exp').keypress(function(event){
        if ( event.which == 13 ) {
            addReturnLine();
        }
    });

    $("form[name=return]").keypress(function(event){
        if ( event.which == 13 ) {
            event.stopPropagation();
            return false;
        }
    });

    $("#return_supplier").selectize();

});

$(document).on('change', '.qty-line, .qty-line-use, .qty-line-exp', function () {
    var tr = $(this).parentsUntil('tbody', 'tr');
    updateLineVal(tr);
    updateReturnVal();
});

$(document).on('change', '#return_supplier', function () {
    supplierId = $(this).val();
    refreshProducts();
});

$(document).on('click', '#new-line-btn', function () {
    addReturnLine();
});

$(document).on('click','.remove-line',function(){

    $(this).parentsUntil('tbody','tr').remove();

});

$(document).on('change', '#qty-cmd, #qty-cmd-use, #qty-cmd-exp', function () {
    updadeNewTotal();
});

$(document).on('submit','form',function(){
    var r= addReturnLine(true);
    if (r){
        loader.show();
        return true;
    }


});