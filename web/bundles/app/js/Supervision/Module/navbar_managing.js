/**
 * Created by anouira on 11/02/2016.
 */
//Opening Menu
$(document).ready(function () {
    if(typeof currentModule !== 'undefined') {
        switch (currentModule) {
            case 'financial':
                $("li.financial-item").slideDown();
                break;
            case 'supplying':
                $("li.supplying-item").slideDown();
                break;
            case 'stock':
                $("li.stock-item").slideDown();
                break;
            case 'staff':
                $("li.people-item").slideDown();
                break;
            case 'utilities':
                $("li.utility-item").slideDown();
                break;
            case 'config':
                $("li.config-item").slideDown();
                break;
            case 'report':
                $("li.reporting-item").slideDown();
                break;
        }
    }

    if ($(window).width() > 480) {
        $('ul.sf-menu').superfish();
    }

});

