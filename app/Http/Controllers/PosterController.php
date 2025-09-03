<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Intervention\Image\Laravel\Facades\Image;

class PosterController extends Controller
{
    public function index()
    {
        return view('poster.index', [
            // preview (ligeras)
            'anversoPreviewUrl' => asset('storage/poster/preview/anverso.jpg'),
            'reversoPreviewUrl' => asset('storage/poster/preview/reverso.jpg'),
            // impresión (HD)
            'anversoPrintUrl'   => asset('storage/poster/print/anverso.jpg'),
            'reversoPrintUrl'   => asset('storage/poster/print/reverso.jpg'),
            // defaults desde config
            'defaults'          => config('poster.defaults'),
        ]);
    }

    public function stateSave(Request $request)
    {
        $data = $request->validate([
            'name'         => 'nullable|string|max:120',
            'phone'        => 'nullable|string|max:40',
            'code'         => 'nullable|string|max:30',
            'size'         => 'nullable|string|in:text-2xl,text-3xl,text-4xl',
            'align'        => 'nullable|string|max:40',
            'anversoX'     => 'nullable|numeric|min:0|max:100',
            'anversoY'     => 'nullable|numeric|min:0|max:100',
            'qrX'          => 'nullable|numeric|min:0|max:100',
            'qrY'          => 'nullable|numeric|min:0|max:100',
            'qrSize'       => 'nullable|integer|min:100|max:600',
            'previewScale' => 'nullable|numeric|min:0.35|max:1',
            'fitContain'   => 'nullable|boolean', // <--- nuevo
        ]);

        $request->session()->put('poster.state', $data);
        return response()->json(['ok' => true]);
    }

    public function state(Request $request)
    {
        $state = $request->session()->get('poster.state', []);
        return response()->json($state);
    }

    public function rebuildAssets()
    {
        $srcDir   = storage_path('app/poster/source');
        $outPrev  = storage_path('app/public/poster/preview');
        $outPrint = storage_path('app/public/poster/print');

        File::ensureDirectoryExists($outPrev);
        File::ensureDirectoryExists($outPrint);

        $this->makeA4Derivatives("$srcDir/anverso.jpg", "$outPrev/anverso.jpg", "$outPrint/anverso.jpg");
        $this->makeA4Derivatives("$srcDir/reverso.jpg", "$outPrev/reverso.jpg", "$outPrint/reverso.jpg");

        return response()->json(['ok' => true]);
    }

    private function makeA4Derivatives(string $src, string $dstPreview, string $dstPrint): void
    {
        if (!is_file($src)) return;

        // A4 en píxeles a 72 y 600 dpi
        [$pw, $ph] = $this->mmToPx(210, 297, 72);   // ≈ 595 × 842
        [$tw, $th] = $this->mmToPx(210, 297, 600);  // ≈ 4961 × 7016

        $img = Image::read($src)->orient();

        // Recorte A4 centrado (como background-cover pero exacto y controlado)
        $img->clone()->cover($pw, $ph, 'center')->toJpeg(80)->save($dstPreview);
        $img->clone()->cover($tw, $th, 'center')->toJpeg(90)->save($dstPrint);
    }

    private function mmToPx(int $mmW, int $mmH, int $dpi): array
    {
        $inchW = $mmW / 25.4;
        $inchH = $mmH / 25.4;
        return [ (int) round($inchW * $dpi), (int) round($inchH * $dpi) ];
    }
}
