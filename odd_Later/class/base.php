<?php

// 读取 JSON 并随机返回一项
function odd_Later_ReadJSON()
{
    // global $zbp;
    $dirs = ['config' => odd_Later_Path('usr'), 'cache' => odd_Later_Path('cache'), 'log' => odd_Later_Path('log')];
    $objJSON = new odd_Later_json('bili', $dirs);

    return $objJSON->GetRnd();
}
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
// 清理某个 uid 的缓存文件
function odd_Later_ClearCacheByUid($uid)
{
    $dir = odd_Later_Path('cache');
    if (!is_dir($dir)) {
        return false;
    }
    $flag = "-{$uid}-";
    $files = GetFilesInDir($dir, 'txt|json');
    foreach ($files as $name => $file) {
        if (false !== strpos(basename($file), $flag)) {
            unlink($file);
        }
    }

    return true;
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
// 获取一组质数
function odd_Later_PrimeIt($input)
{
    $map = [
        0 => [7, 13],
        1 => [11, 17],
        3 => [23, 37],
        7 => [59, 73],
        13 => [107, 137],
        17 => [151, 181],
        37 => [379, 409],
        49 => [503, 593],
        59 => [643, 709],
    ];
    $pick = (object) ['key' => 0, 'value' => [7, 13]];
    foreach ($map as $key => $arr) {
        $pick = (object) ['key' => $key, 'value' => $arr];
        if ($input <= $key) {
            break;
        }
    }
    $pick->value = array_map(function ($item) use ($pick) {
        return $item * $pick->key;
    }, $pick->value);

    return $pick->value;
}
