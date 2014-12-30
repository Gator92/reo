<?php
namespace Reo\Autoload;

/**
 * AutoLoader
 *
 * Simple Autoloader registers a method with the spl autoloader to autoload psr-0 compliant classes.
 *
 * Copyright (c) Schuyler W Langdon.
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */

class Autoloader
{
/**
 * @param $exts array of full extension names
 */
    private $exts = array('.php');

/**
 * @param $append_path bool flag to append or reset include path
 */
    private $append_path = false;

/**
 * @param $set_include_path bool|string flag to append the include path with injected lib paths, optionally a single named path eg: 'vendor'
 *
 * @note: For stuff like Zend 1, where classes are not designed to be autoloaded (contain require_once)
 */
    private $set_include_path = false;
/**
 * @param array $paths stack of named library paths
 */
    private $paths = array();
/**
 * @param $registry array registry stack of classes to map or skip (contain their own autoload)
 * @note checks the deepest namespace first, ie Framework\Component\Fabstuff will override Framework\Component
 */
    private $registry = array();

/**
 * @param $register_spl bool whether or not to register class loader with spl autoloader
 */
    private $register_spl = true;

/**
 * @param debug bool will throw exception when class not found
 */
    private $debug = false;

/**
 * construct
 *
 * @param $paths array of paths for autoloading
 * @param $options array of options that will set the autoloaders private properties of the same name
 * @param $registry array of classes / namespaces for which to register a load path
 */
    public function __construct(array $paths = null, array $options = null, array $registry = null)
    {
        if (isset($this->paths)) {
            $this->paths = $paths;
        }
        if (isset($options)) {
            $this->mapOptions($options);
        }
        if (false !== $this->set_include_path) {
            if (true === $this->set_include_path) {
                $this->doSetIncludePath($this->paths);
            } elseif (isset($this->paths[$this->set_include_path])) {
                $this->doSetIncludePath((array) $this->paths[$this->set_include_path]);
            } else {
                throw new \InvalidArgumentException(sprintf('The named path [%s] to include does not exist', $this->set_include_path));
            }
        }
        if (isset($registry)) {
            foreach ($registry as $key => $path) {
                $this->register($key, $path);
            }
        }

        if ($this->register_spl) {
            spl_autoload_register(array($this, 'loadClass'));
        }
    }

    public static function create(array $paths = null, array $options = null, array $registry = null)
    {
        return new static($paths, $options, $registry);
    }

    public function loadClass($class)
    {
        // work around for PHP 5.3.0 - 5.3.2 https://bugs.php.net/50731
        if ('\\' == $class[0]) {
            $class = substr($class, 1);
        }
        //namespaces and classes that follow convention (pear ^ camel)
        $classPath = str_replace('_', \DIRECTORY_SEPARATOR, str_replace('\\', \DIRECTORY_SEPARATOR, $class));
        if (!$this->loadPath($classPath, $this->paths) && !$this->checkRegistry($class, $classPath)) {
            if ($this->debug) {
                throw new \InvalidArgumentException(sprintf('File not found. Could not load class [%s] in path(s) [%s] [%s]', $class, implode(', ', $this->paths), $classPath));
            }
            return false;
        }
        return true;
    }

    private function loadPath($classPath, $paths)
    {
        foreach ($paths as $path) {
            foreach ($this->exts as $ext) {
                //echo $path. \DIRECTORY_SEPARATOR . $class_path . $ext, "\n";
                if (false !== stream_resolve_include_path($include = $path . \DIRECTORY_SEPARATOR . $classPath . $ext)) {
                    require_once $include;

                    return true;
                    break 2;
                }
            }
        }

        return false;
    }

    /**
     * these should really not be used, pass to constructor instead
     */
    public function addPath($name, $path)
    {
        if (!isset($this->paths[$name])) {
            $this->paths[$name] = $path;
        }
    }

    public function setPaths(array $paths)
    {
        $this->paths = $paths;
    }

    public function appendPath(array $paths)
    {
        $this->paths = empty($this->paths) ? $paths : array_merge($this->paths, array_diff($paths, $this->paths));
    }

    public function getPath($name = null)
    {
        return isset($name) ? (isset($this->paths[$name]) ? $this->paths[$name] : false) : $this->paths;
    }

    public function getRegistry()
    {
        return $this->registry;
    }

/**
 * register
 *
 * Add a namespace root to the autoload registry
 *
 * @param $id string The class name or namespace to register
 * @param $loadPath string|bool directory path or bool false if lib loads itself
 */
    public function register($name, $loadPath)
    {
        $path = str_replace('_', \DIRECTORY_SEPARATOR, str_replace('\\', \DIRECTORY_SEPARATOR, $name));
        if (isset($this->classRegistry[$path])) {
            return false;
        }
        $this->registry[$path] = $loadPath;

        return true;
    }

    public function getRegistryEntry($name)
    {
        $key = str_replace('_', \DIRECTORY_SEPARATOR, str_replace('\\', \DIRECTORY_SEPARATOR, $name));

        return isset($this->registry[$key]) ? $this->registry[$key] : null;
    }

    public function checkRegistry($class, $classPath = null)
    {
        if (empty($this->registry)) {
            return false;
        }
        if (!isset($classPath)) {
            $classPath = str_replace('_', \DIRECTORY_SEPARATOR, str_replace('\\', \DIRECTORY_SEPARATOR, $class));
        }
        $paths = explode(\DIRECTORY_SEPARATOR, $classPath);
        $regPath = $classPath;
        for ($ct = count($paths), $xx = 0; $xx < $ct; $xx++) {
            if (isset($this->registry[$regPath])) {
                return false === ($loadPath = $this->registry[$regPath])
                    ? true //the class loads itself
                    : $this->loadPath($classPath, (array) $loadPath);
                break;
            }
            array_pop($paths);
            $regPath = implode(\DIRECTORY_SEPARATOR, $paths);
        }

        return false;
    }

    public function getOption($key)
    {
        return isset($this->{$key}) ? $this->{$key} : null;
    }

    private function doSetIncludePath($paths)
    {
        if (!isset($paths)) {
            return;
        }
        $set_paths = explode(\PATH_SEPARATOR, get_include_path());
        $ini_paths = explode(\PATH_SEPARATOR, ini_get('include_path'));
        //get rid of ini paths unless append path is on, spl paths dont need to be here
        //@note, would probably just want to append a certain path such as vendor
        set_include_path(implode(\PATH_SEPARATOR, array_unique($this->append_path ? array_merge($set_paths, $ini_paths, $paths) : array_merge(array_diff($set_paths, $ini_paths), $paths))));
    }

    private function mapOptions(array $options)
    {
        foreach ($options as $key => $val) {
            if (isset($this->{$key})) {
                $this->{$key} = $val;
            }
        }
    }
}
