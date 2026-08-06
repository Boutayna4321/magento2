CREATE DATABASE IF NOT EXISTS magento2;
CREATE USER IF NOT EXISTS 'magento'@'%' IDENTIFIED BY 'magento123';
GRANT ALL PRIVILEGES ON magento2.* TO 'magento'@'%';
FLUSH PRIVILEGES;

SET GLOBAL innodb_file_per_table = 1;
SET GLOBAL innodb_buffer_pool_size = 536870912;
SET GLOBAL character_set_server = utf8mb4;
SET GLOBAL collation_server = utf8mb4_unicode_ci;
