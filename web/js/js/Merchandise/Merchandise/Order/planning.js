/**
 * Created by anouira on 25/02/2016.
 */

var planningTable = null;

function initPlanningConsulting(){


    var ordersMarkups = $('.orderDay');

    $.each(ordersMarkups,function(key,value){
        switch (parseInt($(value).attr('ord'))){
            case 0 : $(value).css('background-color','lightred');  break;
            case 1 : $(value).css('background-color','lightblue');  break;
            case 2 : $(value).css('background-color','#A4FDA4');  break;
            case 3 : $(value).css('background-color','lightgray');  break;
            case 4 : $(value).css('background-color','#FFB3FF;');  break;
            case 5 : $(value).css('background-color','lightyellow');  break;
            case 6 : $(value).css('background-color','lightorange');  break;
        }
    });

    var deliveryMarkups = $('.deliveryDay');

    $.each(deliveryMarkups,function(key,value){
        switch (parseInt($(value).attr('ord'))){
            case 0 : $(value).css('background-color','lightred');  break;
            case 1 : $(value).css('background-color','lightblue');  break;
            case 2 : $(value).css('background-color','#A4FDA4');  break;
            case 3 : $(value).css('background-color','lightgray');  break;
            case 4 : $(value).css('background-color','#FFB3FF;');  break;
            case 5 : $(value).css('background-color','lightyellow');  break;
            case 6 : $(value).css('background-color','lightorange');  break;
        }
    });

    planningTable = initSimpleDataTable('#planning-table', {
        lengthChange: false,
        columnDefs: [
            {
                targets: [1,2,3,4,5,6,7],
                orderable: false
            }
        ],
        initComplete: function(settings, json) {
            $('.orderDay, .deliveryDay ').tootltipBootstrap();
        }
    });

}
