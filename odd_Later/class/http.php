<?php
class odd_Later_BiliHTTP
{
  // 抓取结果
  public $arrayRlt = array();
  // 缓存目录
  public $cache;
  // 日志文件
  public $log;
  // 分页
  public $pn;
  // 远程地址
  public $url;
  // 调试开关
  public $debug = false;
  // JSON 载入信息
  protected $info;
  // 初始
  public function __construct($info)
  {
    $this->info = $info;
    $this->cache = $info['dirs']["cache"];
    $this->log = $info['dirs']["log"];
  }
  // 读取 $info 中的字段
  public function __get($name)
  {
    switch ($name) {
      case 'uid':
        return (int)$this->info['uid'];
        break;
      default:
        if (isset($this->info[$name])) {
          return $this->info[$name];
        }
        break;
    }
    return null;
  }
  // 按分页遍历抓取
  public function LoadJSON($pn = 1)
  {
    $this->pn = $pn;
    $this->GenURL();
    $objRlt = json_decode($this->Http());
    if (empty($objRlt)) {
      return $this->arrayRlt;
    }
    $nDays = floor(time() / 86400) + $this->uid;
    $modCycle = $nDays % $this->cycle;
    if ($pn == 1 && $this->debug) {
      $this->Log();
      $this->Log("-----");
      $this->Log("{$this->uid}: {$nDays} % {$this->cycle} = {$modCycle}");
    }
    if ($modCycle > 0) {
      return $this->arrayRlt;
    }
    $count = $objRlt->data->page->count;
    $objData = $objRlt->data->list->vlist;
    $rlt = $this->LoadList($objData, $nDays);
    $this->arrayRlt = array_merge($this->arrayRlt, $rlt);
    if ($count / $objRlt->data->page->ps > $pn) {
      return $this->LoadJSON($pn + 1);
    } else {
      return $this->arrayRlt;
    }
  }
  // 按规则只提取一部分结果
  public function LoadList($list, $nDays)
  {
    $pickList = array(
      'title' => null,
      'aid' => null,
      'bvid' => null,
      'created' => null,
      'pic' => null
    );
    $rlt = array();
    $nMultiple = $nDays / $this->cycle;
    $curCount = count($list);
    $modIndex = $nMultiple % $curCount;
    if ($this->debug) {
      $this->Log();
      $this->Log("{$this->uid}: {$nMultiple} % {$curCount} = {$modIndex}");
    }
    $pickItem = $list[$modIndex];
    $rlt[$modIndex] = array_intersect_key((array) $pickItem, $pickList);
    $rlt[$modIndex]["mod"] = "$modIndex|$curCount";
    $rlt[$modIndex]["jsonUrl"] = $this->url;
    $rlt[$modIndex]["url"] = "https://www.bilibili.com/video/av{$pickItem->aid}";
    return $rlt;
  }
  public function GenURL()
  {
    $uid = $this->uid;
    $pn = $this->pn;
    if ($this->type == "up") {
      $this->url = "https://api.bilibili.com/x/space/arc/search?mid={$uid}&ps=100&pn={$pn}";
    }
  }
  public function Http()
  {
    $cache = "{$this->cache}{$this->type}-{$this->uid}-{$this->pn}.json";
    if (is_file($cache)) {
      return file_get_contents($cache);
    }
    $http = Network::Create();
    $http->open('GET', $this->url);
    $http->send();

    if ($http->status == 200) {
      file_put_contents($cache, $http->responseText);
      return $http->responseText;
    } elseif ($this->debug) {
      $this->Log("\n");
      $this->Log("抓取失败：{$http->status}");
      $this->Log("网址：{$this->url}");
    }
  }
  public function Log($str = "")
  {
    file_put_contents($this->log, $str . "\n", FILE_APPEND);
  }
}
