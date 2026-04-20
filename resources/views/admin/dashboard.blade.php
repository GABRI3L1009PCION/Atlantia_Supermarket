@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8">
    <x-page-header title="Panel administrador" subtitle="Metricas consolidadas de Atlantia Supermarket." />
    <x-data-panel title="Indicadores principales" :items="$metrics" />
</section>
@endsection

