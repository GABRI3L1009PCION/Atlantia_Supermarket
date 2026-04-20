@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Mensajes de contacto" subtitle="Atencion a clientes y vendedores." /><x-data-panel title="Mensajes" :items="$messages" /></section>
@endsection

