# [MySQL备份说明][0]

**阅读目录(Content)**

<font face=微软雅黑>

* [1 使用规范][1]
    * [1.1 实例级备份恢复][2]
    * [1.2 库、表级别备份恢复][3]
    * [1.3 SQL结果备份及恢复][4]
    * [1.4 表结构备份][5]

* [2 mysqldump][6]
    * [2.1 原理][7]
    * [2.2 重要参数][8]
    * [2.3 使用说明][9]
        * [2.3.1 实例备份恢复][10]
        * [2.3.2 部分备份恢复][11]

* [3 PerconaXtraBackup][12]
    * [3.1 innobackupex原理（全量说明）][13]
    * [3.2 重要参数][14]
        * [3.2.1 备份参数][15]
        * [3.2.2 准备还原参数][16]
        * [3.2.3 备份目录拷贝参数][17]
  
    * [3.3 使用说明][18]
        * [3.3.1 实例备份及恢复][19]
        * [3.3.2 部分备份][20]

第一次发布博客，发现目录居然不会生成，后续慢慢熟悉博客园的设置。回正文～～～



### 1 使用规范

#### 1.1 实例级备份恢复

使用`innobackupex`，在业务空闲期执行，考虑到IO影响及 FLUSH TABLE WITH READ LOCAK 拷贝非INNODB文件的锁表时间。

常规备份中，使用`innobackupex`在从库备份执行，在无从库的情况下，允许在业务低峰期对整个实例拷贝。

#### 1.2 库、表级别备份恢复

考虑 数据量、磁盘IO情况、恢复难度问题。

`mysqldump`锁表时间长，备份时间长，但是导入方便，适合数据量小但是表格多 的库/表级别备份。

`innobackupex`锁表时间短，备份时间短，但是恢复较复杂，需要`discord tablespace`及 `import TABLESPACE`，除非允许备份文件成立单个实例，适合表数据量大但表格数量少的库/表级别备份。

#### 1.3 SQL结果备份及恢复

如果是单表简单查询，使用`mysqldump`，添加`where`条件，例如：`mysqldump -S /tmp/mysql3330.sock -uroot -p --databases db1 --tables tb1 tb2 tb3 -d >/data/backup/3330/mysqldump_20161229.sql` 。

如果是复杂SQL查询结果，使用 `INTO OUTFILE`，如下：

 
```
#FIELDS TERMINATED BY ',' 字段间分割符
#OPTIONALLY ENCLOSED BY '"' 将字段包围 对数值型无效
#LINES TERMINATED BY '\n' 换行符
 
#查询导出
select * into outfile '/tmp/pt.txt' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' LINES TERMINATED BY '\n' from pt where id >3;
 
#加载数据
load data infile '/tmp/pt1.txt'  into table pt FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' LINES TERMINATED BY '\n'
```

#### 1.4 表结构备份

使用`mysqldump`，添加`-d`参数。


### 2 mysqldump

支持功能多且全面，但是锁表时间是个风险点，使用时注意，同时，若是5.6版本之前的，要充分考虑`buffer pool`的使用情况。

#### 2.1 原理

通过`general log`查看`mysqldump`运行原理，详细流程见代码块 `mysqldump`。

`mysqldump`运行中，  
第一步，会检查数据库的配置情况，例如是否设置GTID模式及参数配置；  
第二步，锁所有表格，只允许读操作；  
第三步，逐个拷贝表格，生成创建表格上SQL（字符集为binary），再`SELECT * FROM 表格` 生成数据脚步（字符集为UTF8）；  
第四步，解锁。

当导出全实例或者大数据库时，这里有2个需要注意到问题：

* 锁表的时间 
    * 基本可以算是从开始到结束都是锁表期间，不能对数据库进行写操作，只能读
    * 线上主库无法支持这么长时间的锁表操作
    * 线上从库，应考虑对复制到影响

