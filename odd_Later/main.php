<?php
require '../../../zb_system/function/c_system_base.php';

require '../../../zb_system/function/c_system_admin.php';

require __DIR__ . '/class/json.php';

require __DIR__ . '/class/http.php';

require __DIR__ . '/class/base.php';
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
    $delItem = [];
    foreach ($_POST as $key => $val) {
        $cVal = trim($val);
        if ('add-' === substr($key, 0, 4) && '' !== $cVal) {
            $cKey = substr($key, 4 - strlen($key));
            $addItem[$cKey] = $cVal;
        }
        if ('del-' === substr($key, 0, 4) && '' !== $cVal) {
            $cKey = substr($key, 4 - strlen($key));
            $delItem[$cKey] = $cVal;
        }
        if ('cfg-' === substr($key, 0, 4)) {
            $cKey = substr($key, 4 - strlen($key));
            // cookie 允许提交空值以便清空，其它配置项空值忽略
            if ('' === $cVal && 'cookie' !== $cKey) {
                continue;
            }
            if ('debug' == $cKey) {
                $cVal = '1' == $cVal ? true : false;
            }
            $zbp->Config('odd_Later')->{$cKey} = $cVal;
        }
    }
    $zbp->SaveConfig('odd_Later');
    if (4 == count($addItem)) {
        odd_Later_AddItem($addItem);
    } elseif (3 == count($addItem)) {
        $zbp->SetHint('tips', '输入不符合');
    } else {
        $zbp->SetHint('good');
    }
    Redirect('./main.php' . (null == $suc ? '' : '?act=$suc'));
}
if ('clear' == $act) {
    CheckIsRefererValid();
    odd_Later_ClearCache();
    $zbp->SetHint('good');
    Redirect('./main.php' . (null == $suc ? '' : '?act=$suc'));
}
$t = intval(137 * 24 * 3600) + time();
$url = "{$zbp->host}feed.php?oddLater";
InstallPlugin_odd_Later();
$blogtitle = '「奇怪插件」稍后查看';

require $blogpath . 'zb_system/admin/admin_header.php';

require $blogpath . 'zb_system/admin/admin_top.php';
?>
<div id="divMain">
  <div class="divHeader"><?php echo $blogtitle; ?> </div>
  <div class="SubMenu">
    <a href="main.php" title="首页"><span class="m-left m-now">首页</span></a>
    <a href="<?php echo BuildSafeURL('main.php?act=clear'); ?>" title="删除缓存" onclick="return window.confirm('点击「确定」以继续，或者「取消」。')"><span class="m-right">删除缓存</span></a>
    <?php require_once 'about.php'; ?>
  </div>
  <style>
    .ul-style li {
      float: left;
      width: 33%;
    }
  </style>
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
            <p>UID <?php echo zbpform::text('add-uid', '', '90%'); ?></p>
            <p>Name <?php echo zbpform::text('add-name', '', '90%'); ?></p>
            <p>Type <?php echo zbpform::text('add-type', 'up', '90%'); ?></p>
            <p>所有项目必填</p>
          </td>
          <td><input type="submit" value="提交" /></td>
        </tr>
        <tr>
          <td>删除</td>
          <td>
            <p>UID <?php echo zbpform::text('del-uid', '', '90%'); ?></p>
          </td>
          <td><input type="submit" value="提交" /></td>
        </tr>
        <tr>
          <td>列表</td>
          <td colspan="2" class="ul-style"><?php echo odd_Later_ReadJSONList(); ?></td>
        </tr>
        <tr>
          <td>B 站 Cookie</td>
          <td><?php echo zbpform::text('cfg-cookie', $zbp->Config('odd_Later')->cookie, '90%'); ?></td>
          <td>可选。填写<b>自己账号</b>的 Cookie（含 SESSDATA、buvid3 等）可走登录态配额，降低触发风控的概率；留空则为匿名请求，清空后提交即可取消。<input type="submit" value="提交" /></td>
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
    <p><a title="订阅到 Feedly" target="_blank" href="https://feedly.com/i/subscription/feed%2F<?php echo urlencode($url); ?>">订阅到 Feedly</a></p>
    <p><a title="订阅到 Inoreader" target="_blank" href="https://www.inoreader.com/feed/<?php echo urlencode($url); ?>">订阅到 Inoreader</a></p>
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
