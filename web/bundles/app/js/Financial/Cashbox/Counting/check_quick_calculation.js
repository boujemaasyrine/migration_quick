/**
 * Created by mchrif on 08/04/2016.
 */

var calculateCheckQuickTotals = null;

$(function () {
    $(document).on('keyup', '#check_quick_container .check_quick_input,.check_quick_unit_value_input', function() {
        calculateCheckQuickTotals();
    });

    calculateCheckQuickTotals = function () {
        if(! cashBoxCountingIsValid ){
            return;
        }
        var total = 0;
        var checkQuickRows = $("#check_quick_container .checkQuickRow");
        checkQuickRows.each(function(index, elem) {
            var checkQuickRows = $(elem).find('.check_quick_row');
            var subtotalSpan = $(elem).find('.total_check_quick_by_type').first();
            var subtotal = 0;
            checkQuickRows.each(function(index, elem1) {
                var amount = $(elem1).find('.check_quick_input').first().val();
                var unitValue = $(elem1).find('.check_quick_unit_value_input').first().val();
                subtotal+= parseFloat(amount) * parseFloat(unitValue);
            });
            subtotalSpan.text(floatToString(subtotal));

            total += subtotal;

            console.log(total);
        });

        $('.total_check_quick').text(floatToString(total));
        processSummaryCalculation();
    };

    $(document).on('click', '.addCheckQuickValue', function() {
        var nbr = $('.check_quick_input').length;
        var checkName = $(this).attr('data-check-name');
        var realcheckName = checkName.replace(/_/g, ' ');
        var prototype = $('#check_quick_container').data('prototype')
            .replace(/__name__/g, nbr);
        $('#lines' + checkName).append($(prototype));
        $('.check_quick_unit_value_input.toShow').attr('type', 'text');
        $('#cashbox_count_checkQuickContainer_checkQuickCounts_'+ nbr + '_checkName').val(realcheckName);
    });


});