CREATE TABLE IF NOT EXISTS `#__custompages_pages` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(255) NOT NULL,
    `alias`       VARCHAR(255) NOT NULL DEFAULT '',
    `html`        MEDIUMTEXT NOT NULL,
    `state`       TINYINT NOT NULL DEFAULT 1,
    `access`      INT UNSIGNED NOT NULL DEFAULT 1,
    `created`     DATETIME NOT NULL,
    `created_by`  INT UNSIGNED NOT NULL DEFAULT 0,
    `modified`    DATETIME NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
