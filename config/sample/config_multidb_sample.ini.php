<?php $ini = <<<'EOF'
;*************************************************************************
; Hydrogen Configuration
;*************************************************************************

[general]
app_url = "http://example.com/app/folder"

[cache]
engine = "Memcache"

[database]
engine[USA_server] = "MysqlPDO"
host[USA_server] = "us.mydomain.com"
port[USA_server] = 3306
socket[USA_server] = 
database[USA_server] = "hydrogenapp"
username[USA_server] = "hydrogenapp"
password[USA_server] = "password"
table_prefix[USA_server] = "hydro_"

engine[EU_server] = "MysqlPDO"
host[EU_server] = "eu.mydomain.com"
port[EU_server] = 3306
socket[EU_server] = 
database[EU_server] = "hydrogenapp"
username[EU_server] = "hydrogenapp"
password[EU_server] = "password"
table_prefix[EU_server] = "hydro_"

[recache]
unique_name = 'XYZ'

[semaphore]
engine = "Cache"

[errorhandler]
log_errors = 1

[log]
engine = TextFile
logdir = cache
fileprefix = "hydro_"
; 0 = No logging
; 1 = Log Errors
; 2 = Log Warnings & worse
; 3 = Log Notices & worse
; 4 = Log Info & worse
; 5 = Log Debug messages & worse
loglevel = 1

;*************************************************************************
EOF;
?>