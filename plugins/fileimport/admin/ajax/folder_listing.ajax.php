<?php

// includes and security
include_once('../../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// load plugin class, we need to do it like this as the plugin may not be enabled
$classPath = PLUGIN_DIRECTORY_ROOT . 'fileimport/pluginFileimport.class.php';
$pluginClassName = 'PluginFileimport';
include_once($classPath);
$pluginObj = new $pluginClassName();
$basePath = $pluginObj->getLowestWritableBasePath();
if (_CONFIG_DEMO_MODE == true)
{
    $basePath = DOC_ROOT;
}

class fs
{
    protected $base = null;

    protected function real($path)
    {
        $temp = realpath($path);
        if(!$temp)
        {
            throw new Exception('Path does not exist: ' . $path);
        }
        if($this->base && strlen($this->base))
        {
            if(strpos($temp, $this->base) !== 0)
            {
                throw new Exception('Path is not inside base (' . $this->base . '): ' . $temp);
            }
        }
        return $temp;
    }

    protected function path($id)
    {
        $id = str_replace('/', DIRECTORY_SEPARATOR, $id);
        $id = trim($id, DIRECTORY_SEPARATOR);
        $id = $this->real($this->base . DIRECTORY_SEPARATOR . $id);
        return $id;
    }

    protected function id($path)
    {
        $path = $this->real($path);
        $path = substr($path, strlen($this->base));
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        $path = trim($path, '/');
        return strlen($path) ? $path : '/';
    }
    
    public function __construct($base)
    {
        $this->base = $this->real($base);
        if(!$this->base)
        {
            throw new Exception('Base directory does not exist');
        }
    }

    public function lst($id, $with_root = false)
    {
        $dir = $this->path($id);
        $lst = @scandir($dir);
        if(!$lst)
        {
            throw new Exception('Could not list path: ' . $dir);
        }
        $res = array();
        foreach($lst as $item)
        {
            if($item == '.' || $item == '..' || $item === null)
            {
                continue;
            }
            $tmp = preg_match('([^ a-zа-я-_0-9.]+)ui', $item);
            if($tmp === false || $tmp === 1)
            {
                continue;
            }
            
            // skip any which aren't readable
            if(!is_readable($dir . DIRECTORY_SEPARATOR . $item))
            {
                continue;
            }
            if(is_dir($dir . DIRECTORY_SEPARATOR . $item))
            {
                $res[] = array('text' => $item, 'children' => true, 'id' => $this->id($dir . DIRECTORY_SEPARATOR . $item), 'icon' => 'folder');
            }
            else
            {
                // skip files
                continue;
                //$res[] = array('text' => $item, 'children' => false, 'id' => $this->id($dir . DIRECTORY_SEPARATOR . $item), 'type' => 'file', 'icon' => 'file file-' . substr($item, strrpos($item, '.') + 1));
            }
        }
        if($with_root && $this->id($dir) === '/')
        {
            $res = array(array('text' => basename($this->base), 'children' => $res, 'id' => '/', 'icon' => 'folder', 'state' => array('opened' => true, 'disabled' => true)));
        }
        return $res;
    }
}

if(isset($_GET['operation']))
{
    $fs = new fs($basePath);
    try
    {
        $rslt = null;
        switch($_GET['operation'])
        {
            case 'get_node':
                $node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
                $rslt = $fs->lst($node, (isset($_GET['id']) && $_GET['id'] === '#'));
                break;
            default:
                throw new Exception('Unsupported operation: ' . $_GET['operation']);
                break;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($rslt);
    }
    catch(Exception $e)
    {
        header($_SERVER["SERVER_PROTOCOL"] . ' 500 Server Error');
        header('Status:  500 Server Error');
        echo $e->getMessage();
    }
}