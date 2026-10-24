# 「奇怪插件」稍后查看

一个收集 B 站视频链接的「奇怪插件」 For Z-BlogPHP

## 使用

因为 B 站的抓取限制，现改为反向提交数据到插件内；

一、需要配合以下「GM_脚本」使用；

GitHub: [点击安装](https://github.com/wdssmq/userscript/blob/main/dist/later-url.user.js?raw=true "点击安装 「水水」收集各种待看链接到远程服务器 - GitHub") 「[在线查看](https://github.com/wdssmq/userscript/blob/main/dist/later-url.user.js "在线查看 dist 源码")」

CDN: [点击安装](https://cdn.jsdelivr.net/gh/wdssmq/userscript@main/dist/later-url.user.js "点击安装 「水水」收集各种待看链接到远程服务器 - CDN") 「[更新缓存](https://purge.jsdelivr.net/gh/wdssmq/userscript@main/dist/later-url.user.js "点击更新 CDN 缓存")」

二、安装并启用「GM_脚本」后，从插件后台获取「推送地址」；

三、进入 `https://space.bilibili.com/<uid>/upload/video` ← 「投稿」标签页内；

 - 点开右下角 `later` 图标面板，打开「ZBP 插件」的开关并填写「第二步」获取到的地址；
 - 「我的视频」后边有个「复制」按钮，复制用户信息填回插件后台提交；采用白名单机制，仅添加的用户视频会被提交到插件内；
 - 刷新 B 站页面，期间提示远程请求时选「全部允许」；

## 示例

> [稍后查看][bili]水水的演示站
>
> [https://demo.wdssmq.com/feed.php?oddLater](https://demo.wdssmq.com/feed.php?oddLater "[稍后查看][bili]水水的演示站")

## 投喂支持

爱发电：[https://afdian.com/a/wdssmq](https://afdian.com/a/wdssmq "沉冰浮水正在创作和 z-blog 相关或无关的各种有用或没用的代码 | 爱发电")

哔哩哔哩：[https://space.bilibili.com/44744006](https://space.bilibili.com/44744006 "沉冰浮水的个人空间\_哔哩哔哩\_bilibili")「投币或充电」「[大会员卡券领取 - bilibili](https://account.bilibili.com/account/big/myPackage "大会员卡券领取 - bilibili")」

RSS 订阅：[https://feed.wdssmq.com](https://feed.wdssmq.com "沉冰浮水博客的 RSS 订阅地址") 「[「言说」RSS 是一种态度！！](https://www.wdssmq.com/post/20201231613.html "「言说」RSS 是一种态度！！")」

在更多平台关注我：[https://www.wdssmq.com/guestbook.html#其他出没站点和信息](https://www.wdssmq.com/guestbook.html#%E5%85%B6%E4%BB%96%E5%87%BA%E6%B2%A1%E5%9C%B0%E7%82%B9%E5%92%8C%E4%BF%A1%E6%81%AF "在更多平台关注我")

更多「小代码」：[https://cn.bing.com/search?q=小代码+沉冰浮水](https://cn.bing.com/search?q=%E5%B0%8F%E4%BB%A3%E7%A0%81+%E6%B2%89%E5%86%B0%E6%B5%AE%E6%B0%B4 "小代码 沉冰浮水 - 必应搜索")

<!-- ##################################### -->
