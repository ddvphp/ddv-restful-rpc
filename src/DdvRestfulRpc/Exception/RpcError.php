<?php

namespace DdvPhp\DdvRestfulRpc\Exception;

class RpcError extends \DdvPhp\DdvException\Error
{
  // 魔术方法
  public function __construct( $message = 'Unknown Error', $errorId = 'UNKNOWN_ERROR' , $code = '400' )
  {
    parent::__construct( $message , $errorId , $code );
  }
}