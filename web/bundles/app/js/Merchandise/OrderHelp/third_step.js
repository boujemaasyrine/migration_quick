/**
 * Created by anouira on 31/03/2016.
 */

var max = 20;
var tdHeight = 34;
var slid = ".flat-slider-vertical";

function days(index) {
    return $($('#budget-tables tbody tr')[index]).attr('short-day');
}

function getCurrentSupplierID(){
    var items = $('.carousel-inner').find('.item') ;
    for (var i=0 ; i< items.length ; i++){
        if ($(items[i]).hasClass('active')){
            return $(items[i]).attr('supplier-id');
        }
    }
    return null;
}

function colorDays(){
     var deliveryDay = $($('.vertical:visible')[0]).attr('delivery-day');
     var trs = $('#budget-tables tbody tr');
    $('#budget-tables').find('tr').removeClass('order-delivery-0 order-delivery-1 order-delivery-2 order-delivery-3 order-delivery-4 order-delivery-5 order-delivery-6');

    $.each(trs,function(key,value){
        if ($(value).hasClass('d-'+getCurrentSupplierID()+'-'+deliveryDay) || $(value).attr('w') == deliveryDay ){
            $(value).addClass('order-delivery-'+deliveryDay);
        }
    });
}

function getDay(index) {
    index = parseInt(Math.trunc(index));
    if (index < 0)index = 0;
    return days(index);
}

function getDeliveryDay(index) {
    if (((index * 10) % 10) == 0) {
        index--;
    }

    index = Math.trunc(index);
    if (index < 0)index = 0;
    return days(index);
}

function update(event, ui, value) {

    var budgets = $('.budget');
    var budget = 0;

    var orderDay = max - ui.values[1];
    if ((orderDay*10)%10>0){
        budget += 0.5 * parseFloat($(budgets[Math.trunc(orderDay)]).html());
        orderDay = Math.trunc(orderDay) + 1 ;
    }
    var deliveryDay = max - ui.values[0];

    var pos = max - ui.value;
    var i;
    for (i = orderDay; i < deliveryDay; i++) {
        budget = budget + parseFloat($(budgets[i]).html());
    }
    if (i - deliveryDay > 0) {
        budget = budget - (parseFloat($(budgets[i - 1]).html()) / 2 );
    }

    var catName = $(value).find(slid).attr('cat-name');
    $("#" + catName + "").find('.cat-budgets').val(Math.round(budget) + " €");
    $("#" + catName + "").find('.cat-budgets-hidden').val(floatToString(budget));
    $("#" + catName + "").find('.cat-range-hidden').val(pos - orderDay);


    $("#" + catName + "").find('.absolute_order_day').val(20 - ui.values[1]);
    var value = 20 - 0.1 - ui.values[0];
    value = value >= 0 ? value : 0;
    $("#" + catName + "").find('.absolute_delivery_day').val(value);
}

function moveTooltip(event, ui, vertical) {

    var range = Math.abs(ui.values[0] - ui.values[1]);

    var tooltip1 = $(vertical).find('.handle-tooltip-1');
    var height1 = $($(vertical).find('.ui-slider-handle')[0]).position().top;
    tooltip1.css('margin-top', height1);
    tooltip1.html(getDeliveryDay(20 - ui.values[0]) + " (" + (range) + " " + Translator.trans('days_label_shortcut') + ")");

    var tooltip2 = $(vertical).find('.handle-tooltip-2');
    var height2 = $($(vertical).find('.ui-slider-handle')[1]).position().top;
    tooltip2.css('margin-top', height2);
    tooltip2.html(getDay(20 -ui.values[1]));

}

function moveToRight(obj, complete) {

    if (typeof complete == 'undefined') {
        complete = function (x) {
            $(x).hide();
        }
    }

    $(obj).css('position', 'absolute');
    $(obj).animate({"margin-left": "+=100%"}, "slow", function () {
        $(obj).css('position', 'relative');
        $(obj).css('margin', '0%');
        complete(obj);
    });
}

function moveToLeft(obj, complete) {
    if (typeof complete == 'undefined') {
        complete = function (x) {
            $(x).hide();
        }
    }

    $(obj).css('position', 'absolute');
    $(obj).animate({"margin-left": "-=100%"}, "slow", function () {
        $(obj).css('position', 'relative');
        $(obj).css('margin', '0%');
        complete(obj);
    });
}

