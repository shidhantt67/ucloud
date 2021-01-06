<?php
// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

// pie chart of the status of items
$data = array();
$labels = array();
$dataForPie = $db->getRows("SELECT COUNT(1) AS total, status FROM file GROUP BY file.status ORDER BY COUNT(1) DESC");
foreach($dataForPie AS $dataRow)
{
    $data[] = (int) $dataRow['total'];
    $labels[] = UCWords(adminFunctions::t($dataRow['status'], $dataRow['status']));
}

$colors = array("#BDC3C7",
        "#9B59B6",
        "#E74C3C",
        "#26B99A",
        "#3498DB");
?>

<script type="text/javascript">
    $(function () {
        var options1a = {
            legend: false,
            responsive: false
        };

        new Chart(document.getElementById("canvas1"), {
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
                            "#49A9EA"
                        ]
                    }]
            },
            options: options1a
        });
  
        $('#wrapper_file_status_chart .background-loading').removeClass('background-loading');
        
        updatePieChartTable1a();
    });
    
    function updatePieChartTable1a()
    {
        tableHtml = '';
        <?php foreach($labels AS $k=>$label): ?>
        tableHtml += '<tr><td><p><i class="fa fa-square blue" style="color: <?php echo $colors[$k]; ?>"></i> <?php echo validation::safeOutputToScreen($label); ?></p></td><td class="pull-right"><?php echo $data[$k]; ?></td></tr>';
        <?php endforeach; ?>

        $('.wrapper_file_status_chart .tile_info').html(tableHtml);
    }
</script>