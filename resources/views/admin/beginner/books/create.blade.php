@extends('layouts.beginner')

@section('title', 'Create Book')
@section('eyebrow', 'Books & Library')
@section('page_title', 'Create Book')
@section('page_description', 'Create a manual, PDF, external, or compiled book for this app.')
@section('back_url', route('admin.beginner.books.index'))
@section('form_title', 'Book Studio')
@section('form_description', 'Start with the book identity, cover format, type, and publishing settings.')
@section('preview_description', 'Live preview of the book card.')

@section('studio_tabs')
    <button type="button" class="dxm-dock-tab is-active" data-dxm-tab="details">Details</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="media">Cover / Format</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="publishing">Publishing</button>
@endsection

@section('form')
    @include('admin.beginner.books.partials-form', ['mode' => 'create'])
@endsection

@section('preview')
    @include('admin.beginner.books.partials-preview')
@endsection
