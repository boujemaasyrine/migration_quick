/**
 * Created by hcherif on 29/04/2016.
 */

$( document ).ready(function() {
    loader.show();
    ajaxCall({
        url: Routing.generate('default_password'),
        'type': 'json'
    }, function (data) {
        showDefaultModal(data.header, data.formBody, data.footer);
        $('#btn-cancel').css('display', 'none');
        loader.hide();
    });


    $(document).on('click', '#btn-validate-password', function (e) {

        var staffId = $(this).parent().attr('data-user');
        var defaultPasswordForm = $('#defaultPasswordForm');
        loader.show();
        e.preventDefault();
        ajaxCall(
            {
                url: Routing.generate('default_password', {staff: staffId, firstConnexion: 'true'}),
                method: POST,
                data: defaultPasswordForm.serialize()
            },
            function (res) {

                if (res.errors === undefined && res.formError === undefined) {

                    if(res.isFromSupervision){
                        window.location.href = Routing.generate('restaurant_list_super');
                    }
                    else{
                        window.location.href = Routing.generate('index');
                    }

                } else if (res.errors === undefined) {
                    defaultPasswordForm.html(res.formError['0']);
                }
                loader.hide();
            });
    });

});

