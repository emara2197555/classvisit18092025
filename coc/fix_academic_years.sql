-- تعديل جدول academic_years
SET @exist_check = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'academic_years'
    AND COLUMN_NAME = 'first_term_start'
);

SET @sql = IF(@exist_check = 0,
    'ALTER TABLE `academic_years` ADD COLUMN `first_term_start` date NULL AFTER `name`',
    'SELECT "Column first_term_start already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_check = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'academic_years'
    AND COLUMN_NAME = 'first_term_end'
);

SET @sql = IF(@exist_check = 0,
    'ALTER TABLE `academic_years` ADD COLUMN `first_term_end` date NULL AFTER `first_term_start`',
    'SELECT "Column first_term_end already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_check = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'academic_years'
    AND COLUMN_NAME = 'second_term_start'
);

SET @sql = IF(@exist_check = 0,
    'ALTER TABLE `academic_years` ADD COLUMN `second_term_start` date NULL AFTER `first_term_end`',
    'SELECT "Column second_term_start already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_check = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'academic_years'
    AND COLUMN_NAME = 'second_term_end'
);

SET @sql = IF(@exist_check = 0,
    'ALTER TABLE `academic_years` ADD COLUMN `second_term_end` date NULL AFTER `second_term_start`',
    'SELECT "Column second_term_end already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- تعديل نوع عمود is_active
ALTER TABLE `academic_years`
MODIFY COLUMN `is_active` tinyint(1) NOT NULL DEFAULT '0'; 