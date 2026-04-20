@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Evento de auditoria" subtitle="Detalle del registro append-only." /><x-data-panel title="Registro" :items="$log" /></section>
@endsection