* `buffer pool`的影响 
    * 由于是采用`SELECT *` 生成SQL语句，大量读操作，会把缓存里的数据清理出来，导致热点数据移出，对线上DML操作带来严重影响
    * 5.6后版本,新增了`young buffer pool`，一秒内以这个数据被再次访问，则会进入到`buffer pool` 的warm区。youny区占`buffer pool`的3/8，剩下的5/8为warm区，可以有效保证热点数据不被清出。

执行SQL：

    mysqldump -S /tmp/mysql3330.sock -uroot -p --databases zero >/data/backup/3330/mysqldump_20161229.sql

```
2016-12-27T14:38:27.782875Z     1732 Connect    root@localhost on  using Socket
2016-12-27T14:38:27.803572Z     1732 Query    /*!40100 SET @@SQL_MODE='' */
2016-12-27T14:38:27.804096Z     1732 Query    /*!40103 SET TIME_ZONE='+00:00' */
2016-12-27T14:38:27.804528Z     1732 Query    SHOW VARIABLES LIKE 'gtid\_mode' #检查是否设置了GTID
2016-12-27T14:38:27.813387Z     1732 Query    SELECT LOGFILE_GROUP_NAME, FILE_NAME, TOTAL_EXTENTS, INITIAL_SIZE, ENGINE, EXTRA FROM INFORMATION_SCHEMA.FILES WHERE FILE_TYPE = 'UNDO LOG' AND FILE_NAME IS NOT NULL AND LOGFILE_GROUP_NAME IS NOT NULL AND LOGFILE_GROUP_NAME IN (SELECT DISTINCT LOGFILE_GROUP_NAME FROM INFORMATION_SCHEMA.FILES WHERE FILE_TYPE =  'DATAFILE' AND TABLESPACE_NAME IN (SELECT DISTINCT TABLESPACE_NAME FROM INFORMATION_SCHEMA.PARTITIONS WHERE TABLE_SCHEMA IN ('zero'))) GROUP BY LOGFILE_GROUP_NAME, FILE_NAME, ENGINE, TOTAL_EXTENTS, INITIAL_SIZE ORDER BY LOGFILE_GROUP_NAME
2016-12-27T14:38:27.816987Z     1732 Query    SELECT DISTINCT TABLESPACE_NAME, FILE_NAME, LOGFILE_GROUP_NAME, EXTENT_SIZE, INITIAL_SIZE, ENGINE FROM INFORMATION_SCHEMA.FILES WHERE FILE_TYPE = 'DATAFILE' AND TABLESPACE_NAME IN (SELECT DISTINCT TABLESPACE_NAME FROM INFORMATION_SCHEMA.PARTITIONS WHERE TABLE_SCHEMA IN('zero')) ORDER BY TABLESPACE_NAME, LOGFILE_GROUP_NAME
2016-12-27T14:38:27.819423Z     1732 Query    SHOW VARIABLES LIKE 'ndbinfo\_version'
2016-12-27T14:38:27.824802Z     1732 Init DB    zero
2016-12-27T14:38:27.825015Z     1732 Query    SHOW CREATE DATABASE IF NOT EXISTS `zero` #生成创建数据库的的脚步
2016-12-27T14:38:27.825381Z     1732 Query    show tables #检查该数据库里边有多少表格，根据这些表格来开始lock table
2016-12-27T14:38:27.825969Z
    1732 Query    LOCK TABLES `dsns` READ /*!32311 LOCAL */,`pt` READ
/*!32311 LOCAL */,`sbtest20` READ /*!32311 LOCAL */
#锁表，仅允许读操作
 
########################################每个表格重复部分############################################################
2016-12-27T14:38:27.826324Z     1732 Query    show table status like 'dsns'
2016-12-27T14:38:27.832651Z     1732 Query    SET SQL_QUOTE_SHOW_CREATE=1
2016-12-27T14:38:27.832930Z     1732 Query    SET SESSION character_set_results = 'binary'
2016-12-27T14:38:27.833169Z     1732 Query    show create table `dsns`
#字符集修改为 binary，生成架构SQL
2016-12-27T14:38:27.833448Z     1732 Query    SET SESSION character_set_results = 'utf8'
2016-12-27T14:38:27.833793Z     1732 Query    show fields from `dsns`
2016-12-27T14:38:27.834697Z     1732 Query    show fields from `dsns`
2016-12-27T14:38:27.835598Z     1732 Query    SELECT /*!40001 SQL_NO_CACHE */ * FROM `dsns`
#字符集修改为 utf8，导出数据SQL
2016-12-27T14:38:27.836129Z     1732 Query    SET SESSION character_set_results = 'binary'
2016-12-27T14:38:27.836401Z     1732 Query    use `zero`
2016-12-27T14:38:27.836644Z     1732 Query    select @@collation_database
2016-12-27T14:38:27.836949Z     1732 Query    SHOW TRIGGERS LIKE 'dsns'
2016-12-27T14:38:27.837738Z     1732 Query    SET SESSION character_set_results = 'utf8'
########################################每个表格重复部分############################################################
 
#每个表格的导出都重复上述部分
 
2016-12-27T14:38:28.525530Z     1732 Query    SET SESSION character_set_results = 'utf8'
2016-12-27T14:38:28.525832Z     1732 Query    UNLOCK TABLES
#解锁，允许读写
```

