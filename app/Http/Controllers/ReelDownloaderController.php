<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use App\Models\Descarga;
use App\Jobs\DescargarLoteJob;

class ReelDownloaderController extends Controller
{
    private string $pythonBase;
    private string $cookiesPath;
    private string $reelsPath;

    public function __construct()
    {
        $this->pythonBase = base_path('python');
        $this->cookiesPath = base_path('python/cookies.txt');
        $this->reelsPath = storage_path('app/reels');
    }

    public function index()
    {
        $hasCookies = file_exists($this->cookiesPath);
        $cookiesAge = $hasCookies
            ? now()->diffForHumans(now()->setTimestamp(filemtime($this->cookiesPath)), true) . ' ago'
            : null;

        // Listar archivos descargados
        if (!is_dir($this->reelsPath)) {
            mkdir($this->reelsPath, 0775, true);
        }
        $archivos = collect(scandir($this->reelsPath))
            ->filter(fn($f) => !in_array($f, ['.', '..']))
            ->map(fn($f) => [
                'nombre' => $f,
                'tamano' => round(filesize($this->reelsPath . '/' . $f) / 1024 / 1024, 1),
                'fecha' => date('Y-m-d H:i', filemtime($this->reelsPath . '/' . $f)),
            ])
            ->sortByDesc('fecha')
            ->values();

        return view('reels.index', compact('hasCookies', 'cookiesAge', 'archivos'));
    }

    public function uploadCookies(Request $request)
    {
        $request->validate([
            'cookies' => 'required|file|mimes:txt|max:512',
        ]);

        $request->file('cookies')->move($this->pythonBase, 'cookies.txt');

        return back()->with('success', 'Cookies actualizadas correctamente.');
    }

    public function procesar(Request $request)
    {
        $request->validate([
            'texto' => 'required|string|min:10',
        ]);

        $texto = $request->input('texto');

        // Extraer URLs
        preg_match_all('#https?://[^\s<>"\']+#', $texto, $matches);
        $urls = array_unique($matches[0] ?? []);

        if (empty($urls)) {
            return back()->with('error', 'No se encontraron URLs en el texto.')->withInput();
        }

        // Clasificar
        $clasificadas = [];
        foreach ($urls as $url) {
            $tipo = $this->clasificarUrl($url);
            $limpia = $this->limpiarUrl($url);
            $descarga = Descarga::where('url_limpia', $limpia)->where('exitosa', true)->where('eliminado', false)->first();
            $clasificadas[] = [
                'url' => $limpia,
                'original' => $url,
                'tipo' => $tipo,
                'etiqueta' => $this->etiqueta($tipo),
                'descargable' => in_array($tipo, ['ig_reel', 'ig_story', 'facebook']),
                'ya_descargado' => $descarga !== null,
                'archivo' => $descarga?->archivo,
            ];
        }

        $clasificadas = collect($clasificadas)->unique('url')->values()->all();
        session(['texto_analizado' => $texto]);
        return back()->with('clasificadas', $clasificadas)->withInput();
    }

    public function descargar(Request $request)
    {
        $request->validate([
            'urls' => 'required|array',
            'urls.*.url' => 'required|url',
            'urls.*.tipo' => 'required|string',
        ]);

        // Filtrar ya descargados
        $pendientes = [];
        $saltados = 0;
        foreach ($request->input('urls') as $item) {
            $urlLimpia = $this->limpiarUrl($item['url']);
            if (Descarga::where('url_limpia', $urlLimpia)->where('exitosa', true)->where('eliminado', false)->exists()) {
                $saltados++;
                continue;
            }
            $pendientes[] = $item;
        }

        // Despachar en lotes de 5
        $lotes = array_chunk($pendientes, 5);
        foreach ($lotes as $i => $lote) {
            DescargarLoteJob::dispatch($lote)->delay(now()->addSeconds($i * 30));
        }

        $total = count($pendientes);
        $numLotes = count($lotes);
        $msg = "{$total} descargas enviadas a la cola en {$numLotes} lotes.";
        if ($saltados > 0) {
            $msg .= " {$saltados} ya estaban descargados.";
        }

        // Re-analizar links para mantener la clasificación visible
        $texto = session('texto_analizado', '');
        if ($texto) {
            preg_match_all('#https?://[^\s<>"\'\']+#', $texto, $matches);
            $urls = array_unique($matches[0] ?? []);
            $clasificadas = [];
            foreach ($urls as $url) {
                $tipo = $this->clasificarUrl($url);
                $limpia = $this->limpiarUrl($url);
                $descarga = Descarga::where('url_limpia', $limpia)->where('exitosa', true)->where('eliminado', false)->first();
                $clasificadas[] = [
                    'url' => $limpia,
                    'original' => $url,
                    'tipo' => $tipo,
                    'etiqueta' => $this->etiqueta($tipo),
                    'descargable' => in_array($tipo, ['ig_reel', 'ig_story', 'facebook']),
                    'ya_descargado' => $descarga !== null,
                    'archivo' => $descarga?->archivo,
                ];
            }
            $clasificadas = collect($clasificadas)->unique('url')->values()->all();
            return back()->with('success', $msg)->with('clasificadas', $clasificadas);
        }

        return back()->with('success', $msg);
    }


