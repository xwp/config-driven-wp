#!/usr/bin/env bash
# Add a new site to VVV, using the following project structure
#
# wp-cli.yml  -- indicates the path to the docroot
# .gitattributes
# .gitignore
# config/*.env.php -- PHP files returning config arrays which can be merged together
# config/active-env -- the env that is currently active (e.g. vvv or production)
# database/vvv-init.sql -- the SQL that creates the DB and adds the user for the site
# database/vvv-data.sql  -- the database dump used for development, shared by developers
# docroot -- location of WordPress
# docroot/wp-config.php -- loads up the config/{env}.env.php file and extracts array into constants and global vars

set -e

if [ -z "$1" ]; then
	echo "Error: Missing domain (sans www)" 1>&2
	exit 1
fi
if [[ $USER != 'vagrant' ]]; then
   echo 'Error: Must run from inside Vagrant' 1>&2
   exit 1
fi

cd $(dirname $0)
init_root=$(pwd)
cd - > /dev/null

domain=$1
repo_root=$(pwd)/$domain
dev_domain=vvv.$(sed 's:^www\.::' <<< $domain)
docroot=$repo_root/docroot

db_name=$(sed "s:\.:_:g" <<< $domain)
db_name=$(sed "s:-::g" <<< $db_name)
db_user=$(sed "s:_[^_]*$::g" <<< $db_name)
db_user=$(sed "s:_::g" <<< $db_user)
db_user=$(cut -c1-16 <<< $db_user)
db_pass=$db_user

echo "Domain: $domain"
echo "Dev domain: $dev_domain"
echo "Repo root: $repo_root"
echo "DB_NAME: $db_name"
echo "DB_USER: $db_user"
echo "DB_PASS: $db_pass"

mkdir -p $repo_root
cd $repo_root
if [ ! -e .git ]; then
	git init
fi

mkdir -p docroot config bin database log

echo '*' > log/.gitignore
git add -f log/.gitignore

# Set up .gitignore
if [ ! -e .gitignore ]; then
	cp $init_root/src/.gitignore .
fi
git add -v .gitignore

# Set up .gitattributes
if [ ! -e .gitattributes ]; then
	cp $init_root/src/.gitattributes .
fi
git add -v .gitattributes

# Set up nginx config
nginx_conf_file=config/vvv-nginx.conf
if [ ! -e $nginx_conf_file ]; then
	cat $init_root/src/vvv-nginx.conf-tpl \
	| sed s/__SERVER_NAME__/$dev_domain/g \
	| sed 's/^ *#.*//g' \
	| sed s:__ABSPATH__:$repo_root: \
	| sed '/^$/d' \
	> $nginx_conf_file
	git add -v $nginx_conf_file
fi

# Add WP-CLI configs
if [ ! -e wp-cli.yml ]; then
	cat $init_root/src/wp-cli.yml \
	| sed s/__SERVER_NAME__/$dev_domain/g \
	> wp-cli.yml
fi
git add -v wp-cli.yml

# Add hosts
domains_file=config/vvv-hosts
if [ ! -e $domains_file ] || ! grep -qF "$dev_domain" $domains_file; then
	echo $dev_domain >> $domains_file
	git add -v $domains_file
fi

# Download WordPress
if [ ! -e docroot/wp-login.php ]; then
	wp core download --path=docroot
	git add -A docroot
fi

# Set up configs
config_file=default.env.php
if [ ! -e config/$config_file ]; then
	cp $init_root/src/env-defaults/$config_file config/$config_file
	sed s/__WP_CACHE_KEY_SALT__/$domain/ -i config/$config_file
	php -r '
		$src = file_get_contents( "config/default.env.php" );
		eval( file_get_contents( "https://api.wordpress.org/secret-key/1.1/salt/" ) );
		$constants = explode( " ", "AUTH_KEY SECURE_AUTH_KEY LOGGED_IN_KEY NONCE_KEY AUTH_SALT SECURE_AUTH_SALT LOGGED_IN_SALT NONCE_SALT" );
		foreach ( $constants as $constant ) {
			$src = str_replace( "__" . $constant . "__", constant( $constant ), $src );
		}
		file_put_contents( "config/default.env.php", $src );
	'
	git add -v config/$config_file
