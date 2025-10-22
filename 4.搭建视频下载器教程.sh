# yt-dlp 是一个github开源项目，对多达1000个网站的下载提供了解决方案，可具体查看 https://github.com/yt-dlp/yt-dlp/blob/master/supportedsites.md

# 首先下载对应的编译好的二进制包到linux服务器
sudo wget https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -O /usr/local/bin/yt-dlp
# 授予其可执行权限，此时就可直接使用 yt-dlp 命令下载 youtube、b站、p站等视频，但是是分音频和视频单独下载的
sudo chmod a+rx /usr/local/bin/yt-dlp
# 因此需安装 音视频合成转码软件 ffmpeg，以便 yt-dlp 直接调用。（调用过程是自动进行的）
sudo apt install ffmpeg -y
# 此时就可在 linux服务器输入命令直接下载 支持的网站的视频，字幕，并手动选择清晰度，音视频编码格式，可具体询问 ai 获知语法，例如下面命令（默认下载最佳画质 + 音频合并）
# 只查看清晰度不下载
yt-dlp -F "https://www.bilibili.com/video/BV14UUAYmExC/" --cookies /var/www/wordpress/yt-dlp/cookies.txt
# 不使用 cookies
yt-dlp https://www.youtube.com/watch?v=K3sHzsZA9Ac
# 使用 cookies
yt-dlp "https://www.bilibili.com/video/BV14UUAYmExC/" --cookies /var/www/wordpress/yt-dlp/cookies.txt


# 为了便于操作，可使用 web-ui的界面操作
# 创建一个目录，存放 下载视频的网页前端 download.html 和 后端download.php（默认密码是y，需要自行修改，尽量复杂一点）
sudo mkdir -p /var/www/wordpress/yt-dlp/
# 把前端和后端上传即可，注意手动修改 download.php 密码
# 将 网站所在目录的管理权限赋予 www-data 用户（即为nginx 运行时的低权限用户）
sudo chown -R www-data:www-data /var/www/wordpress/
# 下载的web-ui网址为  https://domain.com/yt-dlp/download.html  ,在浏览器中直接输入即可
# 下载视频时，会提示不安全，因此需安装 /root/cert/ 里的公钥证书至 根证书下

# 针对 b 站等网站，只有登陆后才可以下载1080 p 的高清视频，非登陆只能下载 480p。因此，需使用 cookies 鉴权（现在不需要登陆就能下1080p 30帧了）
# 下载谷歌浏览器插件 Get cookies.txt LOCALLY
https://chromewebstore.google.com/detail/get-cookiestxt-locally/cclelndahbckbenkjhflpdbgdldlbecc
# 登陆 b 站账号后，导出 cookies ，并重命名为 cookies.txt
# 将 cookies 上传至linux 服务器的 /var/www/wordpress/yt-dlp/  目录下，但是cookies上传后不安全，可被直接访问到，所以仅建议在内网环境上传登陆后的 cookies ，在公网环境上传 未登录的 cookise
# 此时再去下载即可下载到 高清视频

# 对于 油管等网站，即使不登录就可下载超清视频，但是仍需上传未登录的 cookies，否则会在多次下载后触发风控

# 对于上述列表中不支持的网站视频，可分为 4 类
# 第一类，嵌入了一个完整的 .mp4 格式，此时打开浏览器开发者工具，点击网络network，去搜索对应的 .mp4 格式的文件 或者 直接去媒体里筛选
# 第二类，是把完整的视频切片为 .m3u8，但是对 .m3u8 的下载不作鉴权，此时打开浏览器开发者工具，点击网络network，去搜索 .m3u8 后缀的文件，直接复制链接url 后，然后询问 ai 怎么去下载该链接，提示词如下
我在debian 上安装了ffmpeg，现在 有一个 m3u8 的下载链接为 xxxxx。请问我怎么下载视频
# 第三类，是把完整的视频切片为 .m3u8，但是对 .m3u8 的下载作鉴权，此时打开浏览器开发者工具，点击网络network，去搜索 .m3u8 后缀的文件，需要复制带有请求头的链接，即复制 cmd 的url，然后询问 ai 怎么去下载该链接，提示词如下
我在debian 上安装了ffmpeg，现在 有一个 m3u8 的下载链接为 xxxxx。该 m3u8 下载时候会校验源网站是否为 某某某网址，以及浏览器客户端，请求头等信息，请问我怎么下载该视频
# 第四类，是把完整的视频切片为 .m3u8，但是对 m3u8 的播放逻辑进行了变形，此时需要分析代码，最为麻烦，比如 youtube，bilibili 等大型网站均进行了混淆和加密！！！
