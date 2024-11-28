/**
 * Created by hcherif on 10/03/2016.
 */

$(function () {
    list_restaurant = initSimpleDataTable('#restaurant_list_table', {
        searching: true,
        lengthMenu: false,
        "order": [[ 1, "asc" ]],
        "lengthChange": false,
        "aoColumnDefs": [
            { "bSortable": false, "aTargets": [ 7 ] }
        ]
    });


    $(document).on('click', '.detail-btn', function(e){
        var modalId = 'detail-restaurant-modal-' + $(this).attr('id');
        showDefaultModal(Translator.trans('restaurant.list.details'), $('#' + modalId).html());
    });

    $("#export-btn").on('click',function(){
        submitExportDocumentFile(".filter-zone",list_restaurant,Routing.generate("restaurant_list_export"));
    });

});
