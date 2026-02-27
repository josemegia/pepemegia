<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FlDocumentsController extends Controller
{
    public function index()
    {
        $documents = FlDocument::orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('category');

        return view('admin.documents.index', compact('documents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'category' => 'required|string|max:100',
            'file' => 'required|file|max:20480',
        ]);

        $file = $request->file('file');
        $path = $file->store('docs', 'public');

        FlDocument::create([
            'title' => $request->title,
            'description' => $request->description,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'category' => $request->category,
        ]);

        return redirect()->route('admin.documents.index')
            ->with('success', 'Documento subido correctamente.');
    }

    public function download(FlDocument $document)
    {
        $document->incrementDownloads();

        return Storage::disk('public')->download(
            $document->path,
            $document->filename
        );
    }

    public function toggleActive(FlDocument $document)
    {
        $document->update(['is_active' => !$document->is_active]);

        return redirect()->route('admin.documents.index')
            ->with('success', $document->is_active ? 'Documento activado.' : 'Documento desactivado.');
    }

    public function destroy(FlDocument $document)
    {
        Storage::disk('public')->delete($document->path);
        $document->delete();

        return redirect()->route('admin.documents.index')
            ->with('success', 'Documento eliminado.');
    }
}
