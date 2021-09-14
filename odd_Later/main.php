<?php
require '../../../zb_system/function/c_system_base.php';
require '../../../zb_system/function/c_system_admin.php';
require __DIR__ . "/class/json.php";
require __DIR__ . "/class/http.php";
require __DIR__ . "/class/base.php";
$zbp->Load();
$action = 'root';
if (!$zbp->CheckRights($action)) {
  $zbp->ShowError(6);
  die();
}
if (!$zbp->CheckPlugin('odd_Later')) {
  $zbp->ShowError(48);
  die();
}
$act = GetVars('act', 'GET');
$suc = GetVars('suc', 'GET');
if ($act == 'save') {
  CheckIsRefererValid();
  $addItem = array("cycle" => 7);
  $delItem = array();
  foreach ($_POST as $key => $val) {
    $cVal = trim($val);
    if (substr($key, 0, 4) === "add-" && $cVal !== "") {
      $cKey = substr($key, 4 - strlen($key));
      $addItem[$cKey] = $cVal;
    }
    if (substr($key, 0, 4) === "del-" && $cVal !== "") {
      $cKey = substr($key, 4 - strlen($key));
      $delItem[$cKey] = $cVal;
    }
  }
  if (odd_Later_AddItem($addItem)) {
    $zbp->SetHint('good');
  } else {
    $zbp->SetHint('tips', "输入不符合");
  }
  Redirect('./main.php' . ($suc == null ? '' : '?act=$suc'));
}

$t = intval(137 * 24 * 3600) + time();
$url = "{$zbp->host}feed.php?oddLater";
InstallPlugin_odd_Later();
$blogtitle = '稍后查看';
require $blogpath . 'zb_system/admin/admin_header.php';
require $blogpath . 'zb_system/admin/admin_top.php';
?>
<div id="divMain">
  <div class="divHeader"><?php echo $blogtitle; ?> <small><a href="main.php" title="刷新">刷新</a></small></div>
  <div class="SubMenu">
    <a href="main.php" title="首页"><span class="m-left m-now">首页</span></a>
    <?php require_once "about.php"; ?>
  </div>
  <style>
    .ul-style li {
      float: left;
      width: 33%;
    }
  </style>
  <div id="divMain2">
    <form action="<?php echo BuildSafeURL("main.php?act=save"); ?>" method="post">
      <table width="100%" class="tableBorder">
        <tr>
          <th width="10%">项目</th>
          <th>内容</th>
          <th width="45%">说明</th>
        </tr>
        <tr>
          <td>添加/修改</td>
          <td>
            <p>UID:<?php echo zbpform::text("add-uid", "", "90%"); ?></p>
            <p>Name:<?php echo zbpform::text("add-name", "", "90%"); ?></p>
            <p>Type:<?php echo zbpform::text("add-type", "up", "90%"); ?></p>
            <p>所有项目必填</p>
          </td>
          <td><input type="submit" value="提交" /></td>
        </tr>
        <tr>
          <td>删除</td>
          <td>
            <p>UID:<?php echo zbpform::text("del-uid", "", "90%"); ?></p>
          </td>
          <td><input type="submit" value="提交" /></td>
        </tr>
        <tr>
          <td>列表</td>
          <td colspan="2" class="ul-style"><?php echo odd_Later_ReadJSONList(); ?></td>
        </tr>
      </table>
    </form>
    <h3>
      RSS直链：<?php echo odd_Later_a($url, "当前地址", "点击查看"); ?>
    </h3>
    <p><a title="订阅到Feedly" target="_blank" href="https://feedly.com/i/subscription/feed%2F<?php echo urlencode($url); ?>">订阅到Feedly</a></p>
    <p><a title="订阅到Inoreader" target="_blank" href="https://www.inoreader.com/feed/<?php echo urlencode($url); ?>">订阅到Inoreader</a></p>
    <p><a title="订阅到FeedsPub" target="_blank" href="https://feeds.pub/feed/<?php echo urlencode($url); ?>">订阅到FeedsPub</a></p>
    <!-- <h4>－－－－下边是推广链接－－－－</h4> -->
  </div>
</div>

<?php
function odd_Later_a($href, $title, $text = "")
{
  if (empty($text)) {
    $text = $href;
  }
  return "<a href=\"{$href}\" title=\"{$title}\">$text</a>";
}
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>
