<?php
/**
 * Created by PhpStorm.
 * User: neo
 * Date: 2016/2/22
 * Time: 17:20
 */

namespace aneo\di;


use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionMethod;

class DI
{
    private $context = [];
    private $config = [];
    /**
     * @var DIMetaFactory;
     */
    private $diMetaFactory = null;

    function __construct(DIMetaFactory $diMetaFactory)
    {
        $this->diMetaFactory = $diMetaFactory;
    }


    /**
     * @param string|array $name
     * @param mixed $value
     * @return $this
     */
    public function add($name, $value = null)
    {
        if (is_array($name)) {
            $this->config = array_merge($this->config, $name);
        } else {
            $this->config[$name] = $value;
        }
        return $this;
    }

    public function get($name, $clazz = '')
    {
        if (isset($_REQUEST[$name])) {
            return $_REQUEST[$name];
        }
        if (isset($this->context[$name])) {
            return $this->context[$name];
        }
        $bean = null;
        if (isset($this->config[$name])) {
            if ($this->config[$name] instanceof Closure) {
                $bean = $this->callClosure($this->config[$name]);
            } else {
                $bean = $this->config[$name];
            }
        } else if (!empty($clazz)) {
            if (isset($this->config[$clazz])) {
                if ($this->config[$clazz] instanceof Closure) {
                    $bean = $this->callClosure($this->config[$clazz]);
                } else {
                    $bean = $this->config[$clazz];
                }
            } else {
                // if not configured ,build an object
                $meta = $this->diMetaFactory->getMeta($clazz);
                /* @var $meta DIMetaClass */
                $constructorParameters = $meta->constructParameters;
                //构造器注入
                if ($constructorParameters && count($constructorParameters)) {
                    $params = $this->getParams($constructorParameters);
                    $bean = $meta->reflectionClass->newInstanceArgs($params);
                } else {
                    $bean = new $name();
                }
                //属性注入
//        $injectDepends = $meta->getInjectDepends();
//        foreach ($injectDepends as $dep) {
//            $fieldName = $dep->fieldName;
//            $injectBean = $this->get($dep->injectBean);
//
//            $bean[$fieldName] = $injectBean;
//            return $bean;
//        }
            }
        }
        $this->context[$name] = $bean;
        return $bean;
    }

    public function findByName($name)
    {
        if (isset($_REQUEST[$name])) {
            return $_REQUEST[$name];
        }
        if (isset($this->context[$name])) {
            return $this->context[$name];
        }
        $bean = null;
        if (isset($this->config[$name])) {
            if ($this->config[$name] instanceof Closure) {
                $bean = $this->callClosure($this->config[$name]);
            } else {
                $bean = $this->config[$name];
            }
        }
        if (is_object($bean)) {
            $clazz = get_class($bean);
            $this->context[$clazz] = $bean;
        }
        $this->context[$name] = $bean;
        return $bean;
    }

    public function findByClass($clazz)
    {
        $bean = null;
        if (isset($this->config[$clazz])) {
            if ($this->config[$clazz] instanceof Closure) {
                $bean = $this->callClosure($this->config[$clazz]);
            } else {
                $bean = $this->config[$clazz];
            }
        } else {

            // if not configured ,build an object
            $meta = $this->diMetaFactory->getMeta($clazz);
            /* @var $meta DIMetaClass */
            $constructorParameters = $meta->constructParameters;
            //构造器注入
            if ($constructorParameters && count($constructorParameters)) {
                $params = $this->getParams($constructorParameters);
                $bean = $meta->reflectionClass->newInstanceArgs($params);
            } else {
                $bean = new $clazz();
            }
            //属性注入
//        $injectDepends = $meta->getInjectDepends();
//        foreach ($injectDepends as $dep) {
//            $fieldName = $dep->fieldName;
//            $injectBean = $this->get($dep->injectBean);
//
//            $bean[$fieldName] = $injectBean;
//            return $bean;
//        }
        }
        $this->context[$clazz] = $bean;
        return $bean;
    }


    public function call($obj, $func)
    {
        $reflectionMethod = new ReflectionMethod($obj, $func);
        $methodDepends = $reflectionMethod->getParameters();
        $params = $this->getParams($methodDepends);
        return $reflectionMethod->invokeArgs($obj, $params);
    }

    public function callClosure(Closure $closure)
    {
        $ref = new ReflectionFunction($closure);
        $methodDepends = $ref->getParameters();
        $params = $this->getParams($methodDepends);

        return $ref->invokeArgs($params);
    }

    /**
     * @param DIMetaParameter[] $methodDepends
     * @return array
     */
    private function getParams($methodDepends)
    {
        $params = [];
        if ($methodDepends && count($methodDepends)) {
            foreach ($methodDepends as $dep) {
                $paramName = $dep->name;
                $paramValue = $this->findByName($paramName);
                if(is_null($paramValue)){
                    if(!empty($dep->clazz)){
                        $paramValue = $this->findByClass($dep->clazz);
                    }else{
                        if(!$dep->isDefaultValueAvailable){
                            throw new InvalidArgumentException("$dep->name can't found by di");
                        }
                    }
                }
                array_push($params, $paramValue);
            }
            return $params;
        }
        return $params;
    }
}