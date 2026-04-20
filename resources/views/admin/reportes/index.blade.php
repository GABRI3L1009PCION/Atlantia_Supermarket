@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Reportes" subtitle="Resumen comercial y operativo." /><x-data-panel title="Resumen" :items="$reportes" /></section>
@endsection

