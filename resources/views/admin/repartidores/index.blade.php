@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Repartidores" subtitle="Gestion del equipo de entregas." /><x-data-panel title="Repartidores" :items="$repartidores" /></section>
@endsection