fi

config_file=vvv.env.php
if [ ! -e config/$config_file ]; then
	cp $init_root/src/env-defaults/$config_file config/$config_file
	sed s/__SERVER_NAME__/$dev_domain/ -i config/$config_file
	sed s/__DB_NAME__/$db_name/ -i config/$config_file
	sed s/__DB_PASSWORD__/$db_pass/ -i config/$config_file
	sed s/__DB_USER__/$db_user/ -i config/$config_file
	git add -v config/$config_file
fi

config_file=production.env.php
if [ ! -e config/$config_file ]; then
	cp $init_root/src/env-defaults/$config_file config/$config_file
	sed s/__SERVER_NAME__/$domain/ -i config/$config_file
	git add -v config/$config_file
fi

if [ ! -e docroot/wp-config.php ]; then
	cp $init_root/src/env-defaults/wp-config.php docroot/wp-config.php
	git add -v docroot/wp-config.php
fi

echo 'vvv' > config/active-env

# Grab Memcached and Batcache
function fetch_stable_plugin_file {
	plugin_name=$1
	plugin_file=$2
	echo -n "Fetch $plugin_file from stable plugin $plugin_name..." 1>&2
	svn_root_url="http://plugins.svn.wordpress.org/$plugin_name"
	stable_tag=$(curl -Gs "$svn_root_url/trunk/readme.txt" | grep 'Stable tag:' | sed 's/^.*:\s*//')
	echo " (stable tag: $stable_tag)" 1>&2
	if [[ $stable_tag == 'trunk' ]]; then
		svn_stable_root_url="$svn_root_url/trunk"
	else
		svn_stable_root_url="$svn_root_url/tags/$stable_tag"
	fi
	curl -Gs $svn_stable_root_url/$plugin_file
}

fetch_stable_plugin_file memcached object-cache.php > docroot/wp-content/object-cache.php
git add -v docroot/wp-content/object-cache.php
fetch_stable_plugin_file batcache advanced-cache.php > docroot/wp-content/advanced-cache.php
git add -v docroot/wp-content/advanced-cache.php
mkdir -p docroot/wp-content/mu-plugins
fetch_stable_plugin_file batcache batcache.php > docroot/wp-content/mu-plugins/batcache.php
git add -v docroot/wp-content/mu-plugins/batcache.php

cp $init_root/src/mu-plugins/* docroot/wp-content/mu-plugins
git add -A docroot/wp-content/mu-plugins/*.php

# Add bin/ scripts
for script in load-db-vvv dump-db-vvv; do
	if [ ! -e bin/$script ]; then
		cp $init_root/src/$script bin/$script
		git add bin/$script
	fi
	chmod +x bin/$script
	git add -v bin/$script
done

if [ ! -e config/vvv-init.sh ]; then
	cp $init_root/src/vvv-init.sh-tpl config/vvv-init.sh
	git add -v config/vvv-init.sh
fi

config/vvv-init.sh

# Set up empty DB dump
db_data_path=database/vvv-data.sql
if [ ! -e $db_data_path ]; then
	echo "Setting up empty site, storing dump in $db_data_path"
	admin_name=$db_user
	admin_pass=$(openssl rand -base64 32)
	wp core install --url=$dev_domain --title="$domain" --admin_name=$db_user --admin_email="admin@$domain" --admin_password="$admin_pass"
	printf "Initial WordPress admin user credentials:\nUser: %s\nPass: %s\n" $admin_name $admin_pass | tee wp-admin-user-credentials.txt
	wp db dump $db_data_path
	git add -v $db_data_path
fi

echo
echo 'To recognize your new site, do `vagrant reload --provision`'

echo
echo 'Navigate to and git-commit:'
pwd
