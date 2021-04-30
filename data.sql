# 创建数据库
DROP DATABASE IF EXISTS fairs;
CREATE DATABASE IF NOT EXISTS fairs DEFAULT CHARSET utf8 COLLATE utf8_general_ci;
USE fairs;

# 地区表
DROP TABLE IF EXISTS `region`;
CREATE TABLE `region` (
	`id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT COMMENT '系统编号',
	`type` TINYINT DEFAULT 0 COMMENT '类型，1国家，2省，3市，4区县，5乡镇，6村庄',
	
	`zone` VARCHAR(30) COMMENT '电话区号(不含国家)',
	`zip` VARCHAR(20) COMMENT '邮编号码',
	
	`country` VARCHAR(30) COMMENT '国家代码',
	`country_name` VARCHAR(50) COMMENT '国家名称',
	
	`province` VARCHAR(30) COMMENT '省份代码',
	`province_name` VARCHAR(50) COMMENT '省份名称',
	
	`city` VARCHAR(30) COMMENT '市区代码',
	`city_name` VARCHAR(50) COMMENT '市区名称',
	
	`county` VARCHAR(30) COMMENT '区县代码',
	`county_name` VARCHAR(50) COMMENT '区县名称',
	
	`town` VARCHAR(30) COMMENT '乡镇代码',
	`town_name` VARCHAR(50) COMMENT '乡镇名称',
	
	`village` VARCHAR(30) COMMENT '村庄代码',
	`village_type` VARCHAR(20) COMMENT '村庄类型',
	`village_name` VARCHAR(50) COMMENT '村庄名称',
	
	`address` TEXT COMMENT '完整地址',
	
	`created_at` TIMESTAMP NULL DEFAULT NULL COMMENT '创建时间',
	`updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT '修改时间',
	
	INDEX codes(`country`, `province`, `city`, `county`, `town`, `village`)
) ENGINE=MYISAM DEFAULT CHARSET=utf8 COMMENT='地区表';

# 银行表
DROP TABLE IF EXISTS `bank`;
CREATE TABLE `bank` (
	`id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT COMMENT '系统编号',
	
	`type` CHAR(10) DEFAULT 'DC' COMMENT '类型，DC储蓄卡, CC信用卡, SCC准贷记卡, PC预付费卡',
	`status` TINYINT NOT NULL DEFAULT 2 COMMENT '状态，0失效，1正常',
	
	`zone` VARCHAR(30) COMMENT '电话区号(不含国家)',
	`zip` VARCHAR(20) COMMENT '邮编号码',
	
	`country` VARCHAR(30) COMMENT '国家代码',
	`country_name` VARCHAR(50) COMMENT '国家名称',
	
	`province` VARCHAR(30) COMMENT '省份代码',
	`province_name` VARCHAR(50) COMMENT '省份名称',
	
	`city` VARCHAR(30) COMMENT '市区代码',
	`city_name` VARCHAR(50) COMMENT '市区名称',
	
	`county` VARCHAR(30) COMMENT '区县代码',
	`county_name` VARCHAR(50) COMMENT '区县名称',
	
	`town` VARCHAR(30) COMMENT '乡镇代码',
	`town_name` VARCHAR(50) COMMENT '乡镇名称',
	
	`village` VARCHAR(30) COMMENT '村庄代码',
	`village_type` VARCHAR(20) COMMENT '村庄类型',
	`village_name` VARCHAR(50) COMMENT '村庄名称',
	
	`address` TEXT COMMENT '完整地址',
	
	`created_at` TIMESTAMP NULL DEFAULT NULL COMMENT '创建时间',
	`updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT '修改时间',
	
	INDEX codes(`country`, `province`, `city`, `county`, `town`, `village`)
) ENGINE=MYISAM DEFAULT CHARSET=utf8 COMMENT='银行表';

