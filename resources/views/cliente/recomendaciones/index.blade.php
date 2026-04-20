@extends('layouts.marketplace')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Recomendaciones" subtitle="Productos sugeridos segun tu historial." /><x-data-panel title="Recomendaciones" :items="$recomendaciones" /></section>
@endsection

