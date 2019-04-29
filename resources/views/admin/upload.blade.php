<div style="width: 600px">
    <form class="form-horizontal" action="/admin/getupload" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="inputEmail3" class="col-sm-2 control-label">文件类型</label>
            <div class="col-sm-10">
                <select class="form-control" name="type">
                    <option>image</option>
                    <option>video</option>
                    <option>voice</option>
                    <option>thumb</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="inputPassword3" class="col-sm-2 control-label">选择图片</label>
            <div class="col-sm-10">
                <input type="file" name="file" id="exampleInputFile">
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-default">提交</button>
            </div>
        </div>
    </form>
</div>
