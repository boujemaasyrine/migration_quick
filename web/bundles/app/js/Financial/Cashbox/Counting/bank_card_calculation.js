/**
 * Created by mchrif on 08/04/2016.
 */

var calculateBankCardTotals = null;

$(function () {
    $(document).on('keyup', '.bank_card_amount', function() {
        calculateBankCardTotals();
    });

    calculateBankCardTotals = function () {
        if(! cashBoxCountingIsValid ){
            return;
        }
        var total = 0;
        var rows = $('#bank_card_container').find('.bankCardRow');
        rows.each(function(index, elem) {
            var amount = $(elem).find('.bank_card_amount').first().val();
            total +=  parseFloat(amount);
        });

        $('.bank_card_total').text(floatToString(total));
        processSummaryCalculation();
    };

    var keyUpNamespace = 'bank_card_amount';
    $(document).on('focus', '.bank_card_amount', function () {
        var self = $(this);
        var e = shortcutController.add(KEY_ENTER, null, null, function () {
            var nextCell = $($($(self).closest('.row').next('.row')[0]).find('.bank_card_amount')[0]);
            nextCell.focus();
            nextCell.select();
        }, keyUpNamespace);
        $(self).focusout(function () {
            $(document).unbind('keyup' + '.' + keyUpNamespace);
        });
    });
});