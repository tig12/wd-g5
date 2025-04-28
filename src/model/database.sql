create table wd_g5(
    g5_slug                 string,
    g5_name                 string,
    g5_sex                  string,
    g5_birth                string,
    g5_occus                string,
    wd_data                 string,
    is_wd_stored            boolean not null check(is_wd_stored IN (0, 1)) default 0
);
