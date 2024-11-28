/**
 * Created by hcherif on 19/04/2016.
 */

$(function () {

    var AttributeRoleForm = $("#AttributeRoleForm");

    function initRolesTable() {
         list_roles = initSimpleDataTable('#staff_roles_list_table', {
            searching: false,
            lengthMenu: false,
            "bPaginate": false,
            columnDefs: [
                {width: '20%', "aTargets": [0]},
                {width: '15%', "aTargets": [2]},
                {targets: [2], orderable: false}
            ]
        });
    }

    staff = initSimpleDataTable('#staff_table', {
        rowId: 'id',
        processing: true,
        serverSide: true,
        searching: true,
        "order": [[2, "desc"]],
        columnDefs: [
            {
                targets: [5],
                orderable: false
            },
            {width: '10%', "aTargets": [5]}
        ],
        "columns": [
            {"data": "socialSecurity"},
            {"data": "firstName"},
            {"data": "username"},
            {"data": "email"},
            {"data": "role"},
            {
                className: 'actions-btn',
                "render": function (data, type, row) {
                    var btn = "<button type='button' class='btn btn-view btn-icon btn-xs  detail-btn'>" +
                        " " + Translator.trans('btn.view') + "</button>";
                    return btn;
                }
            }

        ],
        ajax: {
            url: Routing.generate("staff_json_list"),
            data: function (d) {
                d.criteria = serializeArrayToObjectByKey($('#filterStaffForm').serializeArray(), 'name');
                return d;
            },
            type: 'post'
        }
    });

    $(document).on('click', '.detail-btn', function () {
        loader.show();
        var staffId = $(this).parentsUntil('tbody', 'tr').attr('id');
        ajaxCall({
            url: Routing.generate('staff_detail', {'staff': staffId}),
            'type': 'json'
        }, function (data) {

            showDefaultModal(Translator.trans('staff.list.details'), data.data, data.footer);
            initRolesTable();
            loader.hide();
        });
    });

    $('#reset-filter').on('click', function () {
        resetFilter($('.filter-zone'));
        staff.ajax.reload();
    });

    $("#export-btn").on('click', function () {
        submitExportDocumentFile(".filter-zone", staff, Routing.generate("staff_json_list", {"download": 1}));
    });

    $("#export-xls").on('click', function () {
        submitExportDocumentFile(".filter-zone", staff, Routing.generate("staff_json_list", {"download": 2}));
    });
    $(document).on('click', '#attribute-role', function () {
        loader.block();
        var staffId = $(this).parent().attr('data-user');
        ajaxCall({
            url: Routing.generate('attribute_role', {'staff': staffId}),
            'type': 'json'
        }, function (data) {
            showDefaultModal(Translator.trans('staff.list.attribute_role'), data.formBody, data.footer);
        });
        loader.unblock();
    });

    $(document).on('click', '#default-password', function () {
        loader.show();
        var staffId = $(this).parent().attr('data-user');
        ajaxCall({
            url: Routing.generate('default_password', {'staff': staffId}),
            'type': 'json'
        }, function (data) {
            showDefaultModal(Translator.trans('staff.list.default_password_title'), data.formBody, data.footer);
            loader.hide();
        });
    });


    $(document).on('click', '#change-email', function () {
        loader.show();
        var staffId = $(this).parent().attr('data-user');
        ajaxCall({
            url: Routing.generate('change_email', {'staff': staffId}),
            'type': 'json'
        }, function (data) {
            if(data.errors !== undefined)
            {
                var body = '<div class="alert alert-danger">' +
                    '<span class="glyphicon glyphicon-remove"></span> ' + Translator.trans('staff.list.email.right_error') +
                    '</div>';
                showDefaultModal(Translator.trans('staff.list.change_email'), body, data.footer);
            }else
            {
                showDefaultModal(Translator.trans('staff.list.change_email'), data.formBody, data.footer);
            }
            loader.hide();
        });
    });

    $(document).on('click', '#btn-validate-email', function () {
        var staffId = $(this).parent().attr('data-user');
        var defaultPasswordForm = $('#changeEmailForm');
        loader.show();
        ajaxCall({
                url: Routing.generate('change_email', {'staff': staffId}),
                method: POST,
                data: defaultPasswordForm.serialize()
            },
            function (res) {
                if (res.errors === undefined && res.formError === undefined) {
                    ajaxCall({
                        url: Routing.generate('staff_detail', {'staff': staffId}),
                        'type': 'json'
                    }, function (data) {
                        var body = '<div class="alert alert-success">' +
                            '<span class="glyphicon glyphicon-ok"></span> ' + Translator.trans('staff.list.email.success') +
                            '</div>' + data.data;
                        showDefaultModal(Translator.trans('staff.list.details'), body, data.footer);
                        initRolesTable();
                        staff.ajax.reload();

                    });
                } else if (res.errors === undefined) {
                    defaultPasswordForm.html(res.formError['0']);
                }
                loader.hide();
            });
    });

    $(document).on('click', '#btn-validate-role', function () {
        var staffId = $(this).parent().attr('data-user');
        var AttributeRoleForm = $('#AttributeRoleForm');
        loader.show();
        ajaxCall({
                url: Routing.generate('attribute_role', {'staff': staffId}),
            method: POST,
            data: AttributeRoleForm.serialize()
        },
            function (res) {
                if (res.errors === undefined && res.formError === undefined) {
                    ajaxCall({
                        url: Routing.generate('staff_detail', {'staff': staffId}),
                        'type': 'json'
                    }, function (data) {
                        var body = '<div class="alert alert-success">' +
                            '<span class="glyphicon glyphicon-ok"></span> ' + Translator.trans('staff.list.roles.success') +
                            '</div>' + data.data;
                        showDefaultModal(Translator.trans('staff.list.details'), body, data.footer);
                        initRolesTable();

                    });
                } else if (res.errors === undefined) {
                    AttributeRoleForm.html(res.formError['0']);
                }
                loader.hide();
            });


    });

    $(document).on('click', '#btn-validate-password', function () {
        var staffId = $(this).parent().attr('data-user');
        var defaultPasswordForm = $('#defaultPasswordForm');
        loader.show();
        ajaxCall({
                url: Routing.generate('default_password', {'staff': staffId}),
                method: POST,
                data: defaultPasswordForm.serialize()
            },
            function (res) {
                if (res.errors === undefined && res.formError === undefined) {
                    ajaxCall({
                        url: Routing.generate('staff_detail', {'staff': staffId}),
                        'type': 'json'
                    }, function (data) {
                        var body = '<div class="alert alert-success">' +
                            '<span class="glyphicon glyphicon-ok"></span> ' + Translator.trans('staff.list.password.success') +
                            '</div>' + data.data;
                        showDefaultModal(Translator.trans('staff.list.details'), body, data.footer);
                        initRolesTable();

                    });
                } else if (res.errors === undefined) {
                    defaultPasswordForm.html(res.formError['0']);
                }
                loader.hide();
            });

    });

    $(document).on('click', '#btn-cancel', function () {
        loader.show();
        var staffId = $(this).parent().attr('data-user');
        ajaxCall({
            url: Routing.generate('staff_detail', {'staff': staffId}),
            'type': 'json'
        }, function (data) {
            showDefaultModal(Translator.trans('staff.list.details'), data.data, data.footer);
            initRolesTable();

        });
        loader.hide();
    });

    $(document).on('click', '#synchronise-btn', function () {
        loader.block();
        ajaxCall({
            method: 'GET',
            url: Routing.generate('synchronize_users')
        }, function (res) {
            if (res.errors === undefined) {
                if (res.errors === undefined) {
                    var bodyModal = Translator.trans('synchronize.users_added', {
                        'addedUsers': res.countNewUsers,
                        'deletedUsers': res.countDeletedUsers
                    });
                    showDefaultModal(Translator.trans('staff.list.synchronize.success'), bodyModal);
                    staff.ajax.reload();
                } else {

                }
            }
            loader.unblock();
        }, null, function () {

        });
    });

    $(document).on('click', '.btn-delete-role', function () {
        loader.block();
        var staffId = $(this).parents('tr').attr('data-user');
        var roleId = $(this).parents('tr').attr('data-role');
        ajaxCall({
            url: Routing.generate('delete_staff_role', {'staff': staffId, 'role': roleId}),
            'type': 'json'
        }, function (data) {
            ajaxCall({
                url: Routing.generate('staff_detail', {'staff': staffId}),
                'type': 'json'
            }, function (data) {
                var body = '<div class="alert alert-success">' +
                    '<span class="glyphicon glyphicon-ok"></span> ' + Translator.trans('staff.list.roles.deleted') +
                    '</div>' + data.data;
                showDefaultModal(Translator.trans('staff.list.details'), body, data.footer);
                initRolesTable();
                staff.ajax.reload();
            });
        });
        loader.unblock();
    })

});
