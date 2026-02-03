@echo off
REM Backup SPHERE Database
REM Run this before starting migration

echo ========================================
echo SPHERE Database Backup Script
echo ========================================
echo.

REM Set variables
set TIMESTAMP=%date:~-4,4%%date:~-7,2%%date:~-10,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%
set BACKUP_FILE=backup_sphere_%TIMESTAMP%.sql

echo Backing up database to: %BACKUP_FILE%
echo.

REM Backup command (adjust credentials as needed)
mysqldump -u root -p be_sphere > %BACKUP_FILE%

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo Backup completed successfully!
    echo File: %BACKUP_FILE%
    echo ========================================
) else (
    echo.
    echo ========================================
    echo ERROR: Backup failed!
    echo ========================================
)

pause
