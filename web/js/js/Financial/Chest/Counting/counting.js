$(function(){function t(){var t=moment($(".count_date").first().attr("data-last-closured-date"),"YYYY/MM/DD");t.add(1,"days"),fiscalDatepicker=initDatePicker(".count_date",{disable:[!0,{from:new Date(t.format("YYYY/MM/DD")),to:new Date(moment())}]})}function a(){if(""!=$("#count_date").val()){$("#cashbox_count_date").val();loader.block(),ajaxCall({method:"POST",url:Routing.generate("cashbox_counting",{}),data:$("#chest_count_form").serialize()},function(a){void 0===a.errors&&($("#cashbox_count_container").html(a.data[0]),syncRealCashBlock()),t()},null,function(){loader.unblock()})}}t();var n=null;$(document).on("change","#cashbox_count_date",function(){a()}),$(document).ready(function(){processSummaryCalculation()}),$(document).on("click","#validateChestCounting",function(){var a=!1;if($(this).hasClass("password-checked")&&(a=!0),0==a&&$(this).check_password(Translator.trans("title.popup_confirm_password"),"click",$(this),null,$("#chest_count_container")),1==a){if(!countingIsValid)return;var e=parseFloat($($(".chest_total_gap").first()).text());bootbox.confirm({title:Translator.trans("chest.validation_confirmation",{}),message:Translator.trans("chest.validation_confirmation_body",{gap:"<span class='bold'>"+floatToString(e)+"<i class='glyphicon glyphicon-euro'></i></span>"}),closeButton:!1,buttons:{confirm:{label:Translator.trans("keyword.yes"),className:"btn-validate margin-right-left"},cancel:{label:Translator.trans("keyword.no"),className:"btn-default margin-right-left"}},callback:function(a){a&&($(".exchange_fund_rate_select").removeAttr("disabled"),loader.block(),$("#chest_count_date").removeAttr("disabled"),$("#chest_count_cashboxFund_initialCashboxFunds").removeAttr("disabled"),ajaxCall({method:"POST",url:Routing.generate("validate_chest",{}),data:$("#chest_count_form").serialize()},function(a){if(void 0===a.errors){var o="";a.data.gap>25?o="green-text":a.data.gap<-25&&(o="red-text");var s="<p style='text-align: center'><h3 style='text-align: center;'>"+Translator.trans("chest.validation_confirmation_body",{gap:"<span class='bold "+o+"'>"+floatToString(e)+"<i class='glyphicon glyphicon-euro'></i></span>"})+"</h3></p>",l="";0!==a.data.gap&&(l+="<a href='"+a.data.download_url+"' class='btn btn-print'> "+Translator.trans("btn.print")+"</a>"),l+="<span id='okBtn' data-small-chest-id='"+a.data.smallChestId+"' style='margin-right: 5px;' class='btn btn-close'> "+Translator.trans("btn.close")+"</span>",n=showDefaultModal(Translator.trans("chest.chest_count_validated"),s,l,"98%","",!1)}else $("#chest_count_container").html(a.data[0]),processSummaryCalculation(),t()},null,function(){loader.unblock()}))}})}}),$(document).on("click","#okBtn",function(){loader.block(),inChestCountAdminClosing()?nextStep("comparable_day","chest_count"):nextStep("chest_list","chest_count")}),$(document).on("click",".delete-row",function(){$(this).closest(".row").remove(),calculateExchangeFundTotal()}),$(window).keydown(function(t){if(t.keyCode==KEY_ENTER)return t.preventDefault(),!1}),$(document).on("keyup",".only-number input[type=text]",function(t){t.stopPropagation(),32==t.keyCode&&$(this).val($.trim($(this).val())),isNaN($(this).val())?($("#validateChestCounting").attr("disabled",!0),$(this).addClass("shadow-danger"),$("#chest_preview_table").addClass("shadow-danger"),countingIsValid=!1):($(this).removeClass("shadow-danger"),$(".only-number input[type=text]").hasClass("shadow-danger")||($("#validateChestCounting").attr("disabled",!1),countingIsValid=!0,$("#chest_preview_table").removeClass("shadow-danger")))})});