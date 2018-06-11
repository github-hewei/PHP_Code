-- 存储中医药报数据
DROP TABLE IF EXISTS `CNTCM_PDF`;
CREATE TABLE IF NOT EXISTS `CNTCM_PDF` (
    m_ID INT NOT NULL AUTO_INCREMENT,
    m_CID INT DEFAULT 0                 COMMENT '关联ID',
    m_Date CHAR(12) DEFAULT ''          COMMENT '日期',
    m_Order TINYINT(2) DEFAULT 0        COMMENT '第几版',
    m_Title VARCHAR(255) DEFAULT ''     COMMENT '标题',
    m_Path VARCHAR(255) DEFAULT ''      COMMENT '文件路径',
    m_Article text                      COMMENT '文章数据',
    m_CreateTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    m_Delete TINYINT(2) DEFAULT 0,
    PRIMARY KEY( m_ID )
)ENGINE=MYISAM DEFAULT CHARSET=UTF8 COMMENT '中国中医药报PDF数据';

ALTER TABLE CNTCM_PDF ADD m_Keywords text COMMENT '关键字列表';