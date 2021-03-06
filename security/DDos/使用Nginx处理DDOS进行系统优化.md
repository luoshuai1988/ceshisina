## 使用Nginx处理DDOS进行系统优化

来源：[http://server.51cto.com/sSecurity-582117.htm](http://server.51cto.com/sSecurity-582117.htm)

时间 2018-08-27 09:41:24

 
DDoS很常见，甚至被称为黑客圈子的准入技能；DDoS又很凶猛，搞起事来几乎压垮一方网络。
 
什么是分布式拒绝服务DDoS（Distributed Denial of Service）意为分布式拒绝服务攻击，攻击者利用大量“肉鸡”对攻击目标发动大量的正常或非正常请求，耗尽目标主机资源或网络资源，从而使被攻击者不能为合法用户提供服务。通常情况下，攻击者会尝试使拥有这么多连接的系统饱和，并要求它不再能够接受新的流量，或者变得非常缓慢以至于无法使用。
 
![][0]
 
换句话说老张的饭店（被攻击目标）可接待100个顾客同时就餐，隔壁老王（攻击者）雇佣了200个人（肉鸡），进饭店霸占位置却不吃不喝（非正常请求），饭店被挤得满满当当（资源耗尽），而真正要吃饭的顾客却进不来，饭店无法正常营业（DDoS攻击达成）。那么问题来了，老张该怎么办？
 
![][1]
 
当然是，轰出去！
 
![][2]
 
通常情况下，攻击者会尝试使拥有这么多连接的系统饱和，并要求它不再能够接受新的流量，或者变得非常缓慢以至于无法使用。
 
#### 应用层DDoS攻击特性
 
应用层（第7层/ HTTP）DDoS攻击由软件程序（机器人）执行，该软件程序可以定制为最佳利用特定系统的漏洞。例如，对于不能很好地处理大量并发连接的系统，仅通过周期性地发送少量流量打开大量连接并保持活动状态，可能会耗尽系统的新连接容量。其他攻击可以采取发送大量请求或非常大的请求的形式。由于这些攻击是由僵尸程序而不是实际用户执行的，因此攻击者可以轻松地打开大量连接并非常快速地发送大量请求。
 
DDoS攻击的特征可以用来帮助减轻这些攻击，包括以下内容（这并不意味着是一个详尽的列表）：
 
-流量通常来自一组固定的IP地址，属于用于执行攻击的机器。因此，每个IP地址负责的连接和请求数量远远超出您对真实用户的期望。
 
注意：不要认为此流量模式总是代表DDoS攻击。转发代理的使用也可以创建这种模式，因为转发代理服务器的IP地址被用作来自它所服务的所有真实客户端的请求的客户端地址。但是，来自转发代理的连接数和请求数通常远低于DDoS攻击。
 
-由于流量是由机器人生成的，并且意味着压倒服务器，因此流量速率远高于人类用户可以生成的流量。
 
- User-Agent报头被设置有时到非标准值。
 
-该 Referer头有时设为您可以与攻击相关联的值。
 
#### 使用NGINX和NGINX Plus来抵御DDoS攻击
 
NGINX和NGINX Plus具有许多功能，与上述的DDoS攻击特性相结合，可以使它们成为DDoS攻击缓解解决方案的重要组成部分。这些功能通过调节传入流量并通过控制流量代理后端服务器来解决DDoS攻击。
 
#### NGINX事件驱动架构的内在保护
 
NGINX旨在成为您的网站或应用程序的“减震器”。它具有非阻塞的事件驱动架构，可以应对大量请求，而不会明显增加资源利用率。
 
来自网络的新请求不会中断NGINX处理正在进行的请求，这意味着NGINX可以利用下面描述的技术来保护您的站点或应用免受攻击。
 
有关底层架构的更多信息，请参阅Inside NGINX：我们如何为性能和规模设计。
 
#### 限制请求率
 
您可以将NGINX和NGINX Plus接收传入请求的速率限制为实际用户的典型值。例如，您可能会决定访问登录页面的真实用户每2秒只能发出一个请求。您可以配置NGINX和NGINX Plus，以允许单个客户端IP地址每2秒尝试登录（相当于每分钟30个请求）：

```nginx
limit_req_zone $binary_remote_addr zone=one: 
10m 
 rate= 
30r 
/m; 
server { 
     
# ... 
    location /login.html { 
        limit_req zone=one; 
     
# ... 
    } 
} 
```
 
该 limit_req_zone 指令配置一个名为“ one”的共享内存区域，用于存储指定密钥的请求状态，在本例中为客户机IP地址（ $binary_remote_addr）。/login.html块中的 limit_req 指令引用共享内存区域。 location
 
有关速率限制的详细讨论，请参阅博客上的NGINX和NGINX Plus的速率限制。
 
#### 限制连接数量
 
您可以限制单个客户端IP地址可以打开的连接数，也可以限制为适合真实用户的值。例如，您可以允许每个客户端IP地址打开不超过10个到您网站的/ store区域的连接：

```nginx
limit_conn_zone $binary_remote_addr zone=addr: 10m ; 
server { 
     
# ... 
    location /store/ { 
        limit_conn addr  10 ; 
         
# ... 
    } 
} 
```
 
该 limit_conn_zone 指令配置了一个名为addr的共享内存区域，用于存储指定密钥的请求，在这种情况下（如前例所示）客户端IP地址 $binary_remote_addr。在 limit_conn该指令 location为块/存储引用共享存储器区，并设置一个最大从每个客户端IP地址10个连接。
 
#### 关闭慢速连接
 
您可以关闭正在写入数据的连接，这可能意味着尝试尽可能保持连接打开（从而降低服务器接受新连接的能力）。Slowloris就是这种攻击的一个例子。该 client_body_timeout指令控制NGINX在客户机体写入之间等待的时间，该 client_header_timeout 指令控制NGINX在写入客户机标题之间等待的时间。这两个指令的默认值是60秒。本示例将NGINX配置为在来自客户端的写入或头文件之间等待不超过5秒钟：

```nginx
server { 
    client_body_timeout 5s; 
    client_header_timeout 5s; 
     
# ... 
} 
```
 
#### 列入黑名单IP地址
 
如果您可以识别用于攻击的客户端IP地址，则可以使用该 deny指令将其列入黑名单，以便NGINX和NGINX Plus不接受其连接或请求。例如，如果您确定攻击来自地址范围123.123.123.1到123.123.123.16：

```nginx
location / { 
    deny  123.123 . 123.0 / 28 ; 
     
# ... 
} 
```
 
或者，如果您确定攻击来自客户端IP地址123.123.123.3,123.123.123.5和123.123.123.7：

```nginx
location / { 
   deny 123.123.123.3; 
   deny 123.123.123.5; 
   deny 123.123.123.7; 
   # ... 
} 
```
 
将白名单IP地址
 
如果仅允许从一个或多个特定组或范围的客户端IP地址访问您的网站或应用程序，则可以一起使用 allow和 deny指令以仅允许这些地址访问该站点或应用程序。例如，您可以限制只访问特定本地网络中的地址：

```nginx
​location / { 
    allow 192.168.1.0/24; 
    deny all; 
    # ... 
} 
```
 
在这里， deny all指令阻止所有不在 allow指令指定的范围内的客户端IP地址。
 
#### 使用缓存来平滑流量尖峰
 
您可以配置NGINX和NGINX Plus来吸收攻击导致的大量流量峰值，方法是启用缓存并设置某些缓存参数以卸载后端的请求。一些有用的设置是：

 
* 该指令的 updating参数 proxy_cache_use_stale告诉NGINX，当它需要获取一个陈旧的缓存对象的更新时，它应该只发送一个更新请求，并且继续将陈旧对象提供给在接收时间期间请求它的客户端来自后端服务器的更新。当对某个文件的重复请求是攻击的一部分时，这会显着减少对后端服务器的请求数量。 
* 该 proxy_cache_key指令定义的键通常由嵌入式变量组成（缺省键 $scheme$proxy_host$request_uri，有三个变量）。如果该值包含 $query_string 变量，则发送随机查询字符串的攻击可能导致过度缓存。 $query_string除非您有特殊原因，否则我们建议您不要在变量中包含变量。 
 
 
#### 阻止请求
 
您可以配置NGINX或NGINX Plus来阻止几种请求：

 
* 请求一个似乎有针对性的特定网址 
* User-Agent报头设置为与正常客户端流量不对应的值的请求 
* 将 Referer标头设置为可与攻击关联的值的请求 
* 其他头文件具有可与攻击关联的值的请求 
 
 
例如，如果您确定DDoS攻击的目标是URL /foo.php，则可以阻止该页面的所有请求：

```nginx
​location /foo.php { 
    deny all; 
} 
```
 
或者，如果您发现DDoS攻击请求的 User-Agent头部值为 foo或 bar，则可以阻止这些请求。

```nginx
​location / { 
    if ($http_user_agent ~* foo|bar) { 
        return 403; 
    } 
    # ... 
} 
```
 
该变量引用一个请求头，在上面的例子中是头。类似的方法可以用于具有可用于识别攻击的值的其他报头。 http_*name*``User-Agent
 
#### 限制到后端服务器的连接
 
NGINX或NGINX Plus实例通常可以处理比负载平衡的后端服务器更多的并发连接。使用NGINX Plus，您可以限制连接到每个后端服务器的数量。例如，如果要限制NGINX Plus与网站 上游组中的两个后端服务器建立的连接数不超过200个：

```nginx
​upstream website { 
    server 192.168.100.1:80 max_conns=200; 
    server 192.168.100.2:80 max_conns=200; 
    queue 10 timeout=30s; 
} 
```
 
max_conns 应用于每个服务器的参数指定NGINX Plus打开的最大连接数。该 queue 指令限制上游组中所有服务器达到其连接限制时排队的请求数，并且该 timeout参数指定在队列中保留请求的时间。
 
#### 处理基于范围的攻击
 
一种攻击方法是发送一个 Range具有非常大值的标头，这可能导致缓冲区溢出。有关如何使用NGINX和NGINX Plus来缓解此类攻击的讨论，请参阅使用NGINX和NGINX Plus来保护CVE-2015-1635。
 
#### 处理高负荷
 
DDoS攻击通常会导致高流量负载。有关调整NGINX或NGINX Plus以及允许系统处理更高负载的操作系统的提示，请参阅调整NGINX的性能。
 
#### 识别DDoS攻击
 
到目前为止，我们专注于您可以使用NGINX和NGINX Plus来帮助减轻DDoS攻击的影响。但NGINX或NGINX Plus如何帮助您发现DDoS攻击呢？该NGINX加状态模块 提供了有关被负载后端服务器，你可以用它来发现异常流量模式平衡交通的详细指标。NGINX Plus附带了一个状态仪表板网页，以图形方式描述了NGINX Plus系统的当前状态（请参阅demo.nginx.com上的示例）。通过API也可以使用相同的指标，您可以使用它将指标提供给自定义或第三方监控系统，您可以在其中进行历史趋势分析以发现异常模式并启用警报。


[0]: ./img/EzUVrem.jpg
[1]: ./img/fUvmqyR.jpg
[2]: ./img/NBb2uej.jpg