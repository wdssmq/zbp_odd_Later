<?php

class odd_Later_json
{
  public $dirs;
  public $site;
  public $file;
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
      $arrPick["cycle"] = 7;
    }
    // 2021-09-14
    return array_merge($arrPick, array("dirs" => $this->dirs, "site" => $this->site));
  }
  public function GerFormat()
  {
    $rlt = "";
    foreach ($this->data as $key => $item) {
      $cLine = implode(" | ", $item);
      // $cLine = "<li>{$cLine}</li>";
      $rlt .= "<li>{$cLine}</li>";
    }
    return "<ul>{$rlt}</ul>";
  }
}
