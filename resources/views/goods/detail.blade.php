<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>商品推荐</title>
    <style>
        #div{
            margin: 0 auto;
            width: 600px;
            height: 600px;
        }

        #div dl dt{
            float: left;
        }
        #div dl dd{
            float: left;
            margin-bottom: 100px;
        }
        #a{
            clear: both;
            margin-left: 100px;
        }
    </style>
</head>
<body>
<div id="div">
    <dl>
        <dt><img src="http://www.lab993.com/uploads/goodsimgs/{{$detail->goods_img}}" width="150" height="150" /></dt>
        <dd>
            <h3>{{$detail->goods_name}}</h3>
            <div class="prolist-price"><strong>¥{{$detail->self_price}}</strong> <span>¥{{$detail->market_price}}</span></div>
            <div class="prolist-yishou"><span>5.0折</span> <em>销量：{{$detail->goods_score}}</em></div>
        </dd>
        <div class="clearfix"></div>
    </dl>
    <div id="a">

    </div>
</div>
<script src="/js/qrcode.js"></script>
<script src="/js/jquery.js"></script>
<script>
    new QRCode(document.getElementById("a"), "{{$url}}");
</script>

</body>
</html>