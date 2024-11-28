/**
 * Created by mchrif on 12/02/2016.
 */

$(function () {
    initSimpleDataTable('#menus-table', {});

    $(document).on('click', '.details-menu', function (e) {
        loader.block();
        ajaxCall({
            url: Routing.generate('menu_details')
        }, function (res) {
            if (res.errors.length == 0) {
                loader.unblock();
                showEmtptyModal(res.data[0]);
            }
        });
    });

    $(document).on('click', '.add_new_menu', function (e) {
        loader.block();
        ajaxCall({
            url: Routing.generate('create_menu')
        }, function (res) {
            if (res.errors.length == 0) {
                loader.unblock();
                showEmtptyModal(res.data[0]);
            }
        });
    });
});
