# 抓取HTTPS的两种方法

## MITM

中间人是最常用的HTTPS抓包方式，常见的抓包工具都配备了HTTPS抓包的功能，并且都需要在客户端安装指定的证书，例如Charles，FIddler，MITMProxy，AnyProxy等。它们的抓包原理都是中间人，在目标服务器和目标客户端之间介入第三者，对通信双方进行欺骗，Arp欺骗也属于中间人的一种。

## 使用密钥直接解密

HTTPS通信的过程大体分为两部分，公开密钥交换共享密钥和使用共享密钥方式加密传输数据。基于这个过程，我们自然可以想到，如果我们获取到了第二部分的密钥，我们就可以将HTTPS密文解密。

### 获取密钥

利用FireFox获取密钥

### 解密

利用WireShark解密

## 最后

## 参考

- https://blog.csdn.net/u010726042/article/details/53408077

- https://codeday.me/bug/20180909/247949.html

