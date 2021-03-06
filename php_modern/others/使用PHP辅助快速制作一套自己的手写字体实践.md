## 使用PHP辅助快速制作一套自己的手写字体实践

来源：[https://zhuanlan.zhihu.com/p/42487713](https://zhuanlan.zhihu.com/p/42487713)

时间：发布于 2018-08-20

## 一、背景

笔者以前在网上看到有民间高手制作字体的相关事迹，觉得把自己的手写字用键盘敲出来是一件很有意思的事情，所以一直有时间想制作一套自己的手写体，前几天在网上搜索了一下制作字体的方法，发现技术上并不是太难，结合了自己PHP方面的开发经验，很快的做出了一套自己的手写字体。

制作字体的流程大致是这样，首先我们需要确定那些字体需要自己写，确定了字体之后将这一批字利用工具做成一个模板，不过汉字的总量非常的多，搜索了一下大概在10万字左右，这个工程量太大，因此我们需要找出一批属于自己常用的字体（大概1700字左右），或者自己所常见到的字体，这个过程就需要用PHP来分析，分析出来之后再将其提取出来，做成模板。

在这篇文章当中笔者将完整的记录制作字体过程，其中会将用到的PHP代码公布出来，方便其他读者使用，也给自己留个备份。

## 二、操作概要

* 提取常用汉字
* 制作字体模板
* 生成字体文件

## 三、提取常用汉字

做一套字体的工作量是比较大的，因为汉字数量比较多，不过我们可以将我们常用的汉字提取出来，优先将这写汉字的做出来，后面不常用的字体空闲时再去累加，这里我们用到了PHP来辅助我们提取常用的汉字。

## 3.1 收集数据

在网络中有各种2000个常用汉字之类的doc文档，但是每个人所用到的却不一样；因此我们需要收集一批自己经常接触的字体数据，比如可以从自己的笔记、博客、聊天数据、通讯录中提取；比如笔者便将以往的笔记、文章、通讯录收集了起来，如下图则是笔者过往的文章列表

![][1]

我们将文章内容复制到txt文件当中，然后保存到某一个文件夹当中，如下图所示

![][2]

## 3.2 去除杂项

收集了文章之后，里面有很多杂项，比如空格和换行，这些内容我们并不需要，如下图所示

![][3]

此时可以通过正则表达式将不需要的内容删除，笔者使用的`匹配非中文`的正则表达式如下:

```

[^\u4e00-\u9fa5]

```

笔者平时开发习惯使用phpstorm这款IDE，因此这里教大家使用此IDE来删除非中文字符；按住键盘`ctrl`+`r`,替换文本内容，然后将正则表达式放入查找项当中，并且勾选`regex`,此时所有非汉字内容会被选中，如下图所示：

![][4]

当笔者点击`Replace all`按钮时，变删除了所有非中文字符，此时我们的内容应该只有一行内容，如下图所示

![][5]

## 3.3 字体去重

在整理好文字之后，我们现在需要对里面的内容进行去重，保证每一个汉字只保留一个，因为我们字体模板每个字只需要写一次就可以；因此可以使用PHP对汉字进行去重，代码如下所示

```php

<?php

//汉字去重函数
function mb_str_split(string $string)
{
    return implode('', array_unique(preg_split('/(?<!^)(?!$)/u', $string)));
}

//将收集的汉字数据读取出来
$word = file_get_contents('ziti/shoulu.txt');
$word .= file_get_contents('ziti/phpsafe.txt');
$word .= file_get_contents('ziti/reming.txt');
$word .= file_get_contents('ziti/2000.txt');
$word .= file_get_contents('ziti/https.txt');
$word .= file_get_contents('ziti/wangwen/wuxian.txt');
$word .= file_get_contents('ziti/wangwen/qixi.txt');
$word .= file_get_contents('ziti/wangwen/qiantan.txt');
$word .= file_get_contents('ziti/wangwen/jiaoyi.txt');

//执行去重
echo mb_str_split($word);

```

当这段代码被执行之后，会返回去重后的结果，笔者执行结果如下图：

![][6]

从图中可以看出，笔者已经得到了一批去重后的文字

## 3.4 统计并排序

去重之后已经得到了一批独一无二的汉字，但是字数实在太多，达到了1730个汉字，可能一下写不完，不过作为开发者当然是要讲究高效率的；所以可以通过PHP来进行优先级的计算，把最常用到字体排在前面，因此笔者需要写一段PHP代码。

## 3.4.1 汉字拆分数组

首先笔者将去重后的字符串拆分成数组，因为汉字比较特殊，所以需要自定义一段代码，参考代码如下:

```php

//把汉字拆分为数组
function ch2arr(string $str)
{
    $length = mb_strlen($str, 'utf-8');
    $array = [];
    for ($i = 0; $i < $length; $i++) {
        $array[] = mb_substr($str, $i, 1, 'utf-8');
    }
    return $array;
}

```

## 3.4.1 排序后筛选

下载笔者需要通过foreach来遍历统计每个字出现的次数，并且安装倒序排序，如果limit大于0，还可以筛选重复次数大于0的汉字，代码如下

```php

function strSelect(string $string, string $word, $limit = 0)
{
    //把字符串分割为数组
    $cnList = ch2arr($string);
    foreach ($cnList as $val) {
        $result[$val] = substr_count($word, $val);
    }

    //重复高的出现在最前
    arsort($result);

    //筛选字符串
    $ret = '';
    foreach ($result as $key => $val) {
        if ($val > $limit) {
//            $ret .= "$key:$val".PHP_EOL;  //查看每个字重复的次数
            $ret .= $key;
        }

    }

    return $ret;
}

```

在前面两个方法写完之后，笔者只需要调用一行代码即可得出最常用的一些字符，也可以筛选结果，调用代码如下：

```php

echo strSelect($str, $allStr, 1);

```

代码执行之后，笔者将会安装汉字出现的次数进行排序，把最常见的字符排在前面，并且筛选出现次数大于1的才返回，返回结果如下图所示：

![][7]

从图中可以看到字体顺序已经发生了很大变化，数量明显少了很多。

[参考代码地址:][22]

```

http://tuchuang.songboy.net/ziti/code.txt

```

## 四、制作字体模板

把自己最常接触的汉字找出来之后，需要制作一套字体模板，这套字体模板的用处是让手写汉字后，顺利的找到对应的汉字，这里需要依靠第三方网站提供的一些功能。

## 4.1 字体文件编码

现在笔者将PHP计算的字符写入到一个txt文件当中，参考命令如下

```

php quchong.php  > result.txt

```

保存之后，还需要将它的编码设置为UTF-8；操作步骤为:首先用windows的记事本打开，然后将文件另存为UTF-8编码的文件，`笔者用mac系统怎么也不行，使用windows很顺利的就完成了，建议使用windows`，如下图所示

![][8]

## 4.2 生成字体模板

现在笔者需要将之前保存的汉字，用固定格式的模板展现出来，后期需要用此模板生成字体文件，这里需要用到一个网站来辅助,网站地址如下

```

http://www.flexifont.com/

```

网站需要注册，注册过程笔者这里将不做描述；在登陆之后点击`我的字体`，可以看到当前的字体模板，选择自定义，参考下图

![][9]

点击自定义之后，笔者能看到一个上传txt文件的表单，如下图所示

![][10]

上传完成之后，笔者回到列表当中，就可以看到刚才创建的字体模板，如下图所示

![][11]

## 4.3 手写字体

笔者将刚才创建的模板下载到电脑当中，并解压该文件，解压后的结果如下图所示

![][12]

这里一定要打开这些图片确认无误，确认这些字和上传的字能对应的上，如果里面的字明显不是刚才上传的，很有可能是你上传文件的编码不正确，笔者生成的字体模板如下图所示

![][13]

确认无误后需要将这几张图片打印下来，最好自己有打印机，笔者之前买过一款惠普的1121打印机，总价格不到200块钱，建议各位读者也买一个，有打印机有时候真的很方便；

打印出来之后，就需要笔者将对应文字意义手写。

## 五、生成字体文件

手写字体是一个比较辛苦的过程，手写完成之后还有一些步骤，如果读者比较熟悉用手机编辑图片，那么这一步很快就能完成，如果不熟悉，就详细的看一下笔者的处理方法吧。

## 5.1 拍照

首先需要将刚才手写的文字进行拍照，拍照的时候注意尽量平着拍，需要把4个黑边拍进去；笔者使用的是iPhone手机，因此非常建议使用iPhone的读者将相机的网格线功能打开，因为这样就可以看出手机是否是平着拍的，在`设置`->`相机`->`网格线`，参考如下图

![][14]

设置好之后，笔者再次打开相机，就能看到网格线，如下图所示

![][15]

中间的十字架如果是黄颜色的，说明笔者当前是平着拍摄的，这样拍照的时候图片就不会那么斜了。

## 5.2 处理图片

虽然在拍照的时候已经很用心的去拍摄，但拍的过程当中难免有一些不满意，这个时候可以用手机简单处理一下，笔者这里依然以iPhone手机为例

打开相册查看图片的右上方有一个编辑功能，如下图所示

点击编辑之后，在左下角有一个方块按钮，点击之后可以对图片进行放大缩小的跳转，以及旋转，对齐等功能，读者可以自己去操作一番，将图片尽量调整到理想的状态。

笔者处理后的效果如下图所示

![][16]

## 5.3 上传并生成字体

现在打开字体上传页面，把笔者已经处理过的图片上传到手写体网站当中了，URL地址如下

```

http://www.flexifont.com/flexifont-chn/add_font/

```

如下图所示，手写体站点的一些规则

![][17]

需要记住别选择错模板(笔者一开始没选择对，还以为系统出问题了)，然后把字体上传，上传完成之后，可以点击查看队列，看看当前的字体处理状态，URL地址如下

```

http://www.flexifont.com/flexifont-chn/queuers/

```

笔者上传字体后，不到1分钟便已经处理完成，处理完成之后，可以在我的字体下方看到字体列表，如下图所示

![][18]

## 六、使用字体

当字体生成完成之后，笔者安装字体文件即可

## 6.1 安装字体

安装字体在mac下和widnows下都非常简单，首先看看mac下安装方法，下载字体之后，可以直接双击字体文件，会看到如下图

![][19]

笔者直接点击安装字体就可以了

再说说windows下安装,其实也只需要双击字体文件即可，然后点击安装，如下图所示

![][20]

不过笔者在电脑在安装字体的时候出现了错误，提示字体无效，于是我换了一种方式；`右击鼠标`->`为所有用户安装`又好了，原因未知，如果读者出现这种情况也可以试试。

## 6.2 在WPS中使用

笔者很多时候都会使用到word文件，读者喜欢用wps，那么如何在WPS中使用“`轻松体`”呢，其实非常简单，在随便输入一些文字之后，在上方选择“`轻松体`”即可，效果如下图所示

![][21]

如果发现某个字体不是你手写的风格，那应该是这个字体不再你的字体模板当中，你可以生成一个新的模板，然后合并之前的字体即可。

## 6.3 补充

在手写体当中默认的模板也不错，读者也可以去尝试一下，另外不仅仅汉字可以做手写体，符号也可以。

-----

作者：汤青松

微信：songboy8888

日期：2018年8月20号

[22]: https://link.zhihu.com/?target=http%3A//tuchuang.songboy.net/ziti/code.txt

[1]: ../img/v2-71e61fb02b85981a04a36f33a505b115_r.jpg
[2]: ../img/v2-e7f2d2f15ff29001ac8d207b82fd96c9_r.jpg
[3]: ../img/v2-53ba39ae6ae89e9e1c0a0d6d7eb90a4b_r.jpg
[4]: ../img/v2-17bc22e012ee5a7cfc0284dbe151d8dc_r.jpg
[5]: ../img/v2-0fc9fef12eb4ed0eece5a194e2ccdd3a_r.jpg
[6]: ../img/v2-f7fa78bf1fa046c74fb31f072b704f88_r.jpg
[7]: ../img/v2-427ec60f8fe3e0fdf390c092334f7160_r.jpg
[8]: ../img/v2-212e124cac8dea8850d3f338cb698c9f_r.jpg
[9]: ../img/v2-80eabddd15cbe2579ffa84e01c14fd73_r.jpg
[10]: ../img/v2-8a0d066678daf0f09c16e18a02118084_r.jpg
[11]: ../img/v2-47aee2e62a935037411be422c3428adb_r.jpg
[12]: ../img/v2-a6e20d1b7bc5b78c51f3089bdd3ce182_r.jpg
[13]: ../img/v2-9e22bc7de0d427171bd7e68b6c5ea19e_r.jpg
[14]: ../img/v2-aeb72735096ebfbe4fb271f885966224_r.jpg
[15]: ../img/v2-079248bffe1532ba2dba468882db89a5_r.jpg
[16]: ../img/v2-606a69279f55bd9b8a2af94fa957d6b7_r.jpg
[17]: ../img/v2-d3b02cee2bb0204cb9f9a1d8c6aa9fb5_r.jpg
[18]: ../img/v2-4dbe871be811e7910c7c2e3214e402a5_r.jpg
[19]: ../img/v2-f33285934a8895ea4654f7e3785b875e_b.jpg
[20]: ../img/v2-c9ce9cc8b27fda6e2a71cd9465a1cbfc_r.jpg
[21]: ../img/v2-54cb5e35e5bcc399f0380308931c824b_r.jpg