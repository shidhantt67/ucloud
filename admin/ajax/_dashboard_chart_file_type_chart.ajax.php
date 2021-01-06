<?php
// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

// pie chart of file types
$data = array();
$labels = array();
$dataForPie = $db->getRows("SELECT COUNT(1) AS total, file.extension AS status FROM file WHERE status = 'active' GROUP BY file.extension ORDER BY COUNT(1) DESC");
$counter = 1;
$otherTotal = 0;
foreach($dataForPie AS $dataRow)
{
    if($counter > 5)
    {
        $otherTotal = $otherTotal + $dataRow['total'];
    }
    else
    {
        $data[] = (int) $dataRow['total'];
        $labels[] = UCWords(adminFunctions::t($dataRow['status'], $dataRow['status']));
    }
    $counter++;
}
if($otherTotal > 0)
{
    $data[] = (int) $otherTotal;
    $labels[] = UCWords(strtolower(adminFunctions::t('other', 'other')));
}

$colors = array("#BDC3C7",
        "#9B59B6",
        "#E74C3C",
        "#26B99A",
        "#3498DB",
        "#26B99A");
?>

<script type="text/javascript">
    $(function () {
        var options1b = {
            legend: false,
            responsive: false
        };

        new Chart(document.getElementById("canvas2"), {
            type: 'doughnut',
            tooltipFillColor: "rgba(51, 51, 51, 0.55)",
            data: {
                labels: [
                    <?php echo '"'.implode('","', $labels).'"'; ?>
                ],
                datasets: [{
                        data: [<?php echo implode(',', $data); ?>],
                        backgroundColor: [
                            <?php echo '"'.implode('","', $colors).'"'; ?>
                        ],
                        hoverBackgroundColor: [
                            "#CFD4D8",
                            "#B370CF",
                            "#E95E4F",
                            "#36CAAB",
                            "#49A9EA",
                            "#36CAAB"
                        ]
                    }]
            },
            options: options1b
        });
  
        $('#wrapper_file_type_chart .background-loading').removeClass('background-loading');
        
        updatePieChartTable1b();
    });
    
    function updatePieChartTable1b()
    {
        tableHtml = '';
        <?php foreach($labels AS $k=>$label): ?>
        tableHtml += '<tr><td><p><i class="fa fa-square blue" style="color: <?php echo $colors[$k]; ?>"></i> <?php echo validation::safeOutputToScreen($label); ?></p></td><td class="pull-right"><?php echo $data[$k]; ?></td></tr>';
        <?php endforeach; ?>

        $('.wrapper_file_type_chart .tile_info').html(tableHtml);
    }
</script>