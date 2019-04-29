<div style="width: 600px">
    <script src="/js/jquery.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

        <table class="table table-striped">
            <tr>
                <th><input type="checkbox" id="all"></th>
                <th>openid</th>
            </tr>
            @foreach($openid as $k=>$v)
                <tr>
                    <td><input type="checkbox" class="check"></td>
                    <td>{{$v['openid']}}</td>
                </tr>
            @endforeach
            <tr>
                <td>内容</td>
                <td><input type="text" name="content" id="content"></td>
            </tr>
            <tr>
                <td></td>
                <td><input type="submit" value="发送" id="sub"></td>
            </tr>
        </table>

    <script>
        $(function(){

            // 全选
            $('#all').click(function(){
                var check = $(this).prop('checked');
                if(check == true){
                    $('.check').prop('checked',check);
                }else{
                    $('.check').prop('checked',false);
                }
            });

            // 消息发送
            $('#sub').click(function(){

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                var openid = '';

                $('.check').each(function(index){
                    if($('.check').prop('checked') == true){
                        openid += $(this).parent('td').next().text() + ',';
                    }
                });

                var content = $('#content').val();
                $.post(
                    '/admin/messagedo',
                    {openid:openid,content:content},
                    function(res){
                        console.log(res);
                    }
                );
            });
        });
    </script>
</div>