<?php
#注册插件
RegisterPlugin("odd_Later", "ActivePlugin_odd_Later");
function ActivePlugin_odd_Later()
{
  // Add_Filter_Plugin('Filter_Plugin_Index_Begin', 'odd_Later_test');
  Add_Filter_Plugin('Filter_Plugin_ViewFeed_Begin', 'odd_Later_Feed');
}
function odd_Later_Feed()
{
  global $zbp;
  if (GetVars("oddLater", "GET") === null) {
    return;
  }
  require __DIR__ . "/class/json.php";
  require __DIR__ . "/class/http.php";
  require __DIR__ . "/class/base.php";
  $info = odd_Later_ReadJSON();
  $objBili = new odd_Later_BiliHTTP($info);
  $objBili->debug = $zbp->Config('odd_Later')->debug;
  $items = $objBili->LoadJSON();

  $GLOBALS['hooks']['Filter_Plugin_ViewFeed_Begin']['odd_Later_Feed'] = PLUGIN_EXITSIGNAL_RETURN;
  $rss2 = new Rss2("[稍后查看][{$info["site"]}]" . $zbp->name, $zbp->host, $zbp->subname);
  $time = strtotime("today");
  foreach ($items as $item) {
    $item = (object) $item;
    $Title = "[{$info["name"]}]{$item->title}";
    $URL = "{$item->url}";
    $Content = "<p><img src=\"{$item->pic}\" rel=\"noreferrer\" /></p>";
    $Content .= "<p>Mod: {$item->mod}</p>";
    $Content .= "<p>Url: {$item->jsonUrl}</p>";
    // $Content .= join("<br>", (array)$item);
    // $Time = $item->created;
    $Time = $time;
    $rss2->addItem($Title, $URL, $Content, $Time);
  }
  header("Content-type:text/xml; Charset=utf-8");
  ob_clean();
  echo $rss2->saveXML();
  die();
  return true;
}
function odd_Later_Path($file, $t = 'path')
{
  global $zbp;
  $result = $zbp->$t . 'zb_users/plugin/odd_Later/';
  switch ($file) {
    case 'log':
      return $result . 'cache/log.txt';
      break;
    case 'cache':
      return $result . 'cache/';
      break;
    case 'u-bili':
      return $result . 'usr/bili.json';
      break;
    case 'v-bili':
      return $result . 'var/bili.json';
      break;
    case 'usr':
      return $result . 'usr/';
      break;
    case 'var':
      return $result . 'var/';
      break;
    case 'main':
      return $result . 'main.php';
      break;
    default:
      return $result . $file;
  }
}
function InstallPlugin_odd_Later()
{
  $dir = odd_Later_Path("cache");
  if (!is_dir($dir)) {
    @mkdir($dir, 0755);
    #code
  }
  $filesList = array("bili");
  foreach ($filesList as $key => $value) {
    $uFile = odd_Later_Path("u-{$value}");
    $vFile = odd_Later_Path("v-{$value}");
    if (!is_file($uFile)) {
      @mkdir(dirname($uFile));
      copy($vFile, $uFile);
    }
  }
}
function UninstallPlugin_odd_Later()
{
}