# 账户表
DROP TABLE IF EXISTS `account`;
CREATE TABLE `account` (
	`id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT COMMENT '系统编号',
	`type` TINYINT DEFAULT 1 COMMENT '类型',
	`status` TINYINT DEFAULT 1 COMMENT '状态',
	
	`uid` VARCHAR(32) NOT NULL UNIQUE COMMENT '用户编号',
	`level` TINYINT DEFAULT 1 COMMENT '等级',
	`username` VARCHAR(64) NOT NULL UNIQUE COMMENT '账号',
	`password` VARCHAR(64) NOT NULL COMMENT '密码',
	`safeword` VARCHAR(64) COMMENT '安全密码',
	
	`zone` VARCHAR(24) DEFAULT '86' COMMENT '国家区号',
	`phone` VARCHAR(30) COMMENT '手机号码',
	`email` VARCHAR(64) COMMENT '邮箱',
	
	`nickname` VARCHAR(50) COMMENT '昵称',
	`avatar` VARCHAR(150) COMMENT '头像',
	`gender` TINYINT DEFAULT 0 COMMENT '性别，1男，2女，0未知',
	`birthday` DATE COMMENT '出生年月',
	
	`country` VARCHAR(30) COMMENT '国家',
	`province` VARCHAR(30) COMMENT '省份',
	`city` VARCHAR(30) COMMENT '城市',
	`county` VARCHAR(30) COMMENT '区县',
	`town` VARCHAR(30) COMMENT '乡镇',
	
	`inviter` VARCHAR(32) COMMENT '邀请者',
	
	`created_at` TIMESTAMP NULL DEFAULT NULL COMMENT '创建时间',
	`updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT '修改时间',
	`deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT '删除时间',
	
	INDEX region(`country`, `province`, `city`, `county`, `town`)
	
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COMMENT='账户表';

# 账户关连表
DROP TABLE IF EXISTS `account_link`;
CREATE TABLE `account_link` (
	`id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT COMMENT '系统编号',
	`status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态，0失效，1正常',
	
	`uid` VARCHAR(32) NOT NULL COMMENT '用户编号',
	`platform` INT DEFAULT 0 COMMENT '平台',
	`appid` VARCHAR(64) COMMENT 'appid',
	`unionid` VARCHAR(64) COMMENT '联合编号',
	`openid` VARCHAR(64) COMMENT '开放编号',
	`access_token` VARCHAR(64) COMMENT '刷新密钥',
	`session_key` VARCHAR(64) COMMENT '会话密钥',
	
	`nickname` VARCHAR(50) COMMENT '昵称',
	`avatar` VARCHAR(150) COMMENT '头像',
	`gender` TINYINT DEFAULT 0 COMMENT '性别，1男，2女，0未知',
	`country` VARCHAR(64) COMMENT '国家',
	`province` VARCHAR(64) COMMENT '省份',
	`city` VARCHAR(64) COMMENT '城市',
	
	`created_at` TIMESTAMP NULL DEFAULT NULL COMMENT '创建时间',
	`updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT '修改时间',
	`deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT '删除时间'
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COMMENT='账户关连表';

# 账户认证表
DROP TABLE IF EXISTS `account_authenticate`;
CREATE TABLE `account_authenticate` (
	`id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT COMMENT '系统编号',
	`type` TINYINT NOT NULL DEFAULT 1 COMMENT '类型，1身份证',
	`status` TINYINT NOT NULL DEFAULT 2 COMMENT '状态，0失败，1通过，2待审核',
	
	`uid` VARCHAR(32) NOT NULL COMMENT '用户编号',
	`name` VARCHAR(50) COMMENT '姓名',
	`code` VARCHAR(50) COMMENT '号码',
	`country` VARCHAR(30) COMMENT '国家',
	`province` VARCHAR(30) COMMENT '省份',
	`phone` VARCHAR(30) COMMENT '手机号码',
	
	`front` VARCHAR(150) COMMENT '正面照片',
	`back` VARCHAR(150) COMMENT '反面照片',
	`face` VARCHAR(150) COMMENT '人脸照片',
	`hold` VARCHAR(150) COMMENT '手持照片',
	`video` VARCHAR(150) COMMENT '视频',
	
	`reason` TEXT COMMENT '原因',
	
	`created_at` TIMESTAMP NULL DEFAULT NULL COMMENT '创建时间',
	`updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT '修改时间',
	`deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT '删除时间'
) ENGINE=INNODB DEFAULT CHARSET=utf8 COMMENT='账户认证表';

# 账户地址表
DROP TABLE IF EXISTS `account_address`;
CREATE TABLE `account_address` (
	`id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT COMMENT '系统编号',
	
	`is_default` TINYINT NOT NULL DEFAULT 0 COMMENT '默认，0不是，1是',
	
	`uid` VARCHAR(32) NOT NULL COMMENT '用户编号',
	`name` VARCHAR(50) COMMENT '姓名',
	`phone` VARCHAR(50) COMMENT '号码',
	
	`country` VARCHAR(30) COMMENT '国家',
	`province` VARCHAR(30) COMMENT '省份',
	`city` VARCHAR(30) COMMENT '城市',
	`county` VARCHAR(30) COMMENT '区县',
	`town` VARCHAR(30) COMMENT '乡镇',
	`address` TEXT COMMENT '详细地址',
	
	`created_at` TIMESTAMP NULL DEFAULT NULL COMMENT '创建时间',
	`updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT '修改时间',
	`deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT '删除时间'
) ENGINE=INNODB DEFAULT CHARSET=utf8 COMMENT='账户地址表';

# 钱包表
DROP TABLE IF EXISTS `wallet`;
CREATE TABLE `wallet` (
	`id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT COMMENT '系统编号',
	`type` INT DEFAULT 1 COMMENT '类型',
	`status` INT DEFAULT 1 COMMENT '状态',
	
	`uid` VARCHAR(32) NOT NULL UNIQUE COMMENT '用户编号',
	
	`money` DECIMAL(20,2) DEFAULT 0 COMMENT '可用余额',
	`money2` DECIMAL(20,2) DEFAULT 0 COMMENT '冻结余额',
	
	`score` DECIMAL(20,2) DEFAULT 0 COMMENT '可用积分',
	`score2` DECIMAL(20,2) DEFAULT 0 COMMENT '冻结积分',
	
	`spend` DECIMAL(20,2) DEFAULT 0 COMMENT '消费额',
	`spend2` DECIMAL(20,2) DEFAULT 0 COMMENT '冻结消费额',
	
	`created_at` TIMESTAMP NULL DEFAULT NULL COMMENT '创建时间',
	`updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT '修改时间',
	`deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT '删除时间'
) ENGINE=INNODB DEFAULT CHARSET=utf8 COMMENT='钱包表';