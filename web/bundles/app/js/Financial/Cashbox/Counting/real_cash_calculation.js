/**
 * Created by mchrif on 07/04/2016.
 */

var calculateRealCashTotals = null;
var syncRealCashBlock = null;

$(function () {
    $(document).on('keyup', '.real_cash_input', function() {
        calculateRealCashTotals();
    });

    calculateRealCashTotals = function () {
        if(! cashBoxCountingIsValid ){
            return;
        }
        var total = 0;
        var inputs = [];
        if ($('#cashbox_count_cashContainer_allAmount').prop('checked')) {
            // All amount
            total = $('#allAmountRealCash').find('.total_amount_input').first().val();
        } else {
            // Details block
            inputs = $('#detailsRealCash').find('.real_cash_input');
            inputs.each(function(index, elem) {
                var amount = parseFloat($(elem).val());
                var unitVal = $(elem).attr('data-unit-value') !== undefined ? parseFloat($(elem).attr('data-unit-value')) : 1;
                total +=  amount * unitVal;
            });
        }

        $('.total_real_cash').text(floatToString(total));
        processSummaryCalculation();
    };

    $(document).on('change', '#cashbox_count_cashContainer_allAmount', function () {
        syncRealCashBlock();
    });

    var keyUpNamespace = 'real_cash_input';
    $(document).on('focus', '.real_cash_input', function () {
        var self = $(this);
        var e = shortcutController.add(KEY_ENTER, null, null, function () {
            var nextCell = $($($(self).closest('.row').next('.row')[0]).find('.real_cash_input')[0]);
            nextCell.focus();
            nextCell.select();
        }, keyUpNamespace);
        $(self).focusout(function () {
            $(document).unbind('keyup' + '.' + keyUpNamespace);
        });
    });

    syncRealCashBlock = function () {
        if ($('#cashbox_count_cashContainer_allAmount').prop('checked')) {
            $('#detailsRealCash').fadeOut(function () {
                $('#allAmountRealCash').fadeIn();
                $('#detailsRealCash input').val('');
            });
        } else {
            $('#allAmountRealCash').fadeOut(function () {
                $('#detailsRealCash').fadeIn();
                $('#allAmountRealCash input').val('');
            });
        }
        calculateRealCashTotals();
    };
    syncRealCashBlock();
});