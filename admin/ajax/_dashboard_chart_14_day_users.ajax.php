<?php
// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

// last 14 days user registrations
$tracker = 14;
$last14Days = array();
while($tracker >= 0)
{
    $date = date("Y-m-d", strtotime("-" . $tracker . " day"));
    $last14Days[$date] = 0;
    $tracker--;
}

$tracker = 1;
$dataFree = array();
$dataPaid = array();
$label = array();

// get data
$chartData1 = $db->getRows("SELECT COUNT(1) AS total, MID(datecreated, 1, 10) AS date_part FROM users WHERE users.datecreated >= DATE_ADD(CURDATE(), INTERVAL -15 DAY) AND level_id IN (SELECT id FROM user_level WHERE level_type = 'free') GROUP BY DAY(datecreated)");

// format data for easier lookups
$chartDataArr1 = array();
if($chartData1)
{
    foreach($chartData1 AS $chartDataItem1)
    {
        $chartDataArr1[$chartDataItem1{'date_part'}] = $chartDataItem1['total'];
    }
}

// get data
$chartData2 = $db->getRows("SELECT COUNT(1) AS total, MID(datecreated, 1, 10) AS date_part FROM users WHERE users.datecreated >= DATE_ADD(CURDATE(), INTERVAL -15 DAY) AND level_id IN (SELECT id FROM user_level WHERE level_type = 'paid') GROUP BY DAY(datecreated)");

// format data for easier lookups
$chartDataArr2 = array();
if($chartData2)
{
    foreach($chartData2 AS $chartDataItem2)
    {
        $chartDataArr2[$chartDataItem2{'date_part'}] = $chartDataItem2['total'];
    }
}

// prepare for table
foreach($last14Days AS $k => $total)
{
    $totalUsers = isset($chartDataArr1[$k]) ? $chartDataArr1[$k] : 0;
    $dataFree[] = '[' . $tracker . ',' . (int) $totalUsers . ']';
    $totalUsers = isset($chartDataArr2[$k]) ? $chartDataArr2[$k] : 0;
    $dataPaid[] = '[' . $tracker . ',' . (int) $totalUsers . ']';
    $label[] = '[' . $tracker . ',\'' . date('jS', strtotime($k)) . '\']';
    $tracker++;
}
?>
<script>
    $(function () {
        var data1c = [
            <?php echo implode(", ", $dataFree); ?>
        ];
        var data2c = [
            <?php echo implode(", ", $dataPaid); ?>
        ];
        $("#canvas_dahs3").length && $.plot($("#canvas_dahs3"), [
            data1c, data2c
        ], {
            series: {
                lines: {
                    show: false,
                    fill: false,
                    steps: false
                },
                bars: {show: true, barWidth: 0.9, align: 'center'},
                points: {
                    radius: 0,
                    show: true
                },
                shadowSize: 2
            },
            grid: {
                verticalLines: true,
                hoverable: true,
                clickable: true,
                tickColor: "#d5d5d5",
                borderWidth: 1,
                color: '#fff'
            },
            colors: ["rgba(38, 185, 154, 0.38)", "rgba(3, 88, 106, 0.38)"],
            xaxis: {
                tickColor: "rgba(51, 51, 51, 0.06)",
                mode: "time",
                tickSize: [1, "day"],
                axisLabel: "Date",
                axisLabelUseCanvas: true,
                axisLabelFontSizePixels: 12,
                axisLabelFontFamily: 'Verdana, Arial',
                axisLabelPadding: 10,
                ticks: [<?php echo implode(", ", $label); ?>]
            },
            yaxis: {
                ticks: 8,
                tickColor: "rgba(51, 51, 51, 0.06)",
            },
            tooltip: true
        });
        
        $('#wrapper_14_day_users .background-loading').removeClass('background-loading');
    });
</script>
