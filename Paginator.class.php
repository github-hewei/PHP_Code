<?php // CODE BY HW 
// 自定义分页类
class Paginator
{
    // 记录总条数
    private $total_count;

    // 单页数据条数
    private $page_size;

    // 总页数
    private $total_page;

    // 当前页码
    private $page_index;

    // 请求地址
    private $url;

    /**
     * 构造方法
     * @param int $total_count 数据总条数
     * @param int $page_index 当前页码 
     * @param int $page_size 单页数据条数
     */
    public function __construct($total_count, $page_index, $page_size=10)
    {
        $this->total_count = intval($total_count);
        $this->page_index = intval($page_index);
        $this->page_size = intval($page_size);
        $this->total_page = intval(ceil($this->total_count / $this->page_size));
        $this->url = $this->getURL();
        if( $this->page_index > $this->total_page ) {
            $this->page_index = $this->total_page;
        }
    }   

    /**
     * 输出html
     */
    public function render()
    {
        echo '<div style="width:100%;text-align:center;">';
        echo '<ul style="margin:0;padding:0;display:inline-block;">';
        echo $this->li("共 {$this->total_count} 条");
        echo $this->li("共 {$this->total_page} 页");
        echo $this->li( $this->first() );
        echo $this->li( $this->prev() );
        foreach( $this->nav() as $key => $item ) {
            if( $this->page_index === $key ) {
                echo $this->li($item, "background:#337ab7;");
            } else {
                echo $this->li($item);
            }
        }
        echo $this->li( $this->next() );
        echo $this->li( $this->last() );
        echo $this->li("每页展示 {$this->select_size()} 条");
        echo "</ul>";
        echo "</div>";
    }

    /**
     * 获取limit
     */
    public function limit()
    {
        return sprintf(
            "%d, %d",
            ($this->page_index - 1) * $this->page_size,
            $this->page_size 
        );
    }

    /**
     * 首页
     */
    private function first() 
    {
        return $this->a($this->url.'&page=1', '首页');
    }

    /**
     * 尾页
     */
    private function last() 
    {
        return $this->a($this->url.'&page='.$this->total_page, '尾页');
    }

    /**
     * 下一页
     */
    private function next() 
    {
        if( $this->page_index >= $this->total_page ) {
            return "";
        }
        return $this->a($this->url.'&page='.($this->page_index+1), '下一页');
    }

    /**
     * 上一页
     */
    private function prev() 
    {
        if( $this->page_index <= 1 ) {
            return "";
        }
        return $this->a($this->url.'&page='.($this->page_index-1), '上一页');
    }

    /**
     * 页码导航
     */
    private function nav()
    {
        $list = array();
        if( $this->total_page > 0 ) {
            $left_count = 1;    
            $right_count = 3;   
            $start_index = $this->page_index - $left_count; 
            $end_index   = $this->page_index + $right_count;
            if( $start_index < 1 ) {
                $end_index += abs( $start_index - 1 );
                $start_index = 1;
            }
            $num = $this->total_page - $end_index;      
            if( $num < 0 ) {
                $end_index = $this->total_page;
                $start_index = max($start_index + $num, 1);
            }
            for($i=$start_index; $i<=$end_index; $i++) {
                $list[$i] = $this->a($this->url.'&page='.$i, $i);
            }
        }
        return $list;
    }

    /**
     * 每页条数选择
     */
    private function select_size()
    {
        $list = array( 10, 15, 30, 100 );
        $str = '<select style="height:20px;" onchange="location.href=this.value">';
        foreach($list as $size) {
            $parse = parse_url($this->url);
            $params = array();
            if( isset($parse['query']) ) {
                parse_str($parse['query'], $params);
                unset($params['size']);
            }
            $url = $parse['path'] .'?' . http_build_query($params);
            $value = sprintf('%s&page=%d&size=%d', $url, $this->page_index, $size);
            $attr = '';
            if( $this->page_size === $size ) {
                $attr = ' selected="selected"';
            }
            $str .= sprintf('<option value="%s"%s>%s</option>', $value, $attr, $size);
        }
        $str .= '</select>';
        return $str;
    }

    /**
     * 生成一个li标签
     */
    private function li($content, $style='')
    {
        if( strlen($content) === 0 ) {
            return "";
        }
        $template = '<li style="display:block;height:26px;line-height:26px;float:left;border:solid 1px #337ab7;padding:0 8px;margin:0 1px;%s">%s</li>';
        return sprintf($template, $style, $content);
    }

    /**
     * 生成一个a标签
     */
    private function a($url, $text, $style='')
    {
        $template = '<a href="%s" style="margin:0;color:#000;text-decoration:none;%s">%s</a>';
        return sprintf($template, $url, $style, $text);
    }

    /**
     * 获取当前页面地址
     */
    private function getURL()
    {
        $url = $_SERVER['REQUEST_URI'];
        $parse = parse_url($url);
        $params = array();
        if( isset($parse['query']) ) {
            parse_str($parse['query'], $params);
            unset($params['page']); 
        }
        return $parse['path'] . '?' . http_build_query($params);
    }
}

