/**
 * Created by mchrif on 03/12/2015.
 */

$(function() {
    $('#ca_table').DataTable({
        searching: true,
        pageLength: 10,
        lengthMenu: false,
        "dom": ''
    });

    var yearlyCaChart = dc.rowChart('#yearly-ca-chart');
    var data = [{
        date: "2010-01-01T9:00:0",
        ca_hrs: 100.5,
        to_cumul: 0,
        take_out: 0,
        pourcentage: 0,
        cits_cum: 3.00,
        cits_hrs: 3.00,
        moy_plat: 3.17
    },
        {
            date: "2010-12-31T10:00:00Z",
            ca_hrs: 200.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 3.00,
            moy_plat: 3.17
        },
        {
            date: "2011-11-30T11:00:00Z",
            ca_hrs: 400.40,
            to_cumul: 1.45,
            take_out: 1.45,
            pourcentage: 4.41,
            cits_cum: 9.00,
            cits_hrs: 6.00,
            moy_plat: 3.66
        },
        {
            date: "2012-11-14T12:00:00Z",
            ca_hrs: 600.72,
            to_cumul: 1.45,
            take_out: 0.00,
            pourcentage: 0.59,
            cits_cum: 32.00,
            cits_hrs: 23.00,
            moy_plat: 7.68
        },
        {
            date: "2013-11-14T13:00:00Z",
            ca_hrs: 800.30,
            to_cumul: 37.40,
            take_out: 35.95,
            pourcentage: 4.29,
            cits_cum: 85.00,
            cits_hrs: 53.00,
            moy_plat: 10.25
        },
        {
            date: "2015-11-14T15:00:00Z",
            ca_cumul: 1793.84,
            ca_hrs: 5000.40,
            to_cumul: 82.45,
            take_out: 13.20,
            pourcentage: 4.60,
            cits_cum: 162.00,
            cits_hrs: 31.00,
            moy_plat: 3588.07
        },
        {
            date: "2014-06-15T19:00:00Z",
            ca_cumul: 3200.50,
            ca_hrs: 3000.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 3.00,
            moy_plat: 2100.17
        },
        {
            date: "2014-11-15T19:00:00Z",
            ca_cumul: 3500.50,
            ca_hrs: 3600.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 3.00,
            moy_plat: 2500.17
        },
        {
            date: "2015-11-15T20:00:00Z",
            ca_cumul: 9.50,
            ca_hrs: 6000.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 3.00,
            moy_plat: 3000.17
        },
        {
            date: "2015-11-15T20:00:00Z",
            ca_cumul: 9.50,
            ca_hrs: 9.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 3.00,
            moy_plat: 3.17
        },
        {
            date: "2014-05-15T20:00:00Z",
            ca_cumul: 9.50,
            ca_hrs: 2354.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 3.00,
            moy_plat: 1899.17
        },
        {
            date: "2012-05-15T20:00:00Z",
            ca_cumul: 9.50,
            ca_hrs: 9.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 3.00,
            moy_plat: 3.17
        },
        {
            date: "2012-06-15T20:00:00Z",
            ca_cumul: 9.50,
            ca_hrs: 9.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 3.00,
            moy_plat: 3.17
        },
        {
            date: "2012-08-15T20:00:00Z",
            ca_cumul: 9.50,
            ca_hrs: 9.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 3.00,
            moy_plat: 3.17
        },
        {
            date: "2012-07-15T20:00:00Z",
            ca_cumul: 9.50,
            ca_hrs: 9.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 3.00,
            moy_plat: 348.17
        },
        {
            date: "2012-10-15T20:00:00Z",
            ca_cumul: 9.50,
            ca_hrs: 109.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 3.00,
            moy_plat: 3.17
        },
        {
            date: "2012-09-15T20:00:00Z",
            ca_cumul: 889.50,
            ca_hrs: 885.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 3.00,
            moy_plat: 3.17
        },
        {
            date: "2012-03-15T20:00:00Z",
            ca_cumul: 129.50,
            ca_hrs: 9.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 3.00,
            moy_plat: 3.17
        },
        {
            date: "2012-04-15T20:00:00Z",
            ca_cumul: 9.50,
            ca_hrs: 905.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 3.00,
            moy_plat: 3.17
        },
        {
            date: "2013-06-15T20:00:00Z",
            ca_cumul: 9.50,
            ca_hrs: 3500.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 3.00,
            moy_plat: 3.17
        },
        {
            date: "2014-12-15T20:00:00Z",
            ca_cumul: 9.50,
            ca_hrs: 2600.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 3.00,
            moy_plat: 1999.17
        },
        {
            date: "2013-03-15T20:00:00Z",
            ca_cumul: 4000,
            ca_hrs: 9.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 3.00,
            moy_plat: 3000.17
        },
        {
            date: "2014-01-15T20:00:00Z",
            ca_cumul: 4500,
            ca_hrs: 2500.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 500.00,
            moy_plat: 4000.17
        },
        {
            date: "2015-06-15T20:00:00Z",
            ca_hrs: 6500.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 500.00,
            moy_plat: 5000.17
        },
        {
            date: "2015-12-15T20:00:00Z",
            ca_hrs: 7000.5,
            to_cumul: 0,
            take_out: 0,
            pourcentage: 0,
            cits_cum: 3.00,
            cits_hrs: 500.00,
            moy_plat: 6000.17
        },
        {
            date: "2016-06-14T16:00:00Z",
            ca_cumul: 2147.96,
            ca_hrs: 8000.12,
            to_cumul: 82.45,
            take_out: 0.00,
            pourcentage: 3.84,
            cits_cum: 200.00,
            cits_hrs: 38.00,
            moy_plat: 6500.74
        }];
    var ndx = crossfilter(data);


    var all = ndx.groupAll();

    var yearlyDimension = ndx.dimension(function (d) {
        return new Date(d.date).getFullYear();
    });

    var yearlyDimensionGroup = yearlyDimension.group().reduceSum(function (d) {
        return d.ca_hrs;
    });

    yearlyCaChart/* dc.barChart('#volume-month-chart', 'chartGroup') */
        .width($("#graphique").width())
        .height(180)
        .margins({top: 10, right: 50, bottom: 30, left: 40})
        .group(yearlyDimensionGroup)
        .dimension(yearlyDimension);

    //yearlyCaChart.xAxis().tickFormat(
    //        function (v) {
    //            return v;
    //        });
    //yearlyCaChart.yAxis().ticks(5);


    var moveMonths = ndx.dimension(function (d) {
        return new Date(d.date);
    });

    var caHrs = moveMonths.group().reduceSum(function (d) {
        return d.ca_hrs;
    });
    var moyPlat = moveMonths.group().reduceSum(function (d) {
        return d.moy_plat;
    });
    var citsHrs = moveMonths.group().reduceSum(function (d) {
        return d.cits_hrs;
    });

    var moveChart = dc.compositeChart('#monthly-move-chart');
    moveChart
        .width($("#graphique").width()).height(200)
        .dimension(moveMonths)
        .mouseZoomable(true)
        .margins({top: 30, right: 50, bottom: 25, left: 40})
        .compose([
            dc.lineChart(moveChart).renderArea(true).colors(["blue"]).group(moyPlat, "Moy plat"),
            dc.lineChart(moveChart).renderArea(true).colors(["green"]).group(caHrs, "Chiffre d'afaire"),
            dc.lineChart(moveChart).renderArea(true).colors(["red"]).group(citsHrs, "Cits hrs")
        ])
        .legend(dc.legend().x(50).y(10).itemHeight(13).gap(5))
        .x(d3.time.scale().domain([new Date(2010, 0, 1), new Date(2017, 11, 31)]))
        .brushOn(false)
        .title(function (d) {
            return d.date;
        });
    dc.renderAll();
});
