@extends('layouts.app')

@section('title', $page->title.' | '.config('site.name'))
@section('meta_description', $page->meta_description ?? config('site.name'))

@section('content')
    <section class="page-hero">
        <div class="container">
            <span class="crumbs"><a href="{{ route('home') }}" style="color:var(--gold-400)">Home</a> / {{ $page->title }}</span>
            <h1>{{ $page->title }}</h1>
        </div>
    </section>

    <section class="section">
        <div class="container prose">
            {!! $page->content !!}
        </div>
    </section>
@endsection
