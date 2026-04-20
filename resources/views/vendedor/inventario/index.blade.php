@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Inventario" subtitle="Stock actual, minimo y maximo." /><x-data-panel title="Inventario" :items="$inventario" /></section>
@endsection

