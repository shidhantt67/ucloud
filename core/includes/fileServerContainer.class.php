<?php

/**
 * Main file server container class. Any new adapters defined in Flysystem should be
 * added below as a new method named 'init[UCWORDS_SERVER_TYPE]' i.e. 'initFlysystemFtp'
 */

// ftp
use League\Flysystem\Adapter\Ftp;

// sftp
use League\Flysystem\Sftp\SftpAdapter;

// aws
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;

// rackspace
use OpenCloud\OpenStack;
use OpenCloud\Rackspace;
use League\Flysystem\Rackspace\RackspaceAdapter;

// azure
use WindowsAzure\Common\ServicesBuilder;
use League\Flysystem\Azure\AzureAdapter;

// google drive
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Hypweb\Flysystem\GoogleDrive\GoogleDriveAdapter;

// core flysystem
use League\Flysystem\Filesystem;

class fileServerContainer
{
    /**
     * Main function to instantiate the Flysystem adapter.
     * 
     * @param integer $fileServerId
     * @return boolean
     */
    public static function init($fileServerId)
    {
        // get database connection
        $db = Database::getDatabase();

        // load the file server details
        $fileServer = file::getServerDetailsById($fileServerId);
        if(!$fileServer)
        {
            return false;
        }

        // figure out the name of the method to call within this class
        $serverType = $fileServer['serverType'];
        $methodName = 'init' . str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($serverType))));

        // make sure the method exists
        if(!method_exists('fileServerContainer', $methodName))
        {
            die('Static method \'' . $methodName . '\' for Flysystem adapter does not exist in: ' . __FILE__ . ', it will need to be added.');
        }

        // init the adapter
        return call_user_func('self::' . $methodName, $fileServer);
    }

    /**
     * Method for instantiating Flysystem FTP connectivity.
     * 
     * @param array $fileServer
     * @return Filesystem
     */
    public static function initFlysystemFtp($fileServer)
    {
        // prep config
        $config = self::_doConfigReplacements($fileServer['serverConfig'], [
                    'host' => '',
                    'username' => '',
                    'password' => '',
                    'port' => '',
                    'root' => '',
                    'passive' => '',
                    'ssl' => '',
                    'timeout' => '',
        ]);

        // connect
        $adapter = new League\Flysystem\Adapter\Ftp($config);

        return new Filesystem($adapter);
    }
    
    /**
     * Method for instantiating Flysystem SFTP connectivity.
     * 
     * @param array $fileServer
     * @return Filesystem
     */
    public static function initFlysystemSftp($fileServer)
    {
        // prep config
        $config = self::_doConfigReplacements($fileServer['serverConfig'], [
            'host' => '',
            'port' => 21,
            'username' => '',
            'password' => '',
            'root' => '/',
            'timeout' => 30,
        ]);

        // connect
        $adapter = new League\Flysystem\Sftp\SftpAdapter($config);

        return new Filesystem($adapter);
    }
    
    /**
     * Method for instantiating Flysystem AWS (SDK v3) connectivity.
     * 
     * @param array $fileServer
     * @return Filesystem
     */
    public static function initFlysystemAws($fileServer)
    {
		// prep config
        $config = self::_doConfigReplacements($fileServer['serverConfig'], [
            'credentials' => [
                'key'    => '',
                'secret' => ''
            ],
            'region' => '',
            'version' => '',
        ]);

        // setup client
        $client = new S3Client($config);
        
        // turn the config json into an array
        $serverConfigArr = json_decode($fileServer['serverConfig'], true);

        // connect
        $adapter = new League\Flysystem\AwsS3v3\AwsS3Adapter($client, $serverConfigArr['bucket']);

        return new Filesystem($adapter);
    }
    
    /**
     * Method for instantiating Flysystem Rackspace Cloud Files connectivity.
     * 
     * @param array $fileServer
     * @return Filesystem
     */
    public static function initFlysystemRackspace($fileServer)
    {
        // prep config
        $config = self::_doConfigReplacements($fileServer['serverConfig'], [
            'username' => '',
            'apiKey' => '',
        ]);

        // setup client
        $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, $config);
        
        // turn the config json into an array
        $serverConfigArr = json_decode($fileServer['serverConfig'], true);

        // connect
        $store = $client->objectStoreService('cloudFiles', $serverConfigArr['region']);
        $container = $store->getContainer($serverConfigArr['container']);

        return new Filesystem(new League\Flysystem\Rackspace\RackspaceAdapter($container));
    }
    
    /**
     * Method for instantiating Flysystem Azure Blob Storage connectivity.
     * 
     * @param array $fileServer
     * @return Filesystem
     */
    public static function initFlysystemAzure($fileServer)
    {
        // prep config
        $endpoint = sprintf(
            'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s',
            $serverConfigArr['account-name'],
            $serverConfigArr['api-key']
        );

        $blobRestProxy = ServicesBuilder::getInstance()->createBlobService($endpoint);

        return new Filesystem(new League\Flysystem\Azure\AzureAdapter($blobRestProxy, $serverConfigArr['container']));
    }
    
    /**
     * Method for instantiating Flysystem Google Drive connectivity.
     * 
     * @param array $fileServer
     * @return Filesystem
     */
    public static function initFlysystemGoogledrive($fileServer)
    {
        // turn the config json into an array
        $serverConfigArr = json_decode($fileServer['serverConfig'], true);

        // connect
        $client = new \Google_Client();
        $client->setClientId($serverConfigArr['app_client_id']);
        $client->setClientSecret($serverConfigArr['app_client_secret']);
        $client->refreshToken($serverConfigArr['refresh_token']);

        // setup storage folder
        $storageFolder = strlen($serverConfigArr['storage_folder'])?$serverConfigArr['storage_folder']:'root';
        $storageFolder = $storageFolder=='Root'?'root':$storageFolder;
        
        // start the adapter
        $service = new \Google_Service_Drive($client);
        $adapter = new \Hypweb\Flysystem\GoogleDrive\GoogleDriveAdapter($service, $storageFolder);

        return new Filesystem($adapter);
    }

    /**
     * Helper for replacing config values for the adapters above.
     * 
     * @param string $serverConfig
     * @param array $configTemplate
     * @return array
     */
    public static function _doConfigReplacements($serverConfig, $configTemplate)
    {
        // turn the config json into an array
        $serverConfigArr = json_decode($serverConfig, true);

        // loop over the config template and populate the config for this file server
        foreach($configTemplate AS $k => $configTemplateItem)
        {
            // allow for sub-arrays
            if(is_array($configTemplateItem)) {
                foreach($configTemplateItem AS $k2 => $configTemplateItem2)
                {
                    $configTemplate[$k][$k2] = $serverConfigArr[$k2];
                }
            }
            else {
                $configTemplate[$k] = $serverConfigArr[$k];
            }
        }

        return $configTemplate;
    }

}
