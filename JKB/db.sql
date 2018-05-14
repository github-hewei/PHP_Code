
/* 新版 爬取杏林大会资讯 临时存储 */

drop table if exists temp_information;

create table temp_information (
    m_ID int not null auto_increment,
    m_Type int default 0 comment '区分是哪里爬取的',                        /* 1. 健康报药膳食疗 */
    m_OtherID varchar(32) default '' comment '文章标识',
    m_Title varchar(255) default '' comment '标题',
    m_Cover varchar(255) default '' comment '封面',
    m_Form varchar(255) default '' comment '来源',
    m_Body text default '' comment '内容主体',
    m_PublishTime datetime default null comment '发表时间',
    m_CreateTime timestamp default current_timestamp,
    m_Delete tinyint(2) default 0,
    primary key( m_ID )
)engine=myisam default charset=utf8 comment '爬取资讯临时存储';

alter table temp_information change m_Form m_From varchar(255) default '' comment '来源';
alter table temp_information add m_OriginalURL varchar(255) default '' comment '原网页';