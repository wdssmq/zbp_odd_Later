<?php

/** @var array $lang */
/** @var object $zbp */
/** @var string $blogpath */
require '../../../zb_system/function/c_system_base.php';

require '../../../zb_system/function/c_system_admin.php';

require_once __DIR__ . '/class/json.php';

require_once __DIR__ . '/class/base.php';
$zbp->Load();
$action = 'root';
if (!$zbp->CheckRights($action)) {
    $zbp->ShowError(6);

    exit();
}
if (!$zbp->CheckPlugin('odd_Later')) {
    $zbp->ShowError(48);

    exit();
}
$act = GetVars('act', 'GET');
$suc = GetVars('suc', 'GET');
if ('save' == $act) {
    CheckIsRefererValid();
    $addItem = ['cycle' => odd_Later_json::$defCycle];
    $parsed = odd_Later_ParseAddText(isset($_POST['add-text']) ? $_POST['add-text'] : '');
    foreach ($_POST as $key => $val) {
        $cVal = trim($val);
        if ('cfg-' === substr($key, 0, 4) && '' !== $cVal) {
            $cKey = substr($key, 4 - strlen($key));
            if ('debug' == $cKey) {
                $cVal = '1' == $cVal ? true : false;
            }
            $zbp->Config('odd_Later')->{$cKey} = $cVal;
        }
    }
    $zbp->SaveConfig('odd_Later');
    if ('' !== $parsed['error']) {
        $zbp->SetHint('tips', '输入不符合：' . $parsed['error']);
    } elseif (null !== $parsed['item']) {
        odd_Later_AddItem(array_merge($addItem, $parsed['item']));
    } else {
        $zbp->SetHint('good');
    }
    Redirect('./main.php' . (null == $suc ? '' : '?act=$suc'));
}
if ('del' == $act) {
    CheckIsRefererValid();
    $delUid = trim((string) GetVars('uid', 'GET'));
    if ('' !== $delUid) {
        odd_Later_DelItem($delUid);
    }
    $zbp->SetHint('good');
    Redirect('./main.php');
}
if ('clear' == $act) {
    CheckIsRefererValid();
    odd_Later_ClearCache();
    $zbp->SetHint('good');
    Redirect('./main.php' . (null == $suc ? '' : '?act=$suc'));
}
$t = intval(137 * 24 * 3600) + time();
$url = "{$zbp->host}feed.php?oddLater";
$pushUrl = "{$zbp->host}zb_system/cmd.php?act=ajax&src=odd_Later&token=" . odd_Later_Token(137);
// 以列表中的第一项生成 POST 示例
$arrCfg = odd_Later_GetCfg()->GetData();
// date 为视频发布时间、time 为提交时间，格式均为 Y-m-d H:i:s
$demo = [
    'uid' => '123456',
    'name' => '用户名',
    'url' => 'https://www.bilibili.com/video/BV1xx411c7mD',
    'title' => '标题',
    'date' => date('Y-m-d 00:00:00', strtotime('-1 day')),
    'time' => date('Y-m-d H:i:s'),
];
$firstCfg = empty($arrCfg) ? [] : reset($arrCfg);
if (isset($firstCfg['uid'])) {
    $demo['uid'] = (string) $firstCfg['uid'];
}
if (isset($firstCfg['name'])) {
    $demo['name'] = (string) $firstCfg['name'];
}
$demoJson = json_encode($demo, JSON_UNESCAPED_UNICODE);
InstallPlugin_odd_Later();
$blogtitle = '「奇怪插件」稍后查看';

require $blogpath . 'zb_system/admin/admin_header.php';

require $blogpath . 'zb_system/admin/admin_top.php';
?>
<link rel="stylesheet" type="text/css" href="style/style.css?v=20261020">
<div id="divMain">
    <div class="divHeader"><?php echo $blogtitle; ?> </div>
    <div class="SubMenu">
        <a href="main.php" title="首页"><span class="m-left m-now">首页</span></a>
        <a href="<?php echo BuildSafeURL('main.php?act=clear'); ?>" title="删除缓存" onclick="return window.confirm('点击「确定」以继续，或者「取消」。')"><span class="m-right">删除缓存</span></a>
        <?php require_once 'about.php'; ?>
    </div>
    <div id="divMain2">
        <form action="<?php echo BuildSafeURL('main.php?act=save'); ?>" method="post">
            <table width="100%" class="tableBorder">
                <tr>
                    <th width="10%">项目</th>
                    <th>内容</th>
                    <th width="45%">说明</th>
                </tr>
                <tr>
                    <td>添加/修改</td>
                    <td>
                        <?php echo zbpform::textarea('add-text', '', '90%', '100px', "uid: 123456\nname: 用户名\ntype: up"); ?>
                        <p>严格三行，每行一个「键: 值」</p>
                    </td>
                    <td>
                        示例：
                        <pre>uid: 123456
name: 用户名
type: up</pre>
                        <input type="submit" value="提交" />
                    </td>
                </tr>
                <tr>
                    <td>列表</td>
                    <td colspan="2"><?php echo odd_Later_ReadJSONList(); ?></td>
                </tr>
                <tr>
                    <td>推送地址</td>
                    <td class="odd-push">
                        <input type="text" class="odd-url-input" value="<?php echo htmlspecialchars($pushUrl); ?>" onclick="this.select();" readonly />
                    </td>
                    <td class="odd-push">
                        <p>uid 须已存在于上方列表，name 须与列表中的名称完全一致，否则拒收。</p>
                        <p>url 与已推送记录重复时，按重复推送忽略（保留最新 100 条）。</p>
                    </td>
                </tr>
                <tr>
                    <td>调试模式</td>
                    <td><?php zbpform::zbradio('cfg-debug', $zbp->Config('odd_Later')->debug); ?> <?php echo odd_Later_a(odd_Later_Path('log', 'host'), '查看日志', '查看日志'); ?></td>
                    <td><input type="submit" value="提交" /></td>
                </tr>
            </table>
        </form>
        <h3>
            RSS直链：<?php echo odd_Later_a($url, '当前地址', '点击查看'); ?>
        </h3>
        <p>按 uid 过滤：<?php echo odd_Later_a($url . '&uid=' . $demo['uid'], 'uid=' . $demo['uid'], '点击查看'); ?></p>
        <p><a title="订阅到 Feedly" target="_blank" href="https://feedly.com/i/subscription/feed%2F<?php echo urlencode($url); ?>">订阅到 Feedly</a></p>
        <p><a title="订阅到 Inoreader" target="_blank" href="https://www.inoreader.com/feed/<?php echo urlencode($url); ?>">订阅到 Inoreader</a></p>
        <h3>推送示例</h3>
        <p>POST 报文（JSON）：</p>
        <pre><?php echo htmlspecialchars($demoJson); ?></pre>
        <p>curl 示例：</p>
        <pre>curl -X POST "<?php echo htmlspecialchars($pushUrl); ?>" -H "Content-Type: application/json" -d '<?php echo $demoJson; ?>'</pre>
        <!-- <h4>－－－－下边是推广链接－－－－</h4> -->
    </div>
</div>

<?php
function odd_Later_a($href, $title, $text = '', $target = '_blank')
{
    if (empty($text)) {
        $text = $href;
    }

    return "<a href=\"{$href}\" title=\"{$title}\" target=\"{$target}\">{$text}</a>";
}

require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>
