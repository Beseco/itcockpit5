<?php

namespace App\Modules\Onboarding\Services;

use App\Modules\Onboarding\Models\OnboardingSettings;

class ExchangeMailboxService
{
    private OnboardingSettings $settings;

    public function __construct()
    {
        $this->settings = OnboardingSettings::getSingleton();
    }

    public function isConfigured(): bool
    {
        return !empty($this->settings->exchange_url)
            && !empty($this->settings->exchange_user)
            && !empty($this->settings->exchange_password);
    }

    /**
     * Postfach für einen AD-Benutzer per Exchange PowerShell aktivieren.
     * Versucht es bis zu 3× mit 10s Pause (AD-Replikation kann verzögert sein).
     *
     * @return array{success: bool, output: string, error: string}
     */
    public function enableMailbox(string $samAccountName): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'output' => '', 'error' => 'Exchange nicht konfiguriert.'];
        }

        $pwsh = $this->findPwsh();
        if (!$pwsh) {
            return ['success' => false, 'output' => '', 'error' => 'pwsh (PowerShell Core) nicht gefunden.'];
        }

        $env = array_merge(getenv() ?: [], [
            'EX_URL'      => $this->settings->exchange_url,
            'EX_USER'     => $this->settings->exchange_user,
            'EX_PASSWORD' => $this->settings->exchange_password,
            'EX_AUTH'     => $this->settings->exchange_auth ?? 'Negotiate',
            'EX_IDENTITY' => $samAccountName,
            'EX_DB'       => $this->settings->exchange_mailbox_db ?? '',
            'TERM'        => 'dumb',
            'NO_COLOR'    => '1',
        ]);

        return $this->runScript($this->buildEnableScript(), $env, $pwsh);
    }

    /**
     * Prüft ob pwsh vorhanden und eine PS-Session zum Exchange-Server aufgebaut werden kann.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        $pwsh = $this->findPwsh();
        if (!$pwsh) {
            return ['success' => false, 'message' => 'pwsh nicht gefunden. Installation: https://aka.ms/install-powershell'];
        }

        $env = array_merge(getenv() ?: [], [
            'EX_URL'      => $this->settings->exchange_url,
            'EX_USER'     => $this->settings->exchange_user,
            'EX_PASSWORD' => $this->settings->exchange_password,
            'EX_AUTH'     => $this->settings->exchange_auth ?? 'Negotiate',
            'TERM'        => 'dumb',
            'NO_COLOR'    => '1',
        ]);

        $script = <<<'PS'
$ErrorActionPreference = 'Stop'
if ($PSStyle) { $PSStyle.OutputRendering = [System.Management.Automation.OutputRendering]::PlainText }
try {
    $secPwd  = ConvertTo-SecureString $env:EX_PASSWORD -AsPlainText -Force
    $cred    = New-Object System.Management.Automation.PSCredential($env:EX_USER, $secPwd)
    $session = New-PSSession `
        -ConfigurationName Microsoft.Exchange `
        -ConnectionUri     $env:EX_URL `
        -Authentication    $env:EX_AUTH `
        -Credential        $cred `
        -ErrorAction       Stop
    Remove-PSSession $session
    Write-Output "OK: Verbindung zu Exchange erfolgreich."
} catch {
    Write-Error $_.Exception.Message
    exit 1
}
PS;

        $result = $this->runScript($script, $env, $pwsh);
        return [
            'success' => $result['success'],
            'message' => $result['success'] ? $result['output'] : ($result['error'] ?: $result['output']),
        ];
    }

    private function buildEnableScript(): string
    {
        // 3 Versuche mit 10s Pause – AD-Replikation kann nach User-Anlage verzögert sein.
        // Credentials/Identity/DB kommen aus Env-Variablen (kein Injection-Risiko).
        return <<<'PS'
$ErrorActionPreference = 'Stop'
if ($PSStyle) { $PSStyle.OutputRendering = [System.Management.Automation.OutputRendering]::PlainText }

$maxAttempts = 3
$attempt     = 0
$lastError   = $null

try {
    $secPwd  = ConvertTo-SecureString $env:EX_PASSWORD -AsPlainText -Force
    $cred    = New-Object System.Management.Automation.PSCredential($env:EX_USER, $secPwd)
    $session = New-PSSession `
        -ConfigurationName Microsoft.Exchange `
        -ConnectionUri     $env:EX_URL `
        -Authentication    $env:EX_AUTH `
        -Credential        $cred `
        -ErrorAction       Stop
    Import-PSSession $session -DisableNameChecking -AllowClobber -CommandName Enable-Mailbox,Get-Mailbox | Out-Null

    while ($attempt -lt $maxAttempts) {
        $attempt++
        try {
            $enableParams = @{ Identity = $env:EX_IDENTITY; ErrorAction = 'Stop' }
            if ($env:EX_DB -and $env:EX_DB -ne '') { $enableParams['Database'] = $env:EX_DB }
            Enable-Mailbox @enableParams | Out-Null

            # Verifikation
            $mb = Get-Mailbox -Identity $env:EX_IDENTITY -ErrorAction Stop
            Write-Output "OK: Postfach aktiviert (Versuch $attempt) | Alias=$($mb.Alias) | Datenbank=$($mb.Database) | E-Mail=$($mb.PrimarySmtpAddress)"
            Remove-PSSession $session
            exit 0
        } catch {
            $lastError = $_.Exception.Message
            if ($attempt -lt $maxAttempts) {
                Write-Output "Versuch $attempt fehlgeschlagen ($lastError) – warte 10s..."
                Start-Sleep -Seconds 10
            }
        }
    }

    Remove-PSSession $session
    Write-Error "Alle $maxAttempts Versuche fehlgeschlagen. Letzter Fehler: $lastError"
    exit 1

} catch {
    if ($session) { try { Remove-PSSession $session } catch {} }
    Write-Error $_.Exception.Message
    exit 1
}
PS;
    }

    private function runScript(string $script, array $env, string $pwsh): array
    {
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open([$pwsh, '-NonInteractive', '-NoProfile', '-Command', '-'], $descriptors, $pipes, null, $env);

        if (!is_resource($proc)) {
            return ['success' => false, 'output' => '', 'error' => 'Prozess konnte nicht gestartet werden.'];
        }

        fwrite($pipes[0], $script);
        fclose($pipes[0]);

        $stdout = trim(stream_get_contents($pipes[1]));
        $stderr = trim(stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);

        return [
            'success' => $exitCode === 0,
            'output'  => $stdout,
            'error'   => $stderr,
        ];
    }

    private function findPwsh(): ?string
    {
        foreach (['pwsh', '/usr/bin/pwsh', '/usr/local/bin/pwsh'] as $candidate) {
            $out = shell_exec('command -v ' . escapeshellarg($candidate) . ' 2>/dev/null');
            if ($out && trim($out) !== '') return trim($out);
        }
        if (PHP_OS_FAMILY === 'Windows') {
            foreach (['pwsh.exe', 'C:\\Program Files\\PowerShell\\7\\pwsh.exe'] as $candidate) {
                if (is_executable($candidate)) return $candidate;
            }
            $out = shell_exec('where pwsh 2>NUL');
            if ($out && trim($out) !== '') return trim(explode("\n", $out)[0]);
        }
        return null;
    }
}
