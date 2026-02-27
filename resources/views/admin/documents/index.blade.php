@extends('layouts.app')
@section('title', 'Documentos - Admin')
@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-white mb-6">📄 Documentos</h1>

    {{-- Mensajes --}}
    @if(session('success'))
        <div class="bg-green-500/20 border border-green-500/50 text-green-300 px-4 py-3 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif

    {{-- Formulario subir --}}
    <div class="bg-white/5 backdrop-blur rounded-xl border border-white/10 p-6 mb-8">
        <h2 class="text-lg font-semibold text-white mb-4">⬆️ Subir nuevo documento</h2>
        <form action="{{ route('admin.documents.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Título</label>
                    <input type="text" name="title" required
                        class="w-full bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ej: Propuesta API 4Life">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Categoría</label>
                    <input type="text" name="category" required list="categories"
                        class="w-full bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ej: Propuestas comerciales">
                    <datalist id="categories">
                        @foreach($documents->keys() as $cat)
                            <option value="{{ $cat }}">
                        @endforeach
                        <option value="Propuestas comerciales">
                        <option value="Documentación técnica">
                        <option value="Manuales">
                        <option value="Contratos">
                        <option value="Marketing">
                    </datalist>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1">Descripción (opcional)</label>
                <input type="text" name="description"
                    class="w-full bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Breve descripción del documento">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1">Archivo</label>
                <input type="file" name="file" required
                    class="w-full text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 file:cursor-pointer">
                <p class="text-xs text-gray-500 mt-1">Máximo 20MB. PDF, DOCX, XLSX, ZIP, imágenes...</p>
            </div>
            <button type="submit"
                class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm rounded-lg transition">
                <i class="fas fa-upload mr-2"></i> Subir documento
            </button>
        </form>
    </div>

    {{-- Lista de documentos por categoría --}}
    @forelse($documents as $category => $docs)
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-300 mb-3 flex items-center">
                <i class="fas fa-folder-open text-yellow-400 mr-2"></i>
                {{ $category }}
                <span class="ml-2 text-xs bg-white/10 text-gray-400 px-2 py-0.5 rounded-full">{{ $docs->count() }}</span>
            </h2>
            <div class="space-y-3">
                @foreach($docs as $doc)
                    <div class="bg-white/5 backdrop-blur rounded-xl border border-white/10 p-4 flex items-center justify-between group hover:bg-white/10 transition">
                        <div class="flex items-center space-x-4 flex-1 min-w-0">
                            <i class="{{ $doc->getIconClass() }} text-2xl"></i>
                            <div class="min-w-0">
                                <h3 class="text-white font-medium truncate">{{ $doc->title }}</h3>
                                @if($doc->description)
                                    <p class="text-gray-500 text-sm truncate">{{ $doc->description }}</p>
                                @endif
                                <div class="flex items-center space-x-3 mt-1 text-xs text-gray-500">
                                    <span>{{ $doc->filename }}</span>
                                    <span>•</span>
                                    <span>{{ $doc->getFormattedSize() }}</span>
                                    <span>•</span>
                                    <span>{{ $doc->downloads }} descargas</span>
                                    <span>•</span>
                                    <span>{{ $doc->created_at->format('d/m/Y') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 ml-4">
                            {{-- Descargar --}}
                            <a href="{{ route('admin.documents.download', $doc) }}"
                                class="inline-flex items-center px-3 py-1.5 bg-blue-600/20 hover:bg-blue-600/40 text-blue-400 rounded-lg text-sm transition">
                                <i class="fas fa-download mr-1"></i> Descargar
                            </a>
                            {{-- Copiar link --}}
                            <button onclick="navigator.clipboard.writeText('{{ url(Storage::url($doc->path)) }}').then(() => this.innerHTML='<i class=\'fas fa-check mr-1\'></i> Copiado')"
                                class="inline-flex items-center px-3 py-1.5 bg-gray-600/20 hover:bg-gray-600/40 text-gray-400 rounded-lg text-sm transition">
                                <i class="fas fa-link mr-1"></i> Link
                            </button>
                            {{-- Toggle activo --}}
                            <form action="{{ route('admin.documents.toggle', $doc) }}" method="POST">
                                @csrf @method('PATCH')
                                <button class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm transition
                                    {{ $doc->is_active ? 'bg-green-600/20 hover:bg-green-600/40 text-green-400' : 'bg-red-600/20 hover:bg-red-600/40 text-red-400' }}">
                                    <i class="fas {{ $doc->is_active ? 'fa-eye' : 'fa-eye-slash' }} mr-1"></i>
                                    {{ $doc->is_active ? 'Activo' : 'Oculto' }}
                                </button>
                            </form>
                            {{-- Eliminar --}}
                            <form action="{{ route('admin.documents.destroy', $doc) }}" method="POST"
                                onsubmit="return confirm('¿Eliminar este documento?')">
                                @csrf @method('DELETE')
                                <button class="inline-flex items-center px-3 py-1.5 bg-red-600/20 hover:bg-red-600/40 text-red-400 rounded-lg text-sm transition">
                                    <i class="fas fa-trash mr-1"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="bg-white/5 rounded-xl border border-white/10 p-8 text-center">
            <i class="fas fa-folder-open text-4xl text-gray-600 mb-3"></i>
            <p class="text-gray-400">No hay documentos todavía. Sube el primero.</p>
        </div>
    @endforelse
</div>
@endsection
