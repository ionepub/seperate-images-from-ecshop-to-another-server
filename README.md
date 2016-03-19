# 分离ECShop的图片资源到其他服务器

因项目需要，决定将ECShop的图片资源分离到另一台图片服务器上，并对它单独做加速。

> 查了一下关于图片分布式存储的资料，基本方法可以归结为以下几类：

1. 通过FTP传输
2. 通过分布式文件管理系统进行同步
3. 因使用的ECShop后台编辑器改为了百度编辑器，而百度编辑器有一个版本是用七牛云存储的，可以考虑将图片存储到七牛上
4. 使用ajax模拟表单提交，将图片上传到目标服务器上
5. 使用php的curl函数模拟表单提交

因为本身项目不大，所以放弃了方法2，方法1还要部署FTP，觉得麻烦也放弃了，公司的项目数据文件还是放在自己的服务器上比较好，基于此，方法3也放弃。

最终思考之后决定：绝大多数地方用php的curl方法，局部使用ajax加强体验。

关于ueditor，吐槽一下，在这个项目上使用的是1.3.6版本的，现在官网上的文档都是1.4.0+的，1.4+有比较大的改动，官方文档上也写了如何设置远程地址，但对于只在项目开始时配置一下其他时候不管的我来说，想知道怎么设置很费劲，而且官网上也没有关于版本升级的方案，总之，文档模模糊糊的，不够清晰，果断放弃升级、配置。

### 图片服务器修改

> 不管使用方法4还是5，都需要图片服务器有php环境，用以接收文件

1. 添加 uploadFile.php 文件到图片服务器根目录，并在根目录下创建 logs 文件夹，作为日志目录
2. 编辑 uploadFile.php 文件，设置第7行的 `access_key` ，用来进行简单的验证
2. 分别复制 bdimages(ueditor文件上传目录)、data、images、themes（主题目录，可不传）目录到图片服务器根目录下
 
### 主服务器修改

1. 添加 RemoteFileUp.class.php 文件到主服务器 includes 目录下，并将其中的 $cus_remote_file_target 替换为目标图片服务器地址，设置 $cus_remote_file_key 为上面设置的 access_key 值
2. 【ueditor修改】修改ueditor.config.js 文件中的 imagePath为空，使上传的图片在编辑器里不会加上当前主服务器网址

`,imagePath: ""` 

3. 【ueditor修改】修改php目录下的imageUp.php文件，在第67行左右[$info = $up->getFileInfo();] 后添加如下代码：
 
```php
    ...
    $info = $up->getFileInfo();

    //上传图片到远程服务器
    include_once dirname(__FILE__) . '/../../remoteFileUp.php';
    $CusRemoteFile = new CustomRemoteFile();
    $info['url'] = $CusRemoteFile->cus_remote_editor_file($info['url']);
    
    ...
```

4. 【数据库修改】为后台能统一设置图片服务器地址，在`ecs_shop_config`表 中添加一条记录：
![ecs_shop_config](https://dn-shimo-image.qbox.me/kCLL9RAPweQB0xkL.png!thumbnail "ecs_shop_config")
此时要获取图片服务器地址，只需要使用 `$GLOBALS['_CFG']['site_url']` 即可

5. 【语言文件修改】打开 /languages/zh_cn/admin/shop_config.php 在末尾或者适当位置添加一行代码
```php
$_LANG['cfg_name']['site_url'] = "网店图片资源地址";
```
重新打开后台系统设置页面，即可看到刚刚设置的这个site_url了。

6. 网店最主要的是产品图片资源较多，
