# 解密HTTPS的两种方法

## MITM

中间人是最常用的HTTPS抓包方式，常见的抓包工具都配备了HTTPS抓包的功能，并且都需要在客户端安装指定的证书，例如Charles，FIddler，MITMProxy，AnyProxy等。它们的抓包原理都是中间人，在目标服务器和目标客户端之间介入第三者，对通信双方进行欺骗，Arp欺骗也属于中间人的一种。

### 问题

现在很多App都会上SSL pinning，SSL pinning实质就是在App端绑定了服务端证书来防治中间人


## 使用密钥直接解密

HTTPS通信的过程大体分为三部分，协商加密算法，公开密钥（存在证书内）交换共享密钥（非对称）和使用共享密钥方式（对称）加密传输数据。基于这个过程，我们自然可以想到，如果我们获取到了对称加密过程的密钥，我们就可以将HTTPS密文解密。

### setup1 获取密钥：

设置环境变量[SSLKEYLOGFILE](<https://developer.mozilla.org/en-US/docs/Mozilla/Projects/NSS/Key_Log_Format>)。

[NSS](<https://developer.mozilla.org/en-US/docs/Mozilla/Projects/NSS/Overview>)是由Mozilla主导开发的安全库，实现了SSL，TLS。使用NSS的软件可以通过设置环境变量SSLKEYLOGFILE记录第一次交换后的共享密钥。

```shell
export SSLKEYLOGFILE = ～/ssl_key.log
# zsh or bash
source ~/.zshrc
source ~/.bash_profile
```

### setup2 解密:

通过wireshark来使用密钥解密HTTPS密文



## 最后

- 因为证书存在有效期，所以SSL pinning的缺点很明显，当证书失效的时候，SSL握手都是完不成的。
- Xposed是Android下一个框架，可以改变系统和应用的一些行为，JustTruestMe是一个Xposed模块，通过Hook 应用中的SSL API来绕过证书检测，只要能Hook到，便可以无视SSL pinning。

## 参考

- https://blog.csdn.net/u010726042/article/details/53408077
- https://codeday.me/bug/20180909/247949.html
- https://bbs.pediy.com/thread-226435.htm

