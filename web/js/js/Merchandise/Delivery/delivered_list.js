var deliveries=null,listSupp=null;$(function(){deliveries=initSimpleDataTable("#deliveries_table",{rowId:"id",processing:!0,serverSide:!0,searching:!1,order:[[3,"desc"]],columnDefs:[{targets:[6],orderable:!1}],columns:[{data:"num_delivery"},{data:"order.supplier"},{data:"order.dateOrder"},{data:"date"},{data:"valorization",render:function(e){return floatToString(e)}},{data:"responsible"},{className:"actions-btn",render:function(e,t,i){var r="<button type='button' class='btn blue  detail-btn'><span class='glyphicon glyphicon-eye-open'></span> &nbsp;"+Translator.trans("see")+"</button>";return r}}],ajax:{url:Routing.generate("deliveries_list"),data:function(e){return e.criteria=serializeArrayToObjectByKey($("#delivery-filter-form").serializeArray(),"name"),e},type:"post"}}),listSupp=$("#supplier").selectize(),listSupp=listSupp[0].selectize}),$(document).on("click",".detail-btn",function(){var e=$(this).parentsUntil("tbody","tr").attr("id");loader.show(),showDetailsInPopUp(Routing.generate("delivery_details",{delivery:e}))}),$(function(){$("#reset-filter").on("click",function(){resetFilter($(".filter-zone")),listSupp.clear()}),$("#export-btn").on("click",function(){submitExportDocumentFile(".filter-zone",deliveries,Routing.generate("deliveries_list",{download:1}))}),$("#export-xls").on("click",function(){submitExportDocumentFile(".filter-zone",deliveries,Routing.generate("deliveries_list",{download:2}))}),showEntityDetailsWhenDocumentReady("delivery_details","delivery","")});