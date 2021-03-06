## MySQL 锁机制实践

来源：[https://mp.weixin.qq.com/s/zi86vvktNbA0_R6s1P67iQ](https://mp.weixin.qq.com/s/zi86vvktNbA0_R6s1P67iQ)

时间 2018-08-23 08:29:50

姜宇祥，携程资深数据库工程师
 
这么热天能来的都是真爱，我给大家讲一下这个课题，主要讲源码，这个课题与运维看起来有点小差别。
 
你能看源码，至少你在运维时候可以跟开发说是看源码怎么怎么样，我们就可以更有底气。
 
而且有点很重要，我们看数据库里面，现在大家很多用的，不管开源的MySQL，还是闭源的像 oracle 都是国外数据库，我们数据库底子还是挺薄，越来越人开始研究源码和写源码，这样我们数据库才会蓬勃发展，跟我们的国策，提倡国产这个东西，我觉得大家都要承担起学习源码、研究源码的美好愿望和美好的意志。
 
今天我给大家带来一个入门的MySQL锁机制与实践，主要里面给大家介绍一下它的源码入口之类东西，帮助大家以后这方面读源码读得更顺畅。
 
![][1]
 
首先我先介绍一下MySQL两层架构，MySQL的两层架构所有数据库里面，除了MySQL以外，其他数据库都没有这个特点，MySQL两层架构就是它把服务层和存储引擎层拆成两个层次来看待，服务层集中所有通用化的功能，如网络通讯和语法分析等工作。
 
存储层它做到的是进行数据相关的存储，比如它是采用内存方式的存储，比如像之前的赖老师介绍的memcache，它是内存存储，还有文件方式存储，像InnoDB。
 
它的好处是什么，让这个数据库非常灵活，但是同样MySQL这两层存储也提出它的问题缺点，由于它是一个关系性数据库，也涉及到事务，再处理事务时候，我们使用多引擎时候一定要注意这个问题。
 
![][2]
 
我们往下看一下，这是非常经典的MySQL一个架构，它在这里面列出来，从客户端我用的是什么，它发出的消息或者说是请求，到MySQL server，它的语法分析或者优化器部分，还有这些东西，这都是MySQL在很多上面。如果我们刨去底层是通用部分，它把这部分集中起来，这部分它做得非常好。下面可以接不同存储引擎，像比如 InnoDB 和 MEMORY，它不支持事务，如果两个共用大家要小心。
 
BLACKHOLE 是挺有意思引擎，大家以后做这个可以引进进来，它不实际插数据，我们把这个作为一个中继，binlog存储本地再传输到下一个节点，这是体现MySQL非常灵活性的方面。
 
再说这两种锁，MDL锁在服务器层，我们建表这些事情，比如表和数据库元数据信息，它都是由server保护，这些访问需要用MDL锁。InnoDB是存储引擎，它实现数据库里面最标准隔离级，这部分又自己实现一个锁，InnoDB实现这个锁，把数据并发操作这些事情在这一层做掉。
 
![][3]
 
我们看一下元数据锁和InnoDB锁包含什么东西，元数据锁像我刚才说，它锁的是数据库对象。
 
所谓数据库对象包括什么，首先它有一个global对象，这个对象大家并不常接触，实际我们维过程中最容易碰到的 globle read lock，实际这是global锁存在原因。
 
还有数据库对象、表对象、存储过程对象、函数对象，还有触发器对象，这都是它服务器层所涵盖的概念，元数据锁对应这些的锁，它保护我们存储每一行数据，下面是表锁和行锁，对于InnoDB来说表锁非常少见，很少碰到，这里面我们主要介绍InnoDB的一个间隙所实现原理。
 
我们来看一下，介绍一下MySQL的元数据锁信息，我会从这几个方面讲元数据锁。第一元数据锁之间有关系，元数据锁的类型，元数据锁每一个对象针对的元数据锁有一些从属关系，还有这些元数据锁什么时间请求，什么时间释放，我们怎么读它，我们还要了解怎么读元数据锁的源码，大家得源码比较感兴趣是入口点，还有结合一个案例去分析，我们去教大家怎么读MySQL的源码。
 
下面列出MySQL所有的元数据锁 类型，global我们之前提到过，应该怎么讲呢，它在上面做了最高层次做了就是global锁，就是实例级进行上锁。
 
![][4]
 
它下面是TABLESPACE，TABLESPACE对于MySQL的这个参数设置来讲，实际上针对每个表的表锁，它下面还有一个 table 表锁，实际上可以理解成TABLESPACE 物理方面的锁，table 锁针对表对象的锁。
 
还有一个schema，schema是MySQL里面有一个概念上的混淆，这里面元数据锁对应schema的对象，还有我们下面FUNCTION锁、PROCEDURE锁、TRIGGER锁，这三个锁，commit锁，我在commit提交的时候，它需要持有让COMMIT串行化方式的锁，并不常见到，但是每次commit一定都会有，它和global差不多的。
 
还有USER LEVEL LOOK，这是MySQL向外提供，给你使用MySQL的时候，可以使用对外提供的锁，元数据锁，你可以在两个不同之间，你使用这个锁的话，可以做到这个之间的同步。
 
还有locking service，实际上它是更进一步USER_LEVEL_LOCK，前面的USER_LEVEL_LOCK是有两个函数跟它相关，这个函数可以去查MySQL的手册，它明确提供的接口，它可以把两个之间，对同一个对象进行上锁，它可以对两个之间同步。
 
![][5]
 
类型有这么多数据锁，这张图可以看到数据锁之间的看到。global锁下面，它所谓的关系是什么？这里面关系是在大部分情况下，如果我要对这里面某一个元数据上锁的时候，它有一些前置锁。
 
在上这个锁之前，我把其他对象的锁上了，我把它称之为关系。看这个关系箭头所指，它的关系从属。
 
比如我做tablespace上锁时候加global的锁，这样它通过看图，我们知道一旦做底层操作时候需要上很多，依照这个线它要上很多锁，我们在做下面操作时候可能会产生更多的冲突。也不叫产生更多的冲突，要hold更多的锁。
 
![][6]
 
再讲元数据，锁这个东西有申请和释放过程，元数据锁的申请，它有这样几种，它锁申请的时候在你使用的时候，它才进行申请。
 
但是如果它在释放时候，它会一定遵照这三个类型释放，而这三个类型指明了程序运营某一个点时候，固定点的时候针对这些锁进行释放。比如这里面的statement锁，当我们做一组SQL操作时候加上这些。
 
它在元数据上锁的时候，对元数据操作进行上锁，如果指定STATEMENT的时候，它在STATEMENT结束时候会进行判断，看到有这些上了STATEMENT锁的时候会在这时候进行释放。
 
看下面这一段比较清楚，先介绍有三种类型：STATEMENT级别、TRANSACTION级别、EXPLICIT，就是明确指明了。
 
![][7]
 看这个流程大家可能会清楚一点，在一开始我说的你执行时候获取这些锁。像之前某一个元数据锁制定MDL STATEMENT锁，刚才我说一批SQL过来，设置成这些信息的锁全部被释放掉。
 
举个例子，操作这张表先设置一个MDL锁，当这个执行完之后把这个锁放掉，如果不放掉也就很简单，别人再进行一定会被锁掉。STATEMENT的锁，还有TRANS锁，一个事情批量执行完之后，所有MDL的锁被释放。一个事务全部执行完之后，所有的锁基本上全部会释放掉。
 
我们再讲一下，MDL锁，它的信息就很多，我们在看源码的时候经常被自己混乱掉。你知道了其实就是那么一点东西，你不知道的时候就会四处撒网去看，我们刚才介绍源数据锁核心介绍的关系，它的类型，还有释放点，这几个地方最重要，看到这几个最重要东西，我们看源码的时候可以看到划红线的部分，划红线部分MDL核心源码，我们研究MDL如何操作运行的时候，我们深入MDL.h和MDL.cc，定义之前我们提到的这些类型的宏（这里假设大家有C方面经验，对宏登概念有锁了解）。
 
![][8]
 通过这些宏我们找对应的信息，红框对应的文件，操作上锁、解锁，函数这里面定义。使用这些函数是下面的这些函数，是在这里面使用的。
 
核心，在这里面看代码就是这个东西，看哪几个函数最重要。通常来讲看代码的时候有几个函数最重要，就是越底层的函数是越重要，大家最后入口，以这个例子来讲，不管是哪个，最终都要落到函数里面来，都在这里面描述，不管是哪一种锁最终都是通过这个函数实现了上锁的东西。
 
所以你们要是在调试MySQL的时候，想调MDL的相关代码设置断点一定在这个函数里面设置断点。这个函数作为最底层函数，不过下面并不是完全，我只是举几个例子。
 
比如lock schema，做这个操作，最终它会通过这些落到这个函数里面，所以当我们想要看这个操作如何操作，这块设一个断点，可以通过这个堆栈了解一系列调用什么样。
 
![][9]
 这个就是讲元数据锁与PFS，元数据在上锁时候，我在进行等待，我等待了一个什么样的锁，就是这个东西。当我们在show processlist或者performance_schema下面，然后metadata_locks表查的时候，我们读到就是这些信息，实际这些信息和我们这个锁是一一对应的。
 
我们讲一个从线上碰到的问题，到如何去看源码找到根源，这个作为一个例子，帮助大家加强对于MDL源码概念。
 
![][10]
 这是我们线上碰到现象，重新做备份160个左右连接被阻塞，情况就是这个图。这里面有一个time时间，处理这个问题的时候直接到time时间最长连接，整个问题就都解决了，我们问题解决了还要找它的根本原因，了解到这个原因之后，以后可以避免类似情况再发生。
 
我们在读这个东西的时候，有一个最可疑的，最长的这个时间是，不好意思，没在这里面，当时拿了一个大表的非常长操作，这个操作理论上讲不应该锁那么多数据。
 
在这里面拿出来的这段，只有这一个，当时做一个维护操作，只有这个东西才会把整个全局进行锁掉，针对这个东西我们查为什么前面这个查询导致后面出现了全局的锁，当时分析的一些结果。
 
我们当时调了，因为我们已经知道是flush tables with read lock造成其他事务阻塞，我们看它在这个里面做了什么操作，之前我们看到的这个信息提示很重要，Waiting for global read lock元数据锁导致问题。
 
![][11]
 我们在这块设置断点，我们执行flush tables with read lock得到一个堆栈电梯，在调试的时候我们强调堆栈电梯，而且以后你们碰到问题向别人求助的时候，最好也能抓住这样堆栈电梯。
 
你们如果看阿里或者腾讯发出来的文章，就是说他们讲一些东西的时候，他都会把堆栈电梯给你拿出来看当时是什么情况。
 
当时我们看堆栈电梯的时候，它这里面，我们设断点在这个函数，然后调用到这个是我们很期待的。再往上就是这个函数，这时候我们知道这个函数里面发生的事情是我们所不希望它产生全局锁的情况。
 
![][12]
 再看一下这段代码，通过堆栈找到代码，这段代码上面我们看什么，它在上这个read lock之后，它后面有这个，在上完这个锁之后，它有做了一个close cached tables，我们知道flush tables这个操作被锁住的，它在上完这个东西之后，它做了一个close cached tables。
 
之前我们的长操作，它在一开始一定要起来，它在不执行完不会释放那些，它在执行时候就这个位置等到另外一个全部执行完，这样它等在这之后hold全局的read lock，做元数据操作加一个read lock获取信息，后面全部被锁掉了。
 
通过这个方式，这个断点之后找到全部前因后果，通过这个运维或者开发，所有做超常查询的时候，只要上面做运维操作都可能把别人卡死，做这个几千万行数据，网络传输到本地，这种情况下别人干掉，会影响到别人。
 
这个东西给它分析出来之后，以后可以明确告诉开发或者谁，这个东西不能这么做。这是源码的东西，理直气壮。
 
MDL可以讲的就是这么多的，像我之前说的知道了很简单，不知道一直摸不着头绪，前面大家注意的就是这些。
 
下面讲InnoDB锁，我分这几个部分介绍一下，实际上InnoDB锁是一个非常复杂，PPT里面只能介绍一部分，介绍的这部分也只是针对这种情况下的一个间隙锁的实现，前面还会介绍一些前置知识，事务隔离级，在讲InnoDB会讲锁的级别，间隙锁怎么样，还有和上面MDL告诉源码主要哪几个地方，什么重要入口在哪里。还有再讲一下我在携程针对这方面做了哪些源码改造。
 
![][13]
 
![][14]
 
先讲第一个前置知识，包括事务隔离级里面三个相现象，一个脏读，一个不可重复读，一个幻读，还有一个是它的事务隔离级，就是读未提交、读提交，还有它是一个什么样的状况。因为我在工作的过程中，很多人实际上对这个都是理解不够透彻，我个人认为理解不够透彻，我想把这个东西介绍一下，可能很多人已经很了解了，我把它再细化讲一讲。
 
这里面先讲脏读，什么是脏读，我拿赖老师举现场例子，我和他比较熟，帮助大家形象化理解，赖老师做一件事情，写一点东西，正好我路过看到赖老师写的东西，我说赖老师你这个东西写得不错，我拿这个东西给他传播了，结果赖老师觉得写得不满意，按照数据库的说法回滚掉，不要了，结果我还传播出去了，这个就是脏读，这个读到别人回滚数据，还使用到它，这就是脏读。
 
针对脏读在读位隔离级提交才会发生，为了避免脏读，有读提交隔离级出现，你只有提交了，刚才赖老师说写稿，定稿的稿子给我，我看了之后，这是它提交的东西，我再拿出去用就OK，这是读提交。
 
但是读提交也有一个问题，赖老师做第一个东西拿出去给别人用，但是赖老师又修改一次变成第二版本，这时候拿第一个稿，碰到什么情况？拿第一版本稿子再给别人用的时候，实际上已经有第二个版本情况出现了，这个时候就变成了不可重复读的这种现象了。
 
在我的操作过程中，我读到了两次，就是碰到了两次两个不同的版本，针对这种情况下有一个可重复读的隔离级，很多数据库实现时候，里面有一个MVCC方式，一个事务里面读到我事务之前的版本，我这个事务之前最新的一个版本，这样避免了不可重复读。
 
但是可重复读隔离级下面还有一个问题，带一个查询条件，比如赖老师写了一篇稿子，这个时候我能只操作这一个，只引用了这一个稿子，但是产生了第二个稿子，读第二次发现赖老师的第二个稿子出现了，变成了一个幻读的现象，这个幻读现象是什么？
 
我在一次事务里面查询结果级不一样，又引入串行化。看这个事务隔离级可以想前辈怎么逐渐数据库演化成现在这个样子，最早谁也不知道，没有事务隔离级大家都不知道，都是在那边写，慢慢出现这种情况，这也是软件演化的概念。我推荐一本书讲软件演化，挺有意思的现象。
 
![][15]
 
锁的基本原理我介绍完之后，我们可以进入到InnoDB具体的信息里面，InnoDB有表锁、行锁和间隙锁，间隙锁就是行锁类型，特殊操作，加上特殊标识之后可以认为间隙锁。
 
![][16]
 
它有意向共享锁、意向排它锁和共享所、排他锁、自增锁，自增锁是InnoDB自己实现的自增列的锁，这是全局锁，排它性是这样。它是直接列的数组矩阵，兼容性就是这样子。通常情况下不会碰到IS锁和X锁冲突情况。S锁和X锁放在表级锁，IS锁和IX锁放在进入级别上面。
 
我们讲InnoDB的间隙锁原理，间隙锁主要用在不可充分读，实际上MySQL的第三个隔离级别，可重复读的级别部分实现串行化，在一个事务之内保证它的结果级基本上保持一致，投入这个间隙锁实现的。
 
![][17]
 
![][18]
 
我们先建一个表，插入这四行数据，我们怎么理解这个间隙锁。假如前面做一个begin;select * from t_lock where f2=16 for update，在之前的表里面是没有数据，它在查询的过程中，实际上查到大于它查询条件的最小一条记录，它先定位这条记录，定位这条记录上之后，对这条记录的行锁加特殊标识，当其他事务进行插入的时候，因为它插入的时候一定查询到一个插入的点。
 
比如我在插入15的时候，一定也会通过前面查询定位到17这个上面，这时候它在插入时候发现17上面有一个行锁，而且这个行锁加了一个特殊标识，就是加了这个标识变成间隙锁，这时候它会被锁住。
 
为什么也这样一个间隙锁，它达到了行级不发生改变的情况。比如再插一个这个语句等于16，不把17锁定。
 
有一个人进来插进来16了，这个查询的结果，第二次查询的时候能把这个结果级，提交了就能把这个结果级查询出来，这个就是间隙锁的作用。
 
我每次查到一行记录的时候，在这个情况下每查到一行记录进行加锁，这是间隙锁实现，通过这种方式避免结果级重复查询的时候出现行级不一致的现象。这个是刚才讲的操作，会把东西锁在里面的实例。
 
![][19]
 
我们再讲一下间隙锁，看一下间隙锁在内存里面打印的结果，MySQL锁结构非常简单，就是整个lock打印出来就是这么多，锁处于哪一个事务，这个锁在哪一个index上，这是C里面特殊语法结构，告诉你上一个锁是table锁还是lock锁。这个是加特殊标识，分成16进制是512，这是间隙锁内存里面，打印出来间隙锁的样例。
 
看它的核心源码这几个函数，大家有兴趣一定这几个汉书入手，locko，lock.h，IS、IX类型，行锁类型，还是表锁类型，这些类型都是在这些.h里面定义。如何上锁的地方，在trx0trx.cc 、trx0sys、trx0rec.cc，这是MySQL事务实现的一个非常重要的核心代码，像行锁的是在这里面的。
 
![][20]
 
我再讲一下携程做了相关的事务锁的改造，这个是我们最 常见碰到的现象，发生死锁一定出这个东西，下面的红框产生死锁，这个操作产生死锁的事务，上面是它前面的一个事务，假设我们有一个环，这个操作造成死锁，一直到后面产生闭环，这是一个死锁。
 
![][21]
 
打印这个信息，这个信息不够格式化，产生的内容不好读。第二点，不能把所有参与到死锁里面的事务全部打印出来，我们针对这种情况，实际上我们做了改造，我们希望能把这些信息都格式化出，而且可视化展示。我们做了格式化输出，把它都输出成这个格式，后面是一个数组，哪些事务全部打印出来。
 
![][22]
 
![][23]
 
这里面，之前我们看图像里面打印一个锁信息，我们操作的时候把一个事务里面所有上锁信息全部打印出来了，可视化的操作时候就是把之前输出的信息全部拿出来之后，可以看到这样一个结果，这个对我们线上运维还是挺有帮助。不过有一个问题是什么，你判断不出来，这里面打印信息还是不够全，不能把所有锁涉及到的操作，就是产生所的语句打印出来，我们改进时候有一个配套工程，做一个MySQL的全量trx输出，实际上运维可以根据线上这些信息把当时开发所做的事情拿出来，定位开发的一些问题。
 
InnoDB的源码，锁就讲完了，介绍我自己学习源码的个人经验，希望这个东西帮助大家学习源码。
 
![][24]
 
首先第一点，你一定要先了解数据库的绝大部分功能，然后再去研究它的源码，否则的话，你直接拿过来源码看通常是一头雾水。因为一个好的，像MySQL 源码写得很不错，很多函数名和变量的定义能直接跟它的功能关联。如果你不熟悉功能直接看源码时候，你通常找不到它的关联性。
 
第二个，观摩他人源码学习，怎么观摩？网上很多人提供了很多功能性的这个文件，你拿到这个知道实现什么功能，你拿到这个文件知道做了哪些操作，这样可以反推这个函数做什么，这个函数怎么实现，有一个直观印象，通过某一个功能点嵌入进去，功能到源码还是有点大海捞针的感觉，到下面一步可以更针对性的研究。
 
还有最后一步，最后一个，我们学习源码可以扩充自己对整个数据库的了解，像之前我介绍的MDL时候，实际上我最早的时候在没有研究这部分源码的时候，这些东西我都是不知道，通过源码最终返回加深对功能了解，通过这些功能可以帮助我们线上运维做一些其他的事情。
 
我就讲完了，谢谢大家。
 
说明：本文根据728数据库沙龙携程姜宇祥老师的演讲整理而成。
 
数据库设计优化，看中航信运维经理刘晨带给您独家体验！还有AIOps，DevOps等精彩议题就在 **`GOPS 2018 上海站！  `** 
 
![][25]
 
#### GOPS 2018 上海站视频精彩纷呈
 
点击阅读原文  ，立即订票


[1]: https://img0.tuicool.com/QFJJBfj.jpg
[2]: https://img2.tuicool.com/JRBFzmz.jpg
[3]: https://img0.tuicool.com/mmaqmyq.jpg
[4]: https://img2.tuicool.com/bIzmuur.jpg
[5]: https://img1.tuicool.com/Mbqaqmf.jpg
[6]: https://img2.tuicool.com/QV7fymy.jpg
[7]: https://img0.tuicool.com/IVZJz2I.jpg
[8]: https://img1.tuicool.com/iYFBviJ.jpg
[9]: https://img2.tuicool.com/3aIjIn3.jpg
[10]: https://img0.tuicool.com/VNRjMzv.jpg
[11]: https://img0.tuicool.com/jeeuuam.jpg
[12]: https://img2.tuicool.com/NrauIjn.jpg
[13]: https://img1.tuicool.com/3iM7nqj.jpg
[14]: https://img1.tuicool.com/MbMz2ir.jpg
[15]: https://img0.tuicool.com/MNJ77fm.jpg
[16]: https://img0.tuicool.com/AbamE3b.jpg
[17]: https://img0.tuicool.com/6zi2euU.jpg
[18]: https://img2.tuicool.com/RjuyY36.jpg
[19]: https://img1.tuicool.com/JvYzemf.jpg
[20]: https://img1.tuicool.com/Nbqa6nq.jpg
[21]: https://img1.tuicool.com/qY3m6zE.jpg
[22]: https://img1.tuicool.com/u63MreF.jpg
[23]: https://img2.tuicool.com/myEfeqI.jpg
[24]: https://img2.tuicool.com/V3MV7fJ.jpg
[25]: https://img2.tuicool.com/NfamEj3.jpg