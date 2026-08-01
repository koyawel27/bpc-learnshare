[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$repositoryRoot = (Resolve-Path -LiteralPath (
    Join-Path $PSScriptRoot '..\..'
)).Path
$phpPath = 'C:\xampp\php\php.exe'
$seedPath = Join-Path $PSScriptRoot 'seed_demo_taxonomy.php'

if (-not (Test-Path -LiteralPath $phpPath)) {
    throw "PHP executable not found: $phpPath"
}

if (-not (Test-Path -LiteralPath $seedPath)) {
    throw "Demo taxonomy seed not found: $seedPath"
}

Push-Location $repositoryRoot

try {
    & $phpPath $seedPath

    if ($LASTEXITCODE -ne 0) {
        throw "Demo taxonomy setup failed with exit code $LASTEXITCODE."
    }
}
finally {
    Pop-Location
}