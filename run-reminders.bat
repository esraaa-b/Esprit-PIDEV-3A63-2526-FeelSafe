@echo off
cd /d C:\wamp64\www\feelsafe
C:\wamp64\bin\php\php-8.2.24\php.exe bin/console app:journal:send-reminders >> var/log/reminders.log 2>&1