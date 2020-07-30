<?php
class odd_Later_BiliHTTP
{
  public $arrayRlt = array();
  public $cache;
  public $pn;
  public $url;
  protected $info;
  public function __construct($info)
  {
    $this->info = $info;
    $this->cache = $info["cache"];
  }
  public function __get($name)
  {
    switch ($name) {
      case 'value':
        # code...
        break;

      default:
        if (isset($this->info[$name])) {
          return $this->info[$name];
        }
        break;
    }
  }
  public function LoadJSON($pn = 1)
  {
    $this->pn = $pn;
    $this->GenURL();
    $objRlt = json_decode($this->Http());
    if (empty($objRlt)) {
      return array();
    }
    $objData = $objRlt->data->list->vlist;
    $rlt = $this->LoadList($objData);
    $this->arrayRlt = array_merge($this->arrayRlt, $rlt);
    if ($objRlt->data->page->count / $objRlt->data->page->ps > $pn) {
      return $this->LoadJSON($pn + 1);
    } else {
      return $this->arrayRlt;
    }
  }
  public function LoadList($list)
  {
    $whiteList = array(
      'title' => null,
      'aid' => null,
      'bvid' => null,
      'created' => null,
      'pic' => null
    );
    // $nDays = floor(time() / 86400);
    $nDays = floor(time() / 88888);
    $rlt = array();
    foreach ($list as $key => $item) {
      $hash = $item->aid;
      $modRlt = ($nDays + $hash) % 593 + 1;
      $arrCheck = array_flip(array(13, 37, 73, 137, 313, 433, 577));
      // $arrCheck = array_flip(array(13, 73, 313, 433));
      if (!array_key_exists($modRlt, $arrCheck)) {
        continue;
      }
      $rlt[$key] = array_intersect_key((array) $item, $whiteList);
      $rlt[$key]["mod"] = $modRlt;
      $rlt[$key]["jsonUrl"] = $this->url;
      $rlt[$key]["url"] = "https://www.bilibili.com/video/av{$item->aid}";
    }
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
    } else {
      // debug
      // ob_clean();
      echo __FILE__ . "丨" . __LINE__ . ":<br>\n";
      var_dump($this->url);
      echo "<br><br>\n\n";
      var_dump($http->status);
      echo "<br><br>\n\n";
      die();
      // debug
    }
  }
}
