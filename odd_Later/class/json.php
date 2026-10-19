<?php

class odd_Later_json
{
  public $dirs;
  public $site;
  public $file;
  static public $defCycle = 13;
  protected $data = array();
  public function __construct($site, $dirs = array())
  {
    $this->site = $site;
    $this->dirs = $dirs;
    $this->file = $dirs["config"] . "{$site}.json";
    if (is_file($this->file)) {
      $this->data = json_decode(file_get_contents($this->file), true);
    }
  }
  public function Exist($uid)
  {
    foreach ($this->data as $item) {
      if ($item["uid"] == $uid) {
        return true;
      }
    }
    return false;
  }
  public function Del($uid, $doSave = false)
  {
    foreach ($this->data as $key => $item) {
      if ($item["uid"] == $uid) {
        unset($this->data[$key]);
      }
    }
    if ($doSave) {
      $this->Save();
    }
  }
  public function Add($item)
  {
    if ($this->Exist($item["uid"])) {
      $this->Del($item["uid"]);
    }
    $this->data[] = $item;
    $this->Save();
  }
  public function Save()
  {
    $this->data = array_merge(array(), $this->data);
    file_put_contents($this->file, json_encode($this->data));
  }
  public function GetData()
  {
    return $this->data;
  }
  public function GetRnd()
  {
    $rndKey = array_rand($this->data);
    $arrPick = $this->data[$rndKey];
    // 2021-09-14
    if (!isset($arrPick["cycle"])) {
      $arrPick["cycle"] = odd_Later_json::$defCycle;
    }
    // 2021-09-14
    return array_merge($arrPick, array("dirs" => $this->dirs, "site" => $this->site));
  }
  public function GerFormat()
  {
    $tpl = <<<HTML
    <li class="json-item">
        <span>{{uid}}</span>
        <span>{{name}}</span>
        <span>{{type}}</span>
        <a href="{{delUrl}}" title="删除 UID {{uid}}" onclick="return window.confirm('确定删除该项？UID: {{uid}}');">「删除」</a>
    </li>
HTML;
    $rlt = "";
    foreach ($this->GetData() as $item) {
      $delUrl = BuildSafeURL("main.php?act=del&uid=" . urlencode($item["uid"]));
      $rlt .= str_replace(
        array("{{uid}}", "{{name}}", "{{type}}", "{{delUrl}}"),
        array($item["uid"], $item["name"], $item["type"], $delUrl),
        $tpl
      );
    }
    return "<ul class=\"json-list\">{$rlt}</ul>";
  }
}