#### 2.2 重要参数

以下参数在使用过程中，需要留意，根据实际情况添加：

* `--master-data=1 /2`

    生产`change master to`语句，这里注意，`lock table` 的时间，会提前到最开始的时候，不过相差的时间段非常小。

    1. 则是生产 `change master to`语句 不加注释符号，直接执行；

    2. 生成`change master to`语句，加注释符号

* `--singe-transaction`

    确保事物一致性，建议在GTID模式添加

* `--set-gtid-purged=ON / OFF`

    在`GTID`模式下的`dump`语句，会自动在备份文件之前生成 

    如果打算把该脚本放在非GTID模式的数据库执行，建议添加 `--set-gtid-purged=OFF` ，关闭生成purge 或者是去文件中注释掉该语句

* `-d`

    只导出表结构

* `--databases`

    不更随`--tables`的时候，可以指定多个db，如果指定了`--tables`，则默认第一个是`database`，其他的是table

    也就是只允许导多个DB的数据文件，或者导同个DB的多个table文件；不允许到不同DB的某些table文件

 `mysqldump`主要参数
主要参数相见代码模 `mysqldump`主要参数，并非所有参数内容，这些参数较常使用。


```
    [root@localhost zero]# mysqldump --help
    Dumping structure and contents of MySQL databases and tables.
    Usage: mysqldump [OPTIONS] database [tables]
    OR     mysqldump [OPTIONS] --databases [OPTIONS] DB1 [DB2 DB3...]
    OR     mysqldump [OPTIONS] --all-databases [OPTIONS]
     
     
    --no-defaults           Don't read default options from any option file,
                            except for login file.
    --defaults-file=#       Only read default options from the given file #.
     
     
      -A, --all-databases Dump all the databases. This will be same as --databases
                          with all databases selected.
      -Y, --all-tablespaces
                          Dump all the tablespaces.
      -y, --no-tablespaces
                          Do not dump any tablespace information.
      --add-drop-database Add a DROP DATABASE before each create.
      --add-drop-table    Add a DROP TABLE before each create.
                          (Defaults to on; use --skip-add-drop-table to disable.)
      --add-drop-trigger  Add a DROP TRIGGER before each create.
      --add-locks         Add locks around INSERT statements.
                          (Defaults to on; use --skip-add-locks to disable.)
     
     
      --apply-slave-statements
                          Adds 'STOP SLAVE' prior to 'CHANGE MASTER' and 'START
                          SLAVE' to bottom of dump.
     
     
      -B, --databases     Dump several databases. Note the difference in usage; in
                          this case no tables are given. All name arguments are
                          regarded as database names. 'USE db_name;' will be
                          included in the output.
     
     
      --master-data[=#]   This causes the binary log position and filename to be
                          appended to the output. If equal to 1, will print it as a
                          CHANGE MASTER command; if equal to 2, that command will
                          be prefixed with a comment symbol. This option will turn
                          --lock-all-tables on, unless --single-transaction is
                          specified too (in which case a global read lock is only
                          taken a short time at the beginning of the dump; don't
                          forget to read about --single-transaction below). In all
                          cases, any action on logs will happen at the exact moment
                          of the dump. Option automatically turns --lock-tables
                          off.
      -n, --no-create-db  Suppress the CREATE DATABASE ... IF EXISTS statement that
                          normally is output for each dumped database if
                          --all-databases or --databases is given.
      -t, --no-create-info
                          Don't write table creation info.
      -d, --no-data       No row information.
      -p, --password[=name]
                          Password to use when connecting to server. If password is
                          not given it's solicited on the tty.
      -P, --port=#        Port number to use for connection.
     
     
      --replace           Use REPLACE INTO instead of INSERT INTO.
     
     
      --set-gtid-purged[=name]
                          Add 'SET @@GLOBAL.GTID_PURGED' to the output. Possible
                          values for this option are ON, OFF and AUTO. If ON is
                          used and GTIDs are not enabled on the server, an error is
                          generated. If OFF is used, this option does nothing. If
                          AUTO is used and GTIDs are enabled on the server, 'SET
                          @@GLOBAL.GTID_PURGED' is added to the output. If GTIDs
                          are disabled, AUTO does nothing. If no value is supplied
                          then the default (AUTO) value will be considered.
      --single-transaction
                          Creates a consistent snapshot by dumping all tables in a
                          single transaction. Works ONLY for tables stored in
                          storage engines which support multiversioning (currently
                          only InnoDB does); the dump is NOT guaranteed to be
                          consistent for other storage engines. While a
                          --single-transaction dump is in process, to ensure a
                          valid dump file (correct table contents and binary log
                          position), no other connection should use the following
                          statements: ALTER TABLE, DROP TABLE, RENAME TABLE,
                          TRUNCATE TABLE, as consistent snapshot is not isolated
                          from them. Option automatically turns off --lock-tables.
     
     
      --tables            Overrides option --databases (-B).
      --triggers          Dump triggers for each dumped table.
                          (Defaults to on; use --skip-triggers to disable.)
      -u, --user=name     User for login if not current user.
```



