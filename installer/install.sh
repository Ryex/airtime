#!/bin/bash -e
#-e Causes bash script to exit if any of the installers
#return with a non-zero return value.

if [[ $EUID -ne 0 ]]; then
    echo "Please run as root user."
    exit 1
fi

#Current dir
# Absolute path to this script, e.g. /home/user/bin/foo.sh
SCRIPT=`readlink -f $0`
# Absolute path this script is in, thus /home/user/bin
SCRIPTPATH=`dirname ${SCRIPT}`

showhelp () {
    echo "Usage: airtime-install [options]
--help|-h                         Displays usage information."
    exit 0
}

while [ $# -gt 0 ]
do
    case "$1" in
    (-h|--help) showhelp; exit 0;;

    (--) shift; break;;
    (-*) echo "$0: error - unrecognized option $1" 1>&2; exit 1;;
    (*)  break;;
    esac
    shift
done

echo -e "                 \n****************************************************************"
echo    "                 *    _____  .________________________.___   _____  ___________ *"
echo    "                 *   /  _  \ |   \______   \__    ___/|   | /     \ \_   _____/ *"
echo    "                 *  /  /_\  \|   ||       _/ |    |   |   |/  \ /  \ |    __)_  *"
echo    "                 * /    |    \   ||    |   \ |    |   |   /    Y    \|        \ *"
echo    "                 * \____|__  /___||____|_  / |____|   |___\____|__  /_______  / *"
echo    "                 *         \/            \/                       \/        \/  *"
echo    "                 ****************************************************************"

echo    "               ____ ______   ____   ____     __________  __ _________   ____  ____  "
echo    "              /  _ \\____ \_/ __ \ /    \   /  ___/  _ \|  |  \_  __ \_/ ___\/ __ \ "
echo    "             (  <_> )  |_> >  ___/|   |  \  \___ (  <_> )  |  /|  | \/\  \__\  ___/ "
echo    "              \____/|   __/ \___  >___|  / /____  >____/|____/ |__|    \___  >___  >"
echo    "                    |__|        \/     \/       \/                         \/    \/ "
echo    "                   .___.__                        __                         __  .__                "
echo    "____________     __| _/|__| ____   _____   __ ___/  |_  ____   _____ _____ _/  |_|__| ____   ____   "
echo    "\_  __ \__  \   / __ | |  |/  _ \  \__  \ |  |  \   __\/  _ \ /     \\__  \\   __\  |/  _ \ /    \  "
echo    " |  | \// __ \_/ /_/ | |  (  <_> )  / __ \|  |  /|  | (  <_> )  Y Y  \/ __ \|  | |  (  <_> )   |  \ "
echo    " |__|  (____  /\____ | |__|\____/  (____  /____/ |__|  \____/|__|_|  (____  /__| |__|\____/|___|  / "
echo    "            \/      \/                  \/                         \/     \/                    \/  "

echo -e "\n-----------------------------------------------------"
echo    "                * Installing Apache *                "
echo    "-----------------------------------------------------"

apt-get -y --force-yes install apache2 libapache2-mod-php5
set +e
apache2 -v | grep "2\.4" > /dev/null
apacheversion=$?
set -e

# Apache Config File
if [ "$apacheversion" != "1" ]; then
    airtimeconfigfile="airtime.conf"
else
    airtimeconfigfile="airtime"
fi

if [ ! -f /etc/apache2/sites-available/${airtimeconfigfile} ]; then
    echo "Creating Apache config for Airtime..."

    cp ${SCRIPTPATH}/../apache/airtime-vhost /etc/apache2/sites-available/${airtimeconfigfile}
    a2dissite 000-default
    a2ensite airtime
else
    echo "Apache config for Airtime already exists, skipping"
fi

if [ ! -d /usr/share/airtime/public ]; then
    echo "Creating Apache web root directory..."
    mkdir -p /usr/share/airtime/public/
else
    echo "Airtime web root directory already exists, skipping"
fi

# PHP Config File for Apache
if [ ! -f /etc/php5/apache2/airtime.ini ]; then
    echo "Creating Airtime PHP config for Apache..."
    cp ${SCRIPTPATH}/../php5/airtime.ini /etc/php5/apache2/conf.d/airtime.ini
else
    echo "Airtime PHP config for Apache already exists, skipping"
fi

# Enable modules
a2enmod rewrite php5

echo -e "\n-----------------------------------------------------"
echo    "                  * Installing PHP *                 "
echo    "-----------------------------------------------------"

apt-get -y --force-yes install php5

#Debian Squeeze only has zendframework package. Newer versions of Ubuntu have zend-framework package.
#Ubuntu Lucid has both zendframework and zend-framework. Difference appears to be that zendframework is for
#1.10 and zend-framework is 1.11
if [ "$dist" = "Debian" ]; then
    apt-get -y --force-yes install zendframework
else
    apt-get -y --force-yes install libzend-framework-php
fi

echo -e "\n-----------------------------------------------------"
echo    "                * Setting up Airtime *               "
echo    "-----------------------------------------------------"

# Clear any previous configuration files
if [ -d "/etc/airtime/" ]; then
    rm -rf "/etc/airtime"
else
    mkdir "/etc/airtime"
fi

cp airtime/airtime.conf /etc/airtime/airtime.conf
chown -R www-data:www-data /etc/airtime

if [ ! -d "/var/log/airtime" ]; then
    mkdir "/var/log/airtime"
fi

chown -R www-data:www-data /var/log/airtime

apt-get -y --force-yes install postgresql php5-pgsql php5-mysql

echo -e "\n-----------------------------------------------------"
echo    "                * Basic Setup DONE! *                "
echo    "                                                     "
echo    "  To get started with Airtime, visit localhost:5000  "
echo    "            in your web browser of choice            "
echo    "-----------------------------------------------------"