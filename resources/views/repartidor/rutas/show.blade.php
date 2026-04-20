@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Ruta de entrega" subtitle="Mapa, tiempos y evidencia de entrega." /><x-data-panel title="Ruta" :items="$ruta" /></section>
@endsection