#### 2.3 使用说明

语法主要有以下三类：

    Usage: mysqldump [OPTIONS] database [tables]
    OR     mysqldump [OPTIONS] --databases [OPTIONS] DB1 [DB2 DB3...]
    OR     mysqldump [OPTIONS] --all-databases [OPTIONS]

##### 2.3.1 实例备份恢复

 
```

    #实例备份
    mysqldump -S /tmp/mysql3330.sock -uroot -p --all-datqabases >/data/backup/3330/mysqldump_20161229.sql
     
    #实例恢复
    #新建实例后，导入脚本
    mysql --socket=/tmp/mysql3306.sock -uroot -p < /data/backup/3330/mysqldump_20161229.sql
```

##### 2.3.2 部分备份恢复

 
```
#指定单个或者多个DB备份
mysqldump -S /tmp/mysql3330.sock -uroot -p db1 db2 db3 >/data/backup/3330/mysqldump_20161229.sql
mysqldump -S /tmp/mysql3330.sock -uroot -p --databases db1 db2 db3 >/data/backup/3330/mysqldump_20161229.sql
 
#指定单个或者多个表格备份
mysqldump -S /tmp/mysql3330.sock -uroot -p --databases db1 --tables tb1 tb2 tb3 >/data/backup/3330/mysqldump_20161229.sql
mysqldump -S /tmp/mysql3330.sock -uroot -p db1 tb1 tb2 tb3 >/data/backup/3330/mysqldump_20161229.sql
 
#只导出单个表格的某些行数据
mysqldump -S /tmp/mysql3330.sock -uroot -pycf.com zero pt --where='1=1 limit 2' >/data/backup/3330/mysqldump_20161229.sql
 
#只备份表结构，不要表数据
mysqldump -S /tmp/mysql3330.sock -uroot -p --databases db1 --tables tb1 tb2 tb3 -d >/data/backup/3330/mysqldump_20161229.sql
 
#只备份表数据，不要表结构
mysqldump -S /tmp/mysql3330.sock -uroot -pycf.com zero pt --where='id>3' --no-create-info  >/data/backup/3330/mysqldump_20161229.sql
 
#恢复数据
source /data/backup/3330/mysqldump_20161229.sql
```


