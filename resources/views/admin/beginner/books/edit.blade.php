@extends('layouts.beginner')

@section('title', 'Edit Book')
@section('eyebrow', 'Books & Library')
@section('page_title', 'Edit Book')
@section('page_description', 'Update book details, cover format, chapters, access, and publishing status.')
@section('back_url', route('admin.beginner.books.index'))
@section('form_title', 'Book Studio')
@section('form_description', 'This updates the selected book without affecting other apps.')
@section('preview_description', 'Live preview of this book.')

@section('studio_tabs')
    <button type="button" class="dxm-dock-tab is-active" data-dxm-tab="details">Details / TOC</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="media">Cover / Format</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="publishing">Publishing</button>
@endsection

@section('dock_actions')
    <a href="{{ route('admin.beginner.books.chapters.create', $book) }}" class="dxm-btn">+ Chapter</a>
    <a href="{{ route('admin.beginner.books.chapters.index', $book) }}" class="dxm-btn dxm-btn--primary">Chapter Builder</a>
@endsection

@section('form')
    @include('admin.beginner.books.partials-form', ['mode' => 'edit'])
@endsection

@section('preview')
    @include('admin.beginner.books.partials-preview')
@endsection
