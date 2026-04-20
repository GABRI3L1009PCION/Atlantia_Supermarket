@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Rutas" subtitle="Rutas planificadas y activas." /><x-data-panel title="Rutas" :items="$rutas" /></section>
@endsection

