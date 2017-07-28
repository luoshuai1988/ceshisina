<?php  

/*
汉诺塔（又称河内塔）问题是源于印度一个古老传说的益智玩具。大梵天创造世界的时候做了三根金刚石柱子，在一根柱子上从下往上按照大小顺序摞着64片黄金圆盘。大梵天命令婆罗门把圆盘从下面开始按大小顺序重新摆放在另一根柱子上。并且规定，在小圆盘上不能放大圆盘，在三根柱子之间一次只能移动一个圆盘。简而言之，有三根相邻的柱子，标号为A,B,C，A柱子上从下到上按金字塔状叠放着n个不同大小的圆盘，要把所有盘子一个一个移动到柱子B上，并且每次移动同一根柱子上都不能出现大盘子在小盘子上方，请问至少需要多少次移动？
递归过程序如下:
1)把n-1个圆从A移到C
2)把剩下一个由A移到B
3)再把n-1个由C移到B,完成
 */

//将所有圆盘从a移到b  
function hanuota($n,$a,$b,$c){  
    global $step;  
    if($n==1){  
        $step++;  
        echo "将圆盘 $n 从 $a 柱子 到 $b 柱子 <br />";  
    }else{  
        hanuota($n-1,$a,$c,$b);  
        $step++;  
        echo "将圆盘 $n 从 $a 柱子 到 $b 柱子 <br />";  
        hanuota($n-1,$c,$b,$a);  
    }  
}  
//移动的次数  
$step = 0;  
hanuota(4, 'A', 'B', 'C');  
echo "移动次数：" . $step;  