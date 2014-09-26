ALTER TABLE `users` ADD `gender` ENUM( 'male', 'female' ) NOT NULL AFTER `phone_number_tr` ,
ADD `age` INT( 3 ) NOT NULL AFTER `gender` ,
ADD `country` VARCHAR( 100 ) NOT NULL AFTER `age` ;

ALTER TABLE `users` ADD `has_reminder` TINYINT( 1 ) NOT NULL AFTER `md5_id` ;
ALTER TABLE `users` CHANGE `has_reminder` `has_reminder` TINYINT(1) NOT NULL DEFAULT '0';

ALTER TABLE `event_invitations` ADD `group_id` INT(11) NULL AFTER `user_id`;

ALTER TABLE `events` ADD `photo` VARCHAR(255) NULL AFTER `comment`;