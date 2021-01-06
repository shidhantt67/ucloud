<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Import Translation File');
define('ADMIN_SELECTED_PAGE', 'plugins');

// includes and security
include_once('_local_auth.inc.php');

// get languages
$languages = $db->getRows("SELECT * FROM language ORDER BY languageName");

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // pickup vars
    $languageId = (int)$_REQUEST['languageId'];
    $file = $_FILES['translation_csv'];
    
    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif (strlen($file['name']) == 0)
    {
        adminFunctions::setError(adminFunctions::t("no_file_selected", "No file selected, please try again."));
    }
    elseif (strpos(strtolower($file['name']), '.csv') === false)
    {
        adminFunctions::setError(adminFunctions::t("not_a_csv_file", "The uploaded file does not appear to be a csv file."));
    }

    // load language
    if (adminFunctions::isErrors() == false)
    {
        $language = $db->getRow("SELECT * FROM language WHERE id = ".$languageId." LIMIT 1");
        if(!$language)
        {
            adminFunctions::setError(adminFunctions::t("translation_export_failed_to_load_language", "Failed to load language, please try again."));
        }
    }
    
    // validate data
    if (adminFunctions::isErrors() == false)
    {
        $row    = 1;
        if (($handle = fopen($file['tmp_name'], "r")) !== FALSE)
        {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE)
            {
                $cols = count($data);
                if(($cols != 4) && (adminFunctions::isErrors() == false))
                {
                    adminFunctions::setError(adminFunctions::t("translation_import_csv_incorrect_columns_please_check", "Line [[[LINE]]] should have [[[COLUMNS]]] columns. Please check there's not a double quote in the text content causing the error. Any double quotes in text should be escaped with a backslash. i.e. \\\"", array('LINE'=>$row, 'COLUMNS'=>'4')));
                }
                $row++;
            }
            fclose($handle);
        }
    }
    
    // import
    if (adminFunctions::isErrors() == false)
    {
        // preload content into array for key lookup
        $languageKeyArr = array();
        $languageKeys = $db->getRows('SELECT id, languageKey FROM language_key');
        foreach($languageKeys AS $languageKey)
        {
            $languageKeyArr[$languageKey{'languageKey'}] = $languageKey['id'];
        }
        
        $row    = 1;
        if (($handle = fopen($file['tmp_name'], "r")) !== FALSE)
        {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE)
            {
                // lookup language key id for the update
                $languageKeyId = (int)$languageKeyArr[$data{0}];
                if($languageKeyId > 0)
                {
                    $newContent = $data[3];
                    
                    // update new content
                    $db->query('UPDATE language_content SET content='.$db->quote($newContent).', is_locked = 1 WHERE languageKeyId='.$languageKeyId.' AND languageId='.$languageId.' AND is_locked = 0 LIMIT 1');
                }
                
                $row++;
            }
            fclose($handle);
            
            // redirect to manage translations
            adminFunctions::redirect('translation_manage_text.html?languageId='.$languageId.'&sa=1');
        }
    }
}

// page header
include_once('_header.inc.php');
?>

<div class="right_col" role="main">
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3>Import Translations</h3>
            </div>
        </div>
        <div class="clearfix"></div>

        <?php echo adminFunctions::compileNotifications(); ?>
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <form method="POST" action="translation_manage_import.php" name="pluginForm" id="pluginForm" enctype="multipart/form-data" class="form-horizontal form-label-left">
                    <div class="x_panel">
                        <div class="x_title">
                            <h2>Import From CSV File</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <p>Use this page to import a language file into your site. The format should be CSV, layed out the same as the <a href="translation_manage_export.php">exported language</a> files.</p>
                            <p><strong>IMPORTANT:</strong> The import will <strong>NOT</strong> replace the content of any locked translations (i.e. ones you've manually edited). You can use this tool to re-import the csv file for any new translations.</p>
                            <br/>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Import Language:
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
                            
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Translations CSV:</label>
                                <div class="col-md-4 col-sm-4 col-xs-12">
                                    <input name="translation_csv" type="file" class="form-control"/>
                                </div>
                            </div>
                            
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                    <button type="submit" class="btn btn-primary">Import Data</button>
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