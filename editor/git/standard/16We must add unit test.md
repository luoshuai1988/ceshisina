`发生过程`
=====
* 修改旧的功能，没有添加单元测试
 
`危害`
=====
1.危害范围
* 日常开发
* 日常发布
* 紧急修复

2.危害认识
* 造成该模块的代码上线后有分险
* 如果有问题不能在代码上线前确定问题
* 其他人修改相关文件，没有单元测试可能发生未知错误，对测试、上线等造成了不必要的麻烦。

`解决方案`
=====
* 每次修改新旧功能都要加上单元测试

`注意`
=====
* 单元测试`不`发现未知问题
* 未知问题要`靠人`来解决
* 解决问题`后`添加单元测试