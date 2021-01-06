<?php
define('ADMIN_PAGE_TITLE', 'Server Information');
define('ADMIN_SELECTED_PAGE', 'configuration');
include_once('_local_auth.inc.php');
include_once('_header.inc.php');
?>

<style type="text/css">
    #phpinfo pre {margin: 0px; font-family: monospace;}
    #phpinfo table {border-collapse: collapse; width:100%; font-size: 11px;}
    #phpinfo .center {text-align: center;}
    #phpinfo .center table { margin-left: auto; margin-right: auto; text-align: left; table-layout: fixed; border-collapse:collapse;}
    #phpinfo .center th { text-align: center !important; }
    #phpinfo td, th { border: 1px solid #000000; font-size: 100%; vertical-align: baseline; padding: 5px;}
    #phpinfo .p {text-align: left;}
    #phpinfo .e {background-color: #ccccff; font-weight: bold; color: #000000;}
    #phpinfo .h {background-color: #9999cc; font-weight: bold; color: #000000;}
    #phpinfo .v {background-color: #cccccc; color: #000000;}
    #phpinfo .vr {background-color: #cccccc; text-align: right; color: #000000;}
    #phpinfo img {float: right; border: 0px;}
    #phpinfo hr {width: 800px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;} 
</style>

<div class="right_col" role="main">
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3>Server Info</h3><div class="clearfix"></div>
            </div>
        </div>
        <div class="clearfix"></div>
        <?php
        if (_CONFIG_DEMO_MODE == true)
        {
            adminFunctions::setError("Viewing the server information is not permitted in demo mode.");
            echo adminFunctions::compileErrorHtml();
        }
        else
        {
            ?>
            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="x_panel">
                        <div class="x_title">
                            <h2>PHP Information</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <div id="phpinfo">
                                <?php
                                ob_start();
                                phpinfo();
                                $pinfo = ob_get_contents();
                                ob_end_clean();

                                // the name attribute "module_Zend Optimizer" of an anker-tag is not xhtml valide, so replace it with "module_Zend_Optimizer"
                                echo ( str_replace("module_Zend Optimizer", "module_Zend_Optimizer", preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $pinfo)) );
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        }
        ?>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>