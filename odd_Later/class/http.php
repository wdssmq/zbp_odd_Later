<?php

class odd_Later_BiliHTTP
{
    // 抓取结果
    public $arrayRlt = [];
    // 缓存目录
    public $cache;
    // 日志文件
    public $log;
    // 分页
    public $pn;
    // 远程地址
    public $url;
    // ----
    public $nDays;
    public $count;
    public $pageCount;
    public $arrPrime;
    // 调试开关
    public $debug = false;
    // JSON 载入信息
    protected $info;

    // 初始
    public function __construct($info)
    {
        $this->info = $info;
        $this->cache = $info['dirs']['cache'];
        $this->log = $info['dirs']['log'];
        // 对于每个 uid，每隔 cycle 天抓取一次
        $nDays = floor(time() / 86400) + $this->uid;
        // $nDays = $nDays - 1;
        $this->nDays = $nDays;
    }

    // 读取 $info 中的字段
    public function __get($name)
    {
        switch ($name) {
      case 'uid':
        return (int) $this->info['uid'];

        break;

      default:
        if (isset($this->info[$name])) {
            return $this->info[$name];
        }

        break;
    }

        return null;
    }

    // 按分页遍历抓取
    public function LoadJSON($pn = 1)
    {
        // 生成请求链接
        $this->pn = $pn;
        $this->GenURL();
        $cycle = max(1, (int) $this->cycle);
        $modCycle = $this->nDays % $cycle;
        $shouldFetch = 0 == $modCycle;
        if (1 == $pn && $this->debug) {
            $this->Log();
            $this->Log('-----');
            $this->Log($this->name);
            $this->Log($this->url);
            $this->Log("{$this->uid}: {$this->nDays} % {$cycle} = {$modCycle}");
        }
        // 抓取内容
        $objRlt = $this->Http($shouldFetch);
        if (empty($objRlt)) {
            return $this->arrayRlt;
        }
        if (1 == $pn) {
            if (!isset($objRlt->data->page->count) || !isset($objRlt->data->page->ps) || (int) $objRlt->data->page->ps < 1) {
                if ($this->debug) {
                    $this->Log("返回数据缺少有效分页信息：{$this->url}");
                }

                return $this->arrayRlt;
            }
            $this->count = $objRlt->data->page->count;
            $this->pageCount = ceil($this->count / $objRlt->data->page->ps);
            $this->arrPrime = odd_Later_PrimeIt($this->pageCount);
            $this->Log("{$this->uid}: count: {$this->count}, pageCount: {$this->pageCount}");
        }
        $vlist = isset($objRlt->data->list->vlist) && is_array($objRlt->data->list->vlist) ? $objRlt->data->list->vlist : [];
        $rlt = $this->LoadList($vlist, $this->nDays, $this->arrPrime);
        $this->arrayRlt = array_merge($this->arrayRlt, $rlt);
        if ($this->pageCount > $pn) {
            return $this->LoadJSON($pn + 1);
        }

        return $this->arrayRlt;
    }

    // 按规则只提取一部分结果
    public function LoadList($list, $nDays, $arrPrime)
    {
        $pickList = [
            'title' => null,
            'aid' => null,
            'bvid' => null,
            'created' => null,
            'pic' => null,
        ];
        $rlt = [];
        $nMultiple = floor($nDays / max(1, (int) $this->cycle));
        foreach ($list as $item) {
            $aid = $item->aid;
            $curHash = $nMultiple + $aid;
            // $arrMod = [$curHash % $arrPrime[0], $curHash % $arrPrime[1]];
            $mod = $curHash % ($arrPrime[0] + $arrPrime[1]);
            if (0 == $mod) {
                $rlt[$aid] = array_intersect_key((array) $item, $pickList);
                $rlt[$aid]['jsonUrl'] = $this->url;
                $rlt[$aid]['url'] = "https://www.bilibili.com/video/av{$aid}";
            } else {
                continue;
            }
            if ($this->debug) {
                $this->Log("{$this->uid}: ({$nMultiple} + {$aid}) % ({$arrPrime[0]} + {$arrPrime[1]}) = {$mod}");
            }
        }

        return $rlt;
    }

    public function GenURL()
    {
        $uid = $this->uid;
        $pn = $this->pn;
        if ('up' == $this->type) {
            $this->url = "https://api.bilibili.com/x/space/arc/search?mid={$uid}&ps=50&pn={$pn}";
        }
    }

