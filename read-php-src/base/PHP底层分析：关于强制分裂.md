# [PHP底层分析：关于强制分裂][0]
 
 2016年04月15日发布 

学习需要知其然而知其所以然，PHP底层相关就是这类知识。

前面写过一篇《PHP底层分析：关于写时复制(cow)》：[https://segmentfault.com/a/1190000004926603][12]  
今天来讲讲关于强制分裂的知识，简单来说，强制分裂就是”在引用变量主动赋值前，该变量传值赋值过，就会发生强制分裂。”

话说起来比较绕，看下代码解析吧。

看下面的代码：

![][13]

稍微熟悉 PHP:&引用符号都应该可以看出，output分别为gzchen，傍晚八点半,gzchen。

那么我们来看看以上代码的底层运行流程吧。

众所周知，一个变量就是一个结构体，长成下面这样：

![][14]

每一行都写了注释，此文件在zend.h在PHP源码Zend的目录下。

当代码运行到line：3[$name = ‘傍晚八点半’]的时候，内存中的结构体长这样：

![][15]

当代码运行到line：4[$myName = $name]的时候，结构体变成这样：

![][16]   
运行到line：5[$nameCopy = &$name]和line：[$nameCopy = ‘gzchen’]，是这样：

![][17]

△△△此处，此处，就是此处发生了强制分裂。

当is_ref__gc[引用属性]从0->1，如果refcont_gc>1，那么就会发生强制分裂。伪代码就是：

![][18]

这个就是强制分裂。原本已经经过传值赋值的变量，再次引用赋值出去。被传值赋值的变量就会被分裂出一个结构体，在这里是$myName。

实际开发基本用不到这层原理，但在面试中强制分裂通常会和写时复制(cow)一起考。


[0]: https://segmentfault.com/a/1190000004947637
[12]: https://segmentfault.com/a/1190000004926603
[13]: ./img/bVuVfz.png
[14]: ./img/bVuVfA.png
[15]: ./img/bVuVfG.png
[16]: ./img/bVuVfL.png
[17]: ./img/bVuVfN.png
[18]: ./img/bVuVf5.png