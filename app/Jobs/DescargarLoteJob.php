<?php

namespace App\Jobs;

use App\Models\Descarga;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class DescargarLoteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;
    public int $tries = 1;

    private array $urls;

    public function __construct(array $urls)
    {
        $this->urls = $urls;
        $this->onQueue('descargas');
    }

    public function handle(): void
    {
        $reelsPath = storage_path('app/reels');
        $cookiesPath = base_path('python/cookies.txt');

        if (!is_dir($reelsPath)) {
            mkdir($reelsPath, 0775, true);
        }

        foreach ($this->urls as $item) {
            $url = $item['url'];
            $tipo = $item['tipo'];
            $urlLimpia = $this->limpiarUrl($url);

            // Saltar si ya descargado
            if (Descarga::where('url_limpia', $urlLimpia)->where('exitosa', true)->where('eliminado', false)->exists()) {
                continue;
            }

            // Resolver Facebook /share/r/
            if ($tipo === 'facebook' && str_contains($url, '/share/r/')) {
                $url = $this->resolverFacebookShare($url);
            }

            $prefijo = match ($tipo) {
                'ig_story' => 'story_',
                'facebook' => 'fb_',
                default => '',
            };

            $archivosPre = scandir($reelsPath);

            $cmd = [
                base_path('python/venv/bin/yt-dlp'),
                '--no-warnings',
                '-o', $reelsPath . '/' . $prefijo . '%(id)s.%(ext)s',
                '--no-overwrites',
            ];

            if (file_exists($cookiesPath)) {
                $cmd[] = '--cookies';
                $cmd[] = $cookiesPath;
            }

            if ($tipo === 'ig_story') {
                $cmd[] = '--no-playlist';
            }

            $cmd[] = $url;

            $process = new Process($cmd);
            $process->setTimeout(120);

            try {
                $process->run();
                $exito = $process->isSuccessful();

                $archivoNuevo = null;
                if ($exito) {
                    $archivosPost = scandir($reelsPath);
                    $nuevos = array_diff($archivosPost, $archivosPre);
                    $archivoNuevo = collect($nuevos)->first(fn($f) => !in_array($f, ['.', '..']));

                    // Recodificar para WhatsApp
                    if ($archivoNuevo) {
                        $this->recodificarParaWhatsApp($reelsPath . '/' . $archivoNuevo);
                        $nuevoNombre = pathinfo($archivoNuevo, PATHINFO_FILENAME) . '.mp4';
                        if (file_exists($reelsPath . '/' . $nuevoNombre)) {
                            $archivoNuevo = $nuevoNombre;
                        }
                    }
                }

                Descarga::create([
                    'url' => $item['url'],
                    'url_limpia' => $urlLimpia,
                    'tipo' => $tipo,
                    'archivo' => $archivoNuevo,
                    'exitosa' => $exito,
                    'error' => $exito ? null : $process->getErrorOutput(),
                ]);

            } catch (ProcessTimedOutException $e) {
                Descarga::create([
                    'url' => $item['url'],
                    'url_limpia' => $urlLimpia,
                    'tipo' => $tipo,
                    'exitosa' => false,
                    'error' => 'Timeout',
                ]);
            }

            sleep(2);
        }
    }

    private function limpiarUrl(string $url): string
    {
        $parsed = parse_url($url);
        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . rtrim($parsed['path'] ?? '', '/');
    }

    private function resolverFacebookShare(string $url): string
    {
        $process = new Process(['curl', '-Ls', '-o', '/dev/null', '-w', '%{url_effective}', $url]);
        $process->setTimeout(15);
        $process->run();
        $resolved = trim($process->getOutput());
        if (str_contains($resolved, '/reel/') || str_contains($resolved, '/watch/')) {
            return $this->limpiarUrl($resolved);
        }
        return $url;
    }

    private function recodificarParaWhatsApp(string $path): void
    {
        $info = pathinfo($path);

        $probe = new Process(['ffprobe', '-v', 'error', '-select_streams', 'v:0',
            '-show_entries', 'stream=codec_name', '-of', 'csv=p=0', $path]);
        $probe->run();
        $videoCodec = trim($probe->getOutput());

        $probeAudio = new Process(['ffprobe', '-v', 'error', '-select_streams', 'a:0',
            '-show_entries', 'stream=codec_name', '-of', 'csv=p=0', $path]);
        $probeAudio->run();
        $audioCodec = trim($probeAudio->getOutput());

        $yaEsMp4 = strtolower($info['extension'] ?? '') === 'mp4';
        $yaEsH264 = $videoCodec === 'h264';
        $yaEsAac = $audioCodec === 'aac';

        if ($yaEsMp4 && $yaEsH264 && $yaEsAac) {
            $tmpPath = $info['dirname'] . '/' . $info['filename'] . '_fs.mp4';
            $process = new Process([
                'ffmpeg', '-i', $path,
                '-c', 'copy',
                '-movflags', '+faststart',
                '-y', $tmpPath,
            ]);
            $process->setTimeout(60);
            $process->run();

            if ($process->isSuccessful() && file_exists($tmpPath) && filesize($tmpPath) > 0) {
                unlink($path);
                rename($tmpPath, $path);
            } elseif (file_exists($tmpPath)) {
                unlink($tmpPath);
            }
            return;
        }

        $tmpPath = $info['dirname'] . '/' . $info['filename'] . '_tmp.mp4';
        $process = new Process([
            'ffmpeg', '-i', $path,
            '-c:v', 'libx264', '-preset', 'fast', '-crf', '23',
            '-c:a', 'aac', '-b:a', '128k',
            '-movflags', '+faststart',
            '-y', $tmpPath,
        ]);
        $process->setTimeout(180);
        $process->run();

        if ($process->isSuccessful() && file_exists($tmpPath) && filesize($tmpPath) > 0) {
            unlink($path);
            $nuevoPath = $info['dirname'] . '/' . $info['filename'] . '.mp4';
            rename($tmpPath, $nuevoPath);
        } elseif (file_exists($tmpPath)) {
            unlink($tmpPath);
        }
    }
}
