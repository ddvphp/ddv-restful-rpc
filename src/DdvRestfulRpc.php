<?php

namespace DdvPhp;
use const null;
use const false;
use const true;


class DdvRestfulRpc extends \DdvPhp\DdvRestfulRpc\RpcBase {
  protected $rpcPathPush='/v1_0/push/send';
  /**
   * [pushCall 发送数据到推送服务器]
   * @param  array   $gwcid   [推送gwcid]
   * @param  [type]  $body    [推送数据体]
   * @param  [string]  $pushPath    [推送path]
   * @param  boolean $isSync [是否同步]
   * @param  [string]  $id    [id]
   * @return [array|bool]     [false|array]
   */
  public function pushCall($gwcid=array(), $body='', $pushPath='msg', $isSync=false, $id=null, $headers=array()){
    $isSync = isset($isSync)?$isSync:false;
    $headers = is_array($headers) ? $headers : array();
    $headers['push-path'] = $pushPath ;

    $r = $this->sendDataToRpc($this->rpcPathPush, $gwcid, $body, $headers, $isSync, $id);
    unset($gwcid, $body, $pushPath, $isSync, $id, $headers);
    return $r;
  }
  public function setRpcPathPush($path)
  {
    $this->rpcPathPush = $path;
  }
  public function getRpcPathPush()
  {
    return $this->rpcPathPush;
  }
}
