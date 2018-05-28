## php中关于换行符的一些

众所周知，换行符这个东西在不同系统下会使用不同的字符表示。

```
Windows  \r\n

Unix/Linux \n

Mac \r
```



####  在php中有关于换行符

#### PHP_EOL

PHP_EOL常量会根据所在的操作系统自动选择使用什么字符，不用自己判断何乐不为啊。

#### [ini_set('auto_detect_line_endings', true);](http://php.net/manual/zh/filesystem.configuration.php)

当设为 On 时，PHP 将检查通过 [fgets()](http://php.net/manual/zh/function.fgets.php) 和 [file()](http://php.net/manual/zh/function.file.php) 取得的数据中的行结束符号是符合 Unix，MS-DOS，还是 Macintosh 的习惯。

这使得 PHP 可以和 Macintosh 系统交互操作，但是默认值是 Off，因为在检测第一行的 EOL 习惯时会有很小的性能损失，而且在 Unix 系统下使用回车符号作为项目分隔符的人们会遭遇向下不兼容的行为。

适应于像fgets, file, fgetcsv需要根据换行符来判断一行是不是结束的函数。

举个例子：当我在Mac上创建了一个文件，是以\r作为换行符，但是放到服务器上处理的时候，因为服务器是Linux的操作系统，以\n作为换行符，导致我调用fgets函数时，直接将整个文件内容返回了。此时，可以在程序的开始位置，添加```ini_set('auto_detect_line_endings', true);```使php自己去判断换行符来解决。



