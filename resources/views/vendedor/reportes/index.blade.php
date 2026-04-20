@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Reportes" subtitle="Ventas y actividad de tu tienda." /><x-data-panel title="Resumen" :items="$reportes" /></section>
@endsection

