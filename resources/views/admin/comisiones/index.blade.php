@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Comisiones" subtitle="Conciliacion mensual por vendedor." /><x-data-panel title="Comisiones" :items="$comisiones" /></section>
@endsection

