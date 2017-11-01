<?php
/**
 * The main dispatcher class
 *
 * @author Michael Lux <michi.lux@gmail.com>
 * @copyright Copyright (c) 2017 Michael Lux
 * @license LGPLv3
 */

namespace milux\add;


class Dispatcher {

    private $resNamespace = '';
    /**
     * @var AnnotationParser
     */
    private $parser = null;
    /**
     * @var array
     */
    private $params = null;
    /**
     * @var \ReflectionMethod
     */
    private $reflectionMethod = null;

    public function __construct($resNamespace = null) {
        if (isset($resNamespace)) {
            if (substr($resNamespace, -1) !== '\\') {
                $resNamespace .= '\\';
            }
            $this->resNamespace = $resNamespace;
        }
    }

    /**
     * Resolves and checks the target method and the invocation parameters
     * @param bool $clearRequest Whether to clear $_REQUEST, $_GET and $_POST after parsing
     * @throws DispatchException
     */
    public function dispatch($clearRequest = true) {
        //get resource path parts (method parameters)
        $pathInfo = explode('/', trim($_SERVER['PATH_INFO'], '/'));
        //name of the module/class to invoke
        $module = $this->resNamespace . array_shift($pathInfo);
        //name of the method to invoke
        $method = array_shift($pathInfo);
        if (empty($method)) {
            $method = strtolower($_SERVER['REQUEST_METHOD']);
        }

        //check module class
        if (!class_exists($module)) {
            throw new DispatchException('Module "' . $module . '" not found', 404);
        }
        //create class reflection
        $reflectClass = new \ReflectionClass($module);
        //check if class is callable
        if (!$reflectClass->implementsInterface('\milux\add\ICallable')) {
            throw new DispatchException('Class is no module, PATH_INFO: ' . $_SERVER['PATH_INFO'], 400);
        }
        //check method existence
        if (!$reflectClass->hasMethod($method)) {
            throw new DispatchException('Method not found, PATH_INFO: ' . $_SERVER['PATH_INFO'], 400);
        }
        //create method reflection
        $this->reflectionMethod = $reflectClass->getMethod($method);
        //parse the method's DocComment
        $this->parser = new AnnotationParser($this->reflectionMethod->getDocComment());

        //collect parameters according to information from DocComment
        $this->params = [];
        foreach ($this->parser->getParamMeta() as $pName => $pMeta) {
            $src = null;
            if (isset($pMeta['Source'])) {
                $p = explode('.', $pMeta['Source'][0]);
                switch (array_shift($p)) {
                    case 'POST':
                        $src = $_POST;
                        break;
                    case 'GET':
                        $src = $_GET;
                        break;
                    case 'PATH':
                        $src = $pathInfo;
                        break;
                }
                //scan deeper into data structure according to specified indicies
                while (count($p) > 0) {
                    $subIndex = array_shift($p);
                    if (isset($src[$subIndex])) {
                        $src = $src[$subIndex];
                    } else {
                        $src = NULL;
                        break;
                    }
                }
                //add parameterized data source to parameter array
                if (isset($src)) {
                    settype($src, $pMeta['type']);
                    $this->params[$pName] = $src;
                }
            }
        }

        //check if method is public and static, and number of provided parameters (res. path)
        //is greater or equal to the number of required parameters
        if (!$this->reflectionMethod->isStatic() || !$this->reflectionMethod->isPublic()
            || count($this->params) < $this->reflectionMethod->getNumberOfRequiredParameters()) {
            throw new DispatchException('Method is not public static or wrong parameter number, '
                . 'PATH_INFO: ' . $_SERVER['PATH_INFO'], 400);
        }

        if ($clearRequest) {
            //clear request data
            $_REQUEST = $_POST = $_GET = array();
        }
    }

    /**
     * Invoke the dispatched method with the collected parameters
     * @return mixed
     */
    public function invoke() {
        return $this->reflectionMethod->invokeArgs(NULL, $this->params);
    }

    /**
     * Getter for the annotation parser that processed the method
     * @return AnnotationParser
     */
    public function getAnnotationParser() {
        return $this->parser;
    }

}