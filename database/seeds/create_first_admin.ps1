[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'

$repositoryPath = (Resolve-Path -LiteralPath (
    Join-Path $PSScriptRoot '..\..'
)).Path
$phpPath = 'C:\xampp\php\php.exe'
$seedPath = Join-Path $PSScriptRoot 'create_first_admin.php'

if (-not (Test-Path -LiteralPath $phpPath)) {
    throw "PHP executable not found: $phpPath"
}

if (-not (Test-Path -LiteralPath $seedPath)) {
    throw "First-Admin seed not found: $seedPath"
}

$username = (Read-Host 'First Admin username').Trim()
$displayName = (Read-Host 'First Admin display name').Trim()
$securePassword = Read-Host 'Password (minimum 8 characters)' -AsSecureString
$secureConfirmation = Read-Host 'Confirm password' -AsSecureString
$passwordPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR(
    $securePassword
)
$confirmationPointer =
    [Runtime.InteropServices.Marshal]::SecureStringToBSTR(
        $secureConfirmation
    )

try {
    $plainPassword =
        [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPointer)
    $plainConfirmation =
        [Runtime.InteropServices.Marshal]::PtrToStringBSTR(
            $confirmationPointer
        )

    if ($plainPassword -cne $plainConfirmation) {
        throw 'Password confirmation does not match.'
    }

    Push-Location $repositoryPath

    try {
        $plainPassword |
            & $phpPath `
                $seedPath `
                "--username=$username" `
                "--display-name=$displayName" `
                --password-stdin

        if ($LASTEXITCODE -ne 0) {
            throw "First-Admin setup failed with exit code $LASTEXITCODE."
        }
    }
    finally {
        Pop-Location
    }
}
finally {
    if ($null -ne $passwordPointer) {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPointer)
    }

    if ($null -ne $confirmationPointer) {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($confirmationPointer)
    }

    Remove-Variable plainPassword -ErrorAction SilentlyContinue
    Remove-Variable plainConfirmation -ErrorAction SilentlyContinue
}
