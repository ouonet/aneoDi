<?php
use aneo\cache\CacheByFile;
use aneo\di\DI;
use aneo\di\DIMetaFactory;

/**
 * Created by PhpStorm.
 * User: neo
 * Date: 2016/3/12
 * Time: 11:00
 */
class DITest extends PHPUnit_Framework_TestCase
{

    public function testCreateConfig()
    {
        $config = array(
//            'PDO' => function () {
//                return new PDO('mysql:host=localhost:3306;charset=utf8;dbname=ormtest', 'root', 'jjjjjj',
//                    array(
//                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
//                    ));
//            }
            'dsn' => 'mysql:host=localhost:3306;charset=utf8;dbname=ormtest',
            'username' => 'root',
            'passwd' => 'jjjjjj',
            'options' => array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            )
        );

        return $config;
    }

    /**
     * @return DIMetaFactory
     * @depends testCreateConfig
     */
    public function testInitDI($config)
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . 'runtime';
        $cacher = new CacheByFile($dir);
        $diSchemaFactory = new DIMetaFactory($cacher);
        $this->assertInstanceOf('\aneo\di\DIMetaFactory', $diSchemaFactory);
        $di = new DI($diSchemaFactory);
        $di->add($config);
        return $di;
    }


    /**
     * @param DI $di
     * @param array $config
     * @depends testInitDI
     */
    public function testConstructInject($di)
    {
        $action = $di->findByClass('Action');
        $this->assertInstanceOf('Action', $action);
        $this->assertInstanceOf('PDO', $action->pdo);
        return $action;
    }

    /**
     * @param DI $di
     * @param Action $action
     * @depends testInitDI
     * @depends testConstructInject
     */
    public function testMethodInjectFromRequest($di, $action)
    {
        $_REQUEST['name'] = 'beluga';
        $var = $di->call($action, 'insert');
        $this->assertObjectHasAttribute('id', $var);
        $this->assertObjectHasAttribute('name', $var);
        $this->assertEquals('beluga', $var->name);
    }

    /**
     * @param DI $di
     * @param Action $action
     * @depends testInitDI
     * @depends testConstructInject
     */
    public function testWrong($di, $action)
    {
//        $this->setExpectedException('PDOException');
//        $di->call($di, 'wrong');
//        $this->
        $this->setExpectedException('ReflectionException');
        $di->call($action, 'wrong1');
    }

    public function testPhpInput()
    {
        $var = json_decode(file_get_contents("php://input"));
        var_dump($var);
    }
}
 