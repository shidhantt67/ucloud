<?php

// includes and security
include_once('../_local_auth.inc.php');

$gTranslationId   = (int) $_REQUEST['gTranslationId'];

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = '';
$result['html'] = 'Could not load the form, please try again later.';

// load existing translation
$translation   = $db->getRow("SELECT language_content.id, language_content.content, language_key.languageKey, language_key.defaultContent, language.language_code FROM language_content LEFT JOIN language_key ON language_content.languageKeyId = language_key.id LEFT JOIN language ON language_content.languageId = language.id WHERE language_content.id = ".(int)$gTranslationId);
if(!$translation)
{
    $result['error'] = false;
    $result['msg'] = 'There was a problem loading the translation for editing, please try again later.';
}

$result['html']  = '<p style="padding-bottom: 4px;">Use the form below to update the translation.</p>';
$result['html'] .= '<span id="popupMessageContainer"></span>';
$result['html'] .= '<form id="updateConfigurationForm" class="form-horizontal form-label-left input_mask">';
$result['html'] .= '<table class="table table-data-list">';
$result['html'] .= '<tr>
                        <td>Key:</td>
                        <td>'.adminFunctions::makeSafe($translation['languageKey']).'</td>
                    </tr>';
$result['html'] .= '<tr>
                        <td>Original English Text:</td>
                        <td>'.adminFunctions::makeSafe($translation['defaultContent']).'</td>
                        <input id="enTranslationText" type="text" value="'.adminFunctions::makeSafe($translation['defaultContent']).'" style="display: none;"/>
                        <input id="enTranslationCode" type="text" value="'.adminFunctions::makeSafe($translation['language_code']).'" style="display: none;"/>
                    </tr>';
$result['html'] .= '</table>';

$result['html'] .= '<div class="form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Translated Text: <a href="#" onClick="processAutoTranslate(); return false;" style="text-decoration: underline;">(auto)</a></label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <textarea name="translated_content" id="translated_content" class="form-control" style="height: 80px;">'.  adminFunctions::makeSafe($translation['content']).'</textarea>
                        </div>
                    </div>';
$result['html'] .= '<input id="translation_item_id" name="translation_item_id" value="'.$gTranslationId.'" type="hidden"/>';
$result['html'] .= '</form>';

echo json_encode($result);
exit;
