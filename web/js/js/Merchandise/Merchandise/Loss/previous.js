/**
 * Created by hcherif on 15/02/2016.
 */

$(function() {
    initSimpleDataTable('#previous-loss-table', {
        searching: false,
        lengthMenu: false,
        initComplete: function () {
            $('#loss_list_table_length').closest('.row').remove();
        }
    });

    $(document).on('change', '#previous-loss-list-type', function (e) {
        $('#previous-loss-list-actif').css( "display", "inherit" );
    });
});
