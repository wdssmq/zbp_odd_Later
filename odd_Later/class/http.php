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
    // 对于每个 uid，每隔 cycle 天抓取一次
    $nDays = floor(time() / 86400) + $this->uid;
    // $nDays = $nDays - 1;
    $this->nDays = $nDays;
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
    // 生成请求链接
    $this->pn = $pn;
    $this->GenURL();
    $modCycle = $this->nDays % $this->cycle;
    if ($pn == 1 && $this->debug) {
      $this->Log();
      $this->Log("-----");
      $this->Log($this->name);
      $this->Log($this->url);
      $this->Log("{$this->uid}: {$this->nDays} % {$this->cycle} = {$modCycle}");
    }
    if ($modCycle > 0) {
      return $this->arrayRlt;
    }
    // 抓取内容
    $objRlt = $this->Http();
    if (empty($objRlt)) {
      return $this->arrayRlt;
    }
    if ($pn == 1) {
      $this->count = $objRlt->data->page->count;
      $this->pageCount = ceil($this->count / $objRlt->data->page->ps);
      $this->arrPrime = odd_Later_PrimeIt($this->pageCount);
    }
    $vlist = $objRlt->data->list->vlist;
    $rlt = $this->LoadList($vlist, $this->nDays, $this->arrPrime);
    $this->arrayRlt = array_merge($this->arrayRlt, $rlt);
    if ($this->pageCount > $pn) {
      return $this->LoadJSON($pn + 1);
    } else {
      return $this->arrayRlt;
    }
  }
  // 按规则只提取一部分结果
  public function LoadList($list, $nDays, $arrPrime)
  {
    $pickList = array(
      'title' => null,
      'aid' => null,
      'bvid' => null,
      'created' => null,
      'pic' => null
    );
    $rlt = array();
    $nMultiple = floor($nDays / $this->cycle);
    foreach ($list as $item) {
      $aid = $item->aid;
      $curHash = $nMultiple + $aid;
      // $arrMod = [$curHash % $arrPrime[0], $curHash % $arrPrime[1]];
      $mod = $curHash % ($arrPrime[0] +  $arrPrime[1] + $this->cycle);
      if ($mod == 0) {
        $rlt[$aid] = array_intersect_key((array) $item, $pickList);
        $rlt[$aid]["jsonUrl"] = $this->url;
        $rlt[$aid]["url"] = "https://www.bilibili.com/video/av{$aid}";
      } else {
        continue;
      }
      if ($this->debug) {
        $this->Log("{$this->uid}: ({$nMultiple} + {$aid}) % ({$arrPrime[0]} + {$arrPrime[1]} + {$this->cycle}) = {$mod}");
        $this->Log();
      }
    }
    return $rlt;
  }
  public function GenURL()
  {
    $uid = $this->uid;
    $pn = $this->pn;
    if ($this->type == "up") {
      $this->url = "https://api.bilibili.com/x/space/arc/search?mid={$uid}&ps=50&pn={$pn}";
    }
  }
  public function Http()
  {
    $cache = "{$this->cache}{$this->type}-{$this->uid}-{$this->pn}.json";
    if (is_file($cache)) {
      return json_decode(file_get_contents($cache));
    }
    $http = Network::Create();
    $http->open('GET', $this->url);
    $http->send();
    $data = json_decode($http->responseText);
    if ($http->status == 200 && $data->code == 0) {
      file_put_contents($cache, $http->responseText);
      return $data;
    } elseif ($this->debug) {
      $this->Log("\n");
      $this->Log("抓取失败：{$http->status}");
      $this->Log("网址：{$this->url}");
    }
    return null;
  }
  public function Log($str = "")
  {
    file_put_contents($this->log, $str . "\n", FILE_APPEND);
  }
}
