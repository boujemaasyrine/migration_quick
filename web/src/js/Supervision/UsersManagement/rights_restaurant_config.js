$(function(){loader.block(),ajaxCall({method:"GET",url:Routing.generate("get_existing_rights",{})},function(e){if(void 0===e.errors){var r=$("#Role"),t=$("#lines"),l=t.attr("data-prototype");$.each(e.rights,function(e,t){var o=l.replace(/__name__label__/g,e).replace(/__name__/g,e),i='id="rights_for_roles_roles_'+e+'_role"';o=o.replace(i,i+' value="'+e+'"'),$.each(t,function(e,r){var t='option value="'+r.idRight+'"';o=o.replace(t,t+"selected")}),r.append(o),orderMultoSelect($("#rights_for_roles_roles_"+e+"_right")),initMultiSelect("#rights_for_roles_roles_"+e+"_right")}),loader.unblock()}},null,function(){}),$(document).on("change","#rights_for_roles_rolesLabel",function(){var e=$(this).val();$("#Role").children("div").each(function(r){$(this).attr("style","display:none;"),$("#"+e).attr("style","display: inherit")})})});