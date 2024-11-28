/**
 * Created by mchrif on 05/05/2016.
 */

var calculateCashboxFundTotal = null;

$(function () {

    $(document).on('keyup', '#chest_count_cashboxFund_nbrOfCashboxes', function() {
        calculateCashboxFundTotal();
    });

    $(document).on('keyup', '#chest_count_cashboxFund_initialCashboxFunds', function() {
        calculateCashboxFundTotal();
    });

    calculateCashboxFundTotal = function() {
        if(! countingIsValid){
            return;
        }
        var total = parseFloat($("#chest_count_cashboxFund_nbrOfCashboxes").val()) * parseFloat($('#chest_count_cashboxFund_initialCashboxFunds').val());
        $('.total_cashbox_fund').text(floatToString(total));
        processSummaryCalculation();
    };

});