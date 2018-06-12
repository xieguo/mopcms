<?php
if (!defined('IN_MOPCMS')) {
	exit ('Access failed');
}
/**
 * 100*通用提示
 * 101*管理员相关
 * 102*栏目相关
 * 103*模型相关
 * 104*内容/定位点相关
 * 105*系统设置
 * 106附件上传相关
 * 107*文件安全校验、管理
 * 108数据库管理
 * 109评论相关
 * 110级联数据相关,
 * 111静态文件生成相关
 * 112插件相关
 */
$prompt_main = array (
	100 => 'success',
	101 => '初始化配置文件("/data/config.php")不存在',
	102 => 'MOPCMS运行需PHP版本5.3以上，您PHP版本过低',
	10001 => '传递参数不正确',
	10002 => '必填参数不能为空',
	10003 => '验证码不正确',
	10004 => '读取数据不存在',
	10005 => '表单提交时效已过期，请刷新重试！',
	10006 => 'ID不能为空！',
    10007 => '{$name}不能为空',
    10008 => '您要发布的内容中不能含有{$name}违禁信息！',
    10009 => '对不起，你没有权限执行此操作！',
    10010 => '密码不合法，请使用[0-9a-zA-Z_]内的字符！',
    10011 => '用户名不合法，请使用[0-9a-zA-Z_]内的字符！',
    10012 => '用户名已存在',
    10013 => '修改成功',
    10014 => '添加成功',
    10015 => '必须选择一个或多个文档！',
    10016 => '删除成功',
    10017 => '所进行操作不存在',
    10018 => '没有提交任何数据，操作失败',
    10019 => '操作频率有点高，请先休息一下',
    10020 => '您提交的{$title}已经存在',
    10021 => '此信息尚未通过审核，禁止访问',
    10022 => '模板不存在',
    10023 => '文件夹创建失败',
    10024 => '更新成功',
    10025 => '文件夹创建失败',
    10026 => '数据库配置数据不存在',
    10027 => '数据库连接失败',
    10028 => '没有指定要连接的数据库',
    10029 => '相应类加载失败',
    10030 => '相应处理方法加载失败',
    10031 => '保存成功',
    10032 => '登陆成功',
    10033 => '加载文件不存在',
    10034 => '发送成功',
	10101 => '密码不正确，登陆失败',
	10102 => '管理员分组名称不能为空',
    10103 => '你无操作此模型文档的权限',
	10200 => '成功更改一个分类！',
	10201 => '你无权更改本栏目！',
	10202 => '更新排序成功',
	10203 => '成功创建一个分类！',
	10204 => '添加栏目时创建相应文件夹失败',
	10205 => '栏目绑定域名须以http开头',
	10206 => '成功删除一个栏目',
	10207 => '栏目名称不能为空',
	10208 => '内容模型必须选择',
	10209 => '请填写文件保存目录',
	10210 => '移动栏目失败',
	10211 => '不能将父栏目移至其子栏目中',
	10212 => '所选栏目不存在',
	10213 => '成功移动目录！',
	10214 => '同一栏目不能合并！',
	10215 => '栏目：《{$name}》含有子栏目，不能进行合并操作！',
	10216 => '成功合并指定栏目！',
	10217 => '不同模型栏目不能合并！',
	10301 => '模型名称不能为空',
	10302 => '默认栏目ID不能为空',
	10303 => '此模型不存在!',
	10304 => '更改成功',
	10305 => '附加表表名不能为空',
	10306 => '此数据表已存在，请换一个表名',
	10307 => '此数据表已存在，创建数据表失败!',
	10308 => '模型增加成功',
	10309 => '系统模型不允许删除',
	10310 => '此字段名已存在，请换一个字段名',
    10311 => '此字段名为系统字段名，请换一个字段名',
    10312 => '成功修改一个字段',
    10313 => '成功创建一个字段',
    10314 => '成功删除一个字段',
    10315 => '此模型尚未启用，提交失败',
    10316 => '此字段不存在',
    10317 => '自定义表单名称不能为空',
    10318 => '数据库表名称不能为空',
    10319 => '自定义表单添加成功',
    10320 => '自定义表单不存在',
    10321 => '此自定义表单不允许发布',
    10322 => '此自定义表单已关闭前台展示',
    10323 => '您无此自定义表单的操作权限',
    10324 => '',
    10401 =>'模型ID不能为空',
    10402 =>'所选栏目与对应模型不一致，提交失败',
    10403 =>'不支持会员发布',
    10404 =>'必须选择所属栏目',
    10405 =>'对不起，你没有此栏目文档的操作权限！',
    10406 =>'文档修改成功',
    10407 =>'文档添加成功',
    10408 =>'文档审核成功',
    10409 =>'文档取消审核成功',
    10410 =>'文档删除成功',
    10411 =>'文档推荐成功',
    10412 =>'文档更新成功',
    10413 =>'定位点坐标获取失败，请重试',
    10414 =>'报名已达名额上限，报名失败',
    10415 =>'报名尚未开始',
    10416 =>'报名已经结束',
    10417 =>'谢谢您的参与，您已经报名成功',
    10418 =>'专题报名字段最多只能设置35个',
    10419 =>'生成文件地址请填写一个以.html结尾的文件名称',
    10420 =>'数据保存成功,但静态页面生成失败',
    10421 =>'',
    10422 =>'',
    10423 =>'',
    10506 =>'该变量名称已经存在!',
    10502 =>'数据已入库，但配置文件{$name}不支持写入，无法缓存系统配置参数！',
    10503 =>'设置成功',
    10504 =>'变量名不能为空并且必须为[a-z_]组成!',
    10505 =>'管理员删除成功',
    10506 =>'水印设置保存成功',
    10507 =>'缓存更新成功',
    10508 =>'删除日志的时间点不能为空',
    10509 =>'升级成功',
    10510 =>'',
    10511 =>'',
    10601 =>'此文件类型禁止上传',
    10602 =>'此文件包含可疑信息，上传失败',
    10603 =>'你所上传的图片类型不在许可列表，请上传{$name}类型！',
    10604 =>'获取不到图片的相应信息，上传失败',
    10605 =>'上传的文件必须为flash文件！',
    10606 =>'你所上传的文件类型必须为：{$name}',
    10607 =>'上传附件失败',
    10608 =>'您上传的图片过多，上传失败！',
    10609 =>'',
    10610 =>'',
    10611 =>'',
    10701 =>'文件写入失败',
    10702 =>'成功保存一个文件',
    10703 =>'文件名已经存在',
    10704 =>'文件夹名称不能为空',
    10705 =>'此文件不可写，名称修改失败',
    10706 =>'成功更改一个文件名！',
    10707 =>'相应路径地址不可写，文件夹创建失败',
    10708 =>'文件夹创建成功',
    10709 =>'文件或文件夹名称不能为空',
    10710 =>'{$name}删除成功',
    10711 =>'移动目标地址不能为空',
    10712 =>'原地址不可写，移动失败',
    10713 =>'目标地址不能含有".."',
    10714 =>'移动目标地址与原地址不能相同',
    10715 =>'文件移动成功',
    10716 =>'{$name}文件上传失败',
    10717 =>'成功上传 {$num} 个文件到: {$filepath}',
    10718 =>'更新完成',
    10719 =>'同步成功',
    10720 =>'文件删除失败',
    10721 =>'',
    10722 =>'',
    10723 =>'',
    10724 =>'',
    10801 =>'你没选中任何表！',
    10802 =>'完成所有数据备份！',
    10803 =>'没指定任何要还原的文件!',
    10804 =>'成功还原所有的文件的数据!',
    10805 =>'优化表：{$tablename}失败，原因是：{$reason}',
    10806 =>'优化表成功！',
    10807 =>'没查到任何表，操作失败',
    10808 =>'修复表：{$tablename}失败，原因是：{$reason}',
    10809 =>'修复表成功！',
    10810 =>'',
    10811 =>'',
    10900 =>'评论内容不能为空或过短',
    10901 =>'系统已经关闭评论功能！',
    10902 =>'此文档禁止评论',
    10903 =>'游客禁止评论！',
    10904 =>'您刚刚评论过，请稍后再评论！',
    10905 =>'',
    10906 =>'',
    11001 =>'标识符不能为空',
    11002 =>'此标识符已被占用，请重新命名',
    11003 =>'',
    11004 =>'',
    11005 =>'',
    11100 =>'完成所有列表更新！',
    11101 =>'所选栏目不能为空！',
    11102 =>'静态页面生成失败',
    11103 =>'',
    11104 =>'',
    11105 =>'',
    11106 =>'',
    11107 =>'',
    11200 =>'TOKEN不正确或已过期！',
    11201 =>'所访问插件未安装或不存在',
    11202 =>'关闭成功',
    11203 =>'开启成功',
    11204 =>'',
    11205 =>'',
    11206 =>'安装失败',
    11207 =>'此应用已安装，无需重复安装',
    11208 =>'文件{$file}创建失败，安装失败',
    11209 =>'安装成功，请进后台查看',
    11210 =>'卸载成功',
    11211 =>'安装所需文件不存在，安装失败',
    11212 =>'下载密钥不正确，下载失败',
    11213 =>'下载密钥已过期，下载失败',
    11214 =>'下载的应用不存在，下载失败',
    11222 =>'此应用已经是最新版本，无需更新！',
    11223 =>'更新成功',
    11224 =>'',
    11225 =>'',
);