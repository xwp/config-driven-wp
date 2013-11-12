cd $(dirname $0)/..

printf "Setting up: %s\n" $(basename $(pwd))

eval $(php -r '
	$config = require( "config/vvv.env.php" );
	foreach( explode( " ", "DB_NAME DB_HOST DB_USER DB_PASSWORD DB_CHARSET" ) as $key ) {
		echo $key . "=" . escapeshellcmd( $config[$key] ) . PHP_EOL;
	}
')

# Make a database, if we don't already have one
mysql -u root --password=root -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET $DB_CHARSET"
mysql -u root --password=root -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO $DB_USER@localhost IDENTIFIED BY '$DB_PASSWORD';"

db_populated=`mysql -u root -proot --skip-column-names -e "SHOW TABLES FROM $DB_NAME"`
if [ "" == "$db_populated" ]; then
	echo "Loading vvv-data.sql"
	mysql -u root -proot $DB_NAME < database/vvv-data.sql
fi

echo vvv > config/active-env
