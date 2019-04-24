<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
@foreach($goodsInfo as $v)
    <dl>
        <dt><a href="http://blog.lab993.com/goods/goodsDetail/{{$v->goods_id}}"><img src="http://blog.lab993.com/uploads/goodsimgs/{{$v->goods_img}}" width="100" height="100" /></a></dt>
        <dd>
            <h3><a href="http://blog.lab993.com/goods/goodsDetail/{{$v->goods_id}}">{{$v->goods_name}}</a></h3>
            <div class="prolist-price"><strong>¥{{$v->self_price}}</strong> <span>¥{{$v->market_price}}</span></div>
            <div class="prolist-yishou"><span>5.0折</span> <em>销量：{{$v->goods_score}}</em></div>
        </dd>
        <div class="clearfix"></div>
    </dl>
@endforeach

<button id="btn">分享到朋友圈</button>
<script src="http://res.wx.qq.com/open/js/jweixin-1.4.0.js"></script>
<script src="/js/jquery.js"></script>
<script>
    $(function(){
        // 通过config接口注入权限验证配置
        wx.config({
            debug: true, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
            appId: "{{$appid}}", // 必填，公众号的唯一标识
            timestamp:"{{$timestamp}}", // 必填，生成签名的时间戳
            nonceStr: "{{$noncestr}}", // 必填，生成签名的随机串
            signature: "{{$signature}}",// 必填，签名
            jsApiList: ['chooseImage','uploadImage'] // 必填，需要使用的JS接口列表
        });

        $('#btn').click(function(){
            // 通过ready接口处理成功验证
            wx.ready(function(){

                // 分享到朋友圈
                wx.onMenuShareTimeline({
                    title: '最新推荐的商品', // 分享标题
                    link: 'http://wechar.lab993.com/goods/list', // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
                    imgUrl: 'https://i04picsos.sogoucdn.com/778fa0784ef03a8e', // 分享图标
                    success: function () {
                        // 用户点击了分享后执行的回调函数
                    },
                });
            });
        });

    });
</script>
</body>
</html>