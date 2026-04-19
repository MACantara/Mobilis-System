<?php
declare(strict_types=1);

if (!function_exists('generatePythonInsights')) {
    function generatePythonInsights(array $payload): array
    {
        $cfg = appConfig();
        $python = escapeshellcmd($cfg['python_bin']);
        $script = escapeshellarg(__DIR__ . '/../python/analyze.py');
        $command = $python . ' ' . $script;

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($command, $descriptors, $pipes);
        if (!is_resource($process)) {
            return [
                'status' => 'fallback',
                'message' => 'Python analytics unavailable. Showing base metrics only.',
                'recommendations' => [],
            ];
        }

        fwrite($pipes[0], json_encode($payload, JSON_UNESCAPED_UNICODE));
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0 || trim((string) $stdout) === '') {
            return [
                'status' => 'fallback',
                'message' => 'Python analytics failed: ' . trim((string) $stderr),
                'recommendations' => [],
            ];
        }

        $decoded = json_decode((string) $stdout, true);
        if (!is_array($decoded)) {
            return [
                'status' => 'fallback',
                'message' => 'Python analytics returned invalid data.',
                'recommendations' => [],
            ];
        }

        return $decoded;
    }
}
