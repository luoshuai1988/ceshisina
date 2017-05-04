<?php 

/**
 * 滴滴2016校招测评题(建水库问题)
 *
 * 一、前言

每周面试题，这周来个简单点，而且最近也在写 Android 自定义 View 系列的文章，欢迎大家关注公众号【于你供读】，每周推送面试题，每天推送技术干货。
二、题目

已知每个城市的用水需求相同，每月水库的进水速率恒定不变。现有一座水库供水，如果供应 10 个城市的话，一个月水库就会枯竭；如果供应 8 个城市的话，一个半月水库就会枯竭。当前城市化进程不断加快，新的城市不断产生，为了能够持续满足 12 个城市的供水，还至少需要建设几个这样的水库？

A. 2
B. 3
C. 4
D. 5

三、解题

这题相对来说，还是比较简单的，题目中最重要的一句话就是“现有一座水库供水，如果供应 10 个城市的话，一个月水库就会枯竭；如果供应 8 个城市的话，一个半月水库就会枯竭”，从这句话我们可以得出半个月水库的出水量可以养活 2 个城市，也就是说一个月的出水量可以养活 4 个城市。那么问题来了，要养活 12 个城市呢？

当然，12 / 4 = 3 ，3 个水库就能养活 12 个城市，可是这里要看清楚，千万看清楚，题目问的是 “还至少需要建设几个这样的水库” ，本来有一个这样的水库，还至少需要几个，所以答案是 2

如果上面表述的不清晰，我们用数学公式来解答一下：

假设水库库存的水量为 M
水库每月进水为 x
每个城市每月消耗水 m

根据题目的意思可以得到以下两条公公式：
M + x = 10 * m
M + 1.5 * x = 8 * m * 1.5
解得：x = 4 m

最后假设需要 n 个水库，就能养活 12 个城市，也就是：
n * x = m * 12
因为 x = 4 m ，所以解得 n = 3 ，所以最后的答案为 2 ，选择 A

按照之前的习惯，都会用程序模拟一下的，这次就偷下懒，不写程序了！嘻嘻！
 */