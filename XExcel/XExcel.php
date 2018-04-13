<?php 
/**
 * PHP操作Excel
 */
require_once 'PHPExcel.php';
require_once 'PHPExcel/IOFactory.php';

class XExcel
{
    /**
     * @param string    $fn        文件名
     * @param array     $data      数据
     * @param bool      $overwrite 覆盖原文件 默认 true
     * @return array('ret'=>0, 'error'=>'错误信息')
     */
    public function ToExcel($fn='', $data=array(), $overwrite=true)
    {
        $ET = array( 'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ','BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM','BN','BO','BP','BQ','BR','BS','BT','BU','BV','BW','BX','BY','BZ' );
        $Excel = new PHPExcel();
        $Creator = isset($data['Creator']) ? $data['Creator'] : '';                         // 创造者
        $LastModifiedBy = isset($data['LastModifiedBy']) ? $data['LastModifiedBy'] : '';    // 最后修改
        $Title = isset($data['Title']) ? $data['Title'] : '';                               // 标题
        $Subject = isset($data['Subject']) ? $data['Subject'] : '';                         // 主题
        $Description = isset($data['Description']) ? $data['Description'] : '';             // 描述
        $Keywords = isset($data['Keywords']) ? $data['Keywords'] : '';                      // 关键词
        $Category = isset($data['Category']) ? $data['Category'] : '';                      // 分类

        $Excel->getProperties()->setCreator( $Creator )
                            ->setLastModifiedBy( $LastModifiedBy )
                            ->setTitle( $Title )
                            ->setSubject( $Subject )
                            ->setDescription( $Description )
                            ->setKeywords( $Keywords )
                            ->setCategory( $Category );
        $sheet = $Excel->setActiveSheetIndex(0);
        $idx = 1;
        // 设置表头
        if(isset($data['head']) && is_array($data['head'])) {
            for($i=0,$num=count($data['head']); $i<$num; $i++) {
                $val = $data['head'][$i];
                $CellID = $ET[$i] . $idx;
                $sheet->setCellValueExplicit( $CellID, "{$val}", PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->getStyle($CellID)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $sheet->getColumnDimension($ET[$i])->setAutoSize(true);
                $sheet->getColumnDimension($ET[$i])->setWidth('AutoFit');
            }
            $idx ++;
        }

        // 设置表格数据
        if(isset($data['body'])&&is_array($data['body'])) {
            foreach($data['body'] as $line) {
                for($i=0,$num=count($line); $i<$num; $i++) {
                    $val = strip_tags( $line[$i] );
                    $CellID = $ET[$i] . $idx;
                    $sheet->setCellValueExplicit($CellID, "{$val}", PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->getStyle($CellID)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                    if( strstr( $val, 'http://' ) ) {
                        $sheet->getCell($CellID)->getHyperlink()->setUrl( $val );
                    }
                }
                $idx ++;
            }
        }

        $SheetTitle = ($Title!='') ? $Title : 'sheet1';
        $Excel->getActiveSheet()->setTitle( strip_tags($SheetTitle) );
        $Excel->setActiveSheetIndex(0);
        $objWriter = PHPExcel_IOFactory::createWriter($Excel, 'Excel5');

        if( file_exists( $fn ) ) {
            if($overwrite==false) {
                $ret['ret'] = 0;
                $ret['error'] = '文件已经存在';
                return $ret;
            }
            if( !unlink( $fn ) ) {
                $ret['ret'] = 0;
                $ret['error'] = '无法覆盖现有文件';
                return $ret;
            }
        }

        $objWriter->save( $fn );
        $ret['ret'] = 1;
        $ret['fn'] = $fn;
        return $ret;
    }

    /**
     * @param string    $fn         文件路径
     * @param int       $column     数据列数
     * @param bool      $head       是否将第一行作为 key
     * @param int       $sheet      工作表 第一个为 0
     * @return array('ret'=>0, 'error'=>'错误信息')
     */
    public function ReadExcel($fn,$column=0,$head=false,$sheet=0)
    {
        if(!file_exists($fn)) {
            $ret['ret'] = 0;
            $ret['error'] = '文件不存在';
            return $ret;
        }
        $ext = strtolower( pathinfo($fn, PATHINFO_EXTENSION) );
        if($ext=='xls') {
            // Excel5
            $reader = PHPExcel_IOFactory::createReader('Excel5');
            $excel = $reader->load($fn, 'utf-8');
        } elseif($ext=='xlsx') {
            // Excel2007
            $reader = PHPExcel_IOFactory::createReader('Excel2007');
            $excel = $reader->load($fn, 'utf-8');
        } else {
            $ret['ret'] = 0;
            $ret['error'] = '暂不支持该格式';
            return $ret;
        }
        $sheetObj = $excel->getSheet( $sheet );
        $rows = $sheetObj->getHighestRow();
        $lines = array();
        for($i=1; $i<=$rows; $i++) {
            if($head && $i==1) {
                $line1 = array();
                for($j=0; $j<$column; $j++) {
                    $line1[ $j ] = strval( $sheetObj->getCellByColumnAndRow($j,$i)->getValue() );
                }
                continue;
            }
            $line = array();
            for($j=0; $j<$column; $j++) {
                if(isset($line1[ $j ]) && $line1[ $j ]!='') {
                    $line[ $line1[$j] ] = strval( $sheetObj->getCellByColumnAndRow($j,$i)->getValue() );
                } else {
                    $line[] = strval( $sheetObj->getCellByColumnAndRow($j,$i)->getValue() );
                }
            }
            $lines[] = $line;
        }
        $ret['ret'] = 1;
        $ret['data'] = $lines;
        return $ret;
    }
}


/*/ 测试
$arr = array();
$arr['Title'] = '测试Excel导出';
$arr['head'] = array( 'Title1', 'Title2', 'Title3' );
$arr['body'] = array(
    array( 'a','b','c'),
    array( '1','2','3'),
);
$ret = XExcel::ToExcel( 'test.xls', $arr );
var_dump($ret);
//*/

/*/ 测试
$ret = XExcel::ReadExcel('限时优惠.xlsx',9,true);
var_dump($ret);
//*/