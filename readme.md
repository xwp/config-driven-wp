# Config-Driven WordPress

This repo contains a model for how WordPress projects can be structured in a way that
maximizes configurability and minimizes redundancy. This specific structure is what we
often use at [X-Team](http://x-team.com/wordpress/).

## Dev Setup

You can get up and running quickly via a [fork][1] of [varying-vagrant-vagrants][2].
Follow the [First Vagrant Up][3] instructions:

```sh
git clone git@github.com:x-team/varying-vagrant-vagrants.git vvv
cd vvv
git checkout auto-site-setup
vagrant up
```

If you are already using VVV, you can just add the x-team remote and checkout (or merge from) the branch:

```sh
git remote add -f x-team git@github.com:x-team/varying-vagrant-vagrants.git
git checkout -b auto-site-setup x-team/auto-site-setup
```

You can proceed to add this repo:

```sh
cd www
git clone git@github.com:x-team/config-driven-wp.git config-driven-wp.dev
cd config-driven-wp.dev
echo vvv > config/active-env
vagrant reload --provision
```

Once this finishes (and you've added `vvv.config-driven-wp.dev` to your `hosts` file), you should be able to 
access **[vvv.config-driven-wp.dev](http://vvv.config-driven-wp.dev/)** from your browser.

## Test DB Dump Updates

This repo includes a lightweight test database dump which contains the necessary content to test the features of the site.
In the course of development, if you want to commit some change to the database, first connect with
any other developers who are currently working on the site and obtain a "verbal file lock" on `database/vvv-data.sql`, 
as merging SQL cannot be done cleanly (see also [`.gitattributes`](.gitattributes) which explicitly includes `*.sql merge=binary`).
Once you're clear to commit your changes to the database dump, run:

```sh
bin/dump-db-vvv
git add database/vvv-data.sql
git commit -m "Add some content X"
git push
```

Then let the other developers know to:

```sh
git pull
bin/load-db-vvv
```

Do not commit a production database dump! This committed database dump is intended to be lightweight, to have
just the minimum of content to facilitate development and testing. Committing a large database dump will greatly
increase the repository size.

If any commands complain about needing to be run in Vagrant, you first can `vagrant ssh` then `cd /srv/www/config-driven-wp.dev`
to run the command. Or, you can set up [vassh](https://github.com/x-team/vassh) on your system which
allows you to prefix any command on your system to have it executed at the current working directory in the vagrant
environment. For example:

```sh
vassh wp core upgrade
vassh bin/dump-db-vvv
```

You can also use the `vasshin` command similarly—called without arguments it will drop you into the current directory
in vagrant (not the `vagrant` user's home directory); called with an command argument, it will execute the command in 
the Vagrant environment with full interactive TTY mode and colored output.

## WordPress Superadmin User

This user is only for the database dump committed in this repo and does not correspond to any user credentials on any public site:

User: `admin`  
Pass: `password`

## License ##

As with WordPress core, all code in this repo is [licensed GPLv2+](http://wordpress.org/about/license/).

[1]: https://github.com/x-team/varying-vagrant-vagrants/tree/auto-site-setup
[2]: https://github.com/10up/varying-vagrant-vagrants
[3]: https://github.com/x-team/varying-vagrant-vagrants/tree/auto-site-setup#the-first-vagrant-up

