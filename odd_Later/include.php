<?php

// 注册插件
RegisterPlugin('odd_Later', 'ActivePlugin_odd_Later');
function ActivePlugin_odd_Later()
{
    // Add_Filter_Plugin('Filter_Plugin_Index_Begin', 'odd_Later_test');
    Add_Filter_Plugin('Filter_Plugin_ViewFeed_Begin', 'odd_Later_Feed');
    Add_Filter_Plugin('Filter_Plugin_Cmd_Ajax', 'odd_Later_Ajax');
}
function odd_Later_Feed()
{
    global $zbp;
    if (null === GetVars('oddLater', 'GET')) {
        return;
    }

    require_once __DIR__ . '/class/json.php';

    require_once __DIR__ . '/class/base.php';
    // 数据由外部 POST 推送进来后落盘于 cache/bili-usr-{uid}.json
    $uid = trim((string) GetVars('uid', 'GET'));
    $items = odd_Later_GetItems($uid);

    $GLOBALS['hooks']['Filter_Plugin_ViewFeed_Begin']['odd_Later_Feed'] = PLUGIN_EXITSIGNAL_RETURN;
    $rss2 = new Rss2('[稍后查看][bili]' . $zbp->name, $zbp->host, $zbp->subname);
    foreach ($items as $item) {
        $item = (object) $item;
        $Title = "[{$item->name}]{$item->title}";
        // odd_Later_LeanCache($item, $Title);
        $URL = "{$item->url}";
        $Content = "<p><a href=\"{$item->url}\" rel=\"noreferrer\">{$item->title}</a></p>";
        $Content .= "<p>uid: {$item->uid} / bvid: {$item->bvid}</p>";
        $Time = 0 < $item->time ? $item->time : time();
        $rss2->addItem($Title, $URL, $Content, $Time);
    }
    header('Content-type:text/xml; Charset=utf-8');
    ob_clean();
    echo $rss2->saveXML();

    exit();

    return true;
}
// 外部推送入口：zb_system/cmd.php?act=ajax&src=odd_Later&token=xxx
// POST 报文：{"uid": 123, "name": "up主名", "url": "https://...", "title": "标题"}
function odd_Later_Ajax($src)
{
    global $zbp;
    if ('odd_Later' !== $src) {
        return;
    }

    require_once __DIR__ . '/class/json.php';

    require_once __DIR__ . '/class/base.php';
    $token = GetVars('token', 'GET');
    if (!VerifyWebToken($token, 'odd_Later')) {
        odd_Later_InitCache();
        file_put_contents(odd_Later_Path('cache') . date('Ymd') . '_err.log', date('Y-m-d H:i:s') . " token 无效\n", FILE_APPEND);
        JsonError(9, 'token 无效', null);
    }
    $rlt = ['err' => 1, 'msg' => '请求体为空或不是 JSON'];
    $str = file_get_contents('php://input');
    $obj = json_decode($str);
    if (is_object($obj)) {
        $data = [
            'uid' => isset($obj->uid) ? $obj->uid : '',
            'name' => isset($obj->name) ? $obj->name : '',
            'url' => isset($obj->url) ? $obj->url : '',
            'title' => isset($obj->title) ? $obj->title : '',
        ];
        $rlt = odd_Later_SaveItem($data);
    }
    if (0 < $rlt['err'] && $zbp->Config('odd_Later')->debug) {
        odd_Later_Log($rlt['msg']);
    }
    $data = array_diff_key($rlt, ['err' => true, 'msg' => true]);
    if (0 < $rlt['err']) {
        JsonError($rlt['err'], $rlt['msg'], (object) $data);
    }
    JsonReturn((object) $data);
}
// 后台展示用推送 token
function odd_Later_Token($n = 137)
{
    $t = intval($n * 24 * 3600) + time();

    return CreateWebToken('odd_Later', $t);
}
function odd_Later_Path($file, $t = 'path')
{
    global $zbp;
    $result = $zbp->{$t} . 'zb_users/plugin/odd_Later/';

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
// LeanCloud 服务将停，已停用；代码保留以备考古或换服务时参考
// feed 中的调用见 odd_Later_Feed()，同样是注释状态
/*
function odd_Later_LeanCache($item, &$title)
{
  global $zbp;
  $flag = "";
  if (!$zbp->CheckPlugin('mz_LeanCloud')) {
    return $flag;
  }
  $debug = $zbp->Config('odd_Later')->debug;
  if ($debug) {
    mz_LeanCloud_InitClient("bilibili_debug");
  } else {
    mz_LeanCloud_InitClient("bilibili");
  }
  if (mz_LeanCloud::exist(array("bvid", $item->bvid))) {
    $flag = "exist";
    $title = "「已查看」{$title}";
  } else {
    mz_LeanCloud::create($item);
  }
  $errInfo = mz_LeanCloud::GetErrorInfo();
  if ($errInfo->code > 0) {
    $title = "「{$errInfo->msg}」{$title}";
  }
  if ($errInfo->code > 0 && $zbp->Config('odd_Later')->debug) {
    file_put_contents(odd_Later_Path('log'), $errInfo->msg, FILE_APPEND);
  }
  return $flag;
}
*/
function InstallPlugin_odd_Later()
{
    $dir = odd_Later_Path('cache');
    if (!is_dir($dir)) {
        @mkdir($dir, 0755);
        // code
    }
    $filesList = ['bili'];
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
