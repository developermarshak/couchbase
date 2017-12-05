<?php
/**
 * Created by PhpStorm.
 * User: sascha.presnac
 * Date: 20.04.2017
 * Time: 13:23
 */

namespace Mpociot\Couchbase;


class Helper
{
    const TYPE_NAME = 'eloquent_type';

    public static function getUniqueId( $uuid = false)
    {
        if($uuid){
            return uuid_create();
        }
        else{
            return uniqid();
        }
    }

    public static function getIdWithoutCollection($collection, $id){
        return preg_replace("/^".$collection."::/","", $id);
    }

    public static function getIdWithCollection($collection, $id){
        return $collection."::".$id;
    }
}
