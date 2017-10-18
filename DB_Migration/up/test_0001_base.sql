CREATE TABLE if not exists `good` (
    `id` int(11) unsigned not null auto_increment,
    `name` varchar(255) not null,
    `price` int(11) not null,
    primary key(id)
)

engine = innodb
auto_increment = 1
character set utf8
collate utf8_general_ci;