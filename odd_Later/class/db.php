<?php
class odd_Later extends Base
{
  public static $tableX = '%pre%odd_Later';
  public static $datainfoX = array(
    'ID' => array('cl_ID', 'integer', '', 0),
    'Url' => array('cl_Url', 'string', 255, ''),
    'Cate' => array('cl_Cate', 'string', 37, ''),
    'Hash' => array('cl_Hash', 'integer', "", ''),
    'Time' => array('cl_Time', 'integer', '', 0),
    'State' => array('cl_State', 'integer', '', 0)
  );
  public function __construct()
  {
    global $zbp;
    parent::__construct($zbp->table['odd_Later'], $zbp->datainfo['odd_Later'], __CLASS__);
    $this->Time = time();
    $this->IP = GetGuestIP();
  }
  public function GenCategoryByURL($url)
  {
    $match = array();
    preg_match('/https?:\/\/(?<host>[^\/]+)/', $url, $match);
    if (isset($match['host'])) {
      switch ($match['host']) {
        case 'www.bilibili.com':
        case 'space.bilibili.com':
          return "bilibili";
          break;
        case 'movie.douban.com':
          return "douban";
          break;
        default:
          return "default";
          break;
      }
    }
    return "default";
  }
  // odd_Later::CreateTable()
  public static function CreateTable()
  {
    global $zbp;
    if (!$zbp->db->ExistTable($zbp->table['odd_Later'])) {
      $sql = $zbp->db->sql->CreateTable($zbp->table['odd_Later'], $zbp->datainfo['odd_Later']);
      $zbp->db->QueryMulit($sql);
      $zbp->SetHint('tips', "数据表已创建【odd_Later】");
    }
  }
}
$GLOBALS['table']['odd_Later'] = odd_Later::$tableX;
$GLOBALS['datainfo']['odd_Later'] = odd_Later::$datainfoX;
