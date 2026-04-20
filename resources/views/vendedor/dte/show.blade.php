@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="DTE {{ $dte->numero_dte }}" subtitle="Detalle fiscal de la factura." /><x-data-panel title="DTE" :items="$dte" /></section>
@endsection

