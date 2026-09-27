-- MySQL 8 / MariaDB 10.5+ schema baseline for the Community Edition (converted from the former
-- PostgreSQL pg_dump). Applied by 20240814190154_InitialOssBaseline.php.

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `actions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uniq_id` varchar(255) NOT NULL,
  `module_id` int NOT NULL,
  `action` longtext NOT NULL,
  `display_name` varchar(225) NULL,
  `display_group` smallint NOT NULL DEFAULT 0,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `actions_uniq_id` (`uniq_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `case_actions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `easycase_id` int NOT NULL,
  `user_id` int NOT NULL,
  `action` smallint NOT NULL DEFAULT 0,
  `dt_created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `case_actions_action` (`action`),
  KEY `case_actions_easycase_id` (`easycase_id`),
  KEY `case_actions_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `case_activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `easycase_id` int NOT NULL,
  `comment_id` int NULL,
  `case_no` int NULL,
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` smallint NOT NULL,
  `isactive` smallint NOT NULL DEFAULT 1,
  `dt_created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `case_activities_case_no` (`case_no`),
  KEY `case_activities_comment_id` (`comment_id`),
  KEY `case_activities_easycase_id` (`easycase_id`),
  KEY `case_activities_isactive` (`isactive`),
  KEY `case_activities_project_id` (`project_id`),
  KEY `case_activities_type` (`type`),
  KEY `case_activities_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `case_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `easycase_id` int NOT NULL,
  `comments` longtext NOT NULL,
  `user_id` int NOT NULL,
  `dt_created` datetime(6) NOT NULL,
  `isactive` smallint NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `case_comments_easycase_id` (`easycase_id`),
  KEY `case_comments_isactive` (`isactive`),
  KEY `case_comments_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `case_editor_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uniq_id` varchar(64) NOT NULL,
  `company_id` int NOT NULL,
  `project_id` int NOT NULL DEFAULT 0,
  `easycase_id` int NOT NULL DEFAULT 0,
  `user_id` int NOT NULL,
  `name` varchar(200) NOT NULL,
  `file_size` int NOT NULL DEFAULT 0,
  `is_deleted` smallint NOT NULL DEFAULT 0,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `case_editor_files_company_id` (`company_id`),
  KEY `case_editor_files_easycase_id` (`easycase_id`),
  KEY `case_editor_files_is_deleted` (`is_deleted`),
  KEY `case_editor_files_project_id` (`project_id`),
  KEY `case_editor_files_uniq_id` (`uniq_id`),
  KEY `case_editor_files_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `case_file_drives` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `easycase_id` int NOT NULL,
  `file_info` longtext NULL,
  `cloud_provider` varchar(50) NULL,
  PRIMARY KEY (`id`),
  KEY `case_file_drives_id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `case_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `project_id` int NOT NULL,
  `company_id` int NOT NULL,
  `easycase_id` int NOT NULL,
  `comment_id` int NOT NULL,
  `file` varchar(222) NOT NULL,
  `display_name` varchar(255) NULL,
  `upload_name` varchar(255) NULL,
  `thumb` varchar(222) NOT NULL,
  `file_size` decimal(7,1) NOT NULL,
  `count` int NULL,
  `downloadurl` longtext NULL,
  `weburl` longtext NULL,
  `onedrive_item_id` longtext NULL,
  `isactive` smallint NOT NULL DEFAULT 1,
  `defect_id` int NULL,
  `defect_reply_id` int NULL,
  `execute_id` int NULL,
  `test_case_id` int NULL,
  `type` smallint NULL DEFAULT 1,
  `created` datetime(6) NULL,
  `modified` datetime(6) NULL,
  `cloud_provider` varchar(50) NULL,
  `cloud_file_id` longtext NULL,
  `cloud_file_path` longtext NULL,
  `cloud_thumbnail_url` longtext NULL,
  `cloud_icon_url` longtext NULL,
  `cloud_metadata` longtext NULL,
  `cloud_last_synced` datetime(6) NULL,
  `mime_type` varchar(100) NULL,
  PRIMARY KEY (`id`),
  KEY `case_files_comment_id` (`comment_id`),
  KEY `case_files_easycase_id` (`easycase_id`),
  KEY `case_files_isactive` (`isactive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `case_filters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order` longtext NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `case_recents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `easycase_id` int NOT NULL,
  `company_id` int NOT NULL,
  `user_id` int NOT NULL,
  `project_id` int NOT NULL,
  `dt_created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `case_recents_company_id` (`company_id`),
  KEY `case_recents_easycase_id` (`easycase_id`),
  KEY `case_recents_id` (`id`),
  KEY `case_recents_project_id` (`project_id`),
  KEY `case_recents_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `case_reminders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `user_id` int NOT NULL,
  `easycase_id` int NOT NULL,
  `comment` longtext NOT NULL,
  `reminder_datetime` datetime(6) NOT NULL,
  `status` smallint NOT NULL DEFAULT 0,
  `is_emailed` smallint NOT NULL DEFAULT 0,
  `user_ids` longtext NOT NULL,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  `project_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `case_reminders_company_id` (`company_id`),
  KEY `case_reminders_easycase_id` (`easycase_id`),
  KEY `case_reminders_is_emailed` (`is_emailed`),
  KEY `case_reminders_project_id` (`project_id`),
  KEY `case_reminders_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `case_removed_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `case_file_id` int NOT NULL,
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `company_id` int NOT NULL,
  `case_file_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `case_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `project_uniqid` varchar(250) NOT NULL,
  `type_id` int NOT NULL,
  `assign_to` int NOT NULL,
  `priority` smallint NOT NULL,
  `due_date` varchar(250) NOT NULL,
  `email` varchar(250) NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `case_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `company_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` longtext NOT NULL,
  `is_active` smallint NOT NULL DEFAULT 1,
  `created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `case_templates_company_id` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `case_user_emails` (
  `id` int NOT NULL AUTO_INCREMENT,
  `easycase_id` int NOT NULL,
  `user_id` int NOT NULL,
  `ismail` smallint NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `case_user_emails_easycase_id_user_id` (`easycase_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `case_user_views` (
  `id` int NOT NULL AUTO_INCREMENT,
  `easycase_id` int NOT NULL,
  `user_id` int NOT NULL,
  `project_id` int NOT NULL,
  `istype` smallint NOT NULL DEFAULT 1,
  `isviewed` smallint NOT NULL DEFAULT 0,
  `dt_created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `check_lists` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uniq_id` varchar(64) NOT NULL,
  `company_id` int NOT NULL,
  `project_id` int NOT NULL,
  `easycase_id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` longtext NOT NULL,
  `is_checked` tinyint(1) NOT NULL DEFAULT 0,
  `sequence` int NOT NULL DEFAULT 0,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `check_lists_company_id` (`company_id`),
  KEY `check_lists_easycase_id` (`easycase_id`),
  KEY `check_lists_project_id` (`project_id`),
  UNIQUE KEY `check_lists_uniq_id` (`uniq_id`),
  KEY `check_lists_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `companies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uniq_id` longtext NULL,
  `name` varchar(250) NOT NULL,
  `seo_url` varchar(250) NOT NULL,
  `logo` varchar(100) NOT NULL,
  `website` varchar(100) NOT NULL,
  `contact_phone` varchar(100) NOT NULL,
  `referrer` longtext NULL,
  `industry_id` int NOT NULL DEFAULT 0,
  `work_hour` decimal(10,2) NOT NULL DEFAULT 8,
  `week_ends` varchar(100) NULL,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  `user_last_login` datetime(6) NOT NULL,
  `is_beta` smallint NOT NULL DEFAULT 0,
  `is_active` smallint NOT NULL DEFAULT 1,
  `is_deactivated` smallint NOT NULL DEFAULT 0,
  `is_skipped` smallint NOT NULL DEFAULT 0,
  `twitted` smallint NOT NULL DEFAULT 0,
  `refering_plan_id` int NOT NULL DEFAULT 0,
  `country_name` varchar(150) NOT NULL DEFAULT 'no',
  `new_layout_no` smallint NOT NULL DEFAULT 0,
  `is_per_user` smallint NOT NULL DEFAULT 0,
  `plan_user_count` smallint NOT NULL DEFAULT 0,
  `is_delete_checked` smallint NOT NULL DEFAULT 0,
  `add_defect_master` smallint NULL DEFAULT 0,
  `auth_token` varchar(255) NULL,
  `currency_id` int NOT NULL DEFAULT 144,
  `api_access_code` varchar(8) NULL,
  `parent_company_id` int NULL DEFAULT 0,
  `company_type_id` int NULL,
  `tenant_uuid` varchar(36) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `companies_is_active` (`is_active`),
  UNIQUE KEY `idx_companies_tenant_uuid` (`tenant_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `company_apis` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `is_active` int NULL,
  `created` datetime(6) NOT NULL,
  `user_id` int NOT NULL DEFAULT 0,
  `project_id` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `company_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_type_name` varchar(255) NOT NULL,
  `company_id` int NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `company_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `company_uniq_id` varchar(250) NOT NULL,
  `user_id` int NOT NULL,
  `user_type` int NOT NULL,
  `is_active` smallint NOT NULL DEFAULT 1,
  `is_access_change` smallint NOT NULL DEFAULT 0,
  `change_timestamp` bigint NOT NULL DEFAULT 0,
  `is_client` smallint NOT NULL DEFAULT 0,
  `role_id` int NOT NULL DEFAULT 0,
  `est_billing_amt` float NOT NULL DEFAULT 0,
  `act_date` datetime(6) NULL,
  `billing_start_date` datetime(6) NULL,
  `billing_end_date` datetime(6) NULL,
  `company_trial_expired` smallint NOT NULL DEFAULT 0,
  `google_token` longtext NULL,
  `is_dummy` smallint NOT NULL DEFAULT 0,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  `business_unit_id` int NULL,
  `company_parent_id` int NULL,
  PRIMARY KEY (`id`),
  KEY `company_users_company_id` (`company_id`),
  KEY `company_users_is_active` (`is_active`),
  KEY `company_users_user_id` (`user_id`),
  KEY `company_users_user_type` (`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `countries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ccode` varchar(2) NOT NULL DEFAULT '',
  `country` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `currencies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NULL,
  `code` varchar(3) NULL,
  `cur_symbol` varchar(7) NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`id`),
  KEY `currencies_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `custom_filters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_uniq_id` varchar(64) NOT NULL,
  `company_id` int NOT NULL,
  `user_id` int NOT NULL,
  `filter_name` varchar(100) NOT NULL,
  `filter_date` longtext NULL,
  `filter_duedate` datetime(6) NULL,
  `filter_type_id` longtext NULL,
  `filter_status` longtext NULL,
  `filter_member_id` longtext NULL,
  `filter_priority` longtext NULL,
  `filter_assignto` longtext NULL,
  `filter_search` longtext NULL,
  `dt_created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `custom_statuses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `progress` int NOT NULL,
  `color` varchar(25) NOT NULL,
  `status_master_id` int NOT NULL,
  `status_group_id` int NOT NULL,
  `seq` int NOT NULL DEFAULT 0,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `default_task_views` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `user_id` int NOT NULL,
  `task_view_id` smallint NOT NULL DEFAULT 1,
  `kanban_view_id` smallint NOT NULL DEFAULT 7,
  `timelog_view_id` smallint NOT NULL DEFAULT 5,
  `project_view_id` smallint NOT NULL DEFAULT 8,
  `default_view_id` smallint NOT NULL DEFAULT 0,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  `task_type_filter` longtext NOT NULL DEFAULT ('{"epic":0,"feature":0,"story":1}'),
  `task_detail_view` varchar(10) NOT NULL DEFAULT 'tab',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `default_tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task` varchar(200) NOT NULL,
  `description` longtext NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `duedate_change_reasons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reason` longtext NOT NULL,
  `company_id` int NOT NULL,
  `user_id` int NOT NULL,
  `modified_by` int NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `easycase_favourites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NULL,
  `project_id` int NULL,
  `user_id` int NULL,
  `easycase_id` int NULL,
  `created` datetime(6) NULL,
  `modified` datetime(6) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `easycase_labels` (
  `id` int NOT NULL AUTO_INCREMENT,
  `easycase_id` int NOT NULL,
  `label_id` int NOT NULL,
  `company_id` int NOT NULL,
  `project_id` int NOT NULL,
  `created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `easycase_labels_id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `easycase_linkings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `easycase_id` int NOT NULL,
  `link_id` int NOT NULL,
  `company_id` int NOT NULL,
  `project_id` int NOT NULL,
  `easycase_relate_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `easycase_linkings_id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `easycase_links` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `source` varchar(50) NOT NULL,
  `target` varchar(50) NOT NULL,
  `type` smallint NOT NULL,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `easycase_mentions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `project_id` int NOT NULL,
  `mention_type_id` int NOT NULL,
  `mention_type` int NOT NULL,
  `mention_by` int NOT NULL,
  `easycase_id` int NOT NULL,
  `comment_id` int NULL DEFAULT 0,
  `mention_message` longtext NULL,
  `created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `easycase_milestones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `easycase_id` int NOT NULL,
  `milestone_id` int NOT NULL,
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created` datetime(6) NOT NULL,
  `m_order` int NOT NULL,
  `id_seq` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `easycase_milestones_easycase_id` (`easycase_id`),
  KEY `easycase_milestones_milestone_id` (`milestone_id`),
  KEY `easycase_milestones_project_id` (`project_id`),
  KEY `easycase_milestones_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `easycase_recurring_tracks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `easycase_id` int NOT NULL,
  `created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `easycase_relates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `status` int NOT NULL DEFAULT 1,
  `seq_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `easycases` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uniq_id` varchar(64) NOT NULL,
  `case_no` int NOT NULL,
  `case_count` int NOT NULL,
  `company_id` int NULL,
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `updated_by` int NOT NULL,
  `type_id` int NOT NULL,
  `priority` varchar(4) NULL,
  `title` longtext NULL,
  `message` longtext NULL,
  `estimated_hours` int NOT NULL DEFAULT 0,
  `hours` decimal(6,1) NULL,
  `completed_task` int NOT NULL DEFAULT 0,
  `assign_to` int NOT NULL,
  `gantt_start_date` datetime(6) NULL,
  `due_date` datetime(6) NULL,
  `istype` smallint NOT NULL DEFAULT 1,
  `is_splitted` smallint NOT NULL DEFAULT 0,
  `client_status` smallint NOT NULL DEFAULT 0,
  `format` smallint NOT NULL DEFAULT 1,
  `status` smallint NOT NULL DEFAULT 1,
  `legend` smallint NOT NULL,
  `isactive` smallint NOT NULL DEFAULT 1,
  `is_recurring` smallint NOT NULL DEFAULT 0,
  `dt_created` datetime(6) NOT NULL,
  `dt_closed` datetime(6) NULL,
  `actual_dt_created` datetime(6) NOT NULL,
  `reply_type` int NOT NULL DEFAULT 0,
  `is_chrome_extension` tinyint(1) NOT NULL DEFAULT 0,
  `from_email` tinyint(1) NOT NULL DEFAULT 0,
  `depends` varchar(255) NULL,
  `children` varchar(255) NULL,
  `temp_hours` int NULL,
  `temp_est_hours` int NOT NULL DEFAULT 0,
  `temp_est_hours_back` float NULL,
  `seq_id` int NULL,
  `parent_task_id` int NULL,
  `custom_status_id` int NOT NULL DEFAULT 0,
  `thread_count` int NOT NULL DEFAULT 0,
  `git_sync` smallint NOT NULL DEFAULT 0,
  `git_issue_id` bigint NOT NULL DEFAULT 0,
  `real_git_issue_id` bigint NOT NULL DEFAULT 0,
  `is_zapaction` tinyint(1) NULL DEFAULT 0,
  `initial_due_date` datetime(6) NULL,
  `epic_id` int NULL,
  `is_approved` tinyint(1) NULL,
  `approver_id` int NULL,
  `approved_by` int NULL,
  `approval_status` varchar(50) NULL,
  `dt_approved` datetime(6) NULL,
  `feature_id` int NULL,
  `dependency_type` longtext NULL,
  PRIMARY KEY (`id`),
  KEY `easycases_assign_to` (`assign_to`),
  KEY `easycases_case_no` (`case_no`),
  KEY `easycases_children` (`children`),
  KEY `easycases_depends` (`depends`),
  KEY `easycases_format` (`format`),
  KEY `easycases_isactive` (`isactive`),
  KEY `easycases_istype` (`istype`),
  KEY `easycases_legend` (`legend`),
  KEY `easycases_priority` (`priority`),
  KEY `easycases_project_id` (`project_id`),
  KEY `easycases_project_id_istype_isactive` (`project_id`, `istype`, `isactive`),
  KEY `easycases_project_id_istype_legend_depends_children` (`project_id`, `istype`, `legend`, `depends`, `children`),
  KEY `easycases_status` (`status`),
  KEY `easycases_type_id` (`type_id`),
  KEY `easycases_uniq_id` (`uniq_id`),
  KEY `easycases_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `email_reminders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `email_type` int NOT NULL,
  `cron_date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `email_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NULL,
  `user_id` int NULL,
  `host` varchar(255) NULL,
  `port` varchar(255) NULL,
  `is_smtp` int NULL,
  `email` varchar(255) NULL,
  `password` varchar(255) NULL,
  `from_email` varchar(255) NULL,
  `reply_email` varchar(255) NULL,
  `status` smallint NULL,
  `is_default` smallint NULL,
  `is_verified` int NULL,
  `created` datetime(6) NULL,
  `modified` datetime(6) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `feedback` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `company_id` int NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `star` tinyint(1) NOT NULL,
  `message` longtext NOT NULL,
  `created` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `guest_role_actions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `role_id` int NOT NULL,
  `action_details` longtext NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `helps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` longtext NOT NULL,
  `image` longtext NOT NULL,
  `keywords` longtext NOT NULL,
  `created` datetime(6) NOT NULL DEFAULT '2013-10-10 00:00:00',
  `is_admin` smallint NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `industries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_display` smallint NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `invoice_customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uniq_id` varchar(64) NULL,
  `company_id` int NOT NULL DEFAULT 0,
  `project_id` int NOT NULL,
  `first_name` varchar(100) NULL,
  `last_name` varchar(100) NULL,
  `street` longtext NULL,
  `city` varchar(100) NULL,
  `state` varchar(100) NULL,
  `country` varchar(100) NULL,
  `zipcode` varchar(10) NULL,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  `email` varchar(100) NULL,
  `phone` varchar(50) NULL,
  `title` varchar(25) NULL,
  `organization` varchar(255) NULL,
  `currency` varchar(5) NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Active',
  `user_id` int NULL,
  `customer_code` varchar(100) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `labels` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lbl_title` varchar(50) NOT NULL,
  `company_id` int NOT NULL,
  `project_id` int NOT NULL DEFAULT 0,
  `user_id` int NOT NULL,
  `is_active` smallint NOT NULL DEFAULT 1,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `labels_id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `languages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `language` varchar(255) NULL,
  `short_code` varchar(5) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `log_activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NULL,
  `user_id` int NULL,
  `log_type_id` int NULL,
  `json_value` longtext NULL,
  `created` datetime(6) NULL,
  `ip` varchar(100) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `log_times` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `project_id` int NOT NULL,
  `task_id` int NOT NULL,
  `task_date` date NOT NULL,
  `start_time` time NULL,
  `end_time` time NULL,
  `total_hours` int NOT NULL,
  `is_billable` smallint NOT NULL,
  `description` longtext NOT NULL,
  `task_status` smallint NOT NULL,
  `created` datetime(6) NOT NULL,
  `timesheet_flag` smallint NOT NULL DEFAULT 0,
  `ip` varchar(20) NOT NULL,
  `start_datetime` datetime(6) NULL,
  `end_datetime` datetime(6) NULL,
  `break_time` int NOT NULL DEFAULT 0,
  `approver_id` int NULL,
  `pending_status` int NOT NULL DEFAULT 0,
  `is_from_timer` tinyint(1) NULL DEFAULT 0,
  PRIMARY KEY (`log_id`),
  KEY `log_times_user_id_project_id_task_id` (`user_id`, `project_id`, `task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `log_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `login_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `menu_languages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `string_name` longtext NULL,
  `en` longtext NULL,
  `spa` longtext NULL,
  `por` longtext NULL,
  `deu` longtext NULL,
  `fra` longtext NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `menus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` smallint NOT NULL DEFAULT 1,
  `menu_type` smallint NOT NULL,
  `menu_icon` varchar(150) NOT NULL,
  `menu_order` int NOT NULL,
  `default_menu` smallint NOT NULL,
  `conditional_menu` smallint NOT NULL,
  `meta` longtext NULL,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `migrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `from_company_id` int NULL,
  `to_company_id` int NULL,
  `user_id` int NULL,
  `previous_projects` longtext NULL,
  `current_projects` longtext NULL,
  `type` int NULL DEFAULT 1,
  `comment` longtext NULL,
  `status` int NULL,
  `report` varchar(255) NULL,
  `created` datetime(6) NULL,
  `modified` datetime(6) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `milestones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uniq_id` varchar(250) NOT NULL,
  `project_id` int NOT NULL,
  `company_id` int NOT NULL,
  `title` varchar(250) NOT NULL,
  `description` longtext NOT NULL,
  `user_id` int NOT NULL,
  `closed_by` int NULL,
  `estimated_hours` decimal(10,0) NOT NULL,
  `duration` smallint NOT NULL DEFAULT 0,
  `start_date` date NULL,
  `end_date` date NULL,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  `completed_date` datetime(6) NULL,
  `isactive` smallint NOT NULL DEFAULT 1,
  `is_started` smallint NOT NULL DEFAULT 0,
  `id_seq` smallint NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `milestones_company_id` (`company_id`),
  KEY `milestones_project_id` (`project_id`),
  UNIQUE KEY `milestones_uniq_id` (`uniq_id`),
  KEY `milestones_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `modules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uniq_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` smallint NOT NULL DEFAULT 0,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modules_uniq_id` (`uniq_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `notification_info` longtext NULL,
  `total_seen` datetime(6) NULL,
  `dt_created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `os_session_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `user_agent` longtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `os_session_logs_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_actions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `role_id` int NOT NULL,
  `action_id` int NOT NULL,
  `is_allowed` smallint NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_metas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `project_id` int NOT NULL,
  `project_manager` varchar(100) NOT NULL DEFAULT '0',
  `client` int NOT NULL DEFAULT 0,
  `currency` int NULL,
  `budget` int NOT NULL DEFAULT 0,
  `default_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost_appr` int NOT NULL DEFAULT 0,
  `min_tol` smallint NOT NULL DEFAULT 0,
  `max_tol` smallint NOT NULL DEFAULT 0,
  `proj_type` int NOT NULL DEFAULT 0,
  `industry` int NULL,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  `project_code` varchar(100) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_methodologies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `status_group_id` int NOT NULL,
  `listing_description` longtext NOT NULL,
  `short_description` longtext NOT NULL,
  `description` longtext NOT NULL,
  `thumbnail` varchar(255) NOT NULL,
  `full_image` varchar(255) NOT NULL,
  `project_template_view_id` int NOT NULL DEFAULT 0,
  `status` smallint NOT NULL DEFAULT 1,
  `seq_no` int NOT NULL,
  `created` datetime(6) NOT NULL,
  `updated` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uniq_id` varchar(80) NOT NULL,
  `company_id` int NOT NULL,
  `user_id` int NOT NULL,
  `project_id` int NOT NULL,
  `note` longtext NOT NULL,
  `is_updated` smallint NOT NULL DEFAULT 0,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `project_notes_company_id` (`company_id`),
  KEY `project_notes_is_updated` (`is_updated`),
  KEY `project_notes_project_id` (`project_id`),
  KEY `project_notes_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `company_id` int NOT NULL,
  `sent_mail` smallint NOT NULL,
  `frequncy` smallint NOT NULL,
  `day` smallint NOT NULL,
  `notification_time` varchar(100) NOT NULL,
  `proj_name` varchar(200) NOT NULL,
  `admin_user` varchar(200) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `mail_date` date NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `company_id` int NOT NULL,
  `velocity_reports` smallint NOT NULL,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_statuses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `user_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` smallint NOT NULL DEFAULT 1,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `project_statuses_company_id` (`company_id`),
  KEY `project_statuses_is_active` (`is_active`),
  KEY `project_statuses_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_technologies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `technology_id` int NULL,
  PRIMARY KEY (`id`),
  KEY `project_technologies_project_id` (`project_id`),
  KEY `project_technologies_technology_id` (`technology_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `is_active` smallint NULL DEFAULT 1,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `project_types_company_id` (`company_id`),
  KEY `project_types_is_active` (`is_active`),
  KEY `project_types_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `company_id` int NOT NULL,
  `user_id` int NOT NULL,
  `istype` smallint NOT NULL DEFAULT 2,
  `default_email` smallint NOT NULL DEFAULT 1,
  `dt_visited` datetime(6) NOT NULL,
  `role_id` int NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `project_users_company_id` (`company_id`),
  KEY `project_users_istype` (`istype`),
  KEY `project_users_project_id` (`project_id`),
  KEY `project_users_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uniq_id` varchar(64) NOT NULL,
  `user_id` int NOT NULL,
  `company_id` int NOT NULL,
  `task_type` int NULL,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(100) NOT NULL,
  `description` longtext NOT NULL,
  `logo` varchar(100) NOT NULL,
  `project_type` smallint NOT NULL DEFAULT 1,
  `priority` smallint NOT NULL DEFAULT 2,
  `default_assign` int NOT NULL,
  `isactive` smallint NOT NULL DEFAULT 1,
  `status` smallint NOT NULL DEFAULT 1,
  `start_date` date NULL,
  `end_date` date NULL,
  `estimated_hours` decimal(10,0) NULL,
  `dt_created` datetime(6) NOT NULL,
  `dt_updated` datetime(6) NULL,
  `is_multiple_sprint` smallint NOT NULL DEFAULT 0,
  `project_methodology_id` int NOT NULL DEFAULT 1,
  `status_group_id` int NOT NULL DEFAULT 0,
  `defect_status_group_id` int NOT NULL DEFAULT 0,
  `is_zapaction` tinyint(1) NULL DEFAULT 0,
  `purpose_type` varchar(255) NULL DEFAULT 'project',
  `program_name_key` varchar(255) COLLATE utf8mb4_bin GENERATED ALWAYS AS (CASE WHEN `purpose_type` COLLATE utf8mb4_bin = 'PROGRAM' THEN `name` END) STORED,
  `parent_id` int NULL,
  `organization_id` int NULL,
  PRIMARY KEY (`id`),
  KEY `projects_company_id` (`company_id`),
  KEY `projects_isactive` (`isactive`),
  KEY `projects_project_type` (`project_type`),
  KEY `projects_uniq_id` (`uniq_id`),
  KEY `projects_user_id` (`user_id`),
  UNIQUE KEY `unique_program_name_per_company` (`company_id`, `program_name_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quicklink_menus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `menu_language_id` int NULL,
  `created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quicklink_submenus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quicklink_menu_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `menu_language_id` int NULL,
  `action_name` varchar(255) NULL,
  `status` smallint NOT NULL DEFAULT 1,
  `created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `quicklink_submenus_ibfk_1` (`quicklink_menu_id`),
  KEY `quicklink_submenus_ibfk_3` (`menu_language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `recurring_easycases` (
  `id` int NOT NULL AUTO_INCREMENT,
  `easycase_id` int NOT NULL,
  `recurring_type` varchar(255) NULL,
  `start_date` date NULL,
  `occurrence` int NULL DEFAULT 0,
  `end_date` date NULL,
  `recurring_end_type` varchar(255) NULL,
  `created` datetime(6) NULL,
  `project_id` int NOT NULL,
  `company_id` int NOT NULL,
  `frequency` varchar(255) NULL,
  `rec_interval` int NULL DEFAULT 0,
  `bymonthday` int NULL DEFAULT 0,
  `byday` varchar(255) NULL,
  `byweekno` int NULL DEFAULT 0,
  `bymonth` int NULL DEFAULT 0,
  `occurrences` int NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_actions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `role_id` int NOT NULL,
  `action_id` int NOT NULL,
  `is_allowed` smallint NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uniq_id` varchar(255) NOT NULL,
  `company_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(255) NOT NULL,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_groups_uniq_id` (`uniq_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_modules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `module_id` int NOT NULL,
  `role_id` int NOT NULL,
  `is_active` smallint NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_rates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role_id` int NULL,
  `rate` decimal(20,6) NULL,
  `actual_rate` decimal(20,6) NULL,
  `is_active` smallint NULL DEFAULT 1,
  `created` datetime(6) NOT NULL,
  `updated` datetime(6) NOT NULL,
  `created_by` int NOT NULL,
  `updated_by` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uniq_id` varchar(255) NOT NULL,
  `company_id` int NOT NULL,
  `role_group_id` int NULL DEFAULT 0,
  `role` varchar(255) NOT NULL,
  `short_name` varchar(10) NOT NULL,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_uniq_id` (`uniq_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `search_filters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `company_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `json_array` longtext NOT NULL,
  `first_records` int NOT NULL DEFAULT 0,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(40) NOT NULL,
  `data` longblob NULL,
  `expires` int NULL,
  `created` datetime(6) NULL DEFAULT CURRENT_TIMESTAMP(6),
  `modified` datetime(6) NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  KEY `idx_sessions_expires` (`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sidebar_menus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `menu_language_id` int NULL,
  `status` smallint NOT NULL DEFAULT 1,
  `href_exist` smallint NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sidebar_submenus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sidebar_menu_id` int NOT NULL,
  `menu_language_id` int NULL,
  `name` varchar(255) NOT NULL,
  `status` smallint NOT NULL DEFAULT 1,
  `href_exist` smallint NOT NULL DEFAULT 1,
  `created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sidebar_submenus_sidebar_menu_id` (`sidebar_menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `status_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int NOT NULL DEFAULT 0,
  `company_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` longtext NOT NULL,
  `created_by` int NOT NULL,
  `is_default` smallint NOT NULL DEFAULT 0,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `status_masters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `legend` smallint NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_name` varchar(200) NOT NULL,
  `seq_odr` smallint NOT NULL DEFAULT 0,
  `created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `task_cycles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `status_id` int NOT NULL,
  `start_time` datetime(6) NOT NULL,
  `difference` datetime(6) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `task_due_change_reasons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `easycase_id` int NOT NULL,
  `duedate_change_reason_id` int NOT NULL,
  `user_id` int NOT NULL,
  `due_date` datetime(6) NOT NULL,
  `created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `task_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `edit_task` smallint NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `task_views` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `sub_name` varchar(255) NOT NULL,
  `created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `team_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `team_id` int NOT NULL,
  `role` varchar(255) NULL,
  `created` datetime(6) NULL,
  `modified` datetime(6) NULL,
  `status` varchar(10) NULL,
  `effective_start_date` datetime(6) NULL DEFAULT CURRENT_TIMESTAMP(6),
  `effective_end_date` datetime(6) NULL,
  `company_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `teams` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `parent_id` int NULL,
  `description` varchar(255) NULL,
  `created` datetime(6) NULL,
  `modified` datetime(6) NULL,
  `status` varchar(10) NULL,
  `effective_start_date` datetime(6) NULL DEFAULT CURRENT_TIMESTAMP(6),
  `effective_end_date` datetime(6) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `technologies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `temp_users` (
  `id` int NOT NULL,
  `uniq_id` varchar(255) NOT NULL,
  `user_id` int NOT NULL,
  `company_id` int NOT NULL,
  `email` varchar(250) NOT NULL,
  `type` smallint NOT NULL DEFAULT 1,
  `is_active` smallint NOT NULL DEFAULT 1,
  `ga_count` int NULL,
  `ref_id` int NOT NULL DEFAULT 0,
  `is_winner` smallint NOT NULL DEFAULT 0,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `time_zone` (
  `time_zone_id` bigint NOT NULL AUTO_INCREMENT,
  `use_leap_seconds` varchar(255) NOT NULL DEFAULT 'N',
  PRIMARY KEY (`time_zone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `timezone_names` (
  `id` int NOT NULL,
  `gmt` varchar(15) NOT NULL,
  `zone` varchar(100) NOT NULL,
  KEY `timezone_names_id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `timezones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `gmt_offset` float NULL DEFAULT 0,
  `dst_offset` float NULL,
  `code` varchar(4) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tool_settings` (
  `id` int NOT NULL,
  `days` int NOT NULL,
  `created` datetime(6) NOT NULL,
  `updated` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `type_companies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `project_id` int NULL DEFAULT 0,
  `type_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL DEFAULT 0,
  `project_id` int NOT NULL DEFAULT 0,
  `short_name` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `seq_order` int NOT NULL,
  `is_global` int NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_device_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `ios_device_token` longtext NOT NULL,
  `android_device_token` longtext NOT NULL,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_infos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NULL,
  `access_token` longtext NULL,
  `is_google_signup` smallint NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_invitations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invitor_id` int NOT NULL,
  `company_id` int NOT NULL,
  `user_type` smallint NOT NULL DEFAULT 3,
  `project_id` longtext NULL,
  `user_id` int NOT NULL,
  `is_active` smallint NOT NULL DEFAULT 1,
  `qstr` varchar(100) NOT NULL,
  `created` datetime(6) NOT NULL,
  `invite_token` varchar(64) NULL,
  PRIMARY KEY (`id`),
  KEY `user_invitations_company_id` (`company_id`),
  KEY `user_invitations_invite_token` (`invite_token`),
  KEY `user_invitations_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_logins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_menus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `company_id` int NOT NULL,
  `menu` longtext NOT NULL,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` smallint NOT NULL DEFAULT 1,
  `value` smallint NOT NULL,
  `due_val` smallint NOT NULL,
  `due_frequency` smallint NULL,
  `new_case` smallint NOT NULL DEFAULT 1,
  `reply_case` smallint NOT NULL DEFAULT 1,
  `case_status` smallint NOT NULL DEFAULT 1,
  `weekly_usage_alert` smallint NOT NULL DEFAULT 1,
  `mention_case` smallint NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_quicklinks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `company_id` int NOT NULL,
  `quicklink_menu_id` int NOT NULL,
  `quicklink_submenu_id` int NOT NULL,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_sidebar_menus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `company_id` int NOT NULL,
  `sidebar_menu_id` int NOT NULL,
  `created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_sidebar_menus_sidebar_menu_id` (`sidebar_menu_id`),
  KEY `user_sidebar_menus_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_sidebar_submenus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `company_id` int NOT NULL,
  `user_sidebar_menu_id` int NOT NULL,
  `sidebar_submenu_id` int NOT NULL,
  `created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_themes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `sidebar_color` varchar(100) NULL,
  `navbar_color` varchar(100) NULL,
  `mini_leftmenu` smallint NOT NULL DEFAULT 0,
  `dark_leftmenu` smallint NOT NULL DEFAULT 0,
  `dark_navbar` smallint NOT NULL DEFAULT 0,
  `fixed_navbar` smallint NOT NULL DEFAULT 0,
  `footer_dark` smallint NOT NULL DEFAULT 0,
  `footer_fixed` smallint NOT NULL DEFAULT 0,
  `created` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uniq_id` varchar(64) NOT NULL,
  `btprofile_id` varchar(100) NULL,
  `credit_cardtoken` varchar(100) NULL,
  `card_number` varchar(255) NULL,
  `expiry_date` varchar(255) NULL,
  `email` varchar(150) NOT NULL,
  `username` varchar(255) NULL,
  `update_email` varchar(150) NULL,
  `update_random` varchar(150) NULL,
  `password` varchar(64) NULL,
  `name` varchar(150) NOT NULL,
  `is_beta` smallint NOT NULL DEFAULT 0,
  `last_name` varchar(100) NULL,
  `short_name` varchar(100) NULL,
  `istype` smallint NOT NULL DEFAULT 3,
  `photo` varchar(50) NULL,
  `photo_reset` varchar(50) NULL,
  `isactive` smallint NOT NULL DEFAULT 1,
  `timezone_id` int NULL,
  `isemail` smallint NOT NULL DEFAULT 1,
  `is_agree` smallint NOT NULL DEFAULT 1,
  `usersub_type` smallint NULL DEFAULT 0,
  `est_billing_amount` float NULL DEFAULT 0,
  `dt_created` datetime(6) NOT NULL,
  `dt_updated` datetime(6) NULL,
  `dt_last_login` datetime(6) NULL,
  `dt_last_logout` datetime(6) NULL,
  `query_string` varchar(100) NULL,
  `gaccess_token` longtext NULL,
  `google_id` varchar(200) NULL,
  `ip` varchar(15) NULL,
  `sig` varchar(100) NULL,
  `desk_notify` smallint NOT NULL DEFAULT 1,
  `active_dashboard_tab` int NOT NULL DEFAULT 7,
  `is_moderator` smallint NOT NULL DEFAULT 0,
  `verify_string` varchar(100) NULL,
  `show_default_inner` smallint NOT NULL DEFAULT 1,
  `updated_by` int NOT NULL DEFAULT 0,
  `is_online` smallint NULL DEFAULT 0,
  `is_dst` smallint NOT NULL DEFAULT 0,
  `language_id` int NOT NULL DEFAULT 2,
  `is_agree_tosp` smallint NOT NULL DEFAULT 1,
  `is_receive_update` smallint NOT NULL DEFAULT 0,
  `outer_signup` smallint NOT NULL DEFAULT 0,
  `language` varchar(10) NOT NULL DEFAULT 'eng',
  `time_format` smallint NOT NULL DEFAULT 12,
  `phone` varchar(20) NOT NULL DEFAULT '0',
  `is_dummy` smallint NOT NULL DEFAULT 0,
  `one_tap_token` longtext NULL,
  `keep_hover_effect` smallint NOT NULL DEFAULT 0,
  `linkedin_id` varchar(100) NOT NULL DEFAULT '0',
  `is_zapaction` tinyint(1) NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email` (`email`),
  KEY `users_isactive` (`isactive`),
  KEY `users_isemail` (`isemail`),
  KEY `users_istype` (`istype`),
  KEY `users_timezone_id` (`timezone_id`),
  UNIQUE KEY `users_uniq_id` (`uniq_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `workflow_actions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `workflow_conditions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_active` smallint NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `workflow_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `workflow_id` int NOT NULL,
  `workflow_condition_id` int NOT NULL,
  `workflow_action_id` int NOT NULL,
  `condition_details` longtext NULL,
  `action_details` longtext NULL,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `workflows` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `project_id` int NULL DEFAULT 0,
  `company_id` int NOT NULL DEFAULT 0,
  `project_uniq_id` varchar(255) NULL,
  `created_by` int NOT NULL,
  `updated_by` int NOT NULL,
  `created` datetime(6) NOT NULL,
  `updated` datetime(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `quicklink_submenus` ADD CONSTRAINT `quicklink_submenus_ibfk_1` FOREIGN KEY (`quicklink_menu_id`) REFERENCES `quicklink_menus` (`id`);
ALTER TABLE `quicklink_submenus` ADD CONSTRAINT `quicklink_submenus_ibfk_3` FOREIGN KEY (`menu_language_id`) REFERENCES `menu_languages` (`id`);

SET FOREIGN_KEY_CHECKS = 1;
