<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Site Settings');
define('ADMIN_SELECTED_PAGE', 'configuration');
define('ADMIN_SELECTED_SUB_PAGE', 'setting_manage');

// includes and security
include_once('_local_auth.inc.php');

// handle page submission
if(isset($_REQUEST['submitted']))
{
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    else
    {
        if(isset($_REQUEST['config_item']))
        {
            $configItems = $_REQUEST['config_item'];
            if(COUNT($configItems))
            {
                // update config items
                foreach($configItems AS $configKey=>$configValue)
                {
                    if(is_array($configValue))
                    {
                        $configValue = implode('|', $configValue);
                    }
                    $db->query('UPDATE site_config SET config_value = :config_value WHERE config_key = :config_key', array('config_value' => $configValue, 'config_key' => $configKey));
                }
                
                adminFunctions::setSuccess("Configuration updated.");
            }
        }
    }
}

// page header
include_once('_header.inc.php');

// defaults
$filterByGroup = null;
if(isset($_REQUEST['filterByGroup']))
{
    $filterByGroup = trim($_REQUEST['filterByGroup']);
}

// load config groups for edit settings
$sQL = "SELECT config_group FROM site_config WHERE config_group != 'System' ";
if($filterByGroup != null)
{
    $sQL .= "AND config_group = ".$db->quote($filterByGroup)." ";
}
$sQL .= "GROUP BY config_group ORDER BY config_group";
$groupDetails = $db->getRows($sQL);

// for the drop-down select
$groupListing = $db->getRows("SELECT config_group FROM site_config WHERE config_group != 'System' GROUP BY config_group ORDER BY config_group");
?>

<script>
    oTable = null;
    gConfigId = null;
    $(document).ready(function () {
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/setting_manage.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 50,
            "aaSorting": [[1, "asc"]],
            "aoColumns": [
                {bSortable: false, sWidth: '4%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                {sName: 'config_description', sWidth: '48%'},
                {sName: 'config_value', sClass: "adminResponsiveHide"},
                {bSortable: false, sWidth: '10%', sClass: "center"}
            ],
            "fnServerData": function (sSource, aoData, fnCallback) {
                aoData.push({"name": "filterByGroup", "value": $('#filterByGroup').val()});
                aoData.push({"name": "filterText", "value": $('#filterText').val()});
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/setting_manage.ajax.php",
                    "data": aoData,
                    "success": fnCallback
                });
            }
        });

        // update custom filter
        $('.dataTables_filter').html($('#customFilter').html());
    });

    function setLoader()
    {
        $('#configurationForm').html('Loading, please wait...');
    }

    function loadEditConfigurationForm()
    {
        $.ajax({
            type: "POST",
            url: "ajax/setting_manage_edit_form.ajax.php",
            data: {gConfigId: gConfigId},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    $('#configurationForm').html(json.msg);
                } else
                {
                    $('#configurationForm').html(json.html);
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $('#configurationForm').html(XMLHttpRequest.responseText);
            }
        });
    }

    function updateConfigurationValue()
    {
        // get data
        configId = $('#configIdElement').val();
        configValue = $('#configValueElement').val();

        $.ajax({
            type: "POST",
            url: "ajax/setting_manage_edit_process.ajax.php",
            data: {configId: configId, configValue: configValue},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    showError(json.msg, 'popupMessageContainer');
                } else
                {
                    showSuccess(json.msg);
                    reloadTable();
                    $("#editConfigurationForm").dialog("close");
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });

    }

    function editConfigurationForm(configId)
    {
        gConfigId = configId;
        $('#editConfigurationForm').dialog('open');
    }

    function reloadTable()
    {
        oTable.fnDraw(false);
    }
</script>

