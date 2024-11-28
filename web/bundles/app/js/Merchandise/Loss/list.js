/**
 * Created by hcherif on 15/02/2016.
 */

$(function() {
    initSimpleDataTable('#loss-list-table', {
        searching: false,
        lengthMenu: false,
        initComplete: function () {
            $('#loss_list_table_length').closest('.row').remove();
        }
    });


    $(document).on('change','#consultation_loss_type',function(){
        var type = $('#consultation_loss_type').val();
        ajaxCall({
                url : Routing.generate('loss_consult',{type : type}),
                dataType : 'json'
            },
            function(data){
                $('#consult_form').html(data.data['0']);
                $('#hourly_loss_startTime_hour').attr("class", "form-control");
                $('#hourly_loss_endTime_hour').attr("class", "form-control");
                initDatePicker();
            })
    });


    $(document).on('click','#consult-loss-product',function(e){
        var type = $('#consultation_loss_type').val();
        loader.show();
        ajaxCall({
                url : Routing.generate('loss_consult',{type : type}),
                Method: GET,
                data: $("#hourlyLossForm").serialize(),
            },
            function(data){
                $('#consult_loss_table').html(data.data['0']);
                initSimpleDataTable('#loss-list-table', {
                    searching: false,
                    lengthMenu: false,
                    initComplete: function () {
                        $('#loss_list_table_length').closest('.row').remove();
                    }
                });
            });
        loader.hide();
    });

});


