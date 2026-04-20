@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Mis DTE" subtitle="Facturas emitidas por tu tienda." /><x-data-panel title="DTE" :items="$dtes" /></section>
@endsection

