/**
 * Created by mchrif on 07/04/2016.
 */

var processElectronicCheckRestaurantCalculation = null;

/**
 * All of the summary calculation is being done in this file
 */
$(function () {

    $(document).on('keyup', '#check_restaurant_electronic_container .check_restaurant_input', function() {
        processElectronicCheckRestaurantCalculation();
    });

    processElectronicCheckRestaurantCalculation = function () {
        if(! cashBoxCountingIsValid ){
            return;
        }
       var total = 0;
       var checkRestaurantRows = $("#check_restaurant_electronic_container .checkRestaurantRow");
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
        $('.total_check_restaurant_electronic').text(floatToString(total));
        processSummaryCalculation();
    };

});