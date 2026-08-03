@extends('layouts.beginner')

@section('title', 'Book Chapters')
@section('eyebrow', 'Books & Library')
@section('page_title', 'Chapters: ' . $book->title)
@section('page_description', 'Build the table of contents and chapter content for this book.')
@section('back_url', route('admin.beginner.books.edit', $book))
@section('form_title', 'Chapter Builder')
@section('form_description', 'Each chapter becomes part of the frontend book reader and read-aloud engine.')
@section('preview_description', 'Book table of contents preview.')

@section('dock_actions')
    <a href="{{ route('admin.beginner.books.chapters.create', $book) }}" class="dxm-btn dxm-btn--primary">+ Add Chapter</a>
@endsection

@section('form')
    @if (session('status'))
        <div class="dxm-alert"><strong>{{ session('status') }}</strong></div>
    @endif

    <div class="chapter-builder-hero chapter-builder-hero--clean">
        <div class="chapter-cover-slot">
            @include('admin.beginner.books.partials-designed-cover', [
                'bookForCover' => $book,
                'coverSize' => 'mini',
                'coverClass' => 'chapter-book-mini'
            ])
        </div>

        <div class="chapter-book-copy">
            <span class="chapter-book-label">Current Book</span>
            <strong>{{ $book->title }}</strong>
            <small>{{ $book->subtitle ?: $book->author_name ?: 'Manual chapter book' }}</small>
            <div class="chapter-hero-tags">
                <span>{{ $chapters->count() }} Chapters</span>
                <span>{{ $chapters->where('status', 'published')->count() }} Published</span>
                <span>{{ ucfirst($book->status) }}</span>
            </div>
        </div>
    </div>

    <div class="dxm-section-card">
        <h3>Table of Contents</h3>
        <p>Arrange chapters using sort order or the move buttons. The frontend reader will use these chapters as the book table of contents.</p>

        @if ($chapters->isEmpty())
            <div class="empty-toc">
                <strong>No chapters yet.</strong>
                <p>Add the first chapter to start building this book.</p>
                <a href="{{ route('admin.beginner.books.chapters.create', $book) }}" class="dxm-btn dxm-btn--primary">Add First Chapter</a>
            </div>
        @else
            <div class="chapter-list">
                @foreach ($chapters as $chapter)
                    @php
                        $plain = trim(strip_tags((string) $chapter->body_html));
                        $words = str_word_count($plain);
                        $minutes = max(1, (int) ceil($words / 180));
                    @endphp

                    <article class="chapter-card">
                        <div class="chapter-index">{{ $loop->iteration }}</div>

                        <div class="chapter-main">
                            <h4>{{ $chapter->title }}</h4>
                            <small>Order {{ $chapter->sort_order }} · {{ ucfirst($chapter->status) }} · {{ $words }} words · {{ $minutes }} min read</small>
                            @if ($chapter->subtitle)
                                <p>{{ $chapter->subtitle }}</p>
                            @endif
                        </div>

                        <div class="chapter-actions">
                            <form method="POST" action="{{ route('admin.beginner.books.chapters.move', [$chapter, 'up']) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="dxm-btn dxm-btn--small" title="Move chapter up">↑</button>
                            </form>

                            <form method="POST" action="{{ route('admin.beginner.books.chapters.move', [$chapter, 'down']) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="dxm-btn dxm-btn--small" title="Move chapter down">↓</button>
                            </form>

                            <a href="{{ route('admin.beginner.books.chapters.edit', $chapter) }}" class="dxm-btn dxm-btn--small">Edit</a>

                            <form method="POST" action="{{ route('admin.beginner.books.chapters.destroy', $chapter) }}" onsubmit="return confirm('Delete this chapter?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dxm-btn dxm-btn--small" style="border-color:rgba(248,113,113,.45);background:rgba(127,29,29,.20);">Delete</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@section('preview')
    <div class="chapter-preview-book-card">
        @include('admin.beginner.books.partials-designed-cover', [
            'bookForCover' => $book,
            'coverSize' => 'small',
            'coverClass' => 'chapter-preview-cover'
        ])

        <div class="dxm-preview__card chapter-preview-copy">
            <div class="dxm-preview__title">{{ $book->title }}</div>
            <div class="dxm-preview__subtitle">{{ $book->subtitle ?: $book->author_name ?: 'Book preview' }}</div>
            <div class="dxm-preview__meta">
                <span class="dxm-tag">{{ $chapters->count() }} Chapters</span>
                <span class="dxm-tag">{{ ucfirst($book->book_type) }}</span>
                <span class="dxm-tag">{{ ucfirst($book->status) }}</span>
            </div>
        </div>
    </div>

    <div class="dxm-preview__card">
        <div class="dxm-preview__title" style="font-size:16px;">Frontend Table of Contents</div>
        <div class="preview-toc">
            @forelse ($chapters as $chapter)
                <div><span>{{ $loop->iteration }}</span><strong>{{ $chapter->title }}</strong></div>
            @empty
                <p>No chapters yet.</p>
            @endforelse
        </div>
    </div>
