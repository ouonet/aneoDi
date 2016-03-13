<?php
/**
 * Created by PhpStorm.
 * User: neo
 * Date: 2016/3/3
 * Time: 15:08
 */

namespace aneo\di;


use aneo\cache\Cache;
use aneo\cache\CacheDataProvider;
use ReflectionClass;
use ReflectionMethod;

class DIMetaFactory implements CacheDataProvider
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var ReflectionClass[]
     */
    private $reflectionClassCache = [];

    function __construct($cache)
    {
        $this->cache = $cache;
    }


    function get($name)
    {
        return $this->parseClass($name);
    }

    function isModifiedSince($name, $time)
    {
        $reflectionClass = $this->getReflectionClass($name);
        $fileName = $reflectionClass->getFileName();
        $mtime = filemtime($fileName);
        return $mtime >= $time;
    }

    function cacheId($name)
    {
        return 'diCache' . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $name) . '_di.json';
    }

    function encode($data){
        return json_encode($data);
    }
    function decode($data){
        return json_decode($data);
    }

    function initial($data){
        $data->reflectionClass = $this->getReflectionClass($data->name);
        return $data;
    }
    /**
     * @param $name
     * @return ReflectionClass
     */
    private function getReflectionClass($name)
    {
        if (!isset($this->reflectionClassCache[$name])) {
            $this->reflectionClassCache[$name] = new ReflectionClass($name);
        }
        return $this->reflectionClassCache[$name];
    }

    /**
     * @param $name
     * @return DIMetaClass
     */
    public function getMeta($name)
    {
        return $this->cache->get($name, $this);
    }

    public function parseClass($name)
    {
        $reflectionClass = $this->getReflectionClass($name);
        $diMetaClass = new DIMetaClass();
        //set class name
        $diMetaClass->name = $reflectionClass->name;
        $constructor = $reflectionClass->getConstructor();
        if ($constructor) {
            $diMetaClass->constructParameters = $this->parseParameter($constructor);
        }
        // for each method,parse it's parameters
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $diMetaMethod = new DIMetaMethod();
            $diMetaMethod->name = $method->name;
            $diMetaMethod->parameters = $this->parseParameter($method);
            $diMetaClass->methods[$method->name] = $diMetaMethod;
        }
        return $diMetaClass;
    }

    /**
     * @param ReflectionMethod $reflectionMethod
     * @return DIMetaParameter[]
     */
    public function parseParameter(ReflectionMethod $reflectionMethod)
    {
        $refParams = $reflectionMethod->getParameters();
        $diParameters = [];
        foreach ($refParams as $refParam) {
            $dip = new DIMetaParameter();
            $dip->name = $refParam->name;
            $clazz = $refParam->getClass();
            if ($clazz) {
                $dip->clazz = $refParam->getClass()->name;
            }
            $dip->isDefaultValueAvailable = $refParam->isDefaultValueAvailable();
            $dip->position = $refParam->getPosition();
            $diParameters[$dip->name] = $dip;
        }
        return $diParameters;
    }

}