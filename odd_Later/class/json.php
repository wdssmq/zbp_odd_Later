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
    return array_merge($this->data[$rndKey], array("dirs" => $this->dirs, "site" => $this->site));
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
