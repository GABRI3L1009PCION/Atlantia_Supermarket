@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Zonas de entrega" subtitle="Cobertura configurada para tu tienda." /><x-data-panel title="Zonas" :items="$zonas" /></section>
@endsection

