v1.2.13
---------
- 添加
    - 模板依赖检查
    - 注释文档自动生成
    - 绝对命令调用
    - 简易多层回滚
    - 表列处理函数
    - 路由正向解析
- 优化
    - 模板资源搜索
    - SQL语句构建
- 删除
    - DTO对象
    - 内置DOCME

v1.2.12
----------
- 修复
    - Route 错误
    - Query listWhere错误
    - Route Mapping
- 添加
    - 路由缓存
    - 错误处理
- 修正
    - URL构建
    - Cookie操作

v1.2.11-release-1c6b049 
-------------
- 添加 
    - table 函数
    - 大小写不敏感的URL匹配模式

- 删除
    - DAO类

- 调整
    - json可输出中文


v1.2.10-release-88cd868
-------------
- 添加
    - SSL-SMTP 邮件发送
    - 模板信息获取类
    - 更多模板目录
    - asset 函数
    - ZipHelper 
    
- 修复
    - Autoload 平台差异
    - 文件夹非必需不自动创建
    - 模板信息处理问题
    - Storage删除文件夹问题
    - 行末标签问题

- 调整
    - DAO -> Table
    - 模板名唯一化
    - 模板编译顺序
    - 动态编译

- 废弃
    - DAO

v1.2.9-release-2992ca3
-------------
- 添加
    - 可配置资源服务器

- 修改
    - 重要：**DISALLOW_MODULES -> DISABLE_MODULES** 请保证正确禁用模块
    - 模块加载方式调整

- 优化
    - 日志记录
    - 路由缓存
    - 应用加载

v1.2.8-release-870602e
-------------
- 添加
    - 一键编译模板
    - 日志组合压缩

- 优化
	- 日志实时输出
	- URL输出优化
	- 内存消耗优化
	
- 修正
	- 模板@static错误

v1.2.7-release-aa9e25b
-------------
- 添加
    - 外部静态资源路径加密
    - 路由函数添加若干操作
    - 路由表达式支持默认值
    - 添加若干模板函数[@_ @file]
    - 添加Office MIME类型
    - 路由自定义禁用，取消前缀
    - DAO扩展操作
    - 模板扩展显示写入
    - Query计数
    
- 修复
    - Host错误
    - 修饰模板静态资源

- 优化
    - 路由处理函数


v1.2.6-release-93e8ae6
--------
- 修复
    - Linux 平台错误修复
    - 模块管理工具
    - 日志输出若干错误
    - 应用启动顺序
    - 缓存文件保存错误

- 添加
    - Hooks工具
    - u 函数新功能
    - 模板新功能
    - DAO功能扩展
- 删除
    - console工具

v1.2.5
---------
- 修复
    - 修复若干错误

- 添加
    - DAO强化

v1.2.4 
-----------
- 添加
    - 页面全局HOOK显示
- 修复
    - 若干BUG

v1.2.3-dev
------------
- 修正
    - Suda管理面板
    - SQL语句日志纪录
    - 其他细节修复
- 添加
    - 添加DAO对象
    - 添加Archive对象：用于数据处理

- 删除
    - 默认Demo的DTO文件

v1.2.2-dev
------------
- 修正
    - 更新管理面板(修复BUG,添加密码验证)
    - 路由管理模块显示错误
    - 调试时期关闭ETAG生成
    - 修改日志纪录规则，防止大量请求导致服务区内存消耗过多
    - 调整Debug输出，增强日志的性能
    - 管理面板可以设置只允许本地登陆

v1.2.1
-----------
- 添加
    - 非调试模式下的错误提示简化
    - 支持DTO使用SQL创建语句
- 修正
    - 响应跟多信息
    - 开发面板版本调整

v1.2.0
----------
- 添加
    - 网页控制面板
- 废弃
    - 控制台控制命令
- 修复
    - 删除文件夹
    - 数据库备份

v1.2.1-dev 1.2.1 预览版
--------------------------
预览点：添加路由网页管理控制

- 添加
    - 模块自带前缀
    - 错误日志trace的记录

- 优化
    - 添加运行时间监控

- 删除
    - Response->display系列
    - 默认缓存模板

- 废弃
    - 控制台工具
    
- 修正
    - 模板渲染器
    - 自定义路由匹配
    - 渲染模板出错无法显示错误报告的错误

v1.2.0
---------
- 添加
   - 邮箱发送：添加SMTP并且支持DNS查询
   - 模块自动查找路径
   - 模块信息：添加 module.json 记录模块信息
   - 路由：路由前缀
    
- 优化
   - 模板渲染：一类模板一次加载
   - 日志记录：调整日志输出
   - 错误输出：统一错误报告
   - 系统运行：添加详细的日志
   - 网页请求：添加更多参数
   - 数据库：创建、备份、恢复优化
   
- 修复
	- 日志输出：Debug 日志规则
	- Cookie输出设置
	- 模板资源引用

- 弃用
	- 模块路径设置，改为自动查询
	- 弃用未捕获错误接口，统一为异常

1.1.3
---------
- 添加
    - 全局Hook Listener
    - 应用自定义模板函数
    - 中文模板函数和变量名