### 3 PerconaXtraBackup

`PerconaXtraBackup`软件中，含有`xtrabackup`跟`innobackupex`，`xtrabackup`中不备份表结构，`innobackupex`调用`xtrabackup`子线程后再备份表结构，故常用`innobackupex`，`xtraback`不做日常使用。目前支持 `Myisam`,`innodb`，可以备份 `.frm`, `.MRG`, `.MYD`, `.MYI`, `.MAD`, `.MAI`, `.TRG`, `.TRN`, `.ARM`, `.ARZ`, `.CSM`, CSV, `.opt`, `.par`, `innoDB data` 及`innobdb log` 文件。

#### 3.1 innobackupex原理（全量说明）

对数据库文件进行copy操作，同时建立多一个`xtrabackup log` 同步mysql的`redo线程`，copy数据文件结束时，`flush table with read lock`，拷贝非innodb数据文件的文件，拷贝结束后解锁。原理图见下图（图片来自知数堂）。通过general log查看mysqldump运行原理，详细流程见代码块 `innobackupex`。

![][25]

这里需要注意2个点：

* 锁表时间

    `innobackupex`锁表时间是 data文件及log文件copy结束时，才锁表，锁表时长为拷贝non-InnoDB tables and files的时长，相对时间较短，对业务影响小。

* 大事务

    copy数据文件的过程中，由于是不锁表，允许数据进行DML操作，这里需要注意，如果这个时候，拷贝的过程中有大事务一直没有提交，界面显示`log scanned up`，持续copy binlog追上数据库的binlog文件，并且该时间点刚好所有事务已提交（这里测试的时候，如果是单条 `insert` ，`delete`，`update`的大事务，则是要等待单条完成才提交，但是如果是begin事务里边的，不用等待是否`commit or rollback`，begin里边的单条事务执行结束，则就开始提交，恢复的时候，当作是undo 事务，不会提交该事物，回滚该事务）。大事务容易导致备份时长加长，IO占用。

 
