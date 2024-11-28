/**
 * Created by hcherif on 31/05/2016.
 */

$(function() {

    // Get existing rights for each role
    loader.block();
    ajaxCall({
        method: 'GET',
        url: Routing.generate('get_existing_rights', {})
    }, function (res) {
        if (res.errors === undefined) {
            var container = $('#Role');
            var divPrototype = $('#lines');
            var prototype = divPrototype.attr('data-prototype');
            $.each(res.rights, function(key, value){
                var newLine = prototype.replace(/__name__label__/g, key)
                    .replace(/__name__/g, key);
                var inputToFillByRole = 'id="rights_for_roles_roles_'+ key +'_role"';
                newLine = newLine.replace(inputToFillByRole, inputToFillByRole + ' value="'+ key +'"');
                $.each(value, function (key2, value2){
                    var optionToCheck = 'option value="'+ value2['idRight'] +'"';
                    newLine = newLine.replace(optionToCheck, optionToCheck + 'selected');
                });
                container.append(newLine);
                orderMultoSelect($('#rights_for_roles_roles_'+ key +'_right'));
                initMultiSelect('#rights_for_roles_roles_'+ key +'_right');
            });
            loader.unblock();
        }
    }, null, function () {

    });

    $(document).on('change', '#rights_for_roles_rolesLabel', function(){
        var role = $(this).val();
        $('#Role').children('div').each( function(i) {
            $(this).attr('style', 'display:none;');
            $('#' + role).attr('style', 'display: inherit');
        });
    });

});
