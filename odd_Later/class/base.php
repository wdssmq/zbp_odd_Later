<?php
// 读取 JSON 并随机返回一项
function odd_Later_ReadJSON()
{
  // global $zbp;
  $dirs = array("config" => odd_Later_Path("usr"), "cache" => odd_Later_Path("cache"), "log" => odd_Later_Path("log"));
  $objJSON = new odd_Later_json("bili", $dirs);
  return $objJSON->GetRnd();
}
// 添加/更新配置项
function odd_Later_AddItem($item)
{
  $dirs = array("config" => odd_Later_Path("usr"), "cache" => odd_Later_Path("cache"));
  $objJSON = new odd_Later_json("bili", $dirs);
  $objJSON->Add($item);
  return true;
}
// 解析并输出 JSON
function odd_Later_ReadJSONList()
{
  // global $zbp;
  $dirs = array("config" => odd_Later_Path("usr"), "cache" => odd_Later_Path("cache"));
  $objJSON = new odd_Later_json("bili", $dirs);
  return $objJSON->GerFormat();
}
// 清理缓存
function odd_Later_ClearCache()
{
  $dir = odd_Later_Path("cache");
  $files = GetFilesInDir($dir, "txt|json");
  foreach ($files as $name => $file) {
    unlink($file);
  }
}
