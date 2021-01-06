<?php
define('ADMIN_PAGE_TITLE', 'Support Information');
define('ADMIN_SELECTED_PAGE', 'configuration');
define('ADMIN_SELECTED_SUB_PAGE', 'support_info');

include_once('_local_auth.inc.php');

// page header
include_once(ADMIN_ROOT . '/_header.inc.php');
$dt = new DateTime();
$phparr = adminFunctions::phpinfoArray();
?>
<div class="right_col" role="main">
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3>Support Information</h3><div class="clearfix"></div>
            </div>
        </div>
        <div class="clearfix"></div>
        <?php
        if(_CONFIG_DEMO_MODE == true)
        {
            adminFunctions::setError("Viewing the support information is not permitted in demo mode.");
            echo adminFunctions::compileErrorHtml();
        }
        else
        {
            ?>
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <form action="user_add.php" method="POST" class="form-horizontal form-label-left">
                        <div class="x_panel">
                            <div class="x_title">
                                <h2>Support File</h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <p>When requiring support, please click the "Download" button below and attach it to your support ticket.</p>
                                <table class="table table-data-list"><tbody>
                                    <tr>
                                        <td>Support File:</td>
                                        <td><a href="support_info_download.php" name="supportInfo" class="btn btn-primary" style="margin-bottom: 0px;">Download</button></td>
                                    </tr>                            
                                </tbody></table>
                            </div>                       
                        </div> 

                        <div class="x_panel">
                            <div class="x_title">
                                <h2>Server Information</h2><div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <table class="table table-data-list">
                                    <tbody>
                                        <tr>
                                            <td>Operating System:</td>
                                            <td><?php echo php_uname(); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Web Server:</td>
                                            <td><?php echo $_SERVER['SERVER_SIGNATURE'] ? $_SERVER['SERVER_SIGNATURE'] : $_SERVER['SERVER_SOFTWARE']; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Server Hostname:</td>
                                            <td><?php echo $_SERVER['HTTP_HOST']; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Server IP Address:</td>
                                            <td><?php echo $_SERVER['SERVER_ADDR']; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Server Time:</td>
                                            <td><?php echo $dt->format('d-m-Y H:i:s'); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Document Root:</td>
                                            <td><?php echo $_SERVER['DOCUMENT_ROOT']; ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>                        
                        </div>

                        <div class="x_panel">
                            <div class="x_title">
                                <h2>MySQL Information</h2><div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <table class="table table-data-list"><tbody>
                                    <tr>
                                        <td>MySQL Client Version:</td>
                                        <td><?php echo $phparr['mysqli']['Client API library version']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>MySQL Server Version:</td>
                                        <td><?php echo $db->getValue("SELECT version();"); ?></td>
                                    </tr>
                                    <tr>
                                        <td>MySQL Server Time:</td>
                                        <td><?php echo $db->getValue('SELECT NOW();'); ?></td>
                                    </tr>
                                    <tr>
                                        <td>PDO Installed:</td>
                                        <td><?php echo $phparr['PDO']['PDO drivers']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>PDO Version:</td>
                                        <td><?php echo $phparr['pdo_mysql']['Client API version']; ?></td>
                                    </tr>
                                </tbody></table>
                            </div>                        
                        </div>  
                        <div class="x_panel">
                            <div class="x_title">
                                <h2>PHP Information</h2><div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <table class="table table-data-list"><tbody>
                                    <tr>
                                        <td>PHP Version:</td>
                                        <td><?php echo phpversion(); ?></td>
                                    </tr>
                                    <tr>
                                        <td>php.ini Location:</td>
                                        <td><?php echo php_ini_loaded_file(); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Current PHP Time:</td>
                                        <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Max Execution Time:</td>
                                        <td><?php echo $phparr['Core']['max_execution_time']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Max Input Time:</td>
                                        <td><?php echo $phparr['Core']['max_input_time']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Memory Limit:</td>
                                        <td><?php echo $phparr['Core']['memory_limit']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Post Max Size:</td>
                                        <td><?php echo $phparr['Core']['post_max_size']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Upload Max Filesize:</td>
                                        <td><?php echo $phparr['Core']['upload_max_filesize']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>cURL Enabled:</td>
                                        <td><?php echo ucfirst($phparr['curl']['cURL support']); ?></td>
                                    </tr>
                                    <tr>
                                        <td>cURL Version:</td>
                                        <td><?php echo $phparr['curl']['cURL Information']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Default Timezone:</td>
                                        <td><?php echo $phparr['date']['Default timezone']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>GD Enabled:</td>
                                        <td><?php echo ucfirst($phparr['gd']['GD Support']); ?></td>
                                    </tr>
                                    <tr>
                                        <td>OpenSSL Details:</td>
                                        <td><?php echo print_r($phparr['openssl'], true); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Default Timezone:</td>
                                        <td><?php echo $phparr['date']['Default timezone']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Loaded Extensions:</td>
                                        <td>
                                            <select name="" id="" class="form-control" MULTIPLE>
                                                <?php
                                                foreach(get_loaded_extensions() AS $e)
                                                {
                                                    echo '<option value="">' . $e . '</option>';
                                                }
                                                ?>
                                            </select>                                    
                                        </td>
                                    </tr>                              
                                </tbody></table>
                            </div> 
                        </div>
                        <?php
                    }
                    ?>
            </div>
        </div>   
    </div>
</div>
<?php
include_once(ADMIN_ROOT . '/_footer.inc.php');
?>