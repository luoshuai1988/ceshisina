# php设计模式六大原则（1）-单一责任原则 


  
**定义**

不要存在多于一个导致类变更的原因。通俗的说，即一个类只负责一项职责。 

问题由来：类T负责两个不同的职责：职责P1，职责P2。当由于职责P1需求发生改变而需要修改类T时，有可能会导致原本运行正常的职责P2功能发生故障。

**解决方案**

遵循单一职责原则。分别建立两个类T1、T2，使T1完成职责P1功能，T2完成职责P2功能。这样，当修改类T1时，不会使职责P2发生故障风险；同理，当修改T2时，也不会使职责P1发生故障风险。

说到单一职责原则，很多人都会不屑一顾。因为它太简单了。稍有经验的程序员即使从来没有读过设计模式、从来没有听说过单一职责原则，在设计软件时也会自觉的遵守这一重要原则，因为这是常识。在软件编程中，谁也不希望因为修改了一个功能导致其他的功能发生故障。而避免出现这一问题的方法便是遵循单一职责原则。虽然单一职责原则如此简单，并且被认为是常识，但是即便是经验丰富的程序员写出的程序，也会有违背这一原则的代码存在。为什么会出现这种现象呢？因为有职责扩散。所谓职责扩散，就是因为某种原因，职责P被分化为粒度更细的职责P1和P2。

比如：类T只负责一个职责P，这样设计是符合单一职责原则的。后来由于某种原因，也许是需求变更了，也许是程序的设计者境界提高了，需要将职责P细分为粒度更细的职责P1，P2，这时如果要使程序遵循单一职责原则，需要将类T也分解为两个类T1和T2，分别负责P1、P2两个职责。但是在程序已经写好的情况下，这样做简直太费时间了。所以，简单的修改类T，用它来负责两个职责是一个比较不错的选择，虽然这样做有悖于单一职责原则。（这样做的风险在于职责扩散的不确定性，因为我们不会想到这个职责P，在未来可能会扩散为P1，P2，P3，P4……Pn。所以记住，在职责扩散到我们无法控制的程度之前，立刻对代码进行重构。）

**实例**

用一个类描述动物吃什么这个场景

```php
class Animal {
    public function eat($animal)
    {
        echo $animal.'吃草';
    }
}

class Client{
    public static function main(){
        $animal = new Animal();
        $animal->eat("牛");
        $animal->eat("羊");
    }
}
```
执行结果:

    牛吃草
    羊吃草

那么，程序上线后我们发现不是所有动物都是吃草的，比如老虎吃肉。修改时如果遵循单一责任原则，需要将Animal细分为食肉动物meatAnimal和食草动物grassAnimal，代码如下

```php
class meatAnimal{
    public function eat($animal)
    {
        echo $animal.'吃肉';
    }
}

class grassAnimal{
    public function eat($animal)
    {
        echo $animal.'吃草';
    }
}

class Client{
    public static function main(){
        $meatAnimal = new meatAnimal();
        $meatAnimal->eat("狮子");
        $meatAnimal->eat("老虎");
        
        $grassAnimal = new grassAnimal();
        $meatAnimal->eat("牛");
        $meatAnimal->eat("羊");
    }
}
```
执行结果

    狮子吃肉
    老虎吃肉
    牛吃草
    羊吃草

我们会发现如果这样修改花销是很大的，除了将原来的类分解之外，还需要修改客户端。而直接修改类Animal来达成目的虽然违背了单一职责原则，但花销却小的多，代码如下：

```php
class Animal{
    public function eat($animal){
            if($animal == '狮子'||$animal == '老虎')
        echo $animal.'吃肉';
    }else{
            echo $animal.'吃草';
    }
}

class Client{
    public static function main(){
        $animal = new Animal();
        $animal->eat("狮子");
        $animal->eat("老虎");
        $animal->eat("牛");
        $animal->eat("羊");
    }
}
```
  
可以看到，这种修改方式要简单的多。但是却存在着隐患：如果有一天要加一个猪（杂食动物），我们又要去修改动物类，而对原有的功能带来了风险，这样修改方式在代码级别上违背了单一责任原则，虽然改起来简单，隐患却是最大的，还有一种修改方式，代码如下
```php
class Animal{
    public function eat($animal){
        echo $animal.'吃肉';
    }

    public function eat2($animal){
        echo $animal.'吃草';
    }
}

class Client{
    public static function main(){
        $animal = new Animal();
        $animal->eat("狮子");
        $animal->eat("老虎");
        $animal->eat2("牛");
        $animal->eat2("羊");
    }
}
```
可以看到，这种修改方式没有改动原来的方法，而是在类中新加了一个方法，这样虽然也违背了单一职责原则，但在方法级别上却是符合单一职责原则的，因为它并没有动原来方法的代码。这三种方式各有优缺点，那么在实际编程中，采用哪一中呢？其实这真的比较难说，需要根据实际情况来确定。我的原则是：只有逻辑足够简单，才可以在代码级别上违反单一职责原则；只有类中方法数量足够少，才可以在方法级别上违反单一职责原则；

例如本文所举的这个例子，它太简单了，它只有一个方法，所以，无论是在代码级别上违反单一职责原则，还是在方法级别上违反，都不会造成太大的影响。实际应用中的类都要复杂的多，一旦发生职责扩散而需要修改类时，除非这个类本身非常简单，否则还是遵循单一职责原则的好。

**遵循单一职责原的优点有**：

可以降低类的复杂度，一个类只负责一项职责，其逻辑肯定要比负责多项职责简单的多；

1.提高类的可读性，提高系统的可维护性；

2.变更引起的风险降低，变更是必然的，如果单一职责原则遵守的好，当修改一个功能时，可以显著降低对其他功能的影响。

3.需要说明的一点是单一职责原则不只是面向对象编程思想所特有的，只要是模块化的程序设计，都适用单一职责原则。


