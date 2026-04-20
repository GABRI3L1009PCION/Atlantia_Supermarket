@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Productos" subtitle="Moderacion del catalogo multivendedor." /><x-data-panel title="Productos registrados" :items="$productos" /></section>
@endsection

