#Model，存放常用的数据逻辑类。
调用方式：
$clsAdminUsers = model()->getModel('admin_users');
得到一个Model\Admin\Users类的实例。

也可以：
$clsAdminUsers = \Model\Admin\Users::init();