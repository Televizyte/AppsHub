@extends('layouts.beginner')

@section('title', 'Edit Chapter')
@section('eyebrow', 'Book Chapter Builder')
@section('page_title', 'Edit Chapter')
@section('page_description', 'Update chapter in: ' . $book->title)
@section('back_url', route('admin.beginner.books.chapters.index', $book))
@section('form_title', 'Chapter Writer')
@section('form_description', 'Write the chapter body and optional study sections.')
@section('preview_description', 'Preview updates as you write.')

@section('studio_tabs')
    <button type="button" class="dxm-dock-tab is-active" data-dxm-tab="content">Content</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="study">Study Tools</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="publishing">Publishing</button>
@endsection

@section('form')
    <div id="designedBookCoverTemplate" style="display:none;">
        @include('admin.beginner.books.partials-designed-cover', [
            'bookForCover' => $book,
            'coverSize' => 'mini',
            'coverClass' => 'book-editor-cover-designed'
        ])
    </div>
    @include('admin.beginner.books.partials-chapter-form', ['mode' => 'edit'])
@endsection

@section('preview')
    @include('admin.beginner.books.partials-chapter-preview')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const target = document.querySelector('.book-editor-cover');
    const template = document.querySelector('#designedBookCoverTemplate .dxm-designed-book-cover');
    if (!target || !template) return;
    const clone = template.cloneNode(true);
    clone.classList.add('book-editor-cover');
    target.replaceWith(clone);
});
</script>
@endpush
