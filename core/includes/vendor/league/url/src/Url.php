<?php
/**
* This file is part of the League.url library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/thephpleague/url/
* @version 3.0.0
* @package League.url
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace League\Url;

use League\Url\Components\Scheme;
use League\Url\Components\User;
use League\Url\Components\Pass;
use League\Url\Components\Host;
use League\Url\Components\Port;
use League\Url\Components\Path;
use League\Url\Components\Query;
use League\Url\Components\Fragment;

/**
 * A class to manipulate URLs
 *
 *  @package League.url
 *  @since  1.0.0
 */
class Url extends AbstractUrl
{
    /**
     * The Constructor
     * @param Scheme   $scheme   The URL Scheme component
     * @param User     $user     The URL User component
     * @param Pass     $pass     The URL Pass component
     * @param Host     $host     The URL Host component
     * @param Port     $port     The URL Port component
     * @param Path     $path     The URL Path component
     * @param Query    $query    The URL Query component
     * @param Fragment $fragment The URL Fragment component
     */
    public function __construct(
        Scheme $scheme,
        User $user,
        Pass $pass,
        Host $host,
        Port $port,
        Path $path,
        Query $query,
        Fragment $fragment
    ) {
        $this->scheme = $scheme;
        $this->user = $user;
        $this->pass = $pass;
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->query = $query;
        $this->fragment = $fragment;
    }

    /**
     * Set the URL scheme component
     *
     * @param mixed $data
     *
     * @return self
     */
    public function setScheme($data)
    {
        $this->scheme->set($data);

        return $this;
    }

    /**
     * get the URL scheme component
     *
     * @return League\Url\Components\Scheme
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Set the URL user component
     *
     * @param mixed $data
     *
     * @return self
     */
    public function setUser($data)
    {
        $this->user->set($data);

        return $this;
    }

    /**
     * get the URL user component
     *
     * @return League\Url\Components\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the URL pass component
     *
     * @param mixed $data
     *
     * @return self
     */
    public function setPass($data)
    {
        $this->pass->set($data);

        return $this;
    }

    /**
     * get the URL pass component
     *
     * @return League\Url\Components\Pass
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * Set the URL host component
     *
     * @param mixed $data
     *
     * @return self
     */
    public function setHost($data)
    {
        $this->host->set($data);

        return $this;
    }

    /**
     * get the URL host component
     *
     * @return League\Url\Components\Host
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the URL port component
     *
     * @param mixed $data
     *
     * @return self
     */
    public function setPort($data)
    {
        $this->port->set($data);

        return $this;
    }

    /**
     * get the URL port component
     *
     * @return League\Url\Components\Port
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set the URL path component
     *
     * @param mixed $data
     *
     * @return self
     */
    public function setPath($data)
    {
        $this->path->set($data);

        return $this;
    }

    /**
     * get the URL path component
     *
     * @return League\Url\Components\Path
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the URL query component
     *
     * @param mixed $data
     *
     * @return self
     */
    public function setQuery($data)
    {
        $this->query->set($data);

        return $this;
    }

    /**
     * get the URL user component
     *
     * @return League\Url\Components\Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set the URL fragment component
     *
     * @param mixed $data
     *
     * @return self
     */
    public function setFragment($data)
    {
        $this->fragment->set($data);

        return $this;
    }

    /**
     * get the URL fragment component
     *
     * @return League\Url\Components\Fragment
     */
    public function getFragment()
    {
        return $this->fragment;
    }
}
