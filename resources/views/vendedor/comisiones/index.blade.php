@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Mis comisiones" subtitle="Conciliacion mensual con Atlantia." /><x-data-panel title="Comisiones" :items="$comisiones" /></section>
@endsection

