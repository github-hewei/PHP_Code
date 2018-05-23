<?php 

$xml = file_get_contents('http://tispfiletest.utourworld.com/upload/op/xml/agentLine/index.xml');

$domDocument = new DOMDocument( '1.0', 'GBK' );
$domDocument->loadXML( $xml );
$lineDomNodeList = $domDocument->getElementsByTagName('line');
foreach( $lineDomNodeList as $lineDomElement ) {
    # 取 line 标签下的属性
    $lineCode = $lineDomElement->getAttribute('lineCode');
    $lineName = $lineDomElement->getAttribute('lineName');
    // .... 其他属性

    // var_dump( $lineCode, $lineName );exit;
    # 取 team 标签
    $teamDomNodeList = $lineDomElement->getElementsByTagName('team')->item(0);
    # 取 team 标签下的 teamData 标签
    $teamDataDomNodeList = $teamDomNodeList->getElementsByTagName('teamData');
    foreach( $teamDataDomNodeList as $teamDataDomElement ) {
        # 取 teamData 标签下的属性
        $teamId         = $teamDataDomElement->getAttribute('teamId');
        $productCode    = $teamDataDomElement->getAttribute('productCode');
        $productName    = $teamDataDomElement->getAttribute('productName');
        // .... 其他属性

        var_dump( $teamId, $productCode, $productName );exit;
    }
}
