/**
 * Created by hcherif on 12/02/2016.
 */

$(function () {
    initSimpleDataTable('#article_list_table', {
        searching: false,
        pageLength: 100,
        lengthMenu: false,
        initComplete: function () {
            $('#pending_commands_table_length').closest('.row').remove();
        }
    });
});
