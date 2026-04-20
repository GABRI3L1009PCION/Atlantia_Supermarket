@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Mis productos" subtitle="Catalogo propio del vendedor." /><x-data-panel title="Productos" :items="$productos" /></section>
@endsection

