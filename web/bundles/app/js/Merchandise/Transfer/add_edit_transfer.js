/**
 * Created by anouira on 02/03/2016.
 */

var products = [];

function getSelectedProductsId(){
    var selectedProducts = [];

    $.each($('.cmd-line .product input'),function(key,value){
        selectedProducts.push(parseInt($(value).val()));
    });

    return selectedProducts;
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
            $('#name-product').val(ui.item.product.name);
            $('#new-line-unit-price').html(floatToString(ui.item.product.unit_price));
            $('#new-line-unit-label').html(Translator.trans(ui.item.product.unitInv));
            $('#new-line-unit-use-label').html(Translator.trans(ui.item.product.unitUse));
            $('#new-line-unit-exp-label').html(Translator.trans(ui.item.product.unitExp));
            $('#stock-qty').val(ui.item.product.stock);
            $('#qty-cmd').focus();

        },
        autoFocus : true
    });

    $('#name-product').autocomplete({
        source: function (request, response) {
            var result = [];
            $.each(products, function (key, value) {
                if ($.inArray(value.id,getSelectedProductsId()) <0 && value.name.toUpperCase().indexOf(request.term.toUpperCase()) >= 0 ) {
                    result.push({value: value.name, label: value.name + " (" + value.code + ")", product: value});
                }
            });
            sortTabByProp(result,'value',request.term);
            response(result);
        },
        select: function (event, ui) {
            $('#code-product').val(ui.item.product.code);
            $('#new-line-unit-price').html(floatToString(ui.item.product.unit_price));
            $('#stock-qty').val(ui.item.product.stock);
            $('#new-line-unit-label').html(Translator.trans(ui.item.product.unitInv));
            $('#new-line-unit-use-label').html(Translator.trans(ui.item.product.unitUse));
            $('#new-line-unit-exp-label').html(Translator.trans(ui.item.product.unitExp));
            $('#qty-cmd').focus();
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

function verifyInput(dom){

    dom.removeClass('shadow-danger');
    if (dom.val().trim() != '' &&
        (isNaN(dom.val().trim()) ||
        Number.isInteger(parseFloat(dom.val().trim())) == false ||
        parseInt(dom.val().trim()) < 0)
    ) {
        dom.addClass('shadow-danger');
        dom.focus();
        $('.btn-validate').attr('disabled',true);
        return false;
    }else if(dom.val().trim() == '') {
        dom.val(0);
    }
    $('.btn-validate').attr('disabled',false);
    return true;
}

function addNewLine(isSubbmitted) {

    var codeProductDom = $('#code-product');
    var qtyCmdDom = $('#qty-cmd');
    var qtyUseCmdDom = $('#qty-cmd-use');
    var qtyExpCmdDom = $('#qty-cmd-exp');
    var newUnitPriceDom = $('#new-line-unit-price');
    var newStockQtyDom = $('#stock-qty');
    var newArticleDom = $('#name-product');

    codeProductDom.removeClass('shadow-danger');
    qtyCmdDom.removeClass('shadow-danger');

    if (typeof isSubbmitted != 'undefined' && codeProductDom.val().trim() == ''){
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

    var product = getProductBy('code', codeProductDom.val().trim());
    if (product == null) {
        codeProductDom.addClass('shadow-danger');
        return false;
    }

    if ($.inArray(product.id,getSelectedProductsId())>=0){
        codeProductDom.addClass('shadow-danger');
        return false;
    }

    var productTable = $('#products-table tbody');
    var prototype = productTable.attr('data-prototype');

    var count = parseInt(productTable.attr('count'));
    productTable.attr('count', count + 1);

    var useVal = (parseInt(qtyUseCmdDom.val())  / ( product.use_ratio * product.inv_ratio ))  * product.unit_price;
    var invVal = (parseInt(qtyCmdDom.val())  / (  product.inv_ratio ))  * product.unit_price;
    var expVal = parseInt(qtyExpCmdDom.val() ) * product.unit_price;
    var val = useVal + invVal + expVal ;
    val = Math.round(val *100)/100 ;

    var total = parseInt(qtyUseCmdDom.val())  / product.use_ratio ;
    total += parseInt(qtyCmdDom.val());
    total += parseInt(qtyExpCmdDom.val()) *  product.inv_ratio;
    total = Math.round(100* total)/100;

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

    $("#new-line").before(newLine);

    $("#transfer_in_lines_" + count + "_product").val(product.id);
    $("#transfer_in_lines_" + count + "_qty").val(qtyCmdDom.val());
    $("#transfer_in_lines_" + count + "_qtyUse").val(qtyUseCmdDom.val());
    $("#transfer_in_lines_" + count + "_qtyExp").val(qtyExpCmdDom.val());

    $("#transfer_out_lines_" + count + "_product").val(product.id);
    $("#transfer_out_lines_" + count + "_qty").val(qtyCmdDom.val());
    $("#transfer_out_lines_" + count + "_qtyUse").val(qtyUseCmdDom.val());
    $("#transfer_out_lines_" + count + "_qtyExp").val(qtyExpCmdDom.val());

    codeProductDom.val('');
    qtyCmdDom.val('');
    qtyUseCmdDom.val('');
    qtyExpCmdDom.val('');
    newUnitPriceDom.html('');
    newStockQtyDom.val('');
    newArticleDom.val('');

    $('#new-line-unit-label, #new-line-unit-use-label, #new-line-unit-exp-label, #new-total-line ').html('');

    codeProductDom.focus();
    updateTransfertValorization();
}

function updateTransfertValorization() {
    var valorizations = $('.line-valorization');
    var val = 0;
    $.each(valorizations, function (key, value) {
        val += parseFloat($(value).html())
    });

    val = Math.round(100 * val) / 100;
    $('#transfer_in_valorization').val(floatToString(val));
    $('#transfer_out_valorization').val(floatToString(val));
    $('#span-transfer-val').html(floatToString(val));

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
    total = total +  parseInt(qtyCmdDom.val());
    total = total + parseInt(qtyExpCmdDom.val()) *  product.inv_ratio;
    total = Math.round(100* total)/100;

    $('#new-total-line').html(floatToString(total)+" "+Translator.trans(product.unitInv));
}

$(document).on('change', '.qty-line, .qty-line-use, .qty-line-exp', function () {
    var tr = $(this).parentsUntil('tbody', 'tr');
    updateLineVal(tr);
    updateTransfertValorization();
});

$(document).on('click', '.remove-line', function () {
    $(this).parentsUntil('tbody', 'tr').remove();
    updateTransfertValorization();
});

$(document).on('submit','form',function(){

    var r = addNewLine(true);
    //if (r){
        loader.show();
        return true;
    //}
});

$(document).on('change', '#qty-cmd, #qty-cmd-use, #qty-cmd-exp', function () {
    updadeNewTotal();
});

$(function () {
    loader.show();
    ajaxCall({
        url: Routing.generate('get_product_by_supplier', {filterSecondary: 'true'}),
        dataType: 'json'
    }, function (data) {
        products = data.data;
        loader.hide();
    });

    initAutoCompleteForNewLine();

    $('#new-line-btn').on('click', function () {
        addNewLine();
    });

    shortcutController.addCtrlShift(107, function () {
        addNewLine();
    });

    $('#transfer_in_restaurant').selectize();
    $('#transfer_out_restaurant').selectize();

    $('#qty-cmd, #qty-cmd-use, #qty-cmd-exp').keypress(function(event){
        if ( event.which == 13 ) {
            addNewLine();
        }
    });

    $("form[name=transfer_in], form[name=transfer_out]").keypress(function(event){
        if ( event.which == 13 ) {
            event.stopPropagation();
            return false;
        }
    });

    var today = moment();
    if ($('#transfer_in_dateTransfer').length>0){
        var transferInPicker = $('#transfer_in_dateTransfer').pickadate('picker');
        transferInPicker.set('max',[today.year(),today.month(),today.date()]);
    }

    if ($('#transfer_out_dateTransfer').length>0){
        var transferOutPicker = $('#transfer_out_dateTransfer').pickadate('picker');
        transferOutPicker.set('max',[today.year(),today.month(),today.date()])
    }


});//End document ready
