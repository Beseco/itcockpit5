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

        $script = <<<'PS'
$ErrorActionPreference = 'Stop'
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
    Write-Output "OK"
} catch {
    Write-Error $_.Exception.Message
    exit 1
}
PS;

        $env = array_merge(getenv() ?: [], [
            'EX_URL'      => $this->settings->exchange_url,
            'EX_USER'     => $this->settings->exchange_user,
            'EX_PASSWORD' => $this->settings->exchange_password,
            'EX_AUTH'     => $this->settings->exchange_auth ?? 'Negotiate',
        ]);

        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open([$pwsh, '-NonInteractive', '-NoProfile', '-Command', '-'], $descriptors, $pipes, null, $env);

        if (!is_resource($proc)) {
            return ['success' => false, 'message' => 'Prozess konnte nicht gestartet werden.'];
        }

        fwrite($pipes[0], $script);
        fclose($pipes[0]);
        $stdout = trim(stream_get_contents($pipes[1]));
        $stderr = trim(stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);

        if ($exitCode === 0) {
            return ['success' => true, 'message' => 'Verbindung zu Exchange erfolgreich hergestellt.'];
        }
        return ['success' => false, 'message' => $stderr ?: $stdout ?: 'Unbekannter Fehler.'];
    }

    /**
     * Postfach für einen AD-Benutzer per Exchange PowerShell aktivieren.
     * Erfordert pwsh (PowerShell Core) auf dem Server.
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
            return ['success' => false, 'output' => '', 'error' => 'pwsh (PowerShell Core) nicht gefunden. Bitte installieren: https://aka.ms/install-powershell'];
        }

        $script = $this->buildScript($samAccountName);

        $env = array_merge(getenv() ?: [], [
            'EX_URL'      => $this->settings->exchange_url,
            'EX_USER'     => $this->settings->exchange_user,
            'EX_PASSWORD' => $this->settings->exchange_password,
            'EX_AUTH'     => $this->settings->exchange_auth ?? 'Negotiate',
            'EX_IDENTITY' => $samAccountName,
        ]);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open(
            [$pwsh, '-NonInteractive', '-NoProfile', '-Command', '-'],
            $descriptors,
            $pipes,
            null,
            $env
        );

        if (!is_resource($proc)) {
            return ['success' => false, 'output' => '', 'error' => 'Prozess konnte nicht gestartet werden.'];
        }

        fwrite($pipes[0], $script);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($proc);

        return [
            'success' => $exitCode === 0,
            'output'  => trim($stdout),
            'error'   => trim($stderr),
        ];
    }

    private function buildScript(string $samAccountName): string
    {
        // Credentials und Identity kommen aus Umgebungsvariablen – kein Injection-Risiko
        return <<<'PS'
$ErrorActionPreference = 'Stop'
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

    Enable-Mailbox -Identity $env:EX_IDENTITY -ErrorAction Stop | Out-Null

    # Sofortige Verifikation: Postfach muss abrufbar sein
    $mb = Get-Mailbox -Identity $env:EX_IDENTITY -ErrorAction Stop
    Write-Output "OK: Postfach aktiviert | Alias=$($mb.Alias) | Datenbank=$($mb.Database) | E-Mail=$($mb.PrimarySmtpAddress)"

    Remove-PSSession $session
} catch {
    if ($session) { try { Remove-PSSession $session } catch {} }
    Write-Error $_.Exception.Message
    exit 1
}
PS;
    }

    private function findPwsh(): ?string
    {
        foreach (['pwsh', '/usr/bin/pwsh', '/usr/local/bin/pwsh'] as $candidate) {
            $out = shell_exec('command -v ' . escapeshellarg($candidate) . ' 2>/dev/null');
            if ($out && trim($out) !== '') {
                return trim($out);
            }
        }
        // Windows-Fallback (Entwicklungsumgebung)
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
