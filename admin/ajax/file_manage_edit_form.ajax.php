<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

// mnake sure we have the file to edit
if (!isset($_REQUEST['gEditFileId']))
{
    $result          = array();
    $result['error'] = true;
    $result['msg']   = 'File not found.';
    echo json_encode($result);
    exit;
}

// load file
$file = file::loadById((int)$_REQUEST['gEditFileId']);
if (!$file)
{
    $result          = array();
    $result['error'] = true;
    $result['msg']   = 'File not found.';
    echo json_encode($result);
    exit;
}

// load file server
$fileServer = $file->loadFileServer();

// load all user types
$userTypes = $db->getRows('SELECT id, label FROM user_level ORDER BY id ASC');

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

$result['html'] = '';
$result['html'] .= '
        <form id="editFileFormInner" class="form-horizontal form-label-left input_mask">
            <div class="x_panel">
                <div class="x_content">
                    <div class="" role="tabpanel" data-example-id="togglable-tabs">
                      <ul id="myTab" class="nav nav-tabs bar_tabs" role="tablist">
                        <li role="presentation" class="active"><a href="#tab_content1" id="home-tab" role="tab" data-toggle="tab" aria-expanded="true">File Details</a>
                        </li>
                        <li role="presentation" class=""><a href="#tab_content2" role="tab" id="profile-tab" data-toggle="tab" aria-expanded="false">Access Restrictions</a>
                        </li>
                        <li role="presentation" class=""><a href="#tab_content3" role="tab" id="profile-tab2" data-toggle="tab" aria-expanded="false">Other Options</a>
                        </li>
                        <li role="presentation" class=""><a href="#tab_content4" role="tab" id="profile-tab3" data-toggle="tab" aria-expanded="false">File Info</a>
                        </li>
                      </ul>
                      <div id="myTabContent" class="tab-content">
                        <div role="tabpanel" class="tab-pane fade active in" id="tab_content1" aria-labelledby="home-tab">
                            <div class="x_title">
                                <h2>File Details:</h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="form-group">
                              <label class="control-label col-md-3 col-sm-3 col-xs-12">
                                  Filename:
                              </label>
                              <div class="col-md-9 col-sm-9 col-xs-12">
                                  <div class="input-group">
                                      <input name="filename" id="filename" type="text" class="form-control" placeholder="Filename" value="' . validation::safeOutputToScreen($file->getFilenameExcExtension()) . '"/>'.
                                      (strlen($file->extension)?('<span class="input-group-addon">.'.validation::safeOutputToScreen($file->extension). '</span>'):'').
                                  '</div>
                              </div>
                            </div>
                            <div class="form-group">
                              <label class="control-label col-md-3 col-sm-3 col-xs-12">
                                  File Owner:
                              </label>
                              <div class="col-md-4 col-sm-4 col-xs-12">
                                  <input name="file_owner" id="file_owner" type="text" class="form-control" placeholder="File Owner" value="' . validation::safeOutputToScreen($file->getOwnerUsername()) . '"/>
                              </div>
                            </div>
                            <div class="form-group">
                              <label class="control-label col-md-3 col-sm-3 col-xs-12">
                                  Short Url:
                              </label>
                              <div class="col-md-4 col-sm-4 col-xs-12">
                                  <input name="short_url" id="short_url" type="text" class="form-control" placeholder="Short Url" value="' . validation::safeOutputToScreen($file->shortUrl) . '"/>
                              </div>
                              <div class="col-md-5 col-sm-4 col-xs-12">
                                  <p class="right-comment">The download url, no spacing, alphanumeric only</p>
                              </div>
                            </div>
                            <div class="form-group">
                              <label class="control-label col-md-3 col-sm-3 col-xs-12">
                                  File Description:
                              </label>
                              <div class="col-md-9 col-sm-9 col-xs-12">
                                  <textarea name="file_description" id="file_description" class="form-control" placeholder="File Description">' . validation::safeOutputToScreen($file->description) . '</textarea>
                              </div>
                            </div>
                            <div class="form-group">
                              <label class="control-label col-md-3 col-sm-3 col-xs-12">
                                  File Keywords:
                              </label>
                              <div class="col-md-9 col-sm-9 col-xs-12">
                                  <textarea name="file_keywords" id="file_keywords" class="tags form-control" placeholder="File Keywords">' . validation::safeOutputToScreen($file->keywords) . '</textarea>
                              </div>
                            </div>
                        </div>
                        
                        <div role="tabpanel" class="tab-pane fade" id="tab_content2" aria-labelledby="profile-tab">
                          <div class="x_title">
                            <h2>Access Restrictions:</h2>
                            <div class="clearfix"></div>
                          </div>
                            <div class="form-group">
                              <label class="control-label col-md-3 col-sm-3 col-xs-12">
                                  Access Password:
                              </label>
                              <div class="col-md-9 col-sm-9 col-xs-12">
                                  <div class="input-group">
                                      <span class="input-group-btn"><input type="checkbox" name="enablePassword" id="enablePassword" value="1" '.(strlen($file->accessPassword)?'CHECKED':'').' onClick="toggleFilePasswordField();"></span>
                                      <input name="password" id="password" type="password" class="form-control" placeholder="Access Password" autocomplete="off"'.(strlen($file->accessPassword)?' value="**********"':'').(strlen($file->accessPassword)?'':'READONLY').'/>

                                  </div>
                              </div>
                            </div>
                            <div class="form-group">
                              <label class="control-label col-md-3 col-sm-3 col-xs-12">
                                  Minimum User Level:
                              </label>
                              <div class="col-md-4 col-sm-4 col-xs-12">
                                  <select name="min_user_level" id="min_user_level" class="form-control">
                                      <option value="">- any user type can download this file -</option>';
                                      foreach($userTypes AS $userType)
                                      {
                                          $result['html'] .= '<option value="'.validation::safeOutputToScreen($userType['id']).'"';
                                          if($userType['id'] == $file->minUserLevel)
                                          {
                                              $result['html'] .= ' SELECTED';
                                          }
                                          $result['html'] .= '>>= '.validation::safeOutputToScreen(UCWords($userType['label'])).'</option>';
                                      }
      $result['html'] .= '        </select>
                              </div>
                              <div class="col-md-5 col-sm-4 col-xs-12">
                                  <p class="right-comment">To download this file. (exc the uploader)</p>
                              </div>
                            </div>
                            
                            <div class="form-group">
                              <label class="control-label col-md-3 col-sm-3 col-xs-12">
                                  File Privacy:
                              </label>
                              <div class="col-md-4 col-sm-4 col-xs-12">
                                  <select name="is_public" id="is_public" class="form-control">';
                                      $privacyTypes = array(0 => 'Private', 1 => 'Public');
                                      foreach($privacyTypes AS $k=>$privacyType)
                                      {
                                          $result['html'] .= '<option value="'.$k.'"';
                                          if($k == (int)$file->isPublic)
                                          {
                                              $result['html'] .= ' SELECTED';
                                          }
                                          $result['html'] .= '>'.$privacyType.'</option>';
                                      }
      $result['html'] .= '        </select>
                              </div>
                              <div class="col-md-5 col-sm-4 col-xs-12">
                                  <p class="right-comment">If supported by the theme.</p>
                              </div>
                            </div>
                        </div>
                        
                        <div role="tabpanel" class="tab-pane fade" id="tab_content3" aria-labelledby="profile-tab2">
                            <div class="x_title">
                                <h2>Other Options</h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="form-group">
                              <label class="control-label col-md-3 col-sm-3 col-xs-12">
                                  Mime Type:
                              </label>
                              <div class="col-md-4 col-sm-4 col-xs-12">
                                  <input name="mime_type" id="mime_type" type="text" class="form-control" placeholder="Mime Type" value="' . validation::safeOutputToScreen($file->fileType) . '"/>
                              </div>
                            </div>
                            <div class="form-group">
                              <label class="control-label col-md-3 col-sm-3 col-xs-12">
                                  Admin Notes:
                              </label>
                              <div class="col-md-9 col-sm-9 col-xs-12">
                                  <textarea name="admin_notes" id="admin_notes" class="form-control" placeholder="">' . validation::safeOutputToScreen($file->adminNotes) . '</textarea>
                              </div>
                            </div>
                        </div>
                        
                        <div role="tabpanel" class="tab-pane fade" id="tab_content4" aria-labelledby="profile-tab3">
                          <div class="x_title">
                            <h2>File Info:</h2>
                            <div class="clearfix"></div>
                          </div>
                          <div class="x_content">
                            <table class="table table-data-list" style="margin-top: 0px;">
                              <tr>
                                <td style="width: 110px;">
                                    Download Url:
                                </td>
                                <td>
                                    ' . validation::safeOutputToScreen($file->getFullShortUrl()) . '
                                </td>
                              </tr>
                              <tr>
                                <td>
                                    Storage Server:
                                </td>
                                <td>
                                    '.validation::safeOutputToScreen($fileServer['serverLabel']).'
                                </td>
                              </tr>
                              <tr>
                                <td>
                                    Real File Path:
                                </td>
                                <td>
                                    ' . validation::safeOutputToScreen($file->getLocalFilePath()) . '
                                </td>
                              </tr>
                              <tr>
                                <td>
                                    Last Downloaded:
                                </td>
                                <td>
                                    ' . ($file->lastAccessed==NULL?'Never':validation::safeOutputToScreen(coreFunctions::formatDate($file->lastAccessed))) . '
                                </td>
                              </tr>
                              <tr>
                                <td>
                                    Upload Source:
                                </td>
                                <td>
                                    ' . validation::safeOutputToScreen(UCWords($file->uploadSource)) . '
                                </td>
                              </tr>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <input type="hidden" name="existing_file_id" id="existing_file_id" value="'.(int)$file->id.'"/>
            </form>';

echo json_encode($result);
exit;
