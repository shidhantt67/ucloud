<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'API Documentation');
define('ADMIN_SELECTED_PAGE', 'api');

// includes and security
include_once('_local_auth.inc.php');

// page header
include_once('_header.inc.php');
?>

<!-- page content -->
<div class="right_col" role="main">
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3><?php echo ADMIN_PAGE_TITLE; ?></h3>
            </div>
        </div>
        <div class="clearfix"></div>

        <?php echo adminFunctions::compileNotifications(); ?>

        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">

                    <div class="x_content api_wrapper">
                        <div class="api_left">
                            <div class="x_title">
                                <h2>File Upload API</h2>
                                <div class="clearfix"></div>
                            </div>
                            <ul>
                                <li><a href="#overview">Overview</a></li>
                                <li><a href="#error-handling">Error Handling</a></li>
                            </ul>
                            <h4>Authentication</h4>
                            <ul>
                                <li><a href="#authorize">/authorize</a></li>
                                <li><a href="#disable_access_token">/disable_access_token</a></li>
                            </ul>
                            <h4>User Accounts</h4>
                            <ul>
                                <li><a href="#account-info">/account/info</a></li>
                                <li><a href="#account-package">/account/package</a></li>
                                <li><a href="#account-create">/account/create</a> *</li>
                                <li><a href="#account-edit">/account/edit</a> *</li>
                                <li><a href="#account-delete">/account/delete</a> *</li>
                            </ul>
                            <h4>Files</h4>
                            <ul>
                                <li><a href="#file-upload">/file/upload</a></li>
                                <li><a href="#file-download">/file/download</a></li>
                                <li><a href="#file-info">/file/info</a></li>
                                <li><a href="#file-edit">/file/edit</a></li>
                                <li><a href="#file-delete">/file/delete</a></li>
                                <li><a href="#file-move">/file/move</a></li>
                                <li><a href="#file-copy">/file/copy</a></li>
                            </ul>
                            <h4>Folders</h4>
                            <ul>
                                <li><a href="#folder-create">/folder/create</a></li>
                                <li><a href="#folder-listing">/folder/listing</a></li>
                                <li><a href="#folder-info">/folder/info</a></li>
                                <li><a href="#folder-edit">/folder/edit</a></li>
                                <li><a href="#folder-delete">/folder/delete</a></li>
                                <li><a href="#folder-move">/folder/move</a></li>
                            </ul>
                            <h4>Packages</h4>
                            <ul>
                                <li><a href="#package-listing">/package/listing</a> *</li>
                            </ul>
                            <p><br/><i>* Admin Access Only</i></p>
                        </div>

                        <div class="api_right">
                            <div id="page-content">
                                <div id="right-content">
                                    <div id="api-specification" class="section">
                                        <div class="x_title">
                                            <h2 style='margin-top: 5px;'>Overview</h2>
                                            <div class="clearfix"></div>
                                        </div>
                                        <p>The file upload API is an interface which can be used in your own applications to securely upload, manage and download files externally from this website. It can be set for use just by admin accounts, or you can provide the functionality to the rest of your users via the <a href="<?php echo ADMIN_WEB_ROOT; ?>/setting_manage.php?filterByGroup=API">API settings</a>.</p>
                                        <h4>API compatibility</h4>
                                        <p>This API will evolve over time when access to other data within the system is made available. The plugin architecture will also be integrated to enable access to specific functionality within say the rewards plugin. However, none of the current endpoints or response values will change. You can write your integration code knowing that it will now be affected by future updates.</p>                                        
                                        <h4>SSL/HTTPS recommended</h4>
                                        <p>We recommend SSL is used for all requests. You can require SSL by forcing it via your web server.</p>
                                        <h4>UTF-8 encoding</h4>
                                        <p>Every string passed to and from the API needs to be UTF-8 encoded.</p>
                                        <h4>HTTP Method</h4>
                                        <p>All methods are done using POST unless otherwise stated.</p>
                                        <div id="date-format">
                                            <h4>Date format</h4>
                                            <p>All date/times in the API are strings in the following format:</p>
                                            <pre class="literal-block">Y-m-d H:i:s</pre>
                                            <p>For example:</p>
                                            <pre class="literal-block"><?php echo date('Y-m-d H:i:s'); ?></pre>
                                        </div>
                                        <div id="api-server-domains" class="toc-el">
                                            <h4>API Path</h4>
                                            <p>All requests are sent to the follow path: (you can change this via the <a href="<?php echo ADMIN_WEB_ROOT; ?>/setting_manage.php?filterByGroup=API">API settings</a>)</p>
                                            <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?></pre>
                                        </div>
                                    </div>
                                    
                                    <div id="error-handling" class="section">
                                        <div class="x_title">
                                            <h2>Error handling</h2>
                                            <div class="clearfix"></div>
                                        </div>
                                        <div class="section toc-el">
                                            <p>Errors are returned using standard HTTP error code syntax. Any additional information is included in the body of the return call, JSON-formatted.</p>
                                            <h2>Standard API errors</h2>
                                            <table class="table table-data-list">
                                                <tr>
                                                    <td>Error Code:</td>
                                                    <td>Description:</td>
                                                </tr>
                                                <tr>
                                                    <td>400</td>
                                                    <td>Bad input parameter. Response error should show which one and why.</td>
                                                </tr>
                                                <tr>
                                                    <td>401</td>
                                                    <td>Bad or expired token. To fix, you should re-authenticate the user.</td>
                                                </tr>
                                                <tr>
                                                    <td>404</td>
                                                    <td>File not found at the provided path.</td>
                                                </tr>
                                                <tr>
                                                    <td>405</td>
                                                    <td>Request method not expected (generally should be GET or POST).</td>
                                                </tr>
                                                <tr>
                                                    <td>429</td>
                                                    <td>Maximum API request limit reached. Try to reduce the amount of requests or look at raising this via your web server configuration.</td>
                                                </tr>
                                                <tr>
                                                    <td>5xx</td>
                                                    <td>Server error. See <a href="https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#5xx_Server_Error" target='_blank'>full list here</a>.</td>
                                                </tr>
                                            </table>
                                            <p><strong>Sample Error JSON Response</strong></p>
                                            <pre class="literal-block">{
	"status": "error",
	"response": "Could not authenticate user. The username and password may be invalid or your account may be locked from too many failed logins.",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                        </div>
                                    </div>
                                    
                                    <div class="section">
                                        <div class="x_title">
                                            <h2>Authentication</h2>
                                            <div class="clearfix"></div>
                                        </div>
                                        <div class="section toc-el">
                                            <p>All requests to the API must be done with a valid access_token and account_id. 
                                            <?php if(SITE_CONFIG_API_AUTHENTICATION_METHOD == 'Account Access Details'): ?>
                                            These can be obtained by submitting your account username and password to the authorize endpoint. 
                                            <?php else: ?>
                                            These can be obtained by submitting your API keys to the authorize endpoint. 
                                            <?php endif; ?> The same access_token can be used multiple times in the same session, so you shouldn't generate a new access_token for each request.</p>
                                            <h4>API Flow</h4>
                                            <p>Your external application should make API requests in the following order:</p>
                                            <ul style="margin-left:35px;" class="parameters">
                                                <li>Request an access_token and account_id <a href="#authorize"><code>/authorize</code></a>.</li>
                                                <li>
                                                    <p><strong>Example Response:</strong></p>
                                                    <pre class="literal-block">{
	"data": {
		"access_token": "lGoVSof0VRwq1Gaza8fODfIxQ4pu6j6rZvnRUCRPunfw4q5ezk3dALLqQbUWu1ntxKkrnbgSzwoDWtSwOVJoHuPxFKt9LRCjCXK081SIxgmuJe1y9KXQfMoVwS4iJHBm",
		"account_id": "158642"
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                </li>
                                                <li>Request to the core API, such as getting user account details <a href="#account-info"><code>/account/info</code></a>.</li>
                                                <li>Make further API requests at any stage using the same access_token.</li>
                                                <li>Once you've completed your requests you can clear the access_token. This is optional as these are also automatically cleared after 1 hour of no activity <a href="#disable_access_token"><code>/disable_access_token</code></a>.</li>
                                                <li><strong>Important:</strong> On each request you should check an error response. If the request resulted in an error, the "_status" will be "error" with more information in the "response" parameter.</li>
                                            </ul>


                                            <div id="authorize" class="section toc-el api-item-section">
                                                <h4>/authorize</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Provides an access_token and account_id to make further requests into the API.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>authorize</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <?php if(SITE_CONFIG_API_AUTHENTICATION_METHOD == 'Account Access Details'): ?>
                                                            <li><span class="param">username</span> The account username, as used on the site login.</li>
                                                            <li><span class="param">password</span> The account password, as used on the site login.</li>
                                                            <?php else: ?>
                                                            <li><span class="param">key1</span> The API key 1. Expected 64 characters in length.</li>
                                                            <li><span class="param">key2</span> The API key 2. Expected 64 characters in length.</li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string including an access token (<code>access_token</code>) and account id (<code>account_id</code>).</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"data": {
		"access_token": "X3Xp6cUcue22Q3AlpCiZz3mJQWPT2v10zZqGblSGzVIqZiMoV4ou8LeYH4SKAUL9TcP5xIL7DtxDMj2HoqcwbrvTvoD5ioebA4h7M2fqwM3i650vwc1IExB9VffeDtqe",
		"account_id": "158642"
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <?php if(SITE_CONFIG_API_AUTHENTICATION_METHOD == 'Account Access Details'): ?>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide a username.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide a password.</td>
                                                            </tr>
                                                            <?php else: ?>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide key1. It must be 64 characters in length.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide key2. It must be 64 characters in length.</td>
                                                            </tr>
                                                            <?php endif; ?>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not authenticate user. The username and password may be invalid or your account may be locked from too many failed logins..</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Failed issuing access token.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>

                                            <div id="disable_access_token" class="section toc-el api-item-section">
                                                <h4>/disable_access_token</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Disables an active access_token.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>disable_access_token</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id returned by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string with the response message.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"response": "Token removed or no longer available.",
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the access_token param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="section">
                                        <div class="section toc-el">
                                            <div id="account-info" class="section toc-el api-item-section">
                                                <h4>/account/info</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Provides details of an account based on the account_id.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>account/info</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id returned by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string including a username (<code>username</code>), account level id (<code>level_id</code>), email address (<code>email</code>) and more.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"data": {
		"id": "158642",
		"username": "admin",
		"level_id": "20",
		"email": "email@yoursite.com",
		"lastlogindate": "2017-02-18 11:43:39",
		"lastloginip": "192.168.33.1",
		"status": "active",
		"title": "Mr",
		"firstname": "Admin",
		"lastname": "User",
		"languageId": "1",
		"datecreated": null,
		"lastPayment": "2011-12-27 13:45:22",
		"paidExpiryDate": "0000-00-00 00:00:00",
		"storageLimitOverride": null
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the access_token param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div id="account-package" class="section toc-el api-item-section">
                                                <h4>/account/package</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Provides the account restrictions inherited from the package associated to the account.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>account/package</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id returned by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string including a label (<code>label</code>), max upload size (<code>max_upload_size</code>) and more.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"data": {
		"id": "20",
		"label": "Premium Account",
		"max_upload_size": "1073741824",
		"can_upload": "1",
		"wait_between_downloads": "0",
		"download_speed": "0",
		"max_storage_bytes": "0",
		"show_site_adverts": "0",
		"show_upgrade_screen": "1",
		"days_to_keep_inactive_files": "0",
		"concurrent_uploads": "100",
		"concurrent_downloads": "0",
		"downloads_per_24_hours": "0",
		"max_download_filesize_allowed": "0",
		"max_remote_download_urls": "50",
		"level_type": "paid user",
		"on_upgrade_page": "0"
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the access_token param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div id="account-create" class="section toc-el api-item-section">
                                                <h4>/account/create</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Create a new account.</p>
                                                        <p>Note: This endpoint is available to admin only API users.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>account/create</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">username</span> The new account username. No spaces.</li>
                                                            <li><span class="param">password</span> The new account password. No spaces, min 8 characters, subject to password policy.</li>
                                                            <li><span class="param">email</span> The account email address.</li>
                                                            <li><span class="param">package_id</span> The package id. Chosen from <a href="#package-listing"><code>/package/listing</code></a>.</li>
                                                            <li><span class="param">status</span> The account status. Chosen from 'pending', 'active', 'disabled' or 'suspended'. Default 'active'.</li>
                                                            <li><span class="param">title</span> Account owner title. Chosen from 'Mr', 'Ms', 'Mrs', 'Miss' or 'Dr'.</li>
                                                            <li><span class="param">firstname</span> Account owner first name.</li>
                                                            <li><span class="param">lastname</span> Account owner last name.</li>
                                                            <li><span class="param">paid_expiry_date</span> The paid expiry date/time, if this is a premium account. In the format YYYY-MM-DD HH:MM:SS. i.e. <?php echo date('Y-m-d H:i:s'); ?></li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string with the response message.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"response": "User successfully created.",
	"data": {
		"id": "24",
		"username": "accounttest",
		"level_id": "2",
		"email": "someone@gmail.com",
		"lastlogindate": null,
		"lastloginip": null,
		"status": "active",
		"title": "Mr",
		"firstname": "Bob",
		"lastname": "Test",
		"languageId": null,
		"datecreated": "2018-01-21 16:40:33",
		"lastPayment": null,
		"paidExpiryDate": null,
		"storageLimitOverride": null
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the username param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Email address already exists on another account.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token, please reauthenticate or try again.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div id="account-edit" class="section toc-el api-item-section">
                                                <h4>/account/edit</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Edit an existing account.</p>
                                                        <p>Note: This endpoint is available to admin only API users.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>account/edit</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id to edit (admin or moderator accounts can not be edited via the API).</li>
                                                            <li><span class="param">password</span> Update the account password. No spaces, min 8 characters, subject to password policy.</li>
                                                            <li><span class="param">email</span> The account email address.</li>
                                                            <li><span class="param">package_id</span> The package id. Chosen from <a href="#package-listing"><code>/package/listing</code></a>.</li>
                                                            <li><span class="param">status</span> The account status. Chosen from 'pending', 'active', 'disabled' or 'suspended'. Default 'active'.</li>
                                                            <li><span class="param">title</span> Account owner title. Chosen from 'Mr', 'Ms', 'Mrs', 'Miss' or 'Dr'.</li>
                                                            <li><span class="param">firstname</span> Account owner first name.</li>
                                                            <li><span class="param">lastname</span> Account owner last name.</li>
                                                            <li><span class="param">paid_expiry_date</span> The paid expiry date/time, if this is a premium account. In the format YYYY-MM-DD HH:MM:SS. i.e. <?php echo date('Y-m-d H:i:s'); ?></li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string with the response message.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"response": "User successfully updated.",
	"data": {
		"id": "24",
		"username": "accounttest",
		"level_id": "2",
		"email": "someone@gmail.com",
		"lastlogindate": null,
		"lastloginip": null,
		"status": "active",
		"title": "Mr",
		"firstname": "Bob",
		"lastname": "Test",
		"languageId": null,
		"datecreated": "2018-01-21 16:40:33",
		"lastPayment": null,
		"paidExpiryDate": null,
		"storageLimitOverride": null
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Email address already exists on another account.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token, please reauthenticate or try again.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div id="account-delete" class="section toc-el api-item-section">
                                                <h4>/account/delete</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Delete an active account, including all folders and files.</p>
                                                        <p>Note: This endpoint is available to admin only API users.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>file/account</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id to delete.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string with the response message.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"response": "Account successfully set as deleted.",
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                        </div>
                                    </div>
                                    
                                    <div class="section">
                                        <div class="section toc-el">
                                            <div id="file-upload" class="section toc-el api-item-section">
                                                <h4>/file/upload</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Provides an interface to upload files. Note: There is currently no support for chunked uploads, this will be added at a later stage.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>file/upload</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id returned by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">upload_file</span> The uploaded file.</li>
                                                            <li><span class="param">folder_id</span> A folder id within the users account. If left blank the file will be added to the root folder.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string with the response message.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"response": "File uploaded",
	"data": [
		{
			"name": "sample4_l.jpg",
			"size": "149084",
			"type": "application/octet-stream",
			"error": null,
			"url": "http://yoursite.com/2Vv",
			"delete_url": "http://yoursite.com/2Vv~d?41efa710444abad11a8f4b5a90e4d746",
			"info_url": "http://yoursite.com/2Vv~i?41efa710444abad11a8f4b5a90e4d746",
			"delete_type": "DELETE",
			"delete_hash": "41efa710444abad11a8f4b5a90e4d746",
			"hash": "2f4105bc2c626232544275c2d890168b",
			"stats_url": "http://yoursite.com/2Vv~s",
			"short_url": "2Vv",
			"file_id": "1253",
			"unique_hash": "60b0be7e3b18de9a3f00d940a8e5a9834c6cdc0f49d40af64973be5ca504c4fd",
			"url_html": "&lt;a href=&quot;http://yoursite.com/2Vv&quot; target=&quot;_blank&quot; title=&quot;View image on File Upload Script&quot;&gt;view sample4_l (1).jpg on File Upload Script&lt;/a&gt;",
			"url_bbcode": "[url]http://yoursite.com/2Vv[/url]"
		}
	],
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the access_token param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Did not receive uploaded file.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Filesize received was zero.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>PHP Curl module does not exist on your server/web hosting. It will need to be enable to use this upload feature.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Error uploading file. No response received from: </td>
                                                            </tr>
                                                        </table>
                                                        <p>Note: Account upload restrictions are still in place, so you may also receive the same errors as shown on the site uploader.</p>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div id="file-download" class="section toc-el api-item-section">
                                                <h4>/file/download</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Generates a unique download url for a file.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>file/download</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id returned by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">file_id</span> The file id to generate the download url for.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string with the response message.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"data": {
		"file_id": "1253",
		"filename": "sample4_l.jpg",
		"download_url": "http://yoursite.com/2Vv?download_token=c3e6289a23e9819d8663569da96087d0760ccc46d0f3ddbe3f6930b261777067"
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the file_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not find file based on file_id.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not generate download url.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div id="file-info" class="section toc-el api-item-section">
                                                <h4>/file/info</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Provides meta data and urls of a file within a users account.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>file/info</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id returned by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">file_id</span> The file id to get information on.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string with the response message.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"data": {
		"id": "1253",
		"filename": "sample4_l.jpg",
		"shortUrl": "2Vv",
		...
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the file_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not find file based on file_id.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div id="file-edit" class="section toc-el api-item-section">
                                                <h4>/file/edit</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Provides meta data and urls of a file within a users account.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>file/edit</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id returned by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">file_id</span> The file id to update.</li>
                                                            <li><span class="param">filename</span> The new filename. Leave blank to keep existing.</li>
                                                            <li><span class="param">fileType</span> The new file type/mime type. Example: application/octet-stream. Leave blank to keep existing.</li>
                                                            <li><span class="param">folder_id</span> The new folder id in the users account. Leave blank to keep existing.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string with the response message.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"response": "File successfully updated.",
	"data": {
		"id": "1253",
		"filename": "sample4.jpg",
		"shortUrl": "2Vv",
		...
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the file_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not find file based on file_id.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div id="file-delete" class="section toc-el api-item-section">
                                                <h4>/file/delete</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Delete an active file.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>file/delete</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id returned by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">file_id</span> The file id to delete.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string with the response message.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"response": "File successfully set as deleted.",
	"data": {
		"id": "1253",
		"filename": "sample4.jpg",
		"shortUrl": "2Vv",
		"status": "trash",
		...
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the file_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not find file based on file_id.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div id="file-move" class="section toc-el api-item-section">
                                                <h4>/file/move</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Move an active file to another folder.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>file/move</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id returned by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">file_id</span> The file id to move.</li>
                                                            <li><span class="param">new_parent_folder_id</span> The folder id to move the file into.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string with the response message.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"response": "File successfully moved.",
	"data": {
		"id": "1162",
		"filename": "button_back_over.gif",
		"shortUrl": "2U2",
		...
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the file_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not find file based on file_id.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the new_parent_folder_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not find the destination folder id defined by new_parent_folder_id.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div id="file-copy" class="section toc-el api-item-section">
                                                <h4>/file/copy</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Copy an active file to another folder.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>file/copy</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id returned by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">file_id</span> The file id to copy.</li>
                                                            <li><span class="param">copy_to_folder_id</span> The folder id to copy the file into.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string with the response message.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"response": "File successfully copyied.",
	"original_file": {
		"data": {
			"id": "1162",
			"filename": "button_back_over.gif",
			"shortUrl": "2U2",
			...
		}
	},
	"new_file": {
		"data": {
			"id": "1254",
			"filename": "button_back_over.gif",
			"shortUrl": "2Vw",
			...
		}
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the file_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not find file based on file_id.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the copy_to_folder_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not find the destination folder id defined by copy_to_folder_id.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div id="folder-create" class="section toc-el api-item-section">
                                                <h4>/folder/create</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Create a new folder.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>folder/create</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id returned by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">folder_name</span> The new folder name.</li>
                                                            <li><span class="param">parent_id</span> The folder parent id. Optional.</li>
                                                            <li><span class="param">is_public</span> Whether a folder is available publicly or private only. 0 = Private, 1 = Unlisted, 2 = Public in site search. Default Private.</li>
                                                            <li><span class="param">access_password</span> An MD5 hash of an access password. Expects 32 characters in length. Optional.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string with the response message.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"response": "Folder successfully created.",
	"data": {
		"id": "125",
		"parentId": null,
		"folderName": "My New Folder",
		"totalSize": "0",
		"isPublic": "2",
		"accessPassword": "d9729feb74992cc3482b350163a1a010",
		"date_added": "2017-02-18 12:26:06",
		"date_updated": null,
		"url_folder": "http://yoursite.com/folder/125/My_New_Folder",
		"total_downloads": 0,
		"child_folder_count": 0,
		"file_count": 0
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the folder_name param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div id="folder-listing" class="section toc-el api-item-section">
                                                <h4>/folder/listing</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Returns a list of folders and files within the passed parent_folder_id. If this value is blank the root folder/file listing is returned.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>folder/listing</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id returned by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">parent_folder_id</span> The folder parent id. Optional.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string with the response message.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"data": {
		"folders": [
			{
				"id": "123",
				"parentId": null,
				"folderName": "My Folder 1",
				"totalSize": "868689",
				"isPublic": "1",
				"date_added": "2017-02-15 20:02:05",
				"date_updated": null,
				"url_folder": "http://yoursite.com/folder/123/My_Folder_1",
				"total_downloads": 5864,
				"child_folder_count": 1,
				"file_count": 0
			},
			{
				"id": "107",
				"parentId": null,
				"folderName": "My Folder 2",
				...
			},
			{
				"id": "108",
				"parentId": null,
				"folderName": "My Folder 3",
				....
			}
		],
		"files": [
			{
				"id": "1161",
				"filename": "button_back.gif",
				"shortUrl": "2U1",
				"fileType": "image/gif",
				"extension": "gif",
				"fileSize": "1116",
				"status": "active",
				"downloads": "5865",
				"folderId": null,
				"keywords": "button,back,gif",
				"url_file": "http://yoursite.com/2U1"
			},
			{
				"id": "1163",
				"filename": "button_cancel.gif",
				"shortUrl": "2U3",
				...
			},
			{
				"id": "1164",
				"filename": "button_cancel_over.gif",
				"shortUrl": "2U4",
				...
			}
		]
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the access_token param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div id="folder-info" class="section toc-el api-item-section">
                                                <h4>/folder/info</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Provides information for a specific folder id.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>folder/info</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id returned by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">parent_folder_id</span> The folder parent id. Optional.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string with the response message.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"data": {
		"id": "123",
		"parentId": null,
		"folderName": "My Folder 1",
		"totalSize": "868689",
		"isPublic": "1",
		"accessPassword": null,
		"date_added": "2017-02-15 20:02:05",
		"date_updated": null,
		"url_folder": "http://yoursite.com/folder/123/My_Folder_1",
		"total_downloads": 5864,
		"child_folder_count": 1,
		"file_count": 0
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the access_token param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not find folder based on folder_id.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div id="folder-edit" class="section toc-el api-item-section">
                                                <h4>/folder/edit</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Provides an interface to edit a folder.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>folder/edit</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id returned by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">folder_id</span> The folder id to update.</li>
                                                            <li><span class="param">folder_name</span> The new folder name. Optional.</li>
                                                            <li><span class="param">parent_id</span> The new parent id to move the folder. Optional.</li>
                                                            <li><span class="param">is_public</span> Whether a folder is available publicly or private only. 0 = Private, 1 = Unlisted, 2 = Public in site search. Optional.</li>
                                                            <li><span class="param">access_password</span> An MD5 hash of an access password. Expects 32 characters in length. Optional.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string with the response message.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"response": "Folder successfully updated.",
	"data": {
		"id": "118",
		"parentId": "117",
		"folderName": "My New Folder Name",
		"totalSize": "1024538",
		"isPublic": "1",
		"accessPassword": null,
		"date_added": null,
		"date_updated": null,
		"url_folder": "http://yoursite.com/folder/118/My_New_Folder_Name",
		"total_downloads": 0,
		"child_folder_count": 0,
		"file_count": 11
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the access_token param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not find folder based on folder_id.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div id="folder-delete" class="section toc-el api-item-section">
                                                <h4>/folder/delete</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Provides an interface to delete a folder.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>folder/delete</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id returned by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">folder_id</span> The folder id to update.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string with the response message.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"response": "Folder successfully set as deleted.",
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the access_token param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not find folder based on folder_id.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div id="folder-move" class="section toc-el api-item-section">
                                                <h4>/folder/move</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Provides an interface to move a folder.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>folder/move</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id returned by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">folder_id</span> The folder id to update.</li>
                                                            <li><span class="param">new_parent_folder_id</span> The folder id to move the folder into.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string with the response message.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"response": "Folder successfully moved.",
	"data": {
		"id": "117",
		"parentId": "99",
		"folderName": "layered_png_files",
		...
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the access_token param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the folder_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not find folder based on folder_id.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div class="section">
                                        <div class="section toc-el">
                                            <div id="account-info" class="section toc-el api-item-section">
                                                <h4>/account/info</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Provides details of an account based on the account_id.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>account/info</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                            <li><span class="param">account_id</span> The account id returned by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string including a username (<code>username</code>), account level id (<code>level_id</code>), email address (<code>email</code>) and more.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"data": {
		"id": "158642",
		"username": "admin",
		"level_id": "20",
		"email": "email@yoursite.com",
		"lastlogindate": "2017-02-18 11:43:39",
		"lastloginip": "192.168.33.1",
		"status": "active",
		"title": "Mr",
		"firstname": "Admin",
		"lastname": "User",
		"languageId": "1",
		"datecreated": null,
		"lastPayment": "2011-12-27 13:45:22",
		"paidExpiryDate": "0000-00-00 00:00:00",
		"storageLimitOverride": null
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the access_token param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the account_id param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token and account_id, please reauthenticate or try again.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>
                                            
                                            <div id="package-listing" class="section toc-el api-item-section">
                                                <h4>/package/listing</h4>
                                                <dl>
                                                    <dd>
                                                        <p>Provides a list of available user packages within the system.</p>
                                                        <p>Note: This endpoint is available to admin only API users.</p>
                                                    </dd>
                                                    <dt class="url-label">URL Structure</dt>
                                                    <dd>
                                                        <pre class="literal-block"><?php echo apiv2::getApiUrl(); ?><strong>package/listing</strong></pre>
                                                    </dd>
                                                    <dt>Parameters</dt>
                                                    <dd>
                                                        <ul class="parameters">
                                                            <li><span class="param">access_token</span> The access token created previously by <a href="#authorize"><code>/authorize</code></a>.</li>
                                                        </ul>
                                                    </dd>

                                                    <dt>Returns</dt>
                                                    <dd>
                                                        <p>A JSON-encoded string including a label (<code>label</code>), package id (<code>id</code>) and more.</p>
                                                        <p><strong>Sample Successful JSON Response</strong></p>
                                                        <pre class="literal-block">{
	"data": {
            "packages": [
                {
                    "id": "1",
                    "label": "free user",
                    "max_upload_size": "104857600",
                    "can_upload": "1",
                    "wait_between_downloads": "0",
                    "download_speed": "50000",
                    "max_storage_bytes": "0",
                    "show_site_adverts": "1",
                    "show_upgrade_screen": "1",
                    "days_to_keep_inactive_files": "60",
                    "concurrent_uploads": "50",
                    "concurrent_downloads": "0",
                    "downloads_per_24_hours": "0",
                    "max_download_filesize_allowed": "0",
                    "max_remote_download_urls": "5",
                    "level_type": "free",
                    "on_upgrade_page": "0"
                }
            ]
	},
	"_status": "success",
	"_datetime": "<?php echo date('Y-m-d H:i:s'); ?>"
}</pre>
                                                    </dd>

                                                    <dt>Possible Errors</dt>
                                                    <dd>
                                                        <table class="table table-data-list">
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Please provide the access_token param.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>API user must be an admin user for this endpoint.</td>
                                                            </tr>
                                                            <tr>
                                                                <td>200</td>
                                                                <td>Could not validate access_token, please reauthenticate or try again.</td>
                                                            </tr>
                                                        </table>
                                                    </dd>
                                                </dl>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>