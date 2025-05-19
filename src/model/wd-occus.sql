create table wd_occus(
    wd_id                   string,
    wd_label                string,
    slug                    string,
    wd_parents              string not null default '[]',
    is_valid                boolean not null check(is_valid IN (0, 1)) default 0,
    is_wd_stored            boolean not null check(is_wd_stored IN (0, 1)) default 0
);
