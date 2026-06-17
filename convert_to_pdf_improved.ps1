# Improved PowerShell script to convert HTML flowchart to PDF using Chrome

$htmlFile = Join-Path $PSScriptRoot "flowchart_functioneel.html"
$pdfFile = Join-Path $PSScriptRoot "flowchart_functioneel.pdf"
$chromePath = "C:\Program Files\Google\Chrome\Application\chrome.exe"

# Convert file path to file:// URL format
$htmlPath = $htmlFile -replace '\\', '/'
$htmlUrl = "file:///$htmlPath"

Write-Host "Converting HTML flowchart to PDF..."
Write-Host "Source: $htmlFile"
Write-Host "Target: $pdfFile"

# Remove existing PDF if it exists
if (Test-Path $pdfFile) {
    Remove-Item $pdfFile -Force
    Write-Host "Removed existing PDF file"
}

# Use Chrome headless with better settings for PDF conversion
$arguments = @(
    "--headless",
    "--disable-gpu",
    "--run-all-compositor-stages-before-draw",
    "--print-to-pdf=`"$pdfFile`"",
    "--print-to-pdf-no-header",
    "--virtual-time-budget=2000",
    "$htmlUrl"
)

Start-Process -FilePath $chromePath -ArgumentList $arguments -Wait -NoNewWindow -ErrorAction SilentlyContinue

# Wait a bit for file to be written
Start-Sleep -Seconds 2

if (Test-Path $pdfFile) {
    $fileInfo = Get-Item $pdfFile
    Write-Host "`n✓ PDF created successfully!" -ForegroundColor Green
    Write-Host "  File: $pdfFile" -ForegroundColor Green
    Write-Host "  Size: $($fileInfo.Length) bytes" -ForegroundColor Green
    Write-Host "`nYou can now open the PDF file." -ForegroundColor Cyan
} else {
    Write-Host "`n✗ Error: PDF was not created" -ForegroundColor Red
    Write-Host "`nAlternative: Open the HTML file in your browser and use Print > Save as PDF" -ForegroundColor Yellow
}
}

