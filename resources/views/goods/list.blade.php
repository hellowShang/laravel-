<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>商品推荐</title>
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

<script src="http://res2.wx.qq.com/open/js/jweixin-1.4.0.js"></script>
<script>
        // 通过config接口注入权限验证配置
        wx.config({
            debug: true, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
            appId: "{{$appid}}", // 必填，公众号的唯一标识
            timestamp:"{{$timestamp}}", // 必填，生成签名的时间戳
            nonceStr: "{{$noncestr}}", // 必填，生成签名的随机串
            signature: "{{$signature}}",// 必填，签名
            jsApiList: ['updateAppMessageShareData'] // 必填，需要使用的JS接口列表
        });

        wx.ready(function () {   //需在用户可能点击分享按钮前就先调用
            wx.updateAppMessageShareData({
                title: '最新商品数据推荐', // 分享标题
                desc: '没什么可说的', // 分享描述
                link: 'http://wechar.lab993.com/goods/list', // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
                imgUrl: 'http://blog.lab993.com/uploads/goodsimgs/20190220/9974b706375f38d1834dc58df0ec5878.jpg', // 分享图标
                success: function () {
                    // 设置成功
                    alert('分享成功');
                }
            })
        });
</script>
</body>
</html>