@endsection

@push('styles')
<style>
.chapter-builder-hero{display:flex;gap:16px;align-items:center;border:1px solid rgba(255,255,255,.10);border-radius:22px;background:linear-gradient(135deg,rgba(15,23,42,.72),rgba(2,6,23,.40));padding:14px;margin-bottom:14px;min-height:128px}.chapter-cover-slot{flex:0 0 auto;display:flex;align-items:center;justify-content:center}.chapter-book-copy{min-width:0;flex:1}.chapter-book-label{display:inline-flex;margin-bottom:6px;color:#67e8f9;font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.12em}.chapter-builder-hero strong{display:block;color:#fff;font-size:20px;line-height:1.1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.chapter-builder-hero small{display:block;color:rgba(255,255,255,.68);margin-top:5px;line-height:1.35}.chapter-hero-tags{display:flex;gap:7px;flex-wrap:wrap;margin-top:11px}.chapter-hero-tags span{font-size:10px;font-weight:900;color:#cffafe;border:1px solid rgba(34,211,238,.28);background:rgba(34,211,238,.08);border-radius:999px;padding:6px 9px}.chapter-list{display:flex;flex-direction:column;gap:10px}.chapter-card{border:1px solid rgba(255,255,255,.10);border-radius:16px;background:rgba(255,255,255,.035);padding:12px;display:grid;grid-template-columns:42px minmax(0,1fr) auto;gap:12px;align-items:flex-start}.chapter-index{width:36px;height:36px;border-radius:12px;background:rgba(34,211,238,.10);border:1px solid rgba(34,211,238,.30);display:grid;place-items:center;color:#cffafe;font-weight:950}.chapter-main h4{margin:0;color:#fff;font-size:14px}.chapter-main p{margin:8px 0 0;color:rgba(255,255,255,.62);font-size:12px}.chapter-actions{display:flex;gap:6px;align-items:flex-start;flex-wrap:wrap}.dxm-btn--small{min-height:32px;padding:6px 10px;font-size:11px;border-radius:10px}.empty-toc{border:1px dashed rgba(255,255,255,.18);border-radius:18px;padding:20px;text-align:center}.empty-toc strong{color:#fff}.empty-toc p{color:rgba(255,255,255,.62)}.preview-toc{display:grid;gap:8px;margin-top:8px}.preview-toc div{display:grid;grid-template-columns:28px minmax(0,1fr);gap:8px;align-items:center;color:#fff}.preview-toc span{width:24px;height:24px;border-radius:8px;background:rgba(34,211,238,.10);display:grid;place-items:center;color:#cffafe;font-size:11px;font-weight:950}.preview-toc p{color:rgba(255,255,255,.62)}.chapter-preview-book-card{display:flex;gap:14px;align-items:stretch;margin-bottom:12px}.chapter-preview-copy{flex:1;margin:0!important;min-width:0}.chapter-preview-cover{align-self:stretch}@media(max-width:760px){.chapter-card{grid-template-columns:36px minmax(0,1fr)}.chapter-actions{grid-column:1 / -1}.chapter-builder-hero{align-items:flex-start;min-height:unset}.chapter-builder-hero strong{white-space:normal}.chapter-preview-book-card{display:block}.chapter-preview-cover{margin:0 auto 12px}}
</style>
@endpush
