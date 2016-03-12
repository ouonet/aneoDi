<?php
/**
 * Created by PhpStorm.
 * User: neo
 * Date: 2016/3/5
 * Time: 17:22
 */

namespace aneo\di;


class DIMetaParameter
{

    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $clazz = null;
    /**
     * @var bool
     */
    public $isDefaultValueAvailable;
} 