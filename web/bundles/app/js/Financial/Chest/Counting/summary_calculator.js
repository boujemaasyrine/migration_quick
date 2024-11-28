/**
 * Created by mchrif on 04/04/2016.
 */

var processSummaryCalculation = null;

/**
 * All of the summary calculation is being done in this file
 */
$(function () {
    processSummaryCalculation = function () {
        if(! countingIsValid){
            return;
        }
        processSmallChestCalculation();
        var totalChest = 0;
        $('.total').each(function (index, elem) {
            totalChest += parseFloat($(elem).text());
        });
        if(totalChest === 0) {
            $('.total_chest').text('');
        } else {
            $('.total_chest').text(floatToString(totalChest));
        }

        var thTotalChest = 0;
        $('.th_total').each(function (index, elem) {
            thTotalChest += parseFloat($(elem).text());
        });
        if(thTotalChest === 0) {
            $('.th_total_chest').text('');
        } else {
            $('.th_total_chest').text(floatToString(thTotalChest));
        }

        $('.gap').each(function(index, elem) {
            var row = $(elem).closest('tr');
            var real = parseFloat($($(row).find('.total').first()).text());
            var th = parseFloat($($(row).find('.th_total').first()).text());
            var gap = real - th;
            if(gap === 0) {
                $(elem).text('');
            } else {
                $(elem).text(floatToString(gap));
            }
            if(gap > 0) {
                $(elem).removeClass('red-text');
                $(elem).addClass('green-text');
            } else if(gap < 0) {
                $(elem).addClass('red-text');
                $(elem).removeClass('green-text');
            } else {
                $(elem).removeClass('red-text');
                $(elem).removeClass('green-text');
            }
        });

        // gap calculation
        var real = parseFloat($('.total_chest').first().text());
        var th = parseFloat($('.th_total_chest').first().text());
        var gap = real - th;
        if(gap === 0) {
            $('.chest_total_gap').text(floatToString(gap));
        } else {
            $('.chest_total_gap').text(floatToString(gap));
        }
        if(gap > 0) {
            $('.chest_total_gap').removeClass('red-text');
            $('.chest_total_gap').addClass('green-text');
        } else if(gap < 0) {
            $('.chest_total_gap').addClass('red-text');
            $('.chest_total_gap').removeClass('green-text');
        } else {
            $('.chest_total_gap').removeClass('red-text');
            $('.chest_total_gap').removeClass('green-text');
        }
    };
});