@echo off
setlocal
cd /d "%~dp0"

where py >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo Python Launcher (py) was not found. Please install Python and try again.
    pause
    exit /b 1
)

set "VENV_DIR=%~dp0.venv"

if not exist "%VENV_DIR%\Scripts\python.exe" (
    echo Creating virtual environment...
    py -3 -m venv "%VENV_DIR%"
    if %ERRORLEVEL% NEQ 0 (
        echo Failed to create virtual environment.
        pause
        exit /b 1
    )
)

call "%VENV_DIR%\Scripts\activate.bat"

python -m pip install --upgrade pip
python -m pip install -e .

if %ERRORLEVEL% NEQ 0 (
    echo Dependency installation failed.
    pause
    exit /b 1
)

echo Starting Lenticular Machine API on http://127.0.0.1:8081
python -c "from lenticular_machine.cli import api_main; api_main()"

pause
