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
// 获取一组质数
function odd_Later_PrimeIt($input)
{
  $map = array(
    0 => [7, 13],
    1 => [11, 17],
    3 => [23, 37],
    7 => [59, 73],
    13 => [107, 137],
    17 => [151, 181],
    37 => [379, 409],
    49 => [503, 593],
    59 => [643, 709],
  );
  $pick = reset($map);
  foreach ($map as $key => $arr) {
    if ($input > $key) {
      $pick = $arr;
    } else {
      break;
    }
  }
  return $pick;
}
