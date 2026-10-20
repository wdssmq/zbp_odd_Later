<?php

require_once __DIR__ . '/json.php';

// 添加/更新配置项
function odd_Later_AddItem($item)
{
    $dirs = ['config' => odd_Later_Path('usr'), 'cache' => odd_Later_Path('cache')];
    $objJSON = new odd_Later_json('bili', $dirs);
    $objJSON->Add($item);

    return true;
}
// 删除配置项，同时清理该 uid 的缓存
function odd_Later_DelItem($uid)
{
    $dirs = ['config' => odd_Later_Path('usr'), 'cache' => odd_Later_Path('cache')];
    $objJSON = new odd_Later_json('bili', $dirs);
    $objJSON->Del($uid, true);
    odd_Later_ClearCacheByUid($uid);

    return true;
}
// 清理某个 uid 的推送文件
function odd_Later_ClearCacheByUid($uid)
{
    $file = odd_Later_UsrFile($uid);
    if (is_file($file)) {
        unlink($file);

        return true;
    }

    return false;
}
// 解析并输出 JSON（后台列表，每项后带删除按钮）
function odd_Later_ReadJSONList()
{
    // global $zbp;
    $dirs = ['config' => odd_Later_Path('usr'), 'cache' => odd_Later_Path('cache')];
    $objJSON = new odd_Later_json('bili', $dirs);

    return $objJSON->GerFormat();
}
// 清理缓存
function odd_Later_ClearCache()
{
    $dir = odd_Later_Path('cache');
    $files = GetFilesInDir($dir, 'txt|json');
    foreach ($files as $name => $file) {
        unlink($file);
    }
}
// 解析「三行键值对」文本：uid: 123 / name: xxx / type: up
// 返回 array("item" => array|null, "error" => "")，item 为 null 表示未填写或不符合
function odd_Later_ParseAddText($text)
{
    $rlt = ['item' => null, 'error' => ''];
    // 中文冒号先替换为英文冒号，换行符统一
    $text = str_replace(['：', "\r\n", "\r"], [':', "\n", "\n"], (string) $text);
    $lines = [];
    foreach (explode("\n", $text) as $line) {
        $line = trim($line);
        if ('' !== $line) {
            $lines[] = $line;
        }
    }
    if (0 === count($lines)) {
        return $rlt;
    }
    if (3 !== count($lines)) {
        $rlt['error'] = '需要严格三行，当前 ' . count($lines) . ' 行';

        return $rlt;
    }
    $keys = ['uid', 'name', 'type'];
    $item = [];
    foreach ($lines as $line) {
        $pos = strpos($line, ':');
        if (false === $pos) {
            $rlt['error'] = "缺少冒号：{$line}";

            return $rlt;
        }
        $keyRaw = substr($line, 0, $pos);
        if ($keyRaw !== rtrim($keyRaw)) {
            $rlt['error'] = "冒号前不要有空格：{$line}";

            return $rlt;
        }
        $key = strtolower(trim($keyRaw));
        $val = trim(substr($line, $pos + 1));
        if (!in_array($key, $keys, true)) {
            $rlt['error'] = "未知键名：{$key}";

            return $rlt;
        }
        if ('' === $val) {
            $rlt['error'] = "键 {$key} 的值为空";

            return $rlt;
        }
        if (isset($item[$key])) {
            $rlt['error'] = "键名重复：{$key}";

            return $rlt;
        }
        $item[$key] = $val;
    }
    if (3 !== count($item)) {
        $rlt['error'] = '需要 uid / name / type 三行';

        return $rlt;
    }
    if (!preg_match('/^\d+$/', $item['uid'])) {
        $rlt['error'] = "uid 需为数字：{$item['uid']}";

        return $rlt;
    }
    $rlt['item'] = $item;

    return $rlt;
}
// 确保缓存目录存在
function odd_Later_InitCache()
{
    $dir = odd_Later_Path('cache');
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    return $dir;
}

// 某个 uid 的推送文件路径：cache/bili-usr-{uid}.json
function odd_Later_UsrFile($uid)
{
    return odd_Later_Path('cache') . "bili-usr-{$uid}.json";
}

// 去重列表文件路径：cache/bili-last.json
function odd_Later_LastFile()
{
    return odd_Later_Path('cache') . 'bili-last.json';
}

// 取 uid 配置项；$uid 为空时返回 odd_Later_json 实例
function odd_Later_GetCfg($uid = '')
{
    $dirs = ['config' => odd_Later_Path('usr'), 'cache' => odd_Later_Path('cache'), 'log' => odd_Later_Path('log')];
    $objJSON = new odd_Later_json('bili', $dirs);
    if ('' === (string) $uid) {
        return $objJSON;
    }

    return $objJSON->Get($uid);
}

// 取 uid 对应的 up 主名
function odd_Later_GetCfgName($uid)
{
    $cfg = odd_Later_GetCfg($uid);
    if (null === $cfg || !isset($cfg['name'])) {
        return '';
    }

    return (string) $cfg['name'];
}

