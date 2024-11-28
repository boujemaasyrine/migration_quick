/**
 * Created by mchrif on 07/04/2016.
 */

var processCheckRestaurantCalculation = null;

/**
 * All of the summary calculation is being done in this file
 */
$(function () {

    $(document).on('keyup', '#check_restaurant_container .check_restaurant_input', function() {
        processCheckRestaurantCalculation();
    });

    processCheckRestaurantCalculation = function () {
        if(! cashBoxCountingIsValid ){
            return;
        }
       var total = 0;
       var checkRestaurantRows = $("#check_restaurant_container .checkRestaurantRow");
       checkRestaurantRows.each(function(index, elem) {
           var checkRestaurantRows = $(elem).find('.check_restaurant_row');
           var subtotalSpan = $(elem).find('.total_check_restaurant_by_type').first();
           var subtotal = 0;
           checkRestaurantRows.each(function(index, elem1) {
               var amount = $(elem1).find('.check_restaurant_input').first().val();
               var unitValue = $(elem1).find('.check_restaurant_unitvalue_input').first().val();
               subtotal+= parseFloat(amount) * parseFloat(unitValue);
           });
           subtotalSpan.text(floatToString(subtotal));
           total += subtotal;
       });
        $('.total_check_restaurant').text(floatToString(total));
        processSummaryCalculation();
    };

    $(document).on('click', '.addTicketRestaurantValue', function() {
        var nbr = $('.check_restaurant_input').length;
        var ticketName = $(this).attr('data-ticket-name');
        var realTicketName = ticketName.replace(/_/g, ' ');
        var prototype = $('#check_restaurant_container').data('prototype')
            .replace(/__name__/g, nbr);
        $('#lines' + ticketName).append($(prototype));
        $('.check_restaurant_unitvalue_input.toShow').attr('type', 'text');
        $('#cashbox_count_checkRestaurantContainer_ticketRestaurantCounts_'+ nbr + '_ticketName').val(realTicketName);
    });

});