/**
 * Created by mchrif on 06/05/2016.
 */

var processSmallChestCalculation = null;

$(function () {

    $(document).on('keyup', '#chest_count_smallChest_totalCash', function () {
        if(! countingIsValid){
            return;
        }
        var totalCash = parseFloat($('#chest_count_smallChest_totalCash').val());
        $('.total_cash').text(floatToString(totalCash));
        processSummaryCalculation();
    });

    processSmallChestCalculation = function () {
        if(! countingIsValid){
            return;
        }
        var totalSmallChest = 0;
        $('.small_chest_sub_total').each(function (index, elem) {
            totalSmallChest += parseFloat($(elem).text());
        });
        if (totalSmallChest === 0) {
            $('.total_small_chest').text('');
        } else {
            $('.total_small_chest').text(floatToString(totalSmallChest));
        }

        var thTotalSmallChest = 0;
        $('.small_chest_sub_th_total').each(function (index, elem) {
            thTotalSmallChest += parseFloat($(elem).text());
        });
        if (thTotalSmallChest === 0) {
            $('.th_total_small_chest').text('');
        } else {
            $('.th_total_small_chest').text(floatToString(thTotalSmallChest));
        }

        var gap = totalSmallChest - thTotalSmallChest;
        if (gap === 0) {
            $('.total_small_chest_gap').text('');
        } else {
            $('.total_small_chest_gap').text(floatToString(gap));
        }
        if (gap > 0) {
            $('.total_small_chest_gap').removeClass('red-text');
            $('.total_small_chest_gap').addClass('green-text');
        } else if (gap < 0) {
            $('.total_small_chest_gap').addClass('red-text');
            $('.total_small_chest_gap').removeClass('green-text');
        } else {
            $('.total_small_chest_gap').removeClass('red-text');
            $('.total_small_chest_gap').removeClass('green-text');
        }

        // Gap calculation
        $('.small_chest_gap').each(function (index, elem) {
            var row = $(elem).closest('tr');
            var real = parseFloat($($(row).find('.small_chest_sub_total').first()).text());
            var th = parseFloat($($(row).find('.small_chest_sub_th_total').first()).text());
            var gap = real - th;
            if (gap === 0) {
                $(elem).text('');
            } else {
                $(elem).text(floatToString(gap));
            }
            if (gap > 0) {
                $(elem).removeClass('red-text');
                $(elem).addClass('green-text');
            } else if (gap < 0) {
                $(elem).addClass('red-text');
                $(elem).removeClass('green-text');
            } else {
                $(elem).removeClass('red-text');
                $(elem).removeClass('green-text');
            }
        });

    };

});