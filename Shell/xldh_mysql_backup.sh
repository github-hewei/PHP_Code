#!/bin/bash
CUR_DATE=`date +%F` # 当前日期

# 数据库信息
DB_NAME=""

# 备份存储目录
BACKUP_DIR="/home/mysql_backup/"

if [ ! -w ${BACKUP_DIR} ]; then
    echo "路径有误:""${BACKUP_DIR}"
    exit
fi

# 进入目录
cd ${BACKUP_DIR}

# 文件名
BACKUP_FN="${DB_NAME}""_${CUR_DATE}.sql"

# 执行备份命令
mysqldump ${DB_NAME} > ${BACKUP_FN}

# 压缩文件
TAR_FN="${DB_NAME}""_${CUR_DATE}.tar.gz"
tar -zcf ${TAR_FN} ${BACKUP_FN}

# 删除 SQL 文件
rm ${BACKUP_FN}

# 删除n天之前的备份
day=30

for file in `ls`;
do
    BASENAME=`basename ${file} .tar.gz`
    # 时间字符串转换
    old_date=`date -d "${BASENAME:0-10}" +%s`
    cur_date=`date -d "${CUR_DATE}" +%s`
    # 计算时间差
    diff=`expr ${cur_date} - ${old_date}`
    val=`expr 3600 \* 24 \* ${day}`
    # 超出保留时间删除备份
    if [ ${diff} -gt ${val} ]; then
        rm ${file}
    fi
done

# 每天3点备份下
# 00 03 * * * /bin/bash -x /home/website/xldh_mysql_backup.sh >> /home/website/backup.log 2>&1; echo "-----" >> /home/website/backup.log


