# -----------------------------
# init.ps1 — инициализация проекта OZON_PY (v6.3+)
# -----------------------------

Write-Host "Проверяем наличие Python..."

$python = (Get-Command python.exe -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Source)

if (-not $python) {
    Write-Host "❌ Python не найден! Установите Python с https://python.org"
    exit 1
}

Write-Host "✔ Python найден: $python"


# -----------------------------
# Создаём виртуальное окружение
# -----------------------------
$venvPath = ".\.venv"
$venvPython = "$venvPath\Scripts\python.exe"

Write-Host "Проверяем виртуальное окружение..."

if (-not (Test-Path $venvPython)) {
    Write-Host "Создаю виртуальное окружение .venv..."
    python -m venv $venvPath
}

if (-not (Test-Path $venvPython)) {
    Write-Host "❌ Не удалось создать виртуальное окружение"
    exit 1
}

Write-Host "✔ Виртуальное окружение готово"


# -----------------------------
# Установка зависимостей
# -----------------------------
Write-Host "Устанавливаю зависимости..." -ForegroundColor Cyan

& $venvPython -m pip install --upgrade pip

if (Test-Path "requirements.txt") {
    Write-Host "Найден requirements.txt — устанавливаю зависимости"
    & $venvPython -m pip install -r requirements.txt
}
else {
    Write-Host "⚠ requirements.txt не найден — устанавливаю базовый набор"
    & $venvPython -m pip install requests pandas openpyxl jinja2
}

Write-Host "✔ Все зависимости установлены"


# -----------------------------
# Инструкция пользователю
# -----------------------------
Write-Host ""
Write-Host "Готово!"
Write-Host "Для работы активируйте окружение вручную:"
Write-Host "  .\.venv\Scripts\Activate.ps1"
