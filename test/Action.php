<?php

/**
 * Created by PhpStorm.
 * User: neo
 * Date: 2016/3/12
 * Time: 10:58
 */
class Action
{


    public $pdo;

    function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function insert($name)
    {
        $stm = $this->pdo->prepare('insert into person (name,tm_create) VALUE (?,now())');
        $stm->execute(array($name));
        $id = $this->pdo->lastInsertId();
        $stm = $this->pdo->prepare('select * from person where id=?');
        $stm->execute(array($id));
        return $stm->fetch(PDO::FETCH_OBJ);
    }


    /**
     * @param $id
     *
     */
    public function update($id)
    {

    }

    /**
     * @param $obj
     * @RequestBody $obj
     */
    public function modify($obj)
    {

    }

    /**
     * @param $file
     * @RequestFile(param=file,filename=file)
     */
    public function upload($file)
    {

    }

}