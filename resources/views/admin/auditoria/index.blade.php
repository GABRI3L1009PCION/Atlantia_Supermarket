@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Auditoria" subtitle="Registro inmutable de acciones relevantes." /><x-data-panel title="Eventos" :items="$logs" /></section>
@endsection

