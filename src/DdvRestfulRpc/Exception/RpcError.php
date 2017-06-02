<?php

  namespace DdvPhp\DdvRestfulRpc\Exception;

  use \DdvPhp\DdvRestfulApi\Exception\Error as DdvErrorException;


  class RpcError extends DdvErrorException
  {
    // 魔术方法
    public function __construct( $message = 'Unknown Error', $errorId = 'UNKNOWN_ERROR' , $code = '400' )
    {
      parent::__construct( $message , $errorId , $code );
    }
  }