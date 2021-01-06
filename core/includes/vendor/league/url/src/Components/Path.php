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
namespace League\Url\Components;

/**
 *  A class to manipulate URL Path component
 *
 *  @package League.url
 *  @since  1.0.0
 */
class Path extends AbstractSegment implements SegmentInterface
{
    /**
     * {@inheritdoc}
     */
    protected $delimiter = '/';

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        if (! $this->data) {
            return null;
        }

        return implode($this->delimiter, array_map('rawurlencode', $this->data));
    }

    /**
     * {@inheritdoc}
     */
    public function getUriComponent()
    {
        $value = $this->__toString();
        if ('' != $value) {
            $value = '/'.$value;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function append($data, $whence = null, $whence_index = null)
    {
        $this->data = $this->appendSegment(
            $this->data,
            $this->validate($data),
            $whence,
            $whence_index
        );
    }

    /**
     * {@inheritdoc}
     */
    public function prepend($data, $whence = null, $whence_index = null)
    {
        $this->data = $this->prependSegment(
            $this->data,
            $this->validate($data),
            $whence,
            $whence_index
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function validate($data)
    {
        $data = $this->validateSegment($data, $this->delimiter);
        $data = $this->sanitizeValue($data);

        return array_map('urldecode', $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function formatRemoveSegment($data)
    {
        return array_map('urldecode', parent::formatRemoveSegment($data));
    }
}