```
2016-12-26T15:18:39.627366Z     1659 Connect    root@localhost on  using Socket
2016-12-26T15:18:39.627789Z     1659 Query    SET SESSION wait_timeout=2147483
2016-12-26T15:18:39.628193Z     1659 Query    SHOW VARIABLES 
#记录LSN号码，开始copy ibd文件
2016-12-26T15:18:55.673740Z     1659 Query    SET SESSION lock_wait_timeout=31536000
2016-12-26T15:18:55.674281Z     1659 Query    FLUSH NO_WRITE_TO_BINLOG TABLES
#强制把没有 还没写入binlog 磁盘文件的缓存 强制刷新到磁盘
#开始拷贝数据库文件，这里需要注意，如果这个时候，拷贝的过程中有大事务一直没有提交，则会一直拷贝其产生的 ，界面显示log scanned up，直到copy binlog追上数据库的binlog文件，并且该时间点刚好所有事务已提交（这里测试的时候，如果是单条 insert ，delete，update的大事务，则是要等待单条完成才提交，但是如果是begin事务里边的，不用等待是否commit or rollback，begin里边的单条事务执行结束，则就开始提交，恢复的时候，当作是undo 事务，不会提交该事物，回滚该事务。 ）
2016-12-26T15:18:55.676345Z     1659 Query    FLUSH TABLES WITH READ LOCK
#锁表，只允许读，不允许写及其他架构修改操作
#拷贝除innodb 数据文件外的其他所有文件，包括表结构等，Starting to backup non-InnoDB tables and files
2016-12-26T15:18:59.691409Z     1659 Query    SHOW MASTER STATUS
#记录 备份到的 binlog文件及position位置，这个记录在 xtrabackup_binlog_info 文件，可提供复制使用
2016-12-26T15:18:59.734418Z     1659 Query    SHOW VARIABLES
2016-12-26T15:18:59.754530Z     1659 Query    FLUSH NO_WRITE_TO_BINLOG ENGINE LOGS
2016-12-26T15:18:59.968452Z     1659 Query    UNLOCK TABLES
#解锁，表格恢复可写，架构可修改
2016-12-26T15:18:59.991046Z     1659 Query    SELECT UUID()
2016-12-26T15:19:00.005980Z     1659 Query    SELECT VERSION()
```

#### 3.2 重要参数

##### 3.2.1 备份参数

```
innobackupex [--compress] [--compress-threads=NUMBER-OF-THREADS] [--compress-chunk-size=CHUNK-SIZE]
             [--encrypt=ENCRYPTION-ALGORITHM] [--encrypt-threads=NUMBER-OF-THREADS] [--encrypt-chunk-size=CHUNK-SIZE]
             [--encrypt-key=LITERAL-ENCRYPTION-KEY] | [--encryption-key-file=MY.KEY]
             [--include=REGEXP]
             [--user=NAME]
             [--password=WORD] [--port=PORT] [--socket=SOCKET]
             [--no-timestamp] [--ibbackup=IBBACKUP-BINARY]
             [--slave-info] [--galera-info] [--stream=tar|xbstream]
             [--defaults-file=MY.CNF] [--defaults-group=GROUP-NAME]
             [--databases=LIST]
             [--no-lock] #不执行FLUSH TABLES WITH READ LOCK，建议不使用，不会拷贝undo及redo文件
             [--no-timestamp]
             [--kill-long-queries-timeout=#] 
             [--tmpdir=DIRECTORY] [--tables-file=FILE]
             [--history=NAME]
             [--incremental] [--incremental-basedir]
             [--incremental-dir] [--incremental-force-scan] [--incremental-lsn]
             [--incremental-history-name=NAME] [--incremental-history-uuid=UUID]
             [--close-files] [--compact]  BACKUP-ROOT-DIR

```

##### 3.2.2 准备还原参数

根据 `BACKUP-DIR/xtrabackup_logfile`创建新的`logfile`，`xtrabackup`为子进程，不连接数据库服务.

```
innobackupex --apply-log [--use-memory=B]
             [--defaults-file=MY.CNF]
             [--export] [--redo-only] [--ibbackup=IBBACKUP-BINARY]
             BACKUP-DIR
```

**BACKUP-DIR**

##### 3.2.3 备份目录拷贝参数

* 拷贝备份目录到指定目录，备份目录及拷贝目录文件均存在

    `innobackupex --copy-back [--defaults-file=MY.CNF] [--defaults-group=GROUP-NAME] BACKUP-DIR`

* 移动备份目录到指定目录，备份目录为空

    `innobackupex --move-back [--defaults-file=MY.CNF] [--defaults-group=GROUP-NAME] BACKUP-DIR`

#### 3.3 使用说明

##### 3.3.1 实例备份及恢复

###### 3.3.1.1 全量备份

 
```
#全量备份 实例备份及恢复
#备份
innobackupex --defaults-file=/data/mysql/mysql3330.cnf --user=root --password=ycf.com --no-timestamp  /data/backup/3330/20161229
innobackupex --apply-log  /data/backup/3330/20161229
 
#恢复
innobackupex --copy-back --datadir=/data/mysql/mysql3350/data /data/backup/3330/20161229
```

