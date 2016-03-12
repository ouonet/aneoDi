<?php
/**
 * Created by PhpStorm.
 * User: neo
 * Date: 2016/2/22
 * Time: 17:20
 */

namespace aneo\di;


use aneo\benchmark\Bench;
use aneo\cache\Cache;
use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

class DI
{
    private $context = [];
    private $config = [];
    private $meta = [];
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

    public function get($name)
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
        } else {
            // if not configured ,build an object
            $meta = $this->diMetaFactory->getMeta($name);
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
        $this->context[$name] = $bean;
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
                $paramValue = $this->get($paramName);
                if (is_null($paramValue)) {
                    $paramClass = $dep->clazz;
                    if (!is_null($paramClass))
                        array_push($params, $this->get($paramClass));
                } else {
                    array_push($params, $paramValue);
                }
            }
            return $params;
        }
        return $params;
    }
}