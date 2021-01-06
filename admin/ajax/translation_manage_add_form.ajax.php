<?php

// includes and security
include_once('../_local_auth.inc.php');

if(isset($_REQUEST['languageId']))
{
    $languageId = (int) $_REQUEST['languageId'];
}

// defaults
$translation_name = '';
$translation_flag = '';
$direction = 'LTR';
$language_code = '';

// is this an edit?
if($languageId)
{
    $language = $db->getRow("SELECT * FROM language WHERE id = " . (int) $languageId);
    if($language)
    {
        $translation_name = $language['languageName'];
        $translation_flag = $language['flag'];
        $direction = $language['direction'];
        $language_code = $language['language_code'];
    }
}

// load all flag icons
$flags = adminFunctions::getDirectoryList(ADMIN_ROOT . '/assets/images/icons/flags/', 'png');
sort($flags);

// load all language codes
$languageCodes = googleTranslate::getAvailableLanguages();

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = '';
$result['html'] = 'Could not load the form, please try again later.';

$result['html'] = '<p>Use the form below to add a new language. Once it\'s created, you can edit any of the text items into your preferred language.</p>';
$result['html'] .= '<form id="addTranslationForm" class="form-horizontal form-label-left input_mask">';
$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Language Name:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <input name="translation_name" id="translation_name" type="text" value="' . adminFunctions::makeSafe($translation_name) . '" class="form-control"/>
                        </div>
                    </div>';
$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Text Direction:</label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <select name="direction" id="direction" class="form-control">
                                <option value="LTR"' . ($direction == 'LTR' ? ' SELECTED' : '') . '>Left To Right (LTR)</option>
                                <option value="RTL"' . ($direction == 'RTL' ? ' SELECTED' : '') . '>Right To Left (RTL)</option>
                            </select>
                            <span class="text-muted">
                            Note: This is entirely dependant on the theme used, this setting just provides the theme with a request to show text in this direction. If the theme doesn\'t support this setting, it will be ignored.
                            </span>
                        </div>
                    </div>';
$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Language Flag:</label>
                        <div class="col-md-5 col-sm-5 col-xs-12">
                            <select name="translation_flag" id="translation_flag" class="form-control">
                                ';
foreach($flags AS $flag)
{
    $result['html'] .= '<option data-content="<img src=\'assets/images/icons/flags/' . $flag . '\'/>&nbsp;&nbsp;' . $flag . '" value="' . $flag . '"';
    if($translation_flag . '.png' == $flag)
    {
        $result['html'] .= ' SELECTED';
    }
    $result['html'] .= '>' . $flag . '</option>';
}
$result['html'] .= '
                            </select>
                        </div>
                    </div>';
$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Language Code:</label>
                        <div class="col-md-5 col-sm-5 col-xs-12">
                            <select name="language_code" id="language_code" class="form-control">
								<option value="">- select -</option>
                                ';
foreach($languageCodes AS $k => $languageCode)
{
    $result['html'] .= '<option value="' . $k . '""';
    if($language_code == $k)
    {
        $result['html'] .= ' SELECTED';
    }
    $result['html'] .= '>' . strtoupper(adminFunctions::makeSafe($k)) . ' (' . adminFunctions::makeSafe($languageCode) . ')</option>';
}
$result['html'] .= '
                            </select>
                        </div>
                    </div>';
$result['html'] .= '<input name="translation_flag_hidden" id="translation_flag_hidden" type="hidden" value="' . adminFunctions::makeSafe($translation_flag) . '.png"/>';
$result['html'] .= '</form>';

echo json_encode($result);
exit;
