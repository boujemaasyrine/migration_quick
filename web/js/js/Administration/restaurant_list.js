$(function(){list_restaurant=initSimpleDataTable("#restaurant_list_table",{searching:!0,lengthMenu:!1,order:[[1,"asc"]],lengthChange:!1,aoColumnDefs:[{bSortable:!1,aTargets:[7]}]}),$(document).on("click",".detail-btn",function(t){var a="detail-restaurant-modal-"+$(this).attr("id");showDefaultModal(Translator.trans("restaurant.list.details"),$("#"+a).html())}),$("#export-btn").on("click",function(){submitExportDocumentFile(".filter-zone",list_restaurant,Routing.generate("restaurant_list_export"))})});