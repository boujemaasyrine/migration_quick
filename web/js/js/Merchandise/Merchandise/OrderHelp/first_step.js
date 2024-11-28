/**
 * Created by anouira on 06/04/2016.
 */
var duPicker;
var auPicker;

function reloadCa(dontHide){
    if (dontHide == undefined){
        $('#next_step_link').hide();
    }

    ajaxCall({
        url: Routing.generate('calculate_ca'),
        dataType : 'json',
        data: {
            dd : $('#help_order_startDateLastWeek').val(),
            df : $('#help_order_endDateLastWeek').val()
        },
        method: 'POST'
    },function(data){
        if (data.data == null){
            $('#ca-zone').html("-");
        }else{
            $('#ca-zone').html(floatToString(data.data,0)+" &euro;");
        }

    });
}
var dateDebut = null;
var dateFin = null;
$(function(){
    reloadCa(true);

    var today = moment();
    dateDebut = $('#help_order_startDateLastWeek').val();
    dateFin = $('#help_order_endDateLastWeek').val();

    duPicker = $('#help_order_startDateLastWeek').pickadate('picker');
    duPicker.set('max',[today.year(),today.month(),today.date()])
    duPicker.on('open',function(){
        dateDebut = $('#help_order_startDateLastWeek').val();
    });
    duPicker.on('close',function(){
        if ($('#help_order_startDateLastWeek').val() != dateDebut){
            reloadCa();
        }
    });

    auPicker = $('#help_order_endDateLastWeek').pickadate('picker');
    auPicker.set('max',[today.year(),today.month(),today.date()])
    auPicker.on('open',function(){
          dateFin = $('#help_order_endDateLastWeek').val();
    });
    auPicker.on('close',function(){
        if ($('#help_order_endDateLastWeek').val() != dateFin){
            reloadCa();
        }
    });



    duPicker.set('disable', [{
        from : [today.year(),today.month(),today.date()]
    }]);
    auPicker.set('disable', [{
        from : [today.year(),today.month(),today.date()] , to : +3000
    }]);

});