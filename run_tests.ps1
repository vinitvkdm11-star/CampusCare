
$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot

$python = Join-Path $PSScriptRoot ".venv\Scripts\python.exe"
$reports = Join-Path $PSScriptRoot "reports"

if (-not (Test-Path $python)) {
    Write-Error "Virtual environment not found. Create .venv and install requirements first."
    exit 1
}

New-Item -ItemType Directory -Force -Path $reports | Out-Null

& $python -m pytest tests\automation -v `
    --html=reports\selenium_report.html `
    --self-contained-html

if ($LASTEXITCODE -ne 0) {
    Write-Error "Some tests failed. Review the pytest output and HTML report."
    exit $LASTEXITCODE
}

Write-Host ""
Write-Host "All automated tests passed."
Write-Host "Report: $reports\selenium_report.html"
