

```php 
$str = "php编程";
if (preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$str)) {
    print("该字符串全部是中文");
} else {
    print("该字符串不全部是中文");
}

$ret = preg_match_all("/[\x{4e00}-\x{9fa5}]/u",$str,$match); //  匹配汉字内容并捕获存入 $match 
```

```
[\x{3002}\x{ff1b}\x{ff0c}\x{ff1a}\x{201c}\x{201d}\x{ff08}\x{ff09}\x{3001}\x{ff1f}\x{300a}\x{300b}]

匹配所有中文标点符号
。 ；  ， ： “ ”（ ） 、 ？ 《 》 


[\x{3002}] 。
[\x{ff1b}] ；
[\x{ff0c}] ，
[\x{ff1a}] ：
[\x{201c}] “
[\x{201d}] ”
[\x{ff08}] （
[\x{ff09}] ）
[\x{3001}] 、
[\x{ff1f}] ？
[\x{300a}] 《
[\x{300b}] 》
```


[\x{4e00}-\x{9fa5}\x{3002}\x{ff1b}\x{ff0c}\x{ff1a}\x{201c}\x{201d}\x{ff08}\x{ff09}\x{3001}\x{ff1f}\x{300a}\x{300b}]{26}\r\n[\x{4e00}-\x{9fa5}]{1,}

[^\x{3002}\x{ff1b}\x{ff0c}\x{ff1a}\x{201c}\x{201d}\x{ff08}\x{ff09}\x{3001}\x{ff1f}\x{300a}\x{300b}]

[\x{3002}\x{ff0c}\x{ff1f}！]$

```
^[\x{4e00}-\x{9fa5}\x{3002}\x{ff0c}\x{3001}\x{ff1a}\x{ff1b}\s]{1,25}[\x{3002}\x{ff1f}\x{ff01}]$   匹配应该分段的句子

^[\x{4e00}-\x{9fa5}\x{3002}\x{ff0c}\x{3001}\x{ff1a}\x{ff1b}\x{201c}\x{201d}\x{ff01}\x{ff1f}\x{300a}\x{300b}\x{ff1a}\x{2018}\x{2019}\x{ff08}\x{ff09}]{1,26}$

^[\x{4e00}-\x{9fa5}\x{3002}\x{ff0c}\x{3001}\x{ff1a}\x{ff1b}\x{201c}\x{201d}\x{ff01}\x{ff1f}\x{300a}\x{300b}\x{ff1a}\x{2018}\x{2019}\x{ff08}\x{ff09}]{27,}$

```