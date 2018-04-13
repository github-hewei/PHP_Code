<?php 
// 自定义分页类

class Page
{
    private $total;         # 总条数
    private $pageSize;      # 每页大小
    private $pageNum;       # 总页数
    private $page;          # 当前页码
    private $uri;           # 地址
    private $pageParam;     # 分页变量
    private $limit;         # LIMIT
    private $listNum = 8;   # 数字分页展示
    /**
     * 分页展示模板
     * {total}     总数据条数
     * {pagesize}  每页显示条数
     * {start}     本页开始条数
     * {end}       本页结束条数
     * {pagenum}   共有多少页
     * {frist}     首页
     * {pre}       上一页
     * {next}      下一页
     * {last}      尾页
     * {list}      数字分页
     * {goto}      跳转按钮
     */
    private $template = '<div><span>共有{total}条数据</span><span>每页显示{pagesize}条数据</span>,<span>本页{start}-{end}条数据</span><span>共有{pagenum}页</span><ul>{frist}{pre}{list}{next}{last}{goto}</ul></div>';
    # 当前选中的分页链接模板
    private $activeTemplate = '<li class="active"><a href="javascript:;">{text}</a></li>';
    # 未选中的分页链接模板
    private $notActiveTemplate = '<li><a href="{url}">{text}</a></li>';
    # 显示文本设置
    private $conf = array(
        'frist' => '首页', 'pre' => '上一页', 'next' => '下一页', 'last' => '尾页'
    );

    # 初始化
    public function init($total, $pageSize, $param='', $pageParam='page')
    {
        $this->total = intval( $total );
        $this->pageSize = intval( $pageSize );
        $this->pageParam = $pageParam;
        $this->uri = $this->getUri( $param );
        $this->pageNum = ceil( $this->total / $this->pageSize );
        $this->page = $this->getPage();
        $this->limit = $this->getLimit();
    }

    # 返回分页
    public function show()
    {
        return str_ireplace( array(
            '{total}',
            '{pagesize}',
            '{start}',
            '{end}',
            '{pagenum}',
            '{frist}',
            '{pre}',
            '{next}',
            '{last}',
            '{list}',
            '{goto}',
        ), array(
            $this->total,
            $this->setPageSize(),
            $this->star(),
            $this->end(),
            $this->pageNum,
            $this->frist(),
            $this->prev(),
            $this->next(),
            $this->last(),
            $this->pagelist(),
            $this->gopage(),
        ), $this->template );
    }

    # 获取URL
    private function getUri( $param )
    {
        $url = $_SERVER['REQUEST_URI'] . (strpos( $_SERVER['REQUEST_URI'], '?') ? '' : '?') . $param;
        $parse = parse_url( $url );
        if( isset( $parse['query'] ) ) {
            parse_str( $parse['query'], $params );
            unset( $params[ $this->pageParam ] );
            $url = $parse['path'] . '?' . http_build_query( $params );
            return $url;
        }
        return $url;
    }

    # 获取当前页
    private function getPage()
    {
        $page = isset( $_GET[ $this->pageParam ] ) && intval( $_GET[ $this->pageParam ] ) > 0
                    ? intval( $_GET[ $this->pageParam ] ) : 1;
        if( $page > $this->pageNum ) {
            $page = $this->pageNum;
        }
        return $page;
    }

    # 获取限制条数
    private function getLimit()
    {
        return sprintf( 'limit %d,%d',
            ( $this->page - 1 ) * $this->pageSize,
            $this->pageSize
        );
    }

    # 设置当前页大小
    private function setPageSize()
    {
        return $this->end() - $this->star();
    }

    # 本页结束条数
    private function end()
    {
        return min( $this->page * $this->pageSize, $this->total );
    }
    
    # 本页开始条数
    private function star()
    {
        return $this->total != 0 ?
            ( $this->page - 1 ) * $this->pageSize : 0;
    }

    # 首页
    private function frist()
    {
        return $this->replace( $this->uri . '&page=1', 
                               $this->conf['frist'],
                               ($this->page == 1) ? true : false
        );
    }

    # 上一页
    private function prev()
    {
        return $this->replace( $this->uri . '&page=' . ( $this->page - 1 ),
                               $this->conf['pre'],
                               ( $this->page == 1 ) ? false : true
        );
    }

    # 下一页
    private function next()
    {
        return $this->replace( $this->uri . '&page=' . ( $this->page + 1 ),
                               $this->conf['next'],
                               ( $this->page < $this->pageNum ) ? false : true
        );
    }

    # 最后一页
    private function last()
    {
        return $this->replace( $this->uri . '&page=' . $this->pageNum,
                               $this->conf['last'],
                               ( $this->page == $this->pageNum ) ? true : false
        );
    }

    # 分页数字列表
    private function pagelist()
    {
        $linkpage = '';
        $lastlist = floor( $this->listNum / 2 );
        for( $i = $lastlist; $i >= 1; $i-- ) {
            $page = $this->page - $i;
            if( $page >= 1 ) {
                $linkpage .= $this->replace( $this->uri . '&page=' . $page, $page, false );
            } else {
                continue;
            }
        }
        $linkpage .= $this->replace( $this->uri . '&page=' . $this->page, $this->page, true );
        for( $i = 1; $i <= $lastlist; $i++ ) {
            $page = $this->page + $i;
            if( $page <= $this->pageNum ) {
                $linkpage .= $this->replace( $this->uri . '&page=' . $page, $page, false );
            } else {
                break;
            }
        }
        return $linkpage;
    }

    # 跳转按钮
    private function gopage()
    {
        $html = ' <input type="text" value="%s" onkeydown="javascript:if(event.keyCode==13){var page=(this.value>%s)?%s:this.value;location=\'%s&page=\'+page+\'\'}" style="width:25px;"/><input type="button" onclick="javascript:var page=(this.previousSibling.value>%s)?%s:this.previousSibling.value;location=\'%s&page=\'+page+\'\'" value="GO"/>';
        return sprintf( $html,
                        $this->page ,
                        $this->pageNum,
                        $this->pageNum,
                        $this->uri,
                        $this->pageNum,
                        $this->pageNum,
                        $this->uri
        );
    }

    # 模板替换
    private function replace( $url, $text, $result = true )
    {
        $template = $result ? $this->activeTemplate : $this->notActiveTemplate;
        $html = str_replace( '{url}' , $url, $template );
        $html = str_replace( '{text}' , $text, $html );
        return $html;
    }
}