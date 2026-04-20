@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Panel repartidor" subtitle="Resumen de entregas asignadas." /><x-data-panel title="Metricas" :items="$metrics" /></section>
@endsection

