@echo off
echo Lancement des tests...
call vendor\bin\phpunit tests > nul
echo ERRORLEVEL apres les tests : %ERRORLEVEL%
if not errorlevel 1 (
    echo Tests reussis, tentative de demarrage du serveur...
    symfony server:start --no-tls --port=8000
    if %ERRORLEVEL% neq 0 (
        echo Echec du demarrage du serveur, verifiez que Symfony est installe et que le port 8000 est libre.
        pause
        exit /b %ERRORLEVEL%
    ) else (
        echo Serveur demarre avec succes.
    )
) else (
    echo Les tests ont echoue, le serveur ne sera pas demarre.
    exit /b 1
)