###### 3.3.1.2 增量备份恢复

 
```
#增量备份
innobackupex --defaults-file=/data/mysql/mysql3376.cnf --user=root --password=ycf.com --no-timestamp --incremental-basedir=/data/backup/3330/20161229 --incremental /data/backup/mysql3376/20161230diff
 
innobackupex --defaults-file=/data/mysql/mysql3376.cnf --user=root --password=ycf.com --no-timestamp --incremental-basedir=/data/backup/3330/20161230diff --incremental /data/backup/mysql3376/20161231diff
 
#增量恢复
#现在完整备份文件中中应用redo日志，记得是redo-only， redo-only， redo-only， redo-only， 不是readonly，打死记得，不要乱来！！！！！！
innobackupex --apply-log --redo-only /data/backup/3330/20161229
 
#应用第一个增量备份文件的redo日志到完整备份文件夹中
innobackupex --apply-log --redo-only /data/backup/3330/20161229 --incremental-dir=/data/backup/mysql3376/20161230diff
 
#应用最后一个增量备份文件的redo日志到完整备份文件夹中，可以直接apply-log
innobackupex --apply-log /data/backup/3330/20161229 --incremental-dir=/data/backup/mysql3376/20161231diff
```

##### 3.3.2 部分备份

 
```
#部分备份
#指定数据库备份
innobackupex --defaults-file=/data/mysql/mysql3330.cnf --databases='zero mysql' --user=root --password=ycf.com --no-timestamp /data/backup/3330/20161202
 
#指定表格备份
#3.1 --include 使用正则表达式
 
#3.2 --table-file 备份的完整表名写在file文件中
vim /tmp/backupfile 
#每行写一个库名，或者一个表的全名（database.table），写完库名或者表名后，千万不要有空格或者其他空白符号，会导致识别不了该表格或者库名，从而导致跳过
innobackupex --defaults-file=/data/mysql/mysql3330.cnf --tables-file=/tmp/backupfile --user=root --password=ycf.com --no-timestamp  /data/backup/3330/20161204
 
#3.3 --databases 完整库名和表名写在一起，用空格隔开
innobackupex --defaults-file=/data/mysql/mysql3330.cnf --user=root --password=ycf.com --no-timestamp --databases=zero.s1 /data/backup/3330/20161229
 
#指定表格恢复(开启独立表空间)
#首先要自己现在需要恢复的数据库上，创建该表格，然后discard tablespace,拷贝ibd文件过来，chown 文件所有者及用户组为mysql，再 import tablespace。
#如果有大量表格，用这个操作就比较麻烦，需要一个个来创建，包括指定数据库，也是这样处理，整个数据库先创建之后，在一个个表格discard，再import。
ALTER TABLE S1 DISCARD TABLESPACE;
ALTER TABLE S1 import TABLESPACE;
```

如果转载，请注明博文来源： www.cnblogs.com/xinysu/ ，版权归 博客园 苏家小萝卜 所有。望各位支持！

</font>

[0]: http://www.cnblogs.com/xinysu/p/6229991.html
[1]: #_label0
[2]: #_lab2_0_0
[3]: #_lab2_0_1
[4]: #_lab2_0_2
[5]: #_lab2_0_3
[6]: #_label1
[7]: #_lab2_1_0
[8]: #_lab2_1_1
[9]: #_lab2_1_2
[10]: #_label3_1_2_0
[11]: #_label3_1_2_1
[12]: #_label2
[13]: #_lab2_2_0
[14]: #_lab2_2_1
[15]: #_label3_2_1_0
[16]: #_label3_2_1_1
[17]: #_label3_2_1_2
[18]: #_lab2_2_2
[19]: #_label3_2_2_0
[20]: #_label3_2_2_1
[22]: #_labelTop
[25]: ./img/421054534.png