    public function Http($shouldFetch = true)
    {
        $cache = "{$this->cache}{$this->type}-{$this->uid}-{$this->pn}.json";
        $retryCache = "{$cache}.retry.txt";
        $cachedData = null;
        $cacheValid = false;
        if (is_file($cache)) {
            $cacheText = file_get_contents($cache);
            $cachedData = json_decode($cacheText);
            $cacheValid = $this->IsValidResponse($cachedData);
            if ($cacheValid) {
                $cacheTime = filemtime($cache);
                $cycle = max(1, (int) $this->cycle);
                $cacheCycle = false === $cacheTime ? null : floor((floor($cacheTime / 86400) + $this->uid) / $cycle);
                $currentCycle = floor($this->nDays / $cycle);
                if (!$shouldFetch || $cacheCycle === $currentCycle) {
                    return $cachedData;
                }
            } elseif ($this->debug) {
                $this->Log("缓存数据无效：{$cache}");
            }
        }
        if (is_file($retryCache)) {
            $retryTime = filemtime($retryCache);
            if (false !== $retryTime && time() - $retryTime < 3600) {
                return $cacheValid ? $cachedData : null;
            }
        }
        if (false === file_put_contents($retryCache, (string) time(), LOCK_EX) && $this->debug) {
            $this->Log("请求冷却标记写入失败：{$retryCache}");
        }
        clearstatcache(true, $retryCache);
        $http = Network::Create();
        if (!is_object($http)) {
            if ($this->debug) {
                $this->Log("无法创建 HTTP 请求：{$this->url}");
            }

            return $cacheValid ? $cachedData : null;
        }
        $http->open('GET', $this->url);
        if ($http instanceof Network__curl && defined('CURLOPT_HTTP_VERSION') && defined('CURL_HTTP_VERSION_1_1')) {
            curl_setopt($http->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        }
        $http->setRequestHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36');
        $http->setRequestHeader('Accept', 'application/json, text/plain, */*');
        $http->setRequestHeader('Accept-Language', 'zh-CN,zh;q=0.9');
        $http->setRequestHeader('Origin', 'https://space.bilibili.com');
        $http->setRequestHeader('Referer', "https://space.bilibili.com/{$this->uid}/");
        $cookie = $this->Cookie();
        if ('' !== $cookie) {
            $http->setRequestHeader('Cookie', $cookie);
        }
        $http->setTimeOuts(15, 5, 5, 10);
        $http->enableGzip();
        $http->send();
        $responseText = isset($http->responseText) ? $http->responseText : '';
        $data = json_decode($responseText);
        $status = $http->getStatusCode();
        if (200 == $status && $this->IsValidResponse($data)) {
            $cacheWritten = file_put_contents($cache, $responseText, LOCK_EX);
            clearstatcache(true, $cache);
            if (false === $cacheWritten && $this->debug) {
                $this->Log("缓存写入失败：{$cache}");
            }
            if (is_file($retryCache)) {
                unlink($retryCache);
            }

            return $data;
        }
        if ($this->debug) {
            $code = isset($data->code) ? $data->code : 'invalid response';
            $message = isset($data->message) ? $data->message : '';
            $statusText = $http->getStatusText();
            $body = preg_replace('/\s+/', ' ', trim($responseText));
            $body = substr($body, 0, 300);
            $contentType = $http->getResponseHeader('Content-Type');
            $biliStatus = $http->getResponseHeader('Bili-Status-Code');
            $bodyLength = strlen($responseText);
            $this->Log("抓取失败：HTTP {$status} {$statusText}, API {$code} {$message}; 响应字节：{$bodyLength}; Content-Type：{$contentType}; Bili-Status-Code：{$biliStatus}; 响应：{$body}");
            $this->Log("网址：{$this->url}");
            if (in_array((int) $code, [-352, -412, -509, -799], true)) {
                if ('' === $cookie) {
                    $this->Log('提示：疑似命中风控且未配置 Cookie，可在后台填写自己的 B 站 Cookie（含 SESSDATA、buvid3）。');
                } else {
                    $this->Log('提示：请求已携带 Cookie 但仍被风控，请从浏览器当前登录会话复制完整 Cookie（含 SESSDATA、buvid3、b_nut），并稍后重试。');
                }
            }
        }

        return $cacheValid ? $cachedData : null;
    }

    // 读取可选的 B 站 Cookie（后台 cfg-cookie，留空则匿名请求）
    public function Cookie()
    {
        $cookie = $this->cookie;
        if (!is_string($cookie)) {
            return '';
        }
        // 换行 Cookie 按字段分隔拼接，避免把多行字段合并为无效的单个值。
        $cookie = trim(preg_replace('/[\r\n]+/', '; ', $cookie));
        $cookie = preg_replace('/(?:^|;\s*)cookie\s*:\s*/i', '', $cookie);
        $cookie = preg_replace('/\s*;\s*/', '; ', $cookie);

        return trim($cookie);
    }

    public function Log($str = '')
    {
        file_put_contents($this->log, $str . "\n", FILE_APPEND);
    }

    protected function IsValidResponse($data)
    {
        return is_object($data)
      && isset($data->code)
      && 0 === (int) $data->code
      && isset($data->data->page->count, $data->data->page->ps)

      && (int) $data->data->page->ps > 0
      && isset($data->data->list->vlist)
      && is_array($data->data->list->vlist);
    }
}
