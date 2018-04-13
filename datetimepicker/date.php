<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>日期时间选择 demo</title>
<link rel="stylesheet" href="http://xldhapi.tcmshow.com/js/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="http://xldhapi.tcmshow.com/css/jquery.datetimepicker.css">
<script src="http://xldhapi.tcmshow.com/js/jquery-1.7.2.min.js"></script>
<script src="http://xldhapi.tcmshow.com/js/jquery.datetimepicker.full.js"></script>
<style>
    *{padding:0; margin:0;}
</style>
</head>
<body style="padding:10px;">
    <div class="panel panel-info">
        <div class="panel-heading">
            <h3 class="panel-title">日期时间选择</h3>
        </div>
        <div class="panel-body">
            <!-- 日期选择 -->
            <div class="input-group input-group-sm" style="width:100%;">
                <span class="input-group-addon" style="width:100px;">日期选择</span>
                <input type="text" id="Date" name="Date" class="form-control" />
                <div id="panel_date" style="width:200px;height:80px;border:solid 1px #666;position:absolute;padding:8px;background:#fff;top:30px;z-index:999;display:none;">
                    <input type="text" class="_datepicker" style="width:182px;" />
                    <input type="button" class="btn btn-sm btn-warning" value="关闭" style="margin-top:8px;float:right;margin-left:6px;" onclick="javascript:$('#panel_date').hide();"/>
                    <input type="button" class="btn btn-sm btn-primary select_date" value="确认" style="margin-top:8px;float:right;" />
                </div>
            </div>

            <!-- 时间段选择 -->
            <div class="input-group input-group-sm" style="width:100%;margin-top:20px;">
                <span class="input-group-addon" style="width:100px;">时间段选择</span>
                <input type="text" id="Time" name="Time" class="form-control" />
                <div id="panel_time" style="width:200px;height:80px;border:solid 1px #666;position:absolute;padding:8px;background:#fff;top:30px;z-index:999;display:none;">
                    <input type="text" class="_timepicker stime" style="width:85px;float:left;" /> &nbsp;- 
                    <input type="text" class="_timepicker etime" style="width:85px;float:right;" />
                    <input type="button" class="btn btn-sm btn-warning" value="关闭" style="margin-top:8px;float:right;margin-left:6px;" onclick="javascript:$('#panel_time').hide();" />
                    <input type="button" class="btn btn-sm btn-primary select_time" value="确认" style="margin-top:8px;float:right;" />
                </div>
            </div>

        </div>
    </div>
</body>
</html>
<script>
    $(document).ready( function() {

        $(document).on('focus', '#Date', function(e){
            $('._datepicker').val('');
            $('#panel_date').show();
            $('._datepicker').datetimepicker( 'show' );
        });

        $(document).on('focus', '#Time', function(e){
            $('._timepicker').val( '09:00' );
            $('#panel_time').show();
            //$('._timepicker').datetimepicker( 'show' );
        });

        $(document).on('click', '.select_date', function(e){
            var date = $('._datepicker').val();
            if( date != '' ) {
                $('#Date').val( $('#Date').val() + date + ';' );
            }
            $('#panel_date').hide();
        });

        $(document).on('click', '.select_time', function(e){
            var stime = $('.stime').val();
            var etime = $('.etime').val();
            if( stime!='' && etime !='' ) {
                $('#Time').val( $('#Time').val() + stime+'-'+etime + ';' );
            }
            $('#panel_time').hide();
        });

        $('._datepicker').datetimepicker({
            timepicker : false,
            format : 'Y-m-d'
        });
        $('._timepicker').datetimepicker({
            datepicker : false,
            format : 'H:i',
            step : 5
        });


    } );

</script>