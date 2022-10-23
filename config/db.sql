use api_db;

# region creating users table
DROP TABLE IF EXISTS users;
CREATE TABLE users
(
    id            int           not null AUTO_INCREMENT,
    user_name     varchar(255)  not null,
    first_name    varchar(255)  not null,
    last_name     varchar(255)  not null,
    email         varchar(255)  not null,
    password      varchar(2048) not null,
    date_created  datetime      not null DEFAULT CURRENT_TIMESTAMP(),
    date_modified timestamp     not null DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
    deleted       tinyint       not null DEFAULT 0,
    PRIMARY KEY (id)
);
# endregion

# region reset passwords
UPDATE users
SET password = CASE
                   WHEN id = '1' THEN SHA2('NMK123', 256)
                   WHEN id = '2' THEN SHA2('MB123', 256)
                   WHEN id = '3' THEN SHA2('AD123', 256)
                   WHEN id = '4' THEN SHA2('LEO123', 256)
                   ELSE '' END
WHERE deleted = 0  AND id IN ('1', '2', '3', '4');
# endregion
