[Reflection.Assembly]::LoadWithPartialName("Microsoft.WindowsAzure.ServiceRuntime")

.\install-php-impl.cmd
	
# Get PHP installation details
Add-PsSnapin PHPManagerSnapin
$phpConfiguration = Get-PHPConfiguration
$phpExecutable = Get-ChildItem $phpConfiguration.ScriptProcessor
$phpExtensionsPath = $phpExecutable.DirectoryName + "\ext"
$phpIniFile = $phpConfiguration.PHPIniFilePath
	
# Get PHP installation override details
$myExtensionsPath = "..\php\ext"
$myExtensions = Get-ChildItem $myExtensionsPath | where {$_.Name.ToLower().EndsWith(".dll")}
$myPhpIniFile = "..\php\php.ini"
	
# Append PHP.ini directives
if ((Test-Path $myPhpIniFile) -eq 'True') {
	$additionalPhpIniDirectives = Get-Content $myPhpIniFile
	$additionalPhpIniDirectives = $additionalPhpIniDirectives.Replace("%EXT%", $phpExtensionsPath)

	Add-Content $phpIniFile "`r`n"
	Add-Content $phpIniFile $additionalPhpIniDirectives
}
	
# Copy and register extensions
if ($myExtensions -ne $null) {
	foreach ($myExtension in $myExtensions) {
		Copy-Item $myExtension.FullName $phpExtensionsPath
		Set-PHPExtension -Name $myExtension.Name -Status enabled
	} 
}

# Add PHP executable to PATH
if (![Microsoft.WindowsAzure.ServiceRuntime.RoleEnvironment]::CurrentRoleInstance.Id.Contains("deployment")) {
	[Environment]::SetEnvironmentVariable('Path', [Environment]::GetEnvironmentVariable('Path', 'Machine') + ";" + $phpExecutable.DirectoryName, 'Machine')
}