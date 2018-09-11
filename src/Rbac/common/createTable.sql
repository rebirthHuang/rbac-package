CREATE TABLE `permission` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Ȩ��ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT 'Ȩ������',
  `description` varchar(200) NOT NULL DEFAULT '' COMMENT 'Ȩ������',
  `url` varchar(200) NOT NULL DEFAULT '' COMMENT '·��·��',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '����ʱ��',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '����ʱ��',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Ȩ�ޱ�';

CREATE TABLE `role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '��ɫID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '��ɫ����',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '����ʱ��',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='��ɫ��';

CREATE TABLE `role_permission` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '����ID',
  `role_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '��ɫID',
  `permission_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Ȩ��ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '����ʱ��',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='��ɫȨ�޹�����';

CREATE TABLE `user_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '����ID',
  `uid` int(10) unsigned NOT NULL COMMENT '�û�Id',
  `role_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '��ɫID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '����ʱ��',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



