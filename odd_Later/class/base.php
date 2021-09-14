<?php
function odd_Later_ReadJSON()
{
  // global $zbp;
  $dirs = array("config" => odd_Later_Path("usr"), "cache" => odd_Later_Path("cache"), "log" => odd_Later_Path("log"));
  $objJSON = new odd_Later_json("bili", $dirs);
  return $objJSON->GetRnd();
}
function odd_Later_AddItem($item)
{
  // global $zbp;
  if (count($item) < 3) {
    return false;
  }
  $dirs = array("config" => odd_Later_Path("usr"), "cache" => odd_Later_Path("cache"));
  $objJSON = new odd_Later_json("bili", $dirs);
  $objJSON->Add($item);
  return true;
}
function odd_Later_ReadJSONList()
{
  // global $zbp;
  $dirs = array("config" => odd_Later_Path("usr"), "cache" => odd_Later_Path("cache"));
  $objJSON = new odd_Later_json("bili", $dirs);
  return $objJSON->GerFormat();
}
