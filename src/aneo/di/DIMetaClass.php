<?php
/**
 * Created by PhpStorm.
 * User: neo
 * Date: 2016/2/22
 * Time: 17:37
 */

namespace aneo\di;


use ReflectionClass;
use ReflectionParameter;

class DIMetaClass
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var ReflectionClass
     */
    public $reflectionClass;
    /**
     * @var DIMetaParameter[]
     */
    public $constructParameters = null;
    /**
     * @var DIMetaMethod[]
     */
    public $methods = [];
}