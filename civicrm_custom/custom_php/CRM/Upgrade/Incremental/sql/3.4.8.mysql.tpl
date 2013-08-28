-- adding contact_id in Contribution Type

ALTER TABLE `civicrm_contribution_type` ADD `contact_id` INT( 10 ) NULL DEFAULT NULL COMMENT 'Organization that owns the account' AFTER `is_active`;

ALTER TABLE `civicrm_contribution_type` ADD `parent_id` INT( 10 ) NULL DEFAULT NULL COMMENT 'Contribution Type' AFTER `contact_id`;