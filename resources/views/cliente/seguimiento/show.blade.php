@extends('layouts.marketplace')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Seguimiento" subtitle="Estado y ruta de tu pedido." /><x-data-panel title="Seguimiento" :items="$seguimiento" /></section>
@endsection

