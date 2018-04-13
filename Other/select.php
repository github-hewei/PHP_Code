<?php
/*
    输入框待选列表
*/
// 测试数据
$list = array(
    '宋江',
    '卢俊义',
    '吴用',
    '林冲',
    '孙悟空',
    '孙悟空',
    '孙悟空',
    '孙悟空',
    '孙悟空',
    '孙悟空',
    '孙悟空',
    '孙悟空',
    '孙悟空',
    '孙悟空',
    '孙悟空',
    '孙悟空',
    '孙悟空',
    '孙悟空',
    '孙悟空',
    '孙悟空',
    '孙悟空',
    '孙悟空',
    '孙悟空',
    '猪八戒',
    '沙和尚',
    '唐玄奘',
    '林黛玉',
    '贾宝玉',
    '关羽',
    '张飞',
    '赵云',
    '黄忠',
    '马超',
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>DEMO</title>
<script src="http://apps.bdimg.com/libs/jquery/2.1.4/jquery.min.js"></script>
<style>
    #box {
        border: solid 1px;
        width: 800px;
        height: 400px;
        position: fixed;
        margin-left: 50%;
        left :-400px;
        margin-top: 40px;
    }
    #pname {
        width: 600px;
        height: 30px;
        margin-left: 100px;
        margin-top: 30px;
        font-size: 18px;
    }
    #pop {
        position: absolute;
        z-index: 999;
        border : solid 1px;
        height: 260px;
        width: 200px;
        overflow-y: auto;
        display: none;
    }
    #pop ul {
        padding-left: 2px;
        margin: 2px;
        list-style: none;
    }
    #pop li:hover {
        background-color: #eee;
    }
</style>
</head>
<body>
    <!-- 输入框 -->
    <div id="box">
        <input id="pname" type="text" />
    </div>
    <!-- 待选列表 -->
    <div id="pop">
        <ul>
            <?php foreach( $list as $value ) { ?>
            <li><?php echo $value; ?></li>
            <?php } ?>
        </ul>
    </div>
</body>
</html>
<script language="JavaScript">
    var dtime;              // 定时器
    var oldname = '';       // 关键字记录
    $(document).ready( function() {
        /* input 获得焦点 */
        $("#pname").focus( function(e){
             dtime = setInterval( function() {
                var name = $(e.target).val();
                if( name != oldname ) {
                    oldname = name;
                    var t = parseInt( $(e.target).offset().top ) + parseInt( $(e.target).outerHeight() );
                    var l = parseInt( $(e.target).offset().left );
                    var w = parseInt( $(e.target).outerWidth() ) - 2;
                    $("#pop").css( 'top', t + 'px' );
                    $("#pop").css( 'left', l + 'px' );
                    $("#pop").css( 'width', w + 'px' );
                    if( name.length < 1 ) {
                        $("#pop").hide();
                    } else {
                        $("#pop ul li").each( function(){
                            if( $(this).text().indexOf( name ) != -1 ) {
                                $(this).show();
                            } else {
                                $(this).hide();
                            }
                        } );
                        $("#pop").show();
                    }
                }
             }, 100 );
        } );
        /* input 失去焦点 */
        $("#pname").blur( function() {
            clearInterval( dtime );
            setTimeout( function(){
                $("#pop").hide();
            }, 200 );
        } );
        /* 点击选择 */
        $("#pop ul li").on( 'click', function(e) {
            oldname = $(e.target).text();
            $("#pname").val( $(e.target).text() );
        } );

    } );
</script>