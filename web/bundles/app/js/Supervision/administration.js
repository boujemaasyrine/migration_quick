/**
 * Created by mchrif on 10/02/2016.
 */

$(function() {
    initMultiSelect($('.multiselect'));

    $('#workflows_table').DataTable({
        searching: false,
        pageLength: 10,
        lengthMenu: false,
        initComplete: function () {
            $('#workflows_table_wrapper').find('.row:first').remove();
        },
        language: {
            processing: "Traitement en cours...",
            search: "Rechercher&nbsp;:",
            lengthMenu: "Afficher _MENU_ &eacute;l&eacute;ments",
            info: "Affichage de l'&eacute;lement _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
            infoEmpty: "Affichage de l'&eacute;lement 0 &agrave; 0 sur 0 &eacute;l&eacute;ments",
            infoFiltered: "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
            infoPostFix: "",
            loadingRecords: "Chargement en cours...",
            zeroRecords: "Aucun &eacute;l&eacute;ment &agrave; afficher",
            emptyTable: "Aucune donnée disponible dans le tableau",
            paginate: {
                first: "Premier",
                previous: "Pr&eacute;c&eacute;dent",
                next: "Suivant",
                last: "Dernier"
            },
            aria: {
                sortAscending: ": activer pour trier la colonne par ordre croissant",
                sortDescending: ": activer pour trier la colonne par ordre décroissant"
            }
        }
    });

    list_labels = initSimpleDataTable('#labels_list_table', {
        "aoColumnDefs": [
            {"bSortable": false, "aTargets": [2]}
        ]
    });

    $(document).on('click', '.btn-delete-label', function(){
        var label = $(this).attr('data-label');
        var labelId = $(this).parentsUntil('tbody','tr').attr('id');
        var type = $(this).parentsUntil('tbody','tr').attr('data-type');
        var header = Translator.trans('parameters.financial_management.delete');
        var content = Translator.trans('parameters.financial_management.delete_confirm', {label : label});
        var footer = '<div class="row" style="margin-bottom: 0px;">'+
            '<div class="col col-lg-2 col-md-6 col-sm-6 col-xs-6 pull-right">'+
            '<button class="btn btn-primary btn-block  waves-effect waves-light" type="button" data-type="'+ type +'" data-label="'+ labelId +'" id="confirm-delete-label">' +
            '<span>'+ Translator.trans('keyword.yes') +'</span></button>' +
            '</div>' +
            '<div class="col col-lg-2 col-md-6 col-sm-6 col-xs-6 pull-right">' +
            '<button class="btn red btn-block  waves-effect waves-light" type="button" data-dismiss="modal">' +
            '<span>'+ Translator.trans('keyword.no') +'</span></button>' +
            '</div>' +
            '</div>';
        showDefaultModal(header, content, footer);
    });

    $(document).on('click', '#confirm-delete-label', function(){
        loader.block();
        var labelId = $(this).attr('data-label');
        var type = $(this).attr('data-type');
        ajaxCall({
            url : Routing.generate('delete_label',{'label' : labelId}),
            'type' : 'json'
        },function(data){
            if(data.deleted !== undefined && data.deleted == true ){
                window.location.href = Routing.generate('labels_config', {'type': type});
            }
        });
        loader.unblock();
    });

    $("#export-btn").on('click', function () {
        var type = $(this).attr('data-type');
        submitExportDocumentFile(".filter-zone", list_labels, Routing.generate("labels_list_export", {"type": type}));
    });

    $(document).on("change", ".electronic-ticket", function () {
        var checked = false;
        $(".electronic-ticket").each(function (index, element) {
           if($(element).is(":checked") == true)
           {
               checked = true;
           }
        });
        if(checked == true)
        {
            $("#parameters_restaurant_eft option:selected").prop("selected", false);
            $("#parameters_restaurant_eft option:nth-child(2)").prop("selected", "selected");
            $("#hidden-eft").val("true");

        }
        else
        {
            $("#parameters_restaurant_eft option:selected").prop("selected", false);
            $("#parameters_restaurant_eft option:first").prop("selected", "selected");
            $("#hidden-eft").val("false");
        }
        $("#parameters_restaurant_paymentMethod_5").prop("checked", checked);
    });
    $(document).on("change", "#parameters_restaurant_paymentMethod_5", function () {
        if($(this).is(":checked"))
        {
            $("#parameters_restaurant_eft option:selected").prop("selected", false);
            $("#parameters_restaurant_eft option:nth-child(2)").prop("selected", "selected");
            $("#hidden-eft").val("true");
        }
        else
        {
            $("#parameters_restaurant_eft option:selected").prop("selected", false);
            $("#parameters_restaurant_eft option:first").prop("selected", "selected");
            $("#hidden-eft").val("false");
        }
    });




});
