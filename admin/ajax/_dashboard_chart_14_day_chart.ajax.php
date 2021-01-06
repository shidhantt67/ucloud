<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

// last 14 days chart
$tracker              = 14;
$last14Days           = array();
while ($tracker >= 0)
{
    $date              = date("Y-m-d", strtotime("-" . $tracker . " day"));
    $last14Days[$date] = 0;
    $tracker--;
}

$tracker = 1;
$data    = array();
$label   = array();

// get data
$chartData = $db->getRows("SELECT COUNT(1) AS total, MID(uploadedDate, 1, 10) AS date_part FROM file WHERE file.uploadedDate >= DATE_ADD(CURDATE(), INTERVAL -15 DAY) GROUP BY DAY(uploadedDate)");

// format data for easier lookups
$chartDataArr = array();
if($chartData)
{
	foreach($chartData AS $chartDataItem)
	{
		$chartDataArr[$chartDataItem{'date_part'}] = $chartDataItem['total'];
	}
}

// prepare for table
foreach ($last14Days AS $k => $total)
{
    $totalFiles = isset($chartDataArr[$k])?$chartDataArr[$k]:0;
    $data[]     = '[' . $tracker . ',' . (int) $totalFiles . ']';
    $label[]    = '[' . $tracker . ',\'' . date('jS', strtotime($k)) . '\']';
    $tracker++;
}

?>
<script>
    $(function () {
        var data1a = [
            <?php echo implode(", ", $data); ?>
        ];
        $("#canvas_dahs").length && $.plot($("#canvas_dahs"), [
            data1a
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
    });
</script>