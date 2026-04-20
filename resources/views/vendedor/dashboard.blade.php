@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Panel vendedor" subtitle="Resumen comercial de tu tienda." /><x-data-panel title="Metricas" :items="$metrics" /></section>
@endsection