    public function descargarArchivo(string $archivo)
    {
        $path = $this->reelsPath . "/" . basename($archivo);
        if (!file_exists($path)) {
            abort(404);
        }
        return response()->download($path);
    }

    public function verArchivo(string $archivo)
    {
        $path = $this->reelsPath . '/' . $archivo;
        if (!file_exists($path)) {
            abort(404);
        }
        return response()->file($path);
    }

    public function eliminarMasivo(Request $request)
    {
        $request->validate([
            'archivos' => 'required|array',
            'archivos.*' => 'required|string',
        ]);

        $eliminados = 0;
        foreach ($request->input('archivos') as $archivo) {
            $path = $this->reelsPath . '/' . basename($archivo);
            if (file_exists($path)) {
                unlink($path);
                Descarga::where('archivo', basename($archivo))->update(['eliminado' => true]);
                $eliminados++;
            }
        }

        return back()->with('success', "$eliminados archivos eliminados.");
    }

    public function eliminarArchivo(string $archivo)
    {
        $path = $this->reelsPath . '/' . basename($archivo);
        if (file_exists($path)) {
            unlink($path);
            Descarga::where('archivo', basename($archivo))->update(['eliminado' => true]);
        }
        return back()->with('success', "Archivo $archivo eliminado.");
    }

    private function clasificarUrl(string $url): string
    {
        $u = strtolower($url);
        if (str_contains($u, 'instagram.com/reel/')) return 'ig_reel';
        if (str_contains($u, 'instagram.com/stories/')) return 'ig_story';
        if (str_contains($u, 'instagram.com/p/')) return 'ig_post';
        if (str_contains($u, 'facebook.com') || str_contains($u, 'fb.com')) return 'facebook';
        if (str_contains($u, 'tiktok.com')) return 'tiktok';
        if (str_contains($u, 'youtube.com') || str_contains($u, 'youtu.be')) return 'youtube';
        return 'otro';
    }

    private function limpiarUrl(string $url): string
    {
        $parsed = parse_url($url);
        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . rtrim($parsed['path'] ?? '', '/');
    }

    private function etiqueta(string $tipo): string
    {
        return match ($tipo) {
            'ig_reel' => 'IG Reel',
            'ig_story' => 'IG Story',
            'ig_post' => 'IG Post',
            'facebook' => 'Facebook',
            'tiktok' => 'TikTok',
            'youtube' => 'YouTube',
            default => 'Otro',
        };
    }


    private function recodificarParaWhatsApp(string $path): void
    {
        $info = pathinfo($path);

        // Comprobar si ya es h264+aac en mp4
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

        // Si ya es mp4 h264+aac, solo asegurar faststart
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

        // Si no, recodificar completo
        $tmpPath = $info['dirname'] . '/' . $info['filename'] . '_tmp.mp4';
        $process = new Process([
            'ffmpeg', '-i', $path,
            '-c:v', 'libx264',
            '-preset', 'fast',
            '-crf', '23',
            '-c:a', 'aac',
            '-b:a', '128k',
            '-movflags', '+faststart',
            '-y',
            $tmpPath,
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
}
