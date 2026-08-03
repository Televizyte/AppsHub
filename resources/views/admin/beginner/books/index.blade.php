@extends('layouts.beginner')

@section('title', 'Books & Library')
@section('eyebrow', 'Beginner Workspace')
@section('page_title', 'Books & Library')
@section('page_description', 'Create, organize, publish, and manage books for the current app.')
@section('back_url', '/admin/beginner-dashboard')
@section('form_title', 'Library Manager')
@section('form_description', 'Books are scoped to the active app and ready for the frontend Book Reader engine.')
@section('preview_description', 'Quick overview of this app library.')

@section('dock_actions')
    <a href="{{ route('admin.beginner.books.create') }}" class="dxm-btn dxm-btn--primary">+ Add Book</a>
@endsection

@section('form')
    @if (session('status'))
        <div class="dxm-alert"><strong>{{ session('status') }}</strong></div>
    @endif

    <div class="library-toolbar">
        <div>
            <strong>Book Shelf</strong>
            <small>All covers now use the same designed-cover renderer used by the preview and future Flutter reader.</small>
        </div>
        <a href="{{ route('admin.beginner.books.create') }}" class="dxm-btn dxm-btn--primary">+ Create New Book</a>
    </div>

    <div class="dxm-section-card">
        <h3>Books</h3>
        <p>Manage manual books, PDF books, external books, and compiled books. Manual books use chapters as the table of contents.</p>

        @if ($books->isEmpty())
            <div class="empty-library">
                <div class="empty-book-shape"></div>
                <strong>No books yet.</strong>
                <p>Create your first book to activate the library engine.</p>
                <a href="{{ route('admin.beginner.books.create') }}" class="dxm-btn dxm-btn--primary">Create First Book</a>
            </div>
        @else
            <div class="book-shelf-grid">
                @foreach ($books as $book)
                    @php
                        $publishedChapters = $book->chapters->where('status', 'published')->count();
                    @endphp

                    <article class="book-card">
                        <a href="{{ route('admin.beginner.books.edit', $book) }}" class="book-cover-wrap" aria-label="Edit {{ $book->title }}">
                            @include('admin.beginner.books.partials-designed-cover', [
                                'bookForCover' => $book,
                                'coverSize' => 'card',
                                'coverClass' => 'book-cover'
                            ])
                        </a>

                        <div class="book-body">
                            <h4>{{ $book->title }}</h4>
                            <p>{{ $book->subtitle ?: $book->author_name ?: 'No subtitle yet' }}</p>

                            <div class="book-tags">
                                <span>{{ ucfirst($book->book_type) }}</span>
                                <span>{{ ucfirst(str_replace('_', ' ', $book->status)) }}</span>
                                <span>{{ ucfirst(str_replace('_', ' ', $book->access_type)) }}</span>
                                @if ($book->is_featured)
                                    <span>Featured</span>
                                @endif
                            </div>

                            <small>{{ $book->chapters->count() }} chapter(s) · {{ $publishedChapters }} published · Order {{ $book->sort_order }}</small>
                        </div>

                        <div class="book-actions">
                            <a href="{{ route('admin.beginner.books.edit', $book) }}" class="dxm-btn dxm-btn--small">Edit</a>
                            <a href="{{ route('admin.beginner.books.chapters.index', $book) }}" class="dxm-btn dxm-btn--small">Chapters</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@section('preview')
    <div class="dxm-preview__card">
        <div class="dxm-preview__title">Library Status</div>
        <div class="dxm-preview__meta">
            <span class="dxm-tag">{{ $stats['total'] }} Books</span>
            <span class="dxm-tag">{{ $stats['published'] }} Published</span>
            <span class="dxm-tag">{{ $stats['draft'] }} Draft</span>
            <span class="dxm-tag">{{ $stats['featured'] }} Featured</span>
            <span class="dxm-tag">{{ $stats['chapters'] ?? 0 }} Chapters</span>
        </div>
        <div class="dxm-preview__subtitle">This module is ready for the reusable Flutter Book Reader / Library engine.</div>
    </div>

    <div class="dxm-preview__card">
        <div class="dxm-preview__title">Engine Notes</div>
        <div class="dxm-preview__subtitle">
            Manual books support chapters, table of contents, reading progress, and Read Aloud later. PDF books use uploaded files. External books open links. Compiled books are prepared for future article/devotional compilation.
        </div>
    </div>
@endsection

@push('styles')
<style>
.library-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;border:1px solid rgba(255,255,255,.10);background:rgba(2,6,23,.36);border-radius:18px;padding:14px;margin-bottom:14px}.library-toolbar strong{display:block;color:#fff;font-size:15px}.library-toolbar small{display:block;color:rgba(255,255,255,.58);margin-top:4px}.book-shelf-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(168px,1fr));gap:16px}.book-card{border:1px solid rgba(255,255,255,.10);border-radius:20px;background:rgba(255,255,255,.035);overflow:hidden;display:flex;flex-direction:column;min-width:0}.book-cover-wrap{display:block;padding:14px 14px 0;background:linear-gradient(180deg,rgba(255,255,255,.04),rgba(2,6,23,.12))}.book-cover{width:100%}.book-body{padding:12px;flex:1}.book-body h4{margin:0;color:#fff;font-size:14px;line-height:1.25}.book-body p{margin:6px 0 0;color:rgba(255,255,255,.64);font-size:12px;line-height:1.45}.book-tags{display:flex;gap:6px;flex-wrap:wrap;margin:10px 0}.book-tags span{font-size:10px;font-weight:900;color:rgba(207,250,254,.92);border:1px solid rgba(34,211,238,.26);background:rgba(34,211,238,.08);border-radius:999px;padding:5px 7px}.book-actions{display:flex;gap:8px;padding:12px;border-top:1px solid rgba(255,255,255,.08)}.dxm-btn--small{min-height:32px;padding:6px 10px;font-size:11px;border-radius:10px}.empty-library{border:1px dashed rgba(34,211,238,.35);border-radius:22px;background:rgba(34,211,238,.055);padding:28px;text-align:center}.empty-library strong{display:block;color:#fff;font-size:18px;margin-top:12px}.empty-library p{color:rgba(255,255,255,.62)}.empty-book-shape{width:90px;aspect-ratio:3/4;border-radius:12px;margin:0 auto;background:linear-gradient(135deg,#0B1F4D,#1D5CFF,#E2388A);box-shadow:0 18px 40px rgba(0,0,0,.36)}@media(max-width:760px){.library-toolbar{display:block}.library-toolbar .dxm-btn{width:100%;margin-top:12px}.book-shelf-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.book-actions{flex-direction:column}}
</style>
@endpush
