@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Sugerencias de reabasto" subtitle="Alertas de inventario basadas en demanda." /><x-data-panel title="Sugerencias" :items="$sugerencias" /></section>
@endsection

