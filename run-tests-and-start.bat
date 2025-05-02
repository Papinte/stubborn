@echo off
echo Running tests...
vendor\bin\phpunit tests
if %ERRORLEVEL% equ 0 (
    echo Tests passed, starting server...
    symfony server:start
) else (
    echo Tests failed, server not started.
    exit /b 1
)