$(document).on('click', '.day-table-control.left-control', function () {
    var currentTable = $(this).siblings('table.active');
    if (currentTable.prev().hasClass('day-table')) {//These a table previous
        var target = currentTable.prev();
        moveToLeft(currentTable, function (x) {
            x.removeClass('active');
            target.addClass('active');
            colorDays();
        });
    } else {
        var siblingsTables = currentTable.siblings('.day-table');
        if (siblingsTables.length > 0) {
            var target = siblingsTables.last();
            moveToLeft(currentTable, function (x) {
                x.removeClass('active');
                target.addClass('active');
                colorDays();
            });
        }
    }
    if (typeof target != 'undefined') {
        var indicatorContainer = target.siblings('.day-table-indicator-container');
        indicatorContainer.find('.day-table-indicator').removeClass('active');
        indicatorContainer.find(".day-table-indicator[data-target=" + target.attr('day') + "]").addClass('active');
    }
});
$(document).on('click', '.day-table-control.right-control', function () {
    var currentTable = $(this).siblings('table.active');
    if (currentTable.next().hasClass('day-table')) {//These a table previous
        var target = currentTable.next();
        moveToRight(currentTable, function (x) {
            x.removeClass('active');
            target.addClass('active');
            colorDays();
        });
    } else {
        var siblingsTables = currentTable.siblings('.day-table');
        if (siblingsTables.length > 0) {
            var target = siblingsTables.first();
            moveToRight(currentTable, function (x) {
                x.removeClass('active');
                target.addClass('active');
                colorDays();
            });
        }
    }
    if (typeof target != 'undefined') {
        var indicatorContainer = target.siblings('.day-table-indicator-container');
        indicatorContainer.find('.day-table-indicator').removeClass('active');
        indicatorContainer.find(".day-table-indicator[data-target=" + target.attr('day') + "]").addClass('active');
    }
});

$(function () {

    $('#suppliers-caroussel').css('height', $('#budget-tables').outerHeight());

    var verticals = $('.vertical');

    $.each(verticals, function (key, value) {
        var absoluteOrderDay = parseFloat($(value).attr('absolute-order-day'));
        var range = parseFloat($(value).attr('range'));
        var deliveryDay= parseInt($(value).attr('delivery-day'))


        $(value).find(slid).sliderJquery({
            orientation: "vertical",
            range: true,
            values: [max - absoluteOrderDay - range , max - absoluteOrderDay ],
            min: 0,
            max: max,
            step: 0.5,
            slide: function (event, ui) {
                moveTooltip(event, ui, value);
                update(event, ui, value);
            },
            stop: function (event, ui) {
                moveTooltip(event, ui, value);
            },
            create: function (event, ui) {
                /* Color for each vertical */
                $(value).find('.ui-widget-header').addClass('order-delivery-'+ deliveryDay);
                $(value).find('.handle-tooltip-1, .handle-tooltip-2').addClass('order-delivery-' + deliveryDay);

                /* Tooltip for the second handler */
                var height1 = $($(value).find('.ui-slider-handle')[0]).position().top;
                if (height1 > 0) {
                    $(value).find('.handle-tooltip-1').css('margin-top', height1);
                } else {
                    $(value).find('.handle-tooltip-1').css('margin-top', ((range + absoluteOrderDay ) * tdHeight));
                }

                $(value).find('.handle-tooltip-1').html(getDeliveryDay(absoluteOrderDay + range) + "(" + (range) + Translator.trans('days_label_shortcut') + ")");
                $(value).find('.handle-tooltip-1').show();


                /* Tooltip for the first handler */
                var height2 = $($(value).find('.ui-slider-handle')[1]).position().top;
                if (height2 > 0) {
                    $(value).find('.handle-tooltip-2').css('margin-top', height2);
                } else {
                    $(value).find('.handle-tooltip-2').css('margin-top', ((absoluteOrderDay) * tdHeight));
                }

                $(value).find('.handle-tooltip-2').html(getDay(absoluteOrderDay));
                $(value).find('.handle-tooltip-2').show();

            }
        });

    });

    //colorOrderDays();
    colorDays();

    $('#suppliers-caroussel').on('slid.bs.carousel', function () {
        colorDays();
    });
});