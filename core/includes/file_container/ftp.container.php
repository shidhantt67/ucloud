<?php

class ftpContainer extends container
{
	public function __construct()
	{
		$this->name = "FTP File Storage";
		$this->key 	= "ftp";
	}
	
	public function getAdapter()
	{
		$settings = $this->getSettings();
		if(!$settings)
		{
			return false;
		}
		
		
	}
	
	// admin area options, these are automatically shown and saved into the database on update
	public function getAdminSettingsHtml()
	{
		$html  = '';
		$html .= '<div class="clearfix">
                        <label>Ftp Host:</label>
                        <div class="input">
                            <input name="ftp_host" id="ftp_host" type="text" value="">
                        </div>

                        <label>Ftp Port:</label>
                        <div class="input">
                            <input name="ftp_port" id="ftp_port" type="text" value="21" class="small">
                        </div>
                    </div>';
		
		$html .= '<div class="clearfix alt-highlight">
                        <label>Ftp Username:</label>
                        <div class="input">
                            <input name="ftp_username" id="ftp_username" type="text" value="">
                        </div>
                        <label>Ftp Password:</label>
                        <div class="input">
                            <input name="ftp_password" id="ftp_password" type="password" value="">
                        </div>
                    </div>';
					
		$html .= '<div class="clearfix">
                        <label>Storage Path:</label>
                        <div class="input">
                            <input name="storage_path" id="ftp_storage_path" type="text" value="files/" class="large"><br><br>- As the FTP user would see it. Login with this FTP user using an FTP client to confirm<br>the path to use.
                        </div>
                    </div>';

		$html .= '<div class="clearfix alt-highlight">
                        <label>Ftp Server Type:</label>
                        <div class="input">
                            <select name="ftp_server_type" id="ftp_server_type" style="width: 180px;">        <option value="linux" selected="">Linux (for most)</option>        <option value="windows">Windows</option>        <option value="windows_alt">Windows Alternative</option>        </select>
                        </div>
                        <label>Enable Passive Mode:</label>
                        <div class="input">
                            <select name="ftp_passive_mode" id="ftp_passive_mode">        <option value="no" selected="">No (default)</option>        <option value="yes">Yes</option>        </select>
                        </div>
                    </div>';
		
		return $html;
	}
}