// 读取推送条目，按 time 倒序；$uid 为空时取全部
function odd_Later_GetItems($uid = '')
{
    $uid = trim((string) $uid);
    $files = [];
    if ('' !== $uid) {
        $files[] = odd_Later_UsrFile($uid);
    } elseif (is_dir(odd_Later_Path('cache'))) {
        foreach (GetFilesInDir(odd_Later_Path('cache'), 'json') as $file) {
            if (0 === strpos(basename($file), 'bili-usr-')) {
                $files[] = $file;
            }
        }
    }
    $rlt = [];
    foreach ($files as $file) {
        if (!is_file($file)) {
            continue;
        }
        $match = [];
        if (!preg_match('/^bili-usr-(.+)\.json$/', basename($file), $match)) {
            continue;
        }
        $curUid = $match[1];
        $obj = json_decode(file_get_contents($file), true);
        if (!is_array($obj) || empty($obj['urls']) || !is_array($obj['urls'])) {
            continue;
        }
        $curName = isset($obj['name']) ? (string) $obj['name'] : odd_Later_GetCfgName($curUid);
        foreach ($obj['urls'] as $item) {
            if (!is_array($item) || !isset($item['url'])) {
                continue;
            }
            $curUrl = (string) $item['url'];
            $rlt[] = [
                'uid' => $curUid,
                'name' => $curName,
                'url' => $curUrl,
                'title' => isset($item['title']) ? (string) $item['title'] : '',
                'bvid' => isset($item['bvid']) ? (string) $item['bvid'] : odd_Later_ParseBvid($curUrl),
                'time' => isset($item['time']) ? (int) $item['time'] : 0,
            ];
        }
    }
    usort($rlt, function ($a, $b) {
        if ($a['time'] === $b['time']) {
            return 0;
        }

        return ($a['time'] > $b['time']) ? -1 : 1;
    });

    return $rlt;
}

// 保存推送条目：uid 匹配 + name 一致 + url 合法 + title 非空 + 去重
// $data = array("uid" => "", "name" => "", "url" => "", "title" => "")
// 返回 array("err" => int, "msg" => "", "uid" => "", "url" => "", "bvid" => "")
function odd_Later_SaveItem($data)
{
    $rlt = ['err' => 0, 'msg' => 'ok', 'uid' => '', 'url' => '', 'bvid' => ''];
    $uid = isset($data['uid']) ? trim((string) $data['uid']) : '';
    $name = isset($data['name']) ? trim((string) $data['name']) : '';
    $url = isset($data['url']) ? trim((string) $data['url']) : '';
    $title = isset($data['title']) ? trim((string) $data['title']) : '';
    $rlt['uid'] = $uid;
    $rlt['url'] = $url;

    if ('' === $uid || !preg_match('/^\d+$/', $uid)) {
        $rlt['err'] = 1;
        $rlt['msg'] = "uid 缺失或不是数字：{$uid}";

        return $rlt;
    }
    $cfg = odd_Later_GetCfg($uid);
    if (null === $cfg) {
        $rlt['err'] = 2;
        $rlt['msg'] = "uid 未匹配已有数据：{$uid}";

        return $rlt;
    }
    $cfgName = isset($cfg['name']) ? trim((string) $cfg['name']) : '';
    if ('' === $name || $name !== $cfgName) {
        $rlt['err'] = 3;
        $rlt['msg'] = "name 与已有数据不一致：{$name} ≠ {$cfgName}";

        return $rlt;
    }
    if ('' === $url || !preg_match('#^https?://#i', $url)) {
        $rlt['err'] = 4;
        $rlt['msg'] = 'url 缺失或不合法';

        return $rlt;
    }
    if ('' === $title) {
        $rlt['err'] = 5;
        $rlt['msg'] = 'title 缺失';

        return $rlt;
    }
    if (in_array($url, odd_Later_GetLast(), true)) {
        $rlt['err'] = 6;
        $rlt['msg'] = '重复推送';

        return $rlt;
    }

    $bvid = odd_Later_ParseBvid($url);
    $rlt['bvid'] = $bvid;
    odd_Later_InitCache();
    $file = odd_Later_UsrFile($uid);
    $obj = ['name' => $cfgName, 'urls' => []];
    if (is_file($file)) {
        $old = json_decode(file_get_contents($file), true);
        if (is_array($old) && isset($old['urls']) && is_array($old['urls'])) {
            $obj['urls'] = $old['urls'];
        }
    }
    array_unshift($obj['urls'], [
        'url' => $url,
        'title' => $title,
        'bvid' => $bvid,
        'time' => time(),
    ]);
    $obj['urls'] = array_slice($obj['urls'], 0, odd_Later_json::$defMax);
    file_put_contents($file, json_encode($obj, JSON_UNESCAPED_UNICODE));
    odd_Later_SetLast($url);

    return $rlt;
}

// 从 url 截取 bvid：BV 号 → av 号 → 回退整条 url
function odd_Later_ParseBvid($url)
{
    $url = (string) $url;
    $match = [];
    if (preg_match('/BV[0-9A-Za-z]{10}/', $url, $match)) {
        return $match[0];
    }
    if (preg_match('/av\d+/i', $url, $match)) {
        return strtolower($match[0]);
    }

    return $url;
}

// 去重列表：最新在前，最多 odd_Later_json::$defMax 条
function odd_Later_GetLast()
{
    $file = odd_Later_LastFile();
    if (!is_file($file)) {
        return [];
    }
    $arr = json_decode(file_get_contents($file), true);

    return is_array($arr) ? $arr : [];
}

// 写入去重列表
function odd_Later_SetLast($url)
{
    odd_Later_InitCache();
    $arr = odd_Later_GetLast();
    array_unshift($arr, (string) $url);
    $arr = array_slice($arr, 0, odd_Later_json::$defMax);
    file_put_contents(odd_Later_LastFile(), json_encode($arr, JSON_UNESCAPED_UNICODE));

    return true;
}

// 调试日志
function odd_Later_Log($str = '')
{
    odd_Later_InitCache();
    file_put_contents(odd_Later_Path('log'), date('Y-m-d H:i:s') . " {$str}\n", FILE_APPEND);

    return true;
}
