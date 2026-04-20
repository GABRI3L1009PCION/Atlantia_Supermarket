@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Panel empleado" subtitle="Operaciones de soporte, transferencias y moderacion." /><x-data-panel title="Metricas" :items="$metrics" /></section>
@endsection

