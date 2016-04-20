#!/bin/bash
APP_HOME=`dirname $0`
echo "Changing to $APP_HOME"
cd $APP_HOME
php console.php  modules.setup_cli.setup_cli intro
