$(function(){$(document).on("click","#downloadReport",function(){var t=$(this).closest("form"),n=t.attr("action");n.indexOf("?")>-1&&(n=n.substring(0,n.indexOf("?"))),t.attr("action",$.addParamToUrl(n,"download",!0)),$(t).submit()}),$(document).on("click","#export-btn",function(){var t=$(this).closest("form"),n=t.attr("action");n.indexOf("?")>-1&&(n=n.substring(0,n.indexOf("?"))),t.attr("action",$.addParamToUrl(n,"export",!0)),$(t).submit()}),$(document).on("click","#export-xls",function(){var t=$(this).closest("form"),n=t.attr("action");n.indexOf("?")>-1&&(n=n.substring(0,n.indexOf("?"))),t.attr("action",$.addParamToUrl(n,"xls",!0)),$(t).submit()}),$(document).on("click","#generateReport",function(){loader.block();var t=$(this).closest("form"),n=t.attr("action");n.indexOf("?")>-1&&(n=n.substring(0,n.indexOf("?"))),t.attr("action",n),$(t).submit()});var t,n=$("#table"),i=n.find("tbody.mainTbody tr.mainRow:first").children();t=i.map(function(){return $(this).innerWidth()}).get(),n.find("thead tr.mainHeader").children().each(function(n,i){$(i).width(t[n])})});