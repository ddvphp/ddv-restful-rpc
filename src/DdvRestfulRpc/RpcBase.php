<?php

namespace DdvPhp\DdvRestfulRpc;
use \DdvPhp\DdvRestfulRpc\Exception\RpcError as DdvRpcError;
use const NULL;
use const FALSE;
use const TRUE;


class RpcBase {
  protected $rpcType='http';
  protected $rpcServerUrlIndex=0;
  protected $headersPrefix='x-rpc-';
  protected $contentType='application/ddv-rpc-call-data';
  protected $rpcServerUrl=array();
  protected $urlConnectionFailedErrorId='RPC_SERVER_CONNECTION_FAILED';
  public function __construct($obj=NULL)
  {
    // 初始化
    is_array($obj) ? call_user_func_array(array($this, 'init'), func_get_args()) : $this->init(array());
  }
  public function init($config)
  {
    isset($config['rpcUrl']) && $this->setRpcServerUrl($config['rpcUrl']);
  }

  /**
   * [sendDataToRpc 合并请求参数]
   * @param  [type]  $url     [推送服务器地址]
   * @param  array   $gwcid   [推送gwcid]
   * @param  [type]  $body    [推送数据体]
   * @param  array   $headers [推送头]
   * @param  boolean $isSync [是否同步]
   * @param  [string]  $id    [id]
   * @return [array|bool]     [FALSE|array]
   */
  public function sendDataToRpc($path, $gwcid=array(), $body, array $headers=array(), $isSync=FALSE, $id=NULL){
    if(empty($gwcid)){
      throw new DdvRpcError('Must input push gwcid', 'MUST_INPUT_PUSH_GWCID');
    }
    $path = empty($path) ? '/' : (string)$path;
    $headers['id'] = (string)(empty($headers['id']) ? ($this->isRpcCallId($id) ? $id : $this->getRpcCallId()) : $headers['id']);
    $headers['sync'] = empty($headers['sync']) ? ($isSync ? 'true' : 'false') : $headers['sync'];
    $headers['gwcid'] = empty($headers['gwcid']) ? (is_array($gwcid) ? $gwcid : array($gwcid)) : $headers['gwcid'];
    return $this->sendDataToRpcRun($path, $body, $headers);
  }
  /**
   * [sendDataToRpcRun description]
   * @author: 桦 <yuchonghua@163.com>
   * @DateTime 2017-06-02T15:20:51+0800
   * @param    string                   $path          [description]
   * @param    [type]                   $body          [description]
   * @param    array                    $headers       [description]
   * @param    [type]                   $lastException [description]
   * @return   [type]                                  [description]
   */
  protected function sendDataToRpcRun($path, $body, array $headers=array(), $lastException = NULL){
    $url = $this->getRpcUrlByPath($path, ($lastException instanceof DdvRpcError && $lastException->getErrorId() === $this->urlConnectionFailedErrorId));
    try {
      return $this->sendDataToRpcCall($url, $body, $headers);
    } catch (DdvRpcError $e) {
      switch ($e->getErrorId()) {
        case $this->urlConnectionFailedErrorId:
          return $this->sendDataToRpcRun($path, $body, $headers, $e);
        case 'RPC_SERVER_URL_SWITCH_OVERFLOW':
        {
          if ($lastException instanceof DdvRpcError) {
            throw $lastException;
          }else{
            throw $e;
          }
        }
        default:
          throw $e;
      }
    }
  }
  protected function sendDataToRpcCall($url, $body, array $headers=array()){
    if ($this->rpcType === 'http') {
      return $this->sendDataToRpcByHttp($url, $body, $headers);
    }
  }

  /**
   * [sendDataToRpcByHttp 发送数据包到rpc 通过 头 和 体]
   * @param  [type] $url     [description]
   * @param  array  $headers [description]
   * @param  [type] $body    [description]
   * @return [type]          [description]
   */
  protected function sendDataToRpcByHttp($url, $body, array $inputHeaders){
    $headers = array();
    foreach ($inputHeaders as $key => $value) {
      $key = $this->headersPrefix . $key ;
      if (is_array($value)) {
        foreach ($value as $v) {
          $headers[] = trim($key) .': ' . trim($v);
        }
      }else{
        $headers[] = trim($key) .': ' . trim($value);
      }
    }
    unset($inputHeaders);
    $res = $this->callHttp($url, $headers, $body);
    unset($url, $body, $headers);
    if ($res) {
      $resJson = json_decode($res, TRUE);
      if($resJson){
        $res = $resJson ;
      }else{
        throw new DdvRpcError('Parsing return data failed', 'PARSING_RETURN_DATA_FAILED');
        $res = FALSE ;
      }
      unset($resJson);
    }else{
      $res = FALSE ;
    }
    return $res;
  }
  /**
   * 发送post请求
   * @author: 桦 <yuchonghua@163.com>
   */
  protected function callHttp($url, array $headers, $body){
    $oCurl = curl_init();
    if(stripos($url,"https://")!==FALSE){
      curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
      curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
    }

    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($oCurl, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($oCurl, CURLOPT_POSTFIELDS,$body);

    //代理
    //curl_setopt($oCurl, CURLOPT_HTTPPROXYTUNNEL, TRUE);
    //curl_setopt($oCurl, CURLOPT_PROXY, '127.0.0.1:8080');
    //curl_setopt($ch, CURLOPT_PROXYUSERPWD, 'user:password');//如果要密码的话，加上这个

    $sContent = curl_exec($oCurl);
    $aStatus = curl_getinfo($oCurl);
    //var_dump($sContent, $aStatus);
    curl_close($oCurl);

    error_log('rpc:'.$aStatus["http_code"]);
    error_log('rpc_content:'.$sContent);

    if(intval($aStatus["http_code"])==200){
      return $sContent;
    }else{
      return FALSE;
    }
  }
  public function setRpcServerUrl($urls = NULL)
  {
    $this->pushRpcServerUrl(TRUE);
    if(is_array($urls)){
      foreach ($urls as $index => $url) {
        $this->pushRpcServerUrl($url);
      }
    }elseif(is_string($urls)){
        $this->pushRpcServerUrl($urls);
    }else{
      throw new DdvRpcError('Error Rpc Server Url', 'ERROR_RPC_SERVER_URL');
      
    }
  }
  public function pushRpcServerUrl($url = NULL)
  {
    if ($url===TRUE) {
      $this->rpcServerUrl = array();
    }elseif (is_string($url)) {
      $this->rpcServerUrl[] = $url;
    }else{
      throw new DdvRpcError('Error Rpc Server Url', 'ERROR_RPC_SERVER_URL');
    }
  }
  public function getRpcUrlByPath ($path, $isTabUrl = false) {
    $path = empty($path)?'/':$path;
    if ($isTabUrl) {
      $this->rpcServerUrlIndex ++ ;
    }
    if ($this->rpcServerUrlIndex>=count($this->rpcServerUrl)) {
      $this->rpcServerUrlIndex = 0;
      if($isTabUrl){
        if (empty($this->rpcServerUrl)) {
          throw new DdvRpcError('Did not set rpcServerUrl', 'DID_NOT_SET_RPCSERVERURL');
        }else{
          throw new DdvRpcError('Rpc Server Url switch overflow', 'RPC_SERVER_URL_SWITCH_OVERFLOW');
        }
      }
    }
    return $this->rpcServerUrl[$this->rpcServerUrlIndex] . $path ;
  }
  public function getRpcCallId () {
    return time();
  }
  public function isRpcCallId ($id=NULL) {
    return !is_null($id);
  }
}
