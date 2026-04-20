@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Categorias" subtitle="Arbol de categorias del supermercado." /><x-data-panel title="Categorias" :items="$categorias" /></section>
@endsection

