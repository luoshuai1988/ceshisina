# [API开发第二篇：PHP的设计模式之工厂模式][0]


 2015-02-05 23:15  1168人阅读 

版权声明：本文为博主原创文章，未经博主允许不得转载。

以前写代码老觉得，搞那么多乱七八槽的设计模式干嘛啊，这不是自己找罪受嘛。现在在这次的API开发过程中才晓得设计模式的厉害，真的是境界不到，永远不能领悟呀。还好坚持编码这么久，终于进入设计模式的运用了，算是一个进步。OK，废话不多说了，进入今天的主题，[PHP][10](面向对象)的基础模式有三：**工厂模式、单例模式、注册模式**。今天想诉说工厂模式。

工厂模式的作用：不知道为什么用，就像一个人深藏千万两万金，却不知道黄金可以让他富可敌国一样。工厂模式，顾名思义就是一个工厂，这个工厂是生产类对象这个产品的。以前程序员需要对象时，都是new一个嘛，现在科技发达了，需要对象，咱们工厂去造一个嘛。以前new不是好好的吗？干嘛要去工厂，直接自己手工生产多方便，哪儿需要哪儿new，通过工厂还要经过一次中介，好麻烦。对，就是这个看似麻烦的过程就是它的作用，想一想，如果你有一个类，在你的项目中有 **50+**个地方需要使用，OK，你就需要new50次，是的，有童靴会说，你用工厂方法还是要调用50次啊，没错，但是现在想一想，我这个类，构造方法变换了，需要多初始化一个参数，或者少初始化一个参数，如果你是直接new的，恭喜你，你就要 **改50+**次，而我，只需要改改这个工厂的生产过程，也就是 **只修改一个地方**。

现在明白了，这就是为什么有的人加班到苦逼，还是一个月只有四五千，有的人活的潇潇洒洒，月薪上万，革命尚未成功，咱们一起努力吧。

**工厂模式简单代码：**

**需要被工厂生产的类：**
```php
class Database {
    public function where($str){
        echo $str.'<br />';
        
        return $this;
    }
}
```
**工厂类：**
```php
class Factory {
    
    public static function createInstance(){
        $instance = new Database();
        
        return $instance;
    }
}
```
  
**外部调用：**

    $db = Factory::createInstance();
    $db ->where( 'where' )->order( 'order' );

[0]: /hel12he/article/details/43540145
[10]: http://lib.csdn.net/base/php
[11]: #