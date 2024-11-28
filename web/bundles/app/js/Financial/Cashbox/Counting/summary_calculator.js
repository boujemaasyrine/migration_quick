/**
 * Created by mchrif on 04/04/2016.
 */

var processSummaryCalculation = null;

/**
 * All of the summary calculation is being done in this file
 */
$(function () {
    processSummaryCalculation = function () {
        if(! cashBoxCountingIsValid ){
            return;
        }
        var totalCashbox = 0;
        $('.total').each(function (index, elem) {
            totalCashbox += parseFloat($(elem).text());
        });
        if(totalCashbox === 0) {
            $('.total_cashbox').text('');
        } else {
            $('.total_cashbox').text(floatToString(totalCashbox));
        }

        var thTotalCashbox = 0;
        $('.th_total').each(function (index, elem) {
            thTotalCashbox += parseFloat($(elem).text());
        });
        if(thTotalCashbox === 0) {
            $('.th_total_cashbox').text(floatToString(0));
        } else {
            $('.th_total_cashbox').text(floatToString(thTotalCashbox));
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
                $(elem).removeClass('green-text');
                $(elem).removeClass('red-text');
            }
        });

        // gap calculation
        var real = parseFloat($('.total_cashbox').first().text());
        var th = parseFloat($('.th_total_cashbox').first().text());
        var gap = real - th;
        if(gap === 0) {
            $('.total_gap').text(floatToString(gap));
        } else {
            $('.total_gap').text(floatToString(gap));
        }
        if(gap > 0) {
            $('.total_gap').removeClass('red-text');
            $('.total_gap').addClass('green-text');
        } else if(gap < 0) {
            $('.total_gap').addClass('red-text');
            $('.total_gap').removeClass('green-text');
        } else {
            $('.total_gap').removeClass('red-text');
            $('.total_gap').removeClass('green-text');
        }
    };
});