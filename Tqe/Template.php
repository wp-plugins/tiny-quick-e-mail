<?php

//Disable direct view.
if (!defined('IN_PLUGIN'))
    die('You can not access this file directly.');

/**
 * Template class.
 *
 * @author Marijan Šuflaj <msufflaj32@gmail.com>
 * @category Tqe
 * @package Tqe_Template
 */
class Tqe_Template
{

    /**
     * Template directory.
     *
     * @var string
     */
    private $_dir               = '';

    /**
     * Files that have to be included.
     *
     * @var array
     */
    private $_files             = array();

    /**
     * Data passed to class.
     *
     * @var stdClass
     */
    private $_data              = null;


    /**
     * Constructor
     *
     * @param string $dir Directory
     * @param stdClass $data Data object
     * @throws Exception If directory does not exist
     */
    public function __construct($dir, $data = null)
    {
        if (is_dir($dir))
            $this->_dir = $dir;
        else
            throw new Exception('Invalid template directory provided.');

        if (!is_null($data))
        	$this->_data = $data;

    }

    /**
     * Adds file to $_files
     *
     * @param string $file File path
     * @return Tqe_Template Class instance
     * @throws Exception If file does not exist
     */
    public function assignFile($file)
    {
        if (is_file($this->_dir . DS . $file))
        	$this->_files[] = $file;
        else
            throw new Exception(sprintf('File %s does not exist in templates folder.', $file));

        return $this;

    }

    /**
     * Renders files and then returns them.
     *
     * @return string Render
     */
    public function returnRender()
    {
        ob_start();
        $this->_requireAll();
        return ob_get_clean();
    }

    /**
     * Renders files.
     *
     * @return Tqe_Template Class instance
     */
    public function render()
    {
        $this->_requireAll();

        return $this;
    }

    /**
     * Requires all files.
     *
     * @return Tqe_Template Class instance
     */
    private function _requireAll()
    {
        foreach ($this->_files as $file)
            require_once $this->_dir . DS . $file;

        $this->_files = array();

        return $this;
    }
}