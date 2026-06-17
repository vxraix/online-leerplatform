# PowerShell script to convert HTML flowchart to PDF using Chrome

$htmlFile = Join-Path $PSScriptRoot "flowchart_functioneel.html"
$pdfFile = Join-Path $PSScriptRoot "flowchart_functioneel.pdf"
$chromePath = "C:\Program Files\Google\Chrome\Application\chrome.exe"

# Convert file path to file:// URL format
$htmlPath = $htmlFile -replace '\\', '/'
$htmlUrl = "file:///$htmlPath"

Write-Host "Converting HTML to PDF..."
Write-Host "HTML File: $htmlFile"
Write-Host "PDF File: $pdfFile"

# Use Chrome headless to print to PDF
& $chromePath --headless --disable-gpu --print-to-pdf="$pdfFile" --print-to-pdf-no-header "$htmlUrl"

if (Test-Path $pdfFile) {
    Write-Host "PDF created successfully: $pdfFile"
} else {
    Write-Host "Error: PDF was not created"
}


