$(function(){function e(){var e=$("#searchByCode");e.autocomplete({autoFill:!0,source:function(e,r){var o=e.term;return t(o)in a?void r(a[t(o)]):void ajaxCall({url:Routing.generate("find_active_products",{code:o,selectedType:cibledProducts,filterSecondary:"true"}),method:GET},function(e){void 0==e.errors&&(a[t(o)]=$.map(e.data[0],function(e){return{id:e.id,name:e.name,label:e.externalId+"- "+e.name,value:"",externalId:e.externalId}}),r(a[t(o)]))},null,null,!0)},select:function(e,t){var a=$(".product-line"+t.item.id),r=a.attr("data-product-id"),o=a.attr("data-product-external-id"),n=a.attr("data-product-name"),l=a.attr("data-category-name");if("none"!==a.css("display")){a.fadeOut(50);var d=$("#containerSheetModelLines"),c=parseInt(d.attr("data-count"));d.attr("data-count",c+1);var i=d.data("prototype").replace(/_line_number_/g,c).replace(/_id_product_/g,r).replace(/_ref_product_/g,o).replace(/_label_product_/g,n).replace(/_category_name_/g,l).replace(/_order_in_sheet_/g,c).replace(/_num_/g,sheetModelFormTable.data().length);sheetModelFormTable.row.add($(i)).draw(),Notif.success(Translator.trans("sheet_model.notification.item_selected_with_success",{product_name:n}))}else Notif.alert(Translator.trans("sheet_model.notification.item_already_exist",{product_name:n}));return $(this).val(""),!1}});var r=$("#searchByProductName");r.autocomplete({autoFill:!0,source:function(e,r){var o=e.term;return t(o)in a?void r(a[t(o)]):void ajaxCall({url:Routing.generate("find_active_products",{term:o,selectedType:cibledProducts,filterSecondary:"true"}),method:GET},function(e){void 0==e.errors&&(a[t(o)]=$.map(e.data[0],function(e){return{id:e.id,name:e.name,label:e.name,value:e.name,externalId:e.externalId}}),r(a[t(o)]))},null,null,!0)},select:function(e,t){var a=$(".product-line"+t.item.id),r=a.attr("data-product-id"),o=a.attr("data-product-external-id"),n=a.attr("data-product-name"),l=a.attr("data-category-name");if("none"!==a.css("display")){a.fadeOut(50);var d=$("#containerSheetModelLines"),c=parseInt(d.attr("data-count"));d.attr("data-count",c+1);var i=d.data("prototype").replace(/_line_number_/g,c).replace(/_id_product_/g,r).replace(/_ref_product_/g,o).replace(/_label_product_/g,n).replace(/_category_name_/g,l).replace(/_order_in_sheet_/g,c).replace(/_num_/g,sheetModelFormTable.data().length);sheetModelFormTable.row.add($(i)).draw(),$("#searchByProductName").val(n),Notif.success(Translator.trans("sheet_model.notification.item_selected_with_success",{product_name:n}))}else Notif.alert(Translator.trans("sheet_model.notification.item_already_exist",{product_name:n}));return $("#searchByCode").val(o),$(this).val(""),!1}})}function t(e){return e}var a={};specialInit=function(){e()},specialInit(),$(document).on("click",".product-line",function(){var e=$(this).attr("data-product-id"),t=$(this).attr("data-product-external-id"),a=$(this).attr("data-product-name"),r=$(this).attr("data-category-name"),o=!1,n=sheetModelFormTable.rows().data();if(void 0!==n[0]&&void 0!==n[0].length)for(var l=0;l<n[0].length;l++){var d=$(n[0][l]),c=$(d[1]).val();if(parseInt(c)===parseInt(e)){o=!0;break}}if(o)$(this).fadeOut(50);else{$(this).fadeOut(50);var i=$("#containerSheetModelLines"),s=parseInt(i.attr("data-count"));i.attr("data-count",s+1);var u=i.data("prototype").replace(/_line_number_/g,s).replace(/_id_product_/g,e).replace(/_ref_product_/g,t).replace(/_label_product_/g,a).replace(/_category_name_/g,r).replace(/_num_/g,sheetModelFormTable.data().length).replace(/_order_in_sheet_/g,s);sheetModelFormTable.row.add($(u)).draw(),Notif.success(Translator.trans("sheet_model.notification.item_selected_with_success",{product_name:a}))}}),$(document).on("click",".btnRemoveSheetModelLine",function(){var e=$(this).attr("data-product-id");$(".product-line"+e).fadeIn(50),$(this).closest("tr").remove(),sheetModelFormTable.row($(this).closest("tr")).remove().draw(!1)}),$(document).on("click","#print_btn",function(){var e=$($(this).closest("form")),t=e.attr("action"),a=$.addParamToUrl(t,"download",!0);e.attr("action",a);var r=sheetModelFormTable.page.len();sheetModelFormTable.page.len(-1).draw(),e.submit(),sheetModelFormTable.page.len(r).draw(),e.attr("action",t)}),$(document).on("click","#validateFormSheetModel",function(){var e=$("#sheet_model_form");bootbox.confirm({title:Translator.trans("inventory.new_sheet.confirm.message"),message:Translator.trans("popup.do_you_confirm_this_action"),closeButton:!1,buttons:{confirm:{label:Translator.trans("keyword.yes"),className:"btn-validate margin-right-left"},cancel:{label:Translator.trans("keyword.no"),className:"btn-default margin-right-left"}},callback:function(t){t&&(loader.block(),sheetModelFormTable.page.len(-1).draw(),sheetModelFormTable.search("").columns().search("").draw(),e.submit())}})})});