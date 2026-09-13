@echo off
echo Lancement du projet BET Manager...
docker-compose up -d --build
echo.
echo Le projet est en cours de lancement !
echo Veuillez patienter quelques secondes pour que la base de donnees s'initialise.
echo.
echo L'application sera disponible sur : http://localhost:8000/
echo phpMyAdmin (Base de donnees) : (non inclus ici, mais la BDD est prete)
echo.
timeout /t 5 /nobreak > NUL
start http://localhost:8000/
pause
