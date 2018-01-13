<?php
/**
 * Created by PhpStorm.
 * User: danshur
 * Date: 29.05.2017
 * Time: 23:57
 */

namespace Core\API;


interface StocksHandlerInterface
{

    public function sendRequest($method, array $options = []);

}