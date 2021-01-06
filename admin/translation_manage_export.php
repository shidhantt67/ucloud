<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Export Translations');
define('ADMIN_SELECTED_PAGE', 'plugins');

// includes and security
include_once('_local_auth.inc.php');

// get languages
$languages = $db->getRows("SELECT * FROM language ORDER BY languageName");

// handle page submissions
if(isset($_REQUEST['submitted']))
{
    // pickup vars
    $languageId = (int) $_REQUEST['languageId'];

    // load language
    $language = $db->getRow("SELECT * FROM language WHERE id = " . $languageId . " LIMIT 1");
    if(!$language)
    {
        adminFunctions::setError(adminFunctions::t("translation_export_failed_to_load_language", "Failed to load language, please try again."));
    }

    // export data
    if(adminFunctions::isErrors() == false)
    {
        // resulting csv data
        $formattedCSVData = array();

        // header
        $lArr = array();
        $lArr[] = "Language Key (do not change)";
        $lArr[] = "Is Admin Area (do not change)";
        $lArr[] = "Default Content (do not change)";
        $lArr[] = "Translation";
        $formattedCSVData[] = "\"" . implode("\",\"", $lArr) . "\"";

        // get all url data
        $translationData = $db->getRows("SELECT language_key.languageKey, language_key.defaultContent, language_key.isAdminArea, language_content.content FROM language_key LEFT JOIN language_content ON language_key.id = language_content.languageKeyId WHERE language_content.languageId = " . $languageId . " ORDER BY language_key.isAdminArea ASC, language_key.languageKey ASC");
        foreach($translationData AS $row)
        {
            $lArr = array();
            $lArr[] = str_replace("\"", "\\\"", str_replace("\\", "\\\\", $row['languageKey']));
            $lArr[] = (int) $row['isAdminArea'];
            $lArr[] = str_replace("\"", "\\\"", str_replace("\\", "\\\\", $row['defaultContent']));
            $lArr[] = str_replace("\"", "\\\"", str_replace("\\", "\\\\", $row['content']));

            $formattedCSVData[] = "\"" . implode("\",\"", $lArr) . "\"";
        }

        $outname = trim($language['languageName']) . ".csv";
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Content-type: text/plain;");
        header("Content-Transfer-Encoding: binary;");
        header("Content-Disposition: attachment; filename=\"" . $outname . "\";");

        echo implode("\n", $formattedCSVData);
        exit;
    }
}

// page header
include_once('_header.inc.php');
?>

<div class="right_col" role="main">
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3>Export Translations</h3>
            </div>
        </div>
        <div class="clearfix"></div>

        <?php echo adminFunctions::compileNotifications(); ?>
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <form method="POST" action="translation_manage_export.php" name="pluginForm" id="pluginForm" enctype="multipart/form-data" class="form-horizontal form-label-left">
                    <div class="x_panel">
                        <div class="x_title">
                            <h2>Export To CSV</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <p>Choose the language to export as a CSV file. You can amend the CSV offline and <a href="translation_manage_import.php">import</a> it back into the system later. Once exported, change the content within the 'Translation' column only.</p>
                            <br/>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="upload_server_override">Choose Language:
                                </label>
                                <div class="col-md-4 col-sm-4 col-xs-12">
                                    <select name="languageId" id="languageId" class="form-control">
                                        <?php
                                        foreach($languages AS $language)
                                        {
                                            echo '<option value="' . (int) $language['id'] . '">' . adminFunctions::makeSafe($language['languageName']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                    <button type="submit" class="btn btn-primary">Export Data (CSV)</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <input name="submitted" type="hidden" value="1"/>
                    <input name="id" type="hidden" value="<?php echo $userId; ?>"/>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>