<div class="right_col" role="main">
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3>Site Configuration</h3>
            </div>
            <div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                    <form method="GET" action="setting_manage.php">
                        <div class="input-group">
                            <select name="filterByGroup" id="filterByGroup" class="form-control">
                                <option value="" DISABLED>- Other Settings -</option>
                                <option value="">- Show All -</option>
                                <?php
                                foreach ($groupListing AS $groupListingItem)
                                {
                                    echo '<option value="' . $groupListingItem['config_group'] . '"';
                                    if (($filterByGroup) && ($filterByGroup == $groupListingItem['config_group']))
                                    {
                                        echo ' SELECTED';
                                    }
                                    echo '>' . $groupListingItem['config_group'] . '</option>';
                                }
                                ?>
                            </select>
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="submit">Go!</button>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>

        <?php echo adminFunctions::compileNotifications(); ?>

        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <form action="setting_manage.php<?php echo $filterByGroup!=null?('?filterByGroup='.adminFunctions::makeSafe($filterByGroup)):''; ?>" method="POST" class="form-horizontal form-label-left">
                    <?php
                    foreach($groupDetails AS $groupDetail)
                    {
                        // load config items
                        $configItems = $db->getRows("SELECT * FROM site_config WHERE config_group = :config_group ORDER BY display_order ASC, config_description ASC", array('config_group' => $groupDetail['config_group']));
                    ?>
                    <div class="x_panel">
                        <div class="x_title">
                            <h2><?php echo adminFunctions::makeSafe($groupDetail['config_group']); ?></h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <br/>
                            <?php foreach ($configItems AS $config): ?>
                            <div class="form-group" style="margin-bottom: 0px;">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="username"><?php echo adminFunctions::makeSafe($config['label']); ?></label>
                                <?php
                                // prep key for title text
                                $titleText = 'SITE_CONFIG_'.strtoupper($config['config_key']);
                                
                                $colSize = 6;
                                switch($config['config_type'])
                                {
                                    case 'integer':
                                        $element = '<input name="config_item['.adminFunctions::makeSafe($config['config_key']).']" type="text" value="'.adminFunctions::makeSafe($config['config_value']).'" class="form-control" title="'.$titleText.'"/>';
                                        $colSize = 3;
                                        break;
                                    case 'select':
                                    case 'multiselect':
                                        $selectItems = array();
                                        $availableValues = $config['availableValues'];
                                        if(substr($availableValues, 0, 6) == 'SELECT')
                                        {
                                            $items = $db->getRows($availableValues);
                                            if($items)
                                            {
                                                foreach($items AS $item)
                                                {
                                                    $selectItems[] = $item['itemValue'];
                                                }
                                            }
                                        }
                                        else
                                        {
                                            $selectItems = json_decode($availableValues, true);
                                            if(COUNT($selectItems) == 0)
                                            {
                                                $selectItems = array('Error: Failed loading options');
                                            }
                                        }
                                        
                                        $selectedValues = explode("|", $config['config_value']);

                                        $element = '<select name="config_item['.adminFunctions::makeSafe($config['config_key']).']';
                                        if($config['config_type'] == 'multiselect')
                                        {
                                            $element .= '[]';
                                        }
                                        $element .= '" class="form-control"';
                                        if($config['config_type'] == 'multiselect')
                                        {
                                            $element .= ' MULTIPLE';
                                        }
                                        $element .= ' title="'.$titleText.'">';
                                        foreach($selectItems AS $selectItem)
                                        {
                                            $element .= '<option value="'.adminFunctions::makeSafe($selectItem).'"';
                                            if(in_array($selectItem, $selectedValues))
                                            {
                                                $element .= ' SELECTED';
                                            }
                                            $element .= '>'.adminFunctions::makeSafe($selectItem).'</option>';
                                        }
                                        $element .= '</select>';
                                        $colSize = 3;
                                        break;
                                    case 'string':
                                        $type = 'text';
                                        if((strpos($config['config_key'], 'secret') !== false) || (strpos($config['config_key'], 'password') !== false))
                                        {
                                            $type = 'password';
                                        }
                                        $element = '<input name="config_item['.adminFunctions::makeSafe($config['config_key']).']" type="'.$type.'" value="'.adminFunctions::makeSafe($config['config_value']).'" class="form-control" title="'.$titleText.'"/>';
                                        break;
                                    case 'textarea':
                                    default:
                                        $element = '<textarea name="config_item['.adminFunctions::makeSafe($config['config_key']).']" class="form-control" title="'.$titleText.'" style="min-height: 80px;">'.adminFunctions::makeSafe($config['config_value']).'</textarea>';
                                        break;
                                }
                                echo '<div class="col-md-'.$colSize.' col-sm-9 col-xs-12">';
                                echo $element;
                                echo '</div>';
                            ?>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12"></label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <p class="text-muted">
                                        <?php
                                        $description = $config['config_description'];
                                        $description = str_replace('[[[WEB_ROOT]]]', WEB_ROOT, $description);
                                        echo adminFunctions::makeSafe($description);
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php
                    }
                    ?>
                    
                    <div class="x_panel">
                        <div class="x_content">
                            
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                    <button type="button" class="btn btn-default" onClick="window.location = 'index.php?t=dashboard';">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Update Settings</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <input name="submitted" type="hidden" value="1"/>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>
