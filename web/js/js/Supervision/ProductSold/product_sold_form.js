$(function(){function t(t,e){var a=$("#"+t+"_recipeLines_"+e+"_productPurchased"),n=$("#"+t+"_recipeLines_"+e+"_productPurchasedName"),o=$("#"+t+"_recipeLines_"+e+"_supplierCode"),r=$("#"+t+"_recipeLines_"+e+"_qty"),i=$($("#"+t+"_recipeLines_"+e+"_qty").siblings("span")[0]);autocompleteProduct(a,o,n,r,i)()}function e(){i={},$(".canal_selector").each(function(t,e){var a=$(e).val();i[a.toString()]=!0}).each(function(t,e){$(e).find("option").each(function(t,e){if(t>=0){var a=$($(e).closest("select")[0]).val().toString(),n=a===$(e).attr("value").toString();n||(void 0!==i[$(e).attr("value")]?$(e).attr("disabled","disabled"):$(e).removeAttr("disabled"))}})})}function a(t){var e=$("#"+t+"_recipeLines"),a=$("#recipe_revenu_"+t),n=0;e.find(".qty-input").each(function(t,e){var a=parseFloat($(e).val())*parseFloat($(e).attr("data-usage-unit-price"));""==$(e).val()||isNaN(a)||(n+=a,$(e).closest("tr").find(".total-amount").html(a.toFixed("2")+" €"))}),a.text(floatToString(n)+"€")}var n=$("#transformed_product_block"),o=$("#non_transformed_product_block"),r=$("#product_sold_recipes"),i={};orderMultoSelect($("#product_sold_restaurants")),initMultiSelect("#product_sold_restaurants"),$(document).on("change","#product_sold_type input",function(t){$(t.target).val()==TRANSFORMED_PRODUCT?(n.fadeIn(200),o.fadeOut(200)):(n.fadeOut(200),o.fadeIn(200))}),$(document).on("click","#addNewRecipe",function(){var t=parseInt($(this).attr("data-count"));$(this).attr("data-count",t+1);var e=$(this).attr("data-prototype").replace(/__name__label__/g,"").replace(/__name__/g,t);e=$(e),r.append(e)}),$(document).on("click",".deleteRecipe",function(){var t=$(this).closest(".row").parent().parent();t.fadeOut(200),t.remove()}),$(".recipe_line_row, .non_transformed_product_row").each(function(t,e){e=$(e);var a=$(e.find(".productIdInput")[0]),n=$(e.find(".productNameInput")[0]),o=$(e.find(".codeSupplierInput")[0]),r=$(e.find(".qty-input")[0]),i=$(e.find(".usageUnitLabel")[0]);$(e).hasClass("non_transformed_product_row")?autocompleteProduct(a,o,n,r,i,function(t,e){console.debug(e.item),$("#purchasedProductPrice").text(floatToString(e.item.buyingCost/(e.item.inventoryQty*e.item.usageQty))+" €")})():autocompleteProduct(a,o,n,r,i)()}),$(document).on("click",".addNewRecipeLine",function(){var e=$(this).attr("data-recipe-number"),a=$("#"+e+"_recipeLines"),n=parseInt(a.attr("data-count"));a.attr("data-count",n+1);var o=$(this).attr("data-prototype").replace(/__recipe_line__label__/g,"").replace(/_unit_label_/g,"").replace(/__recipe_line__/g,n).replace(/_usage_unit_price_/g,0);o=o.replace(/^<div>/,""),o=o.replace(/$<\/div>/,""),o=o.replace(/<label><\/label>/,""),a.append(o),t(e,n)}),$(document).on("click",".deleteRecipeLine",function(){var t=$(this).closest(".recipe_line_row");t.fadeOut(200),t.remove()}),$(document).on("click",".panel-heading a",function(){$(this).find(".glyphicon"),$(this).find(".glyphicon").toggleClass("glyphicon-collapse-up"),$(this).find(".glyphicon").toggleClass("glyphicon-collapse-down")}),$(document).on("focus",".canal_selector",function(t){e()}),$(document).on("keyup",".qty-input",function(){var t=$($(this).closest(".recipeBlock")[0]).attr("data-recipe-number");a(t)}),$(document).on("click","#validateForm",function(t){t.preventDefault(),$("#sheet_model_form").submit()}),$(document).on("click","#validateFormWithSynchro",function(t){t.preventDefault();var e=$("#sheet_model_form"),a=e.attr("action");e.attr("action",$.addParamToUrl(a,"synchronize",!0)),$(e).submit()}),$(document).on("click","#product_sold_table .btn-deactivate",function(t){var e=$(this).data("id");loader.show(),ajaxCall({url:Routing.generate("deactivate_product_sold_in_restaurants",{productSold:e}),type:"json"},function(t){showDefaultModal(Translator.trans("item.inventory.deactivate"),t.data,t.footerBtn,"80%"),loader.hide()})}),$(document).on("submit","form#form-deactivate-product-sold",function(t){t.preventDefault();var e=$(this),a=e.attr("action");loader.show(),ajaxCall({method:"POST",url:a,data:e.serialize()},function(t){showDefaultModal(Translator.trans("item.inventory.deactivate"),t.data,t.footerBtn,"80%"),loader.hide()},"",function(t){loader.hide()})})});