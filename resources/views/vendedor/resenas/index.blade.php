@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Resenas recibidas" subtitle="Opiniones de clientes verificados." /><x-data-panel title="Resenas" :items="$resenas" /></section>
@endsection

