@echo off
title MySQL (MariaDB) - Leerplatform
echo Starting MySQL (MariaDB) for the Leerplatform...
echo.
echo Druk op Ctrl+C om de server te stoppen.
echo Open in je browser: http://localhost/leerplatform/
echo.
cd /d "C:\xampp\mysql\bin"
mysqld.exe --defaults-file="C:\xampp\mysql\bin\my.ini